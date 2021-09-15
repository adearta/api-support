<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserPersonality extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_userpersonality";
    protected $fillable = [
        'expertise_percentage',
        'personality_id',
        'user_id',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
