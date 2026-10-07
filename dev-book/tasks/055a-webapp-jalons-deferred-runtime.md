# TASK 055a — webapp : jalons de cadencement du chargement différé (deferred-runtime)

- **Créée le** : 2026-07-25.
- **Statut** : ✅ implémenté le 2026-07-25 — socle `base/`, sans consommateur encore (par conception).
  `webapp-quality` : validé au passage PR de la lane.
- **Type** : Front — Webapp Nuxt (`base/`, infra de cadencement, réutilisable 4 espaces).
- **Volet de** : `055` (bootstrap des données en trois paliers) — extrait pour livrer le socle
  isolément, avant l'orchestration.
- **Débloque** : `055` (palier 3 de `useAppBootstrap`), `056` (préchargement N+1), `059` (flux SSE) —
  tous ne démarrent qu'à `runtimeReady`.
- **Réf** : projet de référence `blue-bay-mogador` (hors repo) : `plugins/deferred-runtime.client.ts`,
  `composables/useDeferredRuntime.ts`.

## Contexte

Le bootstrap en trois paliers (`055`), le préchargement N+1 (`056`) et le flux SSE (`059`) partagent
un même besoin : **ne rien lancer de non critique avant que le rendu utile soit là**. Ce cadencement
est un socle commun, indépendant de toute donnée — d'où son extraction en `055a`, livrable et
vérifiable seul.

## Objectif

Trois jalons franchis dans l'ordre après hydratation, exposés en lecture seule, sur lesquels le
différé (palier 3, prefetch, SSE) se branche sans jamais concurrencer le rendu initial.

## Livré

- `base/app/composables/useDeferredRuntime.ts` — accès **readonly** aux 3 jalons (`pageFinished`,
  `runtimeReady`, `passiveReady`) via `useState`. N'ordonnance rien.
- `base/app/plugins/deferred-runtime.client.ts` — franchit les jalons :
  `pageFinished` (hook `page:finish`) → `runtimeReady` (deux frames puis `requestIdleCallback`,
  timeout ~1,2 s, repli `setTimeout` pour Safari) → `passiveReady` (1re interaction
  pointer/clavier/scroll/touch, ou fallback ~16 s). Ré-armement à chaque navigation (`page:start`),
  filet `onNuxtReady` pour le tout premier affichage.

## Décisions

- **Porté de blue-bay, adapté showcase-recruiter-webapp-saas** : commentaires sobres, clés `useState` identiques à la
  référence pour cohérence inter-projets.
- **Écoute et minuterie via VueUse** (`useEventListener` / `useTimeoutFn`, `@vueuse/nuxt` déjà
  installé) plutôt que `window.addEventListener` brut : aligné sur le stack, SSR-safe par
  construction, retrait porté par la fonction `stop` rendue — pas de bookkeeping manuel.
- **`.client` uniquement** : les jalons n'ont de sens qu'après hydratation ; au rendu serveur ils
  restent à `false`. Le palier **critique** du bootstrap, lui, reste résolu en SSR (`055` paliers 1-2)
  — on ne gate jamais du critique sur ces jalons.
- **Exposition `readonly`** : empêche un consommateur UI de désynchroniser les jalons du plugin.
- **Extrait en `055a`** : socle sans donnée, isolable et livrable avant l'orchestration `055`.

## Critères d'acceptation

- [x] Les 3 jalons sont exposés en `readonly` via `useDeferredRuntime`.
- [x] `runtimeReady` passe à `true` après deux frames + `requestIdleCallback` ; `passiveReady` après
      une interaction ou le fallback temporel.
- [x] Chaque navigation ré-arme le cycle (`page:start` remet `pageFinished`/`runtimeReady` à `false`).
- [ ] `webapp-quality` (lint / format / type-check) — validé au passage PR de la lane.

## Points de vigilance

- **SSR** : côté serveur les jalons restent `false` (plugin `.client`) — ne jamais suspendre du
  contenu critique à `runtimeReady`, sous peine d'écran vide au premier paint.
- **Tests Vitest** du séquencement (mocks `requestAnimationFrame` / `requestIdleCallback` / timers) :
  portés avec l'orchestration `055` qui consomme réellement ces jalons, pas sur ce socle nu.
- **Consommateur réel** : arrive avec `useAppBootstrap` (`055`) ; ce commit est volontairement un
  socle sans usage.
