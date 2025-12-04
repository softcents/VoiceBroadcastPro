<?php

namespace App\Jobs;

use App\Models\Audio;
use FFMpeg\Format\Audio\Wav;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ConvertAudioForAsterisk implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Audio $audio)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inputPath = $this->audio->original_path;
        $outputPath = 'audios/converted/' . $this->audio->id . '.alaw';

        // Ensure directory exists
        if (!Storage::disk('public')->exists('audios/converted')) {
            Storage::disk('public')->makeDirectory('audios/converted');
        }

        // Convert to ALAW 8khz mono
        // Asterisk ALAW is basically PCM A-law, 8000Hz, Mono.
        // We can use Wav format with specific codec or raw format.
        // Usually .alaw extension implies raw A-law data.
        
        $ffmpeg = FFMpeg::fromDisk('public')
            ->open($inputPath);
            
        $duration = $ffmpeg->getDurationInSeconds();

        $ffmpeg->export()
            ->inFormat(new Wav)
            ->addFilter(['-acodec', 'pcm_alaw'])
            ->addFilter(['-ar', '8000'])
            ->addFilter(['-ac', '1'])
            ->save($outputPath);

        $this->audio->update([
            'converted_path' => $outputPath,
            'duration' => $duration,
            'size' => Storage::disk('public')->size($outputPath),
        ]);
    }
}
