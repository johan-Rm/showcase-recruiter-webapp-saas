# TASK 133 — admin : store candidats + migration de /candidats vers l'orchestrateur

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : premier domaine migré après le vivier vers l'orchestrateur `055b`. `/candidats`
  ne fait plus aucun `useFetch` de page.
- **Dépend de** : `055b` (orchestrateur + registre), `099` (endpoint `/v1/admin/candidates`).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts` · `stores-standard.md`.

## Contexte

Après le vivier, `/candidats` était le domaine à migrer : son entrée de registre existait déjà
(le vivier y était critique pour le taux de remplissage), mais la **liste** passait encore par le
`useFetch` de `useAdminCandidates`. On la fait passer par un store, comme le vivier.

## Livré

- `spaces/admin/app/stores/candidates.ts` — `useCandidatesStore` (Options API). State brut = la file
  paginée (`AdminCandidateQueue`) ; getters `getQueue` (passe-plat), `getCandidates` (liste dérivée),
  `getTotal` ; setters `setQueue` (défensif) / `reset`. Zéro réseau, zéro UI.
- `spaces/admin/app/bootstrap/loaders.ts` — `+loadCandidates` (fetch BFF `/api/admin/candidates` →
  `setQueue`, no-op si déjà chargée).
- `spaces/admin/app/bootstrap/registry.ts` — `/candidats` : `critical: [loadCandidates, loadVivier]`
  (liste et taux de remplissage tous deux rendus au SSR).
- `spaces/admin/app/composables/useAdminCandidates.ts` — devient **lecteur pur** du store ;
  `pending` / `error` dérivés du statut du bootstrap. `refresh` (jamais consommé) retiré.

## Décisions

- **Store DTO-brut** (comme vivier), `057` toujours différé — la file admin est déjà prête à
  l'affichage, aucun mapper.
- **Liste ET vivier critiques sur `/candidats`** : les deux sont rendus au SSR aujourd'hui ; les
  laisser en arrière-plan (palier 3, client-only) les ferait apparaître après coup.
- **`refresh` supprimé** : `candidats.vue` ne l'utilisait pas ; le rafraîchissement sur action
  (fraîcheur) sera porté au niveau du store/orchestrateur quand un écran actionnable candidats
  arrivera.

## Critères d'acceptation

- [x] `/candidats` chargé par l'orchestrateur (liste + vivier au SSR), aucun `useFetch` de page.
- [x] `candidats.vue` inchangé, rendu identique (compteur, table, taux de remplissage).
- [x] Store pur, conforme à `stores-standard.md`.
- [x] Aucun changement d'API (même endpoint BFF, même OpenAPI).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Fiche candidat** (`/candidats/:id`, `useAdminCandidate`) **non migrée** : elle garde son
  `useFetch` jusqu'à sa propre migration (route paramétrée → loader dépendant de l'`id`).
- **Fraîcheur** : le store se charge une fois par bootstrap ; pas encore de refetch sur action
  (aucun écran candidats actionnable à ce jour).
