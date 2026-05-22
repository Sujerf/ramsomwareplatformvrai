<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAgentSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.agent_api_secret');

        if (! $secret) {
            return $next($request);
        }

        $provided = $request->header('X-Agent-Secret')
            ?? $request->input('agent_secret');

        if (! hash_equals($secret, (string) $provided)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
