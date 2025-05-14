<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\VideoStreamTrack;
use Webrtc\SDP\Enum\DtlsRole;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function React\Async\await;
use function React\Async\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]
class RTCPeerConnectionDtlsTest extends RTCPeerConnectionBaseTest
{
    public function testDtlsRoleOfferActpass()
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

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // set remote description
        await($pc2->setRemoteDescription($pc1->getLocalDescription()));

        // create answer
        $answer = await($pc2->createAnswer());
        $this->assertHasDtls($answer, "active");

        await($pc2->setLocalDescription($answer));
        $this->assertIceChecking($pc2);

        // handle answer
        await($pc1->setRemoteDescription($pc2->getLocalDescription()));
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

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // handle offer with a replaced DTLS role
        await($pc2->setRemoteDescription(new RTCSessionDescription(
            sdp: str_replace("actpass", "passive", $pc1->getLocalDescription()->getSdp()),
            type: "offer"
        )));

        // create answer
        $answer = await($pc2->createAnswer());
        $this->assertHasDtls($answer, "active");

        await($pc2->setLocalDescription($answer));
        $this->assertIceChecking($pc2);

        // handle answer
        await($pc1->setRemoteDescription($pc2->getLocalDescription()));
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

        // create offer
        $pc1->createDataChannel(new RTCDataChannelParameters(label: "chat", protocol: ""));
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());

        // handle offer with a replaced DTLS role
        await($pc2->setRemoteDescription(new RTCSessionDescription(
            sdp: str_replace("actpass", "active", $pc1->getLocalDescription()->getSdp()),
            type: "offer"
        )));

        // create answer
        $answer = await($pc2->createAnswer());
        $this->assertHasDtls($answer, "passive");

        await($pc2->setLocalDescription($answer));
        $this->assertIceChecking($pc2);

        // handle answer
        await($pc1->setRemoteDescription($pc2->getLocalDescription()));
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
        $pc1 = new RTCPeerConnection();
        $pc2 = new RTCPeerConnection();

        $tr1A = $pc1->addTransceiver(MediaKind::Video, SDPDirections::recvonly);
        $tr1B = $pc1->addTransceiver(MediaKind::Video, SDPDirections::recvonly);
        $offer = await($pc1->createOffer());
        $this->assertEquals("offer", $offer->getType());

        await($pc1->setLocalDescription($offer));

        $tr2A = $pc2->addTransceiver(new VideoStreamTrack());
        $tr2B = $pc2->addTransceiver(new VideoStreamTrack());
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
