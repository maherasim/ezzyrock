<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_videos', function (Blueprint $table) {
            $table->string('video_type')->default('upload')->after('status');
            $table->string('youtube_url')->nullable()->after('video_type');
            $table->string('youtube_video_id', 32)->nullable()->after('youtube_url');
        });
    }

    public function down(): void
    {
        Schema::table('site_videos', function (Blueprint $table) {
            $table->dropColumn(['video_type', 'youtube_url', 'youtube_video_id']);
        });
    }
};
