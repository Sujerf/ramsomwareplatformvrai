<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\ProtectionAction;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentCommandController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid' => ['required', 'uuid', 'exists:agents,agent_uuid'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        $settings = SystemSetting::whereIn('key', [
            'enable_real_isolation',
            'enable_real_process_kill',
        ])->pluck('value', 'key');

        $allowIsolation = ($settings['enable_real_isolation'] ?? '0') === '1';
        $allowKill      = ($settings['enable_real_process_kill'] ?? '0') === '1';

        // Bug S fix — rollback_isolation toujours autorisé, indépendamment des
        // settings. Un agent isolé doit pouvoir être dé-isolé même si
        // enable_real_isolation est repassé à 0 après coup.
        // L'ancien early-return (empty($eligibleTypes)) bloquait TOUTES les
        // commandes quand les deux settings étaient à 0, empêchant définitivement
        // le rollback.
        $eligibleTypes = ['rollback_isolation'];
        if ($allowIsolation) {
            $eligibleTypes[] = 'isolate_host';
        }
        if ($allowKill) {
            $eligibleTypes[] = 'kill_process';
        }

        $actions = ProtectionAction::where('agent_id', $agent->id)
            ->where('approval_status', 'approved')
            ->whereIn('execution_status', ['pending', 'waiting_approval'])
            ->whereIn('action_type', $eligibleTypes)
            ->limit(5)
            ->get();

        // Marquer comme "en cours" pour éviter double-exécution
        $actions->each(fn ($a) => $a->update([
            'execution_status' => 'executing',
            'updated_at'       => now(),
        ]));

        $commands = $actions->map(fn ($a) => [
            'action_id'   => $a->id,
            'action_uuid' => $a->action_uuid ?? (string) $a->id,
            'action_type' => $a->action_type,
            'payload'     => $a->payload ?? [],
        ])->values();

        return response()->json(['commands' => $commands]);
    }

    public function result(Request $request, ProtectionAction $action): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid' => ['required', 'uuid', 'exists:agents,agent_uuid'],
            'success'    => ['required', 'boolean'],
            'message'    => ['nullable', 'string', 'max:500'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        if ($action->agent_id !== $agent->id) {
            return response()->json(['error' => 'Forbidden.'], 403);
        }

        $action->update([
            'execution_status' => $validated['success'] ? 'executed' : 'failed',
            'approval_status'  => $validated['success'] ? 'approved' : $action->approval_status,
            'executed_at'      => $validated['success'] ? now() : $action->executed_at,
            'payload'          => array_merge($action->payload ?? [], [
                'agent_execution_result'  => $validated['success'] ? 'success' : 'failure',
                'agent_execution_message' => $validated['message'] ?? null,
                'agent_executed_at'       => now()->toDateTimeString(),
            ]),
        ]);

        return response()->json([
            'message' => $validated['success']
                ? 'Action marquée comme exécutée.'
                : 'Action marquée comme échouée.',
            'action_id'        => $action->id,
            'execution_status' => $action->execution_status,
        ]);
    }
}
