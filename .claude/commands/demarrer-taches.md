---
description: Mener une SÉRIE de tâches d'affilée — une plage de cartes, un commit par tâche, sans reprise de main tant que les preuves sont nettes
argument-hint: [plage — ex. ENT-031..ENT-033, une liste de références, ou un besoin en une phrase à découper]
allowed-tools: Bash(git:*), Bash(make:*), Bash(bun:*), Bash(docker:*), Read, Write, Edit, Glob, Grep, AskUserQuestion
---

Mener la série : **$ARGUMENTS**

## Ce que « en série » veut dire ici, et ce que ça ne veut pas dire

**Une série de cinq tâches produit cinq commits.** L'invariant du dépôt — *un commit = une tâche* —
n'est ni assoupli, ni négocié, ni reporté. Ce que cette commande enchaîne, ce sont les **cycles
complets** : chaque carte est implémentée, prouvée, testée, documentée et **commitée** avant que la
suivante s'ouvre.

Elle ne fusionne jamais deux tâches en un commit. Elle ne diffère jamais un commit à la fin de la
série. Le squash est désactivé sur le dépôt et les commits sont le livrable : un commit fourre-tout
atterrirait tel quel sur `develop`, et personne ne pourrait plus le défaire tâche par tâche.

Ce qu'elle fait gagner n'est donc pas un commit, c'est **le retour de main entre deux tâches** :

| Sans la commande | Avec la commande |
| --- | --- |
| `/demarrer-tache` → preuve → ⏸ verdict → `/commit-tache` → on recommence | Une invocation, N cycles |
| Un verdict humain par tâche, même quand rien n'est à décider | Un arrêt **seulement** quand une preuve n'est pas nette |
| N commits | N commits — identiques |

## Quand elle est le bon outil, et quand elle ne l'est pas

Elle vise le cas où **le travail est déjà cadré et où rien n'appelle d'arbitrage** : trois à cinq
cartes du même espace, écrites par la même session, qui se suivent.

Rester sur `/demarrer-tache`, une carte à la fois, dès que l'une de ces conditions manque : une
carte laisse une décision ouverte, le résultat se juge à l'œil (une allure, un rendu, un texte), ou
les cartes se touchent au point qu'on ne sait pas encore dans quel ordre elles tiennent.

> **L'incident du 2026-07-30 reste la raison d'être de tout ça.** Trois cartes implémentées d'une
> traite, commit repoussé à la fin, quatre fichiers portant des morceaux de deux tâches : le
> découpage a demandé de reconstruire à la main la version intermédiaire de six fichiers pour
> l'écrire dans l'index — `git add -p` n'existe pas dans cet environnement. Cette commande est la
> réponse **outillée** à ce besoin-là. Elle ne l'autorise pas, elle le rend inutile.

## Ce qu'elle ne fait pas

Elle ne choisit pas les tâches — elle ne parcourt jamais `dev-book/tasks/` pour trouver quoi faire,
ne propose pas la file d'attente et ne présente aucune carte comme « la suite » (§ *Ce qu'aucun agent
ne fait* du [CLAUDE.md racine](../../CLAUDE.md), règle stricte issue d'`OPS-025`). Elle ne pousse
pas, n'ouvre pas de PR, et ne lance aucune quality gate : celles-ci tournent en une passe dans
`/creer-pr`.

Elle ne **recopie** aucune procédure existante. Elle appelle :

| Pour | Elle appelle |
| --- | --- |
| Cadrer un lot quand aucune plage n'est fournie | `/creer-tache`, autant de fois que de cartes |
| Positionner la branche — **une seule fois** | La phase 1 de `/demarrer-tache` |
| Implémenter une carte | La phase 2 de `/demarrer-tache` |
| Clore une carte | `/commit-tache`, en entier |

Une copie divergerait. La table des espaces l'avait déjà fait avant `OPS-024`, et la copie avait
manqué l'ajout d'un espace.

## Phase 0 — Établir la plage, et tout vérifier avant de coder

**C'est la phase qui rend la série possible.** Tous les contrôles bloquants se font ici, avant la
première ligne de code : c'est parce qu'il ne reste plus rien à demander que la boucle peut ensuite
tourner sans reprise de main.

### Trois formes d'entrée

| Ce qui est donné | Ce qu'elle fait |
| --- | --- |
| Une plage — `ENT-031..ENT-033` | Bornes **incluses**, expansion en ordre numérique croissant |
| Une liste — `ENT-031 ENT-034 ENT-035` | L'ordre donné est l'ordre d'exécution |
| Un besoin en une phrase | Elle **cadre d'abord** — voir ci-dessous |

**Besoin en une phrase** : dérouler `/creer-tache` jusqu'à ce que le découpage soit stable, ce qui
produit N cartes. **Faire valider le découpage et l'ordre** — les cartes, leur périmètre, leur
enchaînement — puis seulement entrer dans la boucle. Écrire plusieurs cartes d'un cadrage est
légitime et souvent souhaitable ; c'est les **implémenter** sans commit intermédiaire qui ne l'est
pas, et c'est exactement ce que la boucle empêche.

### Les six contrôles, tous bloquants

1. **Un seul espace.** Toutes les cartes portent le même préfixe. Une plage qui traverse deux
   espaces est refusée sur place : deux branches, donc deux séries, à lancer séparément.
2. **Chaque carte existe.** Un numéro sans carte est **nommé et écarté**, jamais inventé — la
   numérotation par espace saute des numéros quand deux sessions avancent en parallèle. Aucune carte
   ne survit ? S'arrêter et renvoyer vers `/creer-tache`.
3. **Chaque carte est ouverte.** Une carte `✅ fait` est nommée et écartée, la série continue sans
   elle.
4. **Chaque carte appartient à cette session.** Comparer le champ `**Session**` de chaque carte à
   l'UUID de la session courante — celui du chemin `/tmp/claude-<uid>/<projet>/<UUID>/scratchpad`.
   Champ absent → carte antérieure à `OPS-025`, continuer sans rien dire. Différent → **s'arrêter et
   demander**, pour toute la plage, **une seule fois**. Reprendre le cadrage d'un autre est
   légitime ; ça se décide, ça ne se découvre pas au commit.
5. **L'ordre tient.** Lire la ligne `**Réf** :` de chaque carte. Une carte qui déclare en amont une
   carte située **plus loin** dans la série arrête la phase 0 : donner l'ordre corrigé et le faire
   valider.
6. **L'arbre est propre.** `git status --porcelain` dans le dossier de travail. Du travail non
   commité présent → l'inventorier et **ne rien écraser** : s'il appartient à une tâche précédente,
   elle n'est pas finie, la clore par `/commit-tache` avant d'ouvrir la série. Ce qui appartient à
   une autre lane ou à l'utilisateur est **noté nommément** : il devra rester hors de tous les
   commits de la série, et sa présence est attendue au contrôle d'arbre propre de chaque tour.

> **Au-delà de cinq tâches, découper.** Ce n'est pas une limite technique : c'est le rapport final
> qui porte le verdict humain différé, et un rapport de douze tâches ne se relit pas.

### Positionner la branche — une fois pour toute la série

Exécuter la **phase 1 de `/demarrer-tache`**, qui possède cette procédure : elle refuse `develop` et
`main`, crée ou reprend `espace/<espace>`, et rebase sur `origin/develop` si la lane a du retard.

**Une seule fois, au début.** Ne pas rebaser entre deux tâches de la série : une branche qui bouge
sous des commits qu'on vient de poser transforme un enchaînement propre en résolution de conflits.

**Sortie de phase 0** — la branche et son état, la liste ordonnée des cartes retenues, les cartes
écartées avec leur motif, et le travail non commité à laisser hors de tous les commits.

## Phase 1 — La boucle

Pour chaque carte, **dans l'ordre**, et sans jamais commencer la suivante avant d'avoir refermé la
précédente :

1. **Implémenter** — phase 2 de `/demarrer-tache` : relire la carte puis les `README.md` des dossiers
   touchés, coder au diff minimal, s'arrêter sur une **preuve fraîche**, écrire les tests annoncés
   par la ligne `**Tests** :`, mettre à jour la documentation déclenchée.

   Une différence, et une seule, avec l'exécution normale de cette phase : **la preuve ne suspend
   pas la série tant qu'elle est nette.** Si elle ne l'est pas, la série s'arrête — critère
   ci-dessous.

2. **Commiter** — `/commit-tache`, en entier : clôture de la carte (statut, vérification, livré,
   définition de terminé cochée), staging **par chemins explicites**, relecture de
   `git diff --cached`, message conforme. La carte, les tests et la doc entrent dans **ce** commit.

3. **Vérifier que l'arbre est propre** — `git status --porcelain` doit être vide, aux fichiers
   inventoriés en phase 0 près.

   > **C'est le garde-fou central de cette commande.** Il reproduit mécaniquement ce que le retour
   > manuel par `/demarrer-tache` garantissait. Un reste inattendu signifie qu'un morceau de la
   > tâche est passé à travers le staging — et s'il part dans le commit suivant, c'est très
   > exactement l'incident du 2026-07-30 qui recommence. **Arrêt immédiat**, sans rien défaire.

4. **Annoncer une ligne**, puis passer à la suivante :
   `ENT-031 ✅ a1b2c3d — feat(entreprise): …`

## Quand la série s'arrête

### Une preuve nette, et rien d'autre, autorise à continuer

Une preuve est nette quand elle réunit **les deux** :

- c'est un **fait observé et reproductible sans toi** — une requête réelle avec sa réponse, une
  sortie de test lue, une capture, une configuration résolue. Jamais « testé manuellement », jamais
  une suite qualité présentée comme preuve de justesse ;
- elle couvre le **résultat observable annoncé par la carte**, pas un indice qui en tiendrait lieu.

Un doute sur l'un des deux **est** le critère d'arrêt. Il n'y a pas de preuve « probablement bonne » :
il y a une preuve, ou un arrêt.

> **Les gates ne voient pas tout.** Le CSS mort d'`ENT-024` sortait vert partout et n'est apparu
> qu'en capture. C'est pour ça qu'une carte dont le résultat se juge à l'œil n'a pas sa place dans
> une série.

### Les cinq motifs d'arrêt

1. La **preuve** manque, échoue, ou contredit ce qu'annonce la carte.
2. L'implémentation demande une **décision que la carte ne tranche pas**. Une incertitude ne se
   convertit pas en hypothèse silencieuse au milieu d'une série : c'est le moment où elle devient
   invisible.
3. Le travail **sort de la lane** ou du périmètre de la carte. Ce qui sort remonte, il ne se traite
   pas — ni le code, ni la carte d'une autre lane.
4. L'**arbre n'est pas propre** après le commit d'une tâche (point 3 de la boucle).
5. Une tâche de la série a **rendu fausse une carte suivante**. La carte n'est plus une spec : elle
   se recadre, et ça ne se fait pas dans la boucle.

### Arrêt net, jamais de saut

Ce qui a été commité **reste**. La série s'arrête **à la tâche qui coince**, et son travail en cours
**reste dans l'arbre, intact** : rien n'est défait, rien n'est stashé, rien n'est écrasé. Les tâches
suivantes ne sont pas démarrées.

> Sauter une carte bloquée demanderait de défaire son travail pour ne pas polluer la suivante,
> c'est-à-dire un `checkout --` ou un `reset` sur un arbre qui peut porter le travail d'un autre.
> Le dépôt l'interdit, et un incident l'a déjà écrit.

## Sortie

Un tableau, une ligne par carte de la plage :

```
Carte     État                 Commit    Titre
ENT-031   ✅ commitée          a1b2c3d   feat(entreprise): …
ENT-032   ✅ commitée          e4f5g6h   fix(entreprise): …
ENT-033   ⛔ arrêt             —         preuve absente : l'endpoint attendu n'existe pas
ENT-034   ⏸ non démarrée       —         —
ENT-035   ⏸ non démarrée       —         —
```

Puis, et seulement si le cas se présente : les cartes **écartées** en phase 0 avec leur motif, le
motif d'arrêt détaillé avec ce qui reste dans l'arbre, et ce qui a été **nommé au passage** et
appartient à une autre lane.

**Enfin, le rappel de la relecture.** Le verdict humain n'a pas disparu, il a été déplacé — il se
rend ici, en une passe, avant `/creer-pr` :

```bash
git -C <ton dossier> log --oneline origin/develop..espace/<espace>
git -C <ton dossier> diff origin/develop...espace/<espace>
```

Rien n'est poussé : tant que la PR n'est pas ouverte, un commit de la série se corrige ou se défait
localement. C'est ce qui rend le rythme acceptable, et c'est aussi pourquoi cette relecture n'est
pas facultative.

Ne rien pousser, ne pas ouvrir la PR : c'est le travail de `/creer-pr`, et **c'est là, et seulement
là, que `make quality` et la suite de tests complète tournent**, en une passe pour toute la branche.
