---
description: Lire l'état de toutes les lanes en une passe — ce qui est en cours, ce qui attend une PR, ce qui est au repos et ce qui a pris du retard sur develop
argument-hint: [espace à isoler — ex. entreprise ; vide = toutes les lanes]
allowed-tools: Bash(git:*), Read, Glob, Grep
---

État des lanes : **$ARGUMENTS** (vide → toutes).

## Ce que cette commande fait, et ce qu'elle ne fait pas

Elle **regarde**. Elle ne branche pas, n'écrit pas de carte, ne code pas, ne commite pas, ne pousse
pas. C'est l'une des deux commandes du dépôt qui ne modifient rien — avec `/evoquer-un-sujet` — et
cette propriété n'est pas négociable : elle est consultée pour décider, pas pour agir.

| Moment | Commande | Ce qu'elle possède |
| --- | --- | --- |
| Évoquer un sujet | `/evoquer-un-sujet` | Rien — la conversation, et elle seule |
| **Se situer** | **`/etat-des-lanes`** | Rien — lecture seule |
| Cadrer et spécifier | `/creer-tache` | Le questionnement, la carte `dev-book/tasks/` |
| Ouvrir et mener une tâche | `/demarrer-tache` | La branche, le code, la preuve, tests et doc |
| Clore une tâche | `/commit-tache` | La clôture de la carte et **un** commit |
| Livrer la lane | `/creer-pr` | Les gates qualité, le titre et le corps de PR |
| Après le merge | `/apres-pr-mergee` | La vérification du merge et le réalignement |

> ⛔ **Rien de ce que cette commande affiche n'est une invitation à agir.** Elle liste des cartes
> ouvertes ; elle ne propose **jamais** d'en reprendre une, ne les classe pas par priorité et ne
> désigne pas « la suite ». La règle vient d'`OPS-025` : c'est la **proposition**, pas l'exécution,
> qui a fait reprendre à trois reprises des cartes cadrées par une autre session. Le rapport se
> termine sur un état, pas sur une recommandation.

## Ce qu'elle appelle « en cours »

**Ce qui est engagé, pas ce qui est envisagé.** Trois signaux, et trois seulement :

- du travail **non commité** dans le worktree d'une lane ;
- des **commits d'avance** sur `origin/develop`, c'est-à-dire des tâches finies mais pas mergées ;
- des **cartes non suivies** dans `dev-book/tasks/` — une carte cadrée dont la tâche n'est pas
  encore commitée.

Le **backlog en est exclu**, délibérément : les cartes déjà commitées et restées `⏳ à faire`
comptent par dizaines, elles ne disent rien de ce qui avance, et les afficher transformerait un
état en file d'attente — exactement ce qu'`OPS-025` interdit.

## Procédure

### 1. Synchroniser la référence, sans rien toucher

```bash
git fetch origin --prune
```

`origin/develop` est la référence de comparaison. Ne **jamais** basculer de branche, ni mettre à
jour `develop` local : c'est le geste d'`/apres-pr-mergee`.

### 2. Énumérer les lanes — les découvrir, pas les écrire en dur

```bash
git for-each-ref --format='%(refname:short)' 'refs/heads/espace/*'
```

Un espace peut apparaître ; une liste en dur le manquerait. Un argument restreint l'affichage à une
lane, il ne change pas la collecte — le retard des autres reste utile à voir.

### 3. Relever, pour chaque lane

| Mesure | Commande |
| --- | --- |
| Son worktree | `git worktree list --porcelain`, ligne `branch refs/heads/espace/<x>` |
| Tâches finies non mergées | `git rev-list --count origin/develop..espace/<x>` |
| Retard sur l'intégration | `git rev-list --count espace/<x>..origin/develop` |
| Branche poussée | `git rev-parse --verify --quiet origin/espace/<x>` |
| Travail non commité | `git -C <worktree> status --porcelain` |
| Cartes non suivies | `git -C <worktree> ls-files --others --exclude-standard -- dev-book/tasks` |

Sans worktree, les deux dernières mesures sont **sans objet** — et non « rien à signaler » : une
lane sans dossier ne peut pas porter de travail en cours, c'est une information, pas un vide.

Le sujet des commits d'avance porte le numéro de tâche (`— task ENT-030`) : les afficher tels quels
suffit, `git log --format='%s' origin/develop..espace/<x>`.

### 4. Lire l'en-tête des cartes non suivies

Deux champs, jamais le corps : `**Statut**` et `**Session**`.

- Le **statut** distingue une carte tout juste cadrée d'une carte bloquée par un amont.
- La **session** dit **à qui** la carte appartient. Différente de la session courante → elle est
  celle d'un autre agent, et le rapport le dit sans ambiguïté. Champ absent → carte antérieure à
  `OPS-025`, pas carte orpheline : ne rien en déduire.

### 5. Classer — le premier état qui s'applique gagne

| Signaux | État |
| --- | --- |
| Arbre sale **et** carte non suivie | **Tâche en cours** — cadrée, en implémentation |
| Arbre sale seul | **Travail non commité** hors carte visible — le signaler tel quel |
| Carte non suivie seule | **Cadrée, pas commencée** |
| Commits d'avance, branche poussée | **En attente de merge** — PR probablement ouverte |
| Commits d'avance, non poussée | **Prête pour `/creer-pr`** |
| Aucun des quatre | **Au repos** |

Le retard sur `origin/develop` se cumule à l'état, il ne le remplace pas : une lane au repos avec
quatre commits de retard sera rebasée par `/demarrer-tache`, une lane **en cours** avec du retard
est un conflit qui mûrit.

> **`gh` n'est pas authentifié dans cet environnement.** L'état d'une PR n'est jamais affirmé : une
> branche poussée avec des commits d'avance se rapporte comme « PR probablement ouverte », et la
> vérification appartient à l'utilisateur.

## Sortie

Un tableau, une ligne par lane — l'espace, son état, ce qui est en cours, le retard :

```
Lane            État                    En cours                              Retard
entreprise      tâche en cours          4 cartes cadrées, arbre sale          —
api             prête pour /creer-pr    1 tâche (API-018)                     —
admin           tâche en cours          ADM-012 cadrée + arbre sale           4 commits
socle-webapp    cadrée, pas commencée   WEB-007                               —
candidat        au repos                —                                     —
```

Puis, **seulement si l'un des trois se présente** :

- les cartes non suivies portant une **autre session** que la courante, nommées comme telles ;
- les lanes **en cours** avec du retard sur `origin/develop` — le conflit qui mûrit ;
- les lanes **sans worktree** dont la branche porte des commits d'avance : le travail existe, le
  dossier pour le reprendre n'existe pas (`make worktree-add ESPACE=<espace>`).

Rien d'autre. Pas de synthèse, pas de « prochaine étape », pas de tâche suggérée.

## Garde-fous

- **Lecture seule, sans exception.** Aucun `switch`, `checkout`, `reset`, `rebase`, `add`, `commit`,
  `stash` ni `worktree add`. Un `git fetch` est le seul écrit toléré, et il ne touche aucun arbre.
- **Ne jamais ouvrir un fichier non suivi au-delà de son en-tête.** Lire le corps d'une carte cadrée
  par une autre session, c'est déjà s'approprier son cadrage.
- **Ne pas confondre « au repos » et « rien à faire ».** Une lane au repos n'a aucun travail
  *engagé* ; son backlog, hors périmètre ici, vit dans `dev-book/tasks/`.
- **Le retard se signale, il ne se corrige pas.** Le réalignement appartient à `/apres-pr-mergee`,
  et il refuse les arbres sales pour de bonnes raisons.
