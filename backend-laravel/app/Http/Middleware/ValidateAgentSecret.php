<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valide l'identité de l'agent sur chaque requête post-enrôlement.
 *
 * Modèle de sécurité (deux niveaux) :
 *
 *   1. Clé per-agent  (prioritaire)
 *      L'agent envoie son agent_uuid + X-Agent-Secret = sa clé unique.
 *      Le middleware retrouve l'agent en base et compare les clés en hash_equals.
 *      → Compromission d'un agent n'affecte pas les autres.
 *
 *   2. Secret global  (fallback de transition)
 *      Utilisé si l'agent n'a pas encore de clé (créé avant la migration
 *      ou en attente d'enrôlement). Disparaîtra quand tous les agents
 *      auront leur clé propre.
 *
 * L'endpoint /enroll est HORS de ce middleware — il a sa propre auth
 * par enrollment_token.
 */
class ValidateAgentSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $provided = (string) ($request->header('X-Agent-Secret') ?? $request->input('agent_secret', ''));

        // ── Niveau 1 : clé per-agent ─────────────────────────────────────────
        $agentUuid = $request->input('agent_uuid');

        if ($agentUuid) {
            $agent = Agent::where('agent_uuid', $agentUuid)->first();

            if ($agent && $agent->agent_api_key) {
                // Agent connu avec clé propre → validation stricte per-agent
                if (! hash_equals($agent->agent_api_key, $provided)) {
                    return response()->json([
                        'error'  => 'Unauthorized.',
                        'reason' => 'invalid_agent_key',
                    ], 401);
                }

                return $next($request);
            }
        }

        // ── Niveau 2 : secret global (fallback) ──────────────────────────────
        $globalSecret = config('app.agent_api_secret', '');

        if (! $globalSecret) {
            // Secret global vide = API non configurée, refus total
            return response()->json([
                'error'  => 'Unauthorized.',
                'reason' => 'api_not_configured',
            ], 401);
        }

        if (! hash_equals($globalSecret, $provided)) {
            return response()->json([
                'error'  => 'Unauthorized.',
                'reason' => 'invalid_global_secret',
            ], 401);
        }

        return $next($request);
    }
}
