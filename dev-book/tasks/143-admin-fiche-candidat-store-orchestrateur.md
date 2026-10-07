# TASK 143 — admin : store candidate (fiche) + migration de /candidats/:id (route paramétrée)

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : **dernier domaine** de la migration admin vers l'orchestrateur `055b`. Introduit le
  cas **route paramétrée** (loader dépendant de l'`id`). Après lui, toutes les routes admin passent
  par l'orchestrateur.
- **Dépend de** : `055b` (orchestrateur + registre + middleware `140`), `099` (endpoint fiche).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts`.

## Livré

- `spaces/admin/app/stores/candidate.ts` — `useCandidateStore` (Options API). State
  `{ candidate, loadedId }` (le store ne tient qu'une fiche à la fois). Getters `getCandidate`
  (passe-plat) / `getLoadedId`, `setCandidate`/`reset`.
- `spaces/admin/app/bootstrap/loaders.ts` — `loadCandidate(id)` (clé par id, `isLoaded` = l'id en
  store correspond).
- `spaces/admin/app/bootstrap/registry.ts` — entrée **paramétrée** `candidats-id` :
  `ctx.params.id ? { critical: [loadCandidate(ctx.params.id)] } : { critical: [] }`. Chargée à la nav
  (via le middleware), **jamais par le preload** (`preloadAll` passe `params: {}` → pas d'id → ignorée).
- `spaces/admin/app/composables/useAdminCandidate.ts` — lecteur pur : ne rend la fiche que si le
  store porte bien l'`id` demandé (évite d'afficher un autre candidat au moment de changer de fiche).

## Décisions

- **Loader paramétré** : le resolver du registre reçoit `ctx.params` — pour une route paramétrée, il
  ne construit le loader que si l'`id` est présent, ce qui exclut naturellement le preload.
- **Store à une fiche** + `loadedId` : naviguer entre deux fiches recharge (id différent) ; le
  lecteur garde la sûreté d'affichage (bon id).
- **Getter passe-plat** : la fiche est déjà prête à l'affichage (pas de mapper).

## Critères d'acceptation

- [x] `/candidats/:id` : la fiche se charge à la nav (middleware, rendu d'un bloc), affichage identique.
- [x] Naviguer entre deux fiches recharge (id différent) sans afficher l'ancienne.
- [x] `preloadAll` n'appelle jamais la fiche (pas d'id au repos).
- [x] Store pur, conforme à `stores-standard`. Aucun changement d'API.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Migration admin COMPLÈTE** : toutes les routes admin passent par l'orchestrateur.
- **Anticipation de la fiche** (précharger les fiches des candidats visibles dans la liste) → carte
  `056` (prédictif N+1), plus tard.
