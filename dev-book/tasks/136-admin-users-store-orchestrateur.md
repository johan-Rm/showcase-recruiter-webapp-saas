# TASK 136 — admin : store users + migration de /utilisateurs vers l'orchestrateur

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : suite de la migration des domaines admin vers l'orchestrateur `055b`, après
  vivier, candidats (`133`) et dashboard (`134`). `/utilisateurs` ne fait plus de `useFetch` de page.
- **Dépend de** : `055b` (orchestrateur + registre), `100` (endpoint `/v1/admin/users`).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts` · `stores-standard.md`.

## Livré

- `spaces/admin/app/stores/users.ts` — `useUsersStore` (Options API). State brut = la file paginée
  (`AdminUserQueue`), getters `getQueue` (passe-plat), `getUsers` (liste dérivée), `getTotal` ;
  setters `setQueue` (défensif) / `reset`. Zéro réseau, zéro UI.
- `spaces/admin/app/bootstrap/loaders.ts` — `+loadUsers` (fetch BFF `/api/admin/users` →
  `setQueue`, no-op si déjà chargée).
- `spaces/admin/app/bootstrap/registry.ts` — `utilisateurs` : `critical: [loadUsers]`.
- `spaces/admin/app/composables/useAdminUsers.ts` — devient **lecteur pur** du store ;
  `pending` / `error` dérivés du statut du bootstrap.

## Décisions

- **Store DTO-brut** (comme candidates), `057` toujours différé — la file admin est déjà prête à
  l'affichage, aucun mapper.
- Migration mécaniquement identique à `133`/`134` (queue paginée → store + loader + registre +
  lecteur).

## Critères d'acceptation

- [x] `/utilisateurs` chargé par l'orchestrateur, aucun `useFetch` de page.
- [x] `utilisateurs.vue` inchangé, rendu identique (compteur, table rôles/statut/dernière connexion).
- [x] Store pur, conforme à `stores-standard.md`.
- [x] Aucun changement d'API (même endpoint BFF, même OpenAPI).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Édition des droits** (ADMIN_OWNER) toujours non implémentée — quand elle arrivera, elle
  introduira une action → refetch du store (fraîcheur), comme entreprises/referentiels.
- Domaines restants : `jarvi`, `entreprises`, `referentiels`, fiche candidat `/candidats/:id`.
