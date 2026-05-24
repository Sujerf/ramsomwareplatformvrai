<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    protected $fillable = [
        'alert_uuid',      // Bug L fix — colonne ajoutée par migration 110001
        'agent_id',
        'incident_id',
        'event_id',
        'title',
        'message',
        'status',
        'risk_level',
        'score',
        'detected_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'resolved_by',
        'metadata',
    ];

    protected $casts = [
        'score' => 'integer',
        'detected_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AlertNotification::class);
    }
}
