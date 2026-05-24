<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProtectionPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProtectionPolicyController extends Controller
{
    public function index(): View
    {
        $all = ProtectionPolicy::query()->orderBy('code')->get();

        // Groupement par niveau, ordre du plus sévère au plus léger
        $levelOrder = ['critical' => 0, 'high' => 1, 'suspect' => 2, 'normal' => 3];
        $groups = $all
            ->sortBy(fn ($p) => $levelOrder[$p->risk_level] ?? 99)
            ->groupBy('risk_level');

        return view('platform.protection-policies.index', [
            'groups'   => $groups,   // Collection groupée par risk_level
            'policies' => $all,      // Collection plate pour les stats
        ]);
    }

    public function update(Request $request, ProtectionPolicy $protectionPolicy): RedirectResponse
    {
        $validated = $request->validate([
            'risk_level'     => ['required', 'in:normal,suspect,high,critical'],
            // manual_only ajouté — critical_manual_process_kill utilise cette valeur
            'execution_mode' => ['required', 'in:automatic,approval_required,manual,manual_only'],
            'is_enabled'     => ['required', 'boolean'],
        ]);

        DB::table('protection_policies')
            ->where('id', $protectionPolicy->id)
            ->update([
                'risk_level' => $validated['risk_level'],
                'execution_mode' => $validated['execution_mode'],
                'is_enabled' => (bool) $validated['is_enabled'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Politique enregistrée dans la base.');
    }
}
