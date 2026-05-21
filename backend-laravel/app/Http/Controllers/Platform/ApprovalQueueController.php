<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionAction;
use Illuminate\Contracts\View\View;

class ApprovalQueueController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.approval-queue.index', [
            'actions' => ProtectionAction::with(['agent', 'incident', 'protectionPolicy'])
                ->where('decision_mode', 'approval_required')
                ->where('approval_status', 'pending')
                ->latest()
                ->paginate(25),
        ]);
    }
}
