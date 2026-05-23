<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Génère un script bash auto-suffisant pour installer l'agent sur une machine cible.
 *
 * Endpoint public (pas de middleware agent.secret) :
 *   GET /api/agent/bootstrap/{uuid}
 *
 * L'UUID sert de jeton d'accès — il est assez entropique (122 bits) pour être
 * utilisé en identification one-time sans secret supplémentaire.
 * Le script embarque le .env complet avec le token d'enrôlement.
 * Une fois l'agent enrôlé, le token est détruit → le script n'est plus rejouable.
 */
class AgentBootstrapController extends Controller
{
    public function script(string $uuid): Response
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

        $socUrl    = rtrim(config('app.soc_url'), '/');
        $apiSecret = config('app.agent_api_secret', '');

        $script = $this->buildScript(
            agent: $agent,
            socUrl: $socUrl,
            apiSecret: $apiSecret,
        );

        return response($script, 200)
            ->header('Content-Type', 'text/plain; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="ransomshield-install.sh"')
            ->header('Cache-Control', 'no-store, no-cache');
    }

    private function buildScript(Agent $agent, string $socUrl, string $apiSecret): string
    {
        $uuid    = $agent->agent_uuid;
        $token   = $agent->enrollment_token;
        $name    = $agent->agent_name;
        $role    = $agent->host_role ?? 'client';
        $apiUrl  = $socUrl.'/api';
        $expires = optional($agent->enrollment_token_expires_at)->toDateTimeString() ?? 'inconnue';
        $now     = now()->toDateTimeString();

        return <<<BASH
        #!/usr/bin/env bash
        # ─────────────────────────────────────────────────────────────────────────────
        #  RansomShield Host Agent — Script d'installation auto-généré
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
            echo "[INFO] Installation de Python 3..."
            apt-get update -qq && apt-get install -y -qq python3 python3-pip python3-venv curl
        fi

        PYTHON_BIN="\$(command -v python3)"
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
        RANSHIELD_API_SECRET={$apiSecret}
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

        systemctl daemon-reload
        systemctl enable "\$SERVICE_NAME"
        systemctl restart "\$SERVICE_NAME"

        echo ""
        echo "╔══════════════════════════════════════════════════╗"
        echo "║          Installation terminée avec succès !     ║"
        echo "╚══════════════════════════════════════════════════╝"
        echo ""
        echo "  L'agent va maintenant contacter le SOC et s'enrôler."
        echo ""
        echo "  Statut : systemctl status \$SERVICE_NAME"
        echo "  Logs   : journalctl -u \$SERVICE_NAME -f"
        echo ""
        BASH;
    }
}
