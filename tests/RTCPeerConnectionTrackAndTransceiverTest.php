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
