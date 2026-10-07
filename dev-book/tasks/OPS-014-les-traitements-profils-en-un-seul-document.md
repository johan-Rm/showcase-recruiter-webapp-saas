# TASK OPS-014 — les traitements profils tiennent en un seul document

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — deux documents fusionnés en un, le runbook rangé, les
  scénarios de recette supprimés.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche ne déplace et ne fusionne que du Markdown.
- **Réf** :
  - task `OPS-013` (la carte des domaines rejoint la doc)
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md`

## Contexte

Le même sujet était documenté à deux endroits. `docs/…/vocabulary-pipeline.md` décrivait la chaîne
vocabulaire ; `dev-book/cadrage/traitements-profils.md` décrivait les **quatre** chaînes — ingestion,
vocabulaire, secteurs, écoles — donc la première en doublon, avec des chiffres et des invariants qui
ne se recoupaient pas exactement.

À côté, `runbook-traitements-profils.md` portait la procédure d'exécution, dans un dossier de
cadrage alors que `dev-book/runbooks/` existe, est documenté par le README du dev-book, est
symlinké par le site — et était **vide**.

Enfin `dev-book/cadrage/tests/` gardait deux scénarios de recette Insomnia.

## Périmètre

- Les deux documents fusionnent en `docs/…/2.architecture/profile-processing.md`.
- Le runbook rejoint `dev-book/runbooks/traitements-profils.md`.
- `dev-book/cadrage/tests/` supprimé.

## Hors périmètre

- La suite de la réduction de `dev-book/cadrage/`.
- Les cartes de tâches historiques qui citent `vocabulary-pipeline.md`.

## Décisions de cadrage

### Un seul document, et il change de nom

La fusion élargit le sujet : un fichier nommé `vocabulary-pipeline.md` qui décrirait l'ingestion
Jarvi, les secteurs et les écoles mentirait sur son titre. Le document fusionné s'appelle
`profile-processing.md`, et l'ancien nom disparaît.

L'ordre suit la lecture : les quatre chaînes, les commandes, l'ordre d'exécution, les invariants, le
modèle de données du vocabulaire, puis les limites.

### La roadmap des traitements ne suit pas

`traitements-profils.md` portait un tableau de vingt lignes — « Roadmap des traitements », avec ses
statuts et ses numéros de cartes. Son titre l'avouait : « commandes **& roadmap** ».

Ce n'est pas de la documentation : **la doc dit ce qui est vrai, les tâches disent ce qu'on va
faire**. Un document qui fait les deux devient faux à moitié dès la première livraison, et ce
tableau dupliquait vingt cartes de tâches qui portent déjà leur propre statut.

Ce qui restait ouvert dedans a été reversé dans la section « Ce qui reste à faire » du document
fusionné — écoles verrouillées, promotion `candidat → validé`, exposition du vocabulaire.

### Le runbook n'est pas de la doc technique

Il décrit **comment opérer** : photo avant, exécution, photo après, lecture de l'écart. Le README du
dev-book définit exactement ça — « une procédure = un fichier markdown reproductible (préconditions,
étapes, rollback, vérifications) ». Il rejoint donc `dev-book/runbooks/`, dont il devient le premier
occupant.

Le préfixe `runbook-` disparaît du nom : le dossier le porte.

## Règles et cas limites

- Les statistiques des deux documents ont été conservées telles quelles, sans arbitrage : elles
  datent de la même campagne de mesure.
- Les cartes de tâches qui citent `vocabulary-pipeline.md` gardent l'ancien chemin — registre
  historique, comme pour les déplacements précédents.

## Documentation impactée

- `docs/…/2.architecture/index.md` — la ligne du sommaire décrit maintenant les quatre chaînes.
- `.claude/commands/commit-tache.md` — le tableau des déclencheurs de doc pointait
  `vocabulary-pipeline.md`.
- `dev-book/resources/taxonomie/README.md` — même chemin.

## Vérification

- **Plus aucune référence morte** à `vocabulary-pipeline` ni à `runbook-traitements-profils` hors
  `dev-book/tasks/`.
- **`dev-book/runbooks/` n'est plus vide** : il porte son premier document.
- **`dev-book/cadrage/`** ne contient plus ni `traitements-profils.md`, ni son runbook, ni `tests/`.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` (+) — le document fusionné.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md` (D) — absorbé.
- `dev-book/cadrage/traitements-profils.md` (D) — absorbé.
- `dev-book/runbooks/traitements-profils.md` (R) — depuis le cadrage.
- `dev-book/cadrage/tests/` (D) — deux scénarios Insomnia.
- `docs/…/2.architecture/index.md`, `.claude/commands/commit-tache.md`,
  `dev-book/resources/taxonomie/README.md` (M) — chemins repointés.

## Reste à faire (hors périmètre de cette tâche)

- **`dev-book/cadrage/` continue de se réduire** : restent `vue-ensemble-plateforme.html`,
  `todo.md`, `draft/` et les dossiers par espace.
- **Le tableau de roadmap des traitements n'a pas été reversé ailleurs** : ses vingt lignes
  dupliquaient des cartes de tâches existantes, elles ont été abandonnées avec le document. Si le
  suivi consolidé manque, il se reconstruit depuis les cartes.

## Définition de terminé

- [x] Un seul document décrit les traitements, et son nom dit son périmètre.
- [x] Le runbook est dans `dev-book/runbooks/`.
- [x] `dev-book/cadrage/tests/` a disparu.
- [x] Aucune référence vivante ne pointe vers l'ancien nom.
