<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastRoomModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_roombroadcast";
    protected $fillable = [
        'school_id',
        'broadcast_type',
        'year'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
