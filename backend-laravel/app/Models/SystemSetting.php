<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'value_type',
        'group',
        'label',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    //  Cache — charge TOUS les settings en une requête, TTL 60 s
    // ──────────────────────────────────────────────────────────────────────────

    private const CACHE_KEY = 'system_settings_all';
    private const CACHE_TTL = 60; // secondes

    /**
     * Retourne la valeur d'un setting depuis le cache (pluck en batch).
     * 1 requête SQL par fenêtre de 60 s, partagée entre tous les services.
     */
    public static function getCached(string $key, mixed $default = null): mixed
    {
        $all = static::allCached();

        return $all[$key] ?? $default;
    }

    /**
     * Retourne tous les settings sous forme ['key' => 'value'].
     * Mise en cache 60 s — invalidée par clearCache() à chaque écriture.
     */
    public static function allCached(): array
    {
        return Cache::remember(static::CACHE_KEY, static::CACHE_TTL, function () {
            return static::pluck('value', 'key')->all();
        });
    }

    /**
     * Invalide le cache — à appeler après tout UPDATE / INSERT / DELETE.
     */
    public static function clearCache(): void
    {
        Cache::forget(static::CACHE_KEY);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Observers — invalide automatiquement le cache sur toute écriture Eloquent
    // ──────────────────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        $clear = fn () => static::clearCache();

        static::saved($clear);
        static::deleted($clear);
    }
}
