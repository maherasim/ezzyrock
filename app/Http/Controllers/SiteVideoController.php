<?php

namespace App\Http\Controllers;

use App\Models\SiteVideo;
use Illuminate\Http\Request;

class SiteVideoController extends Controller
{
    public function edit()
    {
        $video = SiteVideo::query()->firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Uploaded Video',
                'status' => 1,
                'video_type' => 'upload',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        $pageTitle = __('messages.upload_video');

        return view('site-video.edit', compact('video', 'pageTitle'));
    }

    public function update(Request $request)
    {
        $video = SiteVideo::query()->firstOrCreate(
            ['id' => 1],
            [
                'title' => 'Uploaded Video',
                'status' => 1,
                'video_type' => 'upload',
                'created_by' => auth()->id(),
            ]
        );

        $hasExistingVideo = $video->youtube_url || $video->getFirstMedia('site_video');
        $request->validate([
            'title' => 'nullable|string|max:255',
            'status' => 'required|in:0,1',
            'youtube_url' => 'nullable|string|max:255',
            'video' => [
                $hasExistingVideo ? 'nullable' : 'required_without:youtube_url',
                'file',
                'mimes:mp4,mov,avi,webm,mkv',
                'max:102400',
            ],
        ]);

        $youtubeUrl = null;
        $youtubeVideoId = null;
        if ($request->filled('youtube_url')) {
            $youtubeVideoId = $this->youtubeVideoId((string) $request->youtube_url);
            if (!$youtubeVideoId) {
                return redirect()
                    ->back()
                    ->withErrors('Please enter a valid YouTube video URL.')
                    ->withInput();
            }

            $youtubeUrl = 'https://www.youtube.com/watch?v=' . $youtubeVideoId;
        }

        $payload = [
            'title' => $request->title ?: 'Uploaded Video',
            'status' => (int) $request->status,
            'updated_by' => auth()->id(),
        ];

        if ($youtubeVideoId) {
            $payload['video_type'] = 'youtube';
            $payload['youtube_url'] = $youtubeUrl;
            $payload['youtube_video_id'] = $youtubeVideoId;
        } elseif ($request->hasFile('video')) {
            $payload['video_type'] = 'upload';
            $payload['youtube_url'] = null;
            $payload['youtube_video_id'] = null;
        }

        $video->fill($payload);

        if (!$video->created_by) {
            $video->created_by = auth()->id();
        }

        $video->save();

        if ($request->hasFile('video')) {
            $video->clearMediaCollection('site_video');
            $video->addMedia($request->file('video'))->toMediaCollection('site_video');
        }

        if ($youtubeVideoId) {
            $video->clearMediaCollection('site_video');
        }

        return redirect()->route('uploaded-video.edit')->withSuccess(__('messages.update_form', ['form' => __('messages.upload_video')]));
    }

    private function youtubeVideoId(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (str_contains($host, 'youtu.be')) {
            return $this->validYoutubeId(explode('/', $path)[0] ?? null);
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str($parts['query'] ?? '', $query);
            if (!empty($query['v'])) {
                return $this->validYoutubeId($query['v']);
            }

            $segments = explode('/', $path);
            if (in_array($segments[0] ?? '', ['embed', 'shorts', 'live'], true)) {
                return $this->validYoutubeId($segments[1] ?? null);
            }
        }

        return null;
    }

    private function validYoutubeId(?string $id): ?string
    {
        $id = trim((string) $id);

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }
}
