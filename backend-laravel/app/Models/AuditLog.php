<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'user_email',
        'user_name',
        'action',
        'channel',
        'context',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function write(string $action, string $channel, array $context = []): self
    {
        return static::create([
            'user_id'    => auth()->id(),
            'user_email' => auth()->user()?->email,
            'user_name'  => auth()->user()?->name,
            'action'     => $action,
            'channel'    => $channel,
            'context'    => $context ?: null,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
