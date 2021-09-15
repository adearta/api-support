<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class Association extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_association";
    protected $fillable = [
        'role',
        'description',
        'since',
        'until',
        'organization',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
