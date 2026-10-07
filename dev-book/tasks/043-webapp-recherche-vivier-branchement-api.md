# TASK 043 — Webapp : recherche du vivier (barre + filtres + pagination) branchée sur l'API

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à faire.
- **Type** : Phase 2 — Espace entreprise / recherche vivier (front).
- **Prolonge** : tâche `038` (page « Le Vivier » mockup statique) ; **consomme** `035` (endpoint recherche).
- **Dépend de** : `035` (endpoint `/v1/search/candidates`), `037` (auth/BFF), `038` (coquille Vivier).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` (§ Moteur & passage à l'échelle)
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`

## Contexte

La tâche `038` pose « Le Vivier » en **mockup statique** (données factices, aucun branchement API).
La tâche `035` livre l'**endpoint de recherche serveur** (réplica Postgres, cartes masquées). Cette
tâche **branche la vraie recherche** dans le front : l'UI n'orchestre que **requête + affichage**,
la donnée **ne descend pas** dans le navigateur.

## Décision structurante (benchmark 2026-07-06)

La recherche reste **serveur** : moteur **Postgres FTS + `pg_trgm`** en V1 ; le front **ne charge
jamais le dump complet** du vivier (poids réseau, RAM, temps de build rédhibitoires en mobile/PWA,
et exposition de la donnée non dévoilée). Détail et chiffres : cadrage
`recherche_et_masquage.md` § Moteur & passage à l'échelle.

## Objectif

Fournir une recherche du vivier **utilisable en preprod**, **mobile-first**, servie par l'API, qui
n'affiche que des **cartes masquées**.

## Périmètre

- **Barre de recherche debouncée** (~200 ms) + **filtres** (critères de
  `criteres_de_filtrage_profils.md`) → query params vers `GET /v1/search/candidates`.
- **Pagination / scroll infini** (≈ 20 résultats/page) ; **jamais** de récupération massive côté client.
- Affichage des **cartes candidat masquées** telles que servies par l'API (aucun démasquage client).
- États **chargement / vide / erreur** ; accessibilité (clavier, labels, focus) ; **SSR-safe**
  (la recherche est une interaction client, pas un enjeu d'hydratation).
- **Couche d'accès API dédiée** (service/composable), pas d'appel direct depuis le composant.

## Invariants

- Le front ne reçoit et n'affiche que des **cartes masquées** servies par l'API ; **aucune** donnée
  sensible / non dévoilée côté client (cohérent task 006/014/035).
- **Aucune recherche « tout client »** sur un dump complet (cf. décision benchmark 2026-07-06).
- **Pagination côté serveur** ; pas de chargement de la base entière.

## Critères d'acceptation

- [ ] La recherche interroge `/v1/search/candidates` (mots-clés + filtres) et affiche des cartes masquées.
- [ ] Debounce + pagination/scroll infini fonctionnels ; payload limité à une page de résultats.
- [ ] Rendu **mobile-first**, accessible (clavier/labels/focus), **SSR-safe**.
- [ ] `webapp-quality` vert (au scaffold disponible).

## Hors périmètre

- Endpoint / back de recherche → tâches `014` / `035`.
- Recherches enregistrées / alertes → backlog (`SavedSearch`).
- Recherche assistée par IA → V2.
- **PWA** (install, offline, cache résultats récents, push) → V2 (cf. `todo.md`).
