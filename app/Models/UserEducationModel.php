<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserEducationModel extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = 'career_support_models_usereducation';

    protected $fillable = [
        'start_month',
        'start_year',
        'end_month',
        'end_year',
        'gpa',
        'degree_id',
        'school_id',
        'user_id',
        'major_id',
        'end_date',
        'start_date',
        'nim',
        'verified',
        'school_name',
        'is_active',

    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
