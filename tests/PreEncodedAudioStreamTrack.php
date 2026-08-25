<?php

namespace Tests\Webrtc\Webrtc;

use Webrtc\Codecs\EncodedPacket;
use Webrtc\RTP\Enum\MediaKind;
use Webrtc\RTP\MediaStreamTrack\MediaStreamTrack;

final class PreEncodedAudioStreamTrack extends MediaStreamTrack
{
    protected MediaKind $kind = MediaKind::Audio;

    private int $timestamp = 0;

    public function receiveData(): EncodedPacket
    {
        $packet = new EncodedPacket("\xf8\xff\xfe", $this->timestamp, audioLevel: 127);
        $this->timestamp += 960;

        return $packet;
    }
}
