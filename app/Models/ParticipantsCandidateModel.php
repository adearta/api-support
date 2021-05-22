<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantsCandidateModel extends Model
{
    use HasFactory;
    protected $table = "career_support_models_participants";
    protected $fillable = [
        'webinar_id',
        'school_id',
        'name',
        'date',
        'time',
        'picture',
        'link',
    ];
}
