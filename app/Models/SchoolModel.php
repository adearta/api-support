<?php

namespace App\Models;

use App\Traits\ModelHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolModel extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = "career_support_models_school";
    protected $fillable = [
        'school_type_id',
        'name',
        'phone',
        'email',
        'fax',
        'address',
        'website',
        'logo',
        'is_registered',
        'secret',
        'city_id',
        'branch',
        'subdomain',
        'is_active',
        'is_selected',
        'is_recipient',
        'postal_code',
        'about',
        'mission',
        'vision',
        'facebookURL',
        'googleURL',
        'linkedinURL',
        'pinterestURL',
        'twitterURL',
        'verification_status',
        'registration_step',
        'subpath',
        'banner',
        'reason_inactive',
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
