<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Event;
use App\Models\Incident;
use App\Models\ProtectionAction;
use App\Models\ProtectionPolicy;
use App\Services\DynamicDetectionEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentEventController extends Controller
{
    public function store(Request $request, DynamicDetectionEngineService $engine): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid' => ['required', 'uuid'],
            'event_type' => ['required', 'string', 'max:120'],
            'path' => ['nullable', 'string', 'max:2000'],
            'file_extension' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'integer', 'min:0'],
            'risk_level' => ['nullable', 'string', 'max:50'],
            'is_simulation' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        $analysis = $engine->analyze($validated);

        $event = Event::create([
            'event_uuid' => (string) Str::uuid(),
            'agent_id' => $agent->id,
            'event_type' => $validated['event_type'],
            'path' => $validated['path'] ?? null,
            'file_extension' => $validated['file_extension'] ?? null,
            'score' => $analysis['score'],
            'risk_level' => $analysis['risk_level'],
            'is_simulation' => (bool) ($validated['is_simulation'] ?? false),
            'metadata' => array_merge($validated['metadata'] ?? [], [
                'dynamic_detection' => true,
                'signals' => $analysis['signals'],
                'threshold' => $analysis['threshold'],
                'matched_policies' => $analysis['policies'],
                'settings' => $analysis['settings'],
            ]),
            'observed_at' => now(),
        ]);

        $agent->update([
            'risk_score' => max((int) $agent->risk_score, (int) $analysis['score']),
            'risk_level' => $analysis['risk_level'],
            'last_seen_at' => now(),
            'status' => $analysis['risk_level'] === 'critical' ? 'compromised' : 'active',
        ]);

        $incident = null;
        $alert = null;
        $actions = [];

        if ($analysis['should_create_alert']) {
            $alert = Alert::create([
                'alert_uuid' => (string) Str::uuid(),
                'agent_id' => $agent->id,
                'event_id' => $event->id,
                'title' => 'Alerte '.$analysis['risk_level'].' détectée',
                'message' => 'RansomShield a détecté un comportement '.$analysis['risk_level'].' sur '.$agent->agent_name.'.',
                'risk_level' => $analysis['risk_level'],
                'score' => $analysis['score'],
                'status' => 'open',
                'metadata' => [
                    'signals' => $analysis['signals'],
                    'path' => $validated['path'] ?? null,
                    'is_simulation' => (bool) ($validated['is_simulation'] ?? false),
                ],
                'detected_at' => now(),
            ]);
        }

        if ($analysis['should_create_incident']) {
            $incident = Incident::create([
                'incident_uuid' => (string) Str::uuid(),
                'agent_id' => $agent->id,
                'title' => 'Incident '.$analysis['risk_level'].' — '.$agent->agent_name,
                'description' => 'Incident créé automatiquement depuis le moteur dynamique de détection.',
                'risk_level' => $analysis['risk_level'],
                'risk_score' => $analysis['score'],
                'status' => 'open',
                'metadata' => [
                    'signals' => $analysis['signals'],
                    'threshold' => $analysis['threshold'],
                    'source_event_id' => $event->id,
                ],
                'detected_at' => now(),
            ]);

            if ($alert) {
                $alert->update([
                    'incident_id' => $incident->id,
                ]);
            }
        }

        if ($incident && $analysis['should_propose_action']) {
            foreach ($analysis['policies'] as $policyData) {
                $policy = ProtectionPolicy::find($policyData['id']);

                if (! $policy) {
                    continue;
                }

                $approvalStatus = $policy->execution_mode === 'automatic' ? 'approved' : 'pending';
                $executionStatus = $policy->execution_mode === 'automatic' ? 'pending' : 'waiting_approval';

                $action = ProtectionAction::create([
                    'action_uuid' => (string) Str::uuid(),
                    'agent_id' => $agent->id,
                    'incident_id' => $incident->id,
                    'protection_policy_id' => $policy->id,
                    'action_type' => $policy->action_type,
                    'decision_mode' => $policy->execution_mode,
                    'approval_status' => $approvalStatus,
                    'execution_status' => $executionStatus,
                    'description' => $policy->description,
                    'is_reversible' => true,
                    'payload' => [
                        'dynamic_detection' => true,
                        'risk_level' => $analysis['risk_level'],
                        'risk_score' => $analysis['score'],
                        'policy_code' => $policy->code,
                        'signals' => $analysis['signals'],
                        'real_execution_allowed' => $analysis['settings']['enable_real_isolation'] === '1',
                        'human_approval_required' => $analysis['settings']['require_human_approval_for_sensitive_actions'] === '1',
                        'timeline_message' => 'Action proposée automatiquement selon la politique '.$policy->code.'.',
                    ],
                    'proposed_at' => now(),
                ]);

                $actions[] = [
                    'id' => $action->id,
                    'action_type' => $action->action_type,
                    'approval_status' => $action->approval_status,
                    'execution_status' => $action->execution_status,
                ];
            }
        }

        return response()->json([
            'message' => 'Événement reçu et analysé dynamiquement.',
            'event' => [
                'id' => $event->id,
                'event_uuid' => $event->event_uuid,
                'risk_level' => $event->risk_level,
                'score' => $event->score,
            ],
            'analysis' => [
                'risk_level' => $analysis['risk_level'],
                'score' => $analysis['score'],
                'threshold' => $analysis['threshold'],
                'signals_count' => count($analysis['signals']),
                'policies_count' => count($analysis['policies']),
            ],
            'alert_id' => $alert?->id,
            'incident_id' => $incident?->id,
            'actions' => $actions,
        ]);
    }
}
