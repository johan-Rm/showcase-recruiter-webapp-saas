# TASK 134 — admin : store dashboard + migration de la home vers l'orchestrateur

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : suite de la migration des domaines admin vers l'orchestrateur `055b`, après
  vivier (`130`/`055b`) et candidats (`133`). La home `/` ne fait plus de `useFetch` de page.
- **Dépend de** : `055b` (orchestrateur + registre), `102` (endpoint `/v1/admin/dashboard`).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts` · `stores-standard.md`.

## Livré

- `spaces/admin/app/stores/dashboard.ts` — `useDashboardStore` (Options API). State brut = les
  compteurs (`AdminDashboard`), getter passe-plat `getDashboard`, setters `setDashboard` (défensif)
  / `reset`. Zéro réseau, zéro UI.
- `spaces/admin/app/bootstrap/loaders.ts` — `+loadDashboard` (fetch BFF `/api/admin/dashboard` →
  `setDashboard`, no-op si déjà chargé).
- `spaces/admin/app/bootstrap/registry.ts` — `index` (la home `/`) : `critical: [loadDashboard]`.
- `spaces/admin/app/composables/useAdminDashboard.ts` — devient **lecteur pur** du store ;
  `pending` / `error` dérivés du statut du bootstrap.

## Décisions

- **Store DTO-brut** (comme vivier / candidates), `057` toujours différé — les compteurs sont déjà
  prêts à l'affichage, aucun mapper.
- **Clé de registre `index`** : nom de route Nuxt de `pages/index.vue` (la home), après retrait du
  suffixe i18n par l'orchestrateur.

## Critères d'acceptation

- [x] `/` chargée par l'orchestrateur (compteurs au SSR), aucun `useFetch` de page.
- [x] `index.vue` inchangé, rendu identique (tuiles, section vivier).
- [x] Store pur, conforme à `stores-standard.md`.
- [x] Aucun changement d'API (même endpoint BFF, même OpenAPI).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Chevauchement dashboard ↔ vivier** inchangé : la home lit ses propres compteurs
  (`/v1/admin/dashboard`), distincts de l'agrégat `/v1/admin/vivier` — deux endpoints, deux stores.
- Domaines restants à migrer : `utilisateurs`, `jarvi`, `entreprises`, `referentiels`, et la fiche
  candidat `/candidats/:id` (route paramétrée).
