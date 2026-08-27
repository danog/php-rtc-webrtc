<?php

namespace Tests\Webrtc\Webrtc;

use Revolt\EventLoop;
use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

use function Amp\delay;

final class PreEncodedVideoStreamTrack extends MediaStreamTrack
{
    private const INTERVAL = 0.960;

    public function __construct(?string $id = null)
    {
        parent::__construct(MediaKind::Video, $id);
        EventLoop::queue(function () {
            $timestamp = 0;
            while (!$this->isEnded()) {
                $packet = new EncodedPacket("\x00\x00\x00\x01\x65\x88\x84", $timestamp);
                $this->frameQueue->push($packet);
                $timestamp += 3000;
                delay(self::INTERVAL);
            }
        });
    }
}
