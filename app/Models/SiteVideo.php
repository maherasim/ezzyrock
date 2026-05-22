<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SiteVideo extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('site_video')->singleFile();
    }
}
