<?php

use App\Http\Controllers\Api\AgentCommandController;
use App\Http\Controllers\Api\AgentEnrollmentController;
use App\Http\Controllers\Api\AgentEventController;
use App\Http\Controllers\Api\AgentHeartbeatController;
use Illuminate\Support\Facades\Route;

Route::prefix('agent')->name('api.agent.')->middleware('agent.secret')->group(function () {
    Route::post('/enroll', [AgentEnrollmentController::class, 'store'])->name('enroll');
    Route::post('/heartbeat', [AgentHeartbeatController::class, 'store'])->name('heartbeat');
    Route::post('/events', [AgentEventController::class, 'store'])->name('events.store');
    Route::get('/pending-commands', [AgentCommandController::class, 'pending'])->name('commands.pending');
    Route::post('/actions/{action}/result', [AgentCommandController::class, 'result'])->name('commands.result');
});
