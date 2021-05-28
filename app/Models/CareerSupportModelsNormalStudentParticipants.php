<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CareerSupportModelsNormalStudentParticipants extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_normal_studentparticipants";
    protected $fillable = [
        'webinar_id',
        'student_id',
    ];
    protected $hidden = [
        'creator_id',
        'modifier_id',
        'is_deleted',
        'created',
        'modified'
    ];
}
