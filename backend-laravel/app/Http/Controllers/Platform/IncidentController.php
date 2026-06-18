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
        $risk = $request->query('risk');

        $query = Incident::with(['agent', 'attackProfile'])
            ->withCount('alerts')
            ->latest('detected_at')
            ->latest();

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

        $cntActive   = Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])->count();
        $cntResolved = Incident::where('status', 'resolved')->count();
        $cntFalsePos = Incident::where('status', 'false_positive')->count();
        $cntTotal    = Incident::count();

        // Compteurs par risque parmi les incidents actifs (pour onglets filtre)
        $riskCounts = Incident::whereIn('status', ['open', 'investigating', 'under_review', 'reopened'])
            ->selectRaw('risk_level, COUNT(*) as cnt')
            ->groupBy('risk_level')
            ->pluck('cnt', 'risk_level')
            ->toArray();

        return view('platform.incidents.index', [
            'incidents'    => $query->paginate(25)->withQueryString(),
            'activeStatus' => $status,
            'activeRisk'   => $risk ?? '',   // '' = tous risques (jamais null côté vue)
            'stats'        => [
                'active'         => $cntActive,
                'resolved'       => $cntResolved,
                'false_positive' => $cntFalsePos,
                'critical'       => Incident::where('risk_level', 'critical')->count(),
                'high'           => Incident::where('risk_level', 'high')->count(),
                'total'          => $cntTotal,
            ],
            'filterCounts' => [
                'status' => [
                    'active'         => $cntActive,
                    'resolved'       => $cntResolved,
                    'false_positive' => $cntFalsePos,
                    'all'            => $cntTotal,
                ],
                'risk' => [
                    ''         => $cntActive,           // '' = tous risques actifs
                    'critical' => $riskCounts['critical'] ?? 0,
                    'high'     => $riskCounts['high']     ?? 0,
                    'suspect'  => $riskCounts['suspect']  ?? 0,
                    'normal'   => $riskCounts['normal']   ?? 0,
                ],
            ],
        ]);
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
