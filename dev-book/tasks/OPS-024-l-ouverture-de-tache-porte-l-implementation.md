# TASK OPS-024 — l'ouverture de tâche porte l'implémentation

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — `/demarrer-tache` porte deux phases et va du code à la doc,
  `/creer-pr` relit les cartes de la branche, et l'`allowed-tools` qui empêchait matériellement la
  commande d'écrire est corrigé.
- **Type** : Gouvernance IA — commandes de cycle et doc du workflow.
- **Tests** : `sans objet` — aucun code exécutable n'est produit : quatre fichiers de commande en
  Markdown et les pages qui décrivent le cycle. La preuve est l'exécution du cycle corrigé sur la
  tâche suivante, `ENT-029`, dont la carte est déjà écrite et attend son implémentation.
- **Réf** : `.claude/commands/demarrer-tache.md` · `creer-tache.md` · `commit-tache.md` ·
  `creer-pr.md` ·
  [git-workflow.md §Outillage](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md)

## Contexte

**Aucune des cinq commandes ne porte l'implémentation.** `/creer-tache` cadre et écrit la carte,
`/demarrer-tache` positionne la branche, `/commit-tache` fige, `/creer-pr` livre. Le code, lui, vit
dans l'espace **entre** deux commandes — c'est-à-dire nulle part.

Constaté en séance le 2026-07-31 : `/demarrer-tache` lancée deux fois de suite sur `ENT-029` a
produit deux rapports d'état identiques et **pas une ligne de code**, en concluant chaque fois « je
n'ai rien codé, la commande s'arrête là ». Le cycle est correct sur le papier et n'avance pas.

**Les tests et la documentation sont déjà obligatoires, mais réclamés trop tard.** `/commit-tache`
les pose tous deux comme bloquants, avec un tableau de déclencheurs par type de changement. Comme
rien ne les annonce à l'ouverture, ils se découvrent une fois tout le code écrit — en checklist de
sortie plutôt qu'en travail prévu. Une tâche qui n'avait pas anticipé sa couverture la découvre au
moment le plus coûteux pour l'ajouter.

**Et `/creer-pr` ne regarde jamais les cartes qu'elle embarque.** Elle vérifie l'arbre propre, les
commits, les gates. Une carte restée `⏳ à faire`, une *Définition de terminé* aux cases vides, une
page listée en « Documentation impactée » et absente du diff : rien de tout cela ne l'arrête. Le
contrôle qui existe en CI (`commit-check`) ne valide que la **forme** des messages de commit.

## Périmètre

- **`/demarrer-tache`** — deux sections nommées : *Positionner la branche*, puis *Implémenter*.
  Cette seconde section enchaîne le travail réel et va jusqu'à la doc et aux tests.
- **`/creer-tache`** — son appel vise désormais la **section de branche**, pas la commande entière.
- **`/creer-pr`** — cinq contrôles sur l'état des cartes portées par la branche, bloquants.
- **`/commit-tache`** — ses exigences de tests et de doc restent, requalifiées en dernier filet.
- Les endroits qui décrivent le cycle : `git-workflow.md`, `ai-workflow.md`, et le `CLAUDE.md` du
  dépôt s'il devient faux.

## Hors périmètre

- **Renommer la commande.** Elle porte désormais l'implémentation entière et s'appelle toujours
  « démarrer » : le nom décrit le moment où on l'invoque, pas la totalité de ce qu'elle fait. Écarté
  pour ne pas chasser les références dans cinq fichiers et casser un réflexe de frappe.
- **`/apres-pr-mergee`**, que rien ici ne touche.
- **Les gates elles-mêmes** et leur répartition entre commit et PR : inchangées.
- **La CI.** `commit-check` continue de ne valider que la forme des messages ; les cinq contrôles
  d'état restent locaux, dans `/creer-pr`.
- **Écrire du code d'espace.** `ENT-029` est ouverte sur `espace/entreprise` et attend ; elle sera
  la première tâche menée avec le cycle corrigé.

## Décisions de cadrage

### L'implémentation entre dans une commande, et c'est celle qui ouvre

Elle aurait pu devenir une sixième commande. Elle ne le devient pas : le moment « j'ouvre une
tâche » et le moment « je l'implémente » ne sont **pas séparés par une décision humaine** — personne
n'ouvre une tâche pour s'arrêter juste après. Une commande de plus aurait ajouté une frappe entre
deux gestes qui n'en font qu'un.

Le contrat « une tâche à la fois » y gagne au passage : il était énoncé par la commande qui ouvre et
vérifié par celle qui commite, avec tout le travail entre les deux hors de portée. Il couvre
maintenant le geste qu'il protège.

### Deux sections nommées, parce que `/creer-tache` appelle celle-ci

`/creer-tache` **exécute** `/demarrer-tache` pour sa phase de branche, délibérément : le
positionnement n'existe qu'à un seul endroit. La version précédente en gardait une copie, et cette
copie avait manqué l'ajout d'un espace.

Faire coder `/demarrer-tache` sans plus de précaution ferait donc coder `/creer-tache`, qui affirme
l'inverse à sa première ligne. Recopier la procédure de branche pour s'en sortir reproduirait
exactement le bug qu'on connaît.

D'où le découpage : **une seule source pour la branche, deux portes d'entrée**. `/creer-tache`
appelle la section *Positionner la branche* et s'arrête là ; l'appel direct enchaîne sur
*Implémenter*.

### La preuve fraîche arrête la commande — l'oracle, c'est l'utilisateur

L'implémentation ne va pas d'une traite jusqu'au commit. Elle s'interrompt sur une **preuve
fraîche** — capture, requête réelle, sortie lue — et attend le verdict humain.

Ce n'est pas une politesse. Le socle distingue *vérifier le comportement* (libre et attendu à chaque
changement) des *quality gates* (point de contrôle) : la preuve due ici est un appel réel, jamais
une suite qualité. Et `ENT-024` a montré ce que les gates ne voient pas — un CSS mort, vert partout,
visible uniquement en capture.

### Les tests et la doc s'écrivent APRÈS la validation

C'est l'ordre demandé, et son motif est économique : documenter et couvrir un code que la relecture
va faire bouger, c'est l'écrire deux fois. La validation fige ce qu'il y a à décrire.

**Une exception, et elle est nommée** : une tâche dont la carte annonce un cycle RED-GREEN garde ses
tests **avant** le code — le test y est le moteur, pas la couverture. La règle vise le cas courant.

Ce déplacement ne relâche rien : `/commit-tache` continue de refuser un commit sans tests ni doc.
Ces deux points deviennent le **dernier filet** au lieu d'être la première demande.

### `/creer-pr` vérifie l'état, jamais l'exécution

Vérifier qu'une commande a tourné n'a pas de sens : rien n'en garde trace. Ce qui se vérifie, c'est
**l'état qu'elle aurait dû produire**, et il se lit dans les fichiers sans rien exécuter :

1. chaque commit de la branche porte une carte `dev-book/tasks/` ;
2. chaque carte est **close** (`✅ fait le …`) et non restée `⏳ à faire` ;
3. chaque carte a sa ligne `**Tests** :` renseignée — exemption motivée comprise ;
4. aucune case non cochée dans sa *Définition de terminé* ;
5. les pages listées en « Documentation impactée » apparaissent dans le diff cumulé.

**Bloquants, comme l'arbre sale.** Un contrôle consultatif est un contrôle qu'on saute : il serait
lu le jour où il ne trouve rien, et ignoré le jour où il trouve quelque chose.

## Règles et cas limites

- **Sans carte**, `/demarrer-tache` renvoie vers `/creer-tache` avant toute implémentation —
  inchangé, et c'est ce qui protège l'invariant « rien ne se code avant que la carte existe ».
- **Reprise d'une tâche déjà commencée** : la commande reprend à l'étape utile ; elle ne réécrit pas
  ce qui existe et ne redemande pas une validation déjà donnée.
- **Validation refusée** : on corrige, on re-présente. Ni tests ni doc ne s'écrivent tant que le
  rendu n'est pas validé — c'est tout l'intérêt de l'ordre choisi.
- **Tâche sans code exécutable** (doc, cadrage, maquette) : l'étape d'implémentation *est* l'écriture
  de la documentation, et les tests sortent en `sans objet` motivé.
- **La commande ne commite ni ne pousse**, et cela ne change pas : `/commit-tache` garde le commit,
  `/creer-pr` garde le push.
- **Les cinq contrôles de `/creer-pr` s'appliquent aussi à une branche purement Markdown** : une
  carte doit être close même quand aucune gate ne tourne.
- **Une seule source par règle** : le positionnement de branche reste écrit dans une seule section,
  et le tableau `espace → préfixe` reste dans `git-workflow.md`, jamais recopié.

## Documentation impactée

- [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — le paragraphe
  « Outillage : cinq commandes projet » et la description du cycle qui le suit.
- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — la mention des
  cinq commandes.
- Le `CLAUDE.md` du dépôt — son encadré sur les cinq commandes, **si** le changement le rend faux.

## Définition de terminé

- [x] `/demarrer-tache` porte deux sections nommées, et la seconde va du code à la doc.
- [x] La validation humaine sur preuve fraîche est un arrêt explicite, pas une formalité.
- [x] Les tests et la doc y sont demandés après la validation, avec l'exception TDD nommée.
- [x] `/creer-tache` appelle la section de branche et rien d'autre ; aucune procédure recopiée.
- [x] `/creer-pr` porte les cinq contrôles d'état, bloquants.
- [x] `/commit-tache` renvoie vers l'étape qui écrit tests et doc, sans relâcher ses exigences.
- [x] Les pages qui décrivent le cycle disent le nouveau, et aucune ne décrit l'ancien.
- [x] Relecture du diff : rien d'autre que les commandes et les pages du cycle.

## Vérification

**Le défaut le plus coûteux n'était pas dans le texte, il était dans l'en-tête.** `/demarrer-tache`
déclarait `allowed-tools: Bash(git:*), Read, Glob, Grep` — **ni `Write` ni `Edit`**. Lui demander
d'implémenter n'aurait rien changé : l'outillage le lui interdisait. Elle déclare désormais `Write`,
`Edit` et les commandes projet (`make`, `bun`, `docker`). Le cadrage ne l'avait pas vu ; il est
apparu à l'ouverture du fichier.

**Toutes les références vivantes sont couvertes.** `grep -rln "demarrer-tache" --include=*.md`
rend quinze fichiers : les quatre commandes et les deux pages de doc sont dans ce commit ; les huit
autres sont des **cartes de tâches** (`OPS-001`, `OPS-002`, `OPS-007`, `OPS-009`, `OPS-016`, `126`,
`129`) qui décrivent ce qui était vrai à leur date et ne se réécrivent pas. Le quinzième est cette
carte.

**Le `CLAUDE.md` du dépôt n'a pas bougé, et c'est délibéré.** Son encadré — « cinq commandes
projet outillent le cycle, une par moment » — reste vrai après le changement. C'est un routeur, pas
une page de cycle : y recopier les deux phases aurait créé une troisième copie de la règle à tenir
à jour.

**Diff relu en entier** : 6 fichiers modifiés, 143 insertions, 44 suppressions, plus cette carte.
Rien d'autre que les commandes et les pages du cycle ; aucune procédure de branche recopiée hors de
la phase 1.

Aucune gate ne s'applique : le diff est exclusivement Markdown.

## Livré

- `.claude/commands/demarrer-tache.md` **(M)** — deux phases nommées, `allowed-tools` élargi aux
  outils d'écriture, phase 2 en six étapes (carte et README, code, preuve fraîche, tests, doc, écart
  au cadrage).
- `.claude/commands/creer-tache.md` **(M)** — n'appelle plus que la phase 1, et le dit.
- `.claude/commands/commit-tache.md` **(M)** — tests et doc requalifiés en dernier filet.
- `.claude/commands/creer-pr.md` **(M)** — étape 3, cinq contrôles d'état bloquants ; étapes
  suivantes renumérotées.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` **(M)** — le cycle décrit avec ses deux
  phases et l'arrêt sur preuve.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` **(M)** — la preuve fraîche devient une
  étape du workflow de delivery, tests et doc passent après la validation.
- `dev-book/tasks/OPS-024-…md` **(+)** — cette carte.

## Aval

- **Automatiser les cinq contrôles** en CI, aujourd'hui portés par une commande locale donc
  dépendants du fait qu'on la lance.
- **La couverture des tâches précédentes** : les cartes déjà closes n'ont pas été passées à ces
  contrôles, et certaines ne les passeraient pas.
