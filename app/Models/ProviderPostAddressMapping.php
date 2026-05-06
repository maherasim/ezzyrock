<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPostAddressMapping extends Model
{
    use HasFactory;

    protected $table = 'provider_post_address_mappings';

    protected $fillable = ['post_id', 'provider_address_id'];

    protected $casts = [
        'post_id'              => 'integer',
        'provider_address_id'   => 'integer',
    ];

    public function providerAddressMapping()
    {
        return $this->belongsTo(ProviderAddressMapping::class, 'provider_address_id', 'id');
    }
}
