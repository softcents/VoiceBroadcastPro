<?php

declare(strict_types=1);

namespace App\Services\TTS;

use App\Services\TTS\Contracts\TTSDriver;
use App\Services\TTS\Drivers\AzureTTSDriver;
use App\Services\TTS\Drivers\FrolaxTTSDriver;
use Illuminate\Support\Manager;

final class TTSManager extends Manager
{
    public function getDefaultDriver()
    {
        return $this->config->get('tts.default', 'azure');
    }

    public function createAzureDriver(): TTSDriver
    {
        return new AzureTTSDriver(
            $this->config->get('services.azure.tts.key'),
            $this->config->get('services.azure.tts.region')
        );
    }

    public function createFrolaxDriver(): TTSDriver
    {
        return new FrolaxTTSDriver();
    }
}
