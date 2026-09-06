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
        if (PHP_OS_FAMILY === 'Windows') {
            // A resumed peer keeps the session alive by continuing ICE binding checks over the
            // rebound UDP socket. Windows' SIO_UDP_CONNRESET tears a UDP socket down on the first
            // datagram to a momentarily-unreachable peer, so a connection re-established after the
            // cycle is unreliable there — the same limitation that keeps the ICE Windows suite red
            // and cannot be worked around from PHP. Linux and macOS run this in full.
            self::markTestSkipped('Resumed UDP connections are unreliable on Windows (SIO_UDP_CONNRESET).');
        }

        // pc1 is the peer that gets serialized: build it raw so no test closure lands in its graph.
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        $pc2Received = [];
        $pc2->on('datachannel', function (RTCDataChannel $channel) use (&$pc2Received): void {
            $channel->on('message', function ($message) use ($channel, &$pc2Received): void {
                $pc2Received[] = $message;
                $channel->send('echo:' . $message);
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
        $blob = serialize($pc1);
        $weak = WeakReference::create($pc1);
        unset($pc1, $dc);
        while (gc_collect_cycles()) {
        }

        $this->assertNull($weak->get(), 'the connected peer was pinned and not garbage-collected after unset');

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

        // Activity resumes: data flows both ways over the resumed association.
        $restoredDc->send('after');
        $this->waitUntil(fn () => $restoredReceived === ['echo:after'], 10.0);
        $this->assertSame(['before', 'after'], $pc2Received);
        $this->assertSame(['echo:after'], $restoredReceived);

        $restored->close();
        $pc2->close();
    }
}
