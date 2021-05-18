<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCandidateModel extends Model
{
    use HasFactory;
    protected $table = "career_support_models_students";
    protected $fillable = [
        'participants_id',
        'name',
        'date',
        'time',
        'picture',
        'link',
    ];
}
