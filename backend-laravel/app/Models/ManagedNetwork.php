<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManagedNetwork extends Model
{
    protected $fillable = [
        'name',
        'cidr',
        'gateway_ip',
        'interface_name',
        'status',
        'is_scannable',
        'is_monitored',
        'last_scanned_at',
        'retired_at',
        'retired_reason',
        'metadata',
    ];

    protected $casts = [
        'is_scannable' => 'boolean',
        'is_monitored' => 'boolean',
        'last_scanned_at' => 'datetime',
        'retired_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function discoveredHosts(): HasMany
    {
        return $this->hasMany(DiscoveredHost::class);
    }
}
