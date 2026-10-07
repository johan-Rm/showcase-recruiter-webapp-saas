# TASK OPS-017 — Les arborescences rejoignent la doc technique

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — les deux cartes de navigation migrées dans
  `2.architecture/`, deux placeholders vides et une vue d'ensemble dupliquée supprimés.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — aucun code exécutable. La vérification est la résolution de chaque lien
  déplacé ou repointé.
- **Réf** : `OPS-013` · `OPS-014` · `OPS-015` (le même mouvement de réduction du cadrage) ·
  [architecture.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md)

## Contexte

`dev-book/cadrage/` est écrit **pour les clients** : ses documents sont exportés et lus par des
non-techniciens. Trois tâches successives l'ont déjà allégé de ce qui ne s'adresse pas à eux —
sources reçues, roadmap, runbooks, carte des domaines.

Il restait trois anomalies du même ordre :

- **Les deux `arborescence.md`** (espaces entreprise et admin) sont des cartes de routes. Celle de
  l'admin s'ouvre sur « ⚠️ **Statut : proposition de cadrage (2026-07-04)** » et détaille les rôles
  `ROLE_ADMIN_*` : un statut, une date, du jargon d'implémentation — trois des interdits d'un
  document de cadrage, dans le premier écran.
- **`vue-ensemble-plateforme.html`**, une vue d'ensemble de la plateforme dont toute la substance —
  les quatre blocs et les huit sous-domaines — est déjà portée par `architecture.md`, qui en est le
  propriétaire.
- **`public website/`**, un dossier ne contenant qu'un `.gitkeep`, placeholder d'un contenu jamais
  écrit.

## Périmètre

- Déplacer les deux `arborescence.md` vers `docs/showcase-recruiter-webapp-saas/content/2.architecture/`, renommées par
  espace, et les inscrire dans l'index de la section.
- Repointer les références vivantes, dans les deux sens.
- Supprimer `dev-book/cadrage/vue-ensemble-plateforme.html` et `dev-book/cadrage/public website/`.

## Hors périmètre

- **Les voisines de `entreprise/ui/`** (`application_shell.md`, `smart_home.md`, `vue_la_table.md`…)
  qui sont de la même nature technique. Leur sort est une décision distincte ; cette carte ne
  déplace que ce qui a été demandé.
- **Les cartes de tâche closes** qui citent l'ancien chemin — `072`, `097`, `120`, `148`, `159`,
  `OPS-012`. Journal daté, le réécrire le falsifierait.

## Décisions de cadrage

### Destination : `2.architecture/`, pas une section nouvelle

Ce sont des cartes de routes et de portées d'accès — la même nature que `api-routes.md` et
`responsibility-boundaries.md`, déjà voisines. Créer une section dédiée aurait ajouté un niveau à
indexer et à maintenir pour deux documents.

### Renommées par espace, en gardant le mot « arborescence »

Deux fichiers homonymes ne peuvent pas cohabiter dans un même dossier. `arborescence-admin.md` et
`arborescence-entreprise.md` plutôt qu'un `*-routes.md` aligné sur `api-routes.md` : une vingtaine
de références existantes disent « arborescence », et le terme est celui que le projet emploie.

### Le cadrage garde ses liens, et pointe désormais vers la doc technique

Six documents de cadrage renvoient vers l'arborescence entreprise. Leurs liens ont été repointés
plutôt que retirés : les couper aurait privé le lecteur de la carte des routes sans rien lui offrir
à la place.

C'est la contrepartie assumée du choix de ne déplacer que les arborescences — le cadrage, écrit pour
les clients, pointe maintenant vers de la documentation technique. Déplacer tout `entreprise/ui/`
aurait évité cette couture ; ce n'était pas le périmètre retenu.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code. Chaque lien touché a été résolu sur le
disque :

- **Liens sortants des deux fichiers déplacés** : 7 sur 7 résolvent. Contrôlés un par un après
  réécriture, `[ -e ]` sur chaque cible depuis le nouveau dossier.
- **Un lien était déjà cassé avant le déplacement** : l'arborescence admin pointait vers
  `../../todo.md`, c'est-à-dire `dev-book/cadrage/todo.md`, que `OPS-015` avait remonté à
  `dev-book/todo.md`. Corrigé au passage.
- **Références entrantes** : 11 fichiers réécrits — 6 documents de cadrage et 5 cartes de tâche
  **ouvertes** (`098`, `099`, `100`, `101`, `102`).
- `git grep "ui/arborescence.md"` hors `dev-book/tasks/` → **aucun résultat**. Les seules
  occurrences restantes sont dans des cartes closes.
- Le texte visible des liens a été aligné sur la cible : `[ui/arborescence.md]` disait encore
  l'ancien chemin alors que la cible était déjà bonne.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` **(R)** — depuis
  `dev-book/cadrage/entreprise/ui/arborescence.md`, 4 liens sortants recalculés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` **(R)** — depuis
  `dev-book/cadrage/admin/ui/arborescence.md`, 3 liens sortants recalculés dont un déjà cassé.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/index.md` **(M)** — les deux documents inscrits au sommaire.
- 6 documents de cadrage **(M)** — `les_2_outils.md`, `vue_d_ensemble.md`, et dans `ui/` :
  `README.md`, `application_shell.md`, `smart_home.md`, `vue_la_table.md`.
- 5 cartes de tâche ouvertes **(M)** — `098` à `102`.
- `dev-book/cadrage/vue-ensemble-plateforme.html` **(D)** — substance portée par `architecture.md`.
- `dev-book/cadrage/public website/.gitkeep` **(D)** — placeholder vide.
- `dev-book/cadrage/admin/.gitkeep` **(D)** — le dossier n'avait plus que ce placeholder une fois
  son arborescence partie ; `admin/` et `admin/ui/` disparaissent avec lui.

## Reste à faire (hors périmètre)

- **`dev-book/cadrage/entreprise/ui/`** garde neuf documents de nature technique (shell, topbar,
  inspecteur, vues). Si le cadrage doit ne contenir que du client-facing, ils suivront.

## Définition de terminé

- [x] Les deux arborescences vivent dans `2.architecture/` et figurent à son index.
- [x] Les 7 liens sortants des fichiers déplacés résolvent.
- [x] Les références vivantes sont repointées ; les cartes closes sont intactes.
- [x] Le placeholder vide et la vue d'ensemble dupliquée sont supprimés.
