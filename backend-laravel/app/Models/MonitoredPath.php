<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredPath extends Model
{
    protected $fillable = [
        'agent_id',
        'path',
        'is_enabled',
        'is_recursive',
        'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_recursive' => 'boolean',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
