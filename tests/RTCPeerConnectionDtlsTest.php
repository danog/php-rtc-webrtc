<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\SDP\Enum\DtlsRole;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]

    #[UsesClass(\Webrtc\Codecs\Codec::class)]
    #[UsesClass(\Webrtc\Codecs\CodecUtility::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Engine::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Handshake::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\Prf::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCCertificate::class)]
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
    #[UsesClass(\Webrtc\ICE\RTCIceServer::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceTransport::class)]
                #[UsesClass(\Webrtc\ICE\Utils::class)]
        #[UsesClass(\Webrtc\NTP\NetworkTimeProtocol::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpByePacket::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpFeedback::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpParameters::class)]
    #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensionsMap::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterBuffer::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\MediaStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\RTCRtpTransceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\RTCRtpReceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\InterArrival::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateBucket::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateCounter::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RemoteBitrateEstimator::class)]
    #[UsesClass(\Webrtc\RTP\RtpRouter::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCRtpSender::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\AttributeChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\BaseInitChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\Chunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\DataChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\SackChunk::class)]
    #[UsesClass(\Webrtc\SCTP\InboundStream::class)]
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
    #[UsesClass(\Webrtc\Codecs\EncodedPacket::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp8\Vp8Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp8\Vp8PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp9\Vp9Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\Vp9\Vp9PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\Codecs\Video\X264\H264Encoder::class)]
    #[UsesClass(\Webrtc\Codecs\Video\X264\H264PayloadDescriptor::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpReceiverInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpRrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSdesPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSenderInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSourceInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpUtility::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCapabilities::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecCapability::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodingParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionCapability::class)]
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
    #[UsesClass(\Webrtc\RTP\RtpUtility::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCEncodedFrame::class)]
    #[UsesClass(\Webrtc\SCTP\Chunk\BaseParamsChunk::class)]
    #[UsesClass(\Webrtc\SCTP\Param\StreamResetOutgoingParam::class)]
    #[UsesClass(\Webrtc\SCTP\Param\StreamResetResponseParam::class)]
    #[UsesClass(\Webrtc\STUN\Exception\TransactionException::class)]
    #[UsesClass(\Webrtc\Stats\RTCInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCReceivedRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteInboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRemoteOutboundRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCSentRtpStreamStats::class)]
    #[UsesClass(\Webrtc\Stats\RTCStatsReport::class)]
    #[UsesClass(\Webrtc\TURN\Turn::class)]
class RTCPeerConnectionDtlsTest extends RTCPeerConnectionBaseTest
{
    public function testDtlsRoleOfferActpass()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        $pc1States = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);
        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc1->getIceGatheringState());
        $this->assertNull($pc1->getLocalDescription());
        $this->assertNull($pc1->getRemoteDescription());

        $this->assertEquals(IceConnectionState::new, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc2->getIceGatheringState());
        $this->assertNull($pc2->getLocalDescription());
        $this->assertNull($pc2->getRemoteDescription());

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // set remote description
        $pc2->setRemoteDescription($pc1->getLocalDescription());

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertHasDtls($answer, "active");

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        $this->assertEquals(DtlsRole::Server, $pc1->getSctp()->getDtlsTransport()->getRole());
        $this->assertEquals(DtlsRole::Client, $pc2->getSctp()->getDtlsTransport()->getRole());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);

        // check state changes
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc1States["connectionState"]
        );
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
    }

    public function testDtlsRoleOfferPassive()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        $pc1States = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);
        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc1->getIceGatheringState());
        $this->assertNull($pc1->getLocalDescription());
        $this->assertNull($pc1->getRemoteDescription());

        $this->assertEquals(IceConnectionState::new, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc2->getIceGatheringState());
        $this->assertNull($pc2->getLocalDescription());
        $this->assertNull($pc2->getRemoteDescription());

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // handle offer with a replaced DTLS role
        $pc2->setRemoteDescription(new RTCSessionDescription(
            sdp: str_replace("actpass", "passive", $pc1->getLocalDescription()->getSdp()),
            type: "offer"
        ));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertHasDtls($answer, "active");

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // pc1 is explicitly passive so server
        $this->assertEquals(DtlsRole::Server, $pc1->getSctp()->getDtlsTransport()->getRole());
        $this->assertEquals(DtlsRole::Client, $pc2->getSctp()->getDtlsTransport()->getRole());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);

        // check state changes
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc1States["connectionState"]
        );
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
    }

    public function testDtlsRoleOfferActive()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        $pc1States = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);
        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc1->getIceGatheringState());
        $this->assertNull($pc1->getLocalDescription());
        $this->assertNull($pc1->getRemoteDescription());

        $this->assertEquals(IceConnectionState::new, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc2->getIceGatheringState());
        $this->assertNull($pc2->getLocalDescription());
        $this->assertNull($pc2->getRemoteDescription());

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // handle offer with a replaced DTLS role
        $pc2->setRemoteDescription(new RTCSessionDescription(
            sdp: str_replace("actpass", "active", $pc1->getLocalDescription()->getSdp()),
            type: "offer"
        ));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertHasDtls($answer, "passive");

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        delay(1);
        $this->assertIceCompleted($pc1, $pc2);

        // pc1 is explicitly active so client
        $this->assertEquals(DtlsRole::Client, $pc1->getSctp()->getDtlsTransport()->getRole());
        $this->assertEquals(DtlsRole::Server, $pc2->getSctp()->getDtlsTransport()->getRole());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);

        // check state changes
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc1States["connectionState"]
        );
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
    }

    public function testRightMidOrder()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        $tr1A = $pc1->addTransceiver(MediaKind::Video, SDPDirections::recvonly);
        $tr1B = $pc1->addTransceiver(MediaKind::Video, SDPDirections::recvonly);
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());

        $pc1->setLocalDescription($offer);

        $tr2A = $pc2->addTransceiver(new PreEncodedVideoStreamTrack());
        $tr2B = $pc2->addTransceiver(new PreEncodedVideoStreamTrack());
        $pc2->setRemoteDescription($offer);

        $this->assertEquals($tr1A->getMid(), $tr2A->getMid());
        $this->assertEquals($tr1B->getMid(), $tr2B->getMid());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);
    }
}
