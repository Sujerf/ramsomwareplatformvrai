<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentHeartbeatController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid' => ['required', 'uuid', 'exists:agents,agent_uuid'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'metadata' => ['nullable', 'array'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        $agent->update([
            'hostname' => $validated['hostname'] ?? $agent->hostname,
            'ip_address' => $validated['ip_address'] ?? $request->ip(),
            'status' => 'active',
            'last_seen_at' => now(),
            'metadata' => $validated['metadata'] ?? $agent->metadata,
        ]);

        return response()->json([
            'message' => 'Heartbeat reçu.',
            'agent' => [
                'id' => $agent->id,
                'agent_uuid' => $agent->agent_uuid,
                'status' => $agent->status,
                'risk_level' => $agent->risk_level,
                'risk_score' => $agent->risk_score,
            ],
        ]);
    }
}
