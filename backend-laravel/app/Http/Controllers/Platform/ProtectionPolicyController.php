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
        return view('platform.protection-policies.index', [
            'policies' => ProtectionPolicy::query()
                ->orderBy('risk_level')
                ->orderBy('code')
                ->paginate(25),
        ]);
    }

    public function update(Request $request, ProtectionPolicy $protectionPolicy): RedirectResponse
    {
        $validated = $request->validate([
            'risk_level' => ['required', 'in:normal,suspect,high,critical'],
            'execution_mode' => ['required', 'in:automatic,approval_required,manual'],
            'is_enabled' => ['required', 'boolean'],
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
