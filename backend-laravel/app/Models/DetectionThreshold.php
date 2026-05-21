<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetectionThreshold extends Model
{
    protected $fillable = [
        'code',
        'name',
        'risk_level',
        'min_score',
        'max_score',
        'key',
        'label',
        'value',
        'unit',
        'description',
        'metadata',
        'is_enabled',
    ];

    protected $casts = [
        'value'     => 'integer',
        'min_score' => 'integer',
        'max_score' => 'integer',
        'is_enabled' => 'boolean',
        'metadata'  => 'array',
    ];
}
