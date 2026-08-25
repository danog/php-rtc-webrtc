<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Amp\DeferredFuture;
use Webrtc\Codecs\Codec;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\SDP\SessionDescription;
use Webrtc\Stats\RTCStatsReport;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]
class RTCPeerConnectionVideoTest extends RTCPeerConnectionBaseTest
{
    public function testConnectAudioAndVideo()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringContainsString("m=video ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(2, $pc2->getReceivers());
        $this->assertCount(2, $pc2->getSenders());
        $this->assertCount(2, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=video ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // check single transport is used
        $this->assertBundled($pc1);
        $this->assertBundled($pc2);

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
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete],
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

    public function testConnectAudioAndVideoAndDataChannel()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: "bob"));
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringContainsString("m=application ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1", "2"], RTCPeerConnectionHelper::mids($pc1));

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(2, $pc2->getReceivers());
        $this->assertCount(2, $pc2->getSenders());
        $this->assertCount(2, $pc2->getTransceivers());
        $this->assertEquals(["0", "1", "2"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringContainsString("m=application ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // check single transport is used
        $this->assertBundled($pc1);
        $this->assertBundled($pc2);

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
            [
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::complete
            ],
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

    public function testConnectAudioAndVideoAndDataChannelIceFail()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: "bob"));
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringContainsString("m=application ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1", "2"], RTCPeerConnectionHelper::mids($pc1));

        // close one side
        $pc1Description = $pc1->getLocalDescription();
        $pc1->close();

        // handle offer
        $pc2->setRemoteDescription($pc1Description);
        $this->assertEquals($pc1Description, $pc2->getRemoteDescription());
        $this->assertCount(2, $pc2->getReceivers());
        $this->assertCount(2, $pc2->getSenders());
        $this->assertCount(2, $pc2->getTransceivers());
        $this->assertEquals(["0", "1", "2"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringContainsString("m=application ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());

        $deferred = new DeferredFuture();
        $pc2->on("iceconnectionstatechange", fn() => $deferred->resolve(true));
        await($deferred->promise());

        // check the outcome
        $this->assertEquals(IceConnectionState::closed, $pc1->getIceConnectionState());
        $this->assertEquals(IceConnectionState::failed, $pc2->getIceConnectionState());

        // close
        $pc1->close();
        $pc2->close();
        $this->assertClosed($pc1);
        $this->assertClosed($pc2);

        // check state changes
        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::closed],
            $pc1States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::closed],
            $pc1States["iceConnectionState"]
        );
        $this->assertEquals(
            [
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::new,
                IceGatheringState::gathering,
                IceGatheringState::complete
            ],
            $pc1States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveLocalOffer, SignalingState::closed],
            $pc1States["signalingState"]
        );

        $this->assertEquals(
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::failed, ConnectionState::connecting, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::failed, IceConnectionState::closed],
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

    public function testConnectAudioThenVideo()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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

        // 1. AUDIO ONLY

        // create offer
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("m=video ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));


        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("m=video ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringNotContainsString("m=video ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // check single transport is used
        $this->assertBundled($pc1);
        $this->assertBundled($pc2);

        // 2. ADD VIDEO

        // create offer
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringContainsString("m=video ", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(2, $pc2->getReceivers());
        $this->assertCount(2, $pc2->getSenders());
        $this->assertCount(2, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=video ", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertEquals(IceConnectionState::completed, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc2->getIceGatheringState());
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc1->getRemoteDescription(), $pc2->getLocalDescription());
        $this->assertEquals(IceConnectionState::completed, $pc1->getIceConnectionState());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // check single transport is used
        $this->assertBundled($pc1);
        $this->assertBundled($pc2);

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
            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
            $pc2States["connectionState"]
        );
        $this->assertEquals(
            [IceConnectionState::new, IceConnectionState::checking, IceConnectionState::completed, IceConnectionState::new, IceConnectionState::completed, IceConnectionState::closed],
            $pc2States["iceConnectionState"]
        );
        $this->assertEquals(
            [IceGatheringState::new, IceGatheringState::gathering, IceGatheringState::complete, IceGatheringState::new, IceGatheringState::complete],
            $pc2States["iceGatheringState"]
        );
        $this->assertEquals(
            [SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::haveRemoteOffer, SignalingState::stable, SignalingState::closed],
            $pc2States["signalingState"]
        );
    }

    public function testConnectVideoBidirectional()
    {
        $videoSdp = self::VP8_SDP . self::H264_SDP;

        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=video ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString($videoSdp, $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        // let media flow to trigger RTCP feedback, including REMB
        delay(5);

        // check stats
        $report = $pc1->getStats();
        $this->assertInstanceOf(RTCStatsReport::class, $report);
        $this->assertEquals(
            [
                'remote_inbound_rtp_stream',
                'outbound_rtp_stream',
                'transport',
                'remote_outbound_rtp_stream',
                'inbound_rtp_stream'
            ],
            array_map(fn($stat) => preg_replace('/_\d+$/', '', $stat), array_keys($report->getStats()))
        );

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

    public function testConnectVideoH264()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=video ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // strip out vp8
        $decode = SessionDescription::decode($pc1->getLocalDescription()->getSdp());
        array_shift($decode->getMedia()[0]->getRtp()->codecs);
        $format = $decode->getMedia()[0]->getFmt();
        array_shift($format);
        $decode->getMedia()[0]->setFmt($format);
        $desc1 = new RTCSessionDescription((string)$decode, $pc1->getLocalDescription()->getType());
        $this->assertStringNotContainsString("VP8", $desc1->getSdp());
        $this->assertStringContainsString("H264", $desc1->getSdp());

        // handle offer
        $pc2->setRemoteDescription($desc1);
        $this->assertEquals($desc1, $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

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

    public function testConnectVideoNoSsrc()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=video ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // strip out SSRC
        $desc1 = new RTCSessionDescription(preg_replace("/^a=ssrc:.*\r\n/m", "", $pc1->getLocalDescription()->getSdp()), $pc1->getLocalDescription()->getType());

        // handle offer
        $pc2->setRemoteDescription($desc1);
        $this->assertEquals($desc1, $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

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

    public function testConnectVideoCodecPreferencesOfferer()
    {
        $videoSdp = self::VP8_SDP . self::H264_SDP;

        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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

        // add track and set codec preferences to prefer H264
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $codec = new Codec();
        $capabilities = $codec->getCapabilities("video");
        $preferences = array_filter($capabilities->codecs, function ($x) {
            return in_array($x->mimeType, ["video/H264", "video/VP8", "video/rtx"]);
        });
        $transceiver = $pc1->getTransceivers()[0];
        $transceiver->setCodecPreferences($preferences);

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=video ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");
        $this->assertStringContainsString($videoSdp, $pc1->getLocalDescription()->getSdp());

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertStringContainsString($videoSdp, $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

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

    public function testConnectVideoCodecPreferencesOffererOnlyH264()
    {
        $videoSdp = self::H264_SDP;

        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

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

        // add track and set codec preferences to prefer H264
        $pc1->addTrack(new PreEncodedVideoStreamTrack());
        $codec = new Codec();
        $capabilities = $codec->getCapabilities("video");
        $preferences = array_filter($capabilities->codecs, function ($x) {
            return in_array($x->mimeType, ["video/H264", "video/rtx"]);
        });
        $transceiver = $pc1->getTransceivers()[0];
        $transceiver->setCodecPreferences($preferences);

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=video ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=video ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");
        $this->assertStringContainsString($videoSdp, $pc1->getLocalDescription()->getSdp());

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedVideoStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=video ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=video ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertStringContainsString($videoSdp, $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        delay(1);
        $this->assertIceCompleted($pc1, $pc2);

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
