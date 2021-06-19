<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationModel extends Model
{
    use ModelHelper, HasFactory;
    protected $connection = 'pgsql2';
    protected $table = "career_support_models_notification";
    protected $fillable = [
        'title',
        'description',
        'datetime',
        'is_seen',
        'type',
        'type_profile',
        'type_education',
        'user_id',
        'company_id',
        'title_en',
        'title_ind',
        'description_en',
        'description_ind',
        'user_education_id',
        'invitation_id',
        'job_fair_id',
        'broadcast_id'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id',
    ];
}
