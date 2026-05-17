<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderActivity extends Model
{
    protected $fillable = [
        'product_order_id',
        'activity_type',
        'activity_message',
        'activity_data',
        'created_by',
        'datetime',
    ];

    protected $casts = [
        'product_order_id' => 'integer',
        'created_by' => 'integer',
        'datetime' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }
}
