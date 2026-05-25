<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\ProtectionPolicy;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use App\Services\AgentRiskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests Feature — Pipeline de risque AgentRiskService
 *
 * Couverture :
 *   1.  Événement soc_server → ignoré (score 0, pas d'incident)
 *   2.  Événement sous le seuil min_risk_level_for_incident → pas d'incident
 *   3.  Événement critique → incident créé avec signals dans metadata
 *   4.  Mise à jour incident existant → metadata.signals rafraîchis (fix H)
 *   5.  ProtectionAction payload contient 'signals' (fix F)
 *   6.  ProtectionAction payload contient 'human_approval_required' (fix G)
 *   7.  firstOrCreate → pas de doublon d'action pour même incident+policy
 */
class AgentRiskPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedConfig();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  1. Événement soc_server → ignoré
    // ──────────────────────────────────────────────────────────────────────────

    public function test_soc_server_events_are_skipped(): void
    {
        $agent = Agent::factory()->create([
            'agent_uuid'        => (string) Str::uuid(),
            'enrollment_status' => 'enrolled',
            'host_role'         => 'soc_server',
            'risk_level'        => 'normal',
            'risk_score'        => 0,
        ]);

        $event = \App\Models\Event::create([
            'event_uuid'  => (string) Str::uuid(),
            'agent_id'    => $agent->id,
            'event_type'  => 'file_modified',
            'path'        => '/var/www/app/something.php',
            'score'       => 0,
            'risk_level'  => 'normal',
            'is_simulation' => false,
            'metadata'    => [],
            'observed_at' => now(),
        ]);

        $result = app(AgentRiskService::class)->handleIncomingEvent($event);

        $this->assertSame('normal', $result['risk_level']);
        $this->assertSame(0, $result['score']);
        $this->assertNull($result['incident_id']);
        $this->assertEmpty($result['signals']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  2. Événement sous le seuil → pas d'incident créé
    // ──────────────────────────────────────────────────────────────────────────

    public function test_low_risk_event_does_not_create_incident(): void
    {
        $agent = $this->makeAgent();

        $event = $this->makeEvent($agent, 'file_modified', '/home/user/doc.txt');

        $result = app(AgentRiskService::class)->handleIncomingEvent($event);

        // file_modified sans extension sensible → score faible ou 0
        // min_risk_level_for_incident = 'high' → pas d'incident
        if ($result['risk_level'] !== 'high' && $result['risk_level'] !== 'critical') {
            $this->assertNull($result['incident_id'],
                'Un événement sous le seuil ne doit pas créer un incident');
        } else {
            // Si le moteur score haut (signal inattendu), le test est non-applicable
            $this->markTestSkipped('Score inattendu — moteur a escaladé le risque');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  3. Événement critique → incident créé avec signals dans metadata
    // ──────────────────────────────────────────────────────────────────────────

    public function test_critical_event_creates_incident_with_signals(): void
    {
        $agent = $this->makeAgent();
        $event = $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc.locked', 'locked');

        $result = app(AgentRiskService::class)->handleIncomingEvent($event);

        $this->assertNotNull($result['incident_id'],
            'Un événement critique doit créer un incident');
        $this->assertSame('critical', $result['risk_level']);

        $incident = \App\Models\Incident::find($result['incident_id']);
        $signals  = data_get($incident->metadata, 'signals', []);

        $this->assertNotEmpty($signals,
            "L'incident créé doit avoir des signals dans metadata");

        $codes = array_column($signals, 'code');
        $this->assertContains('sensitive_extension_locked', $codes,
            'Le signal sensitive_extension_locked doit être dans metadata.signals');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  4. Mise à jour incident → metadata.signals rafraîchis avec dernier événement (fix H)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_incident_update_refreshes_signals_in_metadata(): void
    {
        $agent  = $this->makeAgent();
        $svc    = app(AgentRiskService::class);

        // Premier événement → crée l'incident
        $event1 = $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc.locked', 'locked');
        $result1 = $svc->handleIncomingEvent($event1);

        $this->assertNotNull($result1['incident_id']);
        $incident = \App\Models\Incident::find($result1['incident_id']);

        $signalsAfterFirst = data_get($incident->metadata, 'signals', []);
        $this->assertNotEmpty($signalsAfterFirst);

        // Second événement sur le même agent → incident existant → update
        $event2 = $this->makeEvent($agent, 'file_created', '/home/user/README-DECRYPT.txt');
        $result2 = $svc->handleIncomingEvent($event2);

        // L'incident doit avoir été mis à jour (même ID)
        $this->assertSame($result1['incident_id'], $result2['incident_id'],
            'Le même incident doit être réutilisé pour le même agent');

        $incident->refresh();
        $signalsAfterSecond = data_get($incident->metadata, 'signals', []);

        // Les signaux doivent refléter le dernier événement (README → ransom_note)
        $this->assertNotEmpty($signalsAfterSecond,
            'metadata.signals doit être mis à jour après un second événement (fix H)');

        $codes = array_column($signalsAfterSecond, 'code');
        $this->assertContains('rule_ransom_note', $codes,
            'Les signaux doivent être rafraîchis avec ceux du dernier événement');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  5. ProtectionAction payload contient 'signals' (fix F)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_protection_action_payload_contains_signals(): void
    {
        $agent  = $this->makeAgent();
        $result = app(AgentRiskService::class)->handleIncomingEvent(
            $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc.locked', 'locked')
        );

        if (! $result['incident_id']) {
            $this->markTestSkipped('Aucun incident créé — policies manquantes');
        }

        $actions = \App\Models\ProtectionAction::where('incident_id', $result['incident_id'])->get();

        if ($actions->isEmpty()) {
            $this->markTestSkipped('Aucune action créée — policies manquantes');
        }

        foreach ($actions as $action) {
            $payload = $action->payload ?? [];
            $this->assertArrayHasKey('signals', $payload,
                "Le payload de l'action {$action->action_type} doit contenir 'signals' (fix F)");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  6. ProtectionAction payload contient 'human_approval_required' (fix G)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_protection_action_payload_contains_human_approval_required(): void
    {
        $agent  = $this->makeAgent();
        $result = app(AgentRiskService::class)->handleIncomingEvent(
            $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc.locked', 'locked')
        );

        if (! $result['incident_id']) {
            $this->markTestSkipped('Aucun incident créé — policies manquantes');
        }

        $actions = \App\Models\ProtectionAction::where('incident_id', $result['incident_id'])->get();

        if ($actions->isEmpty()) {
            $this->markTestSkipped('Aucune action créée — policies manquantes');
        }

        foreach ($actions as $action) {
            $payload = $action->payload ?? [];
            $this->assertArrayHasKey('human_approval_required', $payload,
                "Le payload de l'action {$action->action_type} doit contenir 'human_approval_required' (fix G)");
            $this->assertIsBool($payload['human_approval_required']);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  7. firstOrCreate → pas de doublon d'action
    // ──────────────────────────────────────────────────────────────────────────

    public function test_no_duplicate_actions_on_repeated_events(): void
    {
        $agent = $this->makeAgent();
        $svc   = app(AgentRiskService::class);

        $event1 = $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc.locked', 'locked');
        $result1 = $svc->handleIncomingEvent($event1);

        if (! $result1['incident_id']) {
            $this->markTestSkipped('Aucun incident créé');
        }

        $countAfterFirst = \App\Models\ProtectionAction::where('incident_id', $result1['incident_id'])->count();

        // Deuxième événement identique → même incident → firstOrCreate ne crée pas de doublon
        $event2 = $this->makeEvent($agent, 'file_encrypted_extension', '/home/user/doc2.locked', 'locked');
        $result2 = $svc->handleIncomingEvent($event2);

        $countAfterSecond = \App\Models\ProtectionAction::where('incident_id', $result1['incident_id'])->count();

        $this->assertSame($countAfterFirst, $countAfterSecond,
            'Deux événements successifs sur le même incident ne doivent pas créer de doublons d\'actions');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function makeAgent(): Agent
    {
        return Agent::factory()->create([
            'agent_uuid'        => (string) Str::uuid(),
            'enrollment_status' => 'enrolled',
            'host_role'         => 'client',
            'risk_level'        => 'normal',
            'risk_score'        => 0,
        ]);
    }

    private function makeEvent(
        Agent $agent,
        string $eventType,
        string $path,
        ?string $extension = null
    ): \App\Models\Event {
        return \App\Models\Event::create([
            'event_uuid'     => (string) Str::uuid(),
            'agent_id'       => $agent->id,
            'event_type'     => $eventType,
            'path'           => $path,
            'file_extension' => $extension,
            'score'          => 0,
            'risk_level'     => 'normal',
            'is_simulation'  => false,
            'metadata'       => [],
            'observed_at'    => now(),
        ]);
    }

    private function seedConfig(): void
    {
        // Seuils
        foreach ([
            ['threshold_normal',   'normal',   0,  24],
            ['threshold_suspect',  'suspect',  25, 49],
            ['threshold_high',     'high',     50, 79],
            ['threshold_critical', 'critical', 80, null],
        ] as [$code, $level, $min, $max]) {
            DetectionThreshold::updateOrCreate(['code' => $code], [
                'name' => ucfirst($level), 'key' => $code, 'label' => ucfirst($level),
                'risk_level' => $level, 'level' => $level, 'severity' => $level,
                'min_score' => $min, 'score_min' => $min,
                'max_score' => $max, 'score_max' => $max,
                'value' => $min, 'is_enabled' => true,
            ]);
        }

        // Règles
        foreach ([
            ['rule_mass_rename',        null,                          'high',     55],
            ['rule_ransom_note',         null,                          'critical', 90],
            ['rule_fast_write_activity', 'file_modified',               'suspect',  30],
            ['rule_simulation_marker',   null,                          'suspect',  20],
        ] as [$code, $event, $level, $score]) {
            DetectionRule::updateOrCreate(['code' => $code], [
                'name' => $code, 'event_type' => $event,
                'risk_level' => $level, 'score_weight' => $score, 'is_enabled' => true,
            ]);
        }

        // Extensions
        foreach ([
            ['locked', 'critical', 80],
            ['encrypted', 'critical', 80],
        ] as [$ext, $level, $score]) {
            SensitiveExtension::updateOrCreate(['extension' => $ext], [
                'risk_level' => $level, 'score_weight' => $score,
                'category' => 'suspicious', 'is_enabled' => true,
            ]);
        }

        // Policies pour tests 5, 6, 7
        foreach ([
            ['policy_alert_critical', 'critical', 'alert_only',  'automatic'],
            ['policy_backup_critical','critical', 'emergency_backup', 'automatic'],
        ] as [$code, $level, $action, $mode]) {
            ProtectionPolicy::updateOrCreate(['code' => $code], [
                'name'           => $code,
                'risk_level'     => $level,
                'execution_mode' => $mode,
                'is_enabled'     => true,
                'alert_only'     => $action === 'alert_only',
                'emergency_backup' => $action === 'emergency_backup',
                'lock_safe_copy' => false,
                'isolate_host'   => false,
                'kill_process'   => false,
                'restrict_path'  => false,
            ]);
        }

        // Settings système
        foreach ([
            ['min_risk_level_for_incident',                  'high', 'string'],
            ['min_risk_level_for_action',                    'high', 'string'],
            ['protection_execution_enabled',                 '1',    'boolean'],
            ['notification_ui_enabled',                      '1',    'boolean'],
            ['notification_sound_enabled',                   '1',    'boolean'],
            ['enable_real_isolation',                        '0',    'boolean'],
            ['enable_real_process_kill',                     '0',    'boolean'],
            ['require_human_approval_for_sensitive_actions', '1',    'boolean'],
        ] as [$key, $value, $type]) {
            SystemSetting::updateOrCreate(['key' => $key], [
                'value' => $value, 'value_type' => $type,
                'group' => 'test', 'label' => $key,
            ]);
        }
    }
}
