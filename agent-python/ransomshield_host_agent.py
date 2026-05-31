import json
import os
import platform
import re
import signal
import socket
import subprocess
import threading
import time
import uuid
from collections import defaultdict, deque
from pathlib import Path
from typing import Any

import psutil
import requests
from dotenv import load_dotenv
from watchdog.events import FileSystemEventHandler
from watchdog.observers import Observer


# Chemin explicite vers le .env (dossier du script) + override=True
# pour que le fichier .env prenne toujours la priorité sur les variables
# d'environnement système (fix Windows : load_dotenv() sans argument pouvait
# remonter l'arborescence et trouver un mauvais fichier, ou ne pas écraser
# des variables déjà définies dans l'env Windows).
load_dotenv(dotenv_path=Path(__file__).parent / ".env", override=True)

# ── Détection OS ──────────────────────────────────────────────────────────────
IS_WINDOWS = platform.system() == "Windows"
IS_MACOS   = platform.system() == "Darwin"
OS_NAME    = platform.system()   # "Windows", "Linux", "Darwin"

API_URL = os.getenv("RANSHIELD_API_URL", "http://127.0.0.1:8000/api").rstrip("/")
API_SECRET = os.getenv("RANSHIELD_API_SECRET", "")
AGENT_UUID_OVERRIDE = os.getenv("RANSHIELD_AGENT_UUID", "")        # UUID pré-enrôlé depuis la console SOC
ENROLLMENT_TOKEN = os.getenv("RANSHIELD_ENROLLMENT_TOKEN", "")     # Token à usage unique, valable 48h
AGENT_NAME = os.getenv("RANSHIELD_AGENT_NAME", socket.gethostname())
HOST_ROLE = os.getenv("RANSHIELD_HOST_ROLE", "client")

API_HEADERS = {
    "Accept": "application/json",
    "X-Agent-Secret": API_SECRET,
}

MONITOR_MODE = os.getenv("RANSHIELD_MONITOR_MODE", "host")
HEARTBEAT_INTERVAL = int(os.getenv("RANSHIELD_HEARTBEAT_INTERVAL", "30"))
SCAN_INTERVAL = int(os.getenv("RANSHIELD_SCAN_INTERVAL", "5"))
COMMAND_POLL_INTERVAL = int(os.getenv("RANSHIELD_COMMAND_POLL_INTERVAL", "10"))

ENABLE_FILE_MONITOR = os.getenv("RANSHIELD_ENABLE_FILE_MONITOR", "true").lower() == "true"
ENABLE_PROCESS_MONITOR = os.getenv("RANSHIELD_ENABLE_PROCESS_MONITOR", "true").lower() == "true"
ENABLE_NETWORK_CONTEXT = os.getenv("RANSHIELD_ENABLE_NETWORK_CONTEXT", "true").lower() == "true"

# ── Chemins à surveiller (dépend de l'OS) ────────────────────────────────────
#
# Windows : on surveille uniquement C:\Users pour les dossiers utilisateur.
#   C:\Program Files, C:\Program Files (x86) et C:\ProgramData sont exclus par
#   défaut car ils génèrent des centaines d'événements légitimes par heure
#   (mises à jour, antivirus, services système, apps en arrière-plan).
#   Les fichiers ransomware cibles (Documents, Desktop, Downloads...) sont
#   tous sous C:\Users — c'est suffisant pour la détection.
#
_DEFAULT_MONITOR_PATHS = (
    r"C:\Users"
    if IS_WINDOWS
    else "/Users,/Volumes"           # macOS — dossiers utilisateurs + volumes montés
    if IS_MACOS
    # /tmp exclus par défaut — trop de faux positifs (IDE, outils, builds)
    else "/home,/media,/mnt,/opt,/srv"
)

MONITOR_PATHS = [
    Path(p.strip()).expanduser()
    for p in os.getenv("RANSHIELD_MONITOR_PATHS", _DEFAULT_MONITOR_PATHS).split(",")
    if p.strip()
]

# ── Chemins exclus (dépend de l'OS) ──────────────────────────────────────────
_DEFAULT_EXCLUDED_PATHS = (
    # ── AppData entier exclu ──────────────────────────────────────────────────
    # AppData (Local, Roaming, LocalLow) contient exclusivement des données
    # applicatives : caches navigateurs, bases SQLite d'apps, journaux système,
    # profils Teams/WhatsApp/Outlook... Ces chemins génèrent plusieurs centaines
    # d'événements légitimes par heure et ne sont PAS les cibles primaires des
    # ransomwares (qui visent Documents, Desktop, Downloads, OneDrive, partages).
    # Seule exception : si un ransomware y dépose une note de rançon — mais la
    # détection de ransom_note_detected fonctionne par nom de fichier (README,
    # DECRYPT...) indépendamment du chemin.
    r"AppData\Local,AppData\Roaming,AppData\LocalLow,"
    # Système Windows / ProgramData — même raisonnement
    r"C:\Windows\System32,C:\Windows\SysWOW64,C:\Windows\WinSxS,"
    r"C:\ProgramData,"
    # Outils de développement et IDE
    r"node_modules,vendor,.git,venv,__pycache__,"
    # Temporaires Windows
    r"\Temp,\tmp"
    if IS_WINDOWS
    else
    # Système macOS toujours exclus
    "/private,/System,/Library/Caches,/Library/Logs,"
    "/var/folders,/var/tmp,/private/tmp,/private/var,"
    # Outils de développement et IDE
    "node_modules,vendor,.git,venv,__pycache__,"
    # Caches utilisateur macOS
    ".cache,.npm,.cargo,.rustup,Library/Caches,Library/Logs,"
    # Outils IA
    ".claude,.cursor,.vscode"
    if IS_MACOS
    else
    # Chemins système Linux toujours exclus
    "/proc,/sys,/dev,/run,/snap,/var/lib,/var/cache,/var/log,"
    # Outils de développement et IDE — génèrent de l'I/O légitime intense
    "node_modules,vendor,.git,venv,__pycache__,"
    # Fichiers temporaires et caches système
    "/tmp,/var/tmp,.cache,.npm,.cargo,.rustup,"
    # Fichiers de session / outils IA
    ".claude,.cursor,.vscode,claude-1000,claude-code"
)

EXCLUDED_PARTS = [
    p.strip()
    for p in os.getenv("RANSHIELD_EXCLUDED_PATHS", _DEFAULT_EXCLUDED_PATHS).split(",")
    if p.strip()
]

# ── Chemin racine disque (pour disk_usage) ────────────────────────────────────
_DISK_ROOT = "C:\\" if IS_WINDOWS else "/"

STATE_FILE = Path(__file__).parent / ".ransomshield_host_agent_state.json"
_state_lock = threading.Lock()

SENSITIVE_EXTENSIONS = {
    "locked",
    "encrypted",
    "crypt",
    "crypto",
    "enc",
    "pay",
    "ryk",
    "lockbit",
    "blackcat",
}

RANSOM_NOTE_KEYWORDS = {
    "readme",
    "decrypt",
    "recover",
    "restore",
    "ransom",
    "how_to_decrypt",
    "instructions",
}

SUSPICIOUS_PROCESS_KEYWORDS = {
    # Outils de chiffrement / exfiltration réels — rare sur un poste utilisateur
    "openssl",
    "gpg",
    "cryptsetup",
    "encfs",
    "rclone",
    # Outils d'exfiltration réseau connus des ransomwares
    "psexec",
    "cobaltstrike",
    "meterpreter",
    "mimikatz",
    "wce.exe",
    # Chiffrement connu associé à des ransomwares
    "veracrypt",
    # Suppression de shadow copies (technique de destruction des sauvegardes)
    "vssadmin",
    # NOTE : zip, 7z, tar sont des outils légitimes — retirés pour éviter
    # les faux positifs sur les sauvegardes et compressions normales.
}


class RateLimiter:
    def __init__(self, max_events: int = 80, window_seconds: int = 60):
        self.max_events = max_events
        self.window_seconds = window_seconds
        self.events = deque()

    def allow(self) -> bool:
        now = time.time()

        while self.events and now - self.events[0] > self.window_seconds:
            self.events.popleft()

        if len(self.events) >= self.max_events:
            return False

        self.events.append(now)
        return True


event_limiter = RateLimiter(max_events=100, window_seconds=60)
rename_tracker: dict[str, deque] = defaultdict(deque)
process_seen: set[int] = set()


def load_state() -> dict[str, Any]:
    with _state_lock:
        if STATE_FILE.exists():
            try:
                return json.loads(STATE_FILE.read_text())
            except Exception:
                return {}
    return {}


def save_state(state: dict[str, Any]) -> None:
    with _state_lock:
        STATE_FILE.write_text(json.dumps(state, indent=2, ensure_ascii=False))


def get_local_ip() -> str:
    try:
        sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        sock.connect(("8.8.8.8", 80))
        ip = sock.getsockname()[0]
        sock.close()
        return ip
    except Exception:
        return "127.0.0.1"


def get_network_context() -> dict[str, Any]:
    if not ENABLE_NETWORK_CONTEXT:
        return {}

    addrs = psutil.net_if_addrs()
    stats = psutil.net_if_stats()

    interfaces = []

    for name, addresses in addrs.items():
        ipv4 = [
            addr.address
            for addr in addresses
            if getattr(addr, "family", None).name == "AF_INET"
        ]

        mac = [
            addr.address
            for addr in addresses
            if getattr(addr, "family", None).name in {"AF_PACKET", "AF_LINK"}
        ]

        interfaces.append(
            {
                "name": name,
                "ipv4": ipv4,
                "mac": mac[0] if mac else None,
                "is_up": stats.get(name).isup if name in stats else None,
            }
        )

    return {
        "local_ip": get_local_ip(),
        "interfaces": interfaces,
    }


def host_inventory() -> dict[str, Any]:
    try:
        disk_pct = psutil.disk_usage(_DISK_ROOT).percent
    except Exception:
        disk_pct = 0

    return {
        "hostname": socket.gethostname(),
        "ip_address": get_local_ip(),
        "os": OS_NAME,
        "os_version": platform.version(),
        "cpu_count": psutil.cpu_count(),
        "ram_total_gb": round(psutil.virtual_memory().total / (1024**3), 2),
        "disk_root_percent": disk_pct,
        "boot_time": psutil.boot_time(),
        "network": get_network_context(),
    }


def _apply_api_key(key: str) -> None:
    """Injecte la clé API per-agent dans les headers globaux (mutation en place)."""
    API_HEADERS["X-Agent-Secret"] = key


def enroll_agent() -> str:
    state = load_state()

    # Priorité 1 : UUID déjà en état local (déjà enrôlé)
    if state.get("agent_uuid"):
        # Restaure la clé per-agent si elle est sauvegardée
        if state.get("agent_api_key"):
            _apply_api_key(state["agent_api_key"])
        return state["agent_uuid"]

    payload = {
        "agent_name": AGENT_NAME,
        "hostname": socket.gethostname(),
        "ip_address": get_local_ip(),
        "host_role": HOST_ROLE,
        "metadata": {
            "source": "ransomshield_host_agent",
            "agent_local_id": str(uuid.uuid4()),
            "monitor_mode": MONITOR_MODE,
            "os": OS_NAME,
            "inventory": host_inventory(),
        },
    }

    # UUID pré-enrôlé depuis le .env : permet à l'API de retrouver le record
    if AGENT_UUID_OVERRIDE:
        payload["agent_uuid"] = AGENT_UUID_OVERRIDE

    # Token d'enrôlement (usage unique, fourni par le bootstrap)
    if ENROLLMENT_TOKEN:
        payload["enrollment_token"] = ENROLLMENT_TOKEN

    # L'endpoint /enroll n'est pas protégé par agent.secret (pas de clé encore)
    # On envoie la requête sans le secret global pour éviter tout rejet
    enroll_headers = {k: v for k, v in API_HEADERS.items() if k != "X-Agent-Secret"}
    enroll_headers["Accept"] = "application/json"

    response = requests.post(
        f"{API_URL}/agent/enroll",
        json=payload,
        headers=enroll_headers,
        timeout=12,
    )

    response.raise_for_status()

    data = response.json()
    agent_data = data["agent"]
    agent_uuid = agent_data["agent_uuid"]

    # ── Clé API per-agent ─────────────────────────────────────────────────────
    # L'API génère une clé unique à l'enrôlement et la renvoie une seule fois.
    # On la stocke dans le state local et on l'applique immédiatement aux headers.
    api_key = agent_data.get("agent_api_key")
    if api_key:
        _apply_api_key(api_key)
        state["agent_api_key"] = api_key
        print(f"[ENROLL] Clé API per-agent reçue et stockée.")
    else:
        print(f"[ENROLL] Aucune clé per-agent dans la réponse (agent déjà enrôlé ?).")

    state["agent_uuid"] = agent_uuid
    state["api_url"] = API_URL
    state["agent_name"] = AGENT_NAME
    save_state(state)

    print(f"[ENROLL] Agent enrôlé : {agent_uuid}")

    return agent_uuid


def heartbeat(agent_uuid: str) -> None:
    try:
        disk_pct = psutil.disk_usage(_DISK_ROOT).percent
    except Exception:
        disk_pct = 0

    payload = {
        "agent_uuid": agent_uuid,
        "hostname": socket.gethostname(),
        "ip_address": get_local_ip(),
        "metadata": {
            "source": "ransomshield_host_agent",
            "os": OS_NAME,
            "cpu": psutil.cpu_percent(interval=0.2),
            "ram": psutil.virtual_memory().percent,
            "disk": disk_pct,
            "inventory": host_inventory(),
        },
    }

    try:
        response = requests.post(
            f"{API_URL}/agent/heartbeat",
            json=payload,
            headers=API_HEADERS,
            timeout=10,
        )
        response.raise_for_status()
        print("[HEARTBEAT] OK")
    except Exception as exc:
        print(f"[HEARTBEAT] Erreur : {exc}")


def should_ignore(path: str) -> bool:
    # Sur Windows les chemins sont case-insensitive — normaliser en minuscules
    normalized = str(path).lower() if IS_WINDOWS else str(path)

    for part in EXCLUDED_PARTS:
        if part and part.lower() in normalized:
            return True

    return False


def file_extension(path: str) -> str | None:
    suffix = Path(path).suffix

    if not suffix:
        return None

    return suffix.lstrip(".").lower()


def looks_like_ransom_note(path: str) -> bool:
    name = Path(path).name.lower()

    return any(keyword in name for keyword in RANSOM_NOTE_KEYWORDS)


def send_event(
    agent_uuid: str,
    event_type: str,
    path: str,
    extra_metadata: dict[str, Any] | None = None,
    is_simulation: bool = False,
) -> None:
    if should_ignore(path):
        return

    if not event_limiter.allow():
        print("[RATE-LIMIT] Trop d'événements, envoi temporairement limité.")
        return

    try:
        disk_pct = psutil.disk_usage(_DISK_ROOT).percent
    except Exception:
        disk_pct = 0

    ext = file_extension(path)
    metadata = {
        "source": "ransomshield_host_agent",
        "monitor_mode": MONITOR_MODE,
        "os": OS_NAME,
        "cpu": psutil.cpu_percent(interval=0.1),
        "ram": psutil.virtual_memory().percent,
        "disk": disk_pct,
        "observed_by": socket.gethostname(),
        "is_sensitive_extension": ext in SENSITIVE_EXTENSIONS if ext else False,
        "looks_like_ransom_note": looks_like_ransom_note(path),
    }

    if extra_metadata:
        metadata.update(extra_metadata)

    payload = {
        "agent_uuid": agent_uuid,
        "event_type": event_type,
        "path": path,
        "file_extension": ext,
        "is_simulation": is_simulation,
        "metadata": metadata,
    }

    try:
        response = requests.post(
            f"{API_URL}/agent/events",
            json=payload,
            headers=API_HEADERS,
            timeout=10,
        )
        response.raise_for_status()

        data = response.json()
        analysis = data.get("analysis", {})

        print(
            f"[EVENT] {event_type} | {path} | "
            f"risk={analysis.get('risk_level')} score={analysis.get('score')}"
        )

    except Exception as exc:
        print(f"[EVENT] Erreur : {exc}")


def track_rename(agent_uuid: str, dest_path: str) -> None:
    parent = str(Path(dest_path).parent)
    now = time.time()

    queue = rename_tracker[parent]
    queue.append(now)

    while queue and now - queue[0] > 30:
        queue.popleft()

    if len(queue) >= 10:
        send_event(
            agent_uuid,
            "mass_rename_detected",
            parent,
            {
                "rename_count_30s": len(queue),
                "reason": "Plusieurs renommages détectés dans un délai court.",
            },
        )


class HostFileEventHandler(FileSystemEventHandler):
    def __init__(self, agent_uuid: str):
        self.agent_uuid = agent_uuid

    def on_created(self, event):
        if event.is_directory:
            return

        path = event.src_path

        if should_ignore(path):
            return

        event_type = "file_created"

        if looks_like_ransom_note(path):
            event_type = "ransom_note_created"

        send_event(self.agent_uuid, event_type, path)

    def on_modified(self, event):
        if event.is_directory:
            return

        path = event.src_path

        if should_ignore(path):
            return

        send_event(self.agent_uuid, "file_modified", path)

    def on_moved(self, event):
        if event.is_directory:
            return

        dest_path = event.dest_path

        if should_ignore(dest_path):
            return

        ext = file_extension(dest_path)

        event_type = "file_moved"

        if ext in SENSITIVE_EXTENSIONS:
            event_type = "file_encrypted_extension"

        send_event(
            self.agent_uuid,
            event_type,
            dest_path,
            {
                "src_path": event.src_path,
                "dest_path": dest_path,
                "rename_detected": True,
            },
        )

        track_rename(self.agent_uuid, dest_path)


def monitor_processes(agent_uuid: str) -> None:
    # Bug X fix — purge les PIDs morts avant chaque cycle.
    #
    # Sans cette ligne, process_seen grossit sans limite (tous les PIDs éphémères
    # s'accumulent) et les PIDs recyclés par l'OS sont silencieusement ignorés :
    # si PID 1234 était un process bénin déjà vu, et que l'OS le réattribue à un
    # nouveau process suspect (openssl, gpg…), il serait sauté — faux négatif.
    #
    # intersection_update(live_pids) retire du set tous les PIDs qui n'existent
    # plus. Le prochain process portant ce PID sera évalué comme nouveau.
    # psutil.process_iter(["pid"]) est très léger (lecture /proc sans cmdline).
    live_pids = {p.pid for p in psutil.process_iter(["pid"])}
    process_seen.intersection_update(live_pids)

    for proc in psutil.process_iter(["pid", "name", "cmdline", "username", "cpu_percent", "memory_percent"]):
        try:
            pid = proc.info["pid"]

            if pid in process_seen:
                continue

            process_seen.add(pid)

            name = (proc.info.get("name") or "").lower()
            cmdline = " ".join(proc.info.get("cmdline") or []).lower()
            joined = f"{name} {cmdline}"

            if any(keyword in joined for keyword in SUSPICIOUS_PROCESS_KEYWORDS):
                send_event(
                    agent_uuid,
                    "suspicious_process_detected",
                    f"process://{pid}",
                    {
                        "process_name": proc.info.get("name"),
                        "cmdline": proc.info.get("cmdline"),
                        "username": proc.info.get("username"),
                        "cpu_percent": proc.info.get("cpu_percent"),
                        "memory_percent": proc.info.get("memory_percent"),
                    },
                )

        except (psutil.NoSuchProcess, psutil.AccessDenied, psutil.ZombieProcess):
            continue


def start_file_observers(agent_uuid: str) -> list[Observer]:
    observers: list[Observer] = []

    if not ENABLE_FILE_MONITOR:
        return observers

    handler = HostFileEventHandler(agent_uuid)

    for path in MONITOR_PATHS:
        if not path.exists():
            continue

        if should_ignore(str(path)):
            continue

        try:
            observer = Observer()
            observer.schedule(handler, str(path), recursive=True)
            observer.start()
            observers.append(observer)
            print(f"[FILE-MONITOR] Surveillance active : {path}")
        except Exception as exc:
            print(f"[FILE-MONITOR] Impossible de surveiller {path} : {exc}")

    return observers


def _soc_ip_from_url() -> str:
    match = re.search(r"https?://([^:/]+)", API_URL)
    return match.group(1) if match else "127.0.0.1"


def execute_isolation(soc_ip: str) -> None:
    """Isole l'hôte — seul le SOC reste joignable.

    Linux  : iptables — stateful, règles en mémoire.
    macOS  : pfctl (Packet Filter) — fichier de règles dans /tmp/ransomshield.pf.
             Sauvegarde de /etc/pf.conf avant modification pour rollback propre.
    Windows: netsh advfirewall — politique blockinbound/blockoutbound +
             règles explicites pour le SOC et le loopback.
    """
    if IS_WINDOWS:
        # Réinitialise toutes les règles RansomShield existantes
        subprocess.run(
            ["netsh", "advfirewall", "firewall", "delete", "rule", "name=RansomShield"],
            capture_output=True,
        )
        rules = [
            # Politique globale : tout bloquer par défaut
            ["netsh", "advfirewall", "set", "allprofiles", "firewallpolicy", "blockinbound,blockoutbound"],
            # Loopback entrant / sortant
            ["netsh", "advfirewall", "firewall", "add", "rule",
             "name=RansomShield Loopback In", "dir=in", "action=allow", "remoteip=127.0.0.1"],
            ["netsh", "advfirewall", "firewall", "add", "rule",
             "name=RansomShield Loopback Out", "dir=out", "action=allow", "remoteip=127.0.0.1"],
            # SOC entrant / sortant
            ["netsh", "advfirewall", "firewall", "add", "rule",
             f"name=RansomShield SOC In", "dir=in", "action=allow", f"remoteip={soc_ip}"],
            ["netsh", "advfirewall", "firewall", "add", "rule",
             f"name=RansomShield SOC Out", "dir=out", "action=allow", f"remoteip={soc_ip}"],
        ]
        for rule in rules:
            subprocess.run(rule, check=True, capture_output=True)
        print(f"[ISOLATION] Hôte isolé (Windows) — SOC {soc_ip} autorisé uniquement.")

    elif IS_MACOS:
        # ── macOS : pfctl (Packet Filter) ────────────────────────────────────
        # On sauvegarde les règles actives avant toute modification pour
        # permettre un rollback propre via execute_rollback_isolation().
        pf_backup = "/tmp/ransomshield_pf_backup.conf"
        pf_rules  = "/tmp/ransomshield_isolation.pf"

        # 1. Sauvegarder les règles actuelles
        backup_result = subprocess.run(
            ["pfctl", "-s", "rules"],
            capture_output=True, text=True
        )
        with open(pf_backup, "w") as f:
            f.write(backup_result.stdout or "# no existing rules\n")

        # 2. Écrire le jeu de règles d'isolation
        ruleset = (
            "# RansomShield — isolation réseau (généré automatiquement)\n"
            "# Loopback toujours autorisé\n"
            "set skip on lo0\n"
            "\n"
            f"# SOC ({soc_ip}) — communication bidirectionnelle autorisée\n"
            f"pass in  quick inet from {soc_ip} to any\n"
            f"pass out quick inet from any to {soc_ip}\n"
            "\n"
            "# Tout le reste est bloqué\n"
            "block in  all\n"
            "block out all\n"
        )
        with open(pf_rules, "w") as f:
            f.write(ruleset)

        # 3. Activer pfctl et charger les règles
        subprocess.run(["pfctl", "-e"], capture_output=True)          # enable (idempotent)
        subprocess.run(["pfctl", "-f", pf_rules], check=True, capture_output=True)
        print(f"[ISOLATION] Hôte isolé (macOS/pfctl) — SOC {soc_ip} autorisé uniquement.")
        print(f"[ISOLATION] Backup des règles pf dans {pf_backup}")

    else:
        # ── Linux : iptables ─────────────────────────────────────────────────
        # Sauvegarder les règles existantes avant toute modification (Docker, VPN…)
        _IPTABLES_BACKUP = "/tmp/ransomshield_iptables_backup.rules"
        backup = subprocess.run(["iptables-save"], capture_output=True, text=True)
        with open(_IPTABLES_BACKUP, "w") as f:
            f.write(backup.stdout or "# no existing rules\n")

        rules = [
            ["iptables", "-F"],
            ["iptables", "-A", "INPUT", "-i", "lo", "-j", "ACCEPT"],
            ["iptables", "-A", "OUTPUT", "-o", "lo", "-j", "ACCEPT"],
            ["iptables", "-A", "INPUT", "-s", soc_ip, "-j", "ACCEPT"],
            ["iptables", "-A", "OUTPUT", "-d", soc_ip, "-j", "ACCEPT"],
            ["iptables", "-A", "INPUT", "-m", "state", "--state", "ESTABLISHED,RELATED", "-j", "ACCEPT"],
            ["iptables", "-P", "INPUT", "DROP"],
            ["iptables", "-P", "OUTPUT", "DROP"],
            ["iptables", "-P", "FORWARD", "DROP"],
        ]
        for rule in rules:
            subprocess.run(rule, check=True, capture_output=True)
        print(f"[ISOLATION] Hôte isolé (Linux) — SOC {soc_ip} autorisé uniquement.")
        print(f"[ISOLATION] Règles iptables originales sauvegardées dans {_IPTABLES_BACKUP}")


def execute_rollback_isolation() -> None:
    """Lève l'isolation réseau — restaure le trafic normal.

    Linux  : flush iptables + politique ACCEPT.
    macOS  : restaure le backup pfctl ou désactive pfctl.
    Windows: restaure la politique advfirewall par défaut et supprime les règles.
    """
    if IS_WINDOWS:
        subprocess.run(
            ["netsh", "advfirewall", "firewall", "delete", "rule", "name=RansomShield"],
            capture_output=True,
        )
        subprocess.run(
            ["netsh", "advfirewall", "set", "allprofiles", "firewallpolicy", "blockinbound,allowoutbound"],
            capture_output=True,
        )
        print("[ROLLBACK] Isolation levée (Windows) — règles advfirewall restaurées.")

    elif IS_MACOS:
        pf_backup = "/tmp/ransomshield_pf_backup.conf"
        if Path(pf_backup).exists():
            # Restaurer les règles sauvegardées avant l'isolation
            subprocess.run(["pfctl", "-f", pf_backup], capture_output=True)
            print(f"[ROLLBACK] Isolation levée (macOS) — règles pf restaurées depuis {pf_backup}.")
        else:
            # Pas de backup → désactiver pfctl (comportement macOS par défaut = pf désactivé)
            subprocess.run(["pfctl", "-d"], capture_output=True)
            print("[ROLLBACK] Isolation levée (macOS) — pfctl désactivé (pas de backup trouvé).")

        # Nettoyage des fichiers temporaires
        for tmp in [pf_backup, "/tmp/ransomshield_isolation.pf"]:
            try:
                Path(tmp).unlink(missing_ok=True)
            except OSError:
                pass

    else:
        # Linux : restaurer le backup iptables si disponible, sinon flush + ACCEPT
        _IPTABLES_BACKUP = "/tmp/ransomshield_iptables_backup.rules"
        if Path(_IPTABLES_BACKUP).exists():
            subprocess.run(["iptables-restore", _IPTABLES_BACKUP], capture_output=True)
            Path(_IPTABLES_BACKUP).unlink(missing_ok=True)
            print("[ROLLBACK] Isolation levée (Linux) — règles iptables originales restaurées.")
        else:
            for cmd in [
                ["iptables", "-F"],
                ["iptables", "-P", "INPUT",   "ACCEPT"],
                ["iptables", "-P", "OUTPUT",  "ACCEPT"],
                ["iptables", "-P", "FORWARD", "ACCEPT"],
            ]:
                subprocess.run(cmd, capture_output=True)
            print("[ROLLBACK] Isolation levée (Linux) — iptables flushé, politique ACCEPT.")


def execute_process_kill(pid: int) -> None:
    """Termine un processus de façon cross-platform via psutil."""
    try:
        proc = psutil.Process(pid)
        proc.kill()   # SIGKILL sur Linux, TerminateProcess() sur Windows
        print(f"[KILL] Processus {pid} terminé.")
    except psutil.NoSuchProcess:
        print(f"[KILL] Processus {pid} introuvable (déjà terminé ?).")
    except psutil.AccessDenied:
        print(f"[KILL] Accès refusé pour terminer le processus {pid}.")


def report_command_result(
    agent_uuid: str, action_id: int, success: bool, message: str | None = None
) -> None:
    try:
        response = requests.post(
            f"{API_URL}/agent/actions/{action_id}/result",
            json={"agent_uuid": agent_uuid, "success": success, "message": message},
            headers=API_HEADERS,
            timeout=10,
        )
        response.raise_for_status()
        print(f"[CMD] Résultat rapporté : action_id={action_id} success={success}")
    except Exception as exc:
        print(f"[CMD] Erreur rapport résultat : {exc}")


def execute_self_update(agent_uuid: str, action_id: int) -> tuple[bool, str]:
    """
    Télécharge la dernière version de l'agent depuis le SOC, remplace le fichier
    courant et redémarre le service.  Rapporte le résultat AVANT le redémarrage
    pour ne pas perdre la trace de l'action.

    Flux :
      1. GET /api/agent/download/ransomshield_host_agent.py  → nouveau source
      2. Écriture atomique (.tmp puis rename)
      3. Rapport du résultat au SOC (success=True)
      4. Lancement d'un subprocess détaché qui redémarre le service dans 3 s
      5. sys.exit(0) — le service redémarre avec la nouvelle version
    """
    import shutil
    import sys

    script_path = Path(__file__).resolve()
    tmp_path = script_path.with_suffix(".py.updating")

    download_url = f"{API_URL}/agent/download/ransomshield_host_agent.py"
    print(f"[UPDATE] Téléchargement depuis {download_url}")

    try:
        resp = requests.get(download_url, headers=API_HEADERS, timeout=30)
        resp.raise_for_status()
    except Exception as exc:
        return False, f"Échec du téléchargement : {exc}"

    if len(resp.content) < 1000:
        return False, f"Fichier téléchargé trop petit ({len(resp.content)} octets) — annulé."

    # Valider que le fichier téléchargé est du Python syntaxiquement correct
    # avant de remplacer le binaire en cours d'exécution.
    import py_compile, tempfile as _tf
    try:
        with _tf.NamedTemporaryFile(suffix=".py", delete=False) as _f:
            _f.write(resp.content)
            _validate_path = _f.name
        py_compile.compile(_validate_path, doraise=True)
    except py_compile.PyCompileError as exc:
        try:
            Path(_validate_path).unlink(missing_ok=True)
        except Exception:
            pass
        return False, f"Fichier téléchargé invalide (erreur de syntaxe Python) : {exc}"
    finally:
        try:
            Path(_validate_path).unlink(missing_ok=True)
        except Exception:
            pass

    try:
        tmp_path.write_bytes(resp.content)
        shutil.move(str(tmp_path), str(script_path))
    except Exception as exc:
        return False, f"Échec remplacement du fichier : {exc}"

    print(f"[UPDATE] Fichier remplacé ({len(resp.content)} octets). Redémarrage dans 3 s...")

    # Rapporter le succès AVANT de redémarrer
    report_command_result(agent_uuid, action_id, True,
                          f"Agent mis à jour ({len(resp.content)} octets). Redémarrage en cours.")

    # Lancer le redémarrage en subprocess détaché
    try:
        if IS_WINDOWS:
            subprocess.Popen(
                [
                    "powershell", "-Command",
                    "Start-Sleep 3; "
                    "Stop-ScheduledTask  -TaskName RansomShieldAgent -ErrorAction SilentlyContinue; "
                    "Start-Sleep 1; "
                    "Start-ScheduledTask -TaskName RansomShieldAgent",
                ],
                creationflags=subprocess.DETACHED_PROCESS | subprocess.CREATE_NEW_PROCESS_GROUP,
                close_fds=True,
            )
        elif IS_MACOS:
            subprocess.Popen(
                ["bash", "-c",
                 "sleep 3 && launchctl stop com.ransomshield.agent "
                 "&& launchctl start com.ransomshield.agent"],
                start_new_session=True,
                close_fds=True,
            )
        else:
            subprocess.Popen(
                ["bash", "-c", "sleep 3 && systemctl restart ransomshield-agent"],
                start_new_session=True,
                close_fds=True,
            )
    except Exception as exc:
        print(f"[UPDATE] Avertissement : impossible de lancer le redémarrage automatique : {exc}")
        print("[UPDATE] Redémarrez le service manuellement.")

    # Sortie propre — le subprocess ci-dessus prend le relais
    sys.exit(0)


def poll_commands(agent_uuid: str) -> None:
    try:
        response = requests.get(
            f"{API_URL}/agent/pending-commands",
            params={"agent_uuid": agent_uuid},
            headers=API_HEADERS,
            timeout=10,
        )
        response.raise_for_status()
        commands = response.json().get("commands", [])
    except Exception as exc:
        print(f"[CMD] Erreur poll : {exc}")
        return

    for cmd in commands:
        action_id = cmd["action_id"]
        action_type = cmd["action_type"]
        payload = cmd.get("payload") or {}

        print(f"[CMD] Commande reçue : {action_type} (id={action_id})")

        success = False
        message = None

        try:
            if action_type == "isolate_host":
                soc_ip = payload.get("soc_ip") or _soc_ip_from_url()
                execute_isolation(soc_ip)
                success = True
                message = f"Hôte isolé — seul {soc_ip} reste autorisé."
            elif action_type == "kill_process":
                pid = int(payload.get("pid", 0))
                if pid > 0:
                    execute_process_kill(pid)
                    success = True
                    message = f"Processus {pid} terminé."
                else:
                    message = "PID manquant ou invalide dans le payload."
            elif action_type == "rollback_isolation":
                execute_rollback_isolation()
                success = True
                message = "Isolation réseau levée — trafic normal restauré."
            elif action_type == "update_agent":
                success, message = execute_self_update(agent_uuid, action_id)
                # execute_self_update rapporte lui-même le résultat AVANT de
                # redémarrer — on sort du loop après pour ne pas double-reporter.
                break
            else:
                message = f"Type d'action non supporté localement : {action_type}"
        except Exception as exc:
            message = str(exc)

        report_command_result(agent_uuid, action_id, success, message)


def main() -> None:
    print("=== RansomShield Host Agent ===")
    print(f"API       : {API_URL}")
    print(f"OS        : {OS_NAME} ({platform.version()})")
    print(f"Mode      : {MONITOR_MODE}")
    print(f"Hostname  : {socket.gethostname()}")
    print(f"IP        : {get_local_ip()}")
    print(f"Paths     : {[str(p) for p in MONITOR_PATHS]}")

    agent_uuid = enroll_agent()
    heartbeat(agent_uuid)

    observers = start_file_observers(agent_uuid)

    last_heartbeat = 0
    last_process_scan = 0
    last_command_poll = 0

    # Gestion du signal d'arrêt (SIGTERM sur Linux, CTRL_C_EVENT sur Windows)
    _stop = [False]

    def _handle_stop(signum, frame):
        _stop[0] = True

    signal.signal(signal.SIGTERM, _handle_stop)
    if IS_WINDOWS:
        try:
            signal.signal(signal.SIGBREAK, _handle_stop)
        except (OSError, AttributeError):
            pass

    try:
        while not _stop[0]:
            now = time.time()

            if now - last_heartbeat >= HEARTBEAT_INTERVAL:
                heartbeat(agent_uuid)
                last_heartbeat = now

            if ENABLE_PROCESS_MONITOR and now - last_process_scan >= SCAN_INTERVAL:
                monitor_processes(agent_uuid)
                last_process_scan = now

            if now - last_command_poll >= COMMAND_POLL_INTERVAL:
                poll_commands(agent_uuid)
                last_command_poll = now

            time.sleep(1)

    except KeyboardInterrupt:
        print("[STOP] Arrêt demandé.")

    finally:
        for observer in observers:
            observer.stop()

        for observer in observers:
            observer.join()

        print("[STOP] Agent arrêté proprement.")


if __name__ == "__main__":
    main()
