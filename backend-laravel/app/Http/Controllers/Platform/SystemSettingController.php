<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index(Request $request): View
    {
        $group = $request->query('group');

        $query = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key');

        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();

        $groups = SystemSetting::query()
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->filter()
            ->values();

        return view('platform.system-settings.index', [
            'settings' => $settings,
            'groups' => $groups,
            'activeGroup' => $group,
            'stats' => [
                'total' => SystemSetting::count(),
                'protection' => SystemSetting::where('group', 'protection')->count(),
                'detection' => SystemSetting::where('group', 'detection')->count(),
                'notifications' => SystemSetting::where('group', 'notifications')->count(),
                'ui' => SystemSetting::where('group', 'ui')->count(),
                'boolean' => SystemSetting::where('value_type', 'boolean')->count(),
            ],
        ]);
    }

    public function update(Request $request, SystemSetting $systemSetting): RedirectResponse
    {
        $validated = $request->validate([
            'value' => ['nullable'],
        ]);

        $value = $validated['value'] ?? null;

        $normalized = $this->normalizeValue($systemSetting, $value);

        $systemSetting->update([
            'value' => $normalized,
        ]);

        return back()->with('success', 'Paramètre enregistré : '.$systemSetting->key);
    }

    public function resetOne(SystemSetting $systemSetting): RedirectResponse
    {
        $metadata = $systemSetting->metadata ?? [];
        $defaultValue = $metadata['default_value'] ?? null;

        if ($defaultValue === null) {
            return back()->withErrors([
                'setting' => 'Aucune valeur par défaut enregistrée pour ce paramètre.',
            ]);
        }

        $systemSetting->update([
            'value' => $this->normalizeValue($systemSetting, $defaultValue),
        ]);

        return back()->with('success', 'Paramètre restauré : '.$systemSetting->key);
    }

    private function normalizeValue(SystemSetting $setting, mixed $value): string
    {
        $type = $setting->value_type ?? 'string';

        if ($type === 'boolean') {
            return in_array((string) $value, ['1', 'true', 'on', 'yes'], true) ? '1' : '0';
        }

        if ($type === 'integer') {
            return (string) max(0, (int) $value);
        }

        if ($type === 'json') {
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $decoded = json_decode((string) $value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }

            return '{}';
        }

        return (string) $value;
    }
}
