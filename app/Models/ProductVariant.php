<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_attribute_option_id',
        'price',
        'stock',
        'max_purchase_qty',
        'status',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'product_attribute_option_id' => 'integer',
        'price' => 'double',
        'stock' => 'integer',
        'max_purchase_qty' => 'integer',
        'status' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeOption::class, 'product_attribute_option_id');
    }
}

