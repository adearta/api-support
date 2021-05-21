<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class StudentCandidateModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_student";
    protected $fillable = [
        'school_id',
        'name',
        'nim',
        'class',
        'batch',
    ];
}
