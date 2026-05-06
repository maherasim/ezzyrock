<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostConversation extends Model
{
    use HasFactory;

    protected $table = 'post_conversations';

    protected $fillable = [
        'post_id',
        'seller_id',
        'buyer_id',
        'last_message_at',
    ];

    protected $casts = [
        'post_id' => 'integer',
        'seller_id' => 'integer',
        'buyer_id' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id', 'id')->withTrashed();
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id', 'id')->withTrashed();
    }

    public function messages()
    {
        return $this->hasMany(PostMessage::class, 'conversation_id', 'id');
    }
}
