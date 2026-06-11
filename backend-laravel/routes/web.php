<?php

use App\Http\Controllers\Api\AgentBootstrapController;
use App\Http\Controllers\Platform\AgentController;
use App\Http\Controllers\Platform\AlertController;
use App\Http\Controllers\Platform\AppearanceController;
use App\Http\Controllers\Platform\ApprovalQueueController;
use App\Http\Controllers\Platform\Auth\LoginController;
use App\Http\Controllers\Platform\Auth\TwoFactorController;
use App\Http\Controllers\Platform\DashboardController;
use App\Http\Controllers\Platform\DetectionRuleController;
use App\Http\Controllers\Platform\DetectionThresholdController;
use App\Http\Controllers\Platform\DiscoveredHostController;
use App\Http\Controllers\Platform\HomeController;
use App\Http\Controllers\Platform\IncidentController;
use App\Http\Controllers\Platform\IncidentTimelineController;
use App\Http\Controllers\Platform\LocalHostController;
use App\Http\Controllers\Platform\ManagedNetworkController;
use App\Http\Controllers\Platform\ProtectionActionController;
use App\Http\Controllers\Platform\ProtectionPolicyController;
use App\Http\Controllers\Platform\SensitiveExtensionController;
use App\Http\Controllers\Platform\SimulationController;
use App\Http\Controllers\Platform\SystemSettingController;
use App\Http\Controllers\Platform\UserController;
use Illuminate\Support\Facades\Route;

// ── URL courte d'enrôlement : /e/{8chars} — copier-coller-free pour KVM ─────
// Exemple : curl http://10.20.0.1:8080/e/c075615a | sudo bash
Route::get('/e/{code}', [AgentBootstrapController::class, 'scriptByShortCode'])
    ->name('agent.enroll.short')
    ->where('code', '[a-zA-Z0-9]{8}');

Route::get('/login', [LoginController::class, 'showForm'])->name('platform.login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('platform.login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('platform.logout')->middleware('auth');

// ── Challenge 2FA (après password OK, avant session complète) ────────────────
Route::get('/2fa/challenge',  [TwoFactorController::class, 'showChallenge'])->name('platform.2fa.challenge');
Route::post('/2fa/challenge', [TwoFactorController::class, 'verifyChallenge'])->name('platform.2fa.verify')->middleware('throttle:two-factor');

Route::get('/', HomeController::class)->name('platform.home');

Route::prefix('console')->name('platform.')->middleware('auth')->group(function () {

    // ════════════════════════════════════════════════════════════════════════
    //  Routes accessibles à TOUS les utilisateurs authentifiés
    //  (admin + analyst)
    // ════════════════════════════════════════════════════════════════════════

    Route::post('/appearance/theme', [AppearanceController::class, 'updateTheme'])->name('appearance.theme');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');

    // Hôte local — lecture seule pour analyst
    Route::get('/local-host', [LocalHostController::class, 'index'])->name('local-host.index');

    // Réseaux — lecture seule pour analyst
    Route::get('/networks', [ManagedNetworkController::class, 'index'])->name('networks.index');

    // Hôtes découverts — lecture seule pour analyst
    Route::get('/discovered-hosts', [DiscoveredHostController::class, 'index'])->name('discovered-hosts.index');

    // Agents — lecture + commandes opérationnelles pour analyst
    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');
    Route::post('/agents/{agent}/command', [AgentController::class, 'sendCommand'])->name('agents.send-command');
    Route::post('/agents/{agent}/regenerate-token', [AgentController::class, 'regenerateToken'])->name('agents.regenerate-token');

    // Alertes — lecture + actions opérationnelles pour analyst
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::patch('/alerts/{alert}/false-positive', [AlertController::class, 'falsePositive'])->name('alerts.false-positive');
    Route::patch('/alerts/{alert}/reopen', [AlertController::class, 'reopen'])->name('alerts.reopen');

    // Incidents — lecture + actions opérationnelles pour analyst
    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::patch('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::patch('/incidents/{incident}/false-positive', [IncidentController::class, 'falsePositive'])->name('incidents.false-positive');
    Route::patch('/incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
    Route::get('/incidents/{incident}/timeline', IncidentTimelineController::class)->name('incidents.timeline');

    // Actions de protection — lecture + approbation/rejet/exécution pour analyst
    Route::get('/protection-actions', [ProtectionActionController::class, 'index'])->name('protection-actions.index');
    Route::get('/protection-actions/{protectionAction}', [ProtectionActionController::class, 'show'])->name('protection-actions.show');
    Route::get('/protection-actions/{protectionAction}/status', [ProtectionActionController::class, 'status'])->name('protection-actions.status');
    Route::patch('/protection-actions/{protectionAction}/approve', [ProtectionActionController::class, 'approve'])->name('protection-actions.approve');
    Route::patch('/protection-actions/{protectionAction}/reject', [ProtectionActionController::class, 'reject'])->name('protection-actions.reject');
    Route::patch('/protection-actions/{protectionAction}/execute', [ProtectionActionController::class, 'execute'])->name('protection-actions.execute');
    Route::patch('/protection-actions/{protectionAction}/rollback', [ProtectionActionController::class, 'rollback'])->name('protection-actions.rollback');

    // File d'approbation
    Route::get('/approval-queue', ApprovalQueueController::class)->name('approval-queue.index');

    // Configuration — lecture seule pour analyst
    Route::resource('/detection-rules', DetectionRuleController::class)->only(['index']);
    Route::resource('/detection-thresholds', DetectionThresholdController::class)->only(['index']);
    Route::resource('/protection-policies', ProtectionPolicyController::class)->only(['index']);
    Route::resource('/system-settings', SystemSettingController::class)->only(['index']);
    Route::resource('/sensitive-extensions', SensitiveExtensionController::class)->only(['index']);
    Route::get('/configuration', \App\Http\Controllers\Platform\ConfigurationCenterController::class)->name('configuration.index');

    // Événements
    Route::get('/events', [\App\Http\Controllers\Platform\EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\Platform\EventController::class, 'show'])->name('events.show');

    // Notifications
    Route::get('/notifications/poll', \App\Http\Controllers\Platform\NotificationPollController::class)->name('notifications.poll');

    // Simulateur — lecture seule pour analyst (voir les scenarios, pas lancer)
    Route::get('/simulation', [SimulationController::class, 'index'])->name('simulation.index');

    // ── Gestion du profil utilisateur (admin ou soi-même — géré par UserPolicy)
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.update-password');
    Route::get('/profile', fn () => redirect()->route('platform.users.edit', auth()->user()))->name('profile');

    // ── Configuration 2FA (profil) ───────────────────────────────────────────
    Route::get('/two-factor',         [TwoFactorController::class, 'showSetup'])->name('two-factor.setup');
    Route::post('/two-factor/enable', [TwoFactorController::class, 'enable'])->name('two-factor.enable');
    Route::post('/two-factor/disable',[TwoFactorController::class, 'disable'])->name('two-factor.disable');

    // ════════════════════════════════════════════════════════════════════════
    //  Routes réservées aux ADMINISTRATEURS uniquement
    // ════════════════════════════════════════════════════════════════════════

    Route::middleware('role:admin')->group(function () {

        // Hôte local — actions sensibles
        Route::post('/local-host/detect', [LocalHostController::class, 'detect'])->name('local-host.detect');
        Route::post('/local-host/push-to-networks', [LocalHostController::class, 'pushToNetworks'])->name('local-host.push-to-networks');

        // Réseaux — gestion complète
        Route::post('/networks', [ManagedNetworkController::class, 'store'])->name('networks.store');
        Route::post('/networks/detect', [ManagedNetworkController::class, 'detect'])->name('networks.detect');
        Route::post('/networks/{managedNetwork}/scan', [ManagedNetworkController::class, 'scan'])->name('networks.scan');
        Route::patch('/networks/{managedNetwork}/approve', [ManagedNetworkController::class, 'approve'])->name('networks.approve');
        Route::patch('/networks/{managedNetwork}/retire', [ManagedNetworkController::class, 'retire'])->name('networks.retire');
        Route::patch('/networks/{managedNetwork}/restore', [ManagedNetworkController::class, 'restore'])->name('networks.restore');

        // Hôtes découverts — gestion complète
        Route::patch('/discovered-hosts/{discoveredHost}/validate', [DiscoveredHostController::class, 'validateHost'])->name('discovered-hosts.validate');
        Route::patch('/discovered-hosts/{discoveredHost}/reset', [DiscoveredHostController::class, 'reset'])->name('discovered-hosts.reset');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-role', [DiscoveredHostController::class, 'markAs'])->name('discovered-hosts.mark-role');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-client', [DiscoveredHostController::class, 'markClient'])->name('discovered-hosts.mark-client');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-mobile', [DiscoveredHostController::class, 'markMobile'])->name('discovered-hosts.mark-mobile');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-file-server', [DiscoveredHostController::class, 'markFileServer'])->name('discovered-hosts.mark-file-server');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-soc-server', [DiscoveredHostController::class, 'markSocServer'])->name('discovered-hosts.mark-soc-server');
        Route::patch('/discovered-hosts/{discoveredHost}/mark-attacker-demo', [DiscoveredHostController::class, 'markAttackerDemo'])->name('discovered-hosts.mark-attacker-demo');
        Route::patch('/discovered-hosts/{discoveredHost}/retire', [DiscoveredHostController::class, 'retire'])->name('discovered-hosts.retire');
        Route::patch('/discovered-hosts/{discoveredHost}/restore', [DiscoveredHostController::class, 'restore'])->name('discovered-hosts.restore');
        Route::post('/discovered-hosts/{discoveredHost}/enroll', [DiscoveredHostController::class, 'enroll'])->name('discovered-hosts.enroll');
        Route::delete('/discovered-hosts/purge-retired', [DiscoveredHostController::class, 'purgeRetired'])->name('discovered-hosts.purge-retired');

        // Agents — actions destructives
        Route::patch('/agents/{agent}/unenroll', [AgentController::class, 'unenroll'])->name('agents.unenroll');
        Route::delete('/agents/{agent}', [AgentController::class, 'destroy'])->name('agents.destroy');

        // Actions de protection — suppression
        Route::delete('/protection-actions/{protectionAction}', [ProtectionActionController::class, 'destroy'])->name('protection-actions.destroy');

        // Configuration — écriture
        Route::resource('/detection-rules', DetectionRuleController::class)->only(['update']);
        Route::resource('/detection-thresholds', DetectionThresholdController::class)->only(['update']);
        Route::resource('/protection-policies', ProtectionPolicyController::class)->only(['update']);
        Route::resource('/system-settings', SystemSettingController::class)->only(['update']);
        Route::patch('/system-settings/{systemSetting}/reset', [SystemSettingController::class, 'resetOne'])->name('system-settings.reset-one');
        Route::patch('/system-settings/{systemSetting}/toggle', [SystemSettingController::class, 'toggle'])->name('system-settings.toggle');
        Route::patch('/system-settings/{systemSetting}/set-value', [SystemSettingController::class, 'setValue'])->name('system-settings.set-value');
        Route::resource('/sensitive-extensions', SensitiveExtensionController::class)->only(['store', 'update', 'destroy']);
        Route::post('/configuration/reset-defaults', \App\Http\Controllers\Platform\ConfigurationResetController::class)->name('configuration.reset-defaults');

        // Simulateur — lancement
        Route::post('/simulation/run', [SimulationController::class, 'run'])->name('simulation.run');

        // Gestion des utilisateurs
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
