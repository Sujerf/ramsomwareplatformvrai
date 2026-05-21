<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetectionRule extends Model
{
    protected $fillable = [
        'code',
        'name',
        'event_type',
        'risk_level',
        'score_weight',
        'is_enabled',
        'description',
        'conditions',
    ];

    protected $casts = [
        'score_weight' => 'integer',
        'is_enabled' => 'boolean',
        'conditions' => 'array',
    ];
}
