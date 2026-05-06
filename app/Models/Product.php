<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TranslationTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory, SoftDeletes;
    use TranslationTrait;

    protected $table = 'products';

    protected $fillable = [
        'name', 'category_id', 'provider_id', 'type', 'is_slot', 'discount', 'duration', 'description',
        'is_featured', 'status', 'price', 'added_by', 'service_request_status', 'is_service_request', 'subcategory_id', 'service_type', 'visit_type',
        'is_enable_advance_payment', 'advance_payment_amount', 'total_stock', 'max_purchase_qty', 'product_unit_id',
        'meta_title', 'meta_description', 'meta_keywords', 'slug', 'seo_enabled'
    ];

    protected $casts = [
        'category_id'               => 'integer',
        'subcategory_id'            => 'integer',
        'provider_id'               => 'integer',
        'price'                     => 'double',
        'total_stock'               => 'integer',
        'max_purchase_qty'          => 'integer',
        'product_unit_id'           => 'integer',
        'discount'                  => 'double',
        'status'                    => 'integer',
        'is_featured'               => 'integer',
        'added_by'                  => 'integer',
        'is_slot'                   => 'integer',
        'is_enable_advance_payment' => 'integer',
        'advance_payment_amount'    => 'double',
        'meta_keywords'             => 'array',
        'seo_enabled'               => 'boolean',
    ];

    public function translations()
    {
        return $this->morphMany(Translations::class, 'translatable');
    }

    public function translate($attribute, $locale = null)
    {
        $locale = $locale ?? app()->getLocale() ?? 'en';
        if ($locale !== 'en') {
            $translation = $this->translations()
                ->where('attribute', $attribute)
                ->where('locale', $locale)
                ->value('value');
            return $translation !== null ? $translation : '';
        }
        return $this->$attribute;
    }

    public function providers()
    {
        return $this->belongsTo(User::class, 'provider_id', 'id')->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id')->withTrashed();
    }

    public function subcategory()
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id', 'id')->withTrashed();
    }

    public function providerProductAddress()
    {
        return $this->hasMany(ProviderProductAddressMapping::class, 'product_id', 'id')->with('providerAddressMapping');
    }

    public function productZoneMapping()
    {
        return $this->hasMany(ProductZoneMapping::class, 'product_id');
    }

    public function zones()
    {
        return $this->belongsToMany(ServiceZone::class, 'product_zone_mappings', 'product_id', 'zone_id');
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_product_mappings', 'product_id', 'shop_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id', 'id');
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductAttributeOption::class,
            'product_attribute_option_product',
            'product_id',
            'product_attribute_option_id'
        )->withPivot('product_attribute_id')->withTimestamps();
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }

    public function scopeMyProduct($query)
    {
        if (auth()->user()->hasRole('admin')) {
            return $query->where('service_type', 'ecommerce')->withTrashed();
        }
        if (auth()->user()->hasRole('provider')) {
            return $query->where('products.provider_id', \Auth::id());
        }
        return $query;
    }

    public function scopeList($query)
    {
        return $query->orderByRaw('deleted_at IS NULL DESC, deleted_at DESC')->orderBy('created_at', 'desc');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_attachment');
        $this->addMediaCollection('seo_image')->singleFile();
    }
}
