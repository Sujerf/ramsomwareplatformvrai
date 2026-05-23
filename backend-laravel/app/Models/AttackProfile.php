<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttackProfile extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_simulation',
        'is_enabled',
        'indicators',
    ];

    protected $casts = [
        'is_simulation' => 'boolean',
        'is_enabled' => 'boolean',
        'indicators' => 'array',
    ];

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }


}
