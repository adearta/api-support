<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CareerSupportModelsCertificate extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_certificates";
    protected $fillable = [
        'certificate',
        'webinar_id',
        'participant_id',
        'file_name'
    ];
    protected $hidden = [
        'creator_id',
        'modifier_id',
        'is_deleted',
        'created',
        'modified'
    ];
}
