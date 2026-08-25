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
    protected const H264_SDP = "a=rtpmap:99 H264/90000\r\n" .
    "a=rtcp-fb:99 nack\r\n" .
    "a=rtcp-fb:99 nack pli\r\n" .
    "a=rtcp-fb:99 goog-remb\r\n" .
    "a=fmtp:99 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42001f\r\n" .
    "a=rtpmap:100 rtx/90000\r\n" .
    "a=fmtp:100 apt=99\r\n" .
    "a=rtpmap:101 H264/90000\r\n" .
    "a=rtcp-fb:101 nack\r\n" .
    "a=rtcp-fb:101 nack pli\r\n" .
    "a=rtcp-fb:101 goog-remb\r\n" .
    "a=fmtp:101 level-asymmetry-allowed=1;packetization-mode=1;profile-level-id=42e01f\r\n" .
    "a=rtpmap:102 rtx/90000\r\n" .
    "a=fmtp:102 apt=101\r\n";

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
        delay(.1);
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
        delay(.1);
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
        delay(.1);
        $this->assertEquals(State::Closed, $dc->getReadyState());
    }

    protected function asyncSleep(float $seconds): void
    {
        delay($seconds);
    }
}
