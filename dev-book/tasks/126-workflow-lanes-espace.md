# TASK 126 — workflow : lanes d'espace, un commit = une tâche

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25 — doc alignée + deux commandes projet.
- **Type** : Process / outillage — aucune ligne de code applicatif.
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` (le workflow de référence)
  - `.github/workflows/commit-check.yml` (commitlint sur **chaque** commit d'une PR)

## Contexte

Le workflow documenté décrivait des branches `feature/*` **courtes**, une par tâche, squashées puis
supprimées. Dans les faits le travail se mène par **espace produit** : plusieurs tâches se suivent
sur le même périmètre, et recréer une branche à chaque fois n'apporte rien. Les consignes de
cadrage (« un commit = une tâche », « la tâche est rédigée au moment du commit ») étaient répétées
à chaque session sans être écrites nulle part.

## Objectif

Écrire le workflow réellement pratiqué, et le rendre applicable sans avoir à s'en souvenir.

## Décisions

### Cinq lanes d'espace, longues

`feature/espace-admin` · `feature/espace-entreprise` · `feature/espace-candidat` ·
`feature/espace-public` · `feature/api`. Non supprimées avant la fin du MVP v1, **réutilisées**
tâche après tâche, réalignées sur `develop` entre deux PR. Le transverse se rattache à la lane de
l'espace consommateur, ou à `feature/api` s'il porte le contrat.

### Un commit = une tâche, fichier de tâche inclus

Le commit embarque le code **et** son `dev-book/tasks/NNN-*.md`. Le code est souvent écrit avant
que la tâche soit rédigée : elle l'est alors au moment du commit.

### Rebase & merge, plus de squash

Le squash écrasait les N tâches d'une lane en un seul commit sur `develop` — l'invariant ne
survivait qu'au prix d'une contrainte de rythme (merger avant d'enchaîner). En rebase, les commits
atterrissent tels quels. La CI `commit-check` valide **déjà** chaque commit d'une PR : le squash
jetait ce travail.

Contrepartie assumée : **les commits sont le livrable**. Pas de `wip`, pas de `fixup`, message
définitif dès le commit ; le nettoyage se fait localement avant d'ouvrir la PR. Et la CI ne testant
que le sommet d'une PR, un commit intermédiaire cassé atterrit sur `develop` — l'historique reste
lisible mais n'est plus garanti bissectable.

### Quality gates à l'ouverture de la PR, pas à chaque commit

Une lane empile plusieurs tâches pour un seul passage en CI : lancer `make quality` à chaque commit
multiplie le coût sans rien prouver de plus. Pendant le développement, seuls les **tests impactés**
et la régénération d'`api/openapi.json` sont exigés — cette dernière n'étant pas une gate mais du
**contenu de commit** (la CI `api-quality` la vérifie par `git diff --exit-code`).

## Livré

- `git-workflow.md` : section « Lanes d'espace », stratégie de merge réécrite, règles de PR.
- `.claude/commands/demarrer-tache.md` → `/demarrer-tache` : mappe le périmètre à la bonne lane,
  la crée ou la réaligne, refuse `develop` et `main`.
- `.claude/commands/commit-tache.md` → `/commit-tache` : découpe le diff par tâche, rédige le
  fichier de tâche manquant, staging par chemins explicites, message conforme.

## Reste à faire (hors périmètre de cette tâche)

- **Désactiver le squash sur le dépôt** (`allow_squash_merge=false`) : demande le droit *admin* sur
  `origin`, que le compte de développement n'a pas. Tant que ce n'est pas fait, choisir
  explicitement *Rebase and merge* au moment de merger — le défaut GitHub est *Squash*.
- ~~Trancher le rattachement du **transverse pur** (`docs/`, `.claude/`, CI)~~ — fait le 2026-07-27
  par `OPS-001-numerotation-des-taches-par-lane.md` : deux espaces de plus, `socle-webapp` et
  `outillage`, pour ce qu'aucun espace produit ne consomme. `OPS-002` a remplacé le lendemain les
  lanes `feature/espace-*` par des branches `espace/<espace>`.
