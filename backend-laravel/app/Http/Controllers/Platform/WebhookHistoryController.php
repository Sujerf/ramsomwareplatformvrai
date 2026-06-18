<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AlertNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class WebhookHistoryController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $status = $request->query('status');
        $type   = $request->query('type');
        $search = $request->query('search');

        $query = AlertNotification::where('channel', 'webhook')
            ->with(['alert', 'incident'])
            ->latest();

        if ($status && in_array($status, ['sent', 'failed', 'pending'])) {
            $query->where('status', $status);
        }

        if ($type && in_array($type, ['slack', 'teams', 'generic'])) {
            $query->whereJsonContains('metadata->webhook_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', '%'.$search.'%')
                  ->orWhere('recipient', 'like', '%'.$search.'%');
            });
        }

        $notifications = $query->paginate(30)->withQueryString();

        $stats = [
            'total'   => AlertNotification::where('channel', 'webhook')->count(),
            'sent'    => AlertNotification::where('channel', 'webhook')->where('status', 'sent')->count(),
            'failed'  => AlertNotification::where('channel', 'webhook')->where('status', 'failed')->count(),
            'pending' => AlertNotification::where('channel', 'webhook')->where('status', 'pending')->count(),
            'tests'   => AlertNotification::where('channel', 'webhook')->whereJsonContains('metadata->is_test', true)->count(),
        ];

        return view('platform.webhook-history.index', [
            'notifications' => $notifications,
            'stats'         => $stats,
            'filters'       => compact('status', 'type', 'search'),
        ]);
    }
}
