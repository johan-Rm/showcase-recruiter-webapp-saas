# TASK OPS-012 — ce que le projet a reçu sort de ce qu'il a écrit

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — `requirements/` à la racine, brief et questionnaires
  déplacés, `inspirations/` ouvert.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche ne déplace que des documents Markdown.
- **Réf** :
  - task `OPS-004` (la charte quitte le cadrage pour `docs/`)
  - task `OPS-006` (les maquettes quittent le cadrage pour `ui-ux-design/`)

## Contexte

`dev-book/cadrage/` était décrit comme « la matière première : brief, charte, maquettes, règles
métier ». Deux de ces quatre l'ont déjà quitté — la charte vers `docs/…/4.design/` en `OPS-004`, les
maquettes vers `ui-ux-design/` en `OPS-006`. Restaient le brief et deux questionnaires de récolte,
dans un sous-dossier `requirements/` qui côtoyait les parcours et les règles métier écrits par
l'équipe.

Or ces documents ne se maintiennent pas : personne ne les a écrits ici, et personne ne les mettra à
jour. Les garder à côté de documents vivants brouille le seul signal qui compte — **peut-on corriger
ce fichier, ou seulement le citer ?**

## Périmètre

- Un dossier `requirements/` à la racine, avec `brief/` et `inspirations/`.
- Les trois documents de `dev-book/cadrage/requirements/` déplacés dans `requirements/brief/`.
- Les références vivantes repointées.

## Hors périmètre

- **La réduction du cadrage**, annoncée mais non traitée ici.
- Le renommage des questionnaires, qui portent encore leur nom d'export Word.
- Les cartes de tâches historiques qui citent l'ancien chemin.

## Décisions de cadrage

### `requirements/` à la racine, et deux sous-dossiers

Le nom retenu est celui du dossier d'origine. `brief/` porte ce qui a été reçu par écrit,
`inspirations/` ce qui l'a été par l'image — références visuelles et produits cités en exemple.

`inspirations/` est créé **vide**, à dessein : un emplacement qui existe se remplit, un emplacement
qu'il faut inventer au moment où l'on en a besoin ne se crée jamais et son contenu atterrit
ailleurs.

### Un README plutôt qu'un `.gitkeep`

Git ne conserve pas un dossier vide. Un `.gitkeep` l'aurait tenu sans rien dire — et `OPS-006` a
justement supprimé un `dev-book/produit/` qui n'était qu'un `.gitkeep` sans définition, dont plus
personne ne savait ce qu'il devait accueillir.

Le README tient le dossier **et** dit ce qui y va. Celui d'`inspirations/` pose en plus la frontière
avec `ui-ux-design/` : une inspiration vient de l'extérieur et ne s'édite pas, une maquette est
produite par le projet.

### Les questionnaires gardent leur nom d'export

`questionnaire graphique – showcase-recruiter-webapp-saas.docx.md` — espaces, tiret cadratin, `.docx` fossile. Le nom est
laid mais il trace l'origine : ces fichiers sont des conversions, et le nom dit d'où ils viennent.
Les renommer relèverait d'une décision séparée.

## Règles et cas limites

- Les chemins relatifs depuis `dev-book/cadrage/` traversent maintenant deux niveaux de plus. Les
  deux repointages ont été **vérifiés sur disque**, pas seulement réécrits.
- `docs/…/2.architecture/architecture.md` cite aussi le brief, mais le fichier porte du travail non
  commité d'une autre tâche : il n'a pas été touché. Voir « Reste à faire ».
- Les cartes de tâches historiques qui citent `dev-book/cadrage/requirements/brand_guidelines.md`
  sont laissées telles quelles — registre du passé, déjà périmé depuis `OPS-004`.

## Documentation impactée

- `requirements/README.md` (+) — ce que le dossier est, et où va le reste.
- `requirements/inspirations/README.md` (+) — la frontière avec l'atelier de maquettes.
- `dev-book/cadrage/admin/ui/arborescence.md`, `dev-book/cadrage/entreprise/vue_d_ensemble.md` —
  chemins repointés.

## Vérification

- **Déplacement** : `git status` rapporte trois renommages à 100 % de similarité — l'historique des
  trois documents est conservé.
- **Chemins relatifs** : les deux références repointées ont été résolues sur disque depuis le
  dossier du fichier citant. Les deux existent.
- **Isolement** : le worktree portait déjà le travail non commité d'`OPS-011` (fusion `AGENTS.md` →
  `CLAUDE.md`), dont une suppression **déjà indexée**. Le commit a donc été fait par chemins
  explicites pour ne pas l'emporter.

## Livré

- `requirements/brief/` (R) — les trois documents, depuis `dev-book/cadrage/requirements/`.
- `requirements/inspirations/README.md` (+) — dossier ouvert, vide.
- `requirements/README.md` (+).
- Deux documents de cadrage repointés.

## Reste à faire (hors périmètre de cette tâche)

- **`docs/…/2.architecture/architecture.md` cite encore l'ancien chemin du brief.** Non touché :
  une autre tâche y travaille. À repointer vers `requirements/brief/initial_brief.md`.
- **La réduction de `dev-book/cadrage/`** — annoncée, à cadrer.
- **Le renommage des questionnaires**, si la trace de l'export Word cesse d'avoir de la valeur.

## Définition de terminé

- [x] `requirements/brief/` porte les trois documents, avec leur historique.
- [x] `requirements/inspirations/` existe et se documente.
- [x] Les références vivantes de `dev-book/cadrage/` résolvent.
- [x] Aucun fichier d'une autre tâche n'est entré dans le commit.
