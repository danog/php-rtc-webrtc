<?php

namespace Tests\Webrtc\Webrtc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Webrtc\DataChannel\RTCDataChannelParameters;
use Webrtc\ICE\Enum\IceGatheringState;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\SDP\Enum\SDPDirections;
use Webrtc\Stats\RTCStatsReport;
use Webrtc\Webrtc\Enum\ConnectionState;
use Webrtc\Webrtc\Enum\IceConnectionState;
use Webrtc\Webrtc\Enum\SignalingState;
use Webrtc\Webrtc\RTCConfiguration;
use Webrtc\Webrtc\RTCPeerConnection;
use function Amp\delay;

#[UsesClass(RTCConfiguration::class)]
#[CoversClass(RTCPeerConnection::class)]
class RTCPeerConnectionAudioTest extends RTCPeerConnectionBaseTest
{
    protected function testConnectAudioBidirectional(RTCPeerConnection $pc1, RTCPeerConnection $pc2): void
    {
        $pc1States = [];
        $pc1Tracks = [];
        $pc2States = [];
        $pc2Tracks = [];

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);
        RTCPeerConnectionHelper::trackRemoteTracks($pc1, $pc1Tracks);
        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);
        RTCPeerConnectionHelper::trackRemoteTracks($pc2, $pc2Tracks);

        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc1->getIceGatheringState());
        $this->assertNull($pc1->getLocalDescription());
        $this->assertNull($pc1->getRemoteDescription());

        $this->assertEquals(IceConnectionState::new, $pc2->getIceConnectionState());
        $this->assertEquals(IceGatheringState::new, $pc2->getIceGatheringState());
        $this->assertNull($pc2->getLocalDescription());
        $this->assertNull($pc2->getRemoteDescription());

        // create offer
        $track1 = new PreEncodedAudioStreamTrack();
        $pc1->addTrack($track1);
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString(
            str_replace("\n", "\r\n", "a=rtpmap:96 opus/48000/2\na=rtpmap:0 PCMU/8000\na=rtpmap:8 PCMA/8000\n"),
            $pc1->getLocalDescription()->getSdp()
        );
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // RemoteStreamTrack should have the same ID as source track
        $this->assertCount(1, $pc2Tracks);
        $this->assertEquals($track1->getId(), $pc2Tracks[0]->getId());

        // create answer
        $track2 = new PreEncodedAudioStreamTrack();
        $pc2->addTrack($track2);
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString(
            str_replace("\n", "\r\n", "a=rtpmap:96 opus/48000/2\na=rtpmap:0 PCMU/8000\na=rtpmap:8 PCMA/8000\n"),
            $pc2->getLocalDescription()->getSdp()
        );
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());

        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::sendrecv, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $pc2->getTransceivers()[0]->getDirection());

//        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getLocalDescription(), $pc1->getRemoteDescription());
        $this->assertEquals(SDPDirections::sendrecv, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $pc1->getTransceivers()[0]->getDirection());

        // RemoteStreamTrack should have the same ID as source track
        $this->assertCount(1, $pc1Tracks);
        $this->assertEquals($track2->getId(), $pc1Tracks[0]->getId());
//
//         check the outcome
        $this->assertIceCompleted($pc1, $pc2);
        // Allow media to flow before reading the stats back.
        delay(2);

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

    public function testConnectAudioBidirectionalOrg()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $this->testConnectAudioBidirectional($pc1, $pc2);
    }

    public function testConnectAudioBidirectionalWithEmptyIceServers()
    {
        $pc1 = RTCPeerConnectionHelper::createPeerConnection([]);
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();
        $this->testConnectAudioBidirectional($pc1, $pc2);
    }

    public function testConnectAudioBidirectionalWithTrickle()
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
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // strip out candidates
        $desc1 = RTCPeerConnectionHelper::stripIceCandidates($pc1->getLocalDescription());

        // handle offer
        $pc2->setRemoteDescription($desc1);
        $this->assertEquals($desc1, $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // strip out candidates
        $desc2 = RTCPeerConnectionHelper::stripIceCandidates($pc2->getLocalDescription());

        // handle answer
        $pc1->setRemoteDescription($desc2);
        $this->assertEquals($desc2, $pc1->getRemoteDescription());

        // trickle candidates
        foreach ($pc2->getTransceivers() as $transceiver) {
            $iceGatherer = $transceiver->getSender()->getTransport()->getIceTransport()->getIceGatherer();
            foreach ($iceGatherer->getLocalCandidates() as $candidate) {
                $candidate->setSdpMid((string)$transceiver->getMid());
                $pc1->addIceCandidate($candidate);
            }
        }
        foreach ($pc1->getTransceivers() as $transceiver) {
            $iceGatherer = $transceiver->getSender()->getTransport()->getIceTransport()->getIceGatherer();
            foreach ($iceGatherer->getLocalCandidates() as $candidate) {
                $candidate->setSdpMid($transceiver->getMid());
                $pc2->addIceCandidate($candidate);
            }
        }

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

    public function testConnectAudioBidirectionalAndClose()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection();
        $pc2 = RTCPeerConnectionHelper::createPeerConnection();

        RTCPeerConnectionHelper::trackStates($pc1, $pc1States);
        RTCPeerConnectionHelper::trackStates($pc2, $pc2States);

        // create offer
        $track1 = new PreEncodedAudioStreamTrack();
        $pc1->addTrack($track1);
        $offer = $pc1->createOffer();
        $pc1->setLocalDescription($offer);

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());

        // create answer
        $track2 = new PreEncodedAudioStreamTrack();
        $pc2->addTrack($track2);
        $answer = $pc2->createAnswer();
        $pc2->setLocalDescription($answer);

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());

        // check an outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

        // close one side, which causes the other to shut down
        $pc1->close();
        delay(2);

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

    public function testConnectAudioMidChanges()
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

        // add audio tracks immediately
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $pc2->addTrack(new PreEncodedAudioStreamTrack());

        // create offer
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        // sdp created by Firfox
        $offer->setSdp(str_replace("a=mid:0", "a=mid:sdparta_0", $offer->getSdp()));

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["sdparta_0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");
        $this->assertStringContainsString("a=mid:sdparta_0", $pc1->getLocalDescription()->getSdp());

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["sdparta_0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertStringContainsString("a=mid:sdparta_0", $pc2->getLocalDescription()->getSdp());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioOfferRecvonlyAnswerRecvonly()
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
        $pc1->addTransceiver(MediaKind::Audio, SDPDirections::recvonly);
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=recvonly", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=inactive", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::inactive, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::recvonly, $pc2->getTransceivers()[0]->getDirection());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals(SDPDirections::inactive, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::recvonly, $pc1->getTransceivers()[0]->getDirection());

        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioOfferRecvonly()
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
        $pc1->addTransceiver(MediaKind::Audio, SDPDirections::recvonly);
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=recvonly", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
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
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendonly", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::sendonly, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $pc2->getTransceivers()[0]->getDirection());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals(SDPDirections::recvonly, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::recvonly, $pc1->getTransceivers()[0]->getDirection());

        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioOfferSendonly()
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
        $pc1->addTransceiver(new PreEncodedAudioStreamTrack(), SDPDirections::sendonly);
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendonly", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=recvonly", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::recvonly, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::recvonly, $pc2->getTransceivers()[0]->getDirection());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals(SDPDirections::sendonly, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendonly, $pc1->getTransceivers()[0]->getDirection());

        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioOfferSendrecvAnswerRecvonly()
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
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=recvonly", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::recvonly, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::recvonly, $pc2->getTransceivers()[0]->getDirection());


        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals(SDPDirections::sendonly, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $pc1->getTransceivers()[0]->getDirection());


        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioOfferSendrecvAnswerSendonly()
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
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $pc2->getTransceivers()[0]->setDirection(SDPDirections::sendonly);
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(1, $pc2->getReceivers());
        $this->assertCount(1, $pc2->getSenders());
        $this->assertCount(1, $pc2->getTransceivers());
        $this->assertEquals(["0"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendonly", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");
        $this->assertEquals(SDPDirections::sendonly, $pc2->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendonly, $pc2->getTransceivers()[0]->getDirection());

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());
        $this->assertEquals(SDPDirections::recvonly, $pc1->getTransceivers()[0]->getCurrentDirection());
        $this->assertEquals(SDPDirections::sendrecv, $pc1->getTransceivers()[0]->getDirection());


        // check outcome
        $this->assertIceCompleted($pc1, $pc2);
        delay(1);

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

    public function testConnectAudioTwoTracks()
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
        $pc1->addTrack(new PreEncodedAudioStreamTrack());
        $offer = $pc1->createOffer();
        $this->assertEquals("offer", $offer->getType());
        $this->assertStringContainsString("m=audio ", $offer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $offer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $offer->getSdp());

        $pc1->setLocalDescription($offer);
        $this->assertEquals(IceConnectionState::new, $pc1->getIceConnectionState());
        $this->assertEquals(IceGatheringState::complete, $pc1->getIceGatheringState());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc1));
        $this->assertStringContainsString("m=audio ", $pc1->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc1->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc1->getLocalDescription());
        $this->assertHasDtls($pc1->getLocalDescription(), "actpass");

        // handle offer
        $pc2->setRemoteDescription($pc1->getLocalDescription());
        $this->assertEquals($pc1->getLocalDescription(), $pc2->getRemoteDescription());
        $this->assertCount(2, $pc2->getReceivers());
        $this->assertCount(2, $pc2->getSenders());
        $this->assertCount(2, $pc2->getTransceivers());
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));

        // create answer
        $pc2->addTrack(new PreEncodedAudioStreamTrack());
        $answer = $pc2->createAnswer();
        $this->assertEquals("answer", $answer->getType());
        $this->assertStringContainsString("m=audio ", $answer->getSdp());
        $this->assertStringNotContainsString("a=candidate:", $answer->getSdp());
        $this->assertStringNotContainsString("a=end-of-candidates", $answer->getSdp());

        $pc2->setLocalDescription($answer);
        $this->assertIceChecking($pc2);
        $this->assertEquals(["0", "1"], RTCPeerConnectionHelper::mids($pc2));
        $this->assertStringContainsString("m=audio ", $pc2->getLocalDescription()->getSdp());
        $this->assertStringContainsString("a=sendrecv", $pc2->getLocalDescription()->getSdp());
        $this->assertHasIceCandidates($pc2->getLocalDescription());
        $this->assertHasDtls($pc2->getLocalDescription(), "active");

        // handle answer
        $pc1->setRemoteDescription($pc2->getLocalDescription());
        $this->assertEquals($pc2->getRemoteDescription(), $pc1->getLocalDescription());

        // check an outcome
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

    public function testConnectAudioAndVideo()
    {
        $pc1States = [];
        $pc2States = [];

        $pc1 = RTCPeerConnectionHelper::createPeerConnection([]);
        $pc2 = RTCPeerConnectionHelper::createPeerConnection([]);

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

        $this->waitUntil(fn() => $pc2->getIceConnectionState() === IceConnectionState::failed);

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

        $pc1 = RTCPeerConnectionHelper::createPeerConnection([]);
        $pc2 = RTCPeerConnectionHelper::createPeerConnection([]);

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

//        $this->assertEquals(
//            [ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::new, ConnectionState::connecting, ConnectionState::connected, ConnectionState::closed],
//            $pc2States["connectionState"]
//        );
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
}
