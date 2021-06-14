<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CareerSupportModelsWebinarBiasa extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_webinarnormal";
    protected $fillable = [
        'event_name',
        'event_date',
        'event_picture',
        'event_link',
        'start_time',
        'end_time',
        'price',
    ];
    protected $hidden = [
        'creator_id',
        'modifier_id',
        'is_deleted',
        'created',
        'modified'
    ];
    public function getImageAttribute($value)
    {

        return env("WEBINAR_URL") . $this->attributes['event_picture'];
    }
}
