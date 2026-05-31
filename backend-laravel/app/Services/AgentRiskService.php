<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Alert;
use App\Models\AttackProfile;
use App\Models\Event;
use App\Models\Incident;
use App\Models\RiskSnapshot;
use Illuminate\Support\Facades\DB;

/**
 * Orchestre tout le pipeline de détection pour un événement entrant.
 *
 * Flux :
 *   Event → DynamicDetectionEngineService::analyze()
 *        → RiskSnapshot
 *        → Incident (create ou update)
 *        → Alert (create ou réutiliser si doublon < 120s)
 *        → NotificationService (UI + son + mail)
 *        → ProtectionDecisionService (actions proposées)
 */
class AgentRiskService
{
    public function __construct(
        private readonly DynamicDetectionEngineService $detectionEngine,
        private readonly NotificationService $notificationService,
        private readonly ProtectionDecisionService $protectionDecisionService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    //  POINT D'ENTRÉE PRINCIPAL
    // ──────────────────────────────────────────────────────────────────────────

    public function handleIncomingEvent(Event $event): array
    {
        $event->loadMissing('agent');

        // ── Le serveur SOC ne doit pas générer d'incidents/alertes ───────────
        // Le rôle soc_server signifie que la machine fait tourner la console de
        // supervision elle-même. Elle génère naturellement de l'I/O intense
        // (PHP, MySQL, logs, outils de travail) qui déclencherait des centaines
        // de faux positifs. On enregistre l'event brut mais on n'escalade pas.
        if (($event->agent->host_role ?? '') === 'soc_server') {
            \Illuminate\Support\Facades\DB::table('events')
                ->where('id', $event->id)
                ->update([
                    'score'      => 0,
                    'risk_level' => 'normal',
                    'metadata'   => json_encode(array_merge(
                        is_array($event->metadata) ? $event->metadata : [],
                        ['skipped_reason' => 'soc_server role — no alerting on SOC machine']
                    ), JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);

            return [
                'risk_level'  => 'normal',
                'score'       => 0,
                'signals'     => [],
                'threshold'   => [],
                'alert_id'    => null,
                'incident_id' => null,
            ];
        }

        // ── Analyse via le moteur dynamique ──────────────────────────────────
        $analysis = $this->detectionEngine->analyze([
            'event_type'     => $event->event_type,
            'path'           => $event->path,
            'file_extension' => $event->file_extension,
            'is_simulation'  => $event->is_simulation,
            'metadata'       => $event->metadata ?? [],
        ]);

        $settings = $analysis['settings'];

        // ── Mettre à jour l'événement avec le résultat ───────────────────────
        $event->update([
            'score'      => $analysis['score'],
            'risk_level' => $analysis['risk_level'],
            'metadata'   => array_merge($event->metadata ?? [], [
                'risk_analysis'    => [
                    'score'      => $analysis['score'],
                    'risk_level' => $analysis['risk_level'],
                    'signals'    => $analysis['signals'],
                    'threshold'  => $analysis['threshold'],
                    'policies'   => $analysis['policies'],
                ],
                'timeline_message' => $this->timelineMessageForEvent($analysis),
            ]),
        ]);

        // ── Mettre à jour le risk score de l'agent ───────────────────────────
        $this->updateAgentRisk($event->agent, $analysis['score'], $analysis['risk_level']);

        // ── Snapshot du risque (traçabilité) ─────────────────────────────────
        $riskSnapshot = RiskSnapshot::create([
            'agent_id'      => $event->agent_id,
            'score'         => $analysis['score'],
            'risk_level'    => $analysis['risk_level'],
            'signals'       => $analysis['signals'],
            'calculated_at' => now(),
        ]);

        $incident        = null;
        $alert           = null;
        $alertWasCreated = false;
        $actions         = collect();

        // ── Incident : créé seulement si le risque atteint le seuil configuré ──
        // Le moteur calcule should_create_incident via min_risk_level_for_incident
        // (réglé dans Settings → Détection). Les événements en-dessous du seuil
        // sont quand même enregistrés comme events bruts, mais sans incident.
        if ($analysis['should_create_incident']) {
            $incident = $this->createOrUpdateIncident($event, $analysis);

            $event->update(['incident_id' => $incident->id]);
            $riskSnapshot->update(['incident_id' => $incident->id]);
        }

        // ── Alerte : créée pour tout événement non-normal (suspect → critical) ─
        // Une alerte peut exister sans incident (pour les niveaux intermédiaires).
        if ($analysis['should_create_alert']) {
            [$alert, $alertWasCreated] = $this->createOrReuseAlert($event, $incident, $analysis);

            if ($alertWasCreated) {
                // Notifications UI + son + mail (une seule fois par nouvelle alerte)
                $this->notificationService->notifyAlert($alert);
            }

            if ($incident) {
                $this->notificationService->notifyIncident(
                    $incident,
                    $alertWasCreated
                        ? "Incident mis à jour après réception d'un nouvel événement suspect."
                        : 'Incident mis à jour — alerte existante réutilisée (doublon < 120 s).'
                );
            }
        }

        // ── Actions de protection ─────────────────────────────────────────────
        if (
            $incident
            && $analysis['should_propose_action']
            && $settings['protection_execution_enabled'] === '1'
        ) {
            $actions = $this->protectionDecisionService->evaluateIncident($incident);
        }

        return [
            'score'                    => $analysis['score'],
            'risk_level'               => $analysis['risk_level'],
            'signals'                  => $analysis['signals'],
            'threshold'                => $analysis['threshold'],
            'policies'                 => $analysis['policies'],
            'settings'                 => $settings,
            'risk_snapshot_id'         => $riskSnapshot->id,
            'incident_id'              => $incident?->id,
            'alert_id'                 => $alert?->id,
            'alert_created'            => $alertWasCreated,
            'protection_actions_count' => $actions->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  MISE À JOUR AGENT
    // ──────────────────────────────────────────────────────────────────────────

    private function updateAgentRisk(Agent $agent, int $score, string $riskLevel): void
    {
        $agent->update([
            'risk_score'   => max((int) $agent->risk_score, $score),
            'risk_level'   => $this->higherRisk($agent->risk_level ?? 'normal', $riskLevel),
            'status'       => $riskLevel === 'critical' ? 'compromised' : 'active',
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Bug D — Décroissance du risque après résolution ou faux positif.
     *
     * Problème : updateAgentRisk() ne fait que monter le risque (max()).
     * Quand tous les incidents d'un agent sont résolus, son risk_level reste
     * bloqué à 'critical' indéfiniment — le tableau de bord affiche des agents
     * compromis alors que les incidents sont clos depuis longtemps.
     *
     * Correction : recalcule le risque depuis les incidents encore ouverts.
     *   - Aucun incident ouvert            → normal / 0
     *   - Incidents ouverts mais pas crit. → plus haut risk_level parmi eux
     *   - status 'compromised' remis à 'active' si le risque redescend
     *
     * Appelé par SocStatusSynchronizerService après resolveIncident()
     * et falsePositiveIncident().
     */
    public function recalculateAgentRisk(Agent $agent): void
    {
        $openIncidents = Incident::query()
            ->where('agent_id', $agent->id)
            ->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
            ->get(['risk_level', 'risk_score']);

        if ($openIncidents->isEmpty()) {
            $agent->update([
                'risk_score' => 0,
                'risk_level' => 'normal',
                'status'     => 'active',
                'updated_at' => now(),
            ]);

            return;
        }

        $order = ['normal' => 0, 'suspect' => 1, 'high' => 2, 'critical' => 3];

        $topLevel = $openIncidents->sortByDesc(
            fn ($inc) => $order[$inc->risk_level] ?? 0
        )->first()->risk_level;

        $topScore = $openIncidents->max('risk_score');

        $agent->update([
            'risk_score' => (int) $topScore,
            'risk_level' => $topLevel,
            'status'     => $topLevel === 'critical' ? 'compromised' : 'active',
            'updated_at' => now(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  INCIDENT
    // ──────────────────────────────────────────────────────────────────────────

    private function createOrUpdateIncident(Event $event, array $analysis): Incident
    {
        return DB::transaction(function () use ($event, $analysis) {
            $attackProfileId = AttackProfile::where(
                'code',
                $event->is_simulation ? 'controlled_demo_ransomware' : 'ransomware_behavior'
            )->value('id');

            $incident = Incident::query()
                ->where('agent_id', $event->agent_id)
                ->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
                ->lockForUpdate()
                ->latest()
                ->first();

            if (! $incident) {
                return Incident::create([
                    'incident_uuid'     => (string) \Illuminate\Support\Str::uuid(),
                    'agent_id'          => $event->agent_id,
                    'attack_profile_id' => $attackProfileId,
                    'title'             => 'Suspicion de comportement ransomware',
                    'description'       => 'Un événement suspect a été reçu depuis un agent surveillé.',
                    'status'            => 'open',
                    'risk_level'        => $analysis['risk_level'],
                    'risk_score'        => $analysis['score'],
                    'detected_at'       => now(),
                    'metadata'          => [
                        'first_event_id'   => $event->id,
                        'is_simulation'    => $event->is_simulation,
                        'signals'          => $analysis['signals'],
                        'threshold'        => $analysis['threshold'],
                        'timeline_message' => 'Incident créé automatiquement après analyse du risque.',
                    ],
                ]);
            }

            $incident->update([
                'risk_level' => $this->higherRisk($incident->risk_level, $analysis['risk_level']),
                'risk_score' => max((int) $incident->risk_score, (int) $analysis['score']),
                'metadata'   => array_merge($incident->metadata ?? [], [
                    'last_event_id'    => $event->id,
                    'last_update_at'   => now()->toDateTimeString(),
                    'timeline_message' => 'Incident mis à jour avec un nouvel événement.',
                    'signals'          => $analysis['signals'],
                ]),
            ]);

            return $incident;
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  ALERTE — déduplication 120 secondes
    // ──────────────────────────────────────────────────────────────────────────

    private function createOrReuseAlert(Event $event, ?Incident $incident, array $analysis): array
    {
        $recentDuplicate = Alert::query()
            ->where('agent_id', $event->agent_id)
            ->when($incident, fn ($q) => $q->where('incident_id', $incident->id))
            ->when(! $incident, fn ($q) => $q->whereNull('incident_id'))
            ->where('status', 'open')
            ->where('title', 'Alerte comportement ransomware')
            ->where('created_at', '>=', now()->subSeconds(120))
            ->latest()
            ->first();

        if ($recentDuplicate) {
            $metadata = $recentDuplicate->metadata ?? [];
            $metadata['repeated_events_count']   = (int) ($metadata['repeated_events_count'] ?? 0) + 1;
            $metadata['last_repeated_event_id']   = $event->id;
            $metadata['last_repeated_event_type'] = $event->event_type;
            $metadata['timeline_message']         = 'Alerte existante réutilisée pour éviter un doublon.';

            $recentDuplicate->update([
                'score'      => max((int) $recentDuplicate->score, (int) $analysis['score']),
                'risk_level' => $this->higherRisk($recentDuplicate->risk_level, $analysis['risk_level']),
                'metadata'   => $metadata,
            ]);

            return [$recentDuplicate, false];
        }

        $alert = Alert::create([
            'alert_uuid'  => (string) \Illuminate\Support\Str::uuid(),
            'agent_id'    => $event->agent_id,
            'incident_id' => $incident?->id,
            'event_id'    => $event->id,
            'title'       => 'Alerte comportement ransomware',
            'message'     => 'RansomShield a détecté un événement suspect : '.$event->event_type,
            'status'      => 'open',
            'risk_level'  => $analysis['risk_level'],
            'score'       => $analysis['score'],
            'detected_at' => now(),
            'metadata'    => [
                'event_type'      => $event->event_type,
                'path'            => $event->path,
                'signals'         => $analysis['signals'],
                'threshold'       => $analysis['threshold'],
                'is_simulation'   => $event->is_simulation,
                'timeline_message'=> 'Alerte créée automatiquement après analyse du risque.',
            ],
        ]);

        return [$alert, true];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  UTILITAIRES
    // ──────────────────────────────────────────────────────────────────────────

    private function higherRisk(string $a, string $b): string
    {
        $order = ['normal' => 0, 'suspect' => 1, 'high' => 2, 'critical' => 3];

        return ($order[$b] ?? 0) > ($order[$a] ?? 0) ? $b : $a;
    }

    private function timelineMessageForEvent(array $analysis): string
    {
        if ($analysis['risk_level'] === 'normal') {
            return 'Événement reçu et classé normal.';
        }

        return 'Événement reçu et classé '.$analysis['risk_level']
            .' (score '.$analysis['score'].')'
            .' — '.(count($analysis['signals'])).' signal(s) déclenché(s).';
    }
}
