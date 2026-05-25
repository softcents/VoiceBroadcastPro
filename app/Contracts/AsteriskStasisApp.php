<?php

declare(strict_types=1);

namespace App\Contracts;

use OpiyOrg\AriClient\Model\Message\Event\ChannelHangupRequest;
use OpiyOrg\AriClient\Model\Message\Event\PlaybackFinished;
use OpiyOrg\AriClient\Model\Message\Event\StasisStart;

interface AsteriskStasisApp
{
    public function onAriEventStasisStart(StasisStart $stasisStart): void;

    public function onAriEventPlaybackFinished(PlaybackFinished $event): void;

    public function onAriEventChannelHangupRequest(ChannelHangupRequest $channelHangupRequest): void;
}
