#!/usr/bin/env python3
"""
Met à jour les notes orateur du PPTX avec le speech corrigé.
"""
import sys
sys.path.insert(0, '.venv-pptx/lib/python3.12/site-packages')

from pptx import Presentation
from pptx.util import Pt
from lxml import etree

PPTX = "presentation_soutenance.pptx"

# Notes corrigées par slide (None = laisser vide)
NOTES = {
    1: """\
[OUVERTURE]

Monsieur le Président du jury, Mesdames et Messieurs les membres du jury, bonjour.

Je vous remercie pour l'occasion qui m'est donnée de présenter mon travail portant sur la conception et la mise en place d'un système de détection et de réponse aux attaques ransomware dans un environnement contrôlé : RansomShield.""",

    2: """\
[SOMMAIRE]

Ma présentation s'articule en cinq parties : le contexte et la problématique, la revue de littérature, la conception, la réalisation et les résultats, puis la conclusion. Je terminerai par une démonstration live.""",

    3: None,  # séparateur Introduction

    4: """\
[CONTEXTE]

Les organisations dépendent aujourd'hui fortement de leurs systèmes informatiques, ce qui les expose de plus en plus aux cyberattaques — en particulier au ransomware.
Selon l'ENISA, ces attaques ont augmenté de 73 % en 2023 ; sans outil dédié, il faut en moyenne 21 jours pour les détecter.""",

    5: """\
[PROBLÉMATIQUE]

La détection comportementale en temps réel des attaques ransomware, combinée à une réponse maîtrisée avec validation humaine systématique, constitue une solution efficace et démontrable pour protéger un parc de machines hétérogène sans déployer de vrai malware.

Trois défis structurent ce travail : les variantes inconnues qui contournent les antivirus, la diversité des systèmes à surveiller, et le risque d'une réponse automatique sans contrôle humain.""",

    6: """\
[OBJECTIFS]

L'objectif général est de concevoir et mettre en œuvre un système de détection et de réponse aux attaques ransomware, basé sur la surveillance comportementale des fichiers et des processus, avec une console de supervision traçable.

Pour y parvenir, six objectifs spécifiques ont structuré ce travail : surveiller les événements fichiers en temps réel, analyser les comportements et calculer un score de risque configurable, générer automatiquement les alertes, soumettre chaque action de protection à une validation humaine, sécuriser la console par une authentification forte à deux facteurs, et valider la solution sur des scénarios d'attaque représentatifs.""",

    7: None,  # séparateur Revue de littérature

    8: """\
[DÉFINITION]

Un ransomware laisse des traces comportementales avant que les dégâts soient irréversibles : extensions suspectes, note de rançon, suppression VSS, LOLBins.
Ce sont ces signaux qui forment la base de notre détection.""",

    9: """\
[SOLUTIONS]

Les antivirus ne couvrent que les variantes connues par leur signature. Les SIEM collectent des journaux, mais sans corrélation comportementale. Les EDR commerciaux sont efficaces, mais leur coût les rend inaccessibles dans un cadre académique.

RansomShield occupe cet espace : une solution open-source, comportementale, multi-plateforme, démontrable sur machines réelles, avec contrôle humain intégré.""",

    10: None,  # séparateur Analyse et Conception

    11: """\
[ARCHITECTURE]

L'architecture suit un pipeline en cinq maillons. L'agent collecte les événements sur la machine et les envoie à l'API Laravel. Le moteur de détection analyse les signaux et calcule un score de risque. Dès que ce score franchit un seuil, la console SOC génère une alerte et soumet une action à l'opérateur. C'est lui qui décide — le système n'agit jamais de manière autonome.""",

    12: """\
[4 COMPOSANTS]

RansomShield repose sur quatre composants distincts. L'agent Python surveille les fichiers en temps réel et maintient une file locale SQLite pour ne perdre aucun événement en cas de coupure réseau. La console SOC centralise la supervision, les alertes et la file d'approbation. Le module de découverte réseau détecte les nouvelles machines, mais aucune n'est enrôlée sans validation manuelle de l'administrateur. Le module de simulation rejoue des scénarios d'attaque réalistes, sans déployer de vrai malware.""",

    13: """\
[USE CASE]

Deux acteurs interagissent avec le système. L'administrateur gère le parc : il enrôle les agents, configure les règles de détection et lance les simulations. L'analyste SOC traite les incidents au quotidien : il consulte les alertes, approuve ou rejette les actions de protection, et suit la timeline d'audit. Il n'a pas accès à la configuration — cette séparation est intentionnelle.""",

    14: """\
[DIAGRAMME DE CLASSES]

Sept entités structurent le modèle objet. Le pipeline se lit de gauche à droite : un Agent génère des Events que les DetectionRules évaluent pour produire des Alerts. Ces alertes s'escaladent en Incidents, qui génèrent des ProtectionActions soumises à approbation, et des entrées dans l'AuditLog pour la traçabilité complète.""",

    15: """\
[MODÈLE DE DONNÉES]

Le modèle de données traduit directement le pipeline de traitement. Huit tables MySQL couvrent l'ensemble du cycle, de la collecte des événements jusqu'à l'archivage des décisions. La traçabilité est intégrée au schéma : chaque action de protection est liée à l'identité de l'opérateur qui l'a validée et conservée dans l'audit log.""",

    16: None,  # séparateur Réalisation

    17: """\
[CHOIX TECHNIQUES]

Les choix techniques ont été guidés par trois critères : la portabilité, la légèreté d'installation, et la capacité à démontrer le système en conditions réelles. Laravel 11 pour l'API REST et la console, Python avec Watchdog pour l'agent multi-plateforme, Chart.js intégré en local pour le frontend, et un environnement de test sur trois machines virtuelles KVM avec authentification 2FA TOTP.""",

    18: """\
[AGENT & MOTEUR DE DÉTECTION]

Pour illustrer le scoring : un fichier renommé avec l'extension .locked déclenche un signal à +80, la création d'une note de rançon ajoute +55. Le score cumulé de 135 dépasse le seuil critique fixé à 100 : une alerte est générée et un incident est ouvert automatiquement en moins de cinq secondes.""",

    19: """\
[CONSOLE SOC]

La console couvre l'ensemble du cycle opérationnel en neuf pages. Chaque action de protection passe obligatoirement par la file d'approbation : l'analyste valide ou rejette explicitement — aucune exécution silencieuse n'est possible. Les notifications sont envoyées simultanément sur quatre canaux : web, e-mail SMTP, son et webhook.""",

    20: """\
[TESTS]

Cinq scénarios d'attaque ont été exécutés, de 5 à 22 événements. Tous ont été détectés en moins de cinq secondes, sans perte d'événement. Le pipeline complet — de la collecte à l'incident — a été validé bout en bout. Une limite est à noter : les tests ont été conduits sur Linux uniquement, ce qui constitue la première perspective d'évolution du projet.""",

    21: """\
[DÉMONSTRATION LIVE]

Checklist rapide :
1. Console SOC ouverte — 3 agents en ligne
2. SSH sur rs-client-1 — renommage .locked + note de rançon
3. Revenir sur la console — alerte critique apparue en moins de 5 secondes
4. File d'approbation — approuver l'action, montrer le rollback
5. Timeline — tout est horodaté
6. Simulation kill chain complète — 22 événements""",

    22: None,  # séparateur Conclusion

    23: """\
[CONCLUSION]

RansomShield couvre l'intégralité du cycle de traitement d'un incident : collecte, analyse, alerte, réponse et audit. Ses limites sont assumées : les tests ont été conduits sur Linux, et la communication reste en HTTP sur un réseau local contrôlé. Les perspectives visent le chiffrement TLS en production, l'introduction de rôles distincts via RBAC, un moteur de détection par Machine Learning, et un alignement sur le référentiel MITRE ATT&CK.""",

    24: """\
[QUESTIONS DU JURY]

• Pourquoi Python et pas C/Go ?
  Watchdog exploite inotify nativement, CPU minimal, portable sans recompilation.

• Faux positifs ?
  Seuils configurables depuis la console. L'opérateur humain valide ou rejette chaque action.

• Pourquoi pas HTTPS ?
  LAN contrôlé ; premier axe d'évolution pour la production.

• Différence avec un antivirus ?
  Comportement, pas signature — les variantes inconnues sont couvertes.

• Testé sur Windows ?
  Non, limite assumée : Linux uniquement pour les tests.""",
}


def set_notes(slide, text):
    """Remplace le texte des notes d'une diapo."""
    if not slide.has_notes_slide:
        slide.notes_slide  # crée la notes slide si inexistante
    tf = slide.notes_slide.notes_text_frame
    # Vider tous les paragraphes existants
    for para in tf.paragraphs[1:]:
        p = para._p
        p.getparent().remove(p)
    # Écrire le nouveau texte dans le premier paragraphe
    tf.paragraphs[0].text = text


prs = Presentation(PPTX)

for i, slide in enumerate(prs.slides, start=1):
    if i in NOTES and NOTES[i] is not None:
        set_notes(slide, NOTES[i])
        print(f"✓ Slide {i} mise à jour")
    else:
        print(f"  Slide {i} ignorée (séparateur ou non définie)")

prs.save(PPTX)
print(f"\nOK : {PPTX} mis à jour.")
