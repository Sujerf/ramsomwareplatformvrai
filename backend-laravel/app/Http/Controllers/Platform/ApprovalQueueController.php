<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionAction;
use Illuminate\Contracts\View\View;

class ApprovalQueueController extends Controller
{
    public function __invoke(): View
    {
        $actions = ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
            ->where('approval_status', 'pending')
            // Tri : critical → high → suspect → normal, puis par ancienneté décroissante
            ->orderByRaw("CASE COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.risk_level')), 'normal')
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'suspect'  THEN 3
                ELSE 4 END ASC")
            ->latest('proposed_at')
            ->latest()
            ->paginate(20);

        $byRisk = ProtectionAction::where('approval_status', 'pending')
            ->selectRaw("COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.risk_level')), 'normal') as risk_level, COUNT(*) as total")
            ->groupBy('risk_level')
            ->pluck('total', 'risk_level')
            ->toArray();

        $stats = [
            'total'    => array_sum($byRisk),
            'critical' => $byRisk['critical'] ?? 0,
            'high'     => $byRisk['high']     ?? 0,
            'suspect'  => $byRisk['suspect']  ?? 0,
            'normal'   => $byRisk['normal']   ?? 0,
            'urgent'   => ($byRisk['critical'] ?? 0) + ($byRisk['high'] ?? 0),
        ];

        return view('platform.approval-queue.index', [
            'actions' => $actions,
            'stats'   => $stats,
        ]);
    }
}
