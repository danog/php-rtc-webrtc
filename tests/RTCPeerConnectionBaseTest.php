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

    #[UsesClass(\Webrtc\DTLS\DTLS\RTCCertificate::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceServer::class)]
    #[UsesClass(\Webrtc\AVCodec\AVCodec::class)]
    #[UsesClass(\Webrtc\AVCodec\AVFilter::class)]
    #[UsesClass(\Webrtc\AVCodec\AVFormat::class)]
    #[UsesClass(\Webrtc\AVCodec\Audio\AudioLayout::class)]
    #[UsesClass(\Webrtc\AVCodec\Codec::class)]
    #[UsesClass(\Webrtc\AVCodec\Context\AudioContext::class)]
    #[UsesClass(\Webrtc\AVCodec\Context\Context::class)]
    #[UsesClass(\Webrtc\AVCodec\Context\Dictionary::class)]
    #[UsesClass(\Webrtc\AVCodec\Format\AudioFormat::class)]
    #[UsesClass(\Webrtc\AVCodec\TransCoder::class)]
    #[UsesClass(\Webrtc\Codecs\Audio\Opus\OpusEncoder::class)]
    #[UsesClass(\Webrtc\Codecs\Audio\PCM\PCMEncoder::class)]
    #[UsesClass(\Webrtc\Codecs\Audio\PCM\PCMuEncoder::class)]
    #[UsesClass(\Webrtc\Codecs\Codec::class)]
    #[UsesClass(\Webrtc\Codecs\CodecUtility::class)]
    #[UsesClass(\Webrtc\Codecs\EncodedPacket::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp8\Vp8Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp8\Vp8PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp9\Vp9Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp9\Vp9PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\Codecs\Video\X264\H264Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\X264\H264PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Engine::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Handshake::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Prf::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCDtlsTransport::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Reader::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RecordLayer::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Srtp::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\TLS\Handshake::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\TLS\TLS::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\BIO::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\Context::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\SSL::class)]
    #[UsesClass(\Webrtc\DataChannel\RTCDataChannel::class)]
    #[UsesClass(\Webrtc\DataChannel\RTCDataChannelParameters::class)]
    #[UsesClass(\Webrtc\ICE\IceProtocolParser::class)]
    #[UsesClass(\Webrtc\ICE\RTCICESetting::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceCandidate::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceCandidatePair::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceConnection::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceGatherer::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceParameters::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceProtocolConfiguration::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceTransport::class)]
    #[UsesClass(\Webrtc\ICE\Utils::class)]
    #[UsesClass(\Webrtc\NTP\NetworkTimeProtocol::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpByePacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpReceiverInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpRrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSdesPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSenderInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSourceInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpUtility::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpFeedback::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCapabilities::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecCapability::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodingParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionCapability::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpReceiveParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpRtxParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpSendParameters::class)]
    #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensions::class)]
    #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensionsMap::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterBuffer::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterFrame::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\MediaStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RTCTrackEvent::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\RTCRtpTransceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\NackGenerator::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\RTCRtpReceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\AimdRateControl::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\InterArrival::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\OveruseDetector::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateBucket::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateCounter::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RemoteBitrateEstimator::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\TimestampGroup::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\StreamStatistics::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\TimestampMapper::class)]
    #[UsesClass(\Webrtc\RTP\RtpPacket::class)]
    #[UsesClass(\Webrtc\RTP\RtpRouter::class)]
    #[UsesClass(\Webrtc\RTP\RtpUtility::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCEncodedFrame::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCRtpSender::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\AttributeChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\BaseInitChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\BaseParamsChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\Chunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\DataChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\SackChunk::class)]
    #[UsesClass(\Webrtc\SCTP\InboundStream::class)]
    #[UsesClass(\Webrtc\SCTP\Param\StreamResetOutgoingParam::class)]
    #[UsesClass(\Webrtc\SCTP\Param\StreamResetResponseParam::class)]
    #[UsesClass(\Webrtc\SCTP\RTCSctpTransport::class)]
    #[UsesClass(\Webrtc\SCTP\SctpPacket::class)]
    #[UsesClass(\Webrtc\SCTP\SctpTimer::class)]
    #[UsesClass(\Webrtc\SCTP\SctpUtility::class)]
    #[UsesClass(\Webrtc\SDP\BitPattern::class)]
    #[UsesClass(\Webrtc\SDP\DtlsParameter\RTCDtlsFingerprint::class)]
    #[UsesClass(\Webrtc\SDP\DtlsParameter\RTCDtlsParameters::class)]
    #[UsesClass(\Webrtc\SDP\Enum\SDPDirections::class)]
    #[UsesClass(\Webrtc\SDP\GroupDescription::class)]
    #[UsesClass(\Webrtc\SDP\H264Sdp::class)]
    #[UsesClass(\Webrtc\SDP\MediaDescription::class)]
    #[UsesClass(\Webrtc\SDP\RTCSessionDescription::class)]
    #[UsesClass(\Webrtc\SDP\SDPUtility::class)]
    #[UsesClass(\Webrtc\SDP\SctpParameter\RTCSctpCapabilities::class)]
    #[UsesClass(\Webrtc\SDP\SessionDescription::class)]
    #[UsesClass(\Webrtc\SDP\SsrcDescription::class)]
    #[UsesClass(\Webrtc\STUN\Datagram::class)]
    #[UsesClass(\Webrtc\STUN\Enum\MessageAttribute::class)]
    #[UsesClass(\Webrtc\STUN\Exception\TransactionException::class)]
    #[UsesClass(\Webrtc\STUN\Exception\TransactionTimeoutException::class)]
    #[UsesClass(\Webrtc\STUN\Message\Message::class)]
    #[UsesClass(\Webrtc\STUN\Message\MessageAttributeCollection::class)]
    #[UsesClass(\Webrtc\STUN\Message\MessageAttributeEncoder::class)]
    #[UsesClass(\Webrtc\STUN\Message\MessageIntegrity::class)]
    #[UsesClass(\Webrtc\STUN\Stun::class)]
    #[UsesClass(\Webrtc\STUN\Transaction::class)]
    #[UsesClass(\Webrtc\STUN\Utils::class)]
    #[UsesClass(\Webrtc\Srtp\Policy::class)]
    #[UsesClass(\Webrtc\Srtp\Session::class)]
    #[UsesClass(\Webrtc\Stats\RTCInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCReceivedRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCSentRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCStatsReport::class)]
    #[UsesClass(\Webrtc\Stats\RTCTransportStats::class)]
    #[UsesClass(\Webrtc\TURN\Turn::class)]
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
