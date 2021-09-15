<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserWorkExperience extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_workexperience";
    protected $fillable = [
        'month_exp',
        'start_work',
        'end_work',
        'desc',
        'referral',
        'referral_position',
        'referral_contact',
        'last_salary',
        'company_id',
        'position_id',
        'user_id',
        'company_name',
        'job_name'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
