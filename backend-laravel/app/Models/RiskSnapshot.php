<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskSnapshot extends Model
{
    protected $fillable = [
        'agent_id',
        'incident_id',
        'score',
        'risk_level',
        'signals',
        'calculated_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'signals' => 'array',
        'calculated_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
}
