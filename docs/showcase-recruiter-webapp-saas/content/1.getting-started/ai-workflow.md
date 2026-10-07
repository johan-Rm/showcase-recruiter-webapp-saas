# Workflow IA — d'une demande au code livré

- Rôle : décrire le chemin complet qu'emprunte une demande, du langage courant jusqu'aux commits
  intégrés sur `develop` — et dire pourquoi ce chemin est fait ainsi.
- Related : [./index.md](./index.md), [./git-workflow.md](./git-workflow.md),
  [./parallel-work.md](./parallel-work.md),
  [../3.quality/testing-and-ci.md](../3.quality/testing-and-ci.md)

## Pourquoi ce dépôt ne se travaille pas comme un autre

showcase-recruiter-webapp-saas est développé par une personne et **plusieurs sessions d'assistant qui écrivent en même
temps**. Cette situation crée trois problèmes qu'un dépôt ordinaire ne connaît pas — et presque tout
ce qui suit en découle.

**Un assistant sans périmètre dérive.** On demande une correction sur un écran, et rien n'empêche de
« profiter d'y être » pour renommer une variable trois dossiers plus loin. Le diff grossit, la
relecture devient impossible, et la régression arrive par un fichier que personne n'avait demandé de
toucher. → D'où le découpage en **espaces** : chaque session travaille dans un périmètre nommé, et ce
qui en sort se **signale** au lieu de se traiter.

**Deux sessions dans le même dossier sont sur la même branche.** Un `git checkout` de l'une l'arrache
sous les pieds de l'autre. → D'où **un dossier de travail par espace**, un `git worktree` avec sa
branche, sa stack Docker et sa base.

**Une intention qui n'est pas écrite se perd.** Un besoin discuté puis implémenté dans la foulée
laisse un code dont plus personne ne sait pourquoi il est ainsi. → D'où la **carte de tâche** : une
spec courte, écrite *avant* le code, qui voyage dans le commit qu'elle décrit.

Trois problèmes, trois notions : l'espace, le dossier de travail, la carte. Le reste de cette page
les met bout à bout.

## Ce qui gouverne l'assistant

**Claude Code** est le seul assistant IA du projet. Sa gouvernance se résout en cascade, du plus
global au plus local — **le niveau local précise celui du dessus, il ne le contredit jamais.**

| Portée | Fichier | Ce qu'il porte |
| --- | --- | --- |
| Globale | `~/.claude/CLAUDE.md` | Principes d'ingénierie, style, mode d'exécution. Hors dépôt, vaut pour tous les projets. |
| Projet | `CLAUDE.md` | Le routage entre lanes et le socle commun à tous les agents. |
| Lane | `.claude/agents/espace-*.md` | L'expertise, le périmètre et les interdits d'un espace. |
| Moment | `.claude/commands/*.md` | Le mode opératoire d'un moment du cycle. |

Le projet n'expose **pas** de fichier `AGENTS.md` : la gouvernance vit dans `CLAUDE.md`, seule et
sans jumeau à synchroniser.

Une précision qui resservira plus bas : la définition d'un agent déclare **qui** il est, **ce qu'il a
le droit de toucher** et **quels outils** il reçoit. Elle ne déclare pas **où** il s'exécute — aucun
champ ne le permet.

### Deux périmètres dans un monorepo

La gouvernance racine vaut pour tout le dépôt ; chaque application porte ses conventions au plus près
de son code. Une convention `webapp/` ne s'applique pas mécaniquement à `api/`, et inversement.

| Périmètre | Techno | Conventions opérationnelles |
| --- | --- | --- |
| `webapp/` | Nuxt (Vue 3, TS strict) | le `README.md` de chaque dossier |
| `api/` | Symfony + API Platform | [api-structure.md](../2.architecture/api-structure.md) + Deptrac |

## Les espaces

### Ce qu'est un espace

Un espace, c'est **quatre choses attachées ensemble** :

- un **périmètre de code** — le seul endroit où cette lane écrit ;
- une **branche** longue, `espace/<nom>`, réutilisée tâche après tâche ;
- un **préfixe de tâche** et son compteur, qui nomme les cartes produites sur cette branche ;
- un **sub-agent** qui porte l'expertise du périmètre.

| Espace | Périmètre de code | Branche | Préfixe |
|---|---|---|---|
| api | `api/` | `espace/api` | `API` |
| entreprise | `webapp/spaces/entreprise/` | `espace/entreprise` | `ENT` |
| candidat | `webapp/spaces/candidat/` | `espace/candidat` | `CAN` |
| admin | `webapp/spaces/admin/` | `espace/admin` | `ADM` |
| public | `webapp/spaces/public/` | `espace/public` | `PUB` |
| socle-webapp | `webapp/base/`, `webapp/shared/` travaillés **pour eux-mêmes** | `espace/socle-webapp` | `WEB` |
| design | `ui-ux-design/` | `espace/design` | `UX` |
| outillage | `docs/`, `dev-book/cadrage/`, `.claude/`, `.github/`, Docker, infra | `espace/outillage` | `OPS` |

Le dossier de travail et la stack Docker ne sont **pas** des attributs de l'espace : ils n'existent
que si quelqu'un travaille dessus. Leur mécanique est dans
[parallel-work.md](./parallel-work.md#un-dossier-de-travail-par-espace--git-worktree).

### Deux règles de routage, et deux seulement

**Le transverse se rattache à l'espace consommateur.** `webapp/base/` touché *pour* l'entreprise part
sur `espace/entreprise` ; une page de `docs/` qui documente le contrat d'API part sur `espace/api`.
`espace/socle-webapp` et `espace/outillage` ne prennent que ce qu'**aucun** espace ne consomme.

**Une demande qui traverse plusieurs lanes se découpe en autant de tâches**, une par lane. Elles
peuvent avancer en parallèle ; elles ne partagent jamais un commit.

### Un sub-agent par espace

Chaque espace a **son** sub-agent, déclaré dans `.claude/agents/`. Un sub-agent travaille dans un
**contexte séparé**, avec son propre prompt et sa propre liste d'outils.

C'est ce qui rend le périmètre explicite au lieu de compter sur la vigilance d'un agent unique : un
agent qui ne connaît que sa lane ne peut pas déborder par inadvertance. Le fichier d'un agent ne
recopie ni l'architecture, ni les conventions front, ni le workflow git — il **pointe** vers la
source qui les porte, et n'ajoute que ce qui lui est propre.

Le tableau qui dit **quelle demande va à quel agent** vit dans `CLAUDE.md`. Il route ; celui
ci-dessus définit.

### Comment tout s'emboîte

```
   Claude Code                    une session dans l'éditeur — elle route
        │
        ▼
   Sub-agent d'espace             contexte séparé, outils propres
        ┊
        ┊   rien ne l'y met : il doit s'y mettre
        ▼
   Worktree                       le dossier de travail
        │
        ├──►  Périmètre de code   ce que la lane possède
        ├──►  Stack Docker        ports, domaine, base — reste locale
        └──►  Branche espace/…    longue, réutilisée
                   │
                   ▼
              Pull request        le seul point de contrôle
                   │
                   ▼
                develop
```

**Un seul de ces liens n'est pas mécanique**, et c'est celui en pointillé. Un dossier de travail
*porte* forcément une branche, la stack est créée avec le dossier, un commit appartient forcément à
sa branche — rien à surveiller. Mais un sub-agent, lui, démarre là où on l'a lancé, **pas** dans le
dossier de son espace : aucun réglage ne fixe son dossier de travail, il doit s'y mettre lui-même.
S'il ne le fait pas, il écrit dans le mauvais arbre.

## Le chemin d'une demande

### Vue d'ensemble

```
                      Une demande, en langage courant
                                     │
                   ┌─────────────────┴─────────────────┐
                   ▼                                   ▼
     ┌──────────────────────────┐        ┌──────────────────────────┐
     │  Lane · espace/api       │        │  Lane · espace/entreprise│
     │  préfixe de tâche API    │        │  préfixe de tâche ENT    │
     │                          │        │                          │
     │   carte 1  →  commit     │        │   carte 1  →  commit     │
     │        ↓                 │        │        ↓                 │
     │   carte 2  →  commit     │        │   carte 2  →  commit     │
     │        ↓                 │        └─────────────┬────────────┘
     │   carte 3  →  commit     │                      │
     └─────────────┬────────────┘                      │
                   │      une PR par lane              │
                   ▼                                   ▼
             Pull request                        Pull request
             gates qualité + CI                  gates qualité + CI
                   │                                   │
                   └─────────────────┬─────────────────┘
                                     ▼
                                  develop
                                     ┊
                                     ┊  réalignement : les branches sont longues
                                     └┄┄►  et resservent à la demande suivante
```

Une demande se répartit entre les lanes qu'elle touche, produit dans chacune **autant de cartes qu'il
faut** — c'est le cas courant, pas l'exception —, et chaque lane ouvre **une** Pull request qui les
emmène toutes.

### Quatre rythmes, pas un seul

Les quatorze étapes qui suivent ne rejouent pas au même rythme, et c'est l'information la plus utile
de cette page :

| Rythme | Étapes | Ce qui se passe |
| --- | --- | --- |
| Une fois par **demande** | 1-2 | La demande arrive et se répartit entre les lanes. |
| Une fois par **lane** | 3 | La session s'installe dans son dossier de travail. |
| À chaque **carte** — la boucle | 4-10 | Cadrer, coder, prouver, figer. Puis on recommence. |
| Une fois par **Pull request** | 11-14 | Livrer la lane et la remettre à niveau. |

### Qualifier — une fois par demande

**1. La demande arrive**, en langage courant, dans une session. Rien n'est encore écrit.

**2. Elle est routée vers une ou plusieurs lanes**, selon les deux règles ci-dessus. Une demande qui
traverse plusieurs espaces se découpe ici, en autant de tâches.

> Ces deux étapes s'outillent par `/evoquer-un-sujet` quand la demande n'est **pas encore une
> décision** : elle reformule le manque, pèse les pistes, et conclut — à faire, à ne pas faire, pas
> maintenant, ou pas tranchable et voici ce qui manque. C'est là que le découpage entre lanes se
> fait à froid, avant qu'une seule carte existe. Elle n'écrit rien et n'ouvre rien : le verdict
> « à faire » **nomme** les tâches, `/creer-tache` les ouvre. Une demande déjà tranchée saute cette
> étape et va directement au cadrage.

### Se positionner — une fois par lane

**3. La session s'installe dans son dossier de travail** — le `git worktree` de l'espace, checkouté
sur sa branche. `make worktree-list` dit qui travaille où ; la branche d'un worktree au travail est
**intouchable**. À partir de là, tout chemin est absolu : le shell repart de la racine du dépôt entre
deux commandes, et un chemin relatif édite l'arbre principal au lieu du sien.

Jamais `develop` ni `main`, et la vérification se fait **dès la première écriture** — pas au moment
de committer. La procédure complète est dans
[parallel-work.md](./parallel-work.md#un-dossier-de-travail-par-espace--git-worktree).

### Cadrer, coder, figer — à chaque carte

**4. Le besoin est instruit par questions**, jusqu'à épuisement des inconnues. Aucune décision de
cadrage n'est prise à la place de l'humain : ce qui reste ambigu se demande, ou s'écrit dans la carte
comme hypothèse assumée.

**5. La carte de tâche est écrite** — voir la section suivante, c'est la pièce centrale.

**6. Les sources utiles au périmètre sont lues**, et elles seules. L'assistant part du contexte le
plus proche du changement et ne remonte vers une règle globale que si nécessaire :

1. le fichier de gouvernance du projet, `CLAUDE.md` ;
2. les seuls fichiers de `2.architecture/` liés au périmètre modifié ;
3. la page thématique de `docs/` si une convention transverse est concernée ;
4. le `README.md` du dossier modifié ;
5. la carte `dev-book/tasks/` correspondante.

Le `README.md` d'un dossier **fait loi** pour son périmètre : ses conventions ne se déduisent ni du
code voisin ni du bon sens, et les plus courtes sont vérifiées en CI. Le contrat complet — README →
règle → script → gate avant merge — est dans
[testing-and-ci.md](../3.quality/testing-and-ci.md#outillage-par-périmètre).

**7. L'implémentation suit la carte.** Diff minimal, pas de refactor hors périmètre, pas de
dépendance ajoutée sans justification. Ce qui sort de la lane se **nomme** et s'arrête : ni le code,
ni la carte d'une autre lane.

**8. La session s'arrête sur une preuve fraîche** et attend le verdict humain. Une preuve est un fait
observé — une capture, une réponse HTTP, une sortie lue. Pas « testé manuellement », et **jamais une
suite qualité présentée comme preuve de justesse** : elle ne voit ni un rendu ni un comportement. Du
CSS mort est déjà sorti vert de toutes les gates et n'est apparu qu'en capture d'écran.

**9. Une fois le résultat validé** : les tests du changement, la documentation qu'il rend fausse, et
`api/openapi.json` régénéré si la surface d'API bouge. Après le verdict, pas avant — couvrir et
documenter un code que la relecture va faire bouger, c'est l'écrire deux fois. La régénération du
contrat est du **contenu de commit**, pas une gate.

**10. Un commit = une tâche** : le code, ses tests, la documentation et la **carte clôturée**, tous
dans le même commit. Le staging se fait **par chemins explicites** — jamais `git add -A` ni un
dossier entier : le fichier d'une autre session peut apparaître entre le `git status` et le
`git add`. Pas de commit `wip`, pas de `fixup` : le merge se fait en rebase, les commits sont le
livrable.

> **La boucle se referme ici.** La carte suivante ne s'ouvre qu'après le commit de la précédente.
> Deux tâches menées de front finissent dans les mêmes fichiers — `webapp/base/`, les fichiers de
> langue, une page partagée — et le découpage a posteriori devient une chirurgie d'index. Trois
> cartes implémentées d'une traite le 2026-07-30 ont demandé de reconstruire à la main la version
> intermédiaire de six fichiers.

### Intégrer — une fois par Pull request

**11. La PR s'ouvre.** C'est **ici** que les quality gates tournent, en une passe pour toutes les
tâches de la branche — pas à chaque commit. Un agent de lane ne pousse pas et n'ouvre pas de PR :
c'est un geste humain.

**12. La CI passe, puis la relecture.** Auto-relecture du diff et revue assistée ; pas de second
approbateur en contexte solo.

**13. Le merge se fait en rebase.** Les commits atterrissent sur `develop` tels quels, un par un.
Jamais de squash : il écraserait toutes les tâches de la branche en une seule.

**14. Toutes les branches de travail se réalignent** — pas seulement celle qui vient d'être mergée.
Les autres divergent en silence dès qu'un commit arrive sur `develop`, et le conflit ne se manifeste
que plusieurs tâches plus tard. Une branche où du travail est en cours est laissée intacte.

Ce réalignement n'est pas du rangement : la branche est **longue** et resservira pour la demande
suivante. C'est lui qui la rend réutilisable.

> **La page s'arrête là.** La suite — `develop` vers `main`, tag `vX.Y.Z`, déploiement — est dans
> [git-workflow.md](./git-workflow.md#versioning-tags--releases) et
> [testing-and-ci.md](../3.quality/testing-and-ci.md#cd--déploiement-vps-docker--caddy).

### Les commandes qui outillent le cycle

Cinq commandes de `.claude/commands/`, une par moment — `/creer-tache`, `/demarrer-tache` (qui
positionne la branche **et** implémente), `/commit-tache`, `/creer-pr`, `/apres-pr-mergee`. Leurs
règles sont dans [git-workflow.md](./git-workflow.md).

Trois autres s'ajoutent **à côté** du cycle plutôt que dedans, et les deux premières le
**précèdent** — ce sont les seules commandes du dépôt qui ne modifient rien, leur liste
`allowed-tools` excluant `Write` et `Edit`.

`/evoquer-un-sujet` répond à *est-ce qu'on le fait*, avant qu'il y ait une tâche à cadrer : elle
outille les étapes 1 et 2 ci-dessus, longtemps le seul rythme sans commande. `/etat-des-lanes`
répond à *où en est-on* avant qu'une tâche s'ouvre. `/demarrer-taches`, elle, prend une plage de
cartes déjà cadrées et enchaîne les tours de cycle sans reprise de main — elle n'assouplit pas
*un commit = une tâche*, elle rend la discipline moins coûteuse que sa transgression.

## La carte de tâche est la spec

**Rien ne se code avant que la carte existe.** Elle est écrite à l'issue du cadrage, avant la
première ligne de code, et le commit ne fait plus que la clôturer. Une carte écrite après coup n'est
plus une spec, c'est un compte rendu — et un compte rendu ne dit pas ce qui a été *écarté*, qui est
souvent l'information la plus utile six mois plus tard.

Elle porte le contexte, le périmètre, **le hors périmètre**, les décisions de cadrage et leur motif,
les cas limites, la documentation impactée et la définition de terminé. Avec le type Conventional
Commit, sa ligne `**Type** :` est le **seul** endroit où la nature du travail est écrite : le nom de
branche, lui, ne dit que l'espace.

### Un compteur par espace

Chaque espace a son préfixe et son compteur, donc deux espaces qui avancent en parallèle ne peuvent
pas réserver le même numéro. Le numéro suivant se lit sur **toutes** les branches et tous les
remotes — un `ls dev-book/tasks/` ne voit que l'arbre courant, qui peut être en retard. La commande
est dans [git-workflow.md](./git-workflow.md#numérotation-des-tâches--un-compteur-par-espace).

### À qui appartient une carte

Le compteur protège les **noms** ; il ne protège pas les **intentions**. Une carte vit en fichier
**non suivi** entre son cadrage et le commit de sa tâche : pendant cette fenêtre, elle est visible de
tous et attribuée à personne — `dev-book/tasks/` est un dossier partagé par conception.

D'où le champ `**Session**` de son en-tête, qui porte l'identifiant de la session qui l'a cadrée, et
le contrôle de `/demarrer-tache` qui s'arrête quand la carte visée vient d'ailleurs. Reprendre la
carte d'un autre reste permis — c'est même parfois exactement ce qu'on veut : une session
s'interrompt, une autre finit le travail. Mais **ça se décide, ça ne se découvre pas au commit.**

## La discipline de session

Une session ne travaille que sur ce qui lui a été demandé, **dans cette session**. Elle n'ouvre
aucune tâche de sa propre initiative, ne parcourt pas `dev-book/tasks/` pour trouver quoi faire, et
ne présente jamais une carte en attente comme « la suite ». L'énoncé de la règle est dans le
[CLAUDE.md racine](../../../../CLAUDE.md) ; voici pourquoi elle existe.

Le 2026-08-02, une session ouverte sur une seule question d'architecture a trouvé sept cartes non
commitées dans un worktree et en a **proposé** la liste. L'humain en a fait implémenter trois. Deux
n'avaient rien à voir avec la demande initiale, l'une portait des décisions de cadrage que la session
exécutante n'avait jamais prises, et tout a dû être défait.

**C'est la proposition, pas l'exécution, qui a ouvert la brèche.** Un contrôle au moment d'implémenter
serait arrivé trop tard : quand une file d'attente est présentée comme « la suite », le choix est
déjà orienté. La règle porte donc sur l'initiative, en amont.

Sa contrepartie est la **clôture par inventaire** : quand le travail demandé est fini, le dernier
message dit ce qui a été **livré**, ce qui reste **ouvert** dans l'arbre, et ce qui a été **nommé au
passage** et appartient à d'autres sessions. Un inventaire, pas une proposition. Sans elle, une
session qui ne propose rien mais reste ouverte invite à demander « et ensuite ? », ce qui ramène au
point de départ.

## Qui possède quoi

**Chaque information a un propriétaire.** Les autres documents renvoient vers lui au lieu de recopier
son contenu — une copie ne se met pas à jour, elle vieillit. Le nom des branches a changé quatre fois
en trois jours ; à chaque fois, une copie oubliée a fait travailler quelqu'un sur une convention
périmée.

| Sujet | Propriétaire |
| --- | --- |
| Le chemin d'une demande, les espaces, la carte de tâche | **cette page** |
| Branches, commits, merge, PR, tags, numérotation | [git-workflow.md](./git-workflow.md) |
| Worktrees, stacks, ressources partagées, base de test | [parallel-work.md](./parallel-work.md) |
| Gates, CI, CD, contrat README → règle → gate | [testing-and-ci.md](../3.quality/testing-and-ci.md) |
| Quelle demande va à quel agent | `CLAUDE.md` |
| Le mode opératoire d'un moment du cycle | `.claude/commands/*.md` |

Trois corollaires :

- un `README.md` local peut rappeler une convention courte et pointer vers `2.architecture/` ;
- une carte `dev-book/tasks/` peut **lister** la documentation à mettre à jour, elle ne devient pas
  la documentation durable ;
- un runbook ne remplace pas une carte : il documente une opération exceptionnelle à exécuter de
  façon contrôlée.
