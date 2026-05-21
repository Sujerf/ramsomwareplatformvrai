<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensitiveExtension extends Model
{
    protected $fillable = [
        'extension',
        'risk_level',
        'score_weight',
        'description',
        'metadata',
        'category',
        'label',
        'is_enabled',
    ];

    protected $casts = [
        'score_weight' => 'integer',
        'is_enabled'   => 'boolean',
        'metadata'     => 'array',
    ];
}
