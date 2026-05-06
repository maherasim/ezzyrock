<div class="service-box-card bg-light rounded-3 mb-5" data-product-id="{{ $data->id }}">
   <div class="iq-image position-relative">
      @if(!empty($data->is_featured) && (int) $data->is_featured === 1)
      <span class="badge bg-warning text-dark position-absolute" style="top:10px; right:10px; z-index:2;">Featured</span>
      @endif
      @if(isset($data->visit_type) && $data->visit_type == 'ONLINE')
         <span class="online-service"></span>
      @endif
      <a href="{{ route('product.detail', $data->id) }}" class="service-img">
         <img src="{{ getSingleMedia($data, 'product_attachment', null) }}" alt="product"
         class="service-img w-100 object-cover img-fluid rounded-3">
      </a>
      @if(isset($data->visit_type) && $data->visit_type == 'on_shop')
        <div class="position-absolute d-flex justify-content-center align-items-center rounded-circle bg-primary"
            style="width: 25px;height: 25px;top: 13px;left: 1rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" width="15" height="15" fill="white">
                <path d="M53.5 245.1L110.3 131.4C121.2 109.7 143.3 96 167.6 96L472.5 96C496.7 96 518.9 109.7 529.7 131.4L586.5 245.1C590.1 252.3 592 260.2 592 268.3C592 295.6 570.8 318 544 319.9L544 512C544 529.7 529.7 544 512 544C494.3 544 480 529.7 480 512L480 320L384 320L384 496C384 522.5 362.5 544 336 544L144 544C117.5 544 96 522.5 96 496L96 319.9C69.2 318 48 295.6 48 268.3C48 260.3 49.9 252.3 53.5 245.1zM160 320L160 432C160 440.8 167.2 448 176 448L304 448C312.8 448 320 440.8 320 432L320 320L160 320z"/>
            </svg>
        </div>
      @endif
   </div>
   <a href="{{ route('product.detail', $data->id) }}" class="service-heading mt-4 d-block p-0">
      <h5 class="service-heading service-title font-size-18 line-count-2">{{ $data->name ?? '-' }}</h5>
   </a>
   @php
      $avgRating = round((float) ($data->reviews_avg_rating ?? 0), 1);
      $reviewCount = (int) ($data->reviews_count ?? 0);
      $cartRow = $cartRow ?? null;
   @endphp
   <div class="d-flex align-items-center gap-1 mt-1 mb-1 small text-muted">
      <span class="text-warning">★</span>
      <span>{{ $reviewCount > 0 ? number_format($avgRating, 1) : '0.0' }}</span>
      <span>({{ $reviewCount }} {{ __('messages.reviews') }})</span>
   </div>
   <ul class="list-inline p-0 mt-0 mb-0 price-content">
      @if($data->price == 0)
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
   @if($data->providers)
   <div class="mt-3 pb-1">
      <div class="d-flex align-items-center gap-2">
         <img src="{{ getSingleMedia($data->providers, 'profile_image', null) }}" alt="provider" class="img-fluid rounded-3 object-cover avatar-24">
         <a href="{{ route('provider.detail', $data->providers->id) }}">
            <span class="font-size-14 service-user-name">{{ $data->providers->display_name }}</span>
         </a>
      </div>
   </div>
   @endif
   <div class="pb-3 pt-2 product-card-cart" data-product-id="{{ $data->id }}">
      @include('product.partials.card-cart-inner', ['data' => $data, 'cartRow' => $cartRow ?? null])
   </div>
</div>
