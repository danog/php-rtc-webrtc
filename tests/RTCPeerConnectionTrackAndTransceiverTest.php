<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Webrtc\Exception\InvalidArgumentException;
use Webrtc\Exception\RuntimeException;
use Webrtc\ICE\Enum\CandidateType;
use Webrtc\ICE\Enum\TransportType;
use Webrtc\ICE\RTCIceCandidate;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]

    #[UsesClass(\Webrtc\DTLS\DTLS\Engine::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCCertificate::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCDtlsTransport::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RecordLayer::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Srtp::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\TLS\TLS::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\BIO::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\Context::class)]
    #[UsesClass(\Webrtc\DTLS\SSL\SSL::class)]
    #[UsesClass(\Webrtc\ICE\IceProtocolParser::class)]
    #[UsesClass(\Webrtc\ICE\RTCICESetting::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceCandidate::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceConnection::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceGatherer::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceProtocolConfiguration::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceServer::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceTransport::class)]
    #[UsesClass(\Webrtc\ICE\Utils::class)]
        #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensionsMap::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterBuffer::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\MediaStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\RTCRtpTransceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\RTCRtpReceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\InterArrival::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateBucket::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateCounter::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RemoteBitrateEstimator::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCRtpSender::class)]
    #[UsesClass(\Webrtc\Srtp\Policy::class)]
    #[UsesClass(\Webrtc\Stats\RTCStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCTransportStats::class)]
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
    #[UsesClass(\Webrtc\DTLS\DTLS\Handshake::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Prf::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Reader::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\TLS\Handshake::class)]
    #[UsesClass(\Webrtc\DataChannel\RTCDataChannel::class)]
    #[UsesClass(\Webrtc\DataChannel\RTCDataChannelParameters::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceCandidatePair::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceParameters::class)]
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
    #[UsesClass(\Webrtc\RTP\Jitter\JitterFrame::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RTCTrackEvent::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\NackGenerator::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\AimdRateControl::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\OveruseDetector::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\TimestampGroup::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\StreamStatistics::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\TimestampMapper::class)]
    #[UsesClass(\Webrtc\RTP\RtpPacket::class)]
    #[UsesClass(\Webrtc\RTP\RtpRouter::class)]
    #[UsesClass(\Webrtc\RTP\RtpUtility::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCEncodedFrame::class)]
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
    #[UsesClass(\Webrtc\Srtp\Session::class)]
    #[UsesClass(\Webrtc\Stats\RTCInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCReceivedRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCSentRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCStatsReport::class)]
    #[UsesClass(\Webrtc\TURN\Turn::class)]
class RTCPeerConnectionTrackAndTransceiverTest extends TestCase
{
    public function testAddIceCandidateNoSdpMidOrSdpMLineIndex()
    {
        $pc = new RTCPeerConnection();

        $candidate = new RTCIceCandidate(1);
        $candidate->setFoundation("0");
        $candidate->setHost("192.168.1.2");
        $candidate->setPort(33562);
        $candidate->setPriority(1256445);
        $candidate->setTransport(TransportType::udp);
        $candidate->setType(CandidateType::host);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Candidate must have either sdpMid or sdpMLineIndex");
        $pc->addIceCandidate($candidate);
    }

    public function testAddTrackAudio()
    {
        $pc = new RTCPeerConnection();

        // add audio track
        $track1 = new PreEncodedAudioStreamTrack();
        $sender1 = $pc->addTrack($track1);
        $this->assertNotNull($sender1);
        $this->assertEquals($track1, $sender1->getTrack());
        $this->assertEquals([$sender1], $pc->getSenders());
        $this->assertCount(1, $pc->getTransceivers());

        // add another audio track
        $track2 = new PreEncodedAudioStreamTrack();
        $sender2 = $pc->addTrack($track2);
        $this->assertNotNull($sender2);
        $this->assertEquals($track2, $sender2->getTrack());
        $this->assertEquals([$sender1, $sender2], $pc->getSenders());
        $this->assertCount(2, $pc->getTransceivers());

        // try to add same track again
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Track already has a sender");
        $pc->addTrack($track1);
    }

    public function testAddTrackInvalid()
    {
        $pc = new RTCPeerConnection();

        $wrongMediaTrack = new class extends MediaStreamTrack {
            public function __construct()
            {
                parent::__construct(MediaKind::Unknown);
            }
        };

        // try adding an invalid track
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid track kind unknown');
        $pc->addTrack($wrongMediaTrack);
    }

    public function testAddTrackVideo()
    {
        $pc = new RTCPeerConnection();

        // add video track
        $videoTrack1 = new PreEncodedVideoStreamTrack();
        $videoSender1 = $pc->addTrack($videoTrack1);
        $this->assertNotNull($videoSender1);
        $this->assertEquals($videoTrack1, $videoSender1->getTrack());
        $this->assertEquals([$videoSender1], $pc->getSenders());
        $this->assertCount(1, $pc->getTransceivers());

        // add another video track
        $videoTrack2 = new PreEncodedVideoStreamTrack();
        $videoSender2 = $pc->addTrack($videoTrack2);
        $this->assertNotNull($videoSender2);
        $this->assertEquals($videoTrack2, $videoSender2->getTrack());
        $this->assertEquals([$videoSender1, $videoSender2], $pc->getSenders());
        $this->assertCount(2, $pc->getTransceivers());

        // add audio track
        $audioTrack = new PreEncodedAudioStreamTrack();
        $audioSender = $pc->addTrack($audioTrack);
        $this->assertNotNull($audioSender);
        $this->assertEquals($audioTrack, $audioSender->getTrack());
        $this->assertEquals([$videoSender1, $videoSender2, $audioSender], $pc->getSenders());
        $this->assertCount(3, $pc->getTransceivers());

        // try to add same track again
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Track already has a sender");
        $pc->addTrack($videoTrack1);
    }

    public function testAddTrackClosed()
    {
        $pc = new RTCPeerConnection();
        $pc->close();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("RTCPeerConnection is closed");
        $pc->addTrack(new PreEncodedAudioStreamTrack());
    }

    public function testAddTransceiverAudioInactive()
    {
        $pc = new RTCPeerConnection();

        // add transceiver
        $transceiver = $pc->addTransceiver(MediaKind::Audio, SDPDirections::inactive);
        $this->assertNotNull($transceiver);
        $this->assertNull($transceiver->getCurrentDirection());
        $this->assertEquals(SDPDirections::inactive, $transceiver->getDirection());
        $this->assertNull($transceiver->getSender()->getTrack());
        $this->assertFalse($transceiver->isStopped());
        $this->assertEquals([$transceiver->getSender()], $pc->getSenders());
        $this->assertCount(1, $pc->getTransceivers());

        // add track
        $track = new PreEncodedAudioStreamTrack();
        $pc->addTrack($track);
        $this->assertNull($transceiver->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendonly, $transceiver->getDirection());
        $this->assertEquals($track, $transceiver->getSender()->getTrack());
        $this->assertFalse($transceiver->isStopped());
        $this->assertCount(1, $pc->getTransceivers());

        // stop transceiver
        $transceiver->stop();
        $this->assertNull($transceiver->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendonly, $transceiver->getDirection());
        $this->assertEquals($track, $transceiver->getSender()->getTrack());
        $this->assertTrue($transceiver->isStopped());
    }

    public function testAddTransceiverAudioSendrecv()
    {
        $pc = new RTCPeerConnection();

        // add transceiver
        $transceiver = $pc->addTransceiver(MediaKind::Audio);
        $this->assertNotNull($transceiver);
        $this->assertNull($transceiver->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $transceiver->getDirection());
        $this->assertNull($transceiver->getSender()->getTrack());
        $this->assertFalse($transceiver->isStopped());
        $this->assertEquals([$transceiver->getSender()], $pc->getSenders());
        $this->assertCount(1, $pc->getTransceivers());

        // add track
        $track = new PreEncodedAudioStreamTrack();
        $pc->addTrack($track);
        $this->assertNull($transceiver->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $transceiver->getDirection());
        $this->assertEquals($track, $transceiver->getSender()->getTrack());
        $this->assertFalse($transceiver->isStopped());
        $this->assertCount(1, $pc->getTransceivers());
    }

    public function testAddTransceiverAudioTrack()
    {
        $pc = new RTCPeerConnection();

        // add audio track
        $track1 = new PreEncodedAudioStreamTrack();
        $transceiver1 = $pc->addTransceiver($track1);
        $this->assertNotNull($transceiver1);
        $this->assertNull($transceiver1->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $transceiver1->getDirection());
        $this->assertEquals($track1, $transceiver1->getSender()->getTrack());
        $this->assertFalse($transceiver1->isStopped());
        $this->assertEquals([$transceiver1->getSender()], $pc->getSenders());
        $this->assertCount(1, $pc->getTransceivers());

        // add another audio track
        $track2 = new PreEncodedAudioStreamTrack();
        $transceiver2 = $pc->addTransceiver($track2);
        $this->assertNotNull($transceiver2);
        $this->assertNull($transceiver2->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $transceiver2->getDirection());
        $this->assertEquals($track2, $transceiver2->getSender()->getTrack());
        $this->assertFalse($transceiver2->isStopped());
        $this->assertEquals([$transceiver1->getSender(), $transceiver2->getSender()], $pc->getSenders());
        $this->assertCount(2, $pc->getTransceivers());

        // try to add same track again
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Track already has a sender");
        $pc->addTransceiver($track1);
    }

    public function testClose()
    {
        $pcStates = [];
        $pc = new RTCPeerConnection();
        RTCPeerConnectionHelper::trackStates($pc, $pcStates);

        // close twice
        $pc->close();
        $pc->close();

        $this->assertEquals([SignalingState::stable, SignalingState::closed], $pcStates["signalingState"]);
    }
}
