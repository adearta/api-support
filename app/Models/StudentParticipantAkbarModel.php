<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentParticipantAkbarModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_studentparticipantakbar";

    protected $hidden = [
        "is_deleted",
        "created",
        "modified",
        "creator_id",
        "modifier_id",
    ];

    protected $fillable = [
        'school_id',
        'webinar_id',
        'student_id'
    ];
}
