@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h3 class="text-capitalize mb-0">{{ __('messages.my_posts') }}</h3>
            <a href="{{ route('user.my-posts.create') }}" class="btn btn-primary btn-sm">{{ __('messages.add_classified_post') }}</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="border rounded-3 p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h6 class="mb-1">Free Posts</h6>
                            <p class="text-muted small mb-0">Normal posts only. Resets every month.</p>
                        </div>
                        <span class="badge bg-primary">{{ $freePostQuota['remaining'] ?? 0 }} left</span>
                    </div>
                    <div class="small text-muted mt-2">
                        Used {{ $freePostQuota['used_this_month'] ?? 0 }} of {{ $freePostQuota['monthly_limit'] ?? 0 }}
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h6 class="mb-1">Featured Posts</h6>
                            <p class="text-muted small mb-0">Requires an active featured plan.</p>
                        </div>
                        <span class="badge bg-warning text-dark">
                            {{ !empty($featuredPostQuota['is_unlimited']) ? 'Unlimited' : (($featuredPostQuota['remaining'] ?? 0) . ' left') }}
                        </span>
                    </div>
                    <div class="small text-muted mt-2">
                        @if(!empty($featuredPostQuota['has_active_subscription']))
                            Used {{ $featuredPostQuota['used'] ?? 0 }} of {{ $featuredPostQuota['total_limit'] ?? 'Unlimited' }}
                        @else
                            No active featured plan
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        @if ($posts->isEmpty())
            <p class="text-body">{{ __('messages.no_record_found') }}</p>
            <a href="{{ route('user.my-posts.create') }}" class="btn btn-primary">{{ __('messages.add_classified_post') }}</a>
        @else
            <div class="row g-4">
                @foreach ($posts as $post)
                    <div class="col-md-6 col-lg-4">
                        <div class="border rounded-3 p-3 h-100 bg-white">
                            <a href="{{ route('post.detail', $post->id) }}" class="d-block mb-2">
                                <img src="{{ getSingleMedia($post, 'post_attachment', null) }}" alt="" class="w-100 rounded-2 object-cover" style="height: 160px;">
                            </a>
                            <h5 class="line-count-2 font-size-16 mb-2">
                                <a href="{{ route('post.detail', $post->id) }}" class="text-decoration-none text-body">{{ $post->name }}</a>
                            </h5>
                            <p class="text-primary fw-500 mb-3">{{ getPriceFormat($post->price) }}</p>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <a href="{{ route('user.my-posts.edit', $post) }}" class="btn btn-sm btn-outline-primary">{{ __('messages.edit') }}</a>
                                <form action="{{ route('user.my-posts.destroy', $post) }}" method="post" class="d-inline"
                                    onsubmit="return confirm(@json(__('messages.delete_form_message', ['form' => __('messages.post')])));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('messages.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $posts->links() }}</div>
        @endif
    </div>
</div>
@endsection
