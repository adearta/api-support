<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserLanguage extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_userlanguage";
    protected $fillable = [
        'written_expertise_percentage',
        'oral_expertise_percentage',
        'language_id',
        'user_id',
        'level_id'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
