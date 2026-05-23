# Architecture globale — RansomShield

## Vue d’ensemble

RansomShield est composé de quatre blocs principaux :

1. Console SOC Laravel
2. Agent Python
3. Outils de découverte réseau
4. Module de démonstration contrôlée

## 1. Console SOC Laravel

La console SOC centralise :

- les agents ;
- les événements ;
- les alertes ;
- les incidents ;
- les actions de protection ;
- les décisions administrateur ;
- les notifications ;
- les paramètres ;
- les règles ;
- les seuils ;
- les politiques.

Elle ne doit pas mélanger les responsabilités des pages.

## 2. Agent Python

L’agent Python est installé sur les machines surveillées.

Il observe les événements fichiers et transmet les données au backend.

Il doit continuer à fonctionner même si le serveur SOC est temporairement indisponible grâce à une file locale.

## 3. Outils de découverte réseau

Les outils réseau détectent :

- la machine SOC ;
- les interfaces réseau ;
- les réseaux locaux ;
- les hôtes présents sur le réseau.

Les hôtes ne sont jamais enrôlés automatiquement sans validation administrateur.

## 4. Module de démonstration contrôlée

La démonstration permet de générer des événements simulant un comportement ransomware sans déployer de vrai ransomware.

Tout événement généré dans ce cadre doit être clairement identifié comme simulation contrôlée.

## Flux principal

Agent Python  
→ événements fichiers  
→ API Laravel  
→ moteur de détection  
→ score de risque  
→ alerte  
→ incident  
→ politique de protection  
→ action proposée ou exécutée  
→ notification  
→ décision administrateur  
→ timeline  
→ résolution, faux positif ou réouverture

## Principe de séparation

Chaque page Laravel a une responsabilité unique.

Le dashboard observe.

Les alertes gèrent les alertes.

Les incidents gèrent les incidents.

La file d’approbation gère uniquement les actions en attente de validation humaine.

Les paramètres modifient uniquement la configuration système.
