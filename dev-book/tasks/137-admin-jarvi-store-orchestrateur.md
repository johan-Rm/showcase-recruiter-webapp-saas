# TASK 137 — admin : store jarvi + migration de /jarvi vers l'orchestrateur

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : dernier domaine admin **simple** migré vers l'orchestrateur `055b`, après
  dashboard (`134`), candidats (`133`) et users (`136`). `/jarvi` ne fait plus de `useFetch` de page.
- **Dépend de** : `055b` (orchestrateur + registre), `101` (endpoint `/v1/admin/jarvi`).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts` · `stores-standard.md`.

## Livré

- `spaces/admin/app/stores/jarvi.ts` — `useJarviStore` (Options API). State brut = l'objet
  supervision (`AdminJarvi` : flux + agrégats), getter passe-plat `getJarvi`, setters `setJarvi`
  (défensif) / `reset`. Zéro réseau, zéro UI.
- `spaces/admin/app/bootstrap/loaders.ts` — `+loadJarvi` (fetch BFF `/api/admin/jarvi` →
  `setJarvi`, no-op si déjà chargé).
- `spaces/admin/app/bootstrap/registry.ts` — `jarvi` : `critical: [loadJarvi]`.
- `spaces/admin/app/composables/useAdminJarvi.ts` — devient **lecteur pur** du store ;
  `pending` / `error` dérivés du statut du bootstrap.

## Décisions

- **Store DTO-brut** objet unique (comme dashboard / vivier), `057` toujours différé — la donnée de
  supervision est déjà prête à l'affichage.

## Critères d'acceptation

- [x] `/jarvi` chargé par l'orchestrateur, aucun `useFetch` de page.
- [x] `jarvi.vue` inchangé, rendu identique (flux, agrégats).
- [x] Store pur, conforme à `stores-standard.md`.
- [x] Aucun changement d'API (même endpoint BFF, même OpenAPI).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Migration simple terminée** : dashboard, candidats, users, jarvi passent tous par
  l'orchestrateur. Restent les domaines à **actions/pagination** (`entreprises`, `referentiels`) et
  la **fiche candidat** paramétrée (`/candidats/:id`), ainsi que la **fraîcheur** (refetch sur action).
- **Relance on-demand Jarvi** (POST, tâche `080`) toujours non implémentée — introduira une action.
