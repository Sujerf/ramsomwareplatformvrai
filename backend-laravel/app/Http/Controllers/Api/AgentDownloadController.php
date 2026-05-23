<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Sert les fichiers statiques de l'agent Python pour l'installation automatisée.
 *
 * Endpoint public : GET /api/agent/download/{file}
 * Seuls les fichiers de la whitelist sont servis.
 */
class AgentDownloadController extends Controller
{
    private const ALLOWED = [
        'ransomshield_host_agent.py' => 'text/plain',
        'requirements.txt'           => 'text/plain',
        'install.sh'                 => 'text/x-sh',
    ];

    // base_path() = racine Laravel, ../agent-python = dossier agent au niveau du projet
    private string $agentDir;

    public function download(string $file): Response
    {
        if (! array_key_exists($file, self::ALLOWED)) {
            abort(404, "Fichier '{$file}' non disponible.");
        }

        $agentDir = realpath(base_path('../agent-python'));

        if (! $agentDir) {
            abort(500, "Dossier agent introuvable sur le serveur.");
        }

        $path = realpath($agentDir.'/'.$file);

        if (! $path || ! str_starts_with($path, $agentDir) || ! file_exists($path)) {
            abort(404, "Fichier introuvable sur le serveur.");
        }

        return response(file_get_contents($path), 200)
            ->header('Content-Type', self::ALLOWED[$file].'; charset=utf-8')
            ->header('Content-Disposition', "attachment; filename=\"{$file}\"")
            ->header('Cache-Control', 'no-store');
    }
}
