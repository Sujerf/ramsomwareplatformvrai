#!/usr/bin/env bash
# RansomShield Host Agent — script d'installation
# Usage : sudo bash install.sh
# Testé sur Ubuntu 22.04 / 24.04 et Debian 12

set -euo pipefail

AGENT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_NAME="ransomshield-agent"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
PYTHON_BIN="$(command -v python3)"

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║       RansomShield Host Agent — Installation     ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# ── 1. Vérification de Python 3.10+ ──────────────────────────────────────────
PY_VERSION=$("$PYTHON_BIN" -c "import sys; print(f'{sys.version_info.major}.{sys.version_info.minor}')")
PY_MAJOR=$(echo "$PY_VERSION" | cut -d. -f1)
PY_MINOR=$(echo "$PY_VERSION" | cut -d. -f2)

if [ "$PY_MAJOR" -lt 3 ] || { [ "$PY_MAJOR" -eq 3 ] && [ "$PY_MINOR" -lt 10 ]; }; then
    echo "[ERREUR] Python 3.10+ requis (trouvé : $PY_VERSION)"
    exit 1
fi
echo "[OK] Python $PY_VERSION détecté"

# ── 2. Dépendances système ────────────────────────────────────────────────────
echo "[INFO] Installation des paquets système..."
apt-get update -qq
apt-get install -y -qq python3-venv python3-pip 2>/dev/null || true

# ── 3. Environnement virtuel ──────────────────────────────────────────────────
VENV_DIR="$AGENT_DIR/venv"
if [ ! -d "$VENV_DIR" ]; then
    echo "[INFO] Création du venv..."
    "$PYTHON_BIN" -m venv "$VENV_DIR"
fi

echo "[INFO] Installation des dépendances Python..."
"$VENV_DIR/bin/pip" install --quiet --upgrade pip
"$VENV_DIR/bin/pip" install --quiet -r "$AGENT_DIR/requirements.txt"
echo "[OK] Dépendances installées"

# ── 4. Fichier .env ───────────────────────────────────────────────────────────
if [ ! -f "$AGENT_DIR/.env" ]; then
    cp "$AGENT_DIR/.env.example" "$AGENT_DIR/.env"
    echo ""
    echo "[ATTENTION] Fichier .env créé depuis .env.example"
    echo "  → Éditez $AGENT_DIR/.env avant de continuer :"
    echo "    - RANSHIELD_API_URL  : URL du serveur SOC (ex: http://10.15.55.88:8000/api)"
    echo "    - RANSHIELD_API_SECRET : secret copié depuis le .env Laravel"
    echo "    - RANSHIELD_AGENT_NAME : nom de cette machine"
    echo ""
    read -r -p "Appuyez sur Entrée après avoir configuré le .env..." _
else
    echo "[OK] Fichier .env existant conservé"
fi

# ── 5. Service systemd ────────────────────────────────────────────────────────
CURRENT_USER="${SUDO_USER:-$(whoami)}"

cat > "$SERVICE_FILE" <<EOF
[Unit]
Description=RansomShield Host Agent
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=${CURRENT_USER}
WorkingDirectory=${AGENT_DIR}
ExecStart=${VENV_DIR}/bin/python ${AGENT_DIR}/ransomshield_host_agent.py
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl restart "$SERVICE_NAME"

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║              Installation terminée !             ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
echo "  Statut   : systemctl status $SERVICE_NAME"
echo "  Logs     : journalctl -u $SERVICE_NAME -f"
echo "  Arrêter  : systemctl stop $SERVICE_NAME"
echo "  Désactiver : systemctl disable $SERVICE_NAME"
echo ""
