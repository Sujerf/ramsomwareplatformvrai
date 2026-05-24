<?php

namespace App\Models;

use App\Services\MacVendorService;
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
        'device_vendor',
        'device_category',
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
        'is_monitored'      => 'boolean',
        'open_ports'        => 'array',
        'detected_services' => 'array',
        'metadata'          => 'array',
        'last_seen_at'      => 'datetime',
        'retired_at'        => 'datetime',
        'enrolled_at'       => 'datetime',
    ];

    // ── Accesseurs sur le fabricant ────────────────────────────────────────────

    /** Retourne l'icône emoji de la catégorie (📱 💻 📡 etc.) */
    public function getDeviceIconAttribute(): string
    {
        return MacVendorService::CATEGORY_ICON[$this->device_category ?? 'unknown'] ?? '❓';
    }

    /** Retourne le libellé humain de la catégorie */
    public function getDeviceLabelAttribute(): string
    {
        return MacVendorService::CATEGORY_LABEL[$this->device_category ?? 'unknown'] ?? 'Inconnu';
    }

    /** Vrai si c'est un appareil mobile (smartphone / tablette) */
    public function getIsMobileAttribute(): bool
    {
        return in_array($this->device_category, ['mobile', 'apple_device'], true);
    }

    // ── Relations ──────────────────────────────────────────────────────────────

    public function managedNetwork(): BelongsTo
    {
        return $this->belongsTo(ManagedNetwork::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
}
