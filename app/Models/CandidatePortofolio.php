<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CandidatePortofolio extends Model
{
    use HasFactory, ModelHelper;
    protected $table = "career_support_models_candidateportfolio";
    protected $fillable = [
        'portofolio_name',
        'portofolio_url',
        'portofolio_description',
        'portofolio_date',
        'candidate_id'
    ];
    protected $hidden = [
        'is_deleted',
        'created',
        'modified',
        'creator_id',
        'modifier_id'
    ];
}
