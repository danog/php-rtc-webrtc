<?php

namespace Webrtc\Webrtc\Listener;

/**
 * Notified when an {@see \Webrtc\Webrtc\RTCPeerConnection} changes its ICE gathering state.
 *
 * A typed replacement for the former Evenement "icegatheringstatechange" event: the listener object
 * is registered on the peer connection and, being an ordinary object rather than a closure, survives
 * a serialize cycle as part of the graph with no wrapper.
 */
interface PeerConnectionIceGatheringStateChangeListener
{
    public function onPeerConnectionIceGatheringStateChange(): void;
}
