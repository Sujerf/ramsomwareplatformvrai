<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimulationRun extends Model
{
    protected $fillable = [
        'run_uuid',
        'agent_id',
        'attack_profile_id',
        'name',
        'description',
        'status',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function attackProfile(): BelongsTo
    {
        return $this->belongsTo(AttackProfile::class);
    }
}
