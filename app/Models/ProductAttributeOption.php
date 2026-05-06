<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttributeOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_attribute_id',
        'value',
        'status',
    ];

    protected $casts = [
        'product_attribute_id' => 'integer',
        'status' => 'boolean',
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttribute::class, 'product_attribute_id');
    }
}

