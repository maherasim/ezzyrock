@if ($user)
    <div class="d-flex gap-3 align-items-center">
        <img src="{{ getSingleMedia($user, 'profile_image', null) }}" alt="" class="avatar avatar-40 rounded-pill">
        <div class="text-start">
            <h6 class="m-0">{{ $user->display_name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: '—' }}</h6>
            <span>{{ $user->email ?? '—' }}</span>
        </div>
    </div>
@else
    <div class="align-items-center">
        <h6 class="text-center m-0">—</h6>
    </div>
@endif
