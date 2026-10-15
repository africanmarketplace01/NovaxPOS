<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_tutorials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('General');
            $table->enum('video_type', ['youtube', 'vimeo', 'upload'])->default('youtube');
            $table->string('video_url', 512)->nullable();   // YouTube or Vimeo URL
            $table->string('video_path')->nullable();       // uploaded file path
            $table->string('thumbnail_path')->nullable();   // custom thumbnail
            $table->string('duration', 20)->nullable();     // e.g. "5:30"
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_tutorials');
    }
};
