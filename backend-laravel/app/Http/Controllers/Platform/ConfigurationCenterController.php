<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\ConfigurationLinkService;
use Illuminate\Contracts\View\View;

class ConfigurationCenterController extends Controller
{
    public function __invoke(ConfigurationLinkService $configuration): View
    {
        return view('platform.configuration.index', [
            'configuration' => $configuration->overview(),
        ]);
    }
}
