<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOrder extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'subtotal',
        'tax_total',
        'total',
        'payment_type',
        'payment_status',
        'txn_id',
        'other_transaction_detail',
        'tax_detail',
        'notes',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_detail' => 'array',
        'txn_id' => 'string',
        'other_transaction_detail' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductOrderItem::class, 'product_order_id');
    }
}
