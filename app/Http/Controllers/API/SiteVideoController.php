<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SiteVideo;

class SiteVideoController extends Controller
{
    public function show()
    {
        $video = SiteVideo::query()->where('status', 1)->first();

        return response()->json([
            'status' => true,
            'data' => $video ? $this->serializeVideo($video) : null,
        ]);
    }

    public function list()
    {
        $video = SiteVideo::query()->where('status', 1)->first();

        return response()->json([
            'status' => true,
            'data' => $video ? [$this->serializeVideo($video)] : [],
        ]);
    }

    private function serializeVideo(SiteVideo $video): array
    {
        $media = $video->getFirstMedia('site_video');
        $isYoutube = $video->video_type === 'youtube' && $video->youtube_url;
        $youtubeEmbedUrl = $video->youtube_video_id
            ? 'https://www.youtube.com/embed/' . $video->youtube_video_id
            : null;

        return [
            'id' => $video->id,
            'title' => $video->title,
            'status' => $video->status,
            'video_type' => $isYoutube ? 'youtube' : 'upload',
            'video_url' => $isYoutube ? $video->youtube_url : ($media ? $media->getFullUrl() : null),
            'youtube_url' => $isYoutube ? $video->youtube_url : null,
            'youtube_video_id' => $isYoutube ? $video->youtube_video_id : null,
            'youtube_embed_url' => $isYoutube ? $youtubeEmbedUrl : null,
            'file_name' => $isYoutube ? null : $media?->file_name,
            'mime_type' => $isYoutube ? null : $media?->mime_type,
            'size' => $isYoutube ? null : $media?->size,
            'updated_at' => optional($video->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
