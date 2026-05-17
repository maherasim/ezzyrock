<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderLiveLocation extends Model
{
    protected $fillable = [
        'product_order_id',
        'user_id',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'product_order_id' => 'integer',
        'user_id' => 'integer',
        'latitude' => 'double',
        'longitude' => 'double',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }
}
