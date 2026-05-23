<?php

use App\Http\Controllers\Api\AgentBootstrapController;
use App\Http\Controllers\Api\AgentCommandController;
use App\Http\Controllers\Api\AgentDownloadController;
use App\Http\Controllers\Api\AgentEnrollmentController;
use App\Http\Controllers\Api\AgentEventController;
use App\Http\Controllers\Api\AgentHeartbeatController;
use Illuminate\Support\Facades\Route;

// ── Routes publiques (pas de secret requis) ───────────────────────────────────
// Bootstrap : script auto-généré avec .env embarqué (UUID = identificateur)
Route::get('/agent/bootstrap/{uuid}', [AgentBootstrapController::class, 'script'])
    ->name('api.agent.bootstrap');

// Téléchargement des fichiers statiques de l'agent (whitelist stricte)
Route::get('/agent/download/{file}', [AgentDownloadController::class, 'download'])
    ->name('api.agent.download')
    ->where('file', '[a-zA-Z0-9_.\-]+');

// ── Routes protégées par X-Agent-Secret ──────────────────────────────────────
Route::prefix('agent')->name('api.agent.')->middleware('agent.secret')->group(function () {
    Route::post('/enroll', [AgentEnrollmentController::class, 'store'])->name('enroll');
    Route::post('/heartbeat', [AgentHeartbeatController::class, 'store'])->name('heartbeat');
    Route::post('/events', [AgentEventController::class, 'store'])->name('events.store');
    Route::get('/pending-commands', [AgentCommandController::class, 'pending'])->name('commands.pending');
    Route::post('/actions/{action}/result', [AgentCommandController::class, 'result'])->name('commands.result');
});
