<?php

namespace Tests\Webrtc\Webrtc;

use Closure;
use Webrtc\DataChannel\Listener\DataChannelMessageListener;
use Webrtc\DataChannel\Listener\DataChannelOpenListener;
use Webrtc\DataChannel\RTCDataChannel;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;
use Webrtc\SDP\RTCSessionDescription;
use Webrtc\Webrtc\Listener\PeerConnectionConnectionStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionDataChannelListener;
use Webrtc\Webrtc\Listener\PeerConnectionIceConnectionStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionIceGatheringStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionSignalingStateChangeListener;
use Webrtc\Webrtc\Listener\PeerConnectionTrackListener;
use Webrtc\Webrtc\RTCConfigurationInterface;
use Webrtc\Webrtc\RTCPeerConnection;

class RTCPeerConnectionHelper
{
    /**
     * The listener registries are WeakMaps, so a typed listener object must be held strongly
     * somewhere for as long as the test needs it. This static sink keeps every listener the helper
     * registers alive for the lifetime of the test process.
     *
     * @var list<object>
     */
    private static array $keptListeners = [];

    /**
     * Create a peer connection whose receivers expose encoded media.
     */
    public static function createPeerConnection(null|array|RTCConfigurationInterface $configuration = null): RTCPeerConnection
    {
        $pc = new RTCPeerConnection($configuration);
        $listener = new class($pc) implements PeerConnectionTrackListener {
            public function __construct(private RTCPeerConnection $pc)
            {
            }

            public function onPeerConnectionTrack(MediaStreamTrack $track): void
            {
                foreach ($this->pc->getReceivers() as $receiver) {
                    if ($receiver->getTrack() === $track) {
                        $receiver->setRawMode(true);
                        return;
                    }
                }
            }
        };
        $pc->addTrackListener($listener);
        self::$keptListeners[] = $listener;

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

        $listener = new class($pc, $states) implements
            PeerConnectionConnectionStateChangeListener,
            PeerConnectionIceConnectionStateChangeListener,
            PeerConnectionIceGatheringStateChangeListener,
            PeerConnectionSignalingStateChangeListener {
            /** @var array<string, list<mixed>> */
            public array $states;

            public function __construct(private RTCPeerConnection $pc, array &$states)
            {
                $this->states = &$states;
            }

            public function onPeerConnectionConnectionStateChange(): void
            {
                $this->states['connectionState'][] = $this->pc->getConnectionState();
            }

            public function onPeerConnectionIceConnectionStateChange(): void
            {
                $this->states['iceConnectionState'][] = $this->pc->getIceConnectionState();
            }

            public function onPeerConnectionIceGatheringStateChange(): void
            {
                $this->states['iceGatheringState'][] = $this->pc->getIceGatheringState();
            }

            public function onPeerConnectionSignalingStateChange(): void
            {
                $this->states['signalingState'][] = $this->pc->getSignalingState();
            }
        };

        $pc->addConnectionStateChangeListener($listener);
        $pc->addIceConnectionStateChangeListener($listener);
        $pc->addIceGatheringStateChangeListener($listener);
        $pc->addSignalingStateChangeListener($listener);
        self::$keptListeners[] = $listener;
    }

    /**
     * Track remote tracks added to peer connection
     */
    public static function trackRemoteTracks(RTCPeerConnection $pc, array &$tracks): void
    {
        $listener = new class($tracks) implements PeerConnectionTrackListener {
            /** @var list<MediaStreamTrack> */
            public array $tracks;

            public function __construct(array &$tracks)
            {
                $this->tracks = &$tracks;
            }

            public function onPeerConnectionTrack(MediaStreamTrack $track): void
            {
                $this->tracks[] = $track;
            }
        };
        $pc->addTrackListener($listener);
        self::$keptListeners[] = $listener;
    }

    /**
     * Register a "datachannel" handler as a typed listener (was $pc->on('datachannel', ...)).
     *
     * The closure body is unchanged; the anonymous listener adapts it to
     * {@see PeerConnectionDataChannelListener} and is held strongly against the WeakMap registry.
     */
    public static function onDataChannel(RTCPeerConnection $pc, Closure $callback): void
    {
        $listener = new class($callback) implements PeerConnectionDataChannelListener {
            public function __construct(private Closure $callback)
            {
            }

            public function onPeerConnectionDataChannel(RTCDataChannel $channel): void
            {
                ($this->callback)($channel);
            }
        };
        $pc->addPeerConnectionDataChannelListener($listener);
        self::$keptListeners[] = $listener;
    }

    /**
     * Register a data-channel "message" handler as a typed listener (was $channel->on('message', ...)).
     */
    public static function onMessage(RTCDataChannel $channel, Closure $callback): void
    {
        $listener = new class($callback) implements DataChannelMessageListener {
            public function __construct(private Closure $callback)
            {
            }

            public function onDataChannelMessage(string $data): void
            {
                ($this->callback)($data);
            }
        };
        $channel->addMessageListener($listener);
        self::$keptListeners[] = $listener;
    }

    /**
     * Register a data-channel "open" handler as a typed listener (was $channel->on('open', ...)).
     */
    public static function onOpen(RTCDataChannel $channel, Closure $callback): void
    {
        $listener = new class($callback) implements DataChannelOpenListener {
            public function __construct(private Closure $callback)
            {
            }

            public function onDataChannelOpen(): void
            {
                ($this->callback)();
            }
        };
        $channel->addOpenListener($listener);
        self::$keptListeners[] = $listener;
    }
}
