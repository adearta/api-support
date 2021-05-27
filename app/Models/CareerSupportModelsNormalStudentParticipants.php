<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerSupportModelsNormalStudentParticipants extends Model
{
    use HasFactory;
    protected $table = "career_support_models_normal_student_participants";
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
