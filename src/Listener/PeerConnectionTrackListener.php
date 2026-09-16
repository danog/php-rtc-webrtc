<?php

namespace Webrtc\Webrtc\Listener;

use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

/**
 * Notified when an {@see \Webrtc\Webrtc\RTCPeerConnection} receives a remote media track.
 *
 * A typed replacement for the former Evenement "track" event: the listener object is registered on
 * the peer connection and, being an ordinary object rather than a closure, survives a serialize
 * cycle as part of the graph with no wrapper.
 */
interface PeerConnectionTrackListener
{
    public function onPeerConnectionTrack(MediaStreamTrack $track): void;
}
