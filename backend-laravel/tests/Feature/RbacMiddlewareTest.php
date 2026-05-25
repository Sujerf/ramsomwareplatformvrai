<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests Feature — RBAC middleware (EnsureRole)
 *
 * Couverture :
 *   1.  Analyst accède au dashboard (route ouverte)
 *   2.  Analyst accède aux incidents (route ouverte)
 *   3.  Analyst accède aux alertes (route ouverte)
 *   4.  Analyst accède à la liste des protection-actions (route ouverte)
 *   5.  Analyst accède à la configuration (lecture seule)
 *   6.  Analyst accède à la simulation (lecture seule)
 *   7.  Analyst bloqué sur POST simulation/run (admin only)
 *   8.  Analyst bloqué sur POST detection-rules (admin only)
 *   9.  Analyst bloqué sur POST protection-policies (admin only)
 *   10. Analyst bloqué sur POST system-settings update (admin only)
 *   11. Analyst bloqué sur POST networks (admin only)
 *   12. Analyst bloqué sur POST configuration/reset-defaults (admin only)
 *   13. Analyst bloqué sur DELETE protection-actions (admin only)
 *   14. Admin accède à toutes les routes admin
 *   15. Non authentifié redirigé vers login sur route protégée
 *   16. Analyst peut accéder à son propre profil
 *   17. Analyst bloqué sur la liste des utilisateurs
 */
class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = []): User
    {
        return User::factory()->admin()->create($attrs);
    }

    private function analyst(array $attrs = []): User
    {
        return User::factory()->analyst()->create($attrs);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Routes ouvertes (admin + analyst)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_analyst_can_access_dashboard(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    public function test_analyst_can_access_incidents_index(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.incidents.index'))
            ->assertOk();
    }

    public function test_analyst_can_access_alerts_index(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.alerts.index'))
            ->assertOk();
    }

    public function test_analyst_can_access_protection_actions_index(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.protection-actions.index'))
            ->assertOk();
    }

    public function test_analyst_can_access_configuration_read(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.configuration.index'))
            ->assertOk();
    }

    public function test_analyst_can_view_simulation_page(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.simulation.index'))
            ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Routes admin-only — analyst bloqué (403)
    // ──────────────────────────────────────────────────────────────────────────

    public function test_analyst_cannot_run_simulation(): void
    {
        $this->actingAs($this->analyst())
            ->post(route('platform.simulation.run'), ['scenario' => 'basic'])
            ->assertForbidden();
    }

    public function test_analyst_cannot_update_detection_rule(): void
    {
        // PATCH sur un ID inexistant → middleware role:admin s'exécute avant le model binding
        $this->actingAs($this->analyst())
            ->patch(route('platform.detection-rules.update', 99999), [
                'score_weight' => 99,
            ])
            ->assertForbidden();
    }

    public function test_analyst_cannot_update_protection_policy(): void
    {
        $this->actingAs($this->analyst())
            ->patch(route('platform.protection-policies.update', 99999), [
                'risk_level' => 'critical',
            ])
            ->assertForbidden();
    }

    public function test_analyst_cannot_update_system_settings(): void
    {
        // Seul l'administrateur peut modifier les paramètres système
        // On utilise la route POST /sensitive-extensions (store) qui est admin-only
        $this->actingAs($this->analyst())
            ->post(route('platform.sensitive-extensions.store'), [
                'extension'    => 'test',
                'risk_level'   => 'high',
                'score_weight' => 50,
                'category'     => 'suspicious',
            ])
            ->assertForbidden();
    }

    public function test_analyst_cannot_create_network(): void
    {
        $this->actingAs($this->analyst())
            ->post(route('platform.networks.store'), [
                'name'       => 'LAN Test',
                'cidr'       => '192.168.1.0/24',
                'gateway_ip' => '192.168.1.1',
            ])
            ->assertForbidden();
    }

    public function test_analyst_cannot_reset_configuration(): void
    {
        $this->actingAs($this->analyst())
            ->post(route('platform.configuration.reset-defaults'))
            ->assertForbidden();
    }

    public function test_analyst_cannot_delete_protection_action(): void
    {
        // L'ID 99999 n'existe pas, mais le middleware doit bloquer avant le 404
        $this->actingAs($this->analyst())
            ->delete(route('platform.protection-actions.destroy', 99999))
            ->assertForbidden();
    }

    public function test_analyst_cannot_unenroll_agent(): void
    {
        $this->actingAs($this->analyst())
            ->patch(route('platform.agents.unenroll', 99999))
            ->assertForbidden();
    }

    public function test_analyst_cannot_delete_agent(): void
    {
        $this->actingAs($this->analyst())
            ->delete(route('platform.agents.destroy', 99999))
            ->assertForbidden();
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Admin accède aux routes admin
    // ──────────────────────────────────────────────────────────────────────────

    public function test_admin_can_access_admin_only_routes(): void
    {
        $admin = $this->admin();

        // POST simulation/run → 422 (validation) ou succès — en tout cas pas 403
        $resp1 = $this->actingAs($admin)
            ->post(route('platform.simulation.run'), []);
        $this->assertNotEquals(403, $resp1->status(),
            'Un admin ne doit pas recevoir 403 sur POST simulation/run');

        // PATCH detection-rules update → 404 (ID inexistant) ou redirect — pas 403
        $resp2 = $this->actingAs($admin)
            ->patch(route('platform.detection-rules.update', 99999), []);
        $this->assertNotEquals(403, $resp2->status(),
            'Un admin ne doit pas recevoir 403 sur PATCH detection-rules');

        // POST configuration/reset-defaults → redirect ou 200 — pas 403
        $resp3 = $this->actingAs($admin)
            ->post(route('platform.configuration.reset-defaults'));
        $this->assertNotEquals(403, $resp3->status(),
            'Un admin ne doit pas recevoir 403 sur POST configuration/reset-defaults');
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Visiteur non authentifié
    // ──────────────────────────────────────────────────────────────────────────

    public function test_unauthenticated_redirected_on_admin_route(): void
    {
        $this->post(route('platform.simulation.run'))
            ->assertRedirect(route('platform.login'));
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Profil utilisateur — analyst accède à son propre profil
    // ──────────────────────────────────────────────────────────────────────────

    public function test_analyst_can_access_own_profile(): void
    {
        $analyst = $this->analyst();

        $this->actingAs($analyst)
            ->get(route('platform.users.edit', $analyst))
            ->assertOk();
    }

    public function test_analyst_cannot_access_users_list(): void
    {
        $this->actingAs($this->analyst())
            ->get(route('platform.users.index'))
            ->assertForbidden();
    }
}
