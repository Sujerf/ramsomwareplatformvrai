import json
import os
import socket
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


load_dotenv()

API_URL = os.getenv("RANSHIELD_API_URL", "http://127.0.0.1:8000/api").rstrip("/")
API_SECRET = os.getenv("RANSHIELD_API_SECRET", "")
AGENT_NAME = os.getenv("RANSHIELD_AGENT_NAME", socket.gethostname())
HOST_ROLE = os.getenv("RANSHIELD_HOST_ROLE", "client")

API_HEADERS = {
    "Accept": "application/json",
    "X-Agent-Secret": API_SECRET,
}

MONITOR_MODE = os.getenv("RANSHIELD_MONITOR_MODE", "host")
HEARTBEAT_INTERVAL = int(os.getenv("RANSHIELD_HEARTBEAT_INTERVAL", "30"))
SCAN_INTERVAL = int(os.getenv("RANSHIELD_SCAN_INTERVAL", "5"))

ENABLE_FILE_MONITOR = os.getenv("RANSHIELD_ENABLE_FILE_MONITOR", "true").lower() == "true"
ENABLE_PROCESS_MONITOR = os.getenv("RANSHIELD_ENABLE_PROCESS_MONITOR", "true").lower() == "true"
ENABLE_NETWORK_CONTEXT = os.getenv("RANSHIELD_ENABLE_NETWORK_CONTEXT", "true").lower() == "true"

MONITOR_PATHS = [
    Path(p.strip()).expanduser()
    for p in os.getenv("RANSHIELD_MONITOR_PATHS", "/home,/tmp,/media,/mnt").split(",")
    if p.strip()
]

EXCLUDED_PARTS = [
    p.strip()
    for p in os.getenv(
        "RANSHIELD_EXCLUDED_PATHS",
        "/proc,/sys,/dev,/run,/snap,/var/lib,node_modules,vendor,.git,venv,__pycache__",
    ).split(",")
    if p.strip()
]

STATE_FILE = Path(".ransomshield_host_agent_state.json")

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
    "openssl",
    "gpg",
    "cryptsetup",
    "encfs",
    "rclone",
    "7z",
    "zip",
    "tar",
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
    if STATE_FILE.exists():
        try:
            return json.loads(STATE_FILE.read_text())
        except Exception:
            return {}

    return {}


def save_state(state: dict[str, Any]) -> None:
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
    return {
        "hostname": socket.gethostname(),
        "ip_address": get_local_ip(),
        "cpu_count": psutil.cpu_count(),
        "ram_total_gb": round(psutil.virtual_memory().total / (1024**3), 2),
        "disk_root_percent": psutil.disk_usage("/").percent,
        "boot_time": psutil.boot_time(),
        "network": get_network_context(),
    }


def enroll_agent() -> str:
    state = load_state()

    if state.get("agent_uuid"):
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
            "inventory": host_inventory(),
        },
    }

    response = requests.post(
        f"{API_URL}/agent/enroll",
        json=payload,
        headers=API_HEADERS,
        timeout=12,
    )

    response.raise_for_status()

    data = response.json()
    agent_uuid = data["agent"]["agent_uuid"]

    state["agent_uuid"] = agent_uuid
    state["api_url"] = API_URL
    state["agent_name"] = AGENT_NAME
    save_state(state)

    print(f"[ENROLL] Agent enrôlé : {agent_uuid}")

    return agent_uuid


def heartbeat(agent_uuid: str) -> None:
    payload = {
        "agent_uuid": agent_uuid,
        "hostname": socket.gethostname(),
        "ip_address": get_local_ip(),
        "metadata": {
            "source": "ransomshield_host_agent",
            "cpu": psutil.cpu_percent(interval=0.2),
            "ram": psutil.virtual_memory().percent,
            "disk": psutil.disk_usage("/").percent,
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
    normalized = str(path)

    for part in EXCLUDED_PARTS:
        if part and part in normalized:
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
        print("[RATE-LIMIT] Trop d’événements, envoi temporairement limité.")
        return

    ext = file_extension(path)
    metadata = {
        "source": "ransomshield_host_agent",
        "monitor_mode": MONITOR_MODE,
        "cpu": psutil.cpu_percent(interval=0.1),
        "ram": psutil.virtual_memory().percent,
        "disk": psutil.disk_usage("/").percent,
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


def main() -> None:
    print("=== RansomShield Host Agent ===")
    print(f"API       : {API_URL}")
    print(f"Mode      : {MONITOR_MODE}")
    print(f"Hostname  : {socket.gethostname()}")
    print(f"IP        : {get_local_ip()}")
    print(f"Paths     : {[str(p) for p in MONITOR_PATHS]}")

    agent_uuid = enroll_agent()
    heartbeat(agent_uuid)

    observers = start_file_observers(agent_uuid)

    last_heartbeat = 0
    last_process_scan = 0

    try:
        while True:
            now = time.time()

            if now - last_heartbeat >= HEARTBEAT_INTERVAL:
                heartbeat(agent_uuid)
                last_heartbeat = now

            if ENABLE_PROCESS_MONITOR and now - last_process_scan >= SCAN_INTERVAL:
                monitor_processes(agent_uuid)
                last_process_scan = now

            time.sleep(1)

    except KeyboardInterrupt:
        print("[STOP] Arrêt demandé.")

    finally:
        for observer in observers:
            observer.stop()

        for observer in observers:
            observer.join()


if __name__ == "__main__":
    main()
