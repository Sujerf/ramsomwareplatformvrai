#!/bin/bash
DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"

# Libère le port si déjà utilisé
fuser -k 8765/tcp 2>/dev/null

python3 -m http.server 8765 &
SERVER_PID=$!
sleep 0.8

xdg-open "http://localhost:8765/dual_viewer.html"

echo "Serveur lancé (PID $SERVER_PID). Appuyez sur Ctrl+C pour arrêter."
wait $SERVER_PID
