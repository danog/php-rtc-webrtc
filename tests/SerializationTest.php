<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversNothing;
use ReflectionProperty;
use WeakReference;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

/**
 * Full-stack proof that a live, connected RTCPeerConnection survives a
 * serialize => destroy => unserialize cycle and resumes activity.
 *
 * "Destroy" is the real thing: the peer is serialized, then its last reference is dropped and the
 * cycle collector is run to completion — no close(). Nothing in the graph (ICE/DTLS/SCTP sockets
 * and timers) may pin itself in the event loop, or the peer could never be reclaimed and its UDP
 * ports would leak. The far peer is left untouched throughout; the restored peer rebinds the same
 * ports and keeps the ICE/DTLS/SCTP/data-channel session alive so data still flows both ways.
 *
 * Only the library's own (serializable) wiring is serialized: application event handlers are
 * closures, which PHP cannot serialize, so the app re-attaches them after unserialize — which is
 * exactly what this test does to the restored data channel.
 */
#[CoversNothing]
final class SerializationTest extends RTCPeerConnectionBaseTest
{
    public function testConnectedDataChannelResumesAfterSerializeCycle(): void
    {
        // pc1 is the peer that gets serialized: build it raw so no test closure lands in its graph.
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        // pc2 records what it receives on the data channel and keeps a handle to reply later.
        $pc2Received = [];
        $pc2Channel = null;
        $pc2->on('datachannel', function (RTCDataChannel $channel) use (&$pc2Received, &$pc2Channel): void {
            $pc2Channel = $channel;
            $channel->on('message', function ($message) use (&$pc2Received): void {
                $pc2Received[] = $message;
            });
        });

        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: 'chat'));

        // Connect the two peers.
        $pc1->setLocalDescription($pc1->createOffer());
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->setLocalDescription($pc2->createAnswer());
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        $this->assertIceCompleted($pc1, $pc2);
        $this->assertDataChannelOpen($dc);

        // Confirm the live channel works before the cycle (observed on the far, un-serialized peer).
        $dc->send('before');
        $this->waitUntil(fn () => $pc2Received === ['before']);
        $this->assertSame(['before'], $pc2Received);

        // Let consent/transaction traffic settle so no transient fiber is mid-flight at unset.
        delay(0.2);

        // serialize => destroy (unset + gc, no close) => unserialize on pc1 while pc2 stays live.
        // Weakly track the nominated UDP protocols before dropping the peer: reclaiming the peer is
        // not instantaneous (see reclaimPeer()), and the restored peer must not rebind a port until
        // its previous socket is truly gone.
        $blob = serialize($pc1);
        $weak = WeakReference::create($pc1);
        $protocols = $this->nominatedProtocols($pc1);
        unset($pc1, $dc);

        $this->reclaimPeer($protocols);

        $this->assertNull($weak->get(), 'the connected peer was pinned and not garbage-collected after unset');
        foreach ($protocols as $protocol) {
            $this->assertNull($protocol->get(), 'a nominated UDP socket was pinned and not released after unset');
        }

        $restored = unserialize($blob);
        $this->assertInstanceOf(RTCPeerConnection::class, $restored);

        // The restored peer still holds an open data channel; the app re-attaches its handler.
        $sctp = $restored->getSctp();
        $this->assertNotNull($sctp);
        $channels = (new ReflectionProperty($sctp, 'dataChannels'))->getValue($sctp);
        $this->assertIsArray($channels);
        $this->assertCount(1, $channels);
        $restoredDc = array_values($channels)[0];
        $this->assertInstanceOf(RTCDataChannel::class, $restoredDc);
        $restoredReceived = [];
        $restoredDc->on('message', function ($message) use (&$restoredReceived): void {
            $restoredReceived[] = $message;
        });
        $this->assertDataChannelOpen($restoredDc);
        $this->assertNotNull($pc2Channel);

        // Activity resumes over the rebound sockets in both directions: the restored peer sends
        // application data on the resumed data channel and the untouched live peer receives it...
        $restoredDc->send('after');
        $this->waitUntil(fn () => $pc2Received === ['before', 'after'], 15.0);
        $this->assertSame(['before', 'after'], $pc2Received);

        // ...and the live peer sends back, which the restored peer receives on the same channel.
        $pc2Channel->send('reply');
        $this->waitUntil(fn () => $restoredReceived === ['reply'], 15.0);
        $this->assertSame(['reply'], $restoredReceived);

        $restored->close();
        $pc2->close();
    }

    /**
     * Fully reclaim a just-unset peer, releasing every UDP socket it held, before it is restored.
     *
     * A live ICE connection runs consent-freshness checks (RFC 7675): onConsentTimer() fires a
     * detached async() fiber that suspends inside a STUN request(). A suspended fiber keeps its
     * whole call stack — the candidate pair, its protocol and the bound UDP socket — reachable
     * however weakly the closure captured them, so the cycle collector cannot reclaim that socket
     * while a check is in flight. Left alone the socket would linger and be re-bound (SO_REUSEPORT)
     * by the restored peer, and the kernel could then route the far peer's datagrams to the dead
     * socket. A real process exit tears those fibers down; here we do the equivalent.
     *
     * The order matters. First collect, which frees the peer (its consent repeat-timer holds only a
     * weak reference, so it self-cancels on its next tick and starts no new checks). Then run the
     * loop so any check still in flight receives its STUN response and returns, dropping the last
     * reference to its socket; the socket's __destruct fclose()s the port synchronously. A missed
     * response waits a retransmit, so this alternates draining and collecting until every tracked
     * protocol is gone rather than draining for a fixed guess — bounded so a genuinely stuck fiber
     * fails the assertion below instead of hanging.
     *
     * @param list<WeakReference<object>> $protocols
     */
    private function reclaimPeer(array $protocols): void
    {
        $deadline = microtime(true) + 5.0;
        do {
            while (gc_collect_cycles()) {
            }
            $pinned = false;
            foreach ($protocols as $protocol) {
                if ($protocol->get() !== null) {
                    $pinned = true;
                    break;
                }
            }
            if (!$pinned) {
                return;
            }
            // Let the loop actually service the sockets: a bare queue()+await only runs
            // already-ready callbacks, so the STUN response the parked fiber awaits may not have
            // arrived. Timed delays block on I/O, the far peer answers, the fiber unwinds.
            for ($i = 0; $i < 5; $i++) {
                delay(0.05);
            }
        } while (microtime(true) < $deadline);
    }

    /**
     * Weakly reference the UDP protocol object behind each nominated candidate pair.
     *
     * These are the objects whose sockets hold the bound ports; tracking them weakly lets
     * reclaimPeer() wait for the ports to be released without keeping them alive itself.
     *
     * @return list<WeakReference<object>>
     */
    private function nominatedProtocols(RTCPeerConnection $pc): array
    {
        $protocols = [];
        $transports = (new ReflectionProperty($pc, 'iceTransports'))->getValue($pc);
        foreach ((array) $transports as $transport) {
            $connection = $transport->getIceConnection();
            $nominated = (new ReflectionProperty($connection, 'nominated'))->getValue($connection);
            foreach ((array) $nominated as $pair) {
                $protocols[] = WeakReference::create($pair->getProtocol());
            }
        }

        return $protocols;
    }
}
