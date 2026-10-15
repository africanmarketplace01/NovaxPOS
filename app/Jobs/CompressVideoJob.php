<?php

namespace App\Jobs;

use App\Models\landlord\VideoTutorial;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompressVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min max for large files
    public int $tries   = 1;   // don't retry — FFmpeg failure is usually deterministic

    public function __construct(private int $tutorialId) {}

    public function handle(): void
    {
        $tutorial = VideoTutorial::find($this->tutorialId);
        if (! $tutorial || ! $tutorial->video_path) {
            return;
        }

        $tutorial->update(['compression_status' => 'processing']);

        $originalPath = storage_path('app/public/' . $tutorial->video_path);

        if (! file_exists($originalPath)) {
            $tutorial->update(['compression_status' => 'failed']);
            return;
        }

        $dir          = 'video-tutorials/videos';
        $compressedName = Str::uuid() . '.mp4';
        $compressedPath = storage_path("app/public/{$dir}/{$compressedName}");

        $success = $this->compress($originalPath, $compressedPath);

        if ($success) {
            // Swap to compressed file, delete original
            $oldPath = $tutorial->video_path;
            $tutorial->update([
                'video_path'         => "{$dir}/{$compressedName}",
                'compression_status' => 'done',
            ]);
            Storage::disk('public')->delete($oldPath);
        } else {
            // Keep original, mark failed (still playable)
            $tutorial->update(['compression_status' => 'failed']);
            // Clean up any partial output
            if (file_exists($compressedPath)) {
                @unlink($compressedPath);
            }
        }
    }

    private function compress(string $input, string $output): bool
    {
        // Configurable binary path — set FFMPEG_PATH in .env if ffmpeg isn't in PATH
        $ffmpeg = config('services.ffmpeg.path', 'ffmpeg');

        // 720p max, CRF 28 (good quality/size), AAC 128k, fast-start for web
        $cmd = sprintf(
            escapeshellcmd($ffmpeg) . ' -y -i %s '
            . '-vf "scale=\'min(1280,iw)\':\'min(720,ih)\':force_original_aspect_ratio=decrease" '
            . '-c:v libx264 -crf 28 -preset fast -movflags +faststart '
            . '-c:a aac -b:a 128k '
            . '%s 2>/dev/null',
            escapeshellarg($input),
            escapeshellarg($output)
        );
        exec($cmd, $out, $code);
        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }
}
