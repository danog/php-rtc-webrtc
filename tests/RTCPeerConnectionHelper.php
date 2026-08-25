<?php

namespace Tests\Webrtc\Webrtc;

use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\RTCConfigurationInterface;
use Webrtc\Webrtc\RTCPeerConnection;

class RTCPeerConnectionHelper
{
    /**
     * Create a peer connection whose receivers expose encoded media.
     */
    public static function createPeerConnection(null|array|RTCConfigurationInterface $configuration = null): RTCPeerConnection
    {
        $pc = new RTCPeerConnection($configuration);
        $pc->on('track', function ($track) use ($pc): void {
            foreach ($pc->getReceivers() as $receiver) {
                if ($receiver->getTrack() === $track) {
                    $receiver->setRawMode(true);
                    return;
                }
            }
        });

        return $pc;
    }

    /**
     * Get media stream IDs (mids) from peer connection
     */
    public static function mids(RTCPeerConnection $pc): array
    {
        $mids = [];
        foreach ($pc->getTransceivers() as $transceiver) {
            $mids[] = $transceiver->getMid();
        }
        if ($pc->getSctp()) {
            $mids[] = $pc->getSctp()->getMid();
        }
        sort($mids);
        return $mids;
    }

    /**
     * Strip ICE candidates from session description
     */
    public static function stripIceCandidates(RTCSessionDescription $description): RTCSessionDescription
    {
        $strippedSdp = preg_replace('/a=candidate:.*\r\n/', '', $description->getSdp());
        $strippedSdp = preg_replace('/a=end-of-candidates\r\n/', '', $strippedSdp);
        return new RTCSessionDescription(
            sdp: $strippedSdp,
            type: $description->getType()
        );
    }

    /**
     * Track state changes of peer connection
     */
    public static function trackStates(RTCPeerConnection $pc, array &$states): void
    {
        $states = [
            'connectionState' => [$pc->getConnectionState()],
            'iceConnectionState' => [$pc->getIceConnectionState()],
            'iceGatheringState' => [$pc->getIceGatheringState()],
            'signalingState' => [$pc->getSignalingState()],
        ];

        $pc->on('connectionstatechange', function() use (&$states, $pc) {
            $states['connectionState'][] = $pc->getConnectionState();
        });

        $pc->on('iceconnectionstatechange', function() use (&$states, $pc) {
            $states['iceConnectionState'][] = $pc->getIceConnectionState();
        });

        $pc->on('icegatheringstatechange', function() use (&$states, $pc) {
            $states['iceGatheringState'][] = $pc->getIceGatheringState();
        });

        $pc->on('signalingstatechange', function() use (&$states, $pc) {
            $states['signalingState'][] = $pc->getSignalingState();
        });
    }

    /**
     * Track remote tracks added to peer connection
     */
    public static function trackRemoteTracks(RTCPeerConnection $pc, array &$tracks): void
    {
        $pc->on('track', function($track) use (&$tracks) {
            $tracks[] = $track;
        });
    }
}
