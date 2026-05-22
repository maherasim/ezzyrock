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
                'created_by' => auth()->id(),
            ]
        );

        $request->validate([
            'title' => 'nullable|string|max:255',
            'status' => 'required|in:0,1',
            'video' => [
                $video->getFirstMedia('site_video') ? 'nullable' : 'required',
                'file',
                'mimes:mp4,mov,avi,webm,mkv',
                'max:102400',
            ],
        ]);

        $video->fill([
            'title' => $request->title ?: 'Uploaded Video',
            'status' => (int) $request->status,
            'updated_by' => auth()->id(),
        ]);

        if (!$video->created_by) {
            $video->created_by = auth()->id();
        }

        $video->save();

        if ($request->hasFile('video')) {
            $video->clearMediaCollection('site_video');
            $video->addMedia($request->file('video'))->toMediaCollection('site_video');
        }

        return redirect()->route('uploaded-video.edit')->withSuccess(__('messages.update_form', ['form' => __('messages.upload_video')]));
    }
}
