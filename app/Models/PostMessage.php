<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostMessage extends Model
{
    use HasFactory;

    protected $table = 'post_messages';

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'sender_id' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(PostConversation::class, 'conversation_id', 'id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id', 'id')->withTrashed();
    }
}
