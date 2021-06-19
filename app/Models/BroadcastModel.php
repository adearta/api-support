<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastModel extends Model
{
    use ModelHelper, HasFactory;
    protected $table = "career_support_models_broadcast";
    protected $fillable = [
        'school_id',
        'chat',
        'link',
        'image',
        'type',
        'year',
        'send_time'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
