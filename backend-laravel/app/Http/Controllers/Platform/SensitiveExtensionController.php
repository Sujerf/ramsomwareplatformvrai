<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SensitiveExtension;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SensitiveExtensionController extends Controller
{
    public function index(): View
    {
        $all = SensitiveExtension::query()
            ->orderByDesc('score_weight')
            ->orderBy('extension')
            ->get();

        // Groupement par niveau, ordre du plus severe au plus leger
        $levelOrder = ['critical' => 0, 'high' => 1, 'suspect' => 2, 'normal' => 3];
        $groups = $all
            ->sortBy(fn ($e) => [$levelOrder[$e->risk_level] ?? 99, $e->extension])
            ->groupBy('risk_level');

        return view('platform.sensitive-extensions.index', [
            'groups'     => $groups,
            'extensions' => $all,
            'maxScore'   => max(1, (int) $all->max('score_weight')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'extension'    => ['required', 'string', 'max:50'],
            'risk_level'   => ['required', 'in:suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'description'  => ['nullable', 'string', 'max:300'],
        ]);

        $ext = ltrim(strtolower(trim($validated['extension'])), '.');

        DB::table('sensitive_extensions')->updateOrInsert(
            ['extension' => $ext],
            [
                'extension'    => $ext,
                'risk_level'   => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled'   => true,
                'description'  => $validated['description'] ?? null,
                'metadata'     => json_encode([
                    'source'    => 'ui',
                    'linked_to' => ['analyzeSensitiveExtension'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]
        );

        return back()->with('success', 'Extension .' . $ext . ' enregistree dans la base.');
    }

    public function update(Request $request, SensitiveExtension $sensitiveExtension): RedirectResponse
    {
        $validated = $request->validate([
            'risk_level'   => ['required', 'in:suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_enabled'   => ['required', 'boolean'],
        ]);

        DB::table('sensitive_extensions')
            ->where('id', $sensitiveExtension->id)
            ->update([
                'risk_level'   => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled'   => (bool) $validated['is_enabled'],
                'updated_at'   => now(),
            ]);

        return back()->with('success', 'Extension sensible mise a jour dans la base.');
    }

    public function destroy(SensitiveExtension $sensitiveExtension): RedirectResponse
    {
        DB::table('sensitive_extensions')
            ->where('id', $sensitiveExtension->id)
            ->delete();

        return back()->with('success', 'Extension sensible supprimee.');
    }
}
