# TASK 055 — webapp : bootstrap des données en trois paliers (métadonnées → principales → arrière-plan)

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire — volet jalons `deferred-runtime` extrait et livré en `055a`.
- **Type** : Front — Webapp Nuxt (orchestration des données, stores).
- **Dépend de** : tâche `057` (transformation DTO → modèles — **préalable direct** : les
  stores ne consomment que des modèles mappés) · tâche `049` (refresh BFF — les chargements
  passent par `apiFetchForUser`) · tâche `050` (types générés, entrées des mappers).
- **Débloque** : tâche `056` (préchargement prédictif N+1) · tâche `043` (branchement API du
  vivier).
- **Réf** : projet de référence `blue-bay-mogador` (hors repo) : `useNuxtServerInit.ts`,
  `plugins/deferred-runtime.client.ts` · `webapp/server/utils/showcase-recruiter-webapp-saas-api.ts` ·
  architecture.md §5 (BFF).

## Contexte

Les stores Pinia sont vides et aucune stratégie de chargement n'existe : chaque page fera son
fetch ad hoc si on ne pose pas l'orchestration avant que les surfaces produit arrivent
(tâches `038`/`043`). Le besoin, formulé le 2026-07-09 :

1. charger **au plus tôt** les données critiques — en général des **métadonnées** : les données
   de relations des entités principales, gardées **à plat** dans le store (référentiels,
   constantes) ;
2. puis les **données principales** de la vue courante ;
3. puis les **données d'arrière-plan**, non critiques, après le premier rendu utile.

Le projet de référence implémente exactement ce séquencement (3 paliers, verrou de
déduplication, jalons de rendu différé) — on transpose en l'adaptant aux stores Pinia et au
routage par espace.

## Objectif

Un service unique qui séquence tout chargement de données de l'app : bloquant pour le cœur,
différé pour le reste, sans jamais concurrencer le rendu initial.

## Architecture

**`app/composables/useAppBootstrap()`** (nom à confirmer) :

- `loadMetadata()` — palier 1 : référentiels et relations à plat → stores dédiés
  (`stores/referentials.ts`). Dépend de la **locale** (i18n des libellés) : rechargé au
  changement de langue.
- `loadMainData()` — palier 2 : entités principales de l'espace courant.
- `loadBackgroundData()` — palier 3 : côté client, après hydratation ; chaque requête échoue
  individuellement en silence (pattern `runRequests` du projet de référence), jamais de crash
  global.
- `initCoreData()` — orchestre 1 + 2 en parallèle (bloquant SSR), statut
  `idle | loading | ready | error` exposé en readonly, **verrou de déduplication** contre les
  appels concurrents.
- Amorçage : `await callOnce('app.init-core-data', ...)` dans `app.vue` ; le palier 3 est
  déclenché par les jalons ci-dessous.

**Plugin `deferred-runtime.client.ts`** — jalons de cadencement du non-critique :

1. `pageFinished` — la navigation Nuxt visible est terminée ;
2. `runtimeReady` — post-rendu contrôlé (double `requestAnimationFrame` puis
   `requestIdleCallback` avec timeout ~1,2 s, fallback `setTimeout`) ;
3. `passiveReady` — première interaction utilisateur (pointer/key/scroll/touch) ou fallback
   temporel (~16 s).

Exposés en lecture seule via un composable (`useDeferredRuntime()`), consommés par le palier 3
et par la tâche `056`.

**Sensibilité à l'espace** : le contenu de chaque palier dépend de l'espace courant
(`resolveSpace`) — le Public Website ne charge pas les référentiels du vivier ; l'espace
entreprise ne charge rien tant que la session n'est pas validée par le middleware.

## Inventaire des données (à déterminer — point ouvert principal)

À arbitrer pendant la tâche, par espace. Candidats pressentis pour **entreprise** :

| Palier | Candidats |
| --- | --- |
| 1 — métadonnées | constantes/référentiels servis par l'API (`CategoryCode`…), session `/me`, organisation + crédits |
| 2 — principales | première page de résultats du vivier (la Table) |
| 3 — arrière-plan | favoris, shortlist, compteurs, données de la Visionneuse |

Règle de tri : critique = ce sans quoi la vue courante ne peut pas rendre ; arrière-plan = ce
qui améliore l'action suivante. En cas de doute → arrière-plan.

## Invariants

- **Tout passe par le BFF** (`server/api/*` → `apiFetchForUser`) : le client ne parle jamais à
  l'API Symfony directement, aucun jeton exposé.
- SSR-safe : aucun état module-level partagé entre requêtes ; l'état vit dans Pinia /
  `useState`, sérialisé dans le payload, réhydraté sans double fetch.
- Le palier 3 ne démarre jamais avant `runtimeReady` et ne bloque jamais une interaction.
- Un échec du palier 3 est silencieux ; un échec des paliers 1-2 produit un état `error`
  explicite (pas de vue à moitié rendue).
- Les métadonnées sont **à plat** dans leurs stores : pas de graphe d'objets imbriqués
  (cf. conventions API : réponses plates, relations par uuid).
- Constantes : lues depuis l'API, jamais recopiées en dur (gate contrats, tâche `050`).

## Critères d'acceptation

- [ ] Premier rendu SSR : paliers 1-2 résolus côté serveur, **un seul** appel par ressource
      (vérifier l'absence de re-fetch client à l'hydratation).
- [ ] Palier 3 : les requêtes partent après `runtimeReady`, visibles dans l'onglet réseau
      après le rendu, jamais avant.
- [ ] Navigation interne : `initCoreData` n'est pas rejoué (callOnce + verrou) ; le changement
      de locale recharge les métadonnées localisées.
- [ ] Coupure d'une ressource du palier 3 → l'app rend normalement ; coupure du palier 1 →
      état d'erreur explicite.
- [ ] Tests Vitest : séquencement, verrou de déduplication, tolérance d'échec du palier 3.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Le contenu métier détaillé des stores du vivier → tâche `043`.
- Le préchargement prédictif N+1 → tâche `056`.
- Cache serveur / SWR des réponses BFF (à évaluer plus tard si besoin).
