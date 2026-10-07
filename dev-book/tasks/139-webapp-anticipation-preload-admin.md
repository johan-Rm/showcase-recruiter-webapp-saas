# TASK 139 — webapp : anticipation — précharger tous les domaines admin dans les stores

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/` orchestration + `spaces/admin`).
- **Placement** : volet **anticipation** du système de chargement (carte `056`, version « tout
  précharger » pour l'admin léger), au-dessus de l'orchestrateur `055b`.
- **Dépend de** : `055b` (orchestrateur + registre), migrations `133`/`134`/`136`/`137` (chaque
  domaine a son loader). `138` (fix cookie SSR).
- **Réf** : carte `056` · `webapp/spaces/admin/app/bootstrap/registry.ts`.

## Contexte

L'orchestrateur `055b` ne chargeait que le critique de la **route courante** ; les autres routes
chargeaient **à la demande** au clic (2 appels, UX saccadée). Le besoin, reformulé par le PO :
**anticiper** le chargement, ne pas charger bêtement au clic. Pour l'admin (peu d'endpoints
légers), la stratégie retenue est « tout précharger au repos » — pas de prédictif N+1.

## Livré

- `shared/types/bootstrap.ts` — `key` (l'endpoint) sur `BootstrapLoader` : dédupe un loader partagé
  par plusieurs routes lors d'un preload global.
- `spaces/admin/app/bootstrap/loaders.ts` — chaque loader porte sa `key`.
- `base/app/composables/useAppBootstrap.ts` — `preloadAll()` : parcourt le registre, dédupe les
  loaders critiques par clé (le vivier, présent sous `/vivier` ET `/candidats`, n'est chargé
  qu'une fois), charge en parallèle ceux qui ne sont pas déjà en store, **échecs silencieux**.
- `base/app/app.vue` — à `runtimeReady` (jalon `055a`), déclenche `preloadAll()` au lieu du seul
  arrière-plan de la route courante.

## Décisions

- **« Tout précharger » pour l'admin** (décision PO) : ~6 endpoints légers → nav toujours
  instantanée ensuite. Le prédictif N+1 (`056`) reste pertinent pour les espaces à grosses listes
  (entreprise) — même moteur, réglage par espace, à voir plus tard.
- **Dédup par clé d'endpoint** plutôt que par store : simple, stable, et prêt pour un preload
  inter-domaines.
- **Client uniquement, à `runtimeReady`** : jamais avant le rendu utile, jamais bloquant.

## Critères d'acceptation

- [x] Après le 1er rendu, les 6 endpoints admin partent en preload (onglet Réseau).
- [x] Les navigations suivantes ne déclenchent **aucun** appel (donnée déjà en store).
- [x] Le vivier n'est préchargé qu'une fois malgré sa présence sur deux routes.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Saccadé résiduel** dans la fenêtre de preload (~1 s) : si on navigue avant que le store cible
  soit rempli, la page se rend encore partiellement → traité par `140` (nav rendue d'un bloc).
- **Routes paramétrées** (`/candidats/:id`) non préchargeables sans l'`id` : à charger à la nav, pas
  dans `preloadAll` (aucune route paramétrée dans le registre à ce jour).
- **Temps réel (`059`, SSE)** tiendra ensuite ces stores préchargés à jour — sinon la donnée
  anticipée vieillit jusqu'au prochain rechargement complet.
