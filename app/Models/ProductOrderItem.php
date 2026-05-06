<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOrderItem extends Model
{
    protected $fillable = [
        'product_order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_label',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'product_order_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
