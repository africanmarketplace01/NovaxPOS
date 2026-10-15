<?php

namespace App\Models\landlord;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoTutorial extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'category', 'video_type',
        'video_url', 'video_path', 'thumbnail_path',
        'duration', 'sort_order', 'is_published', 'compression_status',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order'   => 'integer',
    ];

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * URL-ready iframe embed src (YouTube / Vimeo only).
     */
    public function getEmbedUrlAttribute(): string
    {
        if ($this->video_type === 'youtube') {
            $id = $this->extractYouTubeId($this->video_url ?? '');
            return $id ? "https://www.youtube.com/embed/{$id}?rel=0&modestbranding=1&autoplay=1" : '';
        }
        if ($this->video_type === 'vimeo') {
            $id = $this->extractVimeoId($this->video_url ?? '');
            return $id ? "https://player.vimeo.com/video/{$id}?autoplay=1" : '';
        }
        return ''; // upload — served directly
    }

    /**
     * Thumbnail URL: custom > YouTube auto-thumb > placeholder.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            return Storage::disk('public')->url($this->thumbnail_path);
        }
        if ($this->video_type === 'youtube') {
            $id = $this->extractYouTubeId($this->video_url ?? '');
            return $id ? "https://img.youtube.com/vi/{$id}/mqdefault.jpg" : asset('images/video-placeholder.svg');
        }
        if ($this->video_type === 'upload' && $this->video_path) {
            return asset('images/video-placeholder.svg');
        }
        return asset('images/video-placeholder.svg');
    }

    /**
     * Public URL for an uploaded video file.
     */
    public function getVideoPublicUrlAttribute(): ?string
    {
        if ($this->video_type === 'upload' && $this->video_path) {
            return Storage::disk('public')->url($this->video_path);
        }
        return null;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function extractYouTubeId(string $url): string
    {
        preg_match(
            '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/',
            $url,
            $m
        );
        return $m[1] ?? '';
    }

    public function extractVimeoId(string $url): string
    {
        preg_match('/vimeo\.com\/(\d+)/', $url, $m);
        return $m[1] ?? '';
    }
}
