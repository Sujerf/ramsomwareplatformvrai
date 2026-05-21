<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Alert;
use App\Models\AttackProfile;
use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\Event;
use App\Models\Incident;
use App\Models\RiskSnapshot;
use App\Models\SensitiveExtension;

class AgentRiskService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly ProtectionDecisionService $protectionDecisionService,
    ) {
    }

    public function handleIncomingEvent(Event $event): array
    {
        $event->loadMissing('agent');

        $analysis = $this->analyzeEvent($event);

        $event->update([
            'score' => $analysis['score'],
            'risk_level' => $analysis['risk_level'],
            'metadata' => array_merge($event->metadata ?? [], [
                'risk_analysis' => $analysis,
                'timeline_message' => $this->timelineMessageForEvent($event, $analysis),
            ]),
        ]);

        $this->updateAgentRisk($event->agent, $analysis['score'], $analysis['risk_level']);

        $riskSnapshot = RiskSnapshot::create([
            'agent_id' => $event->agent_id,
            'score' => $analysis['score'],
            'risk_level' => $analysis['risk_level'],
            'signals' => $analysis['signals'],
            'calculated_at' => now(),
        ]);

        $incident = null;
        $alert = null;
        $alertWasCreated = false;
        $actions = collect();

        if ($analysis['risk_level'] !== 'normal') {
            $incident = $this->createOrUpdateIncident($event, $analysis);

            $event->update([
                'incident_id' => $incident->id,
            ]);

            $riskSnapshot->update([
                'incident_id' => $incident->id,
            ]);

            [$alert, $alertWasCreated] = $this->createOrReuseAlert($event, $incident, $analysis);

            if ($alertWasCreated) {
                $this->notificationService->notifyAlert($alert);
            }

            $this->notificationService->notifyIncident(
                $incident,
                $alertWasCreated
                    ? 'Incident mis à jour après réception d’un nouvel événement suspect.'
                    : 'Incident mis à jour sans créer de nouvelle alerte répétée.'
            );

            $actions = $this->protectionDecisionService->evaluateIncident($incident);
        }

        return [
            'score' => $analysis['score'],
            'risk_level' => $analysis['risk_level'],
            'signals' => $analysis['signals'],
            'risk_snapshot_id' => $riskSnapshot->id,
            'incident_id' => $incident?->id,
            'alert_id' => $alert?->id,
            'alert_created' => $alertWasCreated,
            'protection_actions_count' => $actions->count(),
        ];
    }

    private function analyzeEvent(Event $event): array
    {
        $score = max(0, (int) $event->score);
        $signals = [];
        $highestRuleRiskLevel = 'normal';

        $rules = DetectionRule::where('is_enabled', true)->get();

        foreach ($rules as $rule) {
            if ($rule->event_type !== null && $rule->event_type !== $event->event_type) {
                continue;
            }

            if (! $this->ruleMatchesEvent($rule, $event)) {
                continue;
            }

            $score += (int) $rule->score_weight;
            $highestRuleRiskLevel = $this->higherRisk($highestRuleRiskLevel, $rule->risk_level);

            $signals[] = [
                'rule_code' => $rule->code,
                'rule_name' => $rule->name,
                'risk_level' => $rule->risk_level,
                'score_weight' => $rule->score_weight,
                'timeline_message' => 'Règle déclenchée : ' . $rule->name,
            ];
        }

        $riskLevelFromScore = $this->riskLevelFromScore($score);
        $finalRiskLevel = $this->higherRisk($riskLevelFromScore, $highestRuleRiskLevel);

        return [
            'score' => $score,
            'risk_level' => $finalRiskLevel,
            'signals' => $signals,
        ];
    }

    private function ruleMatchesEvent(DetectionRule $rule, Event $event): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($rule->code === 'extension_detected') {
            return $this->isSuspiciousExtension($event->file_extension);
        }

        if ($rule->code === 'ransom_note_detected') {
            return $this->looksLikeRansomNote($event->path, $conditions['keywords'] ?? []);
        }

        if (isset($conditions['threshold_key'])) {
            $threshold = $this->thresholdValue($conditions['threshold_key']);
            $windowSeconds = (int) ($conditions['window_seconds'] ?? 60);

            $recentCount = Event::query()
                ->where('agent_id', $event->agent_id)
                ->where('event_type', $event->event_type)
                ->where('created_at', '>=', now()->subSeconds($windowSeconds))
                ->count();

            return $recentCount >= $threshold;
        }

        return true;
    }

    private function isSuspiciousExtension(?string $extension): bool
    {
        if (! $extension) {
            return false;
        }

        $extension = strtolower(ltrim($extension, '.'));

        return SensitiveExtension::query()
            ->where('extension', $extension)
            ->where('category', 'suspicious')
            ->where('is_enabled', true)
            ->exists();
    }

    private function looksLikeRansomNote(?string $path, array $keywords): bool
    {
        if (! $path) {
            return false;
        }

        $path = strtolower($path);

        foreach ($keywords as $keyword) {
            if (str_contains($path, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function thresholdValue(string $key): int
    {
        return (int) (DetectionThreshold::where('key', $key)->value('value') ?? 1);
    }

    private function riskLevelFromScore(int $score): string
    {
        $critical = $this->thresholdValue('critical_score_min');
        $high = $this->thresholdValue('high_score_min');
        $suspect = $this->thresholdValue('suspect_score_min');

        return match (true) {
            $score >= $critical => 'critical',
            $score >= $high => 'high',
            $score >= $suspect => 'suspect',
            default => 'normal',
        };
    }

    private function higherRisk(string $a, string $b): string
    {
        $order = [
            'normal' => 0,
            'suspect' => 1,
            'high' => 2,
            'critical' => 3,
        ];

        return ($order[$b] ?? 0) > ($order[$a] ?? 0) ? $b : $a;
    }

    private function updateAgentRisk(Agent $agent, int $score, string $riskLevel): void
    {
        $agent->update([
            'risk_score' => max((int) $agent->risk_score, $score),
            'risk_level' => $this->higherRisk($agent->risk_level ?? 'normal', $riskLevel),
            'status' => $riskLevel === 'critical' ? 'compromised' : 'active',
            'last_seen_at' => now(),
        ]);
    }

    private function createOrUpdateIncident(Event $event, array $analysis): Incident
    {
        $attackProfileId = AttackProfile::where('code', $event->is_simulation ? 'controlled_demo_ransomware' : 'ransomware_behavior')
            ->value('id');

        $incident = Incident::query()
            ->where('agent_id', $event->agent_id)
            ->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
            ->latest()
            ->first();

        if (! $incident) {
            return Incident::create([
                'agent_id' => $event->agent_id,
                'attack_profile_id' => $attackProfileId,
                'title' => 'Suspicion de comportement ransomware',
                'description' => 'Un événement suspect a été reçu depuis un agent surveillé.',
                'status' => 'open',
                'risk_level' => $analysis['risk_level'],
                'risk_score' => $analysis['score'],
                'detected_at' => now(),
                'metadata' => [
                    'first_event_id' => $event->id,
                    'is_simulation' => $event->is_simulation,
                    'signals' => $analysis['signals'],
                    'timeline_message' => 'Incident créé automatiquement après analyse du risque.',
                ],
            ]);
        }

        $incident->update([
            'risk_level' => $this->higherRisk($incident->risk_level, $analysis['risk_level']),
            'risk_score' => max((int) $incident->risk_score, (int) $analysis['score']),
            'metadata' => array_merge($incident->metadata ?? [], [
                'last_event_id' => $event->id,
                'last_update_at' => now()->toDateTimeString(),
                'timeline_message' => 'Incident mis à jour avec un nouvel événement.',
            ]),
        ]);

        return $incident;
    }

    private function createOrReuseAlert(Event $event, Incident $incident, array $analysis): array
    {
        $recentDuplicate = Alert::query()
            ->where('incident_id', $incident->id)
            ->where('status', 'open')
            ->where('title', 'Alerte comportement ransomware')
            ->where('created_at', '>=', now()->subSeconds(120))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            $metadata = $recentDuplicate->metadata ?? [];
            $metadata['repeated_events_count'] = (int) ($metadata['repeated_events_count'] ?? 0) + 1;
            $metadata['last_repeated_event_id'] = $event->id;
            $metadata['last_repeated_event_type'] = $event->event_type;
            $metadata['timeline_message'] = 'Alerte existante réutilisée pour éviter un doublon.';

            $recentDuplicate->update([
                'score' => max((int) $recentDuplicate->score, (int) $analysis['score']),
                'risk_level' => $this->higherRisk($recentDuplicate->risk_level, $analysis['risk_level']),
                'metadata' => $metadata,
            ]);

            return [$recentDuplicate, false];
        }

        $alert = Alert::create([
            'agent_id' => $event->agent_id,
            'incident_id' => $incident->id,
            'event_id' => $event->id,
            'title' => 'Alerte comportement ransomware',
            'message' => 'RansomShield a détecté un événement suspect : ' . $event->event_type,
            'status' => 'open',
            'risk_level' => $analysis['risk_level'],
            'score' => $analysis['score'],
            'detected_at' => now(),
            'metadata' => [
                'event_type' => $event->event_type,
                'path' => $event->path,
                'signals' => $analysis['signals'],
                'is_simulation' => $event->is_simulation,
                'timeline_message' => 'Alerte créée automatiquement après analyse du risque.',
            ],
        ]);

        return [$alert, true];
    }

    private function timelineMessageForEvent(Event $event, array $analysis): string
    {
        if ($analysis['risk_level'] === 'normal') {
            return 'Événement reçu et classé normal.';
        }

        return 'Événement reçu et classé ' . $analysis['risk_level'] . ' avec un score de ' . $analysis['score'] . '.';
    }
}
