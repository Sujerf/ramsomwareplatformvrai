<?php

use App\Http\Controllers\Api\AgentBootstrapController;
use App\Http\Controllers\Api\AgentCommandController;
use App\Http\Controllers\Api\AgentDownloadController;
use App\Http\Controllers\Api\AgentEnrollmentController;
use App\Http\Controllers\Api\AgentEventController;
use App\Http\Controllers\Api\AgentHeartbeatController;
use Illuminate\Support\Facades\Route;

// ── Routes publiques (aucun secret requis) ────────────────────────────────────

// Bootstrap : script auto-généré avec .env embarqué (UUID = identificateur one-time)
Route::get('/agent/bootstrap/{uuid}', [AgentBootstrapController::class, 'script'])
    ->name('api.agent.bootstrap');

// Téléchargement des fichiers statiques de l'agent (whitelist stricte)
Route::get('/agent/download/{file}', [AgentDownloadController::class, 'download'])
    ->name('api.agent.download')
    ->where('file', '[a-zA-Z0-9_.\-]+');

// ── Enrôlement (auth par enrollment_token, pas par agent_api_key) ────────────
//
// Cet endpoint a sa propre logique d'authentification interne :
//   - L'agent fournit son enrollment_token à usage unique
//   - En retour il reçoit son agent_api_key permanent
// Il n'appartient PAS au groupe agent.secret car la clé n'existe pas encore.
Route::post('/agent/enroll', [AgentEnrollmentController::class, 'store'])
    ->name('api.agent.enroll');

// ── Routes protégées — clé API per-agent (X-Agent-Secret) ────────────────────
//
// Chaque requête doit contenir :
//   - agent_uuid  (dans le corps JSON ou les query params)
//   - X-Agent-Secret  (en-tête HTTP = agent_api_key stocké dans le .env local)
//
// Le middleware ValiderAgentSecret valide la clé per-agent (fallback: secret global).
Route::prefix('agent')->name('api.agent.')->middleware('agent.secret')->group(function () {
    Route::post('/heartbeat',               [AgentHeartbeatController::class, 'store'])->name('heartbeat');
    Route::post('/events',                  [AgentEventController::class, 'store'])->name('events.store');
    Route::get('/pending-commands',         [AgentCommandController::class, 'pending'])->name('commands.pending');
    Route::post('/actions/{action}/result', [AgentCommandController::class, 'result'])->name('commands.result');
});
