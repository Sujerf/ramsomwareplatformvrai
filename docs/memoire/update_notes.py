#!/usr/bin/env python3
"""
Remplace tous les blocs \\note{} de presentation_soutenance.tex
par un discours orateur complet, fluide et lisible.
"""
import re, os

HERE = os.path.dirname(os.path.abspath(__file__))
TEX  = os.path.join(HERE, "presentation_soutenance.tex")

# ─────────────────────────────────────────────────────────────────────────
#  DISCOURS — un texte par slide, dans l'ordre d'apparition dans le .tex
# ─────────────────────────────────────────────────────────────────────────
NOTES = [

# ── 1. OUVERTURE ─────────────────────────────────────────────────────────
r"""[OUVERTURE — 0:00 | ~30 sec]

Monsieur le Président du jury, Mesdames et Messieurs les membres du jury, bonjour.

Je m'appelle Loïc SANGRONIO. Je vais vous présenter aujourd'hui RansomShield : un système de détection et de réponse aux attaques ransomware, dans un environnement contrôlé.

Dix minutes de présentation. Cinq minutes de démonstration live, sur des machines virtuelles réelles. Je serai à votre disposition pour vos questions à la fin.

→ Commençons par un coup d'œil au plan.""",

# ── 2. SOMMAIRE ───────────────────────────────────────────────────────────
r"""[SOMMAIRE — 0:30 | ~20 sec]

Le fil de cette présentation est simple.

D'abord le pourquoi — le contexte et les objectifs. Ensuite le comment — la conception et la réalisation. Et enfin le résultat — ce que ça donne concrètement, en démonstration.

→ Commençons par le contexte.""",

# ── 3. CONTEXTE ───────────────────────────────────────────────────────────
r"""[CONTEXTE — 1:00 | ~45 sec]

Imaginez : vos fichiers se renomment les uns après les autres, les sauvegardes disparaissent, et un message s'affiche — « payez, ou perdez tout ». C'est un ransomware.

L'ENISA a mesuré une hausse de 73 % de ces attaques en 2023. Sans outil dédié, il faut en moyenne 21 jours pour détecter l'incident — et le coût dépasse souvent le million de dollars.

Les cibles ne sont plus seulement les grandes entreprises : les hôpitaux, les PME, les universités sont touchés.

→ Ce contexte soulève une problématique précise.""",

# ── 4. PROBLÉMATIQUE ──────────────────────────────────────────────────────
r"""[PROBLÉMATIQUE — 1:45 | ~45 sec]

Le problème fondamental : les antivirus classiques détectent par signatures. Un ransomware inconnu passe à travers.

Trois défis structurent ce constat.

Premier défi : les variantes inconnues — il faut détecter le comportement, pas la signature.

Deuxième défi : le parc hétérogène — une organisation réelle gère des machines Windows, Linux, macOS. L'agent doit fonctionner sur toutes les plateformes.

Troisième défi : la réponse automatique est risquée. Couper une machine sans vérification peut paralyser un service. Le contrôle humain n'est pas une contrainte — c'est une garantie.

D'où la question centrale : comment détecter un comportement ransomware, dans un environnement contrôlé, sans déployer de vrai malware ?

→ C'est précisément ce que RansomShield cherche à faire.""",

# ── 5. OBJECTIFS ──────────────────────────────────────────────────────────
r"""[OBJECTIFS — 2:30 | ~35 sec]

Six objectifs concrets.

Un : surveiller les événements fichiers en temps réel.
Deux : calculer un score de risque configurable.
Trois : générer automatiquement des alertes et des incidents.
Quatre : proposer des actions de protection — avec un contrôle humain à chaque étape.
Cinq : sécuriser la console par une double authentification.
Six : valider l'ensemble sur des scénarios d'attaque réalistes.

→ Pour y répondre, le mémoire s'articule en trois chapitres — voyons d'abord les fondements.""",

# ── 6. DÉFINITION ────────────────────────────────────────────────────────
r"""[DÉFINITION — 3:45 | ~45 sec]

Le NIST définit le ransomware comme un logiciel malveillant qui bloque l'accès aux données — le plus souvent par chiffrement — pour ensuite réclamer une rançon.

Ce qui nous intéresse : ces attaques ne sont pas silencieuses. Elles laissent des traces avant que les dégâts soient irréversibles.

On peut observer : un renommage massif de fichiers avec une extension suspecte comme .locked, la création d'une note de rançon, la suppression des clichés instantanés VSS, ou l'utilisation d'outils système détournés — ce qu'on appelle les LOLBins.

Ce sont précisément ces signaux comportementaux que notre moteur surveille.

→ Mais pourquoi les outils existants ne suffisent-ils pas ?""",

# ── 7. SOLUTIONS EXISTANTES ───────────────────────────────────────────────
r"""[SOLUTIONS EXISTANTES — 4:30 | ~30 sec]

Les antivirus ? Signatures connues — inefficaces sur les variantes nouvelles.
Les SIEM ? Ils collectent des journaux, sans corrélation comportementale ciblée.
Les EDR commerciaux — CrowdStrike, SentinelOne — sont efficaces, mais hors de portée dans un contexte académique.

RansomShield se positionne dans cet espace : approche comportementale, open-source, avec un opérateur humain dans la boucle à chaque décision.

→ Concrètement, comment est-il construit ?""",

# ── 8. ARCHITECTURE ───────────────────────────────────────────────────────
r"""[ARCHITECTURE — 5:15 | ~45 sec]

Le flux se lit de gauche à droite, en cinq étapes.

Les agents Python surveillent les fichiers et envoient les événements à l'API Laravel. L'API authentifie et transmet au moteur de détection. Le moteur calcule un score. Quand le seuil est franchi, une alerte est créée et un incident s'ouvre automatiquement. L'opérateur consulte la console SOC et valide chaque action avant qu'elle ne s'exécute.

Règle d'or : aucune action automatique sans validation humaine.

→ Détaillons les quatre composants.""",

# ── 9. LES 4 COMPOSANTS ───────────────────────────────────────────────────
r"""[4 COMPOSANTS — 6:00 | ~40 sec]

Premier : l'agent Python — surveille les fichiers en temps réel, conserve une file SQLite locale pour ne rien perdre en cas de coupure réseau. Enrôlement par token à usage unique.

Deuxième : la console SOC — alertes, incidents, file d'approbation, timeline. Notifications sur quatre canaux : web, e-mail, son, webhook.

Troisième : la découverte réseau — scanne les sous-réseaux automatiquement, mais aucun agent ne s'enrôle sans validation manuelle de l'administrateur.

Quatrième : le module de simulation — rejoue jusqu'à 22 événements d'attaque, sans jamais utiliser un vrai malware.

→ Qui interagit avec le système ?""",

# ── 10. USE CASE ──────────────────────────────────────────────────────────
r"""[USE CASE — 6:40 | ~25 sec]

Deux acteurs, deux rôles distincts.

L'administrateur : il configure, gère les agents, lance les simulations.

L'analyste SOC : il traite l'opérationnel — alertes, incidents, actions. Mais sans accès à la configuration.

Cette séparation garantit qu'un analyste ne peut pas modifier les seuils, et qu'un administrateur ne gère pas les incidents du quotidien.

→ Passons maintenant à la réalisation concrète.""",

# ── 11. CHOIX TECHNIQUES ──────────────────────────────────────────────────
r"""[CHOIX TECHNIQUES — 7:55 | ~25 sec]

Les choix techniques ont été guidés par trois critères : portabilité, légèreté, et capacité à démontrer en conditions réelles.

Backend : Laravel 11 et PHP 8.3, base MySQL, API sécurisée par une clé propre à chaque agent.
Agent : Python avec Watchdog, file locale SQLite.
Frontend : Chart.js embarqué localement — aucune dépendance CDN externe.
Infrastructure : 3 VMs KVM, double authentification activée.

→ Comment l'agent fonctionne-t-il concrètement ?""",

# ── 12. AGENT & MOTEUR DE DÉTECTION ───────────────────────────────────────
r"""[AGENT & MOTEUR — 8:20 | ~50 sec]

Au premier démarrage, l'agent s'enrôle avec un token à usage unique — il obtient une clé API permanente. Ensuite : surveillance des dossiers avec Watchdog, et heartbeat toutes les 30 secondes.

Le moteur calcule un score cumulatif. Exemple concret : un fichier renommé .locked rapporte 80 points. Une note de rançon ajoute 55 points. Total 135 → alerte critique créée et incident ouvert automatiquement. En moins de 5 secondes.

→ Côté analyste, qu'est-ce que la console offre ?""",

# ── 13. CONSOLE SOC ───────────────────────────────────────────────────────
r"""[CONSOLE SOC — 9:10 | ~30 sec]

Dix pages fonctionnelles. Le dashboard se rafraîchit toutes les 30 secondes.

Chaque action de protection passe par une file d'approbation : l'analyste valide ou rejette avant toute exécution. Les notifications partent sur quatre canaux simultanés : web, e-mail, son, et webhook.

Vous allez voir tout ça dans quelques instants.

→ Mais d'abord — qu'est-ce que les tests ont donné ?""",

# ── 14. TESTS ET VALIDATION ───────────────────────────────────────────────
r"""[TESTS — 9:40 | ~25 sec]

Cinq scénarios d'attaque, de 7 à 22 événements. Détection en moins de 5 secondes à chaque fois. Zéro perte d'événement. Pipeline validé de bout en bout — de l'événement jusqu'à la timeline d'audit.

Une limite que j'assume honnêtement : les tests ont été conduits uniquement sur Linux. Un attaquant qui ralentirait délibérément son rythme resterait moins visible.

→ Maintenant, place à la démonstration.""",

# ── 15. DÉMONSTRATION LIVE ────────────────────────────────────────────────
r"""[DÉMONSTRATION LIVE — 10:00 | 5 minutes hors chrono]

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

# ── 16. CONCLUSION & PERSPECTIVES ─────────────────────────────────────────
r"""[CONCLUSION — après démo | ~45 sec]

RansomShield couvre tout le cycle de traitement d'un incident : collecte, analyse, alerte, réponse, audit. Agent multi-plateforme. Console complète. Moteur réactif. Contrôle humain systématique.

Ce travail a ses limites : environnement contrôlé, réponses partiellement simulées, tests sur Linux uniquement.

Les perspectives sont claires : d'abord le chiffrement HTTPS, puis des rôles utilisateurs distincts, puis à terme du machine learning pour affiner la détection, et une intégration SIEM.

Je vous remercie pour votre attention. Je suis à votre disposition pour vos questions.""",

# ── 17. MERCI / QUESTIONS DU JURY ─────────────────────────────────────────
r"""[QUESTIONS DU JURY — réponses prêtes]

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
]

# ─────────────────────────────────────────────────────────────────────────
#  Remplacement dans le fichier .tex
# ─────────────────────────────────────────────────────────────────────────

def extract_braced(text, start):
    depth, i, buf = 1, start, []
    while depth > 0:
        c = text[i]
        if   c == '{': depth += 1
        elif c == '}': depth -= 1
        if depth > 0: buf.append(c)
        i += 1
    return "".join(buf), i

def escape_latex(s):
    """Minimal escaping for plain text → LaTeX note body."""
    s = s.replace('\\', r'\textbackslash{}')
    s = s.replace('%', r'\%')
    # em-dash and en-dash are already fine in UTF-8 inputenc
    return s

with open(TEX, encoding='utf-8') as f:
    src = f.read()

note_re = re.compile(r'\\note\{')
positions = [(m.start(), m.end()) for m in note_re.finditer(src)]

if len(positions) != len(NOTES):
    print(f"ERREUR : {len(positions)} blocs \\note trouvés, "
          f"{len(NOTES)} discours fournis.")
    raise SystemExit(1)

# Rebuild from right to left to preserve offsets
result = src
for (pos_start, pos_after_brace), note_text in reversed(list(zip(positions, NOTES))):
    _, end = extract_braced(result, pos_after_brace)
    new_block = "\\note{\n" + note_text.strip() + "\n}"
    result = result[:pos_start] + new_block + result[end:]

with open(TEX, 'w', encoding='utf-8') as f:
    f.write(result)

print(f"OK : {len(NOTES)} blocs \\note mis à jour dans {TEX}")
