<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ProductCartItem extends Model
{
    protected $fillable = ['user_id', 'product_id', 'product_variant_id', 'quantity'];

    protected $casts = [
        'user_id' => 'integer',
        'product_id' => 'integer',
        'product_variant_id' => 'integer',
        'quantity' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Sum of line quantities for approved ecommerce products (matches header badge). */
    public static function totalEcommerceQuantityForUser(int $userId): int
    {
        if (! Schema::hasTable('product_cart_items')) {
            return 0;
        }

        return (int) static::query()
            ->where('user_id', $userId)
            ->whereHas('product', function ($q) {
                $q->where('service_type', 'ecommerce')
                    ->where('status', 1)
                    ->where('service_request_status', 'approve');
            })
            ->count();
    }
}
