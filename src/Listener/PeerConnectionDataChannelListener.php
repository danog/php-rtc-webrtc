<?php

namespace Webrtc\Webrtc\Listener;

use Webrtc\DataChannel\RTCDataChannel;

/**
 * Notified when an {@see \Webrtc\Webrtc\RTCPeerConnection} receives a remotely-opened data channel.
 *
 * A typed replacement for the former Evenement "datachannel" event: the listener object is
 * registered on the peer connection and, being an ordinary object rather than a closure, survives a
 * serialize cycle as part of the graph with no wrapper.
 */
interface PeerConnectionDataChannelListener
{
    public function onPeerConnectionDataChannel(RTCDataChannel $channel): void;
}
