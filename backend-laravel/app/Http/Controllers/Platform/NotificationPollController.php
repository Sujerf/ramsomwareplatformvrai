<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AlertNotification;
use Illuminate\Http\JsonResponse;

class NotificationPollController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pending = AlertNotification::whereIn('channel', ['ui', 'sound'])
            ->where('status', 'pending')
            ->with('alert:id,risk_level,score,detected_at')
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json(['notifications' => [], 'play_sound' => false]);
        }

        $pending->each(fn ($n) => $n->update(['status' => 'sent', 'sent_at' => now()]));

        $uiNotifs = $pending
            ->where('channel', 'ui')
            ->map(fn ($n) => [
                'id'         => $n->id,
                'subject'    => $n->subject,
                'message'    => $n->message,
                'risk_level' => $n->metadata['risk_level'] ?? 'normal',
                'score'      => $n->metadata['score'] ?? 0,
            ])
            ->values();

        $maxRisk  = $pending->where('channel', 'sound')->sortByDesc(
            fn ($n) => ['normal' => 0, 'suspect' => 1, 'high' => 2, 'critical' => 3][$n->message] ?? 0
        )->first()?->message ?? null;

        return response()->json([
            'notifications' => $uiNotifs,
            'play_sound'    => $pending->where('channel', 'sound')->isNotEmpty(),
            'sound_level'   => $maxRisk,
        ]);
    }
}
