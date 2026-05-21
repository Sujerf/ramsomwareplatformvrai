<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtectionActionDecision extends Model
{
    protected $fillable = [
        'protection_action_id',
        'user_id',
        'decision',
        'comment',
        'metadata',
        'decided_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'decided_at' => 'datetime',
    ];

    public function protectionAction(): BelongsTo
    {
        return $this->belongsTo(ProtectionAction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
