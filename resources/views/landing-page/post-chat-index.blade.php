@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h4 class="mb-0">{{ __('messages.post_chats') }}</h4>
        </div>

        @if($conversations->isEmpty())
            <div class="border rounded-3 p-4 text-center text-body">
                {{ __('messages.no_record_found') }}
            </div>
        @else
            <div class="list-group">
                @foreach($conversations as $conversation)
                    @php
                        $me = auth()->id();
                        $other = (int) $conversation->seller_id === (int) $me ? $conversation->buyer : $conversation->seller;
                        $post = $conversation->post;
                    @endphp
                    <a href="{{ route('post.chat.show', $conversation->id) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h6 class="mb-1">{{ $post?->name ?? __('messages.post') }}</h6>
                                <div class="small text-body">
                                    {{ __('messages.with_user', ['name' => $other?->display_name ?? __('messages.unknown_user')]) }}
                                </div>
                            </div>
                            <small class="text-muted">{{ optional($conversation->last_message_at)->diffForHumans() }}</small>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-3">
                {{ $conversations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
