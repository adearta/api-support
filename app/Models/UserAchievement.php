<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserAchievement extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_achievement";
    protected $fillable = [
        'achievement',
        'link',
        'user_id',
        'year',
        'associate',
        'role_description',
        'received'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
