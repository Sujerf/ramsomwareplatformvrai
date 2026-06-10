<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class ApprovalQueueController extends Controller
{
    public function __invoke(): View
    {
        $isSqlite  = DB::connection()->getDriverName() === 'sqlite';
        $riskExpr  = $isSqlite
            ? "COALESCE(json_extract(payload, '$.risk_level'), 'normal')"
            : "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.risk_level')), 'normal')";

        $actions = ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
            ->where('approval_status', 'pending')
            ->orderByRaw("CASE {$riskExpr}
                WHEN 'critical' THEN 1
                WHEN 'high'     THEN 2
                WHEN 'suspect'  THEN 3
                ELSE 4 END ASC")
            ->latest('proposed_at')
            ->latest()
            ->paginate(20);

        $byRisk = ProtectionAction::where('approval_status', 'pending')
            ->selectRaw("{$riskExpr} as risk_level, COUNT(*) as total")
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
