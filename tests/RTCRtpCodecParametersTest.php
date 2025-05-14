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
