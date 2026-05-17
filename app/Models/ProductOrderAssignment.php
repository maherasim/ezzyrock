<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductOrderAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_order_id',
        'handyman_id',
    ];

    protected $casts = [
        'product_order_id' => 'integer',
        'handyman_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductOrder::class, 'product_order_id');
    }

    public function handyman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handyman_id');
    }
}
