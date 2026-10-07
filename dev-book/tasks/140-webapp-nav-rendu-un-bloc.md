# TASK 140 — webapp : navigation rendue d'un bloc (attendre le critique avant d'afficher)

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/`).
- **Placement** : volet **anti-saccade** de l'UX de chargement, complète l'anticipation `139` sur
  l'orchestrateur `055b`.
- **Dépend de** : `055b` (`loadRouteCritical`), `139` (preload — rend la nav instantanée le plus
  souvent), `055a` (jalons).
- **Réf** : carte `055` · `webapp/base/app/composables/useAppBootstrap.ts`.

## Contexte

Même avec l'anticipation `139`, une navigation lancée **pendant** la fenêtre de preload (ou vers une
route non préchargée) affichait la page **tout de suite** avec des stores vides, puis se remplissait
au fur et à mesure (table, puis taux de remplissage) → **saccade**. Le PO veut une UX maîtrisée :
attendre que tout le critique soit prêt, puis **rendre d'un bloc**.

## Livré

- `base/app/middleware/bootstrap.global.ts` — middleware global : en navigation **client**,
  `await loadRouteCritical(to)` **avant** l'affichage de la page. No-op instantané si la donnée est
  déjà en store (preload `139` ou visite précédente). Le SSR initial reste résolu par `initCoreData`
  (garde `import.meta.server`).
- `base/app/app.vue` — le `router.afterEach(loadRouteCritical)` est retiré (le critique de nav vit
  désormais dans le middleware, avant le rendu). Ajout de `<NuxtLoadingIndicator>` (barre accent)
  pour le retour visuel pendant l'attente.

## Décisions

- **Middleware plutôt que `afterEach`** : `afterEach` s'exécute *après* le montage de la page → la
  page se rendait partiellement avant le chargement. Le middleware s'exécute *avant* → la page ne
  se monte qu'une fois le critique prêt, d'où le rendu d'un bloc.
- **`NuxtLoadingIndicator`** pour le feedback : l'attente bloque la nav ; une barre fine évite
  l'impression d'app figée (surtout dans la fenêtre avant preload).
- **`import.meta.server` gardé** : au SSR, `initCoreData` (callOnce) charge déjà le critique de la
  route d'entrée — le middleware ne doublonne pas.

## Critères d'acceptation

- [x] Nav vers une route préchargée : instantanée, aucun rendu partiel.
- [x] Nav vers une route non préchargée : barre de chargement, puis rendu d'un bloc (plus de
  table-puis-agrégats).
- [x] Refresh : rideau logo inchangé (`AppBootShell`).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Ordre des middlewares globaux** : `auth.global` < `bootstrap.global` (alphabétique) → l'auth
  redirige l'utilisateur non connecté **avant** que le bootstrap tente de charger (et
  `shouldBootstrap` garde de toute façon).
- **`loadBackgroundData`** (palier 3 par route) n'est plus appelé — superséd é par `preloadAll`
  (`139`) ; conservé dans l'orchestrateur pour un usage par-route futur.
