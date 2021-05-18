<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebinarCandidateModel extends Model
{
    use HasFactory;
    protected $table = "career_support_models_webinar";
    protected $fillable = [
        'zoom_link',
        'event_name',
        'event_date',
        'event_time',
        'event_picture',
        'school_name',
    ];
}
