<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;
use Webrtc\DataChannel\Enum\State;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]
class RTCPeerConnectionBaseTest extends TestCase
{
    protected const H264_SDP = "a=rtpmap:101 H264/90000\r\n" .
    "a=rtcp-fb:101 nack\r\n" .
    "a=rtcp-fb:101 nack pli\r\n" .
    "a=rtcp-fb:101 goog-remb\r\n" .
    "a=fmtp:101 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\r\n" .
    "a=rtpmap:102 rtx/90000\r\n" .
    "a=fmtp:102 apt=101\r\n" .
    "a=rtpmap:103 H264/90000\r\n" .
    "a=rtcp-fb:103 nack\r\n" .
    "a=rtcp-fb:103 nack pli\r\n" .
    "a=rtcp-fb:103 goog-remb\r\n" .
    "a=fmtp:103 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\r\n" .
    "a=rtpmap:104 rtx/90000\r\n" .
    "a=fmtp:104 apt=103\r\n";

    // VP8 SDP parameters
    protected const VP8_SDP = "a=rtpmap:97 VP8/90000\r\n" .
    "a=rtcp-fb:97 nack\r\n" .
    "a=rtcp-fb:97 nack pli\r\n" .
    "a=rtcp-fb:97 goog-remb\r\n" .
    "a=rtpmap:98 rtx/90000\r\n" .
    "a=fmtp:98 apt=97\r\n";

    protected RTCPeerConnection $pc;
    protected string $longData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pc = RTCPeerConnectionHelper::createPeerConnection();
        $this->longData = str_repeat("\xff", 2000);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

    }

    public function testConstruct(): void
    {
        $this->assertInstanceOf(RTCPeerConnection::class, $this->pc);
    }

    protected function assertBundled(RTCPeerConnection $pc): void
    {
        $transceivers = $pc->getTransceivers();
        $this->assertEquals(
            $transceivers[0]->getReceiver()->getTransport(),
            $transceivers[0]->getSender()->getTransport()
        );
        $transport = $transceivers[0]->getReceiver()->getTransport();
        for ($i = 1; $i < count($transceivers); $i++) {
            $this->assertEquals($transceivers[$i]->getReceiver()->getTransport(), $transport);
            $this->assertEquals($transceivers[$i]->getSender()->getTransport(), $transport);
        }
        if ($pc->getSctp()) {
            $this->assertEquals($pc->getSctp()->getDtlsTransport(), $transport);
        }
    }

    protected function assertClosed(RTCPeerConnection $pc): void
    {
        $this->assertEquals(ConnectionState::closed, $pc->getConnectionState());
        $this->assertEquals(IceConnectionState::closed, $pc->getIceConnectionState());
        $this->assertEquals(SignalingState::closed, $pc->getSignalingState());
    }

    protected function assertDataChannelOpen(RTCDataChannel $dc): void
    {
        $this->waitUntil(fn() => $dc->getReadyState() === State::Open);
        $this->assertEquals(State::Open, $dc->getReadyState());
    }

    protected function assertIceChecking(RTCPeerConnection $pc): void
    {
        delay(.01);
        $this->assertEquals(IceConnectionState::checking, $pc->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc->getIceGatheringState());
    }

    protected function assertIceCompleted(RTCPeerConnection $pc1, RTCPeerConnection $pc2): void
    {
        $this->waitUntil(fn() =>
            $pc1->getIceConnectionState() === IceConnectionState::completed
            && $pc2->getIceConnectionState() === IceConnectionState::completed
            && $pc1->getConnectionState() === ConnectionState::connected
            && $pc2->getConnectionState() === ConnectionState::connected
        );
        $this->assertEquals(IceConnectionState::completed, $pc1->getIceConnectionState());
        $this->assertEquals(IceConnectionState::completed, $pc2->getIceConnectionState());
    }

    protected function assertHasIceCandidates(RTCSessionDescription $description): void
    {
        $this->assertStringContainsString("a=candidate:", $description->getSdp());
        $this->assertStringContainsString("a=end-of-candidates", $description->getSdp());
    }

    protected function assertHasDtls(RTCSessionDescription $description, string $setup): void
    {
        $this->assertStringContainsString("a=fingerprint:sha-256", $description->getSdp());
        preg_match_all("/a=setup:(.*)\r$/", $description->getSdp(), $matches);
        $this->assertEquals([$setup], array_unique($matches[1]));
    }

    protected function closeDataChannel(RTCDataChannel $dc): void
    {
        $dc->close();
        $this->waitUntil(fn() => $dc->getReadyState() === State::Closed);
        $this->assertEquals(State::Closed, $dc->getReadyState());
    }

    protected function waitUntil(callable $condition, float $timeout = 5.0): void
    {
        $deadline = microtime(true) + $timeout;
        while (!$condition() && microtime(true) < $deadline) {
            delay(.01);
        }
    }

    protected function asyncSleep(float $seconds): void
    {
        delay($seconds);
    }
}
