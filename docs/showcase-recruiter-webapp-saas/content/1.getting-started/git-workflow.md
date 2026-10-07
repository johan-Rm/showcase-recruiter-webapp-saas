# Workflow Git

- Rôle : définir le modèle de branches, la convention de commits et la stratégie de merge du
  monorepo showcase-recruiter-webapp-saas.
- Related : [./index.md](./index.md), [./parallel-work.md](./parallel-work.md),
  [../3.quality/testing-and-ci.md](../3.quality/testing-and-ci.md)

## Modèle de branches — GitHub Flow allégé

```
espace/<espace>  ──PR──►  develop  ──►  main (production)
hotfix/<slug>    ──PR──►  develop  (+ cherry-pick / PR vers main si urgence prod)
```

- **`main`** : état déployé en production. Aucun commit direct.
- **`develop`** : branche d'intégration. Aucun commit direct ; tout passe par PR.
- **`espace/<espace>`** : le travail courant, partant de `develop` (ci-dessous).
- **`hotfix/<slug>`** : correctif urgent ; part de `main`, merge vers `develop`, et remontée vers
  `main` si la prod est touchée. Reste **à la racine**, hors espaces : ce nom qui ne ressemble à
  aucune branche de travail signale son cycle différent.

### Branches de travail — `espace/<espace>`

**Une branche par espace**, et rien d'autre dans le nom. L'espace porte aussi le **préfixe des
tâches** produites sur sa branche.

> Ce qu'est un espace — un périmètre de code, une branche, un préfixe, un agent — et **la liste des
> huit** sont dans [ai-workflow.md](./ai-workflow.md#les-espaces), qui les possède. Cette page ne
> traite que de leurs branches.

Ces branches sont **longues** : créées une fois, réutilisées tâche après tâche, réalignées sur
`develop` entre deux PR, pas supprimées avant la fin du MVP v1.

**Le nom de branche ne dit pas la nature du travail** — cette information vit à deux endroits, et
deux seulement :

- la **carte de tâche**, ligne `**Type** :` de `dev-book/tasks/<PRÉFIXE>-NNN-*.md` ;
- le **message de commit**, via le type Conventional Commit (`feat`, `fix`, `refactor`, `docs`…),
  que `commit-check` valide en CI.

Le mettre aussi dans la branche le dupliquerait sans le fiabiliser : une branche `…/fix` qui porte
un `feat` ne déclenche aucune alerte, alors qu'un commit mal typé, si. Et une branche accumule
plusieurs tâches de natures différentes — elle n'a pas de nature propre à annoncer.

> ⚠️ **Un espace n'est jamais une branche à lui seul.** Git interdit qu'une réf soit à la fois un
> fichier et un dossier : `git branch espace` rendrait `espace/api` impossible à créer, et
> `espace/api` interdit à son tour tout `espace/api/<quoi-que-ce-soit>` (`cannot lock ref … exists`).
> C'est cette contrainte qui a écarté un troisième segment, et le préfixe `feature/` avec lui.

**Un commit = une tâche**, et le commit embarque **le code et son fichier
`dev-book/tasks/<PRÉFIXE>-NNN-*.md`**. La carte est écrite **avant** le code, à l'issue du cadrage,
et clôturée au moment du commit. Le fichier de tâche voyage donc toujours avec son propre commit,
quel que soit l'espace — `dev-book/tasks/` n'appartient pas à `outillage/`.

Le commit porte aussi **les tests du changement** et **la documentation qu'il rend fausse**. Une
tâche sans code exécutable est dispensée de tests, mais la dispense s'écrit dans la carte
(`**Tests** : sans objet — <raison>`) : l'exception est tracée, jamais silencieuse.

Une branche peut donc accumuler **plusieurs tâches** avant d'ouvrir sa PR : chaque commit est une
tâche, et la PR se merge en **rebase** (cf. stratégie de merge ci-dessous), ce qui les dépose tels
quels sur `develop`. Aucune contrainte de rythme — pas besoin de merger avant d'enchaîner.

> En contrepartie, **les commits sont le livrable** : pas de commit `wip`, pas de `fixup`. Nettoyer
> localement (`git commit --amend`, `git rebase -i`) **avant** d'ouvrir la PR — la CI
> `commit-check` valide chacun d'eux.

Après merge d'une PR, **toutes** les branches de travail se réalignent sur l'intégration, pas
seulement celle qui vient d'être mergée : les autres divergent en silence dès qu'un commit arrive
sur `develop`, et le conflit ne se manifeste que plusieurs tâches plus tard. Une branche où du
travail est en cours (arbre sale, ou PR ouverte) est laissée intacte.

> Outillage : cinq commandes projet dans `.claude/commands/`, une par moment du cycle —
> `/creer-tache` (cadre le besoin par questions et écrit la carte ; elle appelle la **phase 1** de la
> suivante pour la branche), `/demarrer-tache` (**deux phases** : positionner la branche — elle
> refuse `develop` et pose le contrat « une tâche à la fois » — puis **implémenter** la carte
> jusqu'aux tests et à la doc), `/commit-tache` (découpe le diff en commits de tâche, exige carte
> clôturée + tests + doc, message), `/creer-pr` (état des cartes portées, gates qualité en une passe,
> titre et corps prêts à coller), `/apres-pr-mergee` (vérifie le merge, alerte sur un squash
> accidentel, réaligne les branches au repos).
>
> Deux autres **précèdent** le cycle sans y entrer, et ce sont les **seules commandes du dépôt qui
> ne modifient rien** — propriété portée par leur liste `allowed-tools`, qui exclut `Write` et
> `Edit`, et non par une consigne de prudence.
>
> Une sixième, `/etat-des-lanes`, répond à *où en est-on* : elle rapporte pour chaque branche
> `espace/*` son worktree, ses tâches finies non mergées, ses cartes non suivies et son retard sur
> `origin/develop`. Elle sert à décider, et ne propose jamais de tâche à reprendre (cf. `OPS-025`).
> Le backlog en est volontairement absent : elle montre ce qui est **engagé**, pas ce qui est
> envisagé.
>
> Une septième, `/evoquer-un-sujet`, répond à *est-ce qu'on le fait* — le moment qui **précède** le
> cadrage, quand ni la réponse ni la lane ne sont acquises. Elle reformule le manque, pèse les
> pistes, et conclut par l'une de quatre sorties : à faire · à ne pas faire · pas maintenant · aucun
> verdict, mais ce qui manque pour trancher. Verdict « à faire » → elle **nomme** les tâches à
> ouvrir, une par lane, et s'arrête : l'ouverture reste un geste humain. Elle n'écrit **aucune
> trace** — un sujet non tranché disparaît avec la session, prix assumé pour que la conversation
> reste libre (`OPS-034`).
>
> **L'implémentation appartient à une commande**, et c'est celle qui ouvre : ouvrir une tâche pour
> s'arrêter juste après n'a pas de sens, et le code vivait jusqu'ici entre deux commandes,
> c'est-à-dire nulle part. Elle **s'interrompt sur une preuve fraîche** et attend le verdict humain ;
> les tests et la documentation ne s'écrivent qu'ensuite, pour ne pas couvrir un code que la
> relecture va faire bouger.
>
> Le cycle **se referme** : après `/commit-tache`, la tâche suivante se rouvre par
> `/demarrer-tache`. C'est ce retour qui empêche deux tâches de finir dans les mêmes fichiers — un
> découpage a posteriori n'est plus possible sans reconstruire les versions intermédiaires à la
> main.
>
> Une huitième, `/demarrer-taches`, **automatise ce retour** au lieu de le contourner : elle prend
> une plage de cartes déjà cadrées du même espace et enchaîne les tours complets — implémenter,
> prouver, tester, documenter, **commiter** — avant d'ouvrir le suivant. Une série de cinq tâches
> produit **cinq commits** ; ce qu'elle économise est la reprise de main entre deux, pas un commit.
> Le verdict humain, lui, est déplacé et non supprimé : l'agent continue tant que sa preuve est
> nette, s'arrête net sinon, et la série entière se relit en une passe avant `/creer-pr`.

### Numérotation des tâches — un compteur par espace

Chaque espace possède **son** préfixe et **son** compteur. Deux espaces qui avancent en parallèle
ne peuvent donc plus réserver le même numéro : c'est le seul point de cette convention.

```
dev-book/tasks/ENT-001-selection-de-la-table.md
dev-book/tasks/API-003-devoiler-un-lot.md

feat(entreprise): la sélection de la Table — task ENT-001
```

- **Le préfixe est celui de l'espace sur lequel le commit atterrit**, pas celui du dossier touché.
  Aucun arbitrage à faire : la branche est déjà choisie quand vient le moment de nommer la tâche.
- **Numéro suivant** — un `ls dev-book/tasks/` ne suffit **pas** : il ne voit que l'arbre courant,
  qui peut être en retard sur `develop` ou sur un worktree. Une commande couvre toutes les branches
  et tous les remotes :

  ```bash
  git log --all --diff-filter=A --name-only --format= -- 'dev-book/tasks/ENT-*' \
    | grep -oE 'ENT-[0-9]{3}' | sort -u | tail -1
  ```

  Elle reste bornée à **un** espace : c'est ce qui la rend praticable, là où l'ancienne règle
  demandait de passer en revue toutes les branches du dépôt et n'était jamais appliquée. Le piège
  n'est pas théorique — `OPS-002` a d'abord été numérotée `OPS-001` depuis un arbre en retard.
- **Trois chiffres**, préfixe en majuscules : `ENT-007`, jamais `ENT-7`.
- Le slug ne répète pas le préfixe : `ENT-001-selection-de-la-table.md`, pas
  `ENT-001-entreprise-selection-de-la-table.md`.
- La mention en commit et en docblock : `— task ENT-001`.

> **Les tâches `001` à `164` sont gelées.** Elles gardent leur numérotation séquentielle historique
> et ne sont pas renumérotées : les 214 fichiers source qui les citent restent valides. Les deux
> conventions cohabitent — un identifiant purement numérique désigne toujours une tâche d'avant le
> 2026-07-27. Cf. `dev-book/tasks/OPS-001-numerotation-des-taches-par-lane.md` (le compteur) et
> `OPS-002-branches-par-espace-et-travail-parallele.md` (la structure des branches).

## Convention de commits — Conventional Commits + scope

Format : `type(scope): description` (impératif, minuscule, sans point final).

**Types** : `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `perf`, `ci`, `build`, `style`.

**Scopes (monorepo)** : `api`, `webapp`, `docs`, `ci`, `infra`, `db`, `deps`. Composables si
pertinent : `feat(api/credits): …`.

Pour un travail **transverse ou de cadrage** (qui ne se rattache pas à un scope code), le scope =
l'**aire / la feature**, généralement l'espace de la branche — ex. `docs(cadrage-architecture): …`.

Le titre se termine par la référence de tâche : `feat(entreprise): la sélection de la Table — task
ENT-001`. Avec la carte de tâche, ce type est le **seul** endroit où la nature du travail est
écrite — le nom de branche, lui, ne dit que l'espace.

Exemples :

```
feat(api): add credit consumption on profile reveal
fix(webapp): guard recruiter search against empty query
chore(ci): add webapp-quality workflow
docs(architecture): document Jarvi upsert via externalId
```

Un **breaking change** se signale par `!` (`feat(api)!: …`) ou un footer `BREAKING CHANGE:`.

> La convention est vérifiée automatiquement en CI (**commitlint**, `.commitlintrc.json` +
> workflow `commit-check.yml`) et porte le **versioning sémantique**.

## Stratégie de merge

**Rebase & merge, et rien d'autre.**

L'objectif est de désactiver squash et merge commit **au niveau du dépôt** (GitHub → *Settings* →
*General* → *Pull Requests*, ou `gh api -X PATCH repos/<owner>/<repo> -F allow_squash_merge=false
-F allow_merge_commit=false -F allow_rebase_merge=true`) — la règle cesse alors d'être une
discipline pour devenir une contrainte de l'outil. Les deux voies exigent le droit **admin** sur
le dépôt.

> Tant que ce réglage n'est pas appliqué : choisir **explicitement** *Rebase and merge* dans le
> menu déroulant du bouton de merge — le défaut GitHub est *Squash*, et un clic sur le défaut
> écrase toutes les tâches d'une branche en un seul commit. En CLI : `gh pr merge --rebase`.

Conséquence directe : **les commits d'une branche sont le livrable**. Ils arrivent tels quels sur
`develop`, un par un. Il n'y a plus de filet — aucun squash ne viendra recoudre une branche sale.

- **Un commit = une tâche**, sur la branche comme sur `develop`. Une branche peut en porter
  plusieurs ; elles atterrissent toutes, dans l'ordre.
- Pas de commit `wip` ni de `fixup` : nettoyer localement (`git commit --amend`, `git rebase -i`)
  **avant** d'ouvrir la PR.
- La CI **`commit-check` valide chaque commit de la PR**, pas seulement le titre : un commit
  malformé au milieu d'une série bloque la PR entière.
- Le rebase **réécrit les SHA** : après merge, la branche locale doit être réalignée
  (`git fetch origin && git reset --hard origin/develop`), jamais re-mergée telle quelle.
- Historique **linéaire**, compatible avec la branch protection.

> Si *automatically delete head branches* est actif, une branche est supprimée **côté distant**
> après merge — la repousser (`git push -u`) la recrée, la branche locale n'est jamais touchée.

## Nettoyage des branches

- **Suppression automatique de la branche après merge** (GitHub : *automatically delete head
  branches*) — locale **et** distante. Vaut pour le flux d'intégration **et** la prod.
- **Exception : les `espace/*`** survivent au merge et sont réutilisées jusqu'à la fin du MVP v1 ;
  elles sont réalignées sur `develop` entre deux tâches, pas recréées.
- `hotfix/*` est **supprimée après merge** : elle n'existe que le temps d'un correctif.
- Élagage local périodique : `git fetch --prune` (supprime les refs distantes disparues).

> Ne jamais créer de branche nommée `espace` tout court : elle rendrait impossible toute
> `espace/<nom>`.

## Règles de PR

**La PR est le point de contrôle** : c'est là que la CI tourne (pré-merge). Toute branche de
travail passe par une PR vers `develop`.

**Les quality gates tournent à l'ouverture de la PR, pas à chaque commit** : une branche empile
plusieurs tâches pour un seul passage. Pendant le développement, seuls les tests impactés et la
régénération d'`api/openapi.json` (contenu du commit, pas une gate) sont exigés.

Avant de merger une PR :

1. Le scope de **chaque commit** correspond à une tâche (`dev-book/tasks/`), pas de refactor hors
   périmètre.
2. Les **gates de pré-merge passent** (CI verte) — cf.
   [testing-and-ci.md](../3.quality/testing-and-ci.md).
3. La [checklist de review](../3.quality/code-review-checklist.md) est satisfaite.
4. **Chaque commit** respecte la convention (ils atterrissent tels quels sur `develop`) ; le titre
   de PR résume l'ensemble.

### Titre de PR

Format : `<Périmètre> : <ce que ça change>`, en français, sans référence de tâche.

Le périmètre est **l'espace de la branche** — `Espace entreprise`, `Espace admin`, `Socle webapp`,
`Outillage` — ou `API`. Ce qui suit dit le CHANGEMENT, pas le moyen : « le dévoilement devient un
geste », pas « ajout de useReveal ».

```
API : /v1/me porte l'organisation du recruteur
Espace entreprise : le dévoilement, l'export, et la passe de design
Socle webapp : revenir à la page demandée après connexion
```

Ce titre n'est **pas** un Conventional Commit et aucune CI ne le vérifie : `commit-check` analyse la
plage de commits, pas le titre. Le merge se faisant en rebase, il ne laisse d'ailleurs aucune trace
dans l'historique — il ne sert qu'à lire une liste de PR. C'est justement pourquoi il doit dire le
périmètre : sans lui, « Revenir à la page demandée après connexion » ne dit pas de quel espace il
s'agit.

Une PR qui porte plusieurs tâches les **énumère** plutôt que d'en élire une : le titre résume
l'ensemble, il ne met pas la première en avant.

### Contexte solo (dev humain unique + assistance IA)

- **Pas de review humaine requise** (impossible à imposer en solo : GitHub exige un 2ᵉ approbateur).
  La review = **auto-relecture du diff** + **`/code-review` (IA)** avant merge.
- **Branch protection** `develop` / `main` : **required status checks (CI verte)** + historique
  linéaire ; **pas** de *required reviewers*. Pas de push direct (PR obligatoire).
- La PR reste systématique (gate CI + traçabilité), même mergée par soi-même.

> Outil : `gh pr create` / `gh pr merge --rebase`.

## Versioning, tags & releases

- **SemVer** `MAJOR.MINOR.PATCH`, dérivé des Conventional Commits (`fix:`→patch, `feat:`→minor,
  `!`/`BREAKING CHANGE`→major). En **0.x**, un breaking change bump le **minor**.
- **Versioning unifié / lockstep** : **un seul** numéro pour la plateforme, tag **`vX.Y.Z`** couvrant
  `webapp` + `api` (qui partagent un contrat et déploient ensemble via `docker-compose`). Un seul
  `CHANGELOG.md` à la racine.

Flux de release :

```
espace/<espace> → PR → develop  ──(prêt à livrer)──►  main
                                                  │
                                                  ▼
                                          tag vX.Y.Z + release GitHub
                                                  │
                                                  ▼
                                          make prod-deploy (CD)
```

- Le **tag** est la référence déployée en prod (cf.
  [testing-and-ci.md](../3.quality/testing-and-ci.md)).

## Corps du message & références fichiers

Structure d'un message de commit :

1. **Titre** — Conventional Commit (`type(scope): description`).
2. **Corps** (facultatif sur un petit diff) — ce qui change et pourquoi, en puces.
3. **Références fichiers** — terminer par une section `Fichiers:` listant les fichiers touchés
   avec un libellé court, groupés par dossier si nombreux.

**Pas de trailer `Co-Authored-By`** (ni autre trailer d'attribution).

Exemple :

```
docs(cadrage-architecture): complète conventions API & tests

- conventions API : réponses plates, relations uuid scalaire
- stratégie de tests API : exhaustif par route

Fichiers:
- docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md (+) — conventions API
- docs/showcase-recruiter-webapp-saas/content/3.quality/api-testing.md (+) — tests API
- docs/showcase-recruiter-webapp-saas/content/index.md (M) — index
```

Notation : `(+)` ajout · `(M)` modification · `(D)` suppression.
