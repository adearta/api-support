<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolParticipantAkbarModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_schoolparticipantakbar";

    protected $hidden = [
        "is_deleted",
        "created",
        "modified",
        "creator_id",
        "modifier_id",
    ];

    protected $fillable = [
        'webinar_id',
        'school_id',
        'status'
    ];
}
