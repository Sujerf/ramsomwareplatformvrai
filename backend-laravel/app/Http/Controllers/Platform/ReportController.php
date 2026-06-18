<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ReportController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class); // admin only

        $settings = SystemSetting::whereIn('key', [
            'report_executive_enabled',
            'report_executive_recipient',
            'report_executive_frequency',
        ])->get()->keyBy('key');

        $reports = $this->listReports();

        return view('platform.reports.index', [
            'settings' => $settings,
            'reports'  => $reports,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $period = in_array($request->input('period'), ['weekly', 'monthly'])
            ? $request->input('period')
            : 'weekly';

        // Force generation regardless of the enabled setting
        SystemSetting::where('key', 'report_executive_enabled')->update(['value' => '1']);

        $exitCode = Artisan::call('ransomshield:executive-report', ['--period' => $period]);

        if ($exitCode === 0) {
            return back()->with('success', 'Rapport généré et envoyé avec succès.');
        }

        return back()->withErrors(['report' => 'Erreur lors de la génération. Vérifiez le destinataire configuré.']);
    }

    public function download(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('viewAny', \App\Models\AuditLog::class);

        $path = storage_path('app/reports/'.basename($filename));

        abort_unless(File::exists($path), 404);

        return response()->download($path);
    }

    private function listReports(): array
    {
        $dir = storage_path('app/reports');

        if (! is_dir($dir)) {
            return [];
        }

        $files = File::files($dir);

        return collect($files)
            ->filter(fn ($f) => $f->getExtension() === 'pdf')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->take(20)
            ->map(fn ($f) => [
                'name'     => $f->getFilename(),
                'size'     => round($f->getSize() / 1024),
                'modified' => date('d/m/Y H:i', $f->getMTime()),
            ])
            ->values()
            ->toArray();
    }
}
