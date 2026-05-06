<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderProductAddressMapping extends Model
{
    use HasFactory;

    protected $table = 'provider_product_address_mappings';

    protected $fillable = ['product_id', 'provider_address_id'];

    protected $casts = [
        'product_id'          => 'integer',
        'provider_address_id' => 'integer',
    ];

    public function providerAddressMapping()
    {
        return $this->belongsTo(ProviderAddressMapping::class, 'provider_address_id', 'id');
    }
}
