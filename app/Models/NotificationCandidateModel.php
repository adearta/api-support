<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationCandidateModel extends Model
{
    use HasFactory;
    protected $table = "career_support_models_notification";
    protected $fillable = [
        'message_id',
        'message_en',
        'date',
        'time',
    ];
}
