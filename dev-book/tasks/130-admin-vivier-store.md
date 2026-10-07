# TASK 130 — admin : premier store Pinia (vivier), état partagé entre les écrans

- **Créée le** : 2026-07-25.
- **Statut** : ✅ implémenté le 2026-07-25 — front seul (webapp `spaces/admin`), aucun changement
  d'API. `webapp-quality` : validé au passage PR de la lane (gates non lancées au commit).
- **Type** : Front (webapp `spaces/admin`).
- **Placement** : refonde la couche d'accès aux données de l'écran vivier livré par `119`/`128`.
  Aucun nouvel endpoint, aucun changement de contrat, rendu identique.
- **Lié à** : `119` (l'écran `/vivier`), `128` (registre d'affichage `vivierMetrics`, qui **reste
  hors store** car c'est de la présentation).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/stores-standard.md` (le standard que ce store applique)
  - `webapp/base/server/api/admin/vivier.get.ts` (l'endpoint BFF hydraté)

## Livré

- `spaces/admin/app/stores/vivier.ts` — **premier store Pinia du repo** (`useVivierStore`, Options
  API). État brut `{ vivier: AdminVivier | null }`, getter passe-plat `getVivier`, setters
  `setVivier` (défensif) / `reset`. Zéro réseau, zéro UI.
- `spaces/admin/app/composables/useAdminVivier.ts` — garde le `useFetch` (BFF), hydrate le store
  via `setVivier` (`watch(data, …, { immediate: true })`), expose `vivier` lu du store. Signature
  `{ vivier, pending, error }` **inchangée**.

## Contexte

`useAdminVivier` est consommé par deux écrans (`vivier.vue`, `candidats.vue`) qui portaient chacun
leur propre ref local issu de `useFetch`. Il n'existait aucun **état admin partagé** ni aucun store
dans le repo. C'est le cas d'usage type d'un store : une donnée d'un domaine lue par plusieurs
écrans, exposée une seule fois via un contrat typé.

## Objectif

1. Introduire une **source unique partagée** pour le snapshot vivier (le store), lue par l'UI.
2. **Poser le premier store** conforme au triptyque (`stores-standard.md`) — il fixe le patron pour
   les suivants — sans toucher au rendu ni au contrat d'API.

## Décisions

- **Options API (store de données)**, pas Composition API : le store porte de l'état, pas
  d'orchestration async — celle-ci reste au composable (`useFetch`). Cf. `stores-standard.md`.
- **Réseau au composable, store pur** : le composable fait le `useFetch` BFF et hydrate via
  `setVivier` ; le store ne touche jamais le réseau (frontière documentée).
- **Getter passe-plat `getVivier`** : le snapshot est déjà prêt à l'affichage, aucun enrichissement
  à porter. La métadonnée d'affichage (bulles/ratios de `128`) reste **hors store** — c'est de la
  présentation, pas de l'état.
- **Bénéfice réel = état partagé, pas une réduction de fetch** : `useFetch` dédoublonne déjà par
  clé d'URL, et les deux écrans ne sont jamais montés ensemble. Le store apporte une source unique
  qu'un autre composant/composable peut lire sans re-piloter `useAdminVivier`.
- **Import relatif explicite de `useVivierStore`** : `app/stores/` est un dossier neuf → même
  fragilité auto-import/HMR que le nouveau `app/utils/` de `128`, même mitigation. Les stores futurs
  pourront s'appuyer sur l'auto-import une fois le dossier établi.
- **`vivier` exposé en `computed`** du getter : les deux écrans ne font que **lire** (vérifié) ;
  signature de retour inchangée.

## Critères d'acceptation

- [x] `vivier.vue` et `candidats.vue` lisent le **même** état (store) au lieu d'un ref local chacun.
- [x] Signature `{ vivier, pending, error }` de `useAdminVivier` inchangée ; rendu identique.
- [x] Store pur : aucun import réseau ni UI ; conforme à `stores-standard.md`.
- [x] Aucun changement d'API (même endpoint BFF, même OpenAPI).
- [ ] `webapp-quality` (lint / format / type-check des 4 espaces) — validé au passage PR de la lane.

## Points de vigilance

- **SSR** : l'état du store est sérialisé par `@pinia/nuxt` ; le `watch immediate` réhydrate côté
  client (nav où `data` est déjà résolue). Double source assumée — `data` (useFetch) est le
  transport interne, le store la source lue par l'UI.
- **Auto-import** : `useVivierStore` importé en relatif (dossier neuf) ; basculer sur l'auto-import
  quand `app/stores/` est établi et le watcher redémarré.
