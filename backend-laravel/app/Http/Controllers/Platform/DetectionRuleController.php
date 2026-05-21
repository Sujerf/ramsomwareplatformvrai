<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\DetectionRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetectionRuleController extends Controller
{
    public function index(): View
    {
        return view('platform.detection-rules.index', [
            'rules' => DetectionRule::query()
                ->orderByDesc('score_weight')
                ->orderBy('code')
                ->paginate(25),
        ]);
    }

    public function update(Request $request, DetectionRule $detectionRule): RedirectResponse
    {
        $validated = $request->validate([
            'risk_level' => ['required', 'in:normal,suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        DB::table('detection_rules')
            ->where('id', $detectionRule->id)
            ->update([
                'risk_level' => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled' => (bool) $validated['is_enabled'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Règle de détection enregistrée dans la base.');
    }
}
