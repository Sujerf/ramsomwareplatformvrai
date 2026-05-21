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
        return view('platform.detection-thresholds.index', [
            'thresholds' => DetectionThreshold::query()
                ->orderBy('min_score')
                ->paginate(25),
        ]);
    }

    public function update(Request $request, DetectionThreshold $detectionThreshold): RedirectResponse
    {
        $validated = $request->validate([
            'min_score' => ['required', 'integer', 'min:0', 'max:1000'],
            'max_score' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_enabled' => ['required', 'boolean'],
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
                'min_score' => (int) $validated['min_score'],
                'max_score' => $validated['max_score'] === null || $validated['max_score'] === ''
                    ? null
                    : (int) $validated['max_score'],
                'value' => (int) $validated['min_score'],
                'is_enabled' => (bool) $validated['is_enabled'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Seuil enregistré dans la base.');
    }
}
