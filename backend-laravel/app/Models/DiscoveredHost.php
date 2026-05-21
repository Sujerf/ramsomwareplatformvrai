<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveredHost extends Model
{
    protected $fillable = [
        'managed_network_id',
        'agent_id',
        'ip_address',
        'mac_address',
        'hostname',
        'host_role',
        'discovery_status',
        'enrollment_status',
        'is_monitored',
        'open_ports',
        'detected_services',
        'metadata',
        'last_seen_at',
        'retired_at',
        'retired_reason',
        'enrolled_at',
    ];

    protected $casts = [
        'is_monitored' => 'boolean',
        'open_ports' => 'array',
        'detected_services' => 'array',
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
        'retired_at' => 'datetime',
        'enrolled_at' => 'datetime',
    ];

    public function managedNetwork(): BelongsTo
    {
        return $this->belongsTo(ManagedNetwork::class);
    }
    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}

