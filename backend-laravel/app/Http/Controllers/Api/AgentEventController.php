<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Event;
use App\Services\AgentRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reçoit les événements de l'agent Python et délègue toute l'orchestration
 * à AgentRiskService (analyse → snapshot → incident → alerte → protection).
 *
 * Ce contrôleur ne décide rien : il valide, crée l'Event brut, délègue.
 */
class AgentEventController extends Controller
{
    public function store(Request $request, AgentRiskService $riskService): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid'     => ['required', 'uuid'],
            'event_type'     => ['required', 'string', 'max:120'],
            'path'           => ['nullable', 'string', 'max:2000'],
            'file_extension' => ['nullable', 'string', 'max:50'],
            'score'          => ['nullable', 'integer', 'min:0'],
            'risk_level'     => ['nullable', 'string', 'max:50'],
            'is_simulation'  => ['nullable', 'boolean'],
            'metadata'       => ['nullable', 'array'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        // Création de l'event brut — score et risk_level seront mis à jour
        // par AgentRiskService après l'analyse dynamique.
        $event = Event::create([
            'event_uuid'     => (string) Str::uuid(),
            'agent_id'       => $agent->id,
            'event_type'     => $validated['event_type'],
            'path'           => $validated['path'] ?? null,
            'file_extension' => $validated['file_extension'] ?? null,
            'score'          => 0,
            'risk_level'     => 'normal',
            'is_simulation'  => (bool) ($validated['is_simulation'] ?? false),
            'metadata'       => $validated['metadata'] ?? [],
            'observed_at'    => now(),
        ]);

        // Délégation complète : analyse + snapshot + incident + alerte + actions
        $result = $riskService->handleIncomingEvent($event);

        // Recharger pour avoir score/risk_level mis à jour
        $event->refresh();

        return response()->json([
            'message'     => 'Événement reçu et analysé.',
            'event'       => [
                'id'         => $event->id,
                'event_uuid' => $event->event_uuid,
                'risk_level' => $event->risk_level,
                'score'      => $event->score,
            ],
            'analysis'    => [
                'risk_level'    => $result['risk_level'],
                'score'         => $result['score'],
                'signals_count' => count($result['signals']),
                'threshold'     => $result['threshold']['code'] ?? null,
            ],
            'alert_id'    => $result['alert_id'],
            'incident_id' => $result['incident_id'],
        ]);
    }
}
