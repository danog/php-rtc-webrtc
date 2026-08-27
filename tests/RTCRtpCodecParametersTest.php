<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\RTPParameter\RTCRtcpFeedback;
use Webrtc\RTPParameter\RTCRtpCodecCapability;
use Webrtc\RTPParameter\RTCRtpCodecParameters;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]

    #[UsesClass(\Webrtc\Codecs\CodecUtility::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCCertificate::class)]
    #[UsesClass(\Webrtc\ICE\RTCIceServer::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpFeedback::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecCapability::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCodecParameters::class)]
    #[UsesClass(\Webrtc\SDP\BitPattern::class)]
    #[UsesClass(\Webrtc\SDP\H264Sdp::class)]
    #[UsesClass(\Webrtc\DTLS\DTLS\RTCDtlsTransport::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpByePacket::class)]
    #[UsesClass(\Webrtc\RTP\RtpRouter::class)]
    #[UsesClass(\Webrtc\RTP\Sender\RTCRtpSender::class)]
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
    #[UsesClass(\Webrtc\RTCP\RtcpPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpReceiverInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpRrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSdesPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSenderInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSourceInfo::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpSrPacket::class)]
    #[UsesClass(\Webrtc\RTCP\RtcpUtility::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtcpParameters::class)]
    #[UsesClass(\Webrtc\RTPParameter\RTCRtpCapabilities::class)]
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
class RTCRtpCodecParametersTest extends RTCPeerConnectionBaseTest
{
    public function testFindCommonCodecsStatic()
    {
        $localCodecs = [
            new RTCRtpCodecParameters("audio/opus", 48000, 2, 96),
            new RTCRtpCodecParameters("audio/PCMU", 8000, 1, 0),
            new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
        ];
        $remoteCodecs = [
            new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
            new RTCRtpCodecParameters("audio/PCMU", 8000, 1, 0),
        ];
        $common = $this->pc->findMutualCodecs($localCodecs, $remoteCodecs);
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
                new RTCRtpCodecParameters("audio/PCMU", 8000, 1, 0),
            ],
            $common
        );
    }

    public function testFindCommonCodecsDynamic()
    {
        $localCodecs = [
            new RTCRtpCodecParameters("audio/opus", 48000, 2, 96),
            new RTCRtpCodecParameters("audio/PCMU", 8000, 1, 0),
            new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
        ];
        $remoteCodecs = [
            new RTCRtpCodecParameters("audio/opus", 48000, 2, 100),
            new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
        ];
        $common = $this->pc->findMutualCodecs($localCodecs, $remoteCodecs);
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("audio/opus", 48000, 2, 100),
                new RTCRtpCodecParameters("audio/PCMA", 8000, 1, 8),
            ],
            $common
        );
    }

    public function testFindCommonCodecsFeedback()
    {
        $localCodecs = [
            new RTCRtpCodecParameters(
                "video/VP8",
                90000,
                payloadType: 100,
                rtcpFeedback: [
                    new RTCRtcpFeedback("nack"),
                    new RTCRtcpFeedback("nack", "pli"),
                ]
            )
        ];
        $remoteCodecs = [
            new RTCRtpCodecParameters(
                "video/VP8",
                90000,
                payloadType: 120,
                rtcpFeedback: [
                    new RTCRtcpFeedback("nack"),
                    new RTCRtcpFeedback("nack", "sli"),
                ]
            )
        ];
        $common = $this->pc->findMutualCodecs($localCodecs, $remoteCodecs);
        $this->assertCount(1, $common);
        $this->assertEquals(90000, $common[0]->clockRate);
        $this->assertEquals("video/VP8", $common[0]->mimeType);
        $this->assertEquals(120, $common[0]->payloadType);
        $this->assertEquals([new RTCRtcpFeedback("nack")], $common[0]->rtcpFeedback);
    }

    public function testFindCommonCodecsRtx()
    {
        $localCodecs = [
            new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 100),
            new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 101, parameters: ["apt" => 100]),
        ];
        $remoteCodecs = [
            new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 96),
            new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 97, parameters: ["apt" => 96]),
            new RTCRtpCodecParameters("video/VP9", 90000, payloadType: 98),
            new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 99, parameters: ["apt" => 98]),
        ];
        $common = $this->pc->findMutualCodecs($localCodecs, $remoteCodecs);
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 96),
                new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 97, parameters: ["apt" => 96]),
            ],
            $common
        );
    }

    public function testFilterPreferredCodecs()
    {
        $codecs = [
            new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 100),
            new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 101, parameters: ["apt" => 100]),
            new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
            new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 103, parameters: ["apt" => 102]),
        ];

        // no preferences
        $this->assertEquals($codecs, $this->pc->findPreferredCodecs($codecs, []));

        // with RTX, prefer VP8
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 100),
                new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 101, parameters: ["apt" => 100]),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
                new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 103, parameters: ["apt" => 102]),
            ],
            $this->pc->findPreferredCodecs(
                $codecs,
                [
                    new RTCRtpCodecCapability("video/VP8", 90000),
                    new RTCRtpCodecCapability("video/rtx", 90000),
                    new RTCRtpCodecCapability("video/H264", 90000),
                ]
            )
        );

        // with RTX, prefer H264
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
                new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 103, parameters: ["apt" => 102]),
                new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 100),
                new RTCRtpCodecParameters("video/rtx", 90000, payloadType: 101, parameters: ["apt" => 100]),
            ],
            $this->pc->findPreferredCodecs(
                $codecs,
                [
                    new RTCRtpCodecCapability("video/H264", 90000),
                    new RTCRtpCodecCapability("video/rtx", 90000),
                    new RTCRtpCodecCapability("video/VP8", 90000),
                ]
            )
        );

        // no RTX, same order
        $this->assertEquals(
            [
                new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 100),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
            ],
            $this->pc->findPreferredCodecs(
                $codecs,
                [
                    new RTCRtpCodecCapability("video/VP8", 90000),
                    new RTCRtpCodecCapability("video/H264", 90000),
                ]
            )
        );
    }

    public function testIsCodecCompatible()
    {
        // compatible: identical
        $this->assertTrue(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102)
            )
        );
        $this->assertTrue(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "packetization-mode" => "0",
                    "profile-level-id" => "42E01F",
                ]),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102)
            )
        );

        // incompatible: different clockRate
        $this->assertFalse(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
                new RTCRtpCodecParameters("video/H264", 12345, payloadType: 102)
            )
        );

        // incompatible: different mimeType
        $this->assertFalse(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102),
                new RTCRtpCodecParameters("video/VP8", 90000, payloadType: 102)
            )
        );

        // incompatible: different H.264 profile
        $this->assertFalse(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "packetization-mode" => "1",
                    "profile-level-id" => "42001f",
                ]),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "packetization-mode" => "1",
                    "profile-level-id" => "42e01f",
                ])
            )
        );

        // incompatible: different H.264 packetization mode
        $this->assertFalse(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "packetization-mode" => "0",
                    "profile-level-id" => "42001f",
                ]),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "packetization-mode" => "1",
                    "profile-level-id" => "42001f",
                ])
            )
        );

        // incompatible: cannot parse H.264 profile
        $this->assertFalse(
            $this->pc->isCodecCompatible(
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "profile-level-id" => "42001f",
                ]),
                new RTCRtpCodecParameters("video/H264", 90000, payloadType: 102, parameters: [
                    "profile-level-id" => "wrong",
                ])
            )
        );
    }
}
