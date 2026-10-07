# TASK 132 — webapp : convention VueUse > API DOM brutes + gate CI

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25 — convention + verrou CI, aucun code applicatif touché.
- **Type** : Outillage / convention front (webapp `base/app` + `scripts/ci`).
- **Placement** : formalise une préférence de stack révélée en refactorant `055a` — l'écoute
  d'événements passait par `window.addEventListener` brut là où VueUse (`@vueuse/nuxt`, déjà
  installé) offre l'équivalent idiomatique.
- **Réf** :
  - `webapp/base/app/plugins/deferred-runtime.client.ts` (le fichier qui a déclenché la règle, déjà
    passé à `useEventListener` en `055a`)
  - `webapp/scripts/ci/verify-conventions.ts` (le moteur de règles)

## Contexte

Un projet Vue/Nuxt a un outil idiomatique pour les effets de bord DOM : VueUse. `useEventListener`
est SSR-safe par construction et se retire seul (fonction `stop` rendue, ou démontage du scope),
là où `addEventListener`/`removeEventListener` bruts imposent un bookkeeping manuel et sont un
piège SSR. La préférence existait implicitement ; elle devient explicite et vérifiée.

## Objectif

Documenter la règle **et** la faire respecter en CI, sans être trop strict (les API sans équivalent
VueUse restent autorisées).

## Livré

- `base/app/README.md` § « Écoute d'événements et minuteries (VueUse) » : la convention et son
  pourquoi (SSR-safe, auto-nettoyé), étendue aux minuteries (`useTimeoutFn`/`useIntervalFn`) et
  observers (`useIntersectionObserver`/`useResizeObserver`).
- `scripts/ci/rules.yaml` → règle `app-prefer-useeventlistener` : interdit `.addEventListener(` /
  `.removeEventListener(` dans `base/app/**` et `spaces/*/app/**` (`.vue` + `.ts`), message pointant
  vers `useEventListener`.

## Décisions

- **Gate limité aux écouteurs d'événements** (cas net, remplacement direct par `useEventListener`) ;
  `setTimeout`/`setInterval` **non gatés** (usage légitime existant, ex. repli Safari du plugin
  deferred-runtime) — la préférence `useTimeoutFn` reste documentée sans verrou.
- **Exceptions assumées** : `requestAnimationFrame` et `requestIdleCallback` n'ont pas d'équivalent
  VueUse → restent directes (le plugin `deferred-runtime.client.ts` les utilise).
- **README dit le pourquoi, le verifier dit le comment** — même contrat que les autres règles de
  `rules.yaml`.

## Critères d'acceptation

- [x] `bun run scripts/ci/verify-conventions.ts` vert avec la nouvelle règle (8 règles, 0 violation).
- [x] Aucune violation pré-existante dans `app/**` (vérifié avant ajout).
- [x] Convention documentée dans le README de couche, référencée par la règle.
- [x] Aucun code applicatif touché.

## Points de vigilance

- La règle scanne en `region: all` (contenu entier) : un `.addEventListener(` en commentaire ou
  chaîne serait faussement signalé. Aucun cas aujourd'hui ; à garder en tête si un exemple en doc
  inline apparaît (utiliser le champ `except` au besoin).
