<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\DetectionThreshold;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetectionThresholdController extends Controller
{
    public function index(): View
    {
        $all = DetectionThreshold::query()->orderBy('min_score')->get();

        // Seuils opérationnels — ceux que le moteur utilise réellement
        // (code préfixé 'threshold_' : threshold_normal/suspect/high/critical).
        // Seuils legacy — anciens seuils de comptage désactivés par Bug E,
        // conservés en base pour rollback possible mais non utilisés.
        $operational = $all->filter(fn ($t) => str_starts_with($t->code, 'threshold_'))->values();
        $legacy      = $all->filter(fn ($t) => ! str_starts_with($t->code, 'threshold_'))->values();

        return view('platform.detection-thresholds.index', compact('operational', 'legacy'));
    }

    public function update(Request $request, DetectionThreshold $detectionThreshold): RedirectResponse
    {
        $validated = $request->validate([
            'label'       => ['required', 'string', 'max:100'],
            'risk_level'  => ['required', 'in:normal,suspect,high,critical'],
            'min_score'   => ['required', 'integer', 'min:0', 'max:1000'],
            'max_score'   => ['nullable', 'integer', 'min:0', 'max:1000'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_enabled'  => ['required', 'boolean'],
        ]);

        if (
            $validated['max_score'] !== null
            && (int) $validated['max_score'] < (int) $validated['min_score']
        ) {
            return back()->withErrors([
                'max_score' => 'Le score maximum doit être supérieur ou égal au score minimum.',
            ]);
        }

        DB::table('detection_thresholds')
            ->where('id', $detectionThreshold->id)
            ->update([
                'label'       => $validated['label'],
                'name'        => $validated['label'],   // cohérence : name = label
                'risk_level'  => $validated['risk_level'],
                'min_score'   => (int) $validated['min_score'],
                'max_score'   => ($validated['max_score'] === null || $validated['max_score'] === '')
                    ? null
                    : (int) $validated['max_score'],
                'value'       => (int) $validated['min_score'],
                'description' => $validated['description'] ?? null,
                'is_enabled'  => (bool) $validated['is_enabled'],
                'updated_at'  => now(),
            ]);

        return back()->with('success', 'Seuil «'.$validated['label'].'» enregistré.');
    }
}
