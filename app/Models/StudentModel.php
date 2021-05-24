<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentModel extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = "career_support_models_student";
    protected $fillable = [
        'nim',
        'name',
        'phone',
        'email',
        'schoolfile_id',
        'school_id',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id',
    ];
}
