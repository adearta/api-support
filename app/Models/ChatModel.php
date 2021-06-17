<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class ChatModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_chat";
    protected $fillable = [
        'room_chat_id',
        'chat',
        'image',
        'send_time',
        'sender',
        'is_readed',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
