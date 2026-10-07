# TASK OPS-007 — le cycle outillé de bout en bout : cadrer, commiter, ouvrir, réaligner

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — quatre commandes couvrent le cycle, la carte précède le code,
  et le commit exige tests et documentation.
- **Type** : Outillage — commandes projet et documentation du workflow, aucun changement applicatif.
- **Tests** : sans objet — la tâche ne produit que des fichiers Markdown (commandes `.claude/` et
  pages de doc). Aucun code exécutable, donc rien à couvrir ; l'épreuve de ces commandes est leur
  usage sur la tâche suivante.
- **Réf** :
  - task `OPS-001` (numérotation par espace) et `OPS-002` (branches par espace) — les commandes
    qu'on modifie ici en sont issues
  - task `126-workflow-lanes-espace.md`, `129-workflow-apres-pr.md` — création des trois commandes
  - `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md`, `ai-workflow.md`

## Contexte

Le cycle outillé partait du **positionnement de branche** (`/demarrer-tache`) et rattrapait la spec
au commit : la carte de tâche était rédigée **après** le code, `/commit-tache` la produisant de
mémoire. Deux conséquences.

D'abord, aucun moment n'était prévu pour **instruire le besoin**. Le cadrage se faisait dans le fil
de la conversation, au rythme de l'implémentation, et les inconnues se réglaient en hypothèses
silencieuses — visibles seulement quand le code partait dans la mauvaise direction.

Ensuite, `/commit-tache` ne demandait **ni test ni documentation**. Les gates qualité étant
repoussées à l'ouverture de la PR — à raison, une branche empile N tâches pour un seul passage CI —
plus rien ne s'assurait qu'un test existait, ni qu'une convention modifiée avait été répercutée.
Une doc qui ne bouge pas quand la règle change devient fausse en silence.

## Périmètre

Le cycle est outillé de bout en bout, **une commande par moment** :

- `/creer-tache` : nouvelle commande, cadrage interactif exhaustif → carte de tâche → branche.
- `/demarrer-tache` : supprimée, absorbée par la précédente.
- `/commit-tache` : la carte se **clôture** au lieu de se rédiger ; deux exigences bloquantes
  s'ajoutent — tests écrits, documentation impactée à jour.
- `/creer-pr` : nouvelle commande, gates qualité en une passe puis titre et corps prêts à coller.
- `/apres-pr` → `/apres-pr-mergee` : renommée.
- `git-workflow.md` et `ai-workflow.md` : le cycle décrit correspond à nouveau à l'outillage.

## Hors périmètre

- Aucune vérification automatisée de ces règles : elles sont portées par les commandes, pas par la
  CI. Un commit fait à la main les contourne toujours.
- La skill générique `create-pr` reste installée : elle sert aux autres dépôts, elle n'est
  simplement plus la référence ici.
- Le fond de `/apres-pr-mergee` est inchangé : seul son nom bouge.

## Décisions de cadrage

### `/creer-tache` absorbe le positionnement de branche

Une fois le cadrage fait, l'espace est connu ; la branche s'en déduit mécaniquement et ne mérite pas
une commande à elle. Enchaîner deux commandes pour une seule intention créait surtout l'occasion
d'en oublier une. Reprendre une branche déjà ouverte reste un `git switch` : ça n'a jamais demandé
d'outil.

### La carte devient une spec, pas un compte rendu

`dev-book/README.md` annonçait déjà la carte comme « spec avant implémentation » ; c'est le workflow
qui ne le tenait pas. L'ossature écrite par `/creer-tache` est donc prospective — *Périmètre*, *Hors
périmètre*, *Définition de terminé* — et `/commit-tache` y ajoute *Vérification*, *Livré*, *Reste à
faire*. Si l'implémentation tranche autrement que le cadrage, c'est la carte qu'on corrige : elle
est ce qui reste.

### Le tableau des espaces n'est plus recopié dans la commande

`/demarrer-tache` en gardait une copie, qui avait déjà raté l'ajout d'`espace/design` (`UX`) par
`OPS-006` : le tableau y listait sept espaces quand `git-workflow.md` en comptait huit. La nouvelle
commande **pointe** vers la page au lieu de dupliquer — c'est la règle de non-duplication du dépôt,
et cette dérive en est la démonstration.

### Les tests sont exigés, l'exemption est nommée

Une règle sans exception aurait rendu incommittable la plupart des tâches `OPS` — ou fait écrire des
tests décoratifs pour la satisfaire. Une règle sans garde-fou n'aurait rien exigé du tout. D'où
l'exemption **écrite dans la carte** : `**Tests** : sans objet — <raison>`, réservée aux tâches sans
code exécutable. L'agent ne se l'accorde pas seul sur du code applicatif : il s'arrête et demande.

Le choix porte sur l'**écriture** des tests, pas sur l'exécution d'une suite complète : les tests
impactés sont joués (quelques secondes), la suite phpunit (~7 min) reste à l'ouverture de la PR.

### L'ouverture de PR méritait une commande, pas une skill générique

La skill partagée `create-pr` couvrait ce moment, mais avec **quatre défauts faux sur ce dépôt** :
squash & merge (le dépôt vise le rebase seul), suppression de la branche après merge (les `espace/*`
survivent et resservent), titre en Conventional Commit (`OPS-005` a tranché
`<Périmètre> : <ce que ça change>`, précisément *parce que* le merge est en rebase), et `gh pr
create` — `gh` n'est pas authentifié dans cet environnement, l'étape échouait à chaque fois.

Il manquait aussi tout le savoir local du moment le plus risqué du cycle : la régénération
d'`api/openapi.json` que le `make` local ne contrôle pas, le flake connu des tests de recherche qui
fait accuser le diff à tort, la base `showcase-recruiter-webapp-saas_test` partagée entre lanes, le push sur `origin` et non
`gd`, et la suite phpunit qu'il ne faut pas attendre en avant-plan.

`/creer-pr` ne commite pas : le travail arrive déjà en commits de tâche.

### `/apres-pr` devient `/apres-pr-mergee`

Avec une commande qui *ouvre* la PR, « après PR » ne voulait plus dire grand-chose — après l'avoir
ouverte ? mergée ? Le nom porte désormais la précondition que la commande vérifie de toute façon dès
son premier contrôle : la PR est mergée, sinon elle s'arrête.

### La doc se vérifie par déclencheurs, pas par balayage

`/commit-tache` porte une table `ce que touche le diff → page à mettre à jour`, couvrant
`docs/showcase-recruiter-webapp-saas/` et le `README.md` du dossier concerné. Vérifier toute la doc à chaque commit aurait
produit du bruit et aurait fini ignoré. `dev-book/cadrage/` en est explicitement exclu : écrit pour
les clients, il suit son propre rythme.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` — carte écrite avant le code, tests et
  doc dans le commit, liste des commandes.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` — étape de cadrage dans le workflow de
  delivery, et levée de la contradiction avec l'ancien « pas de tests après chaque modification ».

## Vérification

- **Les commandes sont réellement chargées.** Le harness a relu `.claude/commands/` à chaque
  écriture : `creer-tache`, puis `creer-pr`, puis `apres-pr-mergee` sont apparues dans la liste des
  commandes disponibles, et `demarrer-tache` en a disparu après le `git rm`. La description de
  `commit-tache` s'est mise à jour au moment où son *front matter* a changé — preuve que l'en-tête
  est parsé, pas seulement le corps.
- **`/commit-tache` a été jouée sur cette tâche même.** La commande s'est chargée, sa procédure a
  été suivie point par point, et c'est elle qui a produit ce commit — y compris l'exemption de tests
  ci-dessus, exercée pour de vrai.
- **Aucune référence morte.** `grep` sur `demarrer-tache` et `apres-pr` hors `dev-book/tasks/` ne
  laisse que les mentions volontaires : celle de `creer-pr.md` qui explique la suppression, et les
  cartes historiques (`126`, `129`, `OPS-001`, `OPS-002`), registre qu'on ne réécrit pas.
  `CONTRIBUTING.md`, `parallel-work.md`, `AGENTS.md` et `README.md` ne citaient aucune commande.
- **Le tableau des espaces n'est plus dupliqué** : `creer-tache.md` renvoie vers `git-workflow.md`,
  qui reste le seul endroit où les huit espaces sont listés.

## Livré

- `.claude/commands/creer-tache.md` (+) — instruction préalable, douze dimensions de cadrage,
  restitution validée avant écriture, positionnement de branche, ossature de carte.
- `.claude/commands/demarrer-tache.md` (D) — absorbée.
- `.claude/commands/commit-tache.md` (M) — carte clôturée au lieu d'être rédigée, tests et doc
  bloquants, table des déclencheurs, étapes renumérotées.
- `.claude/commands/creer-pr.md` (+) — gates par périmètre, pièges locaux, titre `OPS-005`, sortie
  prête à coller.
- `.claude/commands/apres-pr.md` → `apres-pr-mergee.md` (M) — renommée, avec le renvoi qui la
  distingue de `/creer-pr`.
- `docs/…/git-workflow.md` (M) — carte avant le code, tests et doc dans le commit, quatre commandes.
- `docs/…/ai-workflow.md` (M) — étape de cadrage, et fin de la contradiction sur les tests.

## Reste à faire (hors périmètre de cette tâche)

- **Aucune de ces règles n'est vérifiée par la CI.** Elles vivent dans les commandes ; un commit
  fait à la main les contourne toutes. La plus automatisable serait la présence d'une carte de tâche
  citée par le message de commit.
- **Le cycle complet n'a pas encore tourné.** `/creer-tache` n'a pas cadré cette tâche-ci — elle est
  née d'une conversation, et sa carte a été écrite avant le code mais après l'échange. `/creer-pr`
  sera éprouvée à l'ouverture de la PR qui porte ce commit, `/apres-pr-mergee` après son merge.
- **Les cartes historiques citent les anciens noms** (`126`, `129`, `OPS-001`, `OPS-002`). Registre,
  laissé tel quel.

## Définition de terminé

- [x] `/creer-tache` écrite : instruction préalable, douze dimensions de cadrage, restitution
      validée, branche, carte.
- [x] `/demarrer-tache` supprimée, plus aucune référence vivante hors registre historique.
- [x] `/commit-tache` : carte clôturée, tests bloquants avec exemption tracée, table des
      déclencheurs de doc, étapes renumérotées.
- [x] `/creer-pr` écrite : gates par périmètre, pièges locaux, titre `OPS-005`, sortie prête à
      coller faute de `gh`.
- [x] `/apres-pr` renommée `/apres-pr-mergee`, avec le renvoi qui la distingue de `/creer-pr`.
- [x] `git-workflow.md` et `ai-workflow.md` décrivent le cycle réel, quatre commandes.
- [x] `/commit-tache` éprouvée sur cette tâche même — c'est elle qui a produit ce commit.

> **Écart au cadrage.** Un septième critère demandait « une tâche réelle passée de bout en bout par
> les quatre commandes ». Il ne pouvait pas être coché ici : `/creer-pr` s'exécute *après* le
> commit, et `/apres-pr-mergee` après le merge. Un critère qu'aucun commit ne peut satisfaire n'est
> pas une définition de terminé mais une suite — il est passé en *Reste à faire*.
- [ ] Une tâche réelle passée de bout en bout par les quatre commandes.
