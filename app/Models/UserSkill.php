<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserSkill extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_userskill";
    protected $fillable = [
        'expertise_percentage',
        'skill_id',
        'user_id',
        'level_id',

    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
