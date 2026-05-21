<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtectionPolicy extends Model
{
    protected $fillable = [
        'code',
        'name',
        'scope',
        'risk_level',
        'action_type',
        'alert_only',
        'emergency_backup',
        'lock_safe_copy',
        'isolate_host',
        'kill_process',
        'restrict_path',
        'execution_mode',
        'is_enabled',
        'allow_admin_override',
        'description',
        'configuration',
        'metadata',
    ];

    protected $casts = [
        'alert_only'          => 'boolean',
        'emergency_backup'    => 'boolean',
        'lock_safe_copy'      => 'boolean',
        'isolate_host'        => 'boolean',
        'kill_process'        => 'boolean',
        'restrict_path'       => 'boolean',
        'is_enabled'          => 'boolean',
        'allow_admin_override' => 'boolean',
        'configuration'       => 'array',
        'metadata'            => 'array',
    ];

    public function protectionActions(): HasMany
    {
        return $this->hasMany(ProtectionAction::class);
    }
}
