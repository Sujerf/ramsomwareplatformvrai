<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ransomshield:reset-defaults', function () {
    $defaults = app(\App\Services\RansomShieldDefaultConfigurationService::class);

    $results = $defaults->resetAll();

    foreach ($results as $key => $count) {
        $this->info($key.' : '.$count.' élément(s) synchronisé(s).');
    }

    $this->info('Configuration RansomShield réinitialisée avec succès.');

    return 0;
})->purpose('Réinitialise les configurations RansomShield vers les valeurs par défaut.');


Artisan::command('ransomshield:reactivate-infrastructure', function () {
    $networks = \App\Models\ManagedNetwork::query()
        ->where('status', 'retired')
        ->orWhere('is_monitored', false)
        ->get();

    foreach ($networks as $network) {
        \Illuminate\Support\Facades\DB::table('managed_networks')
            ->where('id', $network->id)
            ->update([
                'status' => 'detected',
                'is_monitored' => true,
                'is_scannable' => true,
                'retired_at' => null,
                'retired_reason' => null,
                'updated_at' => now(),
            ]);
    }

    $hosts = \App\Models\DiscoveredHost::query()
        ->where('discovery_status', 'retired')
        ->orWhere('is_monitored', false)
        ->get();

    foreach ($hosts as $host) {
        \Illuminate\Support\Facades\DB::table('discovered_hosts')
            ->where('id', $host->id)
            ->update([
                'discovery_status' => 'detected',
                'is_monitored' => true,
                'retired_at' => null,
                'retired_reason' => null,
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);
    }

    $this->info($networks->count().' réseau(x) réactivé(s).');
    $this->info($hosts->count().' hôte(s) réactivé(s).');

    return 0;
})->purpose('Réactive les réseaux et hôtes retirés lorsqu’ils doivent être remis sous surveillance.');

