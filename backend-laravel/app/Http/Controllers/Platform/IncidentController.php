<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Services\AuditLogService;
use App\Services\SocStatusSynchronizerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'active');
        $risk   = $request->query('risk');

        $query = Incident::with(['agent', 'attackProfile'])
            ->withCount('alerts')
            ->latest('detected_at')
            ->latest();

        if ($status === 'archived') {
            $query->archived();
        } else {
            $query->notArchived();
            if ($status === 'active') {
                $query->whereIn('status', ['open', 'investigating', 'under_review', 'reopened']);
            } elseif ($status === 'resolved') {
                $query->where('status', 'resolved');
            } elseif ($status === 'false_positive') {
                $query->where('status', 'false_positive');
            }
        }

        if ($risk && in_array($risk, ['normal', 'suspect', 'high', 'critical'], true)) {
            $query->where('risk_level', $risk);
        }

        $cntActive   = Incident::notArchived()->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count();
        $cntResolved = Incident::notArchived()->where('status', 'resolved')->count();
        $cntFalsePos = Incident::notArchived()->where('status', 'false_positive')->count();
        $cntArchived = Incident::archived()->count();
        $cntTotal    = Incident::notArchived()->count();

        $riskCounts = Incident::notArchived()
            ->whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
            ->selectRaw('risk_level, COUNT(*) as cnt')
            ->groupBy('risk_level')
            ->pluck('cnt', 'risk_level')
            ->toArray();

        return view('platform.incidents.index', [
            'incidents'    => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'activeRisk'   => $risk ?? '',
            'stats'        => [
                'active'         => $cntActive,
                'resolved'       => $cntResolved,
                'false_positive' => $cntFalsePos,
                'archived'       => $cntArchived,
                'critical'       => Incident::notArchived()->where('risk_level', 'critical')->count(),
                'high'           => Incident::notArchived()->where('risk_level', 'high')->count(),
                'total'          => $cntTotal,
            ],
            'filterCounts' => [
                'status' => [
                    'active'         => $cntActive,
                    'resolved'       => $cntResolved,
                    'false_positive' => $cntFalsePos,
                    'archived'       => $cntArchived,
                    'all'            => $cntTotal,
                ],
                'risk' => [
                    ''         => $cntActive,
                    'critical' => $riskCounts['critical'] ?? 0,
                    'high'     => $riskCounts['high']     ?? 0,
                    'suspect'  => $riskCounts['suspect']  ?? 0,
                    'normal'   => $riskCounts['normal']   ?? 0,
                ],
            ],
        ]);
    }

    public function archive(Incident $incident): RedirectResponse
    {
        $incident->update(['archived_at' => now()]);

        app(AuditLogService::class)->action(
            'incident.archived',
            "Incident archivé : {$incident->title}",
            ['incident_id' => $incident->id, 'status' => $incident->status]
        );

        return back()->with('success', 'Incident archivé.');
    }

    public function unarchive(Incident $incident): RedirectResponse
    {
        $incident->update(['archived_at' => null]);

        app(AuditLogService::class)->action(
            'incident.unarchived',
            "Incident désarchivé : {$incident->title}",
            ['incident_id' => $incident->id]
        );

        return back()->with('success', 'Incident restauré.');
    }

    public function purge(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $count = Incident::archived()->count();

        if ($count === 0) {
            return back()->with('success', 'Aucun incident archivé à supprimer.');
        }

        // Supprimer dans l'ordre pour respecter les FK (comments, notifs, etc.)
        $ids = Incident::archived()->pluck('id');
        \App\Models\IncidentComment::whereIn('incident_id', $ids)->delete();
        \App\Models\AlertNotification::whereIn('incident_id', $ids)->whereNotNull('incident_id')->delete();
        Incident::archived()->delete();

        app(AuditLogService::class)->action(
            'incident.purged',
            "{$count} incident(s) archivé(s) supprimés définitivement.",
            ['count' => $count]
        );

        return redirect()->route('platform.incidents.index', ['status' => 'active'])
            ->with('success', "{$count} incident(s) archivé(s) supprimés définitivement.");
    }

    public function show(Incident $incident): View
    {
        $incident->load([
            'agent',
            'attackProfile',
            'alerts.event',
            'events',
            'protectionActions.protectionPolicy',
            'protectionActions.decisions.user',
            'notifications',
            'comments.user',
        ]);

        return view('platform.incidents.show', [
            'incident' => $incident,
        ]);
    }

    public function export(Incident $incident, string $format): Response|StreamedResponse
    {
        $incident->load([
            'agent',
            'attackProfile',
            'alerts.event',
            'events',
            'protectionActions.protectionPolicy',
        ]);

        $slug = 'incident-'.$incident->id.'-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('platform.incidents.export-pdf', ['incident' => $incident])
                ->setPaper('a4', 'portrait');

            return $pdf->download("{$slug}.pdf");
        }

        // CSV
        $filename = "{$slug}.csv";

        return response()->streamDownload(function () use ($incident) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            // ── En-tête incident ─────────────────────────────────────────────
            fputcsv($out, ['=== INCIDENT ===']);
            fputcsv($out, ['ID', 'UUID', 'Titre', 'Statut', 'Niveau de risque', 'Score', 'Agent', 'IP agent', 'Détecté le', 'Résolu le']);
            fputcsv($out, [
                $incident->id,
                $incident->incident_uuid ?? '',
                $incident->title,
                $incident->status,
                $incident->risk_level,
                $incident->risk_score ?? 0,
                $incident->agent?->agent_name ?? '',
                $incident->agent?->ip_address ?? '',
                optional($incident->detected_at)->format('d/m/Y H:i:s') ?? '',
                optional($incident->resolved_at)->format('d/m/Y H:i:s') ?? '',
            ]);
            fputcsv($out, []);

            // ── Alertes ───────────────────────────────────────────────────────
            fputcsv($out, ['=== ALERTES ('.$incident->alerts->count().')  ===']);
            fputcsv($out, ['ID', 'Titre', 'Niveau de risque', 'Score', 'Statut', 'Détectée le']);
            foreach ($incident->alerts as $alert) {
                fputcsv($out, [
                    $alert->id,
                    $alert->title,
                    $alert->risk_level,
                    $alert->score ?? 0,
                    $alert->status,
                    optional($alert->detected_at)->format('d/m/Y H:i:s') ?? '',
                ]);
            }
            fputcsv($out, []);

            // ── Événements ────────────────────────────────────────────────────
            fputcsv($out, ['=== ÉVÉNEMENTS ('.$incident->events->count().')  ===']);
            fputcsv($out, ['ID', 'Type', 'Chemin', 'Extension', 'Niveau de risque', 'Score', 'Observé le']);
            foreach ($incident->events as $event) {
                fputcsv($out, [
                    $event->id,
                    $event->event_type,
                    $event->path ?? '',
                    $event->file_extension ?? '',
                    $event->risk_level,
                    $event->score ?? 0,
                    optional($event->observed_at)->format('d/m/Y H:i:s') ?? '',
                ]);
            }
            fputcsv($out, []);

            // ── Actions de protection ─────────────────────────────────────────
            fputcsv($out, ['=== ACTIONS DE PROTECTION ('.$incident->protectionActions->count().')  ===']);
            fputcsv($out, ['ID', 'Type', 'Politique', 'Statut', 'Approbation requise', 'Créée le']);
            foreach ($incident->protectionActions as $action) {
                fputcsv($out, [
                    $action->id,
                    $action->action_type,
                    $action->protectionPolicy?->name ?? '',
                    $action->status,
                    $action->human_approval_required ? 'Oui' : 'Non',
                    optional($action->created_at)->format('d/m/Y H:i:s') ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportList(Request $request): StreamedResponse
    {
        $status = $request->query('status', 'active');
        $risk   = $request->query('risk');

        $query = Incident::with('agent')->latest('detected_at');

        if ($status === 'active') {
            $query->whereIn('status', ['open', 'investigating', 'under_review', 'reopened']);
        } elseif ($status === 'resolved') {
            $query->where('status', 'resolved');
        } elseif ($status === 'false_positive') {
            $query->where('status', 'false_positive');
        }

        if ($risk && in_array($risk, ['normal', 'suspect', 'high', 'critical'], true)) {
            $query->where('risk_level', $risk);
        }

        $incidents = $query->limit(5000)->get();
        $filename  = 'incidents-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($incidents) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, ['ID', 'Titre', 'Statut', 'Niveau de risque', 'Score', 'Agent', 'IP agent', 'Détecté le', 'Résolu le']);

            foreach ($incidents as $incident) {
                fputcsv($out, [
                    $incident->id,
                    $incident->title,
                    $incident->status,
                    $incident->risk_level,
                    $incident->risk_score ?? 0,
                    $incident->agent?->agent_name ?? '',
                    $incident->agent?->ip_address ?? '',
                    optional($incident->detected_at)->format('d/m/Y H:i:s') ?? '',
                    optional($incident->resolved_at)->format('d/m/Y H:i:s') ?? '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function resolve(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->resolveIncident($incident, 'Incident résolu manuellement depuis la console SOC.');

        app(AuditLogService::class)->incidentResolved($incident->id, 'resolved');

        return back()->with('success', 'Incident résolu. Les alertes liées ont été clôturées.');
    }

    public function falsePositive(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->falsePositiveIncident($incident);

        app(AuditLogService::class)->incidentResolved($incident->id, 'false_positive');

        return back()->with('success', 'Incident classé faux positif. Alertes et actions liées synchronisées.');
    }

    public function reopen(Request $request, Incident $incident, SocStatusSynchronizerService $sync): RedirectResponse
    {
        $sync->reopenIncident($incident);

        app(AuditLogService::class)->incidentReopened($incident->id);

        return back()->with('success', 'Incident réouvert.');
    }
}
