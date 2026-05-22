<?php

use App\Http\Controllers\Platform\AgentController;
use App\Http\Controllers\Platform\AlertController;
use App\Http\Controllers\Platform\AppearanceController;
use App\Http\Controllers\Platform\ApprovalQueueController;
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
use App\Http\Controllers\Platform\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('platform.home');

Route::prefix('console')->name('platform.')->group(function () {
    Route::post('/appearance/theme', [AppearanceController::class, 'updateTheme'])->name('appearance.theme');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');

    Route::get('/local-host', [LocalHostController::class, 'index'])->name('local-host.index');
    Route::post('/local-host/detect', [LocalHostController::class, 'detect'])->name('local-host.detect');

    Route::get('/networks', [ManagedNetworkController::class, 'index'])->name('networks.index');
    Route::post('/networks', [ManagedNetworkController::class, 'store'])->name('networks.store');
    Route::post('/networks/detect', [ManagedNetworkController::class, 'detect'])->name('networks.detect');
    Route::post('/networks/{managedNetwork}/scan', [ManagedNetworkController::class, 'scan'])->name('networks.scan');
    Route::patch('/networks/{managedNetwork}/approve', [ManagedNetworkController::class, 'approve'])->name('networks.approve');
    Route::patch('/networks/{managedNetwork}/ignore', [ManagedNetworkController::class, 'ignore'])->name('networks.ignore');

    Route::get('/discovered-hosts', [DiscoveredHostController::class, 'index'])->name('discovered-hosts.index');
    Route::patch('/discovered-hosts/{discoveredHost}/validate', [DiscoveredHostController::class, 'validateHost'])->name('discovered-hosts.validate');
    Route::patch('/discovered-hosts/{discoveredHost}/reset', [DiscoveredHostController::class, 'reset'])->name('discovered-hosts.reset');
    Route::patch('/discovered-hosts/{discoveredHost}/mark-client', [DiscoveredHostController::class, 'markClient'])->name('discovered-hosts.mark-client');
    Route::patch('/discovered-hosts/{discoveredHost}/mark-file-server', [DiscoveredHostController::class, 'markFileServer'])->name('discovered-hosts.mark-file-server');
    Route::patch('/discovered-hosts/{discoveredHost}/mark-soc-server', [DiscoveredHostController::class, 'markSocServer'])->name('discovered-hosts.mark-soc-server');
    Route::patch('/discovered-hosts/{discoveredHost}/ignore', [DiscoveredHostController::class, 'ignore'])->name('discovered-hosts.ignore');
    Route::patch('/discovered-hosts/{discoveredHost}/mark-attacker-demo', [DiscoveredHostController::class, 'markAttackerDemo'])->name('discovered-hosts.mark-attacker-demo');

    Route::get('/agents', [AgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AgentController::class, 'show'])->name('agents.show');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::get('/alerts/{alert}', [AlertController::class, 'show'])->name('alerts.show');
    Route::patch('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');
    Route::patch('/alerts/{alert}/false-positive', [AlertController::class, 'falsePositive'])->name('alerts.false-positive');
    Route::patch('/alerts/{alert}/reopen', [AlertController::class, 'reopen'])->name('alerts.reopen');

    Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::patch('/incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
    Route::patch('/incidents/{incident}/false-positive', [IncidentController::class, 'falsePositive'])->name('incidents.false-positive');
    Route::patch('/incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');

    Route::get('/incidents/{incident}/timeline', IncidentTimelineController::class)->name('incidents.timeline');

    Route::get('/protection-actions', [ProtectionActionController::class, 'index'])->name('protection-actions.index');
    Route::get('/protection-actions/{protectionAction}', [ProtectionActionController::class, 'show'])->name('protection-actions.show');
    Route::patch('/protection-actions/{protectionAction}/approve', [ProtectionActionController::class, 'approve'])->name('protection-actions.approve');
    Route::patch('/protection-actions/{protectionAction}/reject', [ProtectionActionController::class, 'reject'])->name('protection-actions.reject');
    Route::patch('/protection-actions/{protectionAction}/execute', [ProtectionActionController::class, 'executeManually'])->name('protection-actions.execute');
    Route::patch('/protection-actions/{protectionAction}/rollback', [ProtectionActionController::class, 'rollback'])->name('protection-actions.rollback');
    Route::delete('/protection-actions/{protectionAction}', [ProtectionActionController::class, 'destroy'])->name('protection-actions.destroy');

    Route::get('/approval-queue', ApprovalQueueController::class)->name('approval-queue.index');

    Route::resource('/detection-rules', DetectionRuleController::class)->except(['create', 'show', 'edit', 'destroy']);
    Route::resource('/detection-thresholds', DetectionThresholdController::class)->except(['create', 'show', 'edit', 'destroy']);
    Route::resource('/protection-policies', ProtectionPolicyController::class)->except(['create', 'show', 'edit', 'destroy']);
    Route::resource('/system-settings', SystemSettingController::class)->only(['index', 'update']);
    Route::resource('/sensitive-extensions', SensitiveExtensionController::class)->except(['create', 'show', 'edit']);
    Route::get('/configuration', \App\Http\Controllers\Platform\ConfigurationCenterController::class)->name('configuration.index');
    Route::post('/configuration/reset-defaults', \App\Http\Controllers\Platform\ConfigurationResetController::class)->name('configuration.reset-defaults');
    Route::patch('/system-settings/{systemSetting}/reset', [SystemSettingController::class, 'resetOne'])->name('system-settings.reset-one');
    Route::patch('/system-settings/{systemSetting}/toggle', [SystemSettingController::class, 'toggle'])->name('system-settings.toggle');
    Route::patch('/system-settings/{systemSetting}/set-value', [SystemSettingController::class, 'setValue'])->name('system-settings.set-value');
    Route::patch('/networks/{managedNetwork}/retire', [ManagedNetworkController::class, 'retire'])->name('networks.retire');
    Route::patch('/networks/{managedNetwork}/restore', [ManagedNetworkController::class, 'restore'])->name('networks.restore');
    Route::patch('/discovered-hosts/{discoveredHost}/retire', [DiscoveredHostController::class, 'retire'])->name('discovered-hosts.retire');
    Route::patch('/discovered-hosts/{discoveredHost}/restore', [DiscoveredHostController::class, 'restore'])->name('discovered-hosts.restore');
    Route::post('/discovered-hosts/{discoveredHost}/enroll', [DiscoveredHostController::class, 'enroll'])->name('discovered-hosts.enroll');
    Route::get('/events', [\App\Http\Controllers\Platform\EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\Platform\EventController::class, 'show'])->name('events.show');
});
