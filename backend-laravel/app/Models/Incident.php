<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    protected $fillable = [
        'incident_uuid',
        'agent_id',
        'attack_profile_id',
        'title',
        'description',
        'status',
        'risk_level',
        'risk_score',
        'detected_at',
        'contained_at',
        'resolved_at',
        'reopened_at',
        'resolved_by',
        'archived_at',
        'metadata',
    ];

    protected $casts = [
        'risk_score'  => 'integer',
        'detected_at' => 'datetime',
        'contained_at'=> 'datetime',
        'resolved_at' => 'datetime',
        'reopened_at' => 'datetime',
        'archived_at' => 'datetime',
        'metadata'    => 'array',
    ];

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function attackProfile(): BelongsTo
    {
        return $this->belongsTo(AttackProfile::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function riskSnapshots(): HasMany
    {
        return $this->hasMany(RiskSnapshot::class);
    }

    public function protectionActions(): HasMany
    {
        return $this->hasMany(ProtectionAction::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(IncidentComment::class)->oldest();
    }
}
