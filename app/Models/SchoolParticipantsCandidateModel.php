<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolParticipantsCandidateModel extends Model
{
    use HasFactory;
    protected $table = "career_support_models_school_participants";
    protected $fillable = [
        'status'
    ];
}
