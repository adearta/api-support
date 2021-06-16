<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class UserPersonal extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = "career_support_models_user";
    protected $fillable = [
        'password',
        'last_login',
        'is_superuser',
        'username',
        'first_name',
        'last_name',
        'email',
        'is_staff',
        'is_active',
        'date_joined',
        'is_candidate',
        'is_school',
        'is_employer',
        'is_admin',
        'is_english'
    ];
}
