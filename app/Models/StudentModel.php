<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentModel extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = "career_support_models_personalinfo";
    protected $fillable = [
        'phone',
        'nim',
        'address',
        'zip_code',
        'date_of_birth',
        'gender',
        'marital_status',
        'religion',
        'employment_status',
        'description',
        'avatar',
        'domicile_id',
        'user_id',
        'school_id',
        'template',
        'portofolio',
        'verification_status',
        'school_name',
        'is_active',
        'is_selected',
        'facebookURL',
        'googleURL',
        'linkedinURL',
        'pinterestURL',
        'twitterURL',
        'reason_inactive',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id',
    ];
}
