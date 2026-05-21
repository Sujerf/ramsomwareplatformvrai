<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'agent_id',
        'incident_id',
        'event_uuid',
        'event_type',
        'path',
        'old_path',
        'file_extension',
        'file_size',
        'file_hash',
        'score',
        'risk_level',
        'is_simulation',
        'simulation_run_uuid',
        'raw_payload',
        'metadata',
        'observed_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'score' => 'integer',
        'is_simulation' => 'boolean',
        'raw_payload' => 'array',
        'metadata' => 'array',
        'observed_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }
    public function alert()
    {
        return $this->hasOne(Alert::class);
    }

}

