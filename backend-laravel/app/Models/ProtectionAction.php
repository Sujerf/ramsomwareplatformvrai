<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtectionAction extends Model
{
    // execution_status : waiting_approval | pending | executing | executed | failed | rolled_back
    // approval_status  : pending | approved | rejected | cancelled

    protected $fillable = [
        'agent_id',
        'incident_id',
        'protection_policy_id',
        'action_uuid',
        'action_type',
        'decision_mode',
        'execution_status',
        'approval_status',
        'is_reversible',
        'rollback_available',
        'description',
        'payload',
        'result',
        'proposed_at',
        'executed_at',
        'rolled_back_at',
        'executed_by',
    ];

    protected $casts = [
        'is_reversible' => 'boolean',
        'rollback_available' => 'boolean',
        'payload' => 'array',
        'result' => 'array',
        'proposed_at' => 'datetime',
        'executed_at' => 'datetime',
        'rolled_back_at' => 'datetime',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function protectionPolicy(): BelongsTo
    {
        return $this->belongsTo(ProtectionPolicy::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(ProtectionActionDecision::class);
    }
}
