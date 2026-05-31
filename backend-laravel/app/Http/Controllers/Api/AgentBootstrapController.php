<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Génère un script d'installation auto-suffisant pour l'agent RansomShield.
 *
 * Endpoint public (pas de middleware agent.secret) :
 *   GET /api/agent/bootstrap/{uuid}            → Bash (Linux — systemd)
 *   GET /api/agent/bootstrap/{uuid}?os=macos   → Bash (macOS — launchd)
 *   GET /api/agent/bootstrap/{uuid}?os=windows → PowerShell (Windows — sc.exe)
 *
 * L'UUID sert de jeton d'accès — 122 bits d'entropie, usage one-time.
 * Le script embarque le .env complet avec le token d'enrôlement.
 * Une fois l'agent enrôlé, le token est détruit → le script n'est plus rejouable.
 */
class AgentBootstrapController extends Controller
{
    /**
     * Route courte : GET /e/{code}   (8 chars alphanumériques)
     * Identique à /api/agent/bootstrap/{uuid} mais avec un code court
     * pensé pour la saisie dans un terminal KVM où copier-coller est impossible.
     *
     * Exemple : curl http://10.20.0.1:8080/e/c075615a | sudo bash
     */
    public function scriptByShortCode(Request $request, string $code): Response
    {
        $agent = Agent::where('enrollment_short_code', $code)->first();

        if (! $agent) {
            abort(404, 'Code d\'enrôlement invalide ou expiré.');
        }

        // Delègue au même handler, en injectant l'UUID comme paramètre de route fictif
        return $this->script($request, $agent->agent_uuid);
    }

    public function script(Request $request, string $uuid): Response
    {
        $agent = Agent::where('agent_uuid', $uuid)->first();

        if (! $agent) {
            abort(404, 'Agent non trouvé.');
        }

        if ($agent->enrollment_status === 'enrolled') {
            abort(410, 'Cet agent est déjà enrôlé. Ce script n\'est plus valide.');
        }

        if (empty($agent->enrollment_token)) {
            abort(403, 'Aucun token d\'enrôlement actif. Régénère un token depuis la console SOC.');
        }

        if ($agent->enrollment_token_expires_at && now()->gt($agent->enrollment_token_expires_at)) {
            abort(403, 'Token d\'enrôlement expiré. Régénère-en un depuis la console SOC.');
        }

        // ── URL SOC auto-résolue ─────────────────────────────────────────────
        // La machine qui télécharge ce script nous contacte déjà via une URL
        // qui fonctionne sur son réseau. On l'utilise directement :
        //   - VM virbr-soc (10.20.0.x)  → Host: 10.20.0.1:8081
        //   - Machine WiFi (192.168.1.x) → Host: 192.168.1.194:8081
        //   - Prod HTTPS / domaine       → Host: soc.company.com
        // Fallback sur RANSHIELD_SOC_URL si le Host est 127.0.0.1 / localhost
        // (accès depuis le navigateur de la même machine, pas depuis la cible).
        $requestHost = $request->getSchemeAndHttpHost();
        $parsedHost  = parse_url($requestHost, PHP_URL_HOST) ?? '';
        $isLocalhost = in_array($parsedHost, ['127.0.0.1', 'localhost', '::1'], true);

        $socUrl    = rtrim($isLocalhost ? config('app.soc_url', config('app.url')) : $requestHost, '/');
        $apiSecret = config('app.agent_api_secret', '');
        $os        = strtolower($request->query('os', 'linux'));

        if ($os === 'windows') {
            $script   = $this->buildPowerShellScript(agent: $agent, socUrl: $socUrl, apiSecret: $apiSecret);
            $filename = 'ransomshield-install.ps1';
            $mime     = 'text/plain; charset=utf-8';
        } elseif ($os === 'macos') {
            $script   = $this->buildMacOsScript(agent: $agent, socUrl: $socUrl, apiSecret: $apiSecret);
            $filename = 'ransomshield-install.sh';
            $mime     = 'text/plain; charset=utf-8';
        } else {
            $script   = $this->buildBashScript(agent: $agent, socUrl: $socUrl, apiSecret: $apiSecret);
            $filename = 'ransomshield-install.sh';
            $mime     = 'text/plain; charset=utf-8';
        }

        return response($script, 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"")
            ->header('Cache-Control', 'no-store, no-cache');
    }

    /**
     * Échappe une valeur pour injection dans une chaîne double-quotée Bash.
     * Neutralise \ " $ ` qui ont une signification dans ce contexte.
     */
    private function bashEscape(string $value): string
    {
        return str_replace(['\\', '"', '$', '`'], ['\\\\', '\\"', '\\$', '\\`'], $value);
    }

    /**
     * Échappe une valeur pour injection dans une chaîne double-quotée PowerShell.
     * Dans ce contexte, ` $ " ont une signification spéciale.
     */
    private function psEscape(string $value): string
    {
        return str_replace(['`', '$', '"'], ['``', '`$', '`"'], $value);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BASH — Linux / macOS
    // ─────────────────────────────────────────────────────────────────────────
    private function buildBashScript(Agent $agent, string $socUrl, string $apiSecret): string
    {
        $uuid      = $agent->agent_uuid;
        $token     = $agent->enrollment_token;
        $name      = $this->bashEscape($agent->agent_name);
        $role      = $this->bashEscape($agent->host_role ?? 'client');
        $apiSecret = $this->bashEscape($apiSecret);
        $apiUrl    = $socUrl.'/api';
        $expires = optional($agent->enrollment_token_expires_at)->toDateTimeString() ?? 'inconnue';
        $now     = now()->toDateTimeString();

        return <<<BASH
        #!/usr/bin/env bash
        # ─────────────────────────────────────────────────────────────────────────────
        #  RansomShield Host Agent — Script d'installation Linux/macOS auto-généré
        #  Généré le : {$now}
        #  Agent     : {$name} ({$uuid})
        #  Token     : usage unique, expire le {$expires}
        # ─────────────────────────────────────────────────────────────────────────────
        set -euo pipefail

        AGENT_UUID="{$uuid}"
        AGENT_NAME="{$name}"
        HOST_ROLE="{$role}"
        API_URL="{$apiUrl}"
        API_SECRET="{$apiSecret}"
        ENROLLMENT_TOKEN="{$token}"
        SOC_BASE="{$socUrl}"
        INSTALL_DIR="/opt/ransomshield-agent"
        SERVICE_NAME="ransomshield-agent"

        echo ""
        echo "╔══════════════════════════════════════════════════╗"
        echo "║    RansomShield — Installation de l'agent        ║"
        echo "╚══════════════════════════════════════════════════╝"
        echo ""
        echo "  Machine cible : \$(hostname) (\$(hostname -I | awk '{print \$1}'))"
        echo "  Agent UUID    : \$AGENT_UUID"
        echo "  SOC API       : \$API_URL"
        echo ""

        # ── 1. Vérifications système ───────────────────────────────────────────────
        if [ "\$(id -u)" -ne 0 ]; then
            echo "[ERREUR] Ce script doit être exécuté en tant que root (sudo)."
            exit 1
        fi

        if ! command -v python3 &>/dev/null; then
            echo "[INFO] Python 3 absent — installation complète..."
            apt-get update -qq
            apt-get install -y -qq python3 python3-pip python3-venv curl
        else
            # python3 présent mais python3-venv peut être absent (Ubuntu minimal)
            echo "[INFO] Vérification de python3-venv et python3-pip..."
            apt-get install -y -qq python3-venv python3-pip 2>/dev/null || true
        fi

        PYTHON_BIN="\$(command -v python3)"
        if [ -z "\$PYTHON_BIN" ]; then
            echo "[ERREUR] python3 introuvable après installation. Installe-le manuellement."
            exit 1
        fi
        PY_VERSION="\$("\$PYTHON_BIN" -c 'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")')"
        echo "[OK] Python \$PY_VERSION"

        # ── 2. Dossier d'installation ──────────────────────────────────────────────
        echo "[INFO] Création du dossier \$INSTALL_DIR..."
        mkdir -p "\$INSTALL_DIR"
        cd "\$INSTALL_DIR"

        # ── 3. Téléchargement des fichiers agent depuis le SOC ─────────────────────
        echo "[INFO] Téléchargement des fichiers depuis le SOC (\$SOC_BASE)..."

        curl -fsSL "\$SOC_BASE/api/agent/download/ransomshield_host_agent.py" \
             -o ransomshield_host_agent.py
        curl -fsSL "\$SOC_BASE/api/agent/download/requirements.txt" \
             -o requirements.txt
        curl -fsSL "\$SOC_BASE/api/agent/download/install.sh" \
             -o install.sh
        chmod +x install.sh

        echo "[OK] Fichiers téléchargés"

        # ── 4. Fichier .env ────────────────────────────────────────────────────────
        echo "[INFO] Écriture du fichier .env..."
        cat > .env << 'ENVEOF'
        RANSHIELD_API_URL={$apiUrl}
        # RANSHIELD_API_SECRET est vide ici intentionnellement.
        # L'agent recevra sa clé API unique lors du premier enrôlement
        # et la persistera dans son state local (.ransomshield_host_agent_state.json).
        RANSHIELD_API_SECRET=
        RANSHIELD_AGENT_UUID={$uuid}
        RANSHIELD_ENROLLMENT_TOKEN={$token}
        RANSHIELD_AGENT_NAME={$name}
        RANSHIELD_HOST_ROLE={$role}
        RANSHIELD_MONITOR_MODE=host
        RANSHIELD_HEARTBEAT_INTERVAL=30
        RANSHIELD_SCAN_INTERVAL=5
        RANSHIELD_ENABLE_FILE_MONITOR=true
        RANSHIELD_ENABLE_PROCESS_MONITOR=true
        ENVEOF
        echo "[OK] .env configuré"

        # ── 5. Environnement virtuel et dépendances ────────────────────────────────
        echo "[INFO] Création du venv Python..."
        if [ ! -d venv ]; then
            "\$PYTHON_BIN" -m venv venv
        fi
        venv/bin/pip install --quiet --upgrade pip
        venv/bin/pip install --quiet -r requirements.txt
        echo "[OK] Dépendances installées"

        # ── 6. Service systemd ─────────────────────────────────────────────────────
        echo "[INFO] Installation du service systemd..."
        cat > "/etc/systemd/system/\${SERVICE_NAME}.service" << SVCEOF
        [Unit]
        Description=RansomShield Host Agent
        After=network-online.target
        Wants=network-online.target

        [Service]
        Type=simple
        User=root
        WorkingDirectory=\${INSTALL_DIR}
        ExecStart=\${INSTALL_DIR}/venv/bin/python \${INSTALL_DIR}/ransomshield_host_agent.py
        Restart=always
        RestartSec=10
        StandardOutput=journal
        StandardError=journal
        Environment=PYTHONUNBUFFERED=1

        [Install]
        WantedBy=multi-user.target
        SVCEOF

        # ── 7. Nettoyage de l'état précédent (force ré-enrôlement) ────────────────
        # Si l'agent était déjà installé, l'ancien state contient un agent_uuid
        # qui ferait sauter l'appel /enroll au démarrage. On le supprime pour
        # garantir que le nouveau token est bien consommé.
        echo "[INFO] Nettoyage de l'état précédent..."
        rm -f "\${INSTALL_DIR}/.ransomshield_host_agent_state.json"
        echo "[OK] État précédent effacé"

        systemctl daemon-reload
        systemctl enable "\$SERVICE_NAME"
        systemctl restart "\$SERVICE_NAME"

        echo ""
        echo "╔══════════════════════════════════════════════════╗"
        echo "║          Installation terminée avec succès !     ║"
        echo "╚══════════════════════════════════════════════════╝"
        echo ""
        echo "  L'agent va maintenant contacter le SOC et s'enrôler."
        echo "  Surveille les logs : journalctl -u \$SERVICE_NAME -f"
        echo ""
        echo "  Statut : systemctl status \$SERVICE_NAME"
        echo "  Logs   : journalctl -u \$SERVICE_NAME -f"
        echo ""
        BASH;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BASH — macOS (launchd au lieu de systemd)
    // ─────────────────────────────────────────────────────────────────────────
    private function buildMacOsScript(Agent $agent, string $socUrl, string $apiSecret): string
    {
        $uuid      = $agent->agent_uuid;
        $token     = $agent->enrollment_token;
        $name      = $this->bashEscape($agent->agent_name);
        $role      = $this->bashEscape($agent->host_role ?? 'client');
        $apiSecret = $this->bashEscape($apiSecret);
        $apiUrl    = $socUrl.'/api';
        $expires = optional($agent->enrollment_token_expires_at)->toDateTimeString() ?? 'inconnue';
        $now     = now()->toDateTimeString();

        return <<<BASH
        #!/usr/bin/env bash
        # ─────────────────────────────────────────────────────────────────────────────
        #  RansomShield Host Agent — Script d'installation macOS auto-généré
        #  Généré le : {$now}
        #  Agent     : {$name} ({$uuid})
        #  Token     : usage unique, expire le {$expires}
        # ─────────────────────────────────────────────────────────────────────────────
        set -euo pipefail

        AGENT_UUID="{$uuid}"
        AGENT_NAME="{$name}"
        HOST_ROLE="{$role}"
        API_URL="{$apiUrl}"
        API_SECRET="{$apiSecret}"
        ENROLLMENT_TOKEN="{$token}"
        SOC_BASE="{$socUrl}"
        INSTALL_DIR="/opt/ransomshield-agent"
        PLIST_LABEL="com.ransomshield.agent"
        PLIST_PATH="/Library/LaunchDaemons/\${PLIST_LABEL}.plist"

        echo ""
        echo "╔══════════════════════════════════════════════════╗"
        echo "║  RansomShield — Installation macOS (launchd)     ║"
        echo "╚══════════════════════════════════════════════════╝"
        echo ""
        echo "  Machine    : \$(hostname)"
        echo "  Agent UUID : \$AGENT_UUID"
        echo "  SOC API    : \$API_URL"
        echo ""

        # ── 1. Vérifications système ───────────────────────────────────────────────
        if [ "\$(id -u)" -ne 0 ]; then
            echo "[ERREUR] Ce script doit être exécuté en tant que root (sudo)."
            exit 1
        fi

        # Python 3 — via Homebrew, Xcode CLT ou python.org
        PYTHON_BIN=""
        for candidate in /usr/local/bin/python3 /opt/homebrew/bin/python3 /usr/bin/python3; do
            if [ -x "\$candidate" ]; then
                PYTHON_BIN="\$candidate"
                break
            fi
        done

        if [ -z "\$PYTHON_BIN" ]; then
            echo "[INFO] Python 3 introuvable. Installation via Homebrew..."
            if ! command -v brew &>/dev/null; then
                echo "[ERREUR] Homebrew absent. Installe Python 3 depuis https://python.org ou installe Homebrew."
                exit 1
            fi
            brew install python3
            PYTHON_BIN="\$(command -v python3)"
        fi

        PY_VERSION="\$("\$PYTHON_BIN" -c 'import sys; print(f"{sys.version_info.major}.{sys.version_info.minor}")')"
        echo "[OK] Python \$PY_VERSION (\$PYTHON_BIN)"

        # ── 2. Dossier d'installation ──────────────────────────────────────────────
        echo "[INFO] Création du dossier \$INSTALL_DIR..."
        mkdir -p "\$INSTALL_DIR"
        cd "\$INSTALL_DIR"

        # ── 3. Téléchargement des fichiers agent ───────────────────────────────────
        echo "[INFO] Téléchargement des fichiers depuis le SOC..."
        curl -fsSL "\$SOC_BASE/api/agent/download/ransomshield_host_agent.py" -o ransomshield_host_agent.py
        curl -fsSL "\$SOC_BASE/api/agent/download/requirements.txt"           -o requirements.txt
        echo "[OK] Fichiers téléchargés"

        # ── 4. Fichier .env ────────────────────────────────────────────────────────
        cat > .env << 'ENVEOF'
        RANSHIELD_API_URL={$apiUrl}
        RANSHIELD_API_SECRET=
        RANSHIELD_AGENT_UUID={$uuid}
        RANSHIELD_ENROLLMENT_TOKEN={$token}
        RANSHIELD_AGENT_NAME={$name}
        RANSHIELD_HOST_ROLE={$role}
        RANSHIELD_MONITOR_MODE=host
        RANSHIELD_HEARTBEAT_INTERVAL=30
        RANSHIELD_SCAN_INTERVAL=5
        RANSHIELD_ENABLE_FILE_MONITOR=true
        RANSHIELD_ENABLE_PROCESS_MONITOR=true
        ENVEOF
        echo "[OK] .env configuré"

        # ── 5. Environnement virtuel et dépendances ────────────────────────────────
        echo "[INFO] Création du venv Python..."
        if [ ! -d venv ]; then
            "\$PYTHON_BIN" -m venv venv
        fi
        venv/bin/pip install --quiet --upgrade pip
        venv/bin/pip install --quiet -r requirements.txt
        echo "[OK] Dépendances installées"

        # ── 6. Service launchd (LaunchDaemon) ─────────────────────────────────────
        echo "[INFO] Installation du LaunchDaemon macOS..."

        # Arrêter et décharger si déjà installé
        if [ -f "\$PLIST_PATH" ]; then
            launchctl unload -w "\$PLIST_PATH" 2>/dev/null || true
        fi

        cat > "\$PLIST_PATH" << PLISTEOF
        <?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0">
        <dict>
            <key>Label</key>
            <string>\${PLIST_LABEL}</string>
            <key>ProgramArguments</key>
            <array>
                <string>\${INSTALL_DIR}/venv/bin/python</string>
                <string>\${INSTALL_DIR}/ransomshield_host_agent.py</string>
            </array>
            <key>WorkingDirectory</key>
            <string>\${INSTALL_DIR}</string>
            <key>RunAtLoad</key>
            <true/>
            <key>KeepAlive</key>
            <true/>
            <key>ThrottleInterval</key>
            <integer>10</integer>
            <key>StandardOutPath</key>
            <string>/var/log/ransomshield-agent.log</string>
            <key>StandardErrorPath</key>
            <string>/var/log/ransomshield-agent-error.log</string>
            <key>EnvironmentVariables</key>
            <dict>
                <key>PYTHONUNBUFFERED</key>
                <string>1</string>
            </dict>
        </dict>
        </plist>
        PLISTEOF

        chmod 644 "\$PLIST_PATH"

        # ── 7. Nettoyage de l'état précédent (force ré-enrôlement) ────────────────
        echo "[INFO] Nettoyage de l'état précédent..."
        rm -f "\${INSTALL_DIR}/.ransomshield_host_agent_state.json"
        echo "[OK] État précédent effacé"

        launchctl load -w "\$PLIST_PATH"

        echo ""
        echo "╔══════════════════════════════════════════════════╗"
        echo "║         Installation terminée avec succès !      ║"
        echo "╚══════════════════════════════════════════════════╝"
        echo ""
        echo "  L'agent va maintenant contacter le SOC et s'enrôler."
        echo ""
        echo "  Statut : launchctl list | grep ransomshield"
        echo "  Logs   : tail -f /var/log/ransomshield-agent.log"
        echo "  Stop   : sudo launchctl unload -w \$PLIST_PATH"
        echo ""
        BASH;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  POWERSHELL — Windows
    //  Fixes :
    //    - Détection robuste Python : ignore le stub Windows Store
    //    - Tâche planifiée (Task Scheduler) au lieu de sc.exe
    //      → Python n'implémente pas le protocole SCM ; sc.exe causait l'erreur 1053
    // ─────────────────────────────────────────────────────────────────────────
    private function buildPowerShellScript(Agent $agent, string $socUrl, string $apiSecret): string
    {
        $uuid      = $agent->agent_uuid;
        $token     = $agent->enrollment_token;
        $name      = $this->psEscape($agent->agent_name);
        $role      = $this->psEscape($agent->host_role ?? 'client');
        $apiSecret = $this->psEscape($apiSecret);
        // $socUrl est transmis par le caller (script()), déjà résolu pour ce réseau
        $apiUrl  = $socUrl.'/api';
        $expires = optional($agent->enrollment_token_expires_at)->toDateTimeString() ?? 'inconnue';
        $now     = now()->toDateTimeString();

        return <<<PS1
# =============================================================================
#  RansomShield Host Agent — Script d'installation Windows
#  Généré le : {$now}
#  Agent     : {$name} ({$uuid})
#  Token     : usage unique, expire le {$expires}
# =============================================================================

# ── Auto-élévation ────────────────────────────────────────────────────────────
# Fonctionne que le script soit lancé via iex ou via un fichier .ps1
if (-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {

    Write-Host "[INFO] Privileges insuffisants — re-lancement en Administrateur..."

    # Sauvegarder le script dans un fichier temporaire pour le re-lancer
    \$tmpFile = "\$env:TEMP\rsinstall-{$uuid}.ps1"
    \$MyInvocation.MyCommand.ScriptContents | Set-Content \$tmpFile -Encoding UTF8 -ErrorAction SilentlyContinue

    if (Test-Path \$tmpFile) {
        Start-Process powershell `
            -ArgumentList "-ExecutionPolicy Bypass -File `"\$tmpFile`"" `
            -Verb RunAs
    } else {
        # Fallback : demander à l'utilisateur de relancer manuellement
        Write-Warning "Relancez PowerShell en tant qu'Administrateur et reexecutez cette commande."
    }
    exit
}

\$ErrorActionPreference = "Stop"

\$AGENT_UUID       = "{$uuid}"
\$AGENT_NAME       = "{$name}"
\$HOST_ROLE        = "{$role}"
\$API_URL          = "{$apiUrl}"
\$API_SECRET       = "{$apiSecret}"
\$ENROLLMENT_TOKEN = "{$token}"
\$SOC_BASE         = "{$socUrl}"
\$INSTALL_DIR      = "C:\RansomShieldAgent"
\$TASK_NAME        = "RansomShieldAgent"
\$LOG_FILE         = "C:\RansomShieldAgent\agent.log"

Write-Host ""
Write-Host "==================================================="
Write-Host "   RansomShield -- Installation de l'agent"
Write-Host "==================================================="
Write-Host ""
\$localIp = (Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object { \$_.IPAddress -notlike "127.*" -and \$_.IPAddress -notlike "169.254.*" } |
    Select-Object -First 1 -ExpandProperty IPAddress)
Write-Host ("  Machine : " + \$env:COMPUTERNAME + " (" + \$localIp + ")")
Write-Host ("  UUID    : " + \$AGENT_UUID)
Write-Host ("  SOC API : " + \$API_URL)
Write-Host ""

# ── 1. Python 3.10+ — détection robuste, ignore le stub Windows Store ────────
#
#  Windows 10/11 installe un "app execution alias" python.exe qui ouvre le
#  Microsoft Store au lieu de lancer Python. Get-Command le trouve mais il
#  ne fonctionne pas. On le détecte en testant l'exécution réelle.
#
Write-Host "[1/6] Recherche de Python 3.10+..."

\$PYTHON_EXE = \$null

# Chercher parmi les candidats courants (py launcher, python3, python)
foreach (\$candidate in @("py", "python3", "python")) {
    \$cmd = Get-Command \$candidate -ErrorAction SilentlyContinue
    if (-not \$cmd) { continue }
    try {
        \$out = & \$cmd -c "import sys; print(sys.version_info.major)" 2>&1
        if (\$out -match '^\d+$' -and [int]\$out -ge 3) {
            \$PYTHON_EXE = \$cmd.Source
            break
        }
    } catch {}
}

if (-not \$PYTHON_EXE) {
    Write-Host "[INFO] Python introuvable ou stub Store detecte. Telechargement de Python 3.12..."
    \$pyInstaller = "\$env:TEMP\python-3.12-setup.exe"
    Invoke-WebRequest -Uri "https://www.python.org/ftp/python/3.12.4/python-3.12.4-amd64.exe" `
                      -OutFile \$pyInstaller -UseBasicParsing
    Write-Host "[INFO] Installation en cours (silencieuse, ~30s)..."
    Start-Process -FilePath \$pyInstaller `
                  -ArgumentList "/quiet InstallAllUsers=1 PrependPath=1 Include_pip=1 Include_launcher=1" `
                  -Wait
    Remove-Item \$pyInstaller -ErrorAction SilentlyContinue

    # Rafraichir PATH depuis le registre (la session en cours ne l'a pas encore)
    \$machinePath = [System.Environment]::GetEnvironmentVariable("PATH", "Machine")
    \$userPath    = [System.Environment]::GetEnvironmentVariable("PATH", "User")
    \$env:PATH    = "\$machinePath;\$userPath"

    # Chercher à nouveau après installation
    foreach (\$candidate in @("py", "python")) {
        \$cmd = Get-Command \$candidate -ErrorAction SilentlyContinue
        if (-not \$cmd) { continue }
        try {
            \$out = & \$cmd -c "import sys; print(sys.version_info.major)" 2>&1
            if (\$out -match '^\d+$' -and [int]\$out -ge 3) {
                \$PYTHON_EXE = \$cmd.Source
                break
            }
        } catch {}
    }
}

if (-not \$PYTHON_EXE) {
    Write-Error "[ERREUR] Python 3.10+ non disponible. Installez-le manuellement depuis https://python.org puis relancez ce script."
    exit 1
}

\$pyVer = & "\$PYTHON_EXE" -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}.{sys.version_info.micro}')"
Write-Host ("[OK] Python \$pyVer -> \$PYTHON_EXE")

# ── 2. Dossier d'installation ─────────────────────────────────────────────────
Write-Host "[2/6] Preparation du dossier \$INSTALL_DIR..."
if (-not (Test-Path \$INSTALL_DIR)) { New-Item -ItemType Directory -Path \$INSTALL_DIR | Out-Null }
Set-Location \$INSTALL_DIR

# ── 3. Téléchargement des fichiers agent ──────────────────────────────────────
Write-Host "[3/6] Telechargement des fichiers depuis le SOC..."
Invoke-WebRequest -Uri "\$SOC_BASE/api/agent/download/ransomshield_host_agent.py" `
                  -OutFile "ransomshield_host_agent.py" -UseBasicParsing
Invoke-WebRequest -Uri "\$SOC_BASE/api/agent/download/requirements.txt" `
                  -OutFile "requirements.txt" -UseBasicParsing
Write-Host "[OK] Fichiers telecharges"

# ── 4. Fichier .env — écrit sans BOM (UTF-8 pur) ─────────────────────────────
# PowerShell Set-Content -Encoding UTF8 écrit un BOM (\xef\xbb\xbf) que
# python-dotenv ne reconnaît pas → toutes les clés tombent sur les defaults.
# On utilise [System.IO.File]::WriteAllText avec UTF8Encoding($false).
Write-Host "[4/6] Ecriture du fichier .env (UTF-8 sans BOM)..."
\$envContent = "RANSHIELD_API_URL={$apiUrl}`nRANSHIELD_API_SECRET=`nRANSHIELD_AGENT_UUID={$uuid}`nRANSHIELD_ENROLLMENT_TOKEN={$token}`nRANSHIELD_AGENT_NAME={$name}`nRANSHIELD_HOST_ROLE={$role}`nRANSHIELD_MONITOR_MODE=host`nRANSHIELD_HEARTBEAT_INTERVAL=30`nRANSHIELD_SCAN_INTERVAL=5`nRANSHIELD_ENABLE_FILE_MONITOR=true`nRANSHIELD_ENABLE_PROCESS_MONITOR=true`n"
\$utf8NoBom = New-Object System.Text.UTF8Encoding(\$false)
[System.IO.File]::WriteAllText((Join-Path \$INSTALL_DIR ".env"), \$envContent, \$utf8NoBom)
Write-Host "[OK] .env configure (sans BOM)"

# ── 5. Environnement virtuel Python et dépendances ────────────────────────────
Write-Host "[5/6] Creation du venv et installation des dependances..."
if (-not (Test-Path "venv")) {
    & "\$PYTHON_EXE" -m venv venv
}
& "venv\Scripts\python.exe" -m pip install --quiet --upgrade pip
& "venv\Scripts\pip.exe" install --quiet -r requirements.txt
Write-Host "[OK] Dependances installees"

# ── 6. Tâche planifiée Windows (remplace sc.exe) ─────────────────────────────
#
#  Python n'implémente pas le protocole Windows SCM → sc.exe cause l'erreur 1053.
#  Une tâche planifiée démarrée au boot sous SYSTEM est plus fiable et ne requiert
#  pas NSSM ni de wrapper de service.
#
Write-Host "[6/6] Installation de la tache planifiee..."

\$pythonExe = (Resolve-Path "venv\Scripts\python.exe").Path
\$agentPy   = (Resolve-Path "ransomshield_host_agent.py").Path

# Supprimer tâche existante
Unregister-ScheduledTask -TaskName \$TASK_NAME -Confirm:\$false -ErrorAction SilentlyContinue

\$action    = New-ScheduledTaskAction `
                -Execute "\$pythonExe" `
                -Argument "\$agentPy" `
                -WorkingDirectory \$INSTALL_DIR

\$trigger   = New-ScheduledTaskTrigger -AtStartup

\$settings  = New-ScheduledTaskSettingsSet `
                -ExecutionTimeLimit ([TimeSpan]::Zero) `
                -RestartCount 5 `
                -RestartInterval (New-TimeSpan -Minutes 1) `
                -StartWhenAvailable

\$principal = New-ScheduledTaskPrincipal `
                -UserID "SYSTEM" `
                -LogonType ServiceAccount `
                -RunLevel Highest

Register-ScheduledTask `
    -TaskName   \$TASK_NAME `
    -Action     \$action `
    -Trigger    \$trigger `
    -Settings   \$settings `
    -Principal  \$principal `
    -Description "RansomShield Host Agent — surveillance anti-ransomware" | Out-Null

# Démarrer immédiatement
Start-ScheduledTask -TaskName \$TASK_NAME

Write-Host ""
Write-Host "==================================================="
Write-Host "   Installation terminee avec succes !"
Write-Host "==================================================="
Write-Host ""
Write-Host "  L'agent demarre et va s'enroler dans les 30 secondes."
Write-Host ""
Write-Host "  Statut   : Get-ScheduledTask -TaskName \$TASK_NAME"
Write-Host "  Logs     : Get-Content C:\RansomShieldAgent\agent.log -Wait"
Write-Host "  Arreter  : Stop-ScheduledTask -TaskName \$TASK_NAME"
Write-Host "  Supprimer: Unregister-ScheduledTask -TaskName \$TASK_NAME -Confirm:\$false"
Write-Host ""
PS1;
    }
}
