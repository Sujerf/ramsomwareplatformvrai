# Roadmap — RansomShield

## PHASE 1 — Architecture et structure

- Créer le dossier projet
- Installer Laravel
- Créer la structure collector-agent
- Créer la structure deployment
- Créer la structure network-tools
- Créer la structure docs
- Définir l’architecture générale

## PHASE 2 — Base de données

- Créer toutes les migrations
- Créer tous les modèles Eloquent
- Définir les relations
- Lancer les migrations
- Créer les seeders propres
- Vérifier la base

## PHASE 3 — Routes et contrôleurs Laravel

- Créer les routes web
- Créer les routes API agent
- Créer les contrôleurs page par page

## PHASE 4 — Services métier

- AgentRiskService
- NotificationService
- ProtectionDecisionService
- NetworkDiscoveryService

## PHASE 5 — Interface Blade

- Layout principal
- Dashboard
- Réseaux
- Hôtes découverts
- Machines surveillées
- Alertes
- Incidents
- Timeline
- Actions
- File d’approbation
- Règles
- Seuils
- Politiques
- Paramètres
- Extensions

## PHASE 6 — Agent Python

- Agent principal
- Configuration JSON
- Surveillance watchdog
- Enrôlement
- Heartbeat
- Envoi événements
- File locale
- Logs
- Service systemd

## PHASE 7 — Découverte réseau réelle

- Détection machine SOC
- Détection interfaces
- Détection réseaux
- Scan hôtes
- Qualification manuelle

## PHASE 8 — Démonstration

- SOC Laravel
- VM victime
- VM attaquant démo
- Simulation contrôlée
- Timeline
- Notification
- Action protection
- Rollback

## PHASE 9 — Documentation mémoire

- Architecture
- Modèle de données
- Flux de détection
- Flux de réponse
- Scénario de test
- Limites
- Perspectives
