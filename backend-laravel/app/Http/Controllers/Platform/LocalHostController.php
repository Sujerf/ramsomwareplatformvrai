<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\LocalHostDetectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LocalHostController extends Controller
{
    public function __construct(
        private readonly LocalHostDetectionService $localHostDetectionService,
    ) {
    }

    public function index(): View
    {
        $localHost = session('local_host_detection') ?? $this->localHostDetectionService->detect();

        return view('platform.local-host.index', [
            'hostname' => $localHost['hostname'] ?? null,
            'serverIp' => $localHost['primary_ip'] ?? null,
            'phpOs' => $localHost['os'] ?? PHP_OS_FAMILY,
            'localHost' => $localHost,
        ]);
    }

    public function detect(): RedirectResponse
    {
        $localHost = $this->localHostDetectionService->detect();

        session()->flash('local_host_detection', $localHost);

        return back()->with('success', 'Machine SOC locale détectée avec succès.');
    }
}
