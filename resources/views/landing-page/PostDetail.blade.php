@extends('landing-page.layouts.default')

@section('content')
@php
    $post = $postData['post_detail'];
    $subtotal = isset($post->discount) && $post->discount > 0
        ? $post->price - ($post->price * $post->discount / 100)
        : $post->price;
@endphp
<div class="section-padding service-detail">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 pe-xxl-5">
                <h3 class="text-capitalize mb-2">{{ $post->name }}</h3>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <ul class="service-meta-list list-inline m-0 d-flex align-items-center flex-wrap">
                        @if(!empty($post->duration))
                            @php
                                $durationParts = explode(':', $post->duration);
                                $hours = isset($durationParts[0]) ? intval($durationParts[0]) : 0;
                                $minutes = isset($durationParts[1]) ? intval($durationParts[1]) : 0;
                            @endphp
                            @if($hours > 0 || $minutes > 0)
                                <li>
                                    <h6 class="text-body">
                                        ({{ $hours }} hrs @if($minutes > 0) {{ $minutes }} min @endif)
                                    </h6>
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
                @if(!empty($post->attchments) && count($post->attchments) > 0)
                    <div class="mt-5">
                        <section-thumbnail-section
                            :attachments="{{ json_encode($post->attchments) }}"></section-thumbnail-section>
                    </div>
                @else
                    <img src="{{ $post->post_image ?? asset('images/default.png') }}" alt=""
                        class="img-fluid object-cover rounded-3 mt-4 w-100" />
                @endif
                @if(!empty($post->description))
                    <div class="mt-5 pt-lg-5 pt-3">
                        <h5 class="mb-3">About Post</h5>
                        <p class="m-0">{{ $post->description }}</p>
                    </div>
                @endif


                @if($post->price > 0)
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3 text-capitalize">{{ __('landingpage.order_detail') }}</h5>
                    <div class="p-5 border rounded-3">
                        <h6 class="mb-1">{{ __('messages.post') }}</h6>
                        <p class="m-0 text-capitalize">{{ $post->name }}</p>
                        <div class="mt-5 border-top">
                            <div class="table-responsive">
                                <table class="table mb-5">
                                    <tbody>
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{ __('messages.price') }}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">+{{ getPriceFormat($post->price) }}</h6>
                                            </td>
                                        </tr>
                                        @if(!empty($post->discount) && $post->discount > 0)
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize">
                                                    <h6>{{ __('messages.discount') }} <span class="text-success">({{ $post->discount }}% Off)</span></h6>
                                                </label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <span class="text-success">-{{ getPriceFormat(($post->price * $post->discount) / 100) }}</span>
                                            </td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <h6 class="text-capitalize m-0">{{ __('messages.Subtotal') }}</h6>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{ getPriceFormat($subtotal) }}</h6>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            <div class="col-lg-4">
                <div class="rounded-3 border p-4 position-sticky" style="top: 100px;">
                    <h5 class="mb-3 text-capitalize">{{ $post->name }}</h5>
                    @if($post->price == 0)
                        <p class="text-primary fw-500 font-size-18">Free</p>
                    @else
                        <p class="text-primary fw-500 font-size-18">
                            {{ getPriceFormat($subtotal) }}
                            @if(!empty($post->discount) && $post->discount > 0)
                                <span class="text-success">({{ $post->discount }}% off)</span>
                            @endif
                        </p>
                    @endif

                    <div class="mt-3 d-grid gap-2">
                        @auth
                            @if(auth()->user()->user_type === 'user' && auth()->id() !== (int) $post->provider_id)
                                <form action="{{ route('post.chat.start', $post->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100">{{ __('messages.chat_with_seller') }}</button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('user.login') }}" class="btn btn-primary w-100">{{ __('messages.login_to_chat') }}</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
