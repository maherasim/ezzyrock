<div class="service-box-card bg-light rounded-3 mb-5" data-service-id="{{ $data->id }}">
   <div class="iq-image position-relative">
      @if(!empty($data->is_featured) && (int) $data->is_featured === 1)
         <span class="badge bg-warning text-dark position-absolute" style="top:10px; right:10px; z-index:2;">Featured</span>
      @endif
      @if($data->visit_type == 'ONLINE')
         <span class="online-service"></span>
      @endif
      <a href="{{ route('service.detail', $data->id) }}" class="service-img">
         <img src="{{ getSingleMedia($data,'service_attachment', null) }}" alt="service"
         class="service-img w-100 object-cover img-fluid rounded-3">
      </a>
      @if($data->visit_type == 'on_shop')
        <div class="position-absolute d-flex justify-content-center align-items-center rounded-circle bg-primary"
            style="width: 25px;height: 25px;top: 13px;left: 1rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="15" height="15" fill="white">
                <path d="M53.5 245.1L110.3 131.4C121.2 109.7 143.3 96 167.6 96L472.5 96C496.7 96 518.9 109.7 529.7 131.4L586.5 245.1C590.1 252.3 592 260.2 592 268.3C592 295.6 570.8 318 544 319.9L544 512C544 529.7 529.7 544 512 544C494.3 544 480 529.7 480 512L480 320L384 320L384 496C384 522.5 362.5 544 336 544L144 544C117.5 544 96 522.5 96 496L96 319.9C69.2 318 48 295.6 48 268.3C48 260.3 49.9 252.3 53.5 245.1zM160 320L160 432C160 440.8 167.2 448 176 448L304 448C312.8 448 320 440.8 320 432L320 320L160 320z"/>
            </svg>
        </div>
      @endif
      @if(auth()->check() && auth()->user()->hasRole('user'))

         @if($favouriteService->isEmpty())
            <form method="POST" id="favoriteForm">
               @csrf

               <input type="hidden" name="service_id" class="service_id" value="{{ $data->id }}" data-service-id="{{ $data->id }}">
               @if(!empty(auth()->user()))
                  <input type="hidden" name="user_id" id="user_id" value="{{ Auth::user()->id }}">
               @endif
               <button type="button" class="btn-link serv-whishlist text-primary save_fav">
                  <svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                     <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                     <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
               </button>
            </form>
         @else
            <form method="POST" id="favoriteForm">
               @csrf

               <input type="hidden" name="service_id" class="service_id" value="{{ $data->id }}" data-service-id="{{ $data->id }}">
               @if(!empty(auth()->user()))
                  <input type="hidden" name="user_id" id="user_id" value="{{ Auth::user()->id }}">
               @endif
               <button type="button" class="btn-link serv-whishlist text-primary delete_fav">
                  <svg width="12" height="13" viewBox="0 0 12 13" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                     <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                     <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                  </svg>
               </button>
            </form>
         @endif
      @else
         <form method="GET" id="favoriteForm" action="{{ route('user.login') }}">
            @csrf
            <button type="submit" class="btn-link serv-whishlist text-primary">
               <svg width="12" height="13" viewBox="0 0 12 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M1.43593 6.29916C0.899433 4.62416 1.52643 2.70966 3.28493 2.14316C4.20993 1.84466 5.23093 2.02066 5.99993 2.59916C6.72743 2.03666 7.78593 1.84666 8.70993 2.14316C10.4684 2.70966 11.0994 4.62416 10.5634 6.29916C9.72843 8.95416 5.99993 10.9992 5.99993 10.9992C5.99993 10.9992 2.29893 8.98516 1.43593 6.29916Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                  <path d="M8 3.84998C8.535 4.02298 8.913 4.50048 8.9585 5.06098" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
               </svg>
            </button>
         </form>
      @endif

   </div>
   <a href="{{ route('service.detail', $data->id) }}" class="service-heading mt-4 d-block p-0">
      <h5 class="service-heading service-title font-size-18 line-count-2">{{$data->name ?? '-' }}</h5>
   </a>
   <ul class="list-inline p-0 mt-0 mb-0 price-content">
      @if($data->price==0)
         <li class="text-primary fw-500 d-inline-block position-relative font-size-18">Free</li>
      @else
         <li class="text-primary fw-500 d-inline-block position-relative font-size-18">
            {{ getPriceFormat($data->price) }}
            @if(isset($data->discount) && $data->discount > 0)
               <span class="text-primary"> ({{ $data->discount }}% off)</span>
            @endif
         </li>
      @endif
      @if(!empty($data->duration))
    @php
        $durationParts = explode(':', $data->duration);
        $hours = isset($durationParts[0]) ? intval($durationParts[0]) : 0;
        $minutes = isset($durationParts[1]) ? intval($durationParts[1]) : 0;
    @endphp

    @if($hours > 0 || $minutes > 0)
        <li class="d-inline-block fw-500 position-relative service-price">
            @if($hours > 0)
                ({{ $hours }} hrs @if($minutes > 0) {{ $minutes }} min @endif)
            @else
                ({{ $minutes }} min)
            @endif
        </li>
    @endif
@endif

   </ul>
   <div
      class="mt-3">
      <div class="d-flex align-items-center gap-2">
         <img src="{{ getSingleMedia($data->providers,'profile_image', null) }}" alt="service" class="img-fluid rounded-3 object-cover avatar-24">
         <a href="{{ route('provider.detail', ($data->providers)->id) }}">
            <span class="font-size-14 service-user-name">{{ ($data->providers)->display_name }}</span>
         </a>
      </div>
      @php
         $svcAvg = round((float) ($totalRating ?? 0), 1);
         $svcReviews = (int) ($totalReviews ?? 0);
      @endphp
      <div class="d-flex align-items-center gap-1 f-none mt-2">
         <span class="text-warning" aria-hidden="true">★</span>
         <h6 class="font-size-14 mb-0">{{ $svcReviews > 0 ? number_format($svcAvg, 1) : '0.0' }}
            @if($svcReviews > 1)
              <a href="{{ route('rating.all', ['service_id' => $data->id]) }}" class="text-body ms-1">({{ $svcReviews }} {{ __('messages.reviews') }})</a>
            @else
              <a href="{{ route('rating.all', ['service_id' => $data->id]) }}" class="text-body ms-1">({{ $svcReviews }} {{ __('messages.review') }})</a>
            @endif
         </h6>
      </div>
   </div>
</div>

<script src="{{ asset('js/sweetalert2.min.js') }}"></script>
<script>
   $(document).ready(function () {

    const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');

    $('.save_fav').off('click').on('click', function () {

       const form = $(this).closest('form');

       const serviceId = form.find('.service_id').data('service-id');
       const userId = $('#user_id').val();

       $.ajax({
            url: baseUrl + '/api/save-favourite',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                service_id: serviceId,
                user_id: userId,
            },
            success: function (response) {
               Swal.fire({
               title: 'Done',
               text: response.message,
               icon: 'success',
               iconColor: '#5F60B9'
               }).then((result) => {
                  if (result.isConfirmed) {
                     $('#datatable').DataTable().ajax.reload();
                  }
               })
            },
            error: function (error) {
                console.error('Error:', error);
            }
        });
    });

    $('.delete_fav').off('click').on('click', function () {
       const form = $(this).closest('form');

       const serviceId = form.find('.service_id').data('service-id');
       const userId = $('#user_id').val();

       $.ajax({
            url: baseUrl + '/api/delete-favourite',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                service_id: serviceId,
                user_id: userId,
            },
            success: function (response) {
               Swal.fire({
               title: 'Done',
               text: response.message,
               icon: 'success',
               iconColor: '#5F60B9'
               }).then((result) => {
                  if (result.isConfirmed) {
                     $('#datatable').DataTable().ajax.reload();
                  }
               })
            },
            error: function (error) {
                console.error('Error', error);
            }
        });
    });

    $('.service-heading, .service-img').on('click', function (e) {
    e.preventDefault();
    var serviceId = $(this).closest('.service-box-card').data('service-id');

    // Local Storage
    var storedServiceIds = JSON.parse(localStorage.getItem('recentlyViewed')) || [];
    if (!storedServiceIds.includes(serviceId)) {
        storedServiceIds.unshift(serviceId);
        storedServiceIds = storedServiceIds.slice(0, 10);
        localStorage.setItem('recentlyViewed', JSON.stringify(storedServiceIds));
    }

    // Laravel Session
    $.ajax({
        url: baseUrl + '/save-recently-viewed/' + serviceId,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
        },
        success: function (response) {
            return response;
        },
        error: function (error) {
            console.error('Error storing recently viewed service:', error);
        }
    });

    window.location.href = $(this).attr('href');
});
});
</script>
