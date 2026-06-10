<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests d'intégration end-to-end — agent → API → détection → incident.
 *
 * Ces tests vérifient que le pipeline complet fonctionne via les endpoints HTTP,
 * pas seulement le moteur de détection en isolation.
 *
 *   1. Enrôlement : agent pending → POST /enroll → API key assignée
 *   2. Événement critique : POST /events avec clé per-agent → incident + alerte créés
 *   3. Événement normal : aucun incident créé sous le seuil
 *   4. Authentification : clé invalide → 401
 */
class AgentEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalConfig();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 1 — Enrôlement complet via API
    // ──────────────────────────────────────────────────────────────────────────

    public function test_enrollment_flow_returns_api_key(): void
    {
        $agent = $this->makePendingAgent();

        $response = $this->postJson('/api/agent/enroll', [
            'agent_name'       => 'test-victim-pc',
            'agent_uuid'       => $agent->agent_uuid,
            'enrollment_token' => 'test-token-e2e',
            'hostname'         => 'victim-pc',
            'ip_address'       => '10.0.0.50',
            'host_role'        => 'client',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Agent enrolled successfully.');

        $apiKey = $response->json('agent.agent_api_key');
        $this->assertNotNull($apiKey, 'La clé API per-agent doit être retournée au premier enrôlement.');
        $this->assertGreaterThanOrEqual(32, strlen($apiKey), 'La clé doit être suffisamment longue.');

        $this->assertDatabaseHas('agents', [
            'agent_uuid'        => $agent->agent_uuid,
            'enrollment_status' => 'enrolled',
        ]);

        // Le token doit être détruit après usage unique
        $agent->refresh();
        $this->assertNull($agent->enrollment_token, 'Le token doit être effacé après enrôlement.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 2 — Événement critique → incident + alerte créés
    // ──────────────────────────────────────────────────────────────────────────

    public function test_critical_event_creates_incident_and_alert(): void
    {
        $agent  = $this->makeEnrolledAgent();
        $apiKey = $agent->agent_api_key;

        $response = $this->withHeaders(['X-Agent-Secret' => $apiKey])
            ->postJson('/api/agent/events', [
                'agent_uuid' => $agent->agent_uuid,
                'event_type' => 'file_created',
                'path'       => '/home/user/Documents/README-DECRYPT.txt',
                'metadata'   => ['os' => 'Linux'],
            ]);

        $response->assertStatus(200);

        $analysis = $response->json('analysis');
        $this->assertSame('critical', $analysis['risk_level'],
            'Une note de rançon doit atteindre le niveau critical.');
        $this->assertGreaterThanOrEqual(80, $analysis['score']);

        $this->assertDatabaseHas('incidents', [
            'agent_id' => $agent->id,
            'status'   => 'open',
        ]);

        $this->assertDatabaseHas('alerts', [
            'agent_id' => $agent->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 3 — Événement normal sous le seuil → pas d'incident
    // ──────────────────────────────────────────────────────────────────────────

    public function test_normal_event_does_not_create_incident(): void
    {
        $agent = $this->makeEnrolledAgent();

        $response = $this->withHeaders(['X-Agent-Secret' => $agent->agent_api_key])
            ->postJson('/api/agent/events', [
                'agent_uuid' => $agent->agent_uuid,
                'event_type' => 'file_created',
                'path'       => '/home/user/Documents/report.docx',
            ]);

        $response->assertStatus(200);
        $this->assertSame('normal', $response->json('analysis.risk_level'));

        $this->assertDatabaseMissing('incidents', ['agent_id' => $agent->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 4 — Extension chiffrée → incident créé via le pipeline API
    // ──────────────────────────────────────────────────────────────────────────

    public function test_encrypted_extension_event_creates_incident_via_api(): void
    {
        $agent = $this->makeEnrolledAgent();

        $response = $this->withHeaders(['X-Agent-Secret' => $agent->agent_api_key])
            ->postJson('/api/agent/events', [
                'agent_uuid'     => $agent->agent_uuid,
                'event_type'     => 'file_encrypted_extension',
                'path'           => '/home/user/Documents/rapport.locked',
                'file_extension' => 'locked',
            ]);

        $response->assertStatus(200);

        $analysis = $response->json('analysis');
        $this->assertSame('critical', $analysis['risk_level']);
        $this->assertDatabaseHas('incidents', ['agent_id' => $agent->id]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 5 — Clé invalide → 401
    // ──────────────────────────────────────────────────────────────────────────

    public function test_invalid_api_key_is_rejected(): void
    {
        $agent = $this->makeEnrolledAgent();

        $response = $this->withHeaders(['X-Agent-Secret' => 'wrong-key'])
            ->postJson('/api/agent/events', [
                'agent_uuid' => $agent->agent_uuid,
                'event_type' => 'file_created',
                'path'       => '/home/user/test.txt',
            ]);

        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ──────────────────────────────────────────────────────────────────────────

    private function makePendingAgent(): Agent
    {
        return Agent::create([
            'agent_uuid'                  => (string) Str::uuid(),
            'agent_name'                  => 'pending-test-agent',
            'hostname'                    => 'test-pc',
            'ip_address'                  => '10.0.0.10',
            'host_role'                   => 'client',
            'status'                      => 'active',
            'enrollment_status'           => 'pending',
            'enrollment_token'            => 'test-token-e2e',
            'enrollment_token_expires_at' => now()->addHours(48),
            'risk_level'                  => 'normal',
            'risk_score'                  => 0,
            'is_isolated'                 => false,
        ]);
    }

    private function makeEnrolledAgent(): Agent
    {
        $agent = Agent::create([
            'agent_uuid'        => (string) Str::uuid(),
            'agent_name'        => 'enrolled-test-agent',
            'hostname'          => 'test-pc',
            'ip_address'        => '10.0.0.20',
            'host_role'         => 'client',
            'status'            => 'active',
            'enrollment_status' => 'enrolled',
            'risk_level'        => 'normal',
            'risk_score'        => 0,
            'is_isolated'       => false,
        ]);

        $agent->agent_api_key = Str::random(64);
        $agent->save();

        return $agent;
    }

    private function seedMinimalConfig(): void
    {
        $thresholds = [
            ['code' => 'threshold_normal',   'name' => 'Normal',   'risk_level' => 'normal',   'min_score' => 0,  'max_score' => 24,   'is_enabled' => true],
            ['code' => 'threshold_suspect',  'name' => 'Suspect',  'risk_level' => 'suspect',  'min_score' => 25, 'max_score' => 49,   'is_enabled' => true],
            ['code' => 'threshold_high',     'name' => 'High',     'risk_level' => 'high',     'min_score' => 50, 'max_score' => 79,   'is_enabled' => true],
            ['code' => 'threshold_critical', 'name' => 'Critical', 'risk_level' => 'critical', 'min_score' => 80, 'max_score' => null, 'is_enabled' => true],
        ];

        foreach ($thresholds as $t) {
            DetectionThreshold::updateOrCreate(
                ['code' => $t['code']],
                array_merge($t, ['key' => $t['code'], 'label' => $t['name'], 'value' => $t['min_score']])
            );
        }

        $rules = [
            [
                'code' => 'rule_mass_rename', 'name' => 'Mass rename',
                'event_type' => null, 'risk_level' => 'high', 'score_weight' => 55,
                'conditions' => [
                    'event_types'   => ['file_moved', 'file_renamed', 'moved', 'renamed', 'file_encrypted_extension', 'mass_rename_detected'],
                    'path_excludes' => ['browser_or_system'],
                ],
            ],
            [
                'code' => 'rule_ransom_note', 'name' => 'Ransom note',
                'event_type' => null, 'risk_level' => 'critical', 'score_weight' => 90,
                'conditions' => [
                    'filename_keywords' => ['readme', 'decrypt', 'recover', 'how_to_decrypt', 'ransom', 'restore_files', 'instructions'],
                ],
            ],
            [
                'code' => 'rule_fast_write_activity', 'name' => 'Fast write activity',
                'event_type' => null, 'risk_level' => 'suspect', 'score_weight' => 30,
                'conditions' => ['event_types' => ['file_modified', 'modified'], 'path_excludes' => ['browser_or_system']],
            ],
            [
                'code' => 'rule_simulation_marker', 'name' => 'Simulation marker',
                'event_type' => null, 'risk_level' => 'suspect', 'score_weight' => 20,
                'conditions' => ['require_simulation_flag' => true],
            ],
            [
                'code' => 'rule_suspicious_process', 'name' => 'Suspicious process',
                'event_type' => 'suspicious_process_detected', 'risk_level' => 'suspect', 'score_weight' => 35,
                'conditions' => [],
            ],
        ];

        foreach ($rules as $r) {
            DetectionRule::updateOrCreate(['code' => $r['code']], array_merge($r, ['is_enabled' => true]));
        }

        $extensions = [
            ['extension' => 'locked',    'risk_level' => 'critical', 'score_weight' => 80],
            ['extension' => 'encrypted', 'risk_level' => 'critical', 'score_weight' => 80],
            ['extension' => 'crypt',     'risk_level' => 'critical', 'score_weight' => 75],
        ];

        foreach ($extensions as $e) {
            SensitiveExtension::updateOrCreate(
                ['extension' => $e['extension']],
                array_merge($e, ['category' => 'suspicious', 'is_enabled' => true])
            );
        }

        $settings = [
            ['key' => 'min_risk_level_for_incident',                  'value' => 'high', 'value_type' => 'string'],
            ['key' => 'min_risk_level_for_action',                    'value' => 'high', 'value_type' => 'string'],
            ['key' => 'protection_execution_enabled',                 'value' => '1',    'value_type' => 'boolean'],
            ['key' => 'notification_ui_enabled',                      'value' => '1',    'value_type' => 'boolean'],
            ['key' => 'notification_sound_enabled',                   'value' => '1',    'value_type' => 'boolean'],
            ['key' => 'enable_real_isolation',                        'value' => '0',    'value_type' => 'boolean'],
            ['key' => 'enable_real_process_kill',                     'value' => '0',    'value_type' => 'boolean'],
            ['key' => 'require_human_approval_for_sensitive_actions', 'value' => '1',    'value_type' => 'boolean'],
        ];

        foreach ($settings as $s) {
            SystemSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
