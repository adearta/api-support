<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CareerSupportModelsNotificationWebinarnormalModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_notificationwebinar";
    protected $fillable = [
        'student_id',
        'message_id',
        'message_en',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'modifier_id',
        'creator_id',
    ];
}
