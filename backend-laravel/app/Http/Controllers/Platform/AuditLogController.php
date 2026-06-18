<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $channel  = $request->query('channel', 'all');
        $action   = $request->query('action');
        $userId   = $request->query('user_id');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');
        $q        = $request->query('q');

        $query = AuditLog::with('user')->latest('created_at');

        if ($channel !== 'all') {
            $query->where('channel', $channel);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('action', 'like', "%{$q}%")
                   ->orWhere('user_email', 'like', "%{$q}%")
                   ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = [
            'total'   => AuditLog::count(),
            'today'   => AuditLog::whereDate('created_at', today())->count(),
            'users'   => AuditLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'actions' => AuditLog::distinct('action')->count('action'),
        ];

        $actionList = AuditLog::distinct('action')->orderBy('action')->pluck('action');
        $userList   = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('platform.audit-log.index', compact(
            'logs', 'stats', 'actionList', 'userList',
            'channel', 'action', 'userId', 'dateFrom', 'dateTo', 'q'
        ));
    }
}
