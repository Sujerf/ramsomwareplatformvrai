#!/usr/bin/env bash
# RansomShield — KVM Lab Setup
# Usage: sudo bash labs/setup-kvm.sh

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
die()     { echo -e "${RED}[FAIL]${NC}  $*" >&2; exit 1; }

[[ $EUID -ne 0 ]] && die "Lance ce script avec sudo : sudo bash labs/setup-kvm.sh"

REAL_USER="${SUDO_USER:-$USER}"
SOC_BRIDGE="virbr-soc"
SOC_NET_NAME="ransomshield-lab"
SOC_NET_CIDR="10.20.0.0/24"
SOC_NET_GW="10.20.0.1"
SOC_NET_DHCP_START="10.20.0.10"
SOC_NET_DHCP_END="10.20.0.99"
ISO_DIR="/var/lib/libvirt/isos"
VM_DISK_DIR="/var/lib/libvirt/images"

# ─── 1. Packages ────────────────────────────────────────────────────────────
info "Installation des paquets KVM/libvirt..."
apt-get install -y \
    qemu-system-x86 \
    qemu-utils \
    libvirt-daemon-system \
    libvirt-clients \
    bridge-utils \
    virtinst \
    virt-viewer \
    ovmf \
    cloud-image-utils \
    genisoimage \
    wget \
    2>/dev/null
success "Paquets installés."

# ─── 2. Service libvirtd ────────────────────────────────────────────────────
info "Activation de libvirtd..."
systemctl enable --now libvirtd
success "libvirtd actif."

# ─── 3. Groupes utilisateur ─────────────────────────────────────────────────
info "Ajout de $REAL_USER aux groupes kvm et libvirt..."
usermod -aG kvm,libvirt "$REAL_USER"
success "Groupes appliqués (reconnexion requise ou 'newgrp libvirt')."

# ─── 4. Réseau interne lab ──────────────────────────────────────────────────
info "Création du réseau libvirt : $SOC_NET_NAME ($SOC_NET_CIDR)..."

if virsh net-info "$SOC_NET_NAME" &>/dev/null; then
    warn "Réseau $SOC_NET_NAME existe déjà, on le reuse."
else
    cat > /tmp/ransomshield-lab-net.xml <<NETEOF
<network>
  <name>${SOC_NET_NAME}</name>
  <forward mode='nat'/>
  <bridge name='${SOC_BRIDGE}' stp='on' delay='0'/>
  <ip address='${SOC_NET_GW}' netmask='255.255.255.0'>
    <dhcp>
      <range start='${SOC_NET_DHCP_START}' end='${SOC_NET_DHCP_END}'/>
      <host mac='52:54:00:aa:01:01' name='rs-client-1'   ip='10.20.0.11'/>
      <host mac='52:54:00:aa:01:02' name='rs-client-2'   ip='10.20.0.12'/>
      <host mac='52:54:00:aa:02:01' name='rs-fileserver'  ip='10.20.0.21'/>
      <host mac='52:54:00:aa:03:01' name='rs-attacker'    ip='10.20.0.31'/>
    </dhcp>
  </ip>
</network>
NETEOF

    virsh net-define /tmp/ransomshield-lab-net.xml
    virsh net-autostart "$SOC_NET_NAME"
    virsh net-start    "$SOC_NET_NAME"
    success "Réseau $SOC_NET_NAME créé et démarré."
fi

# ─── 5. Dossier ISO ─────────────────────────────────────────────────────────
mkdir -p "$ISO_DIR"
chmod 755 "$ISO_DIR"

# ─── 6. Téléchargement Ubuntu Server 22.04 ──────────────────────────────────
ISO_FILE="$ISO_DIR/ubuntu-22.04-server.iso"
ISO_URL="https://releases.ubuntu.com/22.04/ubuntu-22.04.5-live-server-amd64.iso"

if [[ -f "$ISO_FILE" ]]; then
    success "ISO Ubuntu 22.04 déjà présente : $ISO_FILE"
else
    info "Téléchargement Ubuntu Server 22.04 (~1.5 Go)..."
    info "URL : $ISO_URL"
    wget -q --show-progress -O "$ISO_FILE" "$ISO_URL" || {
        warn "Téléchargement échoué. Lance manuellement :"
        warn "  sudo wget -O $ISO_FILE $ISO_URL"
    }
    [[ -f "$ISO_FILE" ]] && success "ISO téléchargée."
fi

# ─── 7. Cloud image Ubuntu 22.04 (pour provisioning rapide) ─────────────────
CLOUD_IMG="$ISO_DIR/ubuntu-22.04-cloud.qcow2"
CLOUD_URL="https://cloud-images.ubuntu.com/jammy/current/jammy-server-cloudimg-amd64.img"

if [[ -f "$CLOUD_IMG" ]]; then
    success "Cloud image déjà présente : $CLOUD_IMG"
else
    info "Téléchargement cloud image Ubuntu 22.04 (~600 Mo)..."
    wget -q --show-progress -O "$CLOUD_IMG" "$CLOUD_URL" || {
        warn "Cloud image non téléchargée. Lance manuellement :"
        warn "  sudo wget -O $CLOUD_IMG $CLOUD_URL"
    }
    [[ -f "$CLOUD_IMG" ]] && success "Cloud image téléchargée."
fi

# ─── 8. Script de création des VMs ──────────────────────────────────────────
cat > /usr/local/bin/rs-create-vm <<'VMEOF'
#!/usr/bin/env bash
# rs-create-vm <role> <vm-name> <ip>
# roles: client | fileserver | attacker
set -euo pipefail

ROLE="${1:-client}"
VM_NAME="${2:-rs-client-1}"
VM_IP="${3:-10.20.0.11}"
NET_NAME="ransomshield-lab"
DISK_DIR="/var/lib/libvirt/images"
ISO_DIR="/var/lib/libvirt/isos"
CLOUD_BASE="$ISO_DIR/ubuntu-22.04-cloud.qcow2"
SOC_IP="10.15.55.88"
SOC_API="http://${SOC_IP}:8000/api"
API_SECRET="ee726fa4d84f8327c81b40b1b522860a1478ed343d23c936d7b628bfc8849bb5"

[[ $EUID -ne 0 ]] && { echo "Requis: sudo rs-create-vm $ROLE $VM_NAME $VM_IP"; exit 1; }
[[ ! -f "$CLOUD_BASE" ]] && { echo "Cloud image manquante : $CLOUD_BASE"; exit 1; }

echo "=== Création VM : $VM_NAME (role=$ROLE ip=$VM_IP) ==="

VM_DISK="$DISK_DIR/${VM_NAME}.qcow2"
SEED_ISO="$DISK_DIR/${VM_NAME}-seed.iso"
CIDATA_DIR=$(mktemp -d)

# Disque VM (copie de la cloud image)
if [[ ! -f "$VM_DISK" ]]; then
    qemu-img create -f qcow2 -b "$CLOUD_BASE" -F qcow2 "$VM_DISK" 20G
    echo "[disk] $VM_DISK créé (20G, backed par cloud image)"
fi

# cloud-init user-data
cat > "$CIDATA_DIR/user-data" <<UDATA
#cloud-config
hostname: ${VM_NAME}
manage_etc_hosts: true
users:
  - name: ubuntu
    sudo: ALL=(ALL) NOPASSWD:ALL
    shell: /bin/bash
    lock_passwd: false
    passwd: "\$6\$rounds=4096\$ransom\$nxF0RFPLuS.GWXIgqjRDpyVsiqJDFlxFxHfxelBqJaVkrI4jJRZ2SKd6KvJyV10LK7BXAk4BuS33AAtJT.Hq1"
packages:
  - python3
  - python3-pip
  - python3-venv
  - git
  - curl
  - net-tools
  - iptables
package_update: true
write_files:
  - path: /opt/ransomshield/.env
    permissions: '0600'
    content: |
      RANSHIELD_API_URL=${SOC_API}
      RANSHIELD_API_SECRET=${API_SECRET}
      RANSHIELD_AGENT_NAME=${VM_NAME}
      RANSHIELD_HOST_ROLE=${ROLE}
      RANSHIELD_MONITOR_PATHS=/home,/tmp,/media
      RANSHIELD_HEARTBEAT_INTERVAL=30
      RANSHIELD_COMMAND_POLL_INTERVAL=10
runcmd:
  - mkdir -p /opt/ransomshield
  - cd /opt && git clone https://github.com/placeholder/ransomshield-agent.git || true
  - |
    if [ ! -d /opt/ransomshield/venv ]; then
      python3 -m venv /opt/ransomshield/venv
      /opt/ransomshield/venv/bin/pip install psutil requests python-dotenv watchdog 2>/dev/null || true
    fi
  - echo "VM ${VM_NAME} initialisée."
UDATA

cat > "$CIDATA_DIR/meta-data" <<MDATA
instance-id: ${VM_NAME}
local-hostname: ${VM_NAME}
MDATA

# Génération seed ISO
genisoimage -output "$SEED_ISO" -volid cidata -joliet -rock \
    "$CIDATA_DIR/user-data" "$CIDATA_DIR/meta-data" 2>/dev/null

rm -rf "$CIDATA_DIR"
echo "[cloud-init] Seed ISO : $SEED_ISO"

# Démarrage VM
MAC="52:54:00:aa:$(printf '%02x' $((RANDOM % 256))):$(printf '%02x' $((RANDOM % 256)))"

virt-install \
    --name "$VM_NAME" \
    --ram 1024 \
    --vcpus 1 \
    --os-variant ubuntu22.04 \
    --disk "path=$VM_DISK,format=qcow2" \
    --disk "path=$SEED_ISO,device=cdrom" \
    --network "network=${NET_NAME},mac=${MAC}" \
    --graphics none \
    --console pty,target_type=serial \
    --import \
    --noautoconsole

echo "=== VM $VM_NAME créée et démarrée ==="
echo "Connexion : sudo virsh console $VM_NAME  (login: ubuntu / ransomshield2026)"
echo "IP attendue : $VM_IP"
VMEOF

chmod +x /usr/local/bin/rs-create-vm

# ─── 9. Script d'installation agent sur VM ──────────────────────────────────
cat > /usr/local/bin/rs-deploy-agent <<'AGEOF'
#!/usr/bin/env bash
# rs-deploy-agent <vm-ip>
# Copie et installe l'agent RansomShield sur une VM déjà accessible en SSH
set -euo pipefail

VM_IP="${1:?Usage: rs-deploy-agent <vm-ip>}"
REPO_DIR="/home/$(logname 2>/dev/null || echo $SUDO_USER)/ransomshield"
AGENT_DIR="$REPO_DIR/agent-python"

echo "=== Déploiement agent sur $VM_IP ==="

# Copie des fichiers agent
ssh -o StrictHostKeyChecking=no "ubuntu@$VM_IP" "mkdir -p /opt/ransomshield"
scp -o StrictHostKeyChecking=no \
    "$AGENT_DIR/ransomshield_host_agent.py" \
    "$AGENT_DIR/requirements.txt" \
    "$AGENT_DIR/ransomshield-agent.service" \
    "ubuntu@$VM_IP:/opt/ransomshield/"

# Installation sur la VM
ssh -o StrictHostKeyChecking=no "ubuntu@$VM_IP" << 'REMOTE'
cd /opt/ransomshield
python3 -m venv venv 2>/dev/null || true
venv/bin/pip install -q -r requirements.txt
sudo cp ransomshield-agent.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now ransomshield-agent
echo "Agent installé et démarré."
REMOTE

echo "=== Agent déployé sur $VM_IP ==="
echo "Status : ssh ubuntu@$VM_IP 'sudo systemctl status ransomshield-agent'"
AGEOF

chmod +x /usr/local/bin/rs-deploy-agent

# ─── 10. Résumé ─────────────────────────────────────────────────────────────
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║        RansomShield — KVM Lab prêt                   ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${CYAN}Réseau lab :${NC} $SOC_NET_NAME ($SOC_NET_CIDR)"
echo -e "${CYAN}Bridge     :${NC} $SOC_BRIDGE"
echo ""
echo -e "${CYAN}VMs à créer :${NC}"
echo "  sudo rs-create-vm client     rs-client-1   10.20.0.11"
echo "  sudo rs-create-vm client     rs-client-2   10.20.0.12"
echo "  sudo rs-create-vm fileserver rs-fileserver  10.20.0.21"
echo "  sudo rs-create-vm attacker   rs-attacker    10.20.0.31"
echo ""
echo -e "${CYAN}Déployer l'agent sur une VM :${NC}"
echo "  sudo rs-deploy-agent 10.20.0.11"
echo ""
echo -e "${CYAN}Console VM :${NC}"
echo "  virsh console rs-client-1   (login: ubuntu / ransomshield2026)"
echo ""
echo -e "${YELLOW}NOTE :${NC} Reconnecte-toi pour activer les groupes kvm/libvirt"
echo -e "${YELLOW}       ou : exec newgrp libvirt${NC}"
echo ""
