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
r"""\textbf{[OUVERTURE]}\\[4pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury, bonjour.\\[4pt]
  Permettez-nous, avant toute chose, de vous exprimer notre profonde reconnaissance
  pour avoir répondu présents à ce rendez-vous. Ce jour revêt pour nous un cachet
  tout particulier : il est l'aboutissement de ces années de formation, et un moment
  qui restera gravé dans notre parcours.\\[4pt]
  Le sujet ayant fait l'objet de notre étude est intitulé : \textit{Conception et mise
  en place d'un système de détection et de réponse aux attaques ransomware dans un
  environnement contrôlé}.\\[4pt]
  C'est avec fierté et humilité que nous vous présentons aujourd'hui RansomShield —
  un système conçu et mis en place de bout en bout pour détecter et contrer
  les attaques ransomware, de manière contrôlée et traçable, avec une validation
  humaine à chaque étape décisive.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Voici comment s'articule cette présentation.}""",

# ── 2. SOMMAIRE ───────────────────────────────────────────────────────────
r"""\textbf{[SOMMAIRE]}\\[4pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury,\\[4pt]
  Notre présentation s'articule en cinq parties. Nous partirons du contexte et de la
  problématique qui ont motivé ce travail, pour aller vers un état des lieux des solutions
  existantes, puis la conception et la réalisation du système. Nous conclurons par les
  perspectives et terminerons par une démonstration en conditions réelles.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Commençons par le contexte et la problématique.}""",

# ── 3. CONTEXTE ───────────────────────────────────────────────────────────
r"""\textbf{[CONTEXTE]}\\[4pt]
  Nous entrons dans la première partie de notre exposé, consacrée au contexte
  et aux motivations de ce travail.\\[4pt]
  Aujourd'hui, les entreprises, les hôpitaux, les universités fonctionnent grâce à
  leurs données numériques. Le ransomware — c'est-à-dire un logiciel malveillant
  qui rend les données inaccessibles puis exige une rançon — exploite précisément
  cette dépendance. En 2023, ces attaques ont progressé de 73\,\% selon l'ENISA,
  avec un coût moyen de plus d'un million de dollars par incident. Ce qui est
  particulièrement grave : sans outil adapté, une attaque passe en moyenne 21 jours
  sans être détectée. C'est 21 jours pendant lesquels les dommages s'accumulent
  en silence.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Ce constat nous amène à la problématique centrale.}""",

# ── 4. PROBLÉMATIQUE ──────────────────────────────────────────────────────
r"""\textbf{[PROBLÉMATIQUE]}\\[4pt]
  Ce contexte nous amène naturellement à la problématique centrale de ce travail.\\[4pt]
  Le constat est simple : les antivirus fonctionnent par reconnaissance d'empreintes.
  Ils connaissent les menaces déjà répertoriées. Mais face à une variante inédite d'un
  ransomware, ils restent complètement aveugles. Et pendant ce temps, les données sont
  chiffrées, les sauvegardes effacées, les dommages deviennent irréversibles.\\[4pt]
  Le problème que ce travail cherche à résoudre est le suivant : il n'existe
  pas de solution libre et démontrable en conditions réelles pour détecter ces attaques
  à partir de leurs comportements, avec un contrôle humain intégré à chaque étape.\\[4pt]
  Trois défis ont structuré ce travail : les variantes inconnues que les outils
  traditionnels ne voient pas, la diversité des systèmes à surveiller, et le risque
  qu'une réponse automatique sans validation humaine cause elle-même des dommages.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Ces défis ont défini les six objectifs que voici.}""",

# ── 5. OBJECTIFS ──────────────────────────────────────────────────────────
r"""\textbf{[OBJECTIFS]}\\[4pt]
  Pour répondre à cette problématique, six objectifs précis ont guidé ce travail.\\[4pt]
  Premièrement, surveiller en temps réel les activités fichiers sur chaque terminal.
  Deuxièmement, analyser ces activités pour calculer un niveau de risque.
  Troisièmement, déclencher automatiquement des alertes dès qu'un comportement suspect
  est détecté. Quatrièmement — et c'est un principe fondateur — soumettre chaque
  action proposée à la décision d'un opérateur humain. Cinquièmement, sécuriser l'accès
  à la console par une double authentification : mot de passe et code temporaire valide
  30 secondes. Et sixièmement, valider l'ensemble sur des scénarios d'attaque représentatifs.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Voyons maintenant les fondements théoriques de ce travail.}""",

# ── 6. DÉFINITION ────────────────────────────────────────────────────────
r"""\textbf{[DÉFINITION]}\\[4pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury,\\[4pt]
  Nous entrons dans la deuxième partie de notre exposé : la revue de littérature.
  Permettez-moi de commencer par définir précisément ce qu'est un ransomware.\\[4pt]
  Un ransomware agit toujours selon le même schéma. Il commence par accéder à vos
  fichiers, les renomme avec une extension inhabituelle pour les rendre illisibles,
  dépose une note de rançon, puis efface les sauvegardes automatiques pour rendre
  toute récupération impossible. Enfin, il utilise parfois des outils légitimes
  du système pour masquer sa présence et passer inaperçu.\\[4pt]
  Ce sont ces comportements observables, ces traces laissées sur le système,
  qui forment la base de notre approche. Si l'on surveille ces actions en temps réel,
  on peut réagir avant que les dommages soient irréversibles.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Mais pourquoi les outils existants ne suffisent-ils pas ?}""",

# ── 7. SOLUTIONS EXISTANTES ───────────────────────────────────────────────
r"""\textbf{[SOLUTIONS]}\\[4pt]
  Face à cette menace bien documentée, des solutions existent. Voyons pourquoi elles
  restent insuffisantes pour notre contexte.\\[4pt]
  Les antivirus ne reconnaissent que les menaces déjà cataloguées dans leur base
  de données. Les SIEM — systèmes de centralisation des journaux d'activité — collectent
  des informations, mais sans chercher activement des comportements suspects. Les EDR
  — solutions de surveillance avancée des terminaux — sont très efficaces, mais leur coût
  les rend inaccessibles pour la grande majorité des organisations — PME, hôpitaux,
  administrations, associations — qui n'ont pas les budgets des grands groupes.\\[4pt]
  RansomShield occupe cet espace laissé vacant : une solution libre, comportementale,
  déployable sur n'importe quelle infrastructure, avec un contrôle humain intégré
  à chaque étape — conçue pour les entreprises et organisations qui veulent se
  protéger sans dépendre de solutions propriétaires coûteuses.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Voyons maintenant comment ce système a été conçu.}""",

# ── 8. ARCHITECTURE ───────────────────────────────────────────────────────
r"""\textbf{[ARCHITECTURE]}\\[4pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury,\\[4pt]
  Nous entrons dans la troisième partie : l'analyse et la conception de RansomShield.
  Voici l'architecture générale du système — vous pouvez la lire de gauche à droite.\\[4pt]
  Un agent, installé sur chaque terminal de l'organisation, observe en permanence
  toute activité inhabituelle sur les fichiers. Ces observations sont transmises au
  serveur central, qui les analyse et calcule un score de risque. Dès que ce score
  dépasse un seuil configuré, une alerte est générée automatiquement et une action
  de protection est proposée à l'opérateur. C'est lui qui prend la décision finale.
  Le système n'agit jamais seul : chaque intervention est soumise à une validation humaine.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Voyons de plus près les quatre composants.}""",

# ── 9. LES 4 COMPOSANTS ───────────────────────────────────────────────────
r"""\textbf{[4 COMPOSANTS]}\\[4pt]
  Voyons maintenant de plus près les quatre composants qui constituent ce système.\\[4pt]
  Premièrement, l'agent de surveillance : installé sur chaque machine, il observe en
  temps réel toute activité inhabituelle sur les fichiers et conserve ces informations
  localement, pour ne rien perdre même en cas de coupure réseau.\\[4pt]
  Deuxièmement, la console de supervision : c'est le poste de commandement, là où
  l'opérateur suit les alertes, prend ses décisions et consulte l'historique complet
  de toutes les actions.\\[4pt]
  Troisièmement, le module de découverte réseau : il signale automatiquement les
  nouvelles machines, mais aucune n'est intégrée au système sans accord explicite
  de l'administrateur.\\[4pt]
  Enfin, le simulateur : il reproduit des scénarios d'attaque réalistes, sans déployer
  le moindre malware réel — ce qui a permis de valider le système en toute sécurité.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Qui sont les acteurs qui interagissent avec ce système ?}""",

# ── 10. USE CASE ──────────────────────────────────────────────────────────
r"""\textbf{[USE CASE]}\\[4pt]
  Ce diagramme présente les deux profils d'utilisateurs qui interagissent avec
  RansomShield, et la répartition claire de leurs responsabilités.\\[4pt]
  L'administrateur configure et pilote le système : il connecte les agents,
  définit les règles de détection et lance les simulations.\\[4pt]
  L'analyste SOC — pour Security Operations Center, c'est-à-dire le centre
  opérationnel de sécurité — traite les incidents au quotidien : il consulte
  les alertes, approuve ou rejette les actions proposées, et suit l'historique
  complet des événements.\\[4pt]
  Cette séparation des rôles est volontaire : l'analyste ne peut pas modifier
  la configuration, ce qui garantit l'intégrité du système.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Passons maintenant à la réalisation concrète.}""",

# ── 11. CHOIX TECHNIQUES ──────────────────────────────────────────────────
r"""\textbf{[CHOIX TECHNIQUES]}\\[4pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury,\\[4pt]
  Nous voici dans la quatrième partie de notre exposé, consacrée à la réalisation
  et aux résultats. Commençons par les choix techniques.\\[4pt]
  Trois critères ont guidé ces choix : fonctionner sur différents systèmes
  d'exploitation, être facile à installer, et être démontrable en conditions réelles
  sans infrastructure coûteuse.\\[4pt]
  Pour le serveur et la console, le choix s'est porté sur Laravel — un cadre de développement
  web robuste et éprouvé. Pour l'agent de surveillance, Python, qui offre une
  excellente portabilité sur Windows, Linux et macOS. L'environnement de test
  repose sur trois machines virtuelles isolées, reproduisant les conditions
  d'un vrai réseau d'entreprise.\\[4pt]
  L'accès à la console est protégé par une double authentification : après le mot
  de passe, l'utilisateur doit saisir un code temporaire valide 30 secondes seulement.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Voyons concrètement comment l'agent et le moteur fonctionnent.}""",

# ── 12. AGENT & MOTEUR DE DÉTECTION ───────────────────────────────────────
r"""\textbf{[AGENT \& MOTEUR DE DÉTECTION]}\\[4pt]
  Entrons dans le cœur du système : l'agent de surveillance et le moteur de scoring.\\[4pt]
  Voici comment fonctionne l'agent au quotidien : dès son démarrage, il est reconnu
  par le serveur central grâce à un jeton sécurisé à usage unique. Il commence alors
  à surveiller les dossiers et envoie régulièrement un signal de vie au serveur,
  toutes les 30 secondes.\\[4pt]
  Pour illustrer le moteur de scoring : imaginons qu'un fichier soit renommé avec
  l'extension \texttt{.locked} — cette extension inhabituelle est un signal connu
  des ransomwares. Ce comportement ajoute 80 points au score de risque. Si en plus
  une note de rançon apparaît dans le même dossier, 55 points s'ajoutent. Le score
  atteint 135 — bien au-dessus du seuil critique de 100. En moins de cinq secondes,
  une alerte est générée et un incident de sécurité est ouvert dans la console.
  Le système attend ensuite la décision de l'opérateur.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Côté analyste, voici ce que la console SOC offre.}""",

# ── 13. CONSOLE SOC ───────────────────────────────────────────────────────
r"""\textbf{[CONSOLE SOC]}\\[4pt]
  Venons-en maintenant à la console SOC — l'interface que l'opérateur utilise
  au quotidien pour surveiller, réagir et rendre des comptes.\\[4pt]
  La console regroupe neuf pages couvrant l'ensemble du cycle de traitement :
  tableau de bord, gestion des agents, alertes, incidents, timeline d'audit,
  file d'approbation, règles de détection, recherche globale et paramètres.\\[4pt]
  Ce qui est fondamental dans RansomShield, c'est que chaque action proposée
  par le système passe obligatoirement par la file d'approbation. L'analyste
  l'approuve ou la rejette explicitement. Aucune action ne s'exécute en silence.
  Ce principe est non négociable.\\[4pt]
  Dès qu'une alerte est levée, l'opérateur est notifié simultanément par quatre
  canaux — sur l'interface web, par e-mail, par signal sonore et par webhook —
  pour s'assurer qu'aucune alerte ne passe inaperçue, quelle que soit sa situation.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Qu'est-ce que les tests ont donné ?}""",

# ── 14. TESTS ET VALIDATION ───────────────────────────────────────────────
r"""\textbf{[TESTS ET VALIDATION]}\\[4pt]
  Après la réalisation, venons-en aux tests et à la validation du système
  en conditions représentatives.\\[4pt]
  Une démarche en trois étapes complémentaires a été adoptée.\\[4pt]
  Premièrement, cinq scénarios d'attaque représentatifs ont été définis, couvrant
  différents niveaux de complexité : du chiffrement simple à la chaîne d'attaque
  complète en 22 événements. Ces scénarios ont été construits à partir des comportements
  réels documentés dans la littérature sur les ransomwares.\\[4pt]
  Deuxièmement, un environnement de test réel sur trois machines virtuelles en
  réseau isolé a été mis en place, pour reproduire les conditions d'un vrai parc
  informatique, sans jamais déployer de malware réel.\\[4pt]
  Troisièmement, pour chaque scénario, trois indicateurs précis ont été mesurés :
  le délai de détection, l'intégrité des données transmises, et la complétude
  de la chaîne jusqu'à la décision de l'opérateur.\\[4pt]
  Les résultats sont constants : cinq scénarios exécutés, cinq détectés en moins
  de cinq secondes, zéro événement perdu. Notons honnêtement une limite :
  les tests ont été conduits sur Linux uniquement — c'est la première perspective
  d'évolution du projet.\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Place maintenant à la démonstration live.}""",

# ── 15. DÉMONSTRATION LIVE ────────────────────────────────────────────────
r"""\textbf{[DÉMONSTRATION LIVE]}\\[4pt]
  Suivre le guide de démonstration.\\[6pt]
  \textbf{Checklist rapide :}\\[3pt]
  1. Console SOC ouverte : 3 agents en ligne\\[3pt]
  2. SSH sur \texttt{rs-client-1} : renommage \texttt{.locked} + note de rançon\\[3pt]
  3. Revenir sur la console : alerte critique apparue en moins de 5 secondes\\[3pt]
  4. File d'approbation : approuver l'action, montrer le rollback\\[3pt]
  5. Timeline : tout est horodaté\\[3pt]
  6. Simulation kill chain complète : 22 événements\\[6pt]
  \textcolor{transition}{$\hookrightarrow$ Revenir à la présentation pour conclure.}""",

# ── 16. CONCLUSION & PERSPECTIVES ─────────────────────────────────────────
r"""\textbf{[CONCLUSION]}\\[2pt]
  Nous voilà dans la cinquième et dernière partie de notre exposé.\\[2pt]
  Excellence Monsieur le Président du jury, Mesdames et Messieurs les membres du jury,
  permettez-nous de vous présenter la synthèse de ce travail.\\[2pt]
  RansomShield couvre l'intégralité du cycle de traitement d'un incident de sécurité :
  la détection sur le terminal, l'analyse comportementale, la génération de l'alerte,
  la décision de l'opérateur, et l'archivage complet de toutes les actions.
  Il est opérationnel, démontrable, et construit sans aucun malware réel.\\[2pt]
  Conscients que cette première implémentation, menée avec des ressources
  et un délai contraints, n'est pas exempte de lacunes : les tests ont été conduits sur Linux
  uniquement, et les communications restent en HTTP. Ce sont des limites assumées
  pleinement et nommées clairement.\\[2pt]
  Nous n'avons nullement la prétention d'avoir épuisé le sujet.
  C'est pourquoi nous comptons sur vos observations, vos critiques et vos recommandations
  pour enrichir ce travail. Chaque remarque du jury est pour nous une opportunité
  de progresser et de mieux faire.\\[2pt]
  Les perspectives sont tracées : sécuriser les communications pour la production,
  affiner les rôles selon le profil de chaque utilisateur, et doter le moteur
  d'un apprentissage automatique pour qu'il apprenne à reconnaître les comportements
  normaux et signale les anomalies avec encore plus de précision.\\[4pt]
  \textcolor{transition}{$\hookrightarrow$ Nous restons à votre disposition pour vos questions.}""",

# ── 17. MERCI / QUESTIONS DU JURY ─────────────────────────────────────────
r"""\textbf{[MERCI — MOT DE CLÔTURE]}\\[2pt]
  Dans un monde où les systèmes automatisent nos décisions, RansomShield rappelle
  une conviction simple : la machine peut détecter, mais c'est l'humain qui décide.
  Ce n'est pas une contrainte technique — c'est un choix éthique.\\[1pt]
  Merci pour votre aimable attention et pour le temps accordé à ce travail.\\[3pt]
  \textbf{[POINTS DE RÉPONSE AU JURY]}\\[1pt]
  \textbullet\ \textbf{Python vs C/Go :}
  Watchdog exploite inotify nativement — CPU minimal, portable Linux/Windows/macOS sans recompilation.\\[1pt]
  \textbullet\ \textbf{Fausses alertes :}
  Seuils configurables. L'analyste valide ou rejette avant toute action — le système ne peut pas agir seul.\\[1pt]
  \textbullet\ \textbf{Absence de HTTPS :}
  LAN contrôlé pour ce projet. Chiffrement TLS = première perspective pour la production.\\[1pt]
  \textbullet\ \textbf{Différence avec un antivirus :}
  L'antivirus reconnaît les empreintes. RansomShield observe le comportement — variantes inédites couvertes.\\[1pt]
  \textbullet\ \textbf{Tests Windows :}
  Limite assumée — Linux uniquement. Extension à Windows = première perspective d'évolution.\\[2pt]
  \textit{Reformuler la question avant de répondre. Garder les \textbf{captures d'écran} prêtes.}""",
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
