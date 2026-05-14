<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreePostSetting extends Model
{
    use HasFactory;

    protected $table = 'free_post_settings';

    protected $fillable = [
        'title',
        'free_posts',
        'status',
        'description',
    ];

    protected $casts = [
        'free_posts' => 'integer',
        'status' => 'integer',
    ];

    public function scopeList($query)
    {
        return $query->orderBy('updated_at', 'desc');
    }
}
