<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        // 100 events/minute par agent (keyed by agent_uuid pour ignorer NAT)
        RateLimiter::for('agent-events', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->input('agent_uuid', $request->ip()))
                ->response(function () {
                    return response()->json(['error' => 'Too many events. Retry in 60s.'], 429);
                });
        });

        // 30 heartbeats/minute par agent
        RateLimiter::for('agent-heartbeat', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->input('agent_uuid', $request->ip()));
        });

        // 5 tentatives de challenge 2FA par 10 minutes par IP
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinutes(10, 5)->by($request->ip());
        });
    }
}
