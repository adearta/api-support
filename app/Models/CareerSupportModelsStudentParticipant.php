<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;


class CareerSupportModelsStudentParticipant extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_studentparticipants";

    protected $fillable = [
        'student_id',
        'webinar_id',
        'status',
    ];
    protected $hidden = [
        'creator_id',
        'modifier_id',
        'is_deleted',
        'created',
        'modified'
    ];
}
