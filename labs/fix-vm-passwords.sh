#!/usr/bin/env bash
# Recrée les seeds cloud-init avec mot de passe correct pour chaque VM
set -euo pipefail

[[ $EUID -ne 0 ]] && { echo "Lance avec : sudo bash labs/fix-vm-passwords.sh"; exit 1; }

DISK_DIR="/var/lib/libvirt/images"
PASSWORD="ransomshield2026"
SOC_IP="10.15.55.88"
SOC_API="http://${SOC_IP}:8000/api"
API_SECRET="ee726fa4d84f8327c81b40b1b522860a1478ed343d23c936d7b628bfc8849bb5"

declare -A VMS=(
    ["rs-attacker"]="attacker"
    ["rs-client-1"]="client"
    ["rs-fileserver"]="fileserver"
)

for VM_NAME in "${!VMS[@]}"; do
    ROLE="${VMS[$VM_NAME]}"
    SEED_ISO="$DISK_DIR/${VM_NAME}-seed.iso"
    CIDATA=$(mktemp -d)

    echo "=== Fix password : $VM_NAME ==="

    # Arrêt propre
    if virsh domstate "$VM_NAME" 2>/dev/null | grep -q "running"; then
        echo "  Arrêt de $VM_NAME..."
        virsh shutdown "$VM_NAME" 2>/dev/null || true
        sleep 5
        virsh destroy "$VM_NAME" 2>/dev/null || true
    fi

    # Nouveau user-data avec mot de passe en clair (cloud-init le hash lui-même)
    cat > "$CIDATA/user-data" <<UDATA
#cloud-config
hostname: ${VM_NAME}
manage_etc_hosts: true
password: ${PASSWORD}
chpasswd:
  expire: false
ssh_pwauth: true
users:
  - name: ubuntu
    sudo: ALL=(ALL) NOPASSWD:ALL
    shell: /bin/bash
    lock_passwd: false
    plain_text_passwd: ${PASSWORD}
packages:
  - python3
  - python3-pip
  - python3-venv
  - git
  - curl
  - net-tools
  - iptables
  - qemu-guest-agent
package_update: false
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
  - systemctl enable --now qemu-guest-agent || true
  - mkdir -p /opt/ransomshield
  - python3 -m venv /opt/ransomshield/venv 2>/dev/null || true
  - /opt/ransomshield/venv/bin/pip install -q psutil requests python-dotenv watchdog 2>/dev/null || true
UDATA

    cat > "$CIDATA/meta-data" <<MDATA
instance-id: ${VM_NAME}-fixed
local-hostname: ${VM_NAME}
MDATA

    # Génération seed ISO
    genisoimage -output "$SEED_ISO" -volid cidata -joliet -rock \
        "$CIDATA/user-data" "$CIDATA/meta-data" 2>/dev/null

    rm -rf "$CIDATA"
    echo "  Seed ISO recréé : $SEED_ISO"

    # Redémarrage
    virsh start "$VM_NAME"
    echo "  $VM_NAME redémarré — attends 30s que cloud-init applique le mot de passe"
    echo ""
done

echo "=================================================="
echo "Mot de passe : $PASSWORD"
echo "Login VM     : ubuntu / $PASSWORD"
echo ""
echo "Attends 30-60s puis connecte-toi depuis virt-manager."
echo "=================================================="
