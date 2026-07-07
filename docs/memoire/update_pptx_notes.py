#!/usr/bin/env python3
"""
Met à jour les notes orateur du PPTX avec le discours vivant et enchaîné.
Usage : python3 update_pptx_notes.py
"""
import os
from pptx import Presentation

HERE = os.path.dirname(os.path.abspath(__file__))
PPTX = os.path.join(HERE, "presentation_soutenance.pptx")

# ─────────────────────────────────────────────────────────────────────────────
# Discours — 24 entrées, une par slide (chaîne vide = slide de transition)
# ─────────────────────────────────────────────────────────────────────────────
NOTES = [

# ── Slide 1 — OUVERTURE ──────────────────────────────────────────────────────
"""[OUVERTURE — 0:00 | ~30 sec]

Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury, bonjour.

Permettez-nous de vous exprimer notre profonde reconnaissance pour avoir répondu présents à ce rendez-vous.

Le sujet ayant fait l'objet de notre étude est intitulé : Conception et mise en place d'un système de détection et de réponse aux attaques ransomware dans un environnement contrôlé — RansomShield.

Dix minutes de présentation. Cinq minutes de démonstration live, sur des machines virtuelles réelles. Nous resterons à votre disposition pour vos questions à la fin.

→ Commençons par un coup d'œil au plan.""",

# ── Slide 2 — SOMMAIRE ───────────────────────────────────────────────────────
"""[SOMMAIRE — 0:30 | ~20 sec]

Le fil de cette présentation est simple.

D'abord le pourquoi — le contexte et les objectifs. Ensuite le comment — la conception et la réalisation. Et enfin le résultat — ce que ça donne concrètement, en démonstration.

→ Commençons par le contexte.""",

# ── Slide 3 — Transition Introduction ────────────────────────────────────────
"",

# ── Slide 4 — CONTEXTE ───────────────────────────────────────────────────────
"""[CONTEXTE — 1:00 | ~45 sec]

Imaginez : vos fichiers se renomment les uns après les autres, les sauvegardes disparaissent, et un message s'affiche — « payez, ou perdez tout ». C'est un ransomware.

L'ENISA a mesuré une hausse de 73 % de ces attaques en 2023. Sans outil dédié, il faut en moyenne 21 jours pour détecter l'incident — et le coût dépasse souvent le million de dollars.

Les cibles ne sont plus seulement les grandes entreprises : les hôpitaux, les PME, les universités sont touchés.

→ Ce contexte soulève une problématique précise.""",

# ── Slide 5 — PROBLÉMATIQUE ───────────────────────────────────────────────────
"""[PROBLÉMATIQUE — 1:45 | ~45 sec]

Le problème fondamental : les antivirus classiques détectent par signatures. Un ransomware inconnu passe à travers.

Trois défis structurent ce constat.

Premier défi : les variantes inconnues — il faut détecter le comportement, pas la signature.

Deuxième défi : le parc hétérogène — une organisation réelle gère des machines Windows, Linux, macOS. L'agent doit fonctionner sur toutes les plateformes.

Troisième défi : la réponse automatique est risquée. Couper une machine sans vérification peut paralyser un service. Le contrôle humain n'est pas une contrainte — c'est une garantie.

D'où la question centrale : comment détecter un comportement ransomware, dans un environnement contrôlé, sans déployer de vrai malware ?

→ C'est précisément ce que RansomShield cherche à faire.""",

# ── Slide 6 — OBJECTIFS ───────────────────────────────────────────────────────
"""[OBJECTIFS — 2:30 | ~35 sec]

Six objectifs concrets.

Un : surveiller les événements fichiers en temps réel.
Deux : calculer un score de risque configurable.
Trois : générer automatiquement des alertes et des incidents.
Quatre : proposer des actions de protection — avec un contrôle humain à chaque étape.
Cinq : sécuriser la console par une double authentification.
Six : valider l'ensemble sur des scénarios d'attaque réalistes.

→ Pour y répondre, le mémoire s'articule en trois chapitres — voyons d'abord les fondements.""",

# ── Slide 7 — Transition Revue de littérature ─────────────────────────────────
"",

# ── Slide 8 — DÉFINITION ─────────────────────────────────────────────────────
"""[DÉFINITION — 3:45 | ~45 sec]

Le NIST définit le ransomware comme un logiciel malveillant qui bloque l'accès aux données — le plus souvent par chiffrement — pour ensuite réclamer une rançon.

Ce qui nous intéresse : ces attaques ne sont pas silencieuses. Elles laissent des traces avant que les dégâts soient irréversibles.

On peut observer : un renommage massif de fichiers avec une extension suspecte comme .locked, la création d'une note de rançon, la suppression des clichés instantanés VSS, ou l'utilisation d'outils système détournés — ce qu'on appelle les LOLBins.

Ce sont précisément ces signaux comportementaux que notre moteur surveille.

→ Mais pourquoi les outils existants ne suffisent-ils pas ?""",

# ── Slide 9 — SOLUTIONS EXISTANTES ────────────────────────────────────────────
"""[SOLUTIONS EXISTANTES — 4:30 | ~30 sec]

Les antivirus ? Signatures connues — inefficaces sur les variantes nouvelles.
Les SIEM ? Ils collectent des journaux, sans corrélation comportementale ciblée.
Les EDR commerciaux — CrowdStrike, SentinelOne — sont efficaces, mais hors de portée dans un contexte académique.

RansomShield se positionne dans cet espace : approche comportementale, open-source, avec un opérateur humain dans la boucle à chaque décision.

→ Concrètement, comment est-il construit ?""",

# ── Slide 10 — Transition Conception ─────────────────────────────────────────
"",

# ── Slide 11 — ARCHITECTURE ──────────────────────────────────────────────────
"""[ARCHITECTURE — 5:15 | ~45 sec]

Le flux se lit de gauche à droite, en cinq étapes.

Les agents Python surveillent les fichiers et envoient les événements à l'API Laravel. L'API authentifie et transmet au moteur de détection. Le moteur calcule un score. Quand le seuil est franchi, une alerte est créée et un incident s'ouvre automatiquement. L'opérateur consulte la console SOC et valide chaque action avant qu'elle ne s'exécute.

Règle d'or : aucune action automatique sans validation humaine.

→ Détaillons les quatre composants.""",

# ── Slide 12 — LES 4 COMPOSANTS ───────────────────────────────────────────────
"""[4 COMPOSANTS — 6:00 | ~40 sec]

Premier : l'agent Python — surveille les fichiers en temps réel, conserve une file SQLite locale pour ne rien perdre en cas de coupure réseau. Enrôlement par token à usage unique.

Deuxième : la console SOC — alertes, incidents, file d'approbation, timeline. Notifications sur quatre canaux : web, e-mail, son, webhook.

Troisième : la découverte réseau — scanne les sous-réseaux automatiquement, mais aucun agent ne s'enrôle sans validation manuelle de l'administrateur.

Quatrième : le module de simulation — rejoue jusqu'à 22 événements d'attaque, sans jamais utiliser un vrai malware.

→ Qui interagit avec le système ?""",

# ── Slide 13 — DIAGRAMME USE CASE ────────────────────────────────────────────
"""[USE CASE — 6:40 | ~25 sec]

Deux acteurs, deux rôles distincts.

L'administrateur : il configure, gère les agents, lance les simulations.

L'analyste SOC : il traite l'opérationnel — alertes, incidents, actions. Mais sans accès à la configuration.

Cette séparation garantit qu'un analyste ne peut pas modifier les seuils, et qu'un administrateur ne gère pas les incidents du quotidien.

→ Voyons comment le modèle objet structure ces entités.""",

# ── Slide 14 — DIAGRAMME DE CLASSES ───────────────────────────────────────────
"""[DIAGRAMME DE CLASSES — 7:05 | ~20 sec]

Sept entités principales.

Le pipeline se lit de gauche à droite : un Agent génère des Events que les DetectionRules évaluent. Quand le score dépasse le seuil, une Alert est créée, escaladée en Incident. Chaque Incident produit des ProtectionActions. Tout est tracé dans l'AuditLog.

→ Comment ces entités se traduisent-elles en base de données ?""",

# ── Slide 15 — MODÈLE DE DONNÉES ─────────────────────────────────────────────
"""[MODÈLE DE DONNÉES — 7:25 | ~20 sec]

Huit tables MySQL. Le pipeline se lit de gauche à droite : agent → événement → alerte → incident → action → journal d'audit.

Chaque action est signée : on sait qui l'a validée, et quand. Aucune décision n'est anonyme.

→ Passons maintenant à la réalisation concrète.""",

# ── Slide 16 — Transition Réalisation ────────────────────────────────────────
"",

# ── Slide 17 — CHOIX TECHNIQUES ───────────────────────────────────────────────
"""[CHOIX TECHNIQUES — 7:55 | ~25 sec]

Les choix techniques ont été guidés par trois critères : portabilité, légèreté, et capacité à démontrer en conditions réelles.

Backend : Laravel 11 et PHP 8.3, base MySQL, API sécurisée par une clé propre à chaque agent.
Agent : Python avec Watchdog, file locale SQLite.
Frontend : Chart.js embarqué localement — aucune dépendance CDN externe.
Infrastructure : 3 VMs KVM, double authentification activée.

→ Comment l'agent fonctionne-t-il concrètement ?""",

# ── Slide 18 — AGENT & MOTEUR ─────────────────────────────────────────────────
"""[AGENT & MOTEUR — 8:20 | ~50 sec]

Au premier démarrage, l'agent s'enrôle avec un token à usage unique — il obtient une clé API permanente. Ensuite : surveillance des dossiers avec Watchdog, et heartbeat toutes les 30 secondes.

Le moteur calcule un score cumulatif. Exemple concret : un fichier renommé .locked rapporte 80 points. Une note de rançon ajoute 55 points. Total 135 → alerte critique créée et incident ouvert automatiquement. En moins de 5 secondes.

→ Côté analyste, qu'est-ce que la console offre ?""",

# ── Slide 19 — CONSOLE SOC ────────────────────────────────────────────────────
"""[CONSOLE SOC — 9:10 | ~30 sec]

Dix pages fonctionnelles. Le dashboard se rafraîchit toutes les 30 secondes.

Chaque action de protection passe par une file d'approbation : l'analyste valide ou rejette avant toute exécution. Les notifications partent sur quatre canaux simultanés : web, e-mail, son, et webhook.

Vous allez voir tout ça dans quelques instants.

→ Mais d'abord — qu'est-ce que les tests ont donné ?""",

# ── Slide 20 — TESTS & RÉSULTATS ──────────────────────────────────────────────
"""[TESTS — 9:40 | ~25 sec]

Cinq scénarios d'attaque, de 7 à 22 événements. Détection en moins de 5 secondes à chaque fois. Zéro perte d'événement. Pipeline validé de bout en bout — de l'événement jusqu'à la timeline d'audit.

Notons honnêtement une limite : les tests ont été conduits uniquement sur Linux. Un attaquant qui ralentirait délibérément son rythme resterait moins visible.

→ Maintenant, place à la démonstration.""",

# ── Slide 21 — DÉMONSTRATION LIVE ────────────────────────────────────────────
"""[DÉMONSTRATION LIVE — 10:00 | 5 minutes hors chrono]

=== CHECKLIST (dans l'ordre) ===

1. Ouvrir http://10.20.0.1 — console SOC
2. Se connecter : identifiants + code 2FA
3. Vérifier : 3 agents en ligne (voyant vert)
4. Agents → VM-02 → Lancer simulation → "Kill chain complète" (22 évts)
5. Dashboard : observer le score monter en temps réel
6. Alertes : niveau Critique + signaux détectés
7. Incidents : ouvrir l'incident auto-créé, montrer les détails
8. File d'approbation : approuver l'isolation réseau
9. Timeline : tout est horodaté et tracé

Rester calme. Laisser le temps aux événements d'arriver. Ne pas se précipiter.

→ Revenir à la présentation pour conclure.""",

# ── Slide 22 — Transition Conclusion ─────────────────────────────────────────
"",

# ── Slide 23 — CONCLUSION & PERSPECTIVES ──────────────────────────────────────
"""[CONCLUSION — après démo | ~45 sec]

Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury, permettez-nous de vous présenter la synthèse de ce travail.

RansomShield couvre tout le cycle de traitement d'un incident : collecte, analyse, alerte, réponse, audit. Agent multi-plateforme. Console complète. Moteur réactif. Contrôle humain systématique.

Ce travail a ses limites : environnement contrôlé, réponses partiellement simulées, tests sur Linux uniquement. Nous n'avons nullement la prétention d'avoir épuisé le sujet.

Les perspectives sont claires : d'abord le chiffrement HTTPS, puis des rôles utilisateurs distincts, puis à terme du machine learning pour affiner la détection, et une intégration SIEM.

Nous vous remercions pour votre attention. Nous restons à votre disposition pour vos questions.""",

# ── Slide 24 — QUESTIONS DU JURY ──────────────────────────────────────────────
"""[QUESTIONS DU JURY — réponses prêtes]

• Pourquoi Python et pas C ou Go ?
  Watchdog exploite inotify nativement — CPU minimal, portable Linux/Windows/macOS sans recompilation.

• Comment gérez-vous les faux positifs ?
  Les seuils sont configurables. L'opérateur humain valide ou rejette chaque action avant qu'elle s'exécute — aucune isolation automatique.

• Pourquoi pas HTTPS ?
  Réseau LAN contrôlé pour ce projet. Le chiffrement HTTPS est la première perspective pour la production.

• Quelle différence avec un antivirus ?
  Un antivirus cherche des signatures connues — aveugle aux variantes nouvelles. RansomShield analyse le comportement : renommages, note de rançon, suppression VSS — indépendamment de toute signature.

• Testé sur Windows ?
  Non — limite assumée. L'agent Python est compatible Windows via Watchdog, mais non testé. C'est une priorité pour la suite.

• La solution passe-t-elle à l'échelle ?
  Testé sur 3 VMs. L'API Laravel peut monter en charge, mais des tests de performance à grande échelle restent à conduire.

Reformuler la question avant de répondre. Garder les captures d'écran prêtes.""",

]  # fin NOTES

# ─────────────────────────────────────────────────────────────────────────────
# Mise à jour du PPTX
# ─────────────────────────────────────────────────────────────────────────────

def set_notes(slide, text):
    notes_slide = slide.notes_slide
    tf = notes_slide.notes_text_frame
    # Vider les paragraphes existants (sauf le premier)
    for para in tf.paragraphs[1:]:
        p = para._p
        p.getparent().remove(p)
    lines = text.split("\n") if text else [""]
    # Premier paragraphe
    p0 = tf.paragraphs[0]
    if p0.runs:
        p0.runs[0].text = lines[0]
    else:
        p0.text = lines[0]
    # Paragraphes suivants
    for line in lines[1:]:
        para = tf.add_paragraph()
        para.text = line


prs = Presentation(PPTX)

if len(prs.slides) != len(NOTES):
    print(f"ERREUR : {len(prs.slides)} slides dans le PPTX, {len(NOTES)} textes fournis.")
    raise SystemExit(1)

for i, (slide, note_text) in enumerate(zip(prs.slides, NOTES), 1):
    set_notes(slide, note_text.strip())
    preview = "(vide)" if not note_text.strip() else note_text.strip()[:55].replace('\n', ' ')
    print(f"Slide {i:2d}: {preview}")

prs.save(PPTX)
print(f"\nOK : {len(NOTES)} slides mis à jour → {PPTX}")
