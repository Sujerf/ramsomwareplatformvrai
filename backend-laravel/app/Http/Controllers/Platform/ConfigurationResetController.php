<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\RansomShieldDefaultConfigurationService;
use Illuminate\Http\RedirectResponse;

class ConfigurationResetController extends Controller
{
    public function __invoke(RansomShieldDefaultConfigurationService $defaults): RedirectResponse
    {
        $results = $defaults->resetAll();

        return back()->with(
            'success',
            'Configuration réinitialisée : '
            .'extensions '.$results['sensitive_extensions'].', '
            .'règles '.$results['detection_rules'].', '
            .'seuils '.$results['detection_thresholds'].', '
            .'politiques '.$results['protection_policies'].', '
            .'paramètres '.$results['system_settings'].'.'
        );
    }
}
