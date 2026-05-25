<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\RansomShieldDefaultConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature — Synchronisation des paramètres système
 *
 * Couverture :
 *   1. syncSystemSettings() couvre les 13 clés attendues
 *   2. Valeurs par défaut correctes (booléens sécurisés, ui_theme)
 *   3. ui_theme = 'soc_dark' (pas 'light' — valeur incorrecte pré-correction)
 *   4. Toutes les clés consommées par les services sont présentes
 *   5. metadata.default_value présent sur chaque entrée (requis par resetOne)
 *   6. resetAll() retourne bien 13 pour system_settings
 *   7. Idempotence : appeler syncSystemSettings() deux fois ne duplique pas
 */
class SystemSettingsSyncTest extends TestCase
{
    use RefreshDatabase;

    private const EXPECTED_KEYS = [
        // Protection
        'protection_execution_enabled',
        'enable_real_isolation',
        'enable_real_process_kill',
        'require_human_approval_for_sensitive_actions',
        'safe_copy_root',
        // Détection
        'min_risk_level_for_incident',
        'min_risk_level_for_action',
        // Notifications
        'notification_ui_enabled',
        'notification_sound_enabled',
        'notification_mail_enabled',
        'notification_mail_recipient',
        'notification_min_risk_level',
        // UI
        'ui_theme',
    ];

    /** Clés lues par ProtectionDecisionService */
    private const PROTECTION_SERVICE_KEYS = [
        'protection_execution_enabled',
        'enable_real_isolation',
        'enable_real_process_kill',
        'require_human_approval_for_sensitive_actions',
    ];

    /** Clés lues par NotificationService */
    private const NOTIFICATION_SERVICE_KEYS = [
        'notification_ui_enabled',
        'notification_sound_enabled',
        'notification_mail_enabled',
        'notification_mail_recipient',
        'notification_min_risk_level',
    ];

    /** Clés lues par DynamicDetectionEngineService */
    private const DETECTION_SERVICE_KEYS = [
        'protection_execution_enabled',
        'enable_real_isolation',
        'enable_real_process_kill',
        'require_human_approval_for_sensitive_actions',
        'min_risk_level_for_incident',
        'min_risk_level_for_action',
        'notification_ui_enabled',
        'notification_sound_enabled',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    //  1. syncSystemSettings() couvre les 13 clés
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_covers_all_13_expected_keys(): void
    {
        $svc    = app(RansomShieldDefaultConfigurationService::class);
        $result = $svc->syncSystemSettings();

        $this->assertSame(13, $result,
            'syncSystemSettings() doit retourner 13 (nombre de paramètres synchronisés)');

        foreach (self::EXPECTED_KEYS as $key) {
            $this->assertTrue(
                \DB::table('system_settings')->where('key', $key)->exists(),
                "La clé '{$key}' doit être présente en base après syncSystemSettings()"
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  2. Valeurs par défaut sécurisées (exécution réelle OFF par défaut)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_dangerous_settings_default_to_off(): void
    {
        $svc = app(RansomShieldDefaultConfigurationService::class);
        $svc->syncSystemSettings();

        foreach (['enable_real_isolation', 'enable_real_process_kill'] as $key) {
            $value = SystemSetting::where('key', $key)->value('value');
            $this->assertSame('0', $value,
                "'{$key}' doit être désactivé par défaut pour sécurité");
        }

        // Approbation humaine activée par défaut
        $approval = SystemSetting::where('key', 'require_human_approval_for_sensitive_actions')->value('value');
        $this->assertSame('1', $approval,
            'require_human_approval_for_sensitive_actions doit être activé par défaut');

        // Moteur de protection actif par défaut
        $engine = SystemSetting::where('key', 'protection_execution_enabled')->value('value');
        $this->assertSame('1', $engine,
            'protection_execution_enabled doit être activé par défaut');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  3. ui_theme = 'soc_dark' (pas 'light')
    // ──────────────────────────────────────────────────────────────────────────

    public function test_ui_theme_default_is_soc_dark(): void
    {
        $svc = app(RansomShieldDefaultConfigurationService::class);
        $svc->syncSystemSettings();

        $theme = SystemSetting::where('key', 'ui_theme')->value('value');

        $this->assertSame('soc_dark', $theme,
            "ui_theme doit être 'soc_dark' (pas 'light' qui est un thème inexistant)");
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  4. Toutes les clés consommées par les services sont présentes
    // ──────────────────────────────────────────────────────────────────────────

    public function test_all_service_keys_are_covered(): void
    {
        $svc = app(RansomShieldDefaultConfigurationService::class);
        $svc->syncSystemSettings();

        $allServiceKeys = array_unique(array_merge(
            self::PROTECTION_SERVICE_KEYS,
            self::NOTIFICATION_SERVICE_KEYS,
            self::DETECTION_SERVICE_KEYS
        ));

        foreach ($allServiceKeys as $key) {
            $this->assertTrue(
                \DB::table('system_settings')->where('key', $key)->exists(),
                "La clé '{$key}' utilisée par un service doit être dans syncSystemSettings()"
            );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  5. metadata.default_value présent sur chaque entrée
    // ──────────────────────────────────────────────────────────────────────────

    public function test_all_settings_have_default_value_in_metadata(): void
    {
        $svc = app(RansomShieldDefaultConfigurationService::class);
        $svc->syncSystemSettings();

        $settings = SystemSetting::whereIn('key', self::EXPECTED_KEYS)->get();

        foreach ($settings as $setting) {
            $meta = $setting->metadata ?? [];
            $this->assertArrayHasKey('default_value', $meta,
                "Le paramètre '{$setting->key}' doit avoir metadata.default_value (requis par resetOne)");
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  6. resetAll() retourne 13 pour system_settings
    // ──────────────────────────────────────────────────────────────────────────

    public function test_reset_all_returns_correct_counts(): void
    {
        $svc    = app(RansomShieldDefaultConfigurationService::class);
        $result = $svc->resetAll();

        $this->assertSame(13, $result['system_settings'],
            'resetAll()[system_settings] doit retourner 13');
        $this->assertArrayHasKey('sensitive_extensions', $result);
        $this->assertArrayHasKey('detection_rules', $result);
        $this->assertArrayHasKey('detection_thresholds', $result);
        $this->assertArrayHasKey('protection_policies', $result);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  7. Idempotence — appeler syncSystemSettings() deux fois ne duplique pas
    // ──────────────────────────────────────────────────────────────────────────

    public function test_sync_is_idempotent(): void
    {
        $svc = app(RansomShieldDefaultConfigurationService::class);
        $svc->syncSystemSettings();
        $svc->syncSystemSettings();

        $count = SystemSetting::whereIn('key', self::EXPECTED_KEYS)->count();

        $this->assertSame(13, $count,
            'syncSystemSettings() est idempotent — pas de doublons après deux appels');
    }
}
