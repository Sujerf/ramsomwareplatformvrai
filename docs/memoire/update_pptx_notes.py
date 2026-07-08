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

Permettez-nous de vous exprimer notre profonde gratitude pour avoir bien voulu accepter d'évaluer ce travail malgré vos agendas sûrement chargés.

Le sujet ayant fait l'objet de notre étude est intitulé : Conception et mise en place d'un système de détection et de réponse aux attaques ransomware dans un environnement contrôlé — RansomShield.

Cette présentation se déroulera en dix minutes, suivie d'une démonstration live de cinq minutes sur des machines virtuelles réelles. Nous resterons à votre entière disposition pour vos questions à l'issue de cet exposé.

→ Voici comment s'articule notre présentation.""",

# ── Slide 2 — SOMMAIRE ───────────────────────────────────────────────────────
"""[SOMMAIRE — 0:30 | ~20 sec]

Notre présentation s'articule en cinq grandes parties.

Nous aborderons dans un premier temps le contexte et la problématique qui ont motivé ce travail. Nous passerons ensuite en revue la littérature existante sur le sujet. Nous présenterons par la suite la conception et la réalisation du système. Nous exposerons les résultats obtenus avant de conclure par une synthèse et les perspectives d'évolution.

Le tout sera couronné d'une démonstration du système en conditions réelles.

→ Commençons par le contexte qui a motivé notre démarche.""",

# ── Slide 3 — Transition Introduction ────────────────────────────────────────
"",

# ── Slide 4 — CONTEXTE ───────────────────────────────────────────────────────
"""[CONTEXTE — 1:00 | ~45 sec]

Nous vivons à une époque où les organisations dépendent fortement de leurs systèmes informatiques. Cette dépendance les expose à une menace croissante : le ransomware. Il s'agit d'un logiciel malveillant qui chiffre les données d'une organisation, puis réclame une rançon pour en rétablir l'accès.

L'ENISA a enregistré une hausse de 73 % de ces attaques en 2023. Sans outil dédié, il faut en moyenne 21 jours pour détecter un tel incident, pour un coût qui dépasse souvent le million de dollars. Les cibles ne se limitent plus aux grandes entreprises : les hôpitaux, les PME et les universités sont désormais tout aussi exposés.

→ Ce constat soulève une problématique précise.""",

# ── Slide 5 — PROBLÉMATIQUE ───────────────────────────────────────────────────
"""[PROBLÉMATIQUE — 1:45 | ~45 sec]

Le problème fondamental réside dans les limites des outils traditionnels : les antivirus classiques détectent les malwares par empreinte connue et se révèlent inefficaces face aux nouvelles variantes.

Trois défis structurent ce constat.

Premier défi : les variantes inconnues. Nous avons besoin d'un système capable de détecter le comportement, et non la signature.

Deuxième défi : le parc hétérogène. Une organisation réelle gère des machines sous Windows, Linux et macOS. L'agent de surveillance doit fonctionner sur toutes ces plateformes.

Troisième défi : la réponse automatique non contrôlée est risquée. Isoler une machine sans vérification préalable peut paralyser un service critique. Le contrôle humain n'est pas une contrainte — c'est une garantie.

D'où la question centrale de notre travail : comment détecter un comportement ransomware, dans un environnement contrôlé, sans déployer de vrai malware ?

→ C'est précisément ce que RansomShield cherche à réaliser.""",

# ── Slide 6 — OBJECTIFS ───────────────────────────────────────────────────────
"""[OBJECTIFS — 2:30 | ~35 sec]

Pour répondre à cette problématique, nous nous sommes fixé six objectifs spécifiques.

Premièrement, surveiller les événements fichiers en temps réel sur les terminaux.
Deuxièmement, analyser les comportements et calculer un score de risque configurable.
Troisièmement, générer automatiquement des alertes et des incidents de sécurité.
Quatrièmement, proposer des actions de protection tout en garantissant un contrôle humain à chaque étape.
Cinquièmement, sécuriser la console par une double authentification.
Sixièmement, valider l'ensemble sur des scénarios d'attaque représentatifs.

→ Voyons maintenant les fondements théoriques qui ont guidé notre démarche.""",

# ── Slide 7 — Transition Revue de littérature ─────────────────────────────────
"",

# ── Slide 8 — DÉFINITION ─────────────────────────────────────────────────────
"""[DÉFINITION — 3:45 | ~45 sec]

Le NIST définit le ransomware comme un logiciel malveillant qui bloque l'accès aux données — le plus souvent par chiffrement — pour ensuite réclamer une rançon à la victime.

Ce qui présente un intérêt particulier pour notre travail, c'est que ces attaques ne sont pas silencieuses. Elles laissent des traces identifiables avant que les dégâts ne deviennent irréversibles.

On peut ainsi observer : un renommage massif de fichiers avec une extension suspecte telle que .locked, la création d'une note de rançon, la suppression des points de restauration système, ou encore le recours à des outils légitimes détournés — ce que l'on appelle les LOLBins.

Ce sont précisément ces signaux comportementaux que notre moteur de détection surveille.

→ Mais pourquoi les outils existants ne permettent-ils pas de répondre à ce défi ?""",

# ── Slide 9 — SOLUTIONS EXISTANTES ────────────────────────────────────────────
"""[SOLUTIONS EXISTANTES — 4:30 | ~30 sec]

Les solutions du marché présentent chacune des limites significatives.

Les antivirus classiques opèrent par reconnaissance d'empreintes connues et se révèlent aveugles aux nouvelles variantes. Les SIEM collectent des journaux d'activité sans offrir de corrélation comportementale ciblée. Quant aux EDR commerciaux — tels que CrowdStrike ou SentinelOne — ils sont efficaces mais inaccessibles pour la majorité des organisations : PME, hôpitaux, administrations, qui ne disposent pas des budgets requis.

C'est dans cet espace que se positionne RansomShield : une approche comportementale, open-source, déployable sur toute infrastructure, avec un opérateur humain dans la boucle à chaque décision.

→ Voyons maintenant comment nous avons conçu ce système.""",

# ── Slide 10 — Transition Conception ─────────────────────────────────────────
"",

# ── Slide 11 — ARCHITECTURE ──────────────────────────────────────────────────
"""[ARCHITECTURE — 5:15 | ~45 sec]

L'architecture de RansomShield repose sur un flux simple que nous pouvons lire de gauche à droite, en cinq étapes.

Les agents Python, installés sur les machines surveillées, collectent les événements fichiers et les transmettent à l'API Laravel. Chaque agent dispose d'une clé qui lui est propre. L'API authentifie et achemine les données vers le moteur de détection, qui calcule un score de risque. Lorsque le seuil configuré est franchi, une alerte est créée et un incident s'ouvre automatiquement. L'analyste SOC consulte alors la console et valide chaque action avant son exécution.

Règle d'or de notre système : aucune action n'est exécutée sans validation humaine préalable.

→ Détaillons les quatre composants qui constituent ce système.""",

# ── Slide 12 — LES 4 COMPOSANTS ───────────────────────────────────────────────
"""[4 COMPOSANTS — 6:00 | ~40 sec]

Notre système repose sur quatre composants complémentaires.

Premier composant : l'agent Python. Il surveille les fichiers en temps réel et conserve une file locale en SQLite pour ne perdre aucun événement, même en cas de coupure réseau. Son enrôlement s'effectue via un jeton à usage unique.

Deuxième composant : la console SOC. Elle centralise les alertes, les incidents, la file d'approbation et la timeline. Les notifications sont diffusées sur quatre canaux simultanés : web, e-mail, son et webhook.

Troisième composant : la découverte réseau. Elle détecte automatiquement les nouvelles machines sur le réseau, mais aucune ne peut rejoindre le système sans validation explicite de l'administrateur.

Quatrième composant : le simulateur d'attaques. Il rejoue jusqu'à vingt-deux événements d'attaque sans jamais recourir à un vrai malware.

→ Qui sont les acteurs qui interagissent avec ce système ?""",

# ── Slide 13 — DIAGRAMME USE CASE ────────────────────────────────────────────
"""[USE CASE — 6:40 | ~25 sec]

Notre système distingue deux acteurs aux rôles bien définis.

L'administrateur assure la configuration du système, la gestion des agents et le lancement des simulations.

L'analyste SOC se concentre quant à lui sur le traitement opérationnel : il visualise les alertes et les incidents, approuve ou rejette les actions de protection, et consulte la timeline d'audit. Il n'a cependant pas accès à la configuration.

Cette séparation garantit qu'un analyste ne peut pas modifier les seuils de détection, et qu'un administrateur ne gère pas les incidents au quotidien.

→ Comment les entités du système sont-elles structurées entre elles ?""",

# ── Slide 14 — DIAGRAMME DE CLASSES ───────────────────────────────────────────
"""[DIAGRAMME DE CLASSES — 7:05 | ~20 sec]

Notre modèle objet distingue sept entités principales.

Le pipeline se lit de gauche à droite : un Agent génère des Events, que les DetectionRules évaluent. Lorsque le score dépasse le seuil, une Alert est créée, puis escaladée en Incident. Chaque Incident génère des ProtectionActions. L'ensemble est tracé dans un AuditLog.

→ Comment ces entités se traduisent-elles en base de données ?""",

# ── Slide 15 — MODÈLE DE DONNÉES ─────────────────────────────────────────────
"""[MODÈLE DE DONNÉES — 7:25 | ~20 sec]

Notre modèle de données repose sur huit tables MySQL.

Le pipeline se lit de gauche à droite : agent, événement, alerte, incident, action, journal d'audit. Chaque action est signée : on sait qui l'a validée, et à quel moment. Aucune décision n'est anonyme dans notre système.

→ Passons maintenant à la réalisation concrète.""",

# ── Slide 16 — Transition Réalisation ────────────────────────────────────────
"",

# ── Slide 17 — CHOIX TECHNIQUES ───────────────────────────────────────────────
"""[CHOIX TECHNIQUES — 7:55 | ~25 sec]

Nos choix techniques ont été guidés par trois critères principaux : la portabilité, la légèreté et la capacité à démontrer le système en conditions réelles.

Pour le backend, nous avons retenu Laravel 11 avec PHP 8.3, une base de données MySQL et une API sécurisée par une clé propre à chaque agent. Pour l'agent de surveillance, nous avons opté pour Python avec la bibliothèque Watchdog et une file locale SQLite. Pour le frontend, Chart.js a été intégré localement, sans aucune dépendance CDN externe. L'infrastructure de test est constituée de trois machines virtuelles KVM avec authentification à double facteur activée.

→ Voyons maintenant comment l'agent et le moteur de détection fonctionnent concrètement.""",

# ── Slide 18 — AGENT & MOTEUR ─────────────────────────────────────────────────
"""[AGENT & MOTEUR — 8:20 | ~50 sec]

Au premier démarrage, l'agent s'enrôle auprès du serveur à l'aide d'un jeton à usage unique. Il obtient en retour une clé API permanente. Il débute ensuite la surveillance des dossiers configurés grâce à Watchdog et envoie un signal de présence toutes les trente secondes.

Notre moteur de détection calcule un score cumulatif configurable. Prenons un exemple concret : un fichier renommé avec l'extension .locked rapporte 80 points. La création d'une note de rançon ajoute 55 points. Le total de 135 points franchit le seuil critique : une alerte et un incident sont créés automatiquement en moins de cinq secondes.

→ Voyons maintenant ce que la console SOC offre à l'analyste.""",

# ── Slide 19 — CONSOLE SOC ────────────────────────────────────────────────────
"""[CONSOLE SOC — 9:10 | ~30 sec]

La console SOC regroupe dix pages fonctionnelles. Le tableau de bord se rafraîchit automatiquement toutes les trente secondes.

Le principe fondamental est le suivant : toute action de protection passe par une file d'approbation. L'analyste doit explicitement valider ou rejeter chaque action avant qu'elle ne s'exécute. Les notifications sont envoyées simultanément sur quatre canaux : le web, l'e-mail, le son et un webhook.

Nous vous invitons à constater cela de visu lors de la démonstration.

→ Examinons d'abord les résultats de nos tests de validation.""",

# ── Slide 20 — TESTS & RÉSULTATS ──────────────────────────────────────────────
"""[TESTS — 9:40 | ~25 sec]

Nous avons validé notre système sur cinq scénarios d'attaque, allant de sept à vingt-deux événements. Dans tous les cas, la détection s'est effectuée en moins de cinq secondes, sans perte d'événement. Le pipeline a été validé de bout en bout, de l'événement initial jusqu'à la timeline d'audit.

Il convient de noter honnêtement une limite de notre travail : les tests ont été conduits exclusivement sur Linux. Un attaquant qui ralentirait délibérément son rythme resterait moins visible au moteur de détection.

→ Passons maintenant à la démonstration.""",

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

RansomShield est une solution opérationnelle qui couvre l'ensemble du cycle de traitement d'un incident de sécurité : collecte, analyse, alerte, réponse et audit. Elle repose sur un agent de surveillance multi-plateforme, une console complète, un moteur de détection réactif et un contrôle humain systématique à chaque décision.

Ce travail présente des limites que nous assumons pleinement : il a été réalisé dans un environnement contrôlé, avec des réponses partiellement simulées, et testé exclusivement sur Linux. Nous n'avons nullement la prétention d'avoir épuisé le sujet.

Les perspectives d'évolution sont clairement identifiées : l'intégration du chiffrement HTTPS, la mise en place de rôles utilisateurs distincts, puis à terme l'introduction du machine learning pour affiner la détection, et une intégration avec les outils SIEM du marché.

Nous vous remercions pour l'attention que vous avez bien voulu accorder à notre travail. Nous restons à votre entière disposition pour vos questions.""",

# ── Slide 24 — QUESTIONS DU JURY ──────────────────────────────────────────────
"""[QUESTIONS DU JURY — réponses prêtes]

• Pourquoi Python et pas C ou Go ?
  La bibliothèque Watchdog exploite l'interface inotify nativement — charge CPU minimale, et portabilité garantie sur Linux, Windows et macOS sans recompilation.

• Comment gérez-vous les faux positifs ?
  Les seuils de détection sont entièrement configurables. L'opérateur humain valide ou rejette chaque action avant son exécution — le système ne peut prendre aucune décision autonome.

• Pourquoi pas HTTPS ?
  L'environnement de ce projet est un réseau LAN contrôlé. Le chiffrement TLS constitue la première perspective pour un déploiement en production.

• Quelle différence avec un antivirus ?
  Un antivirus recherche des empreintes connues — il est aveugle aux variantes nouvelles. RansomShield analyse le comportement : renommages massifs, note de rançon, suppression des sauvegardes — indépendamment de toute signature préalable.

• Avez-vous testé sur Windows ?
  Non — c'est une limite que nous assumons. L'agent Python est compatible Windows via Watchdog, mais cette extension n'a pas été testée dans le cadre de ce travail. Elle constitue une priorité pour la suite.

• La solution passe-t-elle à l'échelle ?
  Nos tests ont été conduits sur trois machines virtuelles. L'API Laravel est conçue pour monter en charge, mais des tests de performance à grande échelle restent à conduire.

Reformuler la question avant de répondre. Garder les captures d'écran prêtes.""",

]  # fin NOTES

# ─────────────────────────────────────────────────────────────────────────────
# Mise à jour du PPTX
# ─────────────────────────────────────────────────────────────────────────────

def set_notes(slide, text):
    notes_slide = slide.notes_slide
    tf = notes_slide.notes_text_frame
    for para in tf.paragraphs[1:]:
        p = para._p
        p.getparent().remove(p)
    lines = text.split("\n") if text else [""]
    p0 = tf.paragraphs[0]
    if p0.runs:
        p0.runs[0].text = lines[0]
    else:
        p0.text = lines[0]
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
