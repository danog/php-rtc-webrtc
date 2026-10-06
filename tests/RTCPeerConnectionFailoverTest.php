<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Webrtc\ICE\Listener\IceConnectionDataListener;
use Webrtc\ICE\RTCIceConnection;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\RTCPeerConnection;

use function Amp\delay;

/**
 * A call keeps going when the network path it uses stops working, on another one.
 */
#[CoversClass(RTCPeerConnection::class)]
final class RTCPeerConnectionFailoverTest extends TestCase
{
    private static function iceConnection(RTCPeerConnection $pc): RTCIceConnection
    {
        $connection = $pc->getTransceivers()[0]->getDtlsTransport()->getIceTransport()->getIceConnection();
        self::assertInstanceOf(RTCIceConnection::class, $connection);
        return $connection;
    }

    private static function validPairs(RTCIceConnection $connection): int
    {
        return \count((new ReflectionMethod(RTCIceConnection::class, 'getValidPairs'))->invoke($connection, 1));
    }

    private static function waitFor(\Closure $condition, float $timeout): bool
    {
        $deadline = microtime(true) + $timeout;
        while (!$condition()) {
            if (microtime(true) > $deadline) {
                return false;
            }
            delay(0.1);
        }
        return true;
    }

    public function testMediaContinuesWhenTheSelectedPathStops(): void
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $pc2->addTrack(new PreEncodedAudioStreamTrack());

        $pc1->setLocalDescription($pc1->createOffer());
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->setLocalDescription($pc2->createAnswer());
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertTrue(self::waitFor(
            static fn () => $pc1->getConnectionState() === ConnectionState::connected && $pc2->getConnectionState() === ConnectionState::connected,
            10
        ), 'Not connected');

        $ice1 = self::iceConnection($pc1);
        $ice2 = self::iceConnection($pc2);
        $ice1->setTimeScale(0.1);
        $ice2->setTimeScale(0.1);
        // The keepalives validate the pairs that were not checked when ICE completed: they're the backups.
        if (!self::waitFor(static fn () => self::validPairs($ice1) >= 2 && self::validPairs($ice2) >= 2, 10)) {
            $pc1->close();
            $pc2->close();
            $this->markTestSkipped('Needs at least two network paths between the peers');
        }

        // Media datagrams arriving at the second peer.
        $listener = new class implements IceConnectionDataListener {
            public int $received = 0;

            public function onIceConnectionData(string $data, int $componentId): void
            {
                $this->received++;
            }
        };
        $ice2->addDataListener($listener);

        // As if the network interface of the selected path went down, mid-call.
        $selected = $ice1->getNominated()[1];
        $selected->getProtocol()->close();
        $this->assertTrue(self::waitFor(static fn () => $ice1->getNominated()[1] !== $selected, 5), 'No switch to a backup path');

        $listener->received = 0;
        // Audio is sent about once a second.
        delay(4);
        $this->assertGreaterThanOrEqual(3, $listener->received, 'The media stopped');
        $this->assertSame(ConnectionState::connected, $pc1->getConnectionState());
        $this->assertSame(ConnectionState::connected, $pc2->getConnectionState());

        $pc1->close();
        $pc2->close();
    }
}
