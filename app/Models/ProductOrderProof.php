<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProductOrderProof extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'product_order_id',
        'user_id',
        'description',
    ];

    protected $casts = [
        'product_order_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof_attachment');
    }
}
