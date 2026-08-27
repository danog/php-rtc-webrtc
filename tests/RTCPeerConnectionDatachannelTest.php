<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\DataChannel\Enum\State;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\Exception\InvalidArgumentException;
use Webrtc\Exception\RuntimeException;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\SCTP\SctpUtility;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]

    #[UsesClass(\Webrtc\Codecs\Audio\Opus\OpusEncoder::class)]
    #[UsesClass(\Webrtc\Codecs\Codec::class)]
    #[UsesClass(\Webrtc\Codecs\CodecUtility::class)]
    #[UsesClass(\Webrtc\Codecs\EncodedPacket::class)]
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
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodingParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpReceiveParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpSendParameters::class)]
    #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensions::class)]
    #[UsesClass(\Webrtc\RTP\HeaderExtension\HeaderExtensionsMap::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterBuffer::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\MediaStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RTCTrackEvent::class)]
    #[UsesClass(\Webrtc\RTP\RTCRtpTransceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\RTCRtpReceiver::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\StreamStatistics::class)]
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
        #[UsesClass(\Webrtc\SDP\DtlsParameter\RTCDtlsFingerprint::class)]
    #[UsesClass(\Webrtc\SDP\DtlsParameter\RTCDtlsParameters::class)]
    #[UsesClass(\Webrtc\SDP\Enum\SDPDirections::class)]
    #[UsesClass(\Webrtc\SDP\GroupDescription::class)]
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
    #[UsesClass(\Webrtc\Codecs\Audio\PCM\PCMEncoder::class)]
    #[UsesClass(\Webrtc\Codecs\Audio\PCM\PCMuEncoder::class)]
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
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpHeaderExtensionCapability::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpRtxParameters::class)]
    #[UsesClass(\Webrtc\RTP\Jitter\JitterFrame::class)]
    #[UsesClass(\Webrtc\RTP\MediaStreamTrack\RemoteStreamTrack::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\NackGenerator::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\AimdRateControl::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\InterArrival::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\OveruseDetector::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateBucket::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RateCounter::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\RemoteBitrateEstimator::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\Rate\TimestampGroup::class)]
    #[UsesClass(\Webrtc\RTP\Receiver\TimestampMapper::class)]
    #[UsesClass(\Webrtc\SDP\BitPattern::class)]
    #[UsesClass(\Webrtc\SDP\H264Sdp::class)]
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
    #[UsesClass(\Webrtc\TURN\Trait\TurnConnection::class)]
class RTCPeerConnectionDatachannelTest extends RTCPeerConnectionBaseTest
{
    public function testConnectDatachannelAndCloseImmediately()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        // create two data channels
        $dc1 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat"));
        $this->assertEquals(State::Connecting, $dc1->getReadyState());
        $dc2 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat2"));
        $this->assertEquals(State::Connecting, $dc2->getReadyState());

        // close one data channel
        $dc1->close();
        $this->assertEquals(State::Closed, $dc1->getReadyState());
        $this->assertEquals(State::Connecting, $dc2->getReadyState());

        // perform SDP exchange
        $pc1->setLocalDescription($pc1->createOffer());
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->setLocalDescription($pc2->createAnswer());
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertEquals(State::Closed, $dc1->getReadyState());
        delay(1);
        $this->assertDataChannelOpen($dc2);

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);
    }

    public function testConnectDatachannelNegotiatedAndCloseImmediately()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        // create two data channels
        $dc1 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1", negotiated: true, id: 100));
        $this->assertEquals(State::Connecting, $dc1->getReadyState());
        $dc2 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat2", negotiated: true, id: 102));
        $this->assertEquals(State::Connecting, $dc2->getReadyState());

        // close one data channel
        $dc1->close();
        $this->assertEquals(State::Closed, $dc1->getReadyState());
        $this->assertEquals(State::Connecting, $dc2->getReadyState());

        // perform SDP exchange
        $pc1->setLocalDescription($pc1->createOffer());
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->setLocalDescription($pc2->createAnswer());
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertEquals(State::Closed, $dc1->getReadyState());
        delay(1);
        $this->assertDataChannelOpen($dc2);

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);
    }

    public function testConnectDatachannelLegacySdp()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1->setSctpLegacySdp(true);
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        /** @var RTCDataChannel[] $pc2DataChannels */
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1", protocol: "bob"));
        $this->assertEquals("chat1", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send messages
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
            $dc->send("");
            $dc->send("\x00\x01\x02\x03");
            $dc->send("");
            $dc->send($this->longData);

            //FIXME: add buffer but immortally flush it after sending due to async issues
            $this->assertEquals(0, $dc->getBufferedAmount());
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctpmap:5000 webrtc-datachannel 65535", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctpmap:5000 webrtc-datachannel 65535", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertDataChannelOpen($dc);
        $this->assertEquals(0, $dc->getBufferedAmount());

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat1", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got messages
        delay(.1); // 100ms
        $this->assertEquals(
            ["hello", "", "\x00\x01\x02\x03", "", $this->longData], $pc2DataMessages
        );

        // check pc1 got replies
        $this->assertEquals(
            $pc1DataMessages,
            [
                "string-echo: hello",
                "string-echo: ",
                "binary-echo: \x00\x01\x02\x03",
                "string-echo: ",
                "binary-echo: " . $this->longData
            ]
        );

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelModernSdp()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1->setSctpLegacySdp(false);
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        /** @var RTCDataChannel[] $pc2DataChannels */
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1", protocol: "bob"));
        $this->assertEquals("chat1", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send messages
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
            $dc->send("");
            $dc->send("\x00\x01\x02\x03");
            $dc->send("");
            $dc->send($this->longData);
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctp-port:5000", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctp-port:5000", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat1", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got messages
        delay(.1); // 100ms
        $this->assertEquals(
            ["hello", "", "\x00\x01\x02\x03", "", $this->longData], $pc2DataMessages
        );

        // check pc1 got replies
        $this->assertEquals(
            $pc1DataMessages,
            [
                "string-echo: hello",
                "string-echo: ",
                "binary-echo: \x00\x01\x02\x03",
                "string-echo: ",
                "binary-echo: " . $this->longData
            ]
        );

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelModernSdpNegotiated()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1->setSctpLegacySdp(false);
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        // create data channels
        $dc1 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: "bob", negotiated: true, id: 100));
        $this->assertEquals(100, $dc1->getId());
        $this->assertEquals("chat", $dc1->getLabel());
        $this->assertNull($dc1->getMaxPacketLifeTime());
        $this->assertNull($dc1->getMaxRetransmits());
        $this->assertTrue($dc1->getOrdered());
        $this->assertEquals("bob", $dc1->getProtocol());
        $this->assertEquals(State::Connecting, $dc1->getReadyState());

        $dc2 = $pc2->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: "bob", negotiated: true, id: 100));
        $this->assertEquals(100, $dc2->getId());
        $this->assertEquals("chat", $dc2->getLabel());
        $this->assertNull($dc2->getMaxPacketLifeTime());
        $this->assertNull($dc2->getMaxRetransmits());
        $this->assertTrue($dc2->getOrdered());
        $this->assertEquals("bob", $dc2->getProtocol());
        $this->assertEquals(State::Connecting, $dc2->getReadyState());

        $dc1->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        $dc2->on("message", function ($message) use (&$pc2DataMessages, $dc2) {
            $pc2DataMessages[] = $message;
            $dc2->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
        });

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctp-port:5000", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sctp-port:5000", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertDataChannelOpen($dc1);
        $this->assertDataChannelOpen($dc2);

        // send message
        $dc1->send("hello");
        $dc1->send("");
        $dc1->send("\x00\x01\x02\x03");
        $dc1->send("");
        $dc1->send($this->longData);

        // check pc2 got messages
        delay(.1); // 0.1 seconds
        $this->assertEquals(["hello", "", "\x00\x01\x02\x03", "", $this->longData], $pc2DataMessages);

        // check pc1 got replies
        $this->assertEquals(
            [
                "string-echo: hello",
                "string-echo: ",
                "binary-echo: \x00\x01\x02\x03",
                "string-echo: ",
                "binary-echo: " . $this->longData,
            ],
            $pc1DataMessages
        );

        // close data channels
        $this->closeDataChannel($dc1);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelRecycleStreamId()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        // create three data channels
        $dc1 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1"));
        $this->assertEquals(State::Connecting, $dc1->getReadyState());
        $dc2 = $pc1->createDataChannel(new RTCDataChannelParameters("chat2"));
        $this->assertEquals(State::Connecting, $dc2->getReadyState());
        $dc3 = $pc1->createDataChannel(new RTCDataChannelParameters("chat3"));
        $this->assertEquals(State::Connecting, $dc3->getReadyState());

        // perform SDP exchange
        $pc1->setLocalDescription($pc1->createOffer());
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->setLocalDescription($pc2->createAnswer());
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);
        $this->assertDataChannelOpen($dc1);
        $this->assertEquals(1, $dc1->getId());
        $this->assertDataChannelOpen($dc2);
        $this->assertEquals(3, $dc2->getId());
        $this->assertDataChannelOpen($dc3);
        $this->assertEquals(5, $dc3->getId());

        // close one data channel
        $this->closeDataChannel($dc2);

        // create a new data channel
        $dc4 = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat4"));
        $this->assertDataChannelOpen($dc4);
        $this->assertEquals(3, $dc4->getId());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);
    }

    public function testCreateDatachannelWithMaxpacketlifetimeAndMaxretransmits()
    {
        $pc = RTCPeerConnectionHelper::createPeerConnection();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot specify both maxPacketLifeTime and maxRetransmits");
        $pc->createDataChannel(new RTCDataChannelParameters(label: "chat", maxPacketLifeTime: 500, maxRetransmits: 0));
    }

    public function testDatachannelBufferedamountlowthreshold()
    {
        $pc = RTCPeerConnectionHelper::createPeerConnection();
        $dc = $pc->createDataChannel(new RTCDataChannelParameters(label: "chat"));
        $this->assertEquals(0, $dc->getBufferedAmountLowThreshold());

        $dc->setBufferedAmountLowThreshold(4294967295);
        $this->assertEquals(4294967295, $dc->getBufferedAmountLowThreshold());

        $dc->setBufferedAmountLowThreshold(16384);
        $this->assertEquals(16384, $dc->getBufferedAmountLowThreshold());

        $dc->setBufferedAmountLowThreshold(0);
        $this->assertEquals(0, $dc->getBufferedAmountLowThreshold());

        try {
            $dc->setBufferedAmountLowThreshold(-1);
            $this->fail("Expected ValueError");
        } catch (InvalidArgumentException) {
            $this->assertEquals(0, $dc->getBufferedAmountLowThreshold());
        }

        try {
            $dc->setBufferedAmountLowThreshold(4294967296);
            $this->fail("Expected ValueError");
        } catch (InvalidArgumentException) {
            $this->assertEquals(0, $dc->getBufferedAmountLowThreshold());
        }
    }

    public function testDatachannelSendInvalidState()
    {
        $pc = RTCPeerConnectionHelper::createPeerConnection();
        $dc = $pc->createDataChannel(new RTCDataChannelParameters(label: "chat"));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Data channel is not open");
        $dc->send("hello");
    }

    public function testConnectDatachannelThenAudio()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1", protocol: "bob"));
        $this->assertEquals("chat1", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send messages
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
            $dc->send("");
            $dc->send("\x00\x01\x02\x03");
            $dc->send("");
            $dc->send($this->longData);
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // 1. DATA CHANNEL ONLY

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat1", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got messages
        delay(.1); // 0.1 seconds
        $this->assertEquals(
            ["hello", "", "\x00\x01\x02\x03", "", $this->longData], $pc2DataMessages
        );

        // check pc1 got replies
        $this->assertEquals(
            $pc1DataMessages,
            [
                "string-echo: hello",
                "string-echo: ",
                "binary-echo: \x00\x01\x02\x03",
                "string-echo: ",
                "binary-echo: " . $this->longData
            ]
        );

        // 2. ADD AUDIO

        // create offer
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertEquals(IceConnectionState::completed, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc2->getIceGatheringState());
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());
        $this->assertEquals(IceConnectionState::completed, $pc1->getIceConnectionState());

        // check an outcome
        $this->assertIceCompleted($pc1, $pc2);

        // check single transport is used
        $this->assertBundled($pc1);
        $this->assertBundled($pc2);

        // 3. CLEANUP

        // close data channel
        $this->closeDataChannel($dc);

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);

        // check state changes
        $this->assertEquals(
            [
                ConnectionState::new,
                ConnectionState::connecting,
                ConnectionState::connected,
                ConnectionState::new,
                ConnectionState::connecting,
                ConnectionState::connected,
                ConnectionState::closed
            ],
            $pc1States["connectionState"]
        );
        $this->assertEquals(
            [
                IceConnectionState::new,
                IceConnectionState::checking,
                IceConnectionState::completed,
                IceConnectionState::new,
                IceConnectionState::completed,
                IceConnectionState::closed
            ],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::complete,
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::complete
            ],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [
                SignalingState::stable,
                SignalingState::haveLocalOffer,
                SignalingState::stable,
                SignalingState::haveLocalOffer,
                SignalingState::stable,
                SignalingState::closed
            ],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [
                ConnectionState::new,
                ConnectionState::connecting,
                ConnectionState::connected,
                ConnectionState::new,
                ConnectionState::connecting,
                ConnectionState::connected,
                ConnectionState::closed
            ],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [
                IceConnectionState::new,
                IceConnectionState::checking,
                IceConnectionState::completed,
                IceConnectionState::new,
                IceConnectionState::completed,
                IceConnectionState::closed
            ],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::complete,
                IceGatheringState::new,
                IceGatheringState::complete
            ],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [
                SignalingState::stable,
                SignalingState::haveRemoteOffer,
                SignalingState::stable,
                SignalingState::haveRemoteOffer,
                SignalingState::stable,
                SignalingState::closed
            ],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelTrickle()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat1", protocol: "bob"));
        $this->assertEquals("chat1", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send messages
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
            $dc->send("");
            $dc->send("\x00\x01\x02\x03");
            $dc->send("");
            $dc->send($this->longData);
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // strip out candidates
        $desc1 = RTCPeerConnectionHelper::stripIceCandidates($pc1->getLocalDescription());

        // handle offer
        $pc2->setRemoteDescription($desc1);
        $this->assertEquals($desc1, $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // strip out candidates
        $desc2 = RTCPeerConnectionHelper::stripIceCandidates($pc2->getLocalDescription());

        // handle answer
        $pc1->setRemoteDescription($desc2);
        $this->assertEquals($desc2, $pc1->getRemoteDescription());

        // trickle candidates
        foreach ($pc2->getSctp()->getDtlsTransport()->getIceTransport()->getIceGatherer()->getLocalCandidates() as $candidate) {
            $candidate->setSdpMid($pc2->getSctp()->getMid());
            $pc1->addIceCandidate($candidate);
        }
        foreach ($pc1->getSctp()->getDtlsTransport()->getIceTransport()->getIceGatherer()->getLocalCandidates() as $candidate) {
            $candidate->setSdpMid($pc1->getSctp()->getMid());
            $pc2->addIceCandidate($candidate);
        }

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat1", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got messages
        delay(.1); // 0.1 seconds
        $this->assertEquals(
            ["hello", "", "\x00\x01\x02\x03", "", $this->longData], $pc2DataMessages
        );

        // check pc1 got replies
        $this->assertEquals(
            $pc1DataMessages,
            [
                "string-echo: hello",
                "string-echo: ",
                "binary-echo: \x00\x01\x02\x03",
                "string-echo: ",
                "binary-echo: " . $this->longData
            ]
        );

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelMaxPacketLifetime()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", maxPacketLifeTime: 500, protocol: "bob"));
        $this->assertEquals("chat", $dc->getLabel());
        $this->assertEquals(500, $dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send message
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $pc1->setLocalDescription($offer);
        $pc2->setRemoteDescription($pc1->getLocalDescription());

        // create answer
        $answer = $pc2->createAnswer();
        $pc2->setLocalDescription($answer);
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat", $pc2DataChannels[0]->getLabel());
        $this->assertEquals(500, $pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got message
        delay(.1); // 0.1 seconds
        $this->assertEquals(["hello"], $pc2DataMessages);

        // check pc1 got replies
        $this->assertEquals(["string-echo: hello"], $pc1DataMessages);

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelMaxRetransmits()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channels
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: "bob", maxRetransmits: 0));
        $this->assertEquals("chat", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertEquals(0, $dc->getMaxRetransmits());
        $this->assertTrue($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send message
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $pc1->setLocalDescription($offer);
        $pc2->setRemoteDescription($pc1->getLocalDescription());

        // create answer
        $answer = $pc2->createAnswer();
        $pc2->setLocalDescription($answer);
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertEquals(0, $pc2DataChannels[0]->getMaxRetransmits());
        $this->assertTrue($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got message
        delay(.1); // 0.1 seconds
        $this->assertEquals(["hello"], $pc2DataMessages);

        // check pc1 got replies
        $this->assertEquals(["string-echo: hello"], $pc1DataMessages);

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectDatachannelUnordered()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc1DataMessages = [];
        $pc1States = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);

        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2DataChannels = [];
        $pc2DataMessages = [];
        $pc2States = [];

        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        $pc2->on("datachannel", function (RTCDataChannel $channel) use (&$pc2DataChannels, &$pc2DataMessages) {
            $this->assertEquals(State::Open, $channel->getReadyState());
            $pc2DataChannels[] = $channel;

            $channel->on("message", function ($message) use ($channel, &$pc2DataMessages) {
                $pc2DataMessages[] = $message;
                $channel->send((SctpUtility::isBinary($message) ? "binary" : "string") . "-echo: " . $message);
            });
        });

        // create data channel
        $dc = $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", ordered: false, protocol: "bob"));
        $this->assertEquals("chat", $dc->getLabel());
        $this->assertNull($dc->getMaxPacketLifeTime());
        $this->assertNull($dc->getMaxRetransmits());
        $this->assertFalse($dc->getOrdered());
        $this->assertEquals("bob", $dc->getProtocol());
        $this->assertEquals(State::Connecting, $dc->getReadyState());

        // send a message
        $dc->on("open", function () use ($dc) {
            $dc->send("hello");
        });

        $dc->on("message", function ($message) use (&$pc1DataMessages) {
            $pc1DataMessages[] = $message;
        });

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=application ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(0, $pc2->getReceivers());
        $this->assertCount(0, $pc2->getSenders());
        $this->assertCount(0, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=application ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        $this->assertDataChannelOpen($dc);

        // check pc2 got a datachannel
        $this->assertCount(1, $pc2DataChannels);
        $this->assertEquals("chat", $pc2DataChannels[0]->getLabel());
        $this->assertNull($pc2DataChannels[0]->getMaxPacketLifeTime());
        $this->assertNull($pc2DataChannels[0]->getMaxRetransmits());
        $this->assertFalse($pc2DataChannels[0]->getOrdered());
        $this->assertEquals("bob", $pc2DataChannels[0]->getProtocol());

        // check pc2 got message
        delay(.1); // 0.1 seconds
        $this->assertEquals(["hello"], $pc2DataMessages);

        // check pc1 got replies
        $this->assertEquals(["string-echo: hello"], $pc1DataMessages);

        // close data channel
        $this->closeDataChannel($dc);

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
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::stable, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }
}
