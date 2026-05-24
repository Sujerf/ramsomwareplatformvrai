<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Event;
use App\Services\AgentRiskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reçoit les événements de l'agent Python et délègue toute l'orchestration
 * à AgentRiskService (analyse → snapshot → incident → alerte → protection).
 *
 * Ce contrôleur ne décide rien : il valide, crée l'Event brut, délègue.
 */
class AgentEventController extends Controller
{
    /**
     * Chemins système/app qui génèrent du bruit légitime intense.
     * Les événements depuis ces chemins sont silencieusement ignorés côté serveur
     * SAUF si l'extension est sensible (ransomware) ou le nom ressemble à une
     * note de rançon — on garde toujours ces signaux critiques.
     *
     * Cette liste complète la logique d'exclusion de l'agent Python et couvre
     * les versions anciennes de l'agent qui n'ont pas encore les nouvelles exclusions.
     */
    private const NOISY_PATH_SEGMENTS = [
        // AppData entier — caches navigateurs, profils apps, bases SQLite d'apps
        'appdata\\local\\',
        'appdata\\roaming\\',
        'appdata\\locallow\\',
        // ProgramData — services système, antivirus, HP, updates
        'c:\\programdata\\',
        // Registre Windows utilisateur
        'ntuser.dat',
        // IsolatedStorage — .NET apps
        'isolatedstorage',
        // Windows Notifications
        'wpndatabase',
    ];

    /**
     * Extensions qui FORCENT le traitement même depuis un chemin bruité.
     * Un ransomware peut chiffrer AppData — on ne veut pas rater ça.
     */
    private const ALWAYS_PROCESS_EXTENSIONS = [
        'locked', 'encrypted', 'crypt', 'crypto', 'enc', 'pay',
        'ryk', 'lockbit', 'blackcat', 'wncry', 'wcry',
    ];

    /**
     * Noms de fichier indiquant une note de rançon — toujours traités.
     */
    private const RANSOM_NOTE_NAMES = [
        'readme', 'decrypt', 'recover', 'restore_files',
        'how_to_decrypt', 'ransom', 'instructions',
    ];

    public function store(Request $request, AgentRiskService $riskService): JsonResponse
    {
        $validated = $request->validate([
            'agent_uuid'     => ['required', 'uuid'],
            'event_type'     => ['required', 'string', 'max:120'],
            'path'           => ['nullable', 'string', 'max:2000'],
            'file_extension' => ['nullable', 'string', 'max:50'],
            'score'          => ['nullable', 'integer', 'min:0'],
            'risk_level'     => ['nullable', 'string', 'max:50'],
            'is_simulation'  => ['nullable', 'boolean'],
            'metadata'       => ['nullable', 'array'],
        ]);

        $agent = Agent::where('agent_uuid', $validated['agent_uuid'])->firstOrFail();

        // ── Filtre bruit système ──────────────────────────────────────────────
        // Ignorer silencieusement les événements depuis des chemins système/app
        // connus pour générer de l'I/O légitime intense — SAUF si c'est une
        // extension ransomware connue ou une note de rançon.
        if (! ($validated['is_simulation'] ?? false)) {
            $path = strtolower(str_replace('/', '\\', $validated['path'] ?? ''));
            $ext  = strtolower(ltrim($validated['file_extension'] ?? '', '.'));

            // Forcer le traitement si extension sensible ou note de rançon
            $isHighValueSignal =
                in_array($ext, self::ALWAYS_PROCESS_EXTENSIONS, true)
                || in_array($validated['event_type'], ['ransom_note_detected', 'file_encrypted_extension', 'mass_rename_detected'], true)
                || $this->pathLooksLikeRansomNote($path);

            if (! $isHighValueSignal && $this->isNoisyPath($path)) {
                // Répondre 200 OK sans stocker — l'agent ne saura pas la différence
                return response()->json(['message' => 'ok', 'filtered' => true], 200);
            }
        }

        // Création de l'event brut — score et risk_level seront mis à jour
        // par AgentRiskService après l'analyse dynamique.
        $event = Event::create([
            'event_uuid'     => (string) Str::uuid(),
            'agent_id'       => $agent->id,
            'event_type'     => $validated['event_type'],
            'path'           => $validated['path'] ?? null,
            'file_extension' => $validated['file_extension'] ?? null,
            'score'          => 0,
            'risk_level'     => 'normal',
            'is_simulation'  => (bool) ($validated['is_simulation'] ?? false),
            'metadata'       => $validated['metadata'] ?? [],
            'observed_at'    => now(),
        ]);

        // Délégation complète : analyse + snapshot + incident + alerte + actions
        $result = $riskService->handleIncomingEvent($event);

        // Recharger pour avoir score/risk_level mis à jour
        $event->refresh();

        return response()->json([
            'message'     => 'Événement reçu et analysé.',
            'event'       => [
                'id'         => $event->id,
                'event_uuid' => $event->event_uuid,
                'risk_level' => $event->risk_level,
                'score'      => $event->score,
            ],
            'analysis'    => [
                'risk_level'    => $result['risk_level'],
                'score'         => $result['score'],
                'signals_count' => count($result['signals']),
                'threshold'     => $result['threshold']['code'] ?? null,
            ],
            'alert_id'    => $result['alert_id'],
            'incident_id' => $result['incident_id'],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  FILTRE BRUIT
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retourne true si le chemin est un chemin système/applicatif connu
     * pour générer de l'I/O légitime intense (AppData, ProgramData...).
     */
    private function isNoisyPath(string $normalizedPath): bool
    {
        if (! $normalizedPath) {
            return false;
        }

        foreach (self::NOISY_PATH_SEGMENTS as $segment) {
            if (str_contains($normalizedPath, $segment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retourne true si le nom de fichier ressemble à une note de rançon.
     */
    private function pathLooksLikeRansomNote(string $path): bool
    {
        $name = strtolower(basename($path));

        foreach (self::RANSOM_NOTE_NAMES as $keyword) {
            if (str_contains($name, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
