<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_school";
    protected $fillable = [
        'school_name',
        'school_email'
    ];
}
