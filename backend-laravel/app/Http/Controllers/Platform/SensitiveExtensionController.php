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
        return view('platform.sensitive-extensions.index', [
            'extensions' => SensitiveExtension::query()
                ->orderBy('risk_level')
                ->orderBy('extension')
                ->paginate(25),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'extension' => ['required', 'string', 'max:50'],
            'risk_level' => ['required', 'in:suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $extension = ltrim(strtolower(trim($validated['extension'])), '.');

        DB::table('sensitive_extensions')->updateOrInsert(
            ['extension' => $extension],
            [
                'extension' => $extension,
                'risk_level' => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled' => true,
                ‘description’ => "Extension ajoutee depuis l’interface.",
                'metadata' => json_encode([
                    'source' => 'ui',
                    'linked_to' => ['rule_sensitive_extension'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return back()->with('success', 'Extension sensible enregistrée dans la base.');
    }

    public function update(Request $request, SensitiveExtension $sensitiveExtension): RedirectResponse
    {
        $validated = $request->validate([
            'risk_level' => ['required', 'in:suspect,high,critical'],
            'score_weight' => ['required', 'integer', 'min:0', 'max:1000'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        DB::table('sensitive_extensions')
            ->where('id', $sensitiveExtension->id)
            ->update([
                'risk_level' => $validated['risk_level'],
                'score_weight' => (int) $validated['score_weight'],
                'is_enabled' => (bool) $validated['is_enabled'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Extension sensible mise à jour dans la base.');
    }

    public function destroy(SensitiveExtension $sensitiveExtension): RedirectResponse
    {
        DB::table('sensitive_extensions')
            ->where('id', $sensitiveExtension->id)
            ->delete();

        return back()->with('success', 'Extension sensible supprimée.');
    }
}
