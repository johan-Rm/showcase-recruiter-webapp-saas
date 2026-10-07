# TASK OPS-015 — le suivi du projet remonte à la racine du dev-book

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — `todo.md` remonté, le README du dev-book réaligné sur ce que
  le dossier contient réellement.
- **Type** : Refactor — rangement documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche déplace un fichier Markdown et corrige un README.
- **Réf** : task `OPS-013` (la roadmap avait déjà remonté)

## Contexte

`todo.md` restait dans `dev-book/cadrage/` alors que la roadmap en était sortie une tâche plus tôt.
Les deux portent la même chose — l'avancement du projet **entier** — et les laisser à deux niveaux
différents n'avait aucune raison d'être.

Le README du dev-book, lui, décrivait encore un `cadrage/` contenant « brief, notes, parcours,
roadmap, infra ». Le brief est parti en `OPS-012`, la roadmap en `OPS-013` : la phrase était fausse
sur deux de ses cinq termes, et le tableau ne mentionnait aucun des deux fichiers de racine.

## Périmètre

- `dev-book/cadrage/todo.md` → `dev-book/todo.md`.
- Le README du dev-book décrit le dossier tel qu'il est.

## Hors périmètre

- Le contenu de `todo.md`, dont plusieurs sections sont périmées.
- La suite de la réduction de `dev-book/cadrage/`.

## Décisions de cadrage

### La racine porte ce qui parle du projet entier

`roadmap.md` et `todo.md` traversent tous les espaces. Les ranger dans `cadrage/` les mettait au même
niveau de lecture que le cadrage d'un écran d'entreprise — un lecteur cherchant l'avancement du
projet devait descendre dans un dossier consacré au produit.

La racine du dev-book porte donc le suivi ; les sous-dossiers portent la matière.

### Le README dit ce qui reste, et ce qui est parti

Trois sorties se sont accumulées en quatre tâches sans qu'aucune ne soit visible depuis le dossier :
maquettes vers `ui-ux-design/`, brief et questionnaires vers `requirements/`, charte vers
`docs/…/4.design/`. Le README les nomme désormais toutes les trois.

Sans cette liste, un lecteur qui cherche la charte dans `cadrage/` ne trouve rien et n'a aucune
indication de l'endroit où regarder — c'est le mode de rupture d'une réorganisation, pas celui d'un
déplacement isolé.

## Règles et cas limites

- `CLAUDE.md` pointait **déjà** `dev-book/todo.md` : la référence était fausse avant ce
  déplacement, elle est juste maintenant. Aucune correction à faire.
- Les cartes de tâches qui citent `dev-book/cadrage/todo.md` gardent l'ancien chemin — registre
  historique.

## Documentation impactée

- `dev-book/README.md` — tableau, mention des deux fichiers de racine et des trois sorties.

## Vérification

- **Les dix chemins du README résolvent** sur disque, les trois relatifs vers l'extérieur compris
  (`../ui-ux-design`, `../requirements`, `../docs/showcase-recruiter-webapp-saas/content/4.design`).
- **Aucune référence vivante** à `dev-book/cadrage/todo.md` hors cartes de tâches.

## Livré

- `dev-book/todo.md` (R) — depuis `dev-book/cadrage/`.
- `dev-book/README.md` (M) — tableau réaligné, sorties documentées.

## Reste à faire (hors périmètre de cette tâche)

- **`todo.md` est périmé par endroits** — sa section « Design » parle encore de Cormorant Garamond
  et d'une palette abandonnée, signalé depuis `OPS-004`.
- **`dev-book/cadrage/` continue de se réduire** : restent `vue-ensemble-plateforme.html`, `draft/`
  et les dossiers par espace.

## Définition de terminé

- [x] `roadmap.md` et `todo.md` sont à la racine du dev-book.
- [x] Le README les liste et décrit `cadrage/` pour ce qu'il contient encore.
- [x] Les sorties des quatre dernières tâches sont visibles depuis le dossier.
