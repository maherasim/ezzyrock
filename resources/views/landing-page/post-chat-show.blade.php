@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        @php
            $me = auth()->id();
            $other = (int) $conversation->seller_id === (int) $me ? $conversation->buyer : $conversation->seller;
        @endphp

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h5 class="mb-1">{{ $conversation->post?->name ?? __('messages.post') }}</h5>
                <small class="text-body">{{ __('messages.chatting_with', ['name' => $other?->display_name ?? __('messages.unknown_user')]) }}</small>
            </div>
            <a href="{{ route('post.chat.index') }}" class="btn btn-outline-primary btn-sm">{{ __('messages.back') }}</a>
        </div>

        <div class="border rounded-3 p-3 bg-white" style="min-height: 300px; max-height: 500px; overflow-y: auto;">
            @forelse($messages as $msg)
                <div class="d-flex mb-2 {{ (int) $msg->sender_id === (int) $me ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="px-3 py-2 rounded {{ (int) $msg->sender_id === (int) $me ? 'bg-primary text-white' : 'bg-light' }}" style="max-width: 75%;">
                        <div class="small">{{ $msg->message }}</div>
                        <div class="small {{ (int) $msg->sender_id === (int) $me ? 'text-white-50' : 'text-muted' }}">
                            {{ $msg->created_at->format('d M Y h:i A') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-body py-4">{{ __('messages.no_messages_yet') }}</div>
            @endforelse
        </div>

        <form action="{{ route('post.chat.send', $conversation->id) }}" method="POST" class="mt-3">
            @csrf
            <div class="input-group">
                <input type="text" name="message" class="form-control" maxlength="2000" placeholder="{{ __('messages.type_message') }}" required>
                <button type="submit" class="btn btn-primary">{{ __('messages.send') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
