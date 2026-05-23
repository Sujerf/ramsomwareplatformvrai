# Collector Agent — RansomShield

Ce dossier contient l’agent Python installé sur les machines surveillées.

## Rôle de l’agent

L’agent doit :

- surveiller les dossiers configurés ;
- détecter les créations de fichiers ;
- détecter les modifications ;
- détecter les suppressions ;
- détecter les renommages ;
- détecter les extensions suspectes ;
- détecter les notes de rançon ;
- enrichir les événements localement ;
- envoyer les événements au backend Laravel ;
- envoyer des heartbeats réguliers ;
- conserver une file d’attente locale si le serveur SOC est indisponible ;
- fonctionner comme service systemd.

## Structure

```txt
collector-agent/
├── app/
│   ├── collectors/
│   ├── services/
│   ├── api/
│   └── security/
├── config/
├── logs/
├── queue/
└── tests/
