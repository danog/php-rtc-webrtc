<?php

namespace Tests\Webrtc\Webrtc;

use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

final class PreEncodedVideoStreamTrack extends MediaStreamTrack
{
    protected MediaKind $kind = MediaKind::Video;

    private int $timestamp = 0;

    public function receiveData(): EncodedPacket
    {
        $packet = new EncodedPacket("\x00\x00\x00\x01\x65\x88\x84", $this->timestamp);
        $this->timestamp += 3000;

        return $packet;
    }
}
