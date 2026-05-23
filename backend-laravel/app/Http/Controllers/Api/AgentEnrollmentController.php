<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\HostEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gère l'enrôlement d'un agent Python.
 *
 * Flux sécurisé :
 *   1. L'agent envoie : agent_uuid + enrollment_token + infos machine
 *   2. L'API retrouve l'Agent pré-autorisé par le SOC via agent_uuid OU enrollment_token
 *   3. Validation du token : correspondance + non expiré
 *   4. En cas d'échec → 401 (aucune indication sur la raison exacte)
 *   5. En cas de succès → enrôlement, token détruit (usage unique)
 *
 * Rétro-compatibilité : si aucun token n'est fourni ET que l'agent est déjà
 * enrôlé (enrollment_status='enrolled'), on accepte la reconnexion.
 */
class AgentEnrollmentController extends Controller
{
    public function store(Request $request, HostEnrollmentService $hostEnrollment): JsonResponse
    {
        $validated = $request->validate([
            'agent_name'       => ['required', 'string', 'max:255'],
            'agent_uuid'       => ['nullable', 'uuid'],
            'enrollment_token' => ['nullable', 'string', 'max:255'],
            'hostname'         => ['nullable', 'string', 'max:255'],
            'ip_address'       => ['nullable', 'ip'],
            'mac_address'      => ['nullable', 'string', 'max:255'],
            'host_role'        => ['nullable', 'in:client,file_server,soc_server,attacker_demo,unknown'],
            'metadata'         => ['nullable', 'array'],
        ]);

        $providedUuid  = $validated['agent_uuid'] ?? null;
        $providedToken = $validated['enrollment_token'] ?? null;

        // ── Recherche de l'agent pré-autorisé ────────────────────────────────
        $agent = null;

        if ($providedUuid) {
            $agent = Agent::where('agent_uuid', $providedUuid)->first();
        }

        if (! $agent && $providedToken) {
            $agent = Agent::where('enrollment_token', $providedToken)->first();
        }

        // ── Validation du token d'enrôlement ─────────────────────────────────
        if ($agent) {
            $isAlreadyEnrolled = $agent->enrollment_status === 'enrolled';
            $hasToken          = ! empty($agent->enrollment_token);

            if (! $isAlreadyEnrolled) {
                // Machine pending : le token est OBLIGATOIRE et doit être valide
                if (! $hasToken) {
                    // Aucun token en base → agent non pré-autorisé ou token déjà consommé
                    return response()->json([
                        'error' => 'Enrollment token required. Pre-enroll this host from the SOC console.',
                    ], 401);
                }

                if (! $providedToken || ! hash_equals($agent->enrollment_token, $providedToken)) {
                    return response()->json([
                        'error' => 'Invalid enrollment token.',
                    ], 401);
                }

                if ($agent->enrollment_token_expires_at && now()->gt($agent->enrollment_token_expires_at)) {
                    return response()->json([
                        'error' => 'Enrollment token has expired. Generate a new one from the SOC console.',
                    ], 401);
                }
            }
            // Si déjà enrôlé : on accepte la reconnexion sans re-valider le token
            // (le token a été détruit au premier enrôlement)

        } else {
            // Aucun agent pré-autorisé trouvé — création interdite sans pré-autorisation SOC
            return response()->json([
                'error' => 'No pre-authorized agent found. Pre-enroll this host from the SOC console first.',
            ], 401);
        }

        // ── Mise à jour des infos machine ─────────────────────────────────────
        $isFirstEnrollment = $agent->enrollment_status !== 'enrolled';

        $agent->fill([
            'agent_name'  => $validated['agent_name'],
            'hostname'    => $validated['hostname'] ?? $agent->hostname,
            'ip_address'  => $validated['ip_address'] ?? $request->ip(),
            'mac_address' => $validated['mac_address'] ?? $agent->mac_address,
            'host_role'   => $validated['host_role'] ?? $agent->host_role ?? 'client',
            'status'      => 'active',
            'last_seen_at'=> now(),
            'metadata'    => $validated['metadata'] ?? $agent->metadata,
        ]);

        $agent->save();

        // ── Finalisation de l'enrôlement (token détruit, lien DiscoveredHost) ─
        $agent = $hostEnrollment->linkRealEnrollment($validated, $agent);

        return response()->json([
            'message' => $isFirstEnrollment
                ? 'Agent enrolled successfully.'
                : 'Agent already enrolled — info updated.',
            'agent' => [
                'id'           => $agent->id,
                'agent_uuid'   => $agent->agent_uuid,
                'agent_name'   => $agent->agent_name,
                'status'       => $agent->status,
                'risk_level'   => $agent->risk_level,
                'risk_score'   => $agent->risk_score,
                'is_isolated'  => $agent->is_isolated,
                'last_seen_at' => optional($agent->last_seen_at)->toDateTimeString(),
            ],
        ], $isFirstEnrollment ? 201 : 200);
    }
}
