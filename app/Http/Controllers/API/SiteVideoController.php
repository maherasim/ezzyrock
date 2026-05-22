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

        return [
            'id' => $video->id,
            'title' => $video->title,
            'status' => $video->status,
            'video_url' => $media ? $media->getFullUrl() : null,
            'file_name' => $media?->file_name,
            'mime_type' => $media?->mime_type,
            'size' => $media?->size,
            'updated_at' => optional($video->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
