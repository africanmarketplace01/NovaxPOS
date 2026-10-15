<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('video_tutorials', function (Blueprint $table) {
            // 'pending' = original stored, awaiting compression
            // 'processing' = FFmpeg running
            // 'done' = compressed
            // 'failed' = FFmpeg error (original kept)
            // null = YouTube/Vimeo (no compression needed)
            $table->string('compression_status', 20)->nullable()->after('video_path');
        });
    }

    public function down(): void
    {
        Schema::table('video_tutorials', function (Blueprint $table) {
            $table->dropColumn('compression_status');
        });
    }
};
