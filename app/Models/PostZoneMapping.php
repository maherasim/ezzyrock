<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostZoneMapping extends Model
{
    protected $table = 'post_zone_mappings';

    protected $fillable = [
        'post_id',
        'zone_id'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function zone()
    {
        return $this->belongsTo(ServiceZone::class, 'zone_id');
    }
}
