<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Scan actif de tous les réseaux surveillés toutes les 5 minutes.
        // Utilise fping si disponible, sinon ping sweep ou ARP fallback.
        $schedule->command('ransomshield:scan-networks')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/network-scan.log'));

        $schedule->command('ransomshield:check-offline-agents')
                 ->everyFiveMinutes()
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/agent-health.log'));
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'agent.secret' => \App\Http\Middleware\ValidateAgentSecret::class,
            'role'         => \App\Http\Middleware\EnsureRole::class,
        ]);

        /*
         * Priorité middleware : EnsureRole doit s'exécuter AVANT SubstituteBindings
         * pour que le 403 soit retourné avant que le model binding tente une résolution DB.
         * Sans cette priorité, les routes avec {model} non existants retournent 404
         * au lieu de 403 — même quand l'utilisateur n'a pas le rôle requis.
         */
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class,
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\EnsureRole::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('platform.login'));
        $middleware->redirectUsersTo(fn () => route('platform.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ── Pages d'erreur personnalisées RansomShield ────────────────────────
        // Utilisent errors/layout.blade.php (standalone, sans requêtes BDD)
        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->expectsJson()) {
                return response()->view('errors.404', ['exception' => $e], 404);
            }
        });

        $exceptions->render(function (
            \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->expectsJson()) {
                return response()->view('errors.403', ['exception' => $e], 403);
            }
        });

        $exceptions->render(function (
            \Illuminate\Session\TokenMismatchException $e,
            \Illuminate\Http\Request $request
        ) {
            if (! $request->expectsJson()) {
                return response()->view('errors.419', ['exception' => $e], 419);
            }
        });
    })->create();