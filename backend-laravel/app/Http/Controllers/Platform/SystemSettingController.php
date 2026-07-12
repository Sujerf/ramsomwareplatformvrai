<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    // Ces clés sont contrôlées exclusivement depuis le tableau de bord (surveillance)
    private const DASHBOARD_KEYS = [
        'notification_ui_enabled',
        'notification_sound_enabled',
        'notification_mail_enabled',
    ];

    public function index(Request $request): View
    {
        $group = $request->query('group');

        $query = SystemSetting::query()
            ->whereNotIn('key', self::DASHBOARD_KEYS)
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
        $old = (string) $systemSetting->value;

        $systemSetting->update([
            'value' => $normalized,
        ]);

        app(AuditLogService::class)->settingUpdated($systemSetting->key, $old, $normalized);

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

    public function toggle(SystemSetting $systemSetting): JsonResponse
    {
        if ($systemSetting->value_type !== 'boolean') {
            return response()->json(['error' => 'Not a boolean setting'], 422);
        }

        $newValue = $systemSetting->value === '1' ? '0' : '1';
        $systemSetting->update(['value' => $newValue]);

        return response()->json([
            'key' => $systemSetting->key,
            'value' => $newValue,
            'active' => $newValue === '1',
        ]);
    }

    public function testMail(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $recipient = trim((string) SystemSetting::getCached('notification_mail_recipient'));

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'error' => 'Adresse email destinataire invalide ou non configurée.'], 422);
        }

        $result = app(NotificationService::class)->sendTestMail($recipient);

        return response()->json($result, $result['success'] ? 200 : 502);
    }

    public function testWebhook(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $url  = trim((string) SystemSetting::getCached('notification_webhook_url'));
        $type = SystemSetting::getCached('notification_webhook_type') ?? 'slack';

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['success' => false, 'error' => 'URL webhook invalide ou non configurée.'], 422);
        }

        $result = app(NotificationService::class)->sendTestWebhook($url, $type);

        return response()->json($result, $result['success'] ? 200 : 502);
    }

    public function setValue(Request $request, SystemSetting $systemSetting): JsonResponse
    {
        $validated = $request->validate(['value' => ['required', 'string']]);
        $normalized = $this->normalizeValue($systemSetting, $validated['value']);
        $systemSetting->update(['value' => $normalized]);

        return response()->json([
            'key' => $systemSetting->key,
            'value' => $normalized,
        ]);
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
