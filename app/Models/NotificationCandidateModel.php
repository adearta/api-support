<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCandidateModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_notification";
    protected $fillable = [
        'school_id',
        'student_id',
        'message_id',
        'message_en',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'modifier_id',
        'creator_id',
    ];
}
