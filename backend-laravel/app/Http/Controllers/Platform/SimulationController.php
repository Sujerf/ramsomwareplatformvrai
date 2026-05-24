<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Services\SimulationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SimulationController extends Controller
{
    public function __construct(
        private readonly SimulationService $simulationService,
    ) {}

    /**
     * Page principale : liste des agents + scénarios disponibles.
     */
    public function index(): View
    {
        $agents = Agent::where('enrollment_status', 'enrolled')
            ->latest('last_seen_at')
            ->get(['id', 'agent_name', 'hostname', 'ip_address', 'risk_level', 'last_seen_at', 'metadata']);

        return view('platform.simulation.index', [
            'agents'    => $agents,
            'scenarios' => SimulationService::scenarios(),
        ]);
    }

    /**
     * Lance un scénario via AJAX (POST).
     * Retourne un JSON avec le résumé de la simulation.
     */
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'integer', 'exists:agents,id'],
            'scenario' => ['required', 'string', 'in:' . implode(',', array_keys(SimulationService::scenarios()))],
        ]);

        $agent = Agent::findOrFail($validated['agent_id']);

        if ($agent->enrollment_status !== 'enrolled') {
            return response()->json([
                'error' => 'L\'agent doit être enrôlé pour lancer une simulation.',
            ], 422);
        }

        try {
            $result = $this->simulationService->run($agent, $validated['scenario']);

            return response()->json([
                'success' => true,
                'result'  => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Simulation failed', [
                'agent_id' => $agent->id,
                'scenario' => $validated['scenario'],
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la simulation : ' . $e->getMessage(),
            ], 500);
        }
    }
}
