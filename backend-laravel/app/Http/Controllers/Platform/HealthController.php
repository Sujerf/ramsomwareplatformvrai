<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\AlertNotification;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('platform.health.index', [
            'checks' => $this->runChecks(),
        ]);
    }

    private function runChecks(): array
    {
        return [
            'database'  => $this->checkDatabase(),
            'cache'     => $this->checkCache(),
            'queue'     => $this->checkQueue(),
            'scheduler' => $this->checkScheduler(),
            'storage'   => $this->checkStorage(),
            'mail'      => $this->checkMail(),
            'webhook'   => $this->checkWebhook(),
            'agents'    => $this->checkAgents(),
            'incidents' => $this->checkIncidents(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $ms = round((microtime(true) - $start) * 1000, 1);

            return [
                'status' => 'ok',
                'label'  => 'Base de données',
                'value'  => $ms.' ms',
                'detail' => config('database.connections.mysql.host').':'.config('database.connections.mysql.port')
                    .' / '.config('database.connections.mysql.database'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'Base de données', 'value' => 'Hors-ligne', 'detail' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            $key = '_soc_health_'.uniqid();
            Cache::put($key, 'ok', 5);
            $ok = Cache::get($key) === 'ok';
            Cache::forget($key);

            return [
                'status' => $ok ? 'ok' : 'error',
                'label'  => 'Cache',
                'value'  => $ok ? ucfirst(config('cache.default')) : 'Défaillant',
                'detail' => 'Driver : '.config('cache.default'),
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'Cache', 'value' => 'Erreur', 'detail' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        try {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            $driver  = config('queue.default');

            return [
                'status' => $failed > 0 ? 'warn' : 'ok',
                'label'  => 'File de jobs',
                'value'  => $pending.' en attente'.($failed > 0 ? ' · '.$failed.' échoués' : ''),
                'detail' => 'Driver : '.$driver,
                'failed' => $failed,
            ];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'label' => 'File de jobs', 'value' => 'Erreur', 'detail' => $e->getMessage()];
        }
    }

    private function checkScheduler(): array
    {
        $logFile = storage_path('logs/scheduler.log');
        $mtime   = file_exists($logFile) ? filemtime($logFile) : null;
        $age     = $mtime ? max(0, (int) now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($mtime), true)) : null;

        $status = match (true) {
            $mtime === null => 'warn',
            $age > 15       => 'warn',
            default         => 'ok',
        };

        $ageLabel = match (true) {
            $age === null  => 'Aucune trace',
            $age === 0     => 'À l\'instant',
            $age < 2       => 'Il y a moins de 2 min',
            default        => 'Il y a '.$age.' min',
        };

        return [
            'status' => $status,
            'label'  => 'Scheduler (cron)',
            'value'  => $ageLabel,
            'detail' => $mtime
                ? 'Dernière activité : '.date('d/m/Y H:i:s', $mtime)
                : 'Vérifiez que le cron "php artisan schedule:run" est actif.',
        ];
    }

    private function checkStorage(): array
    {
        $path  = storage_path();
        $free  = disk_free_space($path);
        $total = disk_total_space($path);
        $pct   = $total > 0 ? round((1 - $free / $total) * 100) : 0;

        $status = match (true) {
            $pct >= 90 => 'error',
            $pct >= 75 => 'warn',
            default    => 'ok',
        };

        $reports = count(glob(storage_path('app/reports/*.pdf')) ?: []);

        return [
            'status' => $status,
            'label'  => 'Stockage',
            'value'  => round($free / 1024 / 1024 / 1024, 1).' Go libres ('.$pct.'% utilisé)',
            'detail' => round($total / 1024 / 1024 / 1024, 1).' Go total · '.$reports.' rapport(s) PDF',
            'pct'    => $pct,
        ];
    }

    private function checkMail(): array
    {
        $enabled   = $this->settingBool('notification_mail_enabled');
        $recipient = trim((string) (SystemSetting::getCached('notification_mail_recipient') ?? ''));

        if (! $enabled) {
            return ['status' => 'off', 'label' => 'Notifications mail', 'value' => 'Désactivé', 'detail' => 'Activez dans Paramètres → Notifications.'];
        }

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'warn', 'label' => 'Notifications mail', 'value' => 'Aucun destinataire', 'detail' => 'Configurez un email valide dans Paramètres.'];
        }

        $last = AlertNotification::where('channel', 'mail')
            ->where('status', 'sent')
            ->latest('sent_at')
            ->first();

        $lastFailed = AlertNotification::where('channel', 'mail')
            ->where('status', 'failed')
            ->latest()
            ->first();

        return [
            'status'  => 'ok',
            'label'   => 'Notifications mail',
            'value'   => 'Activé → '.$recipient,
            'detail'  => $last
                ? 'Dernier envoi réussi : '.$last->sent_at->diffForHumans()
                : ($lastFailed ? 'Aucun envoi réussi — dernier échec : '.$lastFailed->created_at->diffForHumans() : 'Aucun envoi encore.'),
            'host'    => config('mail.mailers.smtp.host'),
        ];
    }

    private function checkWebhook(): array
    {
        $enabled = $this->settingBool('notification_webhook_enabled');
        $url     = trim((string) (SystemSetting::getCached('notification_webhook_url') ?? ''));
        $type    = SystemSetting::getCached('notification_webhook_type') ?? 'slack';

        if (! $enabled) {
            return ['status' => 'off', 'label' => 'Webhook', 'value' => 'Désactivé', 'detail' => 'Activez dans Paramètres → Notifications.'];
        }

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['status' => 'warn', 'label' => 'Webhook', 'value' => 'URL non configurée', 'detail' => 'Configurez une URL valide dans Paramètres.'];
        }

        $sent   = AlertNotification::where('channel', 'webhook')->where('status', 'sent')->count();
        $failed = AlertNotification::where('channel', 'webhook')->where('status', 'failed')->count();
        $last   = AlertNotification::where('channel', 'webhook')->latest()->first();

        $successRate = ($sent + $failed) > 0 ? round($sent / ($sent + $failed) * 100) : null;

        return [
            'status'  => $failed > 0 && $sent === 0 ? 'warn' : 'ok',
            'label'   => 'Webhook ('.strtoupper($type).')',
            'value'   => $successRate !== null ? $successRate.'% succès ('.$sent.'/'.$failed.')' : 'Configuré, aucun envoi',
            'detail'  => $last ? 'Dernier envoi : '.$last->created_at->diffForHumans().' ('.$last->status.')' : 'Aucun envoi encore.',
            'url'     => $url,
        ];
    }

    private function checkAgents(): array
    {
        $total       = Agent::count();
        $online      = Agent::where('status', 'active')->count();
        $offline     = Agent::where('status', 'offline')->count();
        $compromised = Agent::where('status', 'compromised')->count();

        $threshold = (int) (SystemSetting::getCached('agent_offline_threshold_seconds') ?? 300);
        $lastBeat  = Agent::whereNotNull('last_seen_at')->orderByDesc('last_seen_at')->value('last_seen_at');

        $status = match (true) {
            $compromised > 0            => 'error',
            $offline > 0 && $total > 0  => 'warn',
            $total === 0                => 'warn',
            default                     => 'ok',
        };

        return [
            'status'      => $status,
            'label'       => 'Agents',
            'value'       => $online.'/'.$total.' en ligne'.($compromised > 0 ? ' · '.$compromised.' compromis' : ''),
            'detail'      => $lastBeat
                ? 'Dernier heartbeat : '.now()->diffForHumans($lastBeat).' · seuil hors-ligne : '.$threshold.'s'
                : 'Aucun agent connecté.',
            'compromised' => $compromised,
            'offline'     => $offline,
        ];
    }

    private function checkIncidents(): array
    {
        $open      = Incident::whereIn('status', ['open', 'investigating', 'under_review'])->count();
        $critical  = Incident::whereIn('status', ['open', 'investigating'])->where('risk_level', 'critical')->count();
        $openAlerts = Alert::whereIn('status', ['open', 'acknowledged'])->count();

        $status = match (true) {
            $critical > 0 => 'error',
            $open > 0     => 'warn',
            default       => 'ok',
        };

        return [
            'status'  => $status,
            'label'   => 'Incidents actifs',
            'value'   => $open.' incident(s) ouvert(s)'.($critical > 0 ? ' dont '.$critical.' CRITIQUE(S)' : ''),
            'detail'  => $openAlerts.' alerte(s) non résolue(s)',
            'critical' => $critical,
        ];
    }

    private function settingBool(string $key): bool
    {
        $v = SystemSetting::getCached($key) ?? '0';
        return in_array((string) $v, ['1', 'true', 'yes', 'on'], true);
    }
}
