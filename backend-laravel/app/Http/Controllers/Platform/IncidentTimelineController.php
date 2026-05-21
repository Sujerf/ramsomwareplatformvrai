<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class IncidentTimelineController extends Controller
{
    public function __invoke(Incident $incident): View
    {
        $incident->load([
            'agent',
            'events',
            'alerts',
            'notifications',
            'protectionActions.decisions',
        ]);

        $timeline = collect()
            ->merge($incident->events->map(fn ($event) => [
                'type' => 'event',
                'date' => $event->observed_at ?? $event->created_at,
                'title' => $event->event_type,
                'description' => $event->path,
                'risk_level' => $event->risk_level,
            ]))
            ->merge($incident->alerts->map(fn ($alert) => [
                'type' => 'alert',
                'date' => $alert->detected_at ?? $alert->created_at,
                'title' => $alert->title,
                'description' => $alert->message,
                'risk_level' => $alert->risk_level,
            ]))
            ->merge($incident->notifications->map(fn ($notification) => [
                'type' => 'notification',
                'date' => $notification->sent_at ?? $notification->created_at,
                'title' => $notification->channel . ' — ' . $notification->status,
                'description' => $notification->message,
                'risk_level' => null,
            ]))
            ->merge($incident->protectionActions->map(fn ($action) => [
                'type' => 'protection_action',
                'date' => $action->proposed_at ?? $action->created_at,
                'title' => $action->action_type . ' — ' . $action->approval_status,
                'description' => $action->description,
                'risk_level' => null,
            ]))
            ->sortBy('date')
            ->values();

        return view('platform.incidents.timeline', [
            'incident' => $incident,
            'timeline' => $timeline,
        ]);
    }
}
