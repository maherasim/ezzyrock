<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="fw-bold">{{ $pageTitle ?? __('messages.upload_video') }}</h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        {{ html()->form('POST', route('uploaded-video.update'))->attribute('enctype', 'multipart/form-data')->open() }}
                            @csrf

                            <div class="row">
                                <div class="form-group col-md-6">
                                    {{ html()->label(__('messages.title'), 'title')->class('form-control-label') }}
                                    {{ html()->text('title', old('title', $video->title))->class('form-control')->placeholder(__('messages.title')) }}
                                </div>

                                <div class="form-group col-md-6">
                                    {{ html()->label(__('messages.status') . ' <span class="text-danger">*</span>', 'status')->class('form-control-label') }}
                                    {{ html()->select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status', $video->status))->class('form-select select2js')->required() }}
                                </div>

                                <div class="form-group col-md-12">
                                    <label class="form-control-label" for="youtube_url">
                                        YouTube Link @if(!$video->youtube_url && !$video->getFirstMedia('site_video'))<span class="text-danger">*</span>@endif
                                    </label>
                                    <input type="url" name="youtube_url" id="youtube_url" class="form-control" value="{{ old('youtube_url', $video->youtube_url) }}" placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
                                    <small class="text-muted d-block mt-1">Paste a YouTube video link. If provided, it replaces the uploaded video.</small>
                                </div>

                                <div class="form-group col-md-12">
                                    <label class="form-control-label" for="video">
                                        {{ __('messages.video') }}
                                    </label>
                                    <input type="file" name="video" id="video" class="form-control" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo,video/x-matroska">
                                    <small class="text-muted d-block mt-1">Optional fallback. Allowed: mp4, mov, avi, webm, mkv. Max 100 MB. Clear the YouTube link to use an uploaded file.</small>
                                </div>

                                @php
                                    $media = $video->getFirstMedia('site_video');
                                @endphp
                                @if($video->youtube_url)
                                    <div class="form-group col-md-12">
                                        <label class="form-control-label">{{ __('messages.current_video') }}</label>
                                        <div class="ratio ratio-16x9 rounded border overflow-hidden">
                                            <iframe src="https://www.youtube.com/embed/{{ $video->youtube_video_id }}" title="{{ $video->title }}" allowfullscreen></iframe>
                                        </div>
                                        <div class="mt-2">
                                            <a href="{{ $video->youtube_url }}" target="_blank">{{ $video->youtube_url }}</a>
                                        </div>
                                    </div>
                                @elseif($media)
                                    <div class="form-group col-md-12">
                                        <label class="form-control-label">{{ __('messages.current_video') }}</label>
                                        <video controls class="w-100 rounded border" style="max-height: 420px;">
                                            <source src="{{ $media->getFullUrl() }}" type="{{ $media->mime_type }}">
                                        </video>
                                        <div class="mt-2">
                                            <a href="{{ $media->getFullUrl() }}" target="_blank">{{ $media->file_name }}</a>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                            </div>
                        {{ html()->form()->close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
