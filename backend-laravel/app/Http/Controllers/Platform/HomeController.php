<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Alert;
use App\Models\Incident;
use App\Models\ProtectionAction;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.home', [
            'agentsCount' => Agent::count(),
            'openAlertsCount' => Alert::whereIn('status', ['open', 'acknowledged', 'investigating'])->count(),
            'openIncidentsCount' => Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count(),
            'pendingActionsCount' => ProtectionAction::where('approval_status', 'pending')->count(),
        ]);
    }
}
