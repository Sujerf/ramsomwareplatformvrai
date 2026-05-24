<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\DetectionRule;
use App\Models\DetectionThreshold;
use App\Models\SensitiveExtension;
use App\Models\SystemSetting;
use App\Services\DynamicDetectionEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests Feature — Pipeline de détection RansomShield
 *
 * Ces 5 tests couvrent les chemins critiques :
 *   1. Régression bug N — file_created ne doit pas scorer
 *   2. Extension chiffrée → critical
 *   3. Note de rançon → critical
 *   4. Sécurité API — un événement sans clé est refusé
 *   5. Sécurité enrôlement — un agent inconnu est refusé
 */
class DetectionPipelineTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────────────────
    //  SETUP
    // ──────────────────────────────────────────────────────────────────────────

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedMinimalConfig();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 1 — Régression bug N
    //  Avant la correction : file_created scorait 30 (rule_fast_write_activity)
    //  → chaque fichier créé par un IDE déclenchait une alerte "suspect"
    // ──────────────────────────────────────────────────────────────────────────

    public function test_file_created_does_not_score(): void
    {
        $engine = app(DynamicDetectionEngineService::class);

        $result = $engine->analyze([
            'event_type' => 'file_created',
            'path'       => '/home/user/document.py',
        ]);

        $this->assertSame(0, $result['score'],
            'file_created ne doit plus être capturé par rule_fast_write_activity (bug N)');
        $this->assertSame('normal', $result['risk_level']);
        $this->assertFalse($result['should_create_alert']);
        $this->assertEmpty($result['signals']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 2 — Extension chiffrée → critical
    //  file_encrypted_extension + extension .locked = 80 (ext) + 55 (mass_rename) = 135
    // ──────────────────────────────────────────────────────────────────────────

    public function test_encrypted_extension_triggers_critical_alert(): void
    {
        $engine = app(DynamicDetectionEngineService::class);

        $result = $engine->analyze([
            'event_type'     => 'file_encrypted_extension',
            'path'           => '/home/user/rapport.locked',
            'file_extension' => 'locked',
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'],
            'Une extension chiffrée doit dépasser le seuil critical (80)');
        $this->assertSame('critical', $result['risk_level']);
        $this->assertTrue($result['should_create_alert']);

        $signalCodes = array_column($result['signals'], 'code');
        $this->assertContains('sensitive_extension_locked', $signalCodes,
            'Le signal sensitive_extension_locked doit être présent');
        $this->assertContains('rule_mass_rename', $signalCodes,
            'Le signal rule_mass_rename doit être présent sur file_encrypted_extension');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 3 — Note de rançon → critical
    //  rule_ransom_note score 90 → dépasse le seuil critical (80)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_ransom_note_path_triggers_critical(): void
    {
        $engine = app(DynamicDetectionEngineService::class);

        $result = $engine->analyze([
            'event_type' => 'file_created',
            'path'       => '/home/user/README-DECRYPT.txt',
        ]);

        $this->assertGreaterThanOrEqual(80, $result['score'],
            'Une note de rançon doit dépasser le seuil critical (80)');
        $this->assertSame('critical', $result['risk_level']);

        $signalCodes = array_column($result['signals'], 'code');
        $this->assertContains('rule_ransom_note', $signalCodes);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 4 — Sécurité API : événement sans clé est refusé
    //  POST /api/agent/events exige X-Agent-Secret valide (middleware agent.secret)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_agent_event_api_rejects_missing_secret(): void
    {
        $response = $this->postJson('/api/agent/events', [
            'agent_uuid' => (string) Str::uuid(),
            'event_type' => 'file_modified',
            'path'       => '/home/user/test.txt',
        ]);

        // Middleware agent.secret renvoie 401 si aucun secret fourni
        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Unauthorized.']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  TEST 5 — Sécurité enrôlement : agent inconnu refusé
    //  Un UUID qui n'existe pas dans la table agents doit recevoir 401
    // ──────────────────────────────────────────────────────────────────────────

    public function test_enrollment_rejects_unknown_agent(): void
    {
        $response = $this->postJson('/api/agent/enroll', [
            'agent_name'       => 'intruder',
            'agent_uuid'       => (string) Str::uuid(),
            'enrollment_token' => 'fake-token-xyz',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error',
            'No pre-authorized agent found. Pre-enroll this host from the SOC console first.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  HELPERS — Données minimales pour les tests
    // ──────────────────────────────────────────────────────────────────────────

    private function seedMinimalConfig(): void
    {
        // NOTE : certaines migrations de données insèrent déjà des lignes en base
        // (ex: fix_detection_rules_dedup_and_add_suspicious_process).
        // On utilise updateOrCreate / firstOrCreate pour éviter les conflits UNIQUE.

        // ── Seuils de détection ───────────────────────────────────────────────
        $thresholds = [
            ['code' => 'threshold_normal',   'name' => 'Normal',   'risk_level' => 'normal',   'min_score' => 0,  'max_score' => 24,   'is_enabled' => true],
            ['code' => 'threshold_suspect',  'name' => 'Suspect',  'risk_level' => 'suspect',  'min_score' => 25, 'max_score' => 49,   'is_enabled' => true],
            ['code' => 'threshold_high',     'name' => 'High',     'risk_level' => 'high',     'min_score' => 50, 'max_score' => 79,   'is_enabled' => true],
            ['code' => 'threshold_critical', 'name' => 'Critical', 'risk_level' => 'critical', 'min_score' => 80, 'max_score' => null, 'is_enabled' => true],
        ];

        foreach ($thresholds as $t) {
            DetectionThreshold::updateOrCreate(
                ['code' => $t['code']],
                array_merge($t, [
                    'key'   => $t['code'],
                    'label' => $t['name'],
                    'value' => $t['min_score'],
                ])
            );
        }

        // ── Règles de détection ───────────────────────────────────────────────
        $rules = [
            ['code' => 'rule_mass_rename',        'name' => 'Mass rename',         'event_type' => null,                         'risk_level' => 'high',     'score_weight' => 55],
            ['code' => 'rule_ransom_note',         'name' => 'Ransom note',         'event_type' => null,                         'risk_level' => 'critical', 'score_weight' => 90],
            ['code' => 'rule_fast_write_activity', 'name' => 'Fast write activity', 'event_type' => 'file_modified',              'risk_level' => 'suspect',  'score_weight' => 30],
            ['code' => 'rule_simulation_marker',   'name' => 'Simulation marker',   'event_type' => null,                         'risk_level' => 'suspect',  'score_weight' => 20],
            ['code' => 'rule_suspicious_process',  'name' => 'Suspicious process',  'event_type' => 'suspicious_process_detected', 'risk_level' => 'suspect',  'score_weight' => 35],
        ];

        foreach ($rules as $r) {
            DetectionRule::updateOrCreate(
                ['code' => $r['code']],
                array_merge($r, ['is_enabled' => true])
            );
        }

        // ── Extensions sensibles ──────────────────────────────────────────────
        $extensions = [
            ['extension' => 'locked',    'risk_level' => 'critical', 'score_weight' => 80],
            ['extension' => 'encrypted', 'risk_level' => 'critical', 'score_weight' => 80],
            ['extension' => 'crypt',     'risk_level' => 'critical', 'score_weight' => 75],
        ];

        foreach ($extensions as $e) {
            SensitiveExtension::updateOrCreate(
                ['extension' => $e['extension']],
                array_merge($e, [
                    'category'   => 'suspicious',
                    'is_enabled' => true,
                ])
            );
        }

        // ── Paramètres système ────────────────────────────────────────────────
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
