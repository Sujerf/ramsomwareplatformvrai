<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionAction;
use App\Models\ProtectionActionDecision;
use App\Services\SocStatusSynchronizerService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProtectionActionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');

        $query = ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
            ->latest('proposed_at')
            ->latest();

        if ($status === 'active') {
            $query->where('approval_status', 'pending')
                ->whereIn('execution_status', ['waiting_approval', 'pending', 'executing']);
        } elseif ($status === 'approved') {
            $query->where('approval_status', 'approved')
                ->whereIn('execution_status', ['pending', 'executing']);
        } elseif ($status === 'executed') {
            $query->whereIn('execution_status', ['executed', 'success']);
        } elseif ($status === 'rejected') {
            $query->whereIn('approval_status', ['rejected', 'cancelled']);
        } elseif ($status === 'rollback') {
            $query->where('execution_status', 'rolled_back');
        }

        return view('platform.protection-actions.index', [
            'actions'      => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'stats'        => [
                'total'    => ProtectionAction::count(),
                'pending'  => ProtectionAction::where('approval_status', 'pending')->count(),
                'approved' => ProtectionAction::where('approval_status', 'approved')
                                ->whereIn('execution_status', ['pending', 'executing'])->count(),
                'executed' => ProtectionAction::whereIn('execution_status', ['executed', 'success'])->count(),
                'rejected' => ProtectionAction::whereIn('approval_status', ['rejected', 'cancelled'])->count(),
            ],
        ]);
    }

    public function show(ProtectionAction $protectionAction): View
    {
        $protectionAction->load([
            'agent',
            'incident',
            'protectionPolicy',
            'decisions.user',
        ]);

        return view('platform.protection-actions.show', [
            'protectionAction' => $protectionAction,
            'action' => $protectionAction,
        ]);
    }

    /**
     * Endpoint JSON léger pour le polling AJAX du statut.
     * Utilisé par la vue show pour rafraîchir le badge sans recharger la page.
     */
    public function status(ProtectionAction $protectionAction): JsonResponse
    {
        return response()->json([
            'id'               => $protectionAction->id,
            'execution_status' => $protectionAction->execution_status,
            'approval_status'  => $protectionAction->approval_status,
            'executed_at'      => $protectionAction->executed_at?->toDateTimeString(),
            'payload'          => $protectionAction->payload,
        ]);
    }

    public function approve(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'approval_status'  => 'approved',
                'execution_status' => $protectionAction->execution_status === 'waiting_approval'
                    ? 'pending'
                    : $protectionAction->execution_status,
            ]);

            $this->recordDecision($protectionAction, 'approved', "Action approuvée depuis la console SOC.");
        });

        $sync->syncAfterAction($protectionAction);

        if ($request->expectsJson()) {
            return response()->json(['decision' => 'approved', 'id' => $protectionAction->id]);
        }

        return back()->with('success', "Action approuvée.");
    }

    public function reject(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse|JsonResponse
    {
        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'approval_status'  => 'rejected',
                'execution_status' => 'failed',
            ]);

            $this->recordDecision($protectionAction, 'rejected', "Action rejetée depuis la console SOC.");
        });

        $sync->syncAfterAction($protectionAction);

        if ($request->expectsJson()) {
            return response()->json(['decision' => 'rejected', 'id' => $protectionAction->id]);
        }

        return back()->with('success', "Action rejetée et retirée des actions actives.");
    }

    public function execute(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse
    {
        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'approval_status' => $protectionAction->approval_status === 'pending'
                    ? 'approved'
                    : $protectionAction->approval_status,
                'execution_status' => 'executed',
                'executed_at' => now(),
                'rollback_available' => (bool) $protectionAction->is_reversible,
            ]);

            $this->recordDecision($protectionAction, 'executed', 'Exécution contrôlée depuis la console SOC.');
        });

        $sync->syncAfterAction($protectionAction);

        return back()->with('success', 'Action exécutée et statut synchronisé.');
    }

    public function executeManually(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse
    {
        return $this->execute($request, $protectionAction, $sync);
    }

    public function rollback(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse
    {
        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'execution_status' => 'rolled_back',
                'rollback_available' => false,
                'rolled_back_at' => now(),
            ]);

            $this->recordDecision($protectionAction, 'rollback', 'Rollback enregistré depuis la console SOC.');
        });

        $sync->syncAfterAction($protectionAction);

        return back()->with('success', 'Rollback enregistré et statut synchronisé.');
    }

    public function destroy(ProtectionAction $protectionAction): RedirectResponse
    {
        $protectionAction->decisions()->delete();
        $protectionAction->delete();

        return redirect()
            ->route('platform.protection-actions.index')
            ->with('success', 'Action de protection supprimée.');
    }

    private function recordDecision(ProtectionAction $protectionAction, string $decision, string $comment): void
    {
        ProtectionActionDecision::create([
            'protection_action_id' => $protectionAction->id,
            'user_id' => auth()->id(),
            'decision' => $decision,
            'comment' => $comment,
            'decided_at' => now(),
            'metadata' => [
                'source' => 'console_soc',
            ],
        ]);
    }
}
