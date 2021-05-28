<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelHelper;

class CareerSupportModelsPercentage extends Model
{
    use HasFactory, ModelHelper;
    protected $connection = 'pgsql2';
    protected $table = 'career_support_models_profilepercentagehistory';
    protected $fillable = [
        'percent',
        'updated',
        'user_id'
    ];
}
