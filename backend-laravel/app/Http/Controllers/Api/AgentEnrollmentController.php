<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\HostEnrollmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentEnrollmentController extends Controller
{
    public function store(Request $request, HostEnrollmentService $hostEnrollment): JsonResponse
    {
        $validated = $request->validate([
            'agent_name' => ['required', 'string', 'max:255'],
            'agent_uuid' => ['nullable', 'uuid'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'string', 'max:255'],
            'host_role' => ['nullable', 'in:client,file_server,soc_server,attacker_demo,unknown'],
            'metadata' => ['nullable', 'array'],
        ]);

        $agentUuid = $validated['agent_uuid'] ?? (string) Str::uuid();

        $agent = Agent::firstOrNew([
            'agent_uuid' => $agentUuid,
        ]);

        $isNewAgent = ! $agent->exists;

        $agent->fill([
            'agent_name' => $validated['agent_name'],
            'hostname' => $validated['hostname'] ?? null,
            'ip_address' => $validated['ip_address'] ?? $request->ip(),
            'mac_address' => $validated['mac_address'] ?? null,
            'host_role' => $validated['host_role'] ?? 'unknown',
            'status' => 'active',
            'last_seen_at' => now(),
            'metadata' => $validated['metadata'] ?? null,
        ]);

        if ($isNewAgent) {
            $agent->risk_level = 'normal';
            $agent->risk_score = 0;
            $agent->is_isolated = false;
        }

        $agent->save();

        $agent = $hostEnrollment->linkRealEnrollment($validated, $agent);

        return response()->json([
            'message' => $isNewAgent
                ? 'Agent enrôlé avec succès.'
                : 'Agent déjà connu, informations mises à jour.',
            'agent' => [
                'id' => $agent->id,
                'agent_uuid' => $agent->agent_uuid,
                'agent_name' => $agent->agent_name,
                'status' => $agent->status,
                'risk_level' => $agent->risk_level,
                'risk_score' => $agent->risk_score,
                'is_isolated' => $agent->is_isolated,
                'last_seen_at' => optional($agent->last_seen_at)->toDateTimeString(),
            ],
        ], $isNewAgent ? 201 : 200);
    }
}