# RansomShield

RansomShield est une plateforme de cybersurveillance et de réponse à incidents orientée détection de comportements ransomware.

Le projet est construit pour un mémoire universitaire avec une architecture claire, démontrable et aussi réelle que possible.

## Objectif général

RansomShield doit permettre de :

1. détecter automatiquement les réseaux locaux du serveur SOC ;
2. scanner les hôtes réels d’un réseau ;
3. qualifier les hôtes découverts ;
4. approuver uniquement les hôtes autorisés à devenir agents ;
5. installer un agent Python autonome sur les machines surveillées ;
6. collecter des événements fichiers réels ;
7. appliquer des règles de détection configurables ;
8. calculer un score et un niveau de risque ;
9. créer des alertes ;
10. créer des incidents ;
11. déclencher des actions de protection ;
12. notifier l’administrateur ;
13. permettre les décisions humaines ;
14. afficher une timeline d’incident ;
15. produire un scénario de démonstration propre pour le mémoire.

## Flux logique

Agent Python  
→ événements  
→ moteur de détection  
→ alerte  
→ incident  
→ politique de protection  
→ action de protection  
→ notification UI / son / mail  
→ décision administrateur  
→ historique / timeline  
→ résolution ou réouverture

## Structure du projet

```txt
ransomshield/
├── backend-laravel/
├── collector-agent/
├── deployment/
├── network-tools/
├── docs/
├── labs/
├── storage-local/
└── README.md
