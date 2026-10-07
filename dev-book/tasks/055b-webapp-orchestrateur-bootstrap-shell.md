# TASK 055b — webapp : orchestrateur de bootstrap + AppBootShell (registre → loaders → stores)

- **Créée le** : 2026-07-25.
- **Statut** : ✅ implémenté le 2026-07-25 — prouvé au run (SSR + nav client, sans bug apparent),
  type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/` orchestration réutilisable + `spaces/admin` premier client).
- **Volet de** : `055` (bootstrap trois paliers) — après le socle de jalons `055a`.
- **Dépend de** : `055a` (jalons `deferred-runtime`), `130` (store vivier).
- **Débloque** : migration des autres domaines admin par l'orchestrateur ; `056` (préchargement
  N+1) et `059` (flux SSE), qui se branchent sur les mêmes stores.
- **Réf** : carte `055` · projet de référence `blue-bay-mogador` (`useNuxtServerInit`).

## Contexte

`055a` a posé les jalons de cadencement ; il manquait l'orchestrateur qui, par route, charge le
critique (bloquant) puis l'arrière-plan, hydrate les stores, et pilote un overlay de chargement.
Décision d'architecture (validée avec le PO) : **modèle central** — l'orchestrateur charge, les
composables de page deviennent de purs lecteurs de store.

## Livré

**Base (réutilisable 4 espaces)**

- `shared/types/bootstrap.ts` — contrats : `BootstrapLoader` (`isLoaded`/`load`), `BootstrapRouteData`
  (`critical`/`background`), `BootstrapRegistry`, `BootstrapStatus`.
- `base/app/utils/bootstrapRegistry.ts` — holder du registre de l'espace courant (`set`/`get`).
- `base/app/composables/useAppBootstrap.ts` — `initCoreData` (paliers 1-2 bloquants SSR),
  `loadRouteCritical` (nav client), `loadBackgroundData` (palier 3), statut `idle/loading/ready/error`.
- `base/app/components/AppBootShell.vue` — overlay `#appbootshell` visible tant que `status='loading'`.
- `base/app/app.vue` — amorce `await callOnce('app.init-core-data', …)` + hooks client
  (`router.afterEach` → critique ; `watch(runtimeReady)` → arrière-plan) + `<AppBootShell/>`.
- i18n `app.loading` (fr / en / ar).

**Admin (premier client)**

- `spaces/admin/app/bootstrap/loaders.ts` — `loadVivier` (fetch BFF → hydrate le store, no-op si chargé).
- `spaces/admin/app/bootstrap/registry.ts` — `ADMIN_BOOTSTRAP_REGISTRY` (`vivier` et `candidats` →
  vivier critique).
- `spaces/admin/app/plugins/bootstrap-registry.ts` — pose le registre (universel, serveur + client).
- `spaces/admin/app/composables/useAdminVivier.ts` — devient **lecteur pur** du store (le `useFetch`
  part dans `loadVivier`) ; `pending`/`error` dérivés du statut du bootstrap.

## Décisions

- **Modèle central (A)** : l'orchestrateur charge le critique par route dans les stores ; les
  composables lisent. UN seul patron, fidèle à `055`.
- **Registre module-level** (holder dans `base/app/utils`) posé par un plugin d'espace, plutôt que
  `provide/inject` : plus simple et typé. **SSR-safe** car config **statique identique à chaque
  requête** (une build = un espace, `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE`) — jamais de l'état de requête.
- **Critique en nav client avec shell** : au 1er paint SSR le critique est déjà résolu (pas de
  shell) ; l'overlay ne se voit qu'en navigation client vers une route non encore en store.
- **Vivier critique aussi sur `/candidats`** : le taux de remplissage est rendu au SSR ; le mettre
  en arrière-plan (palier 3, client-only) l'aurait fait apparaître après coup.
- **057 différé** : stores DTO-bruts (comme `vivier`), pas de couche mapper en amont pour l'admin.
- **Migration incrémentale** : seul le vivier passe par l'orchestrateur ; les autres domaines
  gardent leur composable jusqu'à leur propre store.

## Critères d'acceptation

- [x] `/vivier` : snapshot chargé au SSR via l'orchestrateur, page lit le store, rendu identique.
- [x] `/candidats` : taux de remplissage toujours rendu au SSR (vivier critique).
- [x] Overlay `#appbootshell` piloté par le statut ; invisible au 1er paint SSR.
- [x] Routes admin non migrées : orchestrateur no-op, composables inchangés.
- [x] Public / déconnecté : `shouldBootstrap()` faux → aucun chargement.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet + tests Vitest de l'orchestrateur — au passage PR (avec la migration
  des domaines qui donnera de quoi tester le séquencement de bout en bout).

## Points de vigilance

- **Runtime non gaté par l'agent** : Docker appartient au PO ; le comportement (SSR bloque, shell en
  nav, hydratation sans re-fetch) a été confirmé par son run, pas par une suite automatisée.
- **Palier 3 client-only** : `loadBackgroundData` ne tourne qu'après `runtimeReady` (jalons `.client`)
  — toute donnée qui doit être au SSR va en `critical`, pas en `background`.
- **`app.vue` en setup async** (`await callOnce`) : pattern voulu par la carte ; à surveiller si un
  souci d'hydratation apparaît.
