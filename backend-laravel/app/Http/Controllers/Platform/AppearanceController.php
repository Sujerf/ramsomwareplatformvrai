<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AppearanceController extends Controller
{
    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:soc_dark,soc_light,cyber_blue,oled_black'],
        ]);

        SystemSetting::updateOrCreate(
            ['key' => 'ui_theme'],
            [
                'value' => $validated['theme'],
                'value_type' => 'string',
                'group' => 'ui',
                'label' => 'Thème interface',
                'description' => 'Thème visuel principal de la console SOC.',
            ]
        );

        return back()->with('success', 'Thème interface mis à jour.');
    }
}
