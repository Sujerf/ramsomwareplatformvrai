<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionAction;
use App\Models\ProtectionActionDecision;
use App\Services\AuditLogService;
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
            // J8 fix — inclut waiting_approval : une action peut être approved
            // mais encore en attente d'exécution avec execution_status=waiting_approval.
            $query->where('approval_status', 'approved')
                ->whereIn('execution_status', ['waiting_approval', 'pending', 'executing']);
        } elseif ($status === 'executed') {
            // J6 fix — 'success' n'existe jamais en base, seul 'executed' est utilisé.
            $query->where('execution_status', 'executed');
        } elseif ($status === 'rejected') {
            $query->whereIn('approval_status', ['rejected', 'cancelled']);
        } elseif ($status === 'rollback') {
            $query->where('execution_status', 'rolled_back');
        }

        // Counts per tab for badge display
        $filterCounts = [
            'active'   => ProtectionAction::where('approval_status', 'pending')
                            ->whereIn('execution_status', ['waiting_approval', 'pending', 'executing'])->count(),
            'approved' => ProtectionAction::where('approval_status', 'approved')
                            ->whereIn('execution_status', ['waiting_approval', 'pending', 'executing'])->count(),
            'executed' => ProtectionAction::where('execution_status', 'executed')->count(),
            'rejected' => ProtectionAction::whereIn('approval_status', ['rejected', 'cancelled'])->count(),
            'rollback' => ProtectionAction::where('execution_status', 'rolled_back')->count(),
            'all'      => ProtectionAction::count(),
        ];

        return view('platform.protection-actions.index', [
            'actions'      => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'filterCounts' => $filterCounts,
            'stats'        => [
                'total'    => ProtectionAction::count(),
                'pending'  => ProtectionAction::where('approval_status', 'pending')->count(),
                'approved' => ProtectionAction::where('approval_status', 'approved')
                                ->whereIn('execution_status', ['waiting_approval', 'pending', 'executing'])->count(),
                'executed' => ProtectionAction::where('execution_status', 'executed')->count(),
                'rejected' => ProtectionAction::whereIn('approval_status', ['rejected', 'cancelled'])->count(),
                'rollback' => ProtectionAction::where('execution_status', 'rolled_back')->count(),
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
        if ($protectionAction->approval_status !== 'pending') {
            $msg = 'Cette action ne peut pas être approuvée (statut : '.$protectionAction->approval_status.').';
            return $request->expectsJson()
                ? response()->json(['error' => $msg], 422)
                : back()->with('error', $msg);
        }

        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'approval_status'  => 'approved',
                'execution_status' => $protectionAction->execution_status === 'waiting_approval'
                    ? 'pending'
                    : $protectionAction->execution_status,
            ]);

            $this->recordDecision($protectionAction, 'approved', "Action approuvée depuis la console SOC.");
        });

        app(AuditLogService::class)->protectionActionApproved(
            $protectionAction->id,
            $protectionAction->action_type,
            $protectionAction->incident_id
        );

        $sync->syncAfterAction($protectionAction);

        if ($request->expectsJson()) {
            return response()->json(['decision' => 'approved', 'id' => $protectionAction->id]);
        }

        return back()->with('success', "Action approuvée.");
    }

    public function reject(Request $request, ProtectionAction $protectionAction, SocStatusSynchronizerService $sync): RedirectResponse|JsonResponse
    {
        if (! in_array($protectionAction->approval_status, ['pending', 'approved'], true)) {
            $msg = 'Cette action ne peut plus être rejetée (statut : '.$protectionAction->approval_status.').';
            return $request->expectsJson()
                ? response()->json(['error' => $msg], 422)
                : back()->with('error', $msg);
        }

        DB::transaction(function () use ($protectionAction) {
            $protectionAction->update([
                'approval_status'  => 'rejected',
                'execution_status' => 'cancelled',
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
        if ($protectionAction->execution_status === 'executed') {
            return back()->with('error', 'Cette action a déjà été exécutée.');
        }

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

        app(AuditLogService::class)->protectionActionExecuted(
            $protectionAction->id,
            $protectionAction->action_type,
            true
        );

        $sync->syncAfterAction($protectionAction);

        return back()->with('success', 'Action exécutée et statut synchronisé.');
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

        app(AuditLogService::class)->protectionActionRolledBack(
            $protectionAction->id,
            $protectionAction->action_type
        );

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
