<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de contrôle d'accès par rôle.
 *
 * Usage dans les routes :
 *   ->middleware('role:admin')
 *   ->middleware('role:admin,analyst')  ← l'un ou l'autre
 *
 * Retourne 403 si l'utilisateur authentifié n'a pas l'un des rôles requis.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            abort(403, 'Accès non autorisé — rôle insuffisant.');
        }

        return $next($request);
    }
}
