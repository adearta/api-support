<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class ChatRoomModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_roomchat";
    protected $fillable = [
        'school_id',
        'student_id',
        'broadcast_reply'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
