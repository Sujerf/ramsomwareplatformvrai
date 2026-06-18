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
        $all = DetectionRule::query()
            ->orderByDesc('score_weight')
            ->orderBy('code')
            ->get();

        return view('platform.detection-rules.index', [
            'active'   => $all->where('is_enabled', true)->values(),
            'inactive' => $all->where('is_enabled', false)->values(),
            'maxScore' => max(1, (int) $all->max('score_weight')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code'         => ['required', 'string', 'max:80', 'unique:detection_rules,code',
                               'regex:/^[a-z0-9_]+$/'],
            'name'         => ['required', 'string', 'max:120'],
            'event_type'   => ['nullable', 'string', 'max:60'],
            'risk_level'   => ['required', 'in:normal,suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_enabled'   => ['required', 'boolean'],
            'description'  => ['nullable', 'string', 'max:500'],
            'conditions'   => ['nullable', 'string'],
        ]);

        $conditions = null;
        if (! empty($validated['conditions'])) {
            $decoded = json_decode($validated['conditions'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['conditions' => 'JSON invalide : '.json_last_error_msg()])
                             ->withInput();
            }
            $conditions = $decoded;
        }

        DetectionRule::create([
            'code'         => $validated['code'],
            'name'         => $validated['name'],
            'event_type'   => $validated['event_type'] ?? null,
            'risk_level'   => $validated['risk_level'],
            'score_weight' => (int) $validated['score_weight'],
            'is_enabled'   => (bool) $validated['is_enabled'],
            'description'  => $validated['description'] ?? null,
            'conditions'   => $conditions,
        ]);

        return back()->with('success', 'Règle « '.$validated['name'].' » créée.');
    }

    public function update(Request $request, DetectionRule $detectionRule): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'event_type'   => ['nullable', 'string', 'max:60'],
            'risk_level'   => ['required', 'in:normal,suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_enabled'   => ['required', 'boolean'],
            'description'  => ['nullable', 'string', 'max:500'],
            'conditions'   => ['nullable', 'string'],
        ]);

        $conditions = $detectionRule->conditions;
        if (array_key_exists('conditions', $validated)) {
            if (empty($validated['conditions'])) {
                $conditions = null;
            } else {
                $decoded = json_decode($validated['conditions'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return back()->withErrors(['conditions' => 'JSON invalide : '.json_last_error_msg()])
                                 ->withInput();
                }
                $conditions = $decoded;
            }
        }

        DB::table('detection_rules')
            ->where('id', $detectionRule->id)
            ->update([
                'name'         => $validated['name'],
                'event_type'   => $validated['event_type'] ?? null,
                'risk_level'   => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled'   => (bool) $validated['is_enabled'],
                'description'  => $validated['description'] ?? null,
                'conditions'   => $conditions !== null ? json_encode($conditions) : null,
                'updated_at'   => now(),
            ]);

        return back()->with('success', 'Règle « '.$validated['name'].' » enregistrée.');
    }

    public function destroy(DetectionRule $detectionRule): RedirectResponse
    {
        $name = $detectionRule->name;
        $detectionRule->delete();

        return back()->with('success', 'Règle « '.$name.' » supprimée.');
    }
}
