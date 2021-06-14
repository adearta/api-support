<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebinarAkbarModel extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_webinarakbar";

    protected $hidden = [
        "is_deleted",
        "created",
        "modified",
        "creator_id",
        "modifier_id",
    ];

    protected $fillable = [
        'zoom_link',
        'event_name',
        'event_date',
        'event_time',
        'event_picture',
    ];

    public function getImageAkbarAttributes()
    {
        return env("WEBINAR_URL") . $this->attributes['event_picture'];
    }
}
