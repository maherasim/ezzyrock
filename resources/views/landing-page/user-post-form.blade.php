@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container" style="max-width: 720px;">
        <div class="mb-4">
            <a href="{{ route('user.my-posts') }}" class="btn btn-link p-0">← {{ __('messages.back') }}</a>
        </div>
        <h3 class="text-capitalize mb-4">{{ $pageTitle }}</h3>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @php
            $isEdit = (bool) $post->id;
            $formAction = $isEdit ? route('user.my-posts.update', $post) : route('user.my-posts.store');
            $formMethod = $isEdit ? 'PUT' : 'POST';
            $isCurrentlyFeatured = (int) ($post->is_featured ?? 0) === 1;
            $canCreateFreePost = (bool) ($freePostQuota['allow_to_create_post'] ?? false);
            $canCreateFeaturedPost = (bool) ($featuredPostQuota['allow_to_create_featured'] ?? false);
        @endphp

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
                    <div class="small text-muted mt-2">Used {{ $freePostQuota['used_this_month'] ?? 0 }} of {{ $freePostQuota['monthly_limit'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 p-3 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h6 class="mb-1">Featured Posts</h6>
                            <p class="text-muted small mb-0">Requires an active featured plan.</p>
                        </div>
                        <span class="badge bg-warning text-dark">{{ !empty($featuredPostQuota['is_unlimited']) ? 'Unlimited' : (($featuredPostQuota['remaining'] ?? 0) . ' left') }}</span>
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

        <form action="{{ $formAction }}" method="post" enctype="multipart/form-data" class="card border-0 shadow-sm">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $post->name) }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.category') }} <span class="text-danger">*</span></label>
                    <select name="category_id" id="classified_category_id" class="form-select" required>
                        <option value="">{{ __('messages.select_name', ['select' => __('messages.category')]) }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id', $post->category_id) == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.subcategory') }}</label>
                    <select name="subcategory_id" id="classified_subcategory_id" class="form-select">
                        <option value="">{{ __('messages.select_name', ['select' => __('messages.subcategory')]) }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.description') }}</label>
                    <textarea name="description" class="form-control" rows="4" maxlength="2000">{{ old('description', $post->description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.price') }} <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0" value="{{ old('price', $post->price) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.zone') }} <span class="text-danger">*</span></label>
                    <select name="service_zones[]" id="classified_zone_ids" class="form-select" multiple required>
                        @foreach ($zones as $zone)
                            <option value="{{ $zone->id }}" @selected(in_array((int) $zone->id, (array) ($selectedZoneIds ?? []), true))>
                                {{ $zone->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Default selection follows your current location.</small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_featured_post" name="is_featured" value="1" @checked(old('is_featured', (int) ($post->is_featured ?? 0)) == 1) @disabled(!$isCurrentlyFeatured && !$canCreateFeaturedPost)>
                    <label class="form-check-label" for="is_featured_post">Add as Featured Post</label>
                    @if(!$isCurrentlyFeatured && !$canCreateFeaturedPost)
                        <div class="small text-muted mt-1">
                            Please purchase a featured plan to create featured posts.
                            <a href="{{ route('user.subscriptions.index') }}">View plans</a>
                        </div>
                    @endif
                    @if(!$isEdit && !$canCreateFreePost)
                        <div class="small text-danger mt-1">Your free post limit is finished for this month. You can create a featured post only after purchasing a plan.</div>
                    @endif
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('messages.image') }} @if(!$isEdit)<span class="text-danger">*</span>@endif</label>
                    <input type="file" name="post_attachment[]" class="form-control" accept="image/*" multiple @if(!$isEdit) required @endif>
                    <small class="text-muted">{{ __('messages.only_jpg_png_jpeg_allowed') }}</small>
                </div>
                @if ($isEdit && getMediaFileExit($post, 'post_attachment'))
                    <p class="small text-muted mb-0">{{ __('messages.existing_images') }} — {{ __('messages.choose_file', ['file' => __('messages.image')]) }} {{ __('messages.update') }}.</p>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0 pt-0 px-4 pb-4">
                <button type="submit" id="classified_post_submit" class="btn btn-primary">{{ __('messages.save') }}</button>
                <a href="{{ route('user.subscriptions.index') }}" class="btn btn-outline-primary ms-2">View Plans</a>
            </div>
        </form>
    </div>
</div>
@endsection

@php
    $subJson = $subcategoriesByCategory->toJson();
@endphp
@section('after_script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const subByCat = {!! $subJson !!};
    const catEl = document.getElementById('classified_category_id');
    const subEl = document.getElementById('classified_subcategory_id');
    const selectedSub = @json((int) old('subcategory_id', $post->subcategory_id ?? 0));
    const selectedCat = @json((int) old('category_id', $post->category_id ?? 0));
    const isEdit = @json($isEdit);
    const canCreateFreePost = @json($canCreateFreePost);
    const canCreateFeaturedPost = @json($canCreateFeaturedPost || $isCurrentlyFeatured);
    const featuredEl = document.getElementById('is_featured_post');
    const submitEl = document.getElementById('classified_post_submit');

    function fillSubcategories() {
        const cid = catEl.value;
        subEl.innerHTML = '<option value="">—</option>';
        if (!cid || !subByCat[cid]) return;
        subByCat[cid].forEach(function (s) {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            if (String(cid) === String(selectedCat) && selectedSub && String(s.id) === String(selectedSub)) {
                opt.selected = true;
            }
            subEl.appendChild(opt);
        });
    }
    catEl.addEventListener('change', fillSubcategories);
    fillSubcategories();

    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#classified_zone_ids').select2({
            width: '100%',
            placeholder: @json(__('messages.select_name', ['select' => __('messages.zone')])),
        });
    }

    function updateSubmitState() {
        if (!submitEl || isEdit) return;
        const wantsFeatured = featuredEl && featuredEl.checked;
        submitEl.disabled = wantsFeatured ? !canCreateFeaturedPost : !canCreateFreePost;
    }

    if (featuredEl) {
        featuredEl.addEventListener('change', updateSubmitState);
    }
    updateSubmitState();
});
</script>
@endsection
