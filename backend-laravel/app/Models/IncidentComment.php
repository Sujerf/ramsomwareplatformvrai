<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentComment extends Model
{
    protected $fillable = ['incident_id', 'user_id', 'user_name', 'body', 'is_system'];

    protected $casts = ['is_system' => 'boolean'];

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
