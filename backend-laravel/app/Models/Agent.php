<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    protected $fillable = [
        'discovered_host_id',
        'agent_uuid',
        'enrollment_token',
        'agent_name',
        'hostname',
        'ip_address',
        'mac_address',
        'host_role',
        'status',
        'enrollment_status',
        'risk_level',
        'risk_score',
        'is_isolated',
        'last_seen_at',
        'enrolled_at',
        'metadata',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'is_isolated' => 'boolean',
        'last_seen_at' => 'datetime',
        'enrolled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function monitoredPaths(): HasMany
    {
        return $this->hasMany(MonitoredPath::class);
    }

    public function networkShares(): HasMany
    {
        return $this->hasMany(NetworkShare::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function riskSnapshots(): HasMany
    {
        return $this->hasMany(RiskSnapshot::class);
    }

    public function protectionActions(): HasMany
    {
        return $this->hasMany(ProtectionAction::class);
    }

    public function simulationRuns(): HasMany
    {
        return $this->hasMany(SimulationRun::class);
    }
    public function discoveredHost()
    {
        return $this->belongsTo(DiscoveredHost::class, 'discovered_host_id');
    }

}

