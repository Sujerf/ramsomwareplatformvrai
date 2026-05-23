# Network Tools — RansomShield

Ce dossier contient les scripts Python de découverte réseau réelle.

## Rôle

Les scripts réseau doivent permettre de :

- détecter la machine SOC locale ;
- identifier son hostname ;
- identifier ses interfaces réseau ;
- détecter les réseaux locaux ;
- scanner les hôtes d’un réseau limité à /24 ;
- collecter les IP, MAC si disponible, hostname si disponible ;
- deviner certains rôles à partir du hostname ou des ports ;
- transmettre ou exporter les résultats pour validation dans Laravel.

## Scripts prévus

```txt
network-tools/scripts/
├── detect_local_host.py
├── discover_local_networks.py
└── discover_hosts.py

