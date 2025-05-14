<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\Exception\InvalidArgumentException;
use Webrtc\Exception\RuntimeException;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\RTP\MediaStreamTrack\AudioStreamTrack;
use Webrtc\RTP\MediaStreamTrack\VideoStreamTrack;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function React\Async\async;
use function React\Async\await;
use function React\Async\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]
class RTCPeerConnectionDescriptionTest extends RTCPeerConnectionBaseTest
{
    public function testCreateAnswerClosed()
    {
        $pc = new RTCPeerConnection();
        $pc->close();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("RTCPeerConnection is closed");
        await($pc->createAnswer());
    }

    public function testCreateAnswerWithoutOffer()
    {
        $pc = new RTCPeerConnection();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot create answer in signaling state stable");
        await($pc->createAnswer());
    }

    public function testCreateOfferClosed()
    {
        $pc = new RTCPeerConnection();
        $pc->close();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("RTCPeerConnection is closed");
        await($pc->createOffer());
    }

    public function testCreateOfferWithoutMedia()
    {
        $pc = new RTCPeerConnection();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Cannot create an offer with no media and no data channels");
        await($pc->createOffer());
    }

    public function testSetLocalDescriptionUnexpectedAnswer()
    {
        $pc = new RTCPeerConnection();
        $pc->addTrack(new AudioStreamTrack());
        $answer = await($pc->createOffer());
        $answer->setType("answer");
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle answer in signaling state \"stable\"");
        await($pc->setLocalDescription($answer));
    }

    public function testSetLocalDescriptionUnexpectedOffer()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        // apply offer
        $pc1->addTrack(new AudioStreamTrack());
        await($pc1->setLocalDescription(await($pc1->createOffer())));
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));

        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        // mangle answer into an offer
        $offer = $pc2->getRemoteDescription();
        $offer->setType("offer");
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle offer in signaling state \"haveRemoteOffer\"");
        await($pc2->setLocalDescription($offer));
    }

    public function testSetRemoteDescriptionNoCommonAudio()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();
        $pc1->addTrack(new AudioStreamTrack());
        $offer = await($pc1->createOffer());

        $mangledSdp = [];
        foreach (explode("\n", $offer->getSdp()) as $line) {
            if (!str_starts_with($line, "a=rtpmap:")) {
                $mangledSdp[] = $line;
            }
        }

        $mangled = new RTCSessionDescription(
            sdp: implode("\n", $mangledSdp),
            type: $offer->getType()
        );


        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to set remote audio description send parameters");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionNoCommonVideo()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();
        $pc1->addTrack(new VideoStreamTrack());
        $offer = await($pc1->createOffer());

        $mangled = new RTCSessionDescription(
            sdp: str_replace("90000", "92000", $offer->getSdp()),
            type: $offer->getType()
        );


        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to set remote video description send parameters");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionMediaMismatch()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        // apply offer
        $pc1->addTrack(new AudioStreamTrack());
        $offer = await($pc1->createOffer());
        await($pc1->setLocalDescription($offer));
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));

        // apply answer
        $answer = await($pc2->createAnswer());
        await($pc2->setLocalDescription($answer));
        $mangled = new RTCSessionDescription(
            sdp: str_replace("m=audio", "m=video", $pc2->getLocalDescription()->getSdp()),
            type: $pc2->getLocalDescription()->getType()
        );


        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle answer in signaling state \"stable\"");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionWithInvalidDtlsSetupForAnswer()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        // apply offer
        $pc1->addTrack(new AudioStreamTrack());
        $offer = await($pc1->createOffer());
        await($pc1->setLocalDescription($offer));
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));

        // apply answer
        $answer = await($pc2->createAnswer());
        await($pc2->setLocalDescription($answer));
        $mangled = new RTCSessionDescription(
            sdp: str_replace("a=setup:active", "a=setup:actpass", $pc2->getLocalDescription()->getSdp()),
            type: $pc2->getLocalDescription()->getType()
        );


        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle answer in signaling state \"stable\"");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionWithoutIceCredentials()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        $pc1->addTrack(new AudioStreamTrack());
        $offer = await($pc1->createOffer());
        await($pc1->setLocalDescription($offer));

        $mangledSdp = preg_replace(
            '/^a=(ice-ufrag|ice-pwd):.*\r\n/m',
            '',
            $pc1->getLocalDescription()->getSdp()
        );
        $mangled = new RTCSessionDescription(
            sdp: $mangledSdp,
            type: $pc1->getLocalDescription()->getType()
        );

        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("ICE username fragment or password is missing");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionWithoutRtcpMux()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        $pc1->addTrack(new AudioStreamTrack());
        $offer = await($pc1->createOffer());
        await($pc1->setLocalDescription($offer));

        $mangledSdp = preg_replace(
            '/^a=rtcp-mux\r\n/m',
            '',
            $pc1->getLocalDescription()->getSdp()
        );
        $mangled = new RTCSessionDescription(
            sdp: $mangledSdp,
            type: $pc1->getLocalDescription()->getType()
        );

        async(function () use ($pc1, $pc2) {
            delay(.1);
            $pc1->close();
            $pc2->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("RTCP mux is not enabled");
        await($pc2->setRemoteDescription($mangled));
    }

    public function testSetRemoteDescriptionUnexpectedAnswer()
    {
        $pc = new RTCPeerConnection();

        async(function () use ($pc) {
            delay(.1);
            $pc->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle answer in signaling state \"stable\"");
        await($pc->setRemoteDescription(new RTCSessionDescription(sdp: "", type: "answer")));
    }

    public function testSetRemoteDescriptionUnexpectedOffer()
    {
        $pc = new RTCPeerConnection();
        $pc->addTrack(new AudioStreamTrack());
        $offer = await($pc->createOffer());
        await($pc->setLocalDescription($offer));


        async(function () use ($pc) {
            delay(.1);
            $pc->close();
        })();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot handle offer in signaling state \"haveLocalOffer\"");
        await($pc->setRemoteDescription(new RTCSessionDescription(sdp: "", type: "offer")));
    }

    public function testSetRemoteDescriptionMediaDatachannelBundled()
    {
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

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

        /*
         * initial negotiation
         */

        // create offer
        $pc1->addTrack(new AudioStreamTrack());
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());

        // handle offer
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = await($pc2->createAnswer());
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $answer->getSdp());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=application ", $answer->getSdp());

        await($pc2->setLocalDescription($answer));
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());

        // handle answer
        await($pc1->setRemoteDescription($pc2->getLocalDescription()));
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());

        // check the outcome
        $this->assertIceCompleted($pc1, $pc2);

        /*
         * renegotiation
         */

        // create offer
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));
        $this->assertEquals(IceConnectionState::completed, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=application ", $pc1->getLocalDescription()->getSdp());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = await($pc2->createAnswer());
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $answer->getSdp());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringContainsString("m=application ", $answer->getSdp());

        await($pc2->setLocalDescription($answer));
        $this->assertEquals(IceConnectionState::completed, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc2->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("a=group:BUNDLE 0 1", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("m=application ", $pc2->getLocalDescription()->getSdp());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        await($pc1->setRemoteDescription($pc2->getLocalDescription()));
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());
        $this->assertEquals(IceConnectionState::completed, $pc1->getIceConnectionState());

        // allow media to flow long enough to collect stats
//        delay(2); // 2 seconds

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
}
