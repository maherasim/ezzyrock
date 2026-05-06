<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductZoneMapping extends Model
{
    protected $table = 'product_zone_mappings';

    protected $fillable = [
        'product_id',
        'zone_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function zone()
    {
        return $this->belongsTo(ServiceZone::class, 'zone_id');
    }
}
