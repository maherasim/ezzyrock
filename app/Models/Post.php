<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TranslationTrait;

class Post extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory, SoftDeletes;
    use TranslationTrait;

    protected $table = 'posts';

    protected $fillable = [
        'name', 'category_id', 'provider_id', 'type', 'is_slot', 'discount', 'duration', 'description',
        'is_featured', 'status', 'price', 'added_by', 'service_request_status', 'is_service_request', 'subcategory_id', 'service_type', 'visit_type',
        'is_enable_advance_payment', 'advance_payment_amount',
        'meta_title', 'meta_description', 'meta_keywords', 'slug', 'seo_enabled'
    ];

    protected $casts = [
        'category_id'               => 'integer',
        'subcategory_id'            => 'integer',
        'provider_id'               => 'integer',
        'price'                     => 'double',
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

    public function providerPostAddress()
    {
        return $this->hasMany(ProviderPostAddressMapping::class, 'post_id', 'id')->with('providerAddressMapping');
    }

    public function postZoneMapping()
    {
        return $this->hasMany(PostZoneMapping::class, 'post_id');
    }

    public function zones()
    {
        return $this->belongsToMany(ServiceZone::class, 'post_zone_mappings', 'post_id', 'zone_id');
    }

    public function shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_post_mappings', 'post_id', 'shop_id');
    }

    public function scopeMyPost($query)
    {
        if (auth()->user()->hasRole('admin')) {
            return $query->where('service_type', 'classified')->withTrashed();
        }
        if (auth()->user()->hasRole('provider')) {
            return $query->where('posts.provider_id', \Auth::id());
        }
        return $query;
    }

    public function scopeList($query)
    {
        return $query->orderByRaw('deleted_at IS NULL DESC, deleted_at DESC')->orderBy('created_at', 'desc');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('post_attachment');
        $this->addMediaCollection('seo_image')->singleFile();
    }
}
