# TASK 056 — webapp : préchargement prédictif de l'action N+1

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (performance perçue).
- **Dépend de** : tâche `055` (bootstrap + jalons `useDeferredRuntime`).
- **Réf** : `dev-book/cadrage/entreprise/les_2_outils.md` (Table = Trouver, Visionneuse =
  Choisir) · tâche `043` (branchement vivier).

## Contexte

Besoin formulé le 2026-07-09 : au premier chargement, à chaque navigation ou à chaque
changement d'affichage, un service doit **déterminer ce qu'il faut précharger pour l'action
N+1** — l'action que l'utilisateur fera probablement ensuite — afin qu'elle paraisse
instantanée.

Le projet de référence n'a pas d'équivalent : conception propre à showcase-recruiter-webapp-saas. Le parcours
entreprise s'y prête particulièrement : Table (trouver) → Visionneuse (choisir) est une
séquence prévisible.

## Objectif

Quand l'utilisateur déclenche l'action N+1, les données sont déjà dans le store — sans jamais
pénaliser l'action N en cours.

## Architecture

**Un registre déclaratif de règles**, pas de logique dispersée dans les pages :

```ts
// shared/prefetch/rules.ts (esquisse)
export const PREFETCH_RULES: PrefetchRule[] = [
  {
    when: { space: 'entreprise', route: 'le-vivier', view: 'table' },
    predict: (ctx) => [
      prefetchNextResultsPage(ctx.currentPage),      // pagination N+1
      prefetchVisibleCandidatesDetails(ctx.visibleIds), // bascule Visionneuse
    ],
  },
  {
    when: { space: 'entreprise', route: 'le-vivier', view: 'visionneuse' },
    predict: (ctx) => [prefetchCandidate(ctx.nextCandidateId)], // candidat suivant
  },
]
```

**Un exécuteur `usePrefetch()`** qui applique les règles :

- déclencheurs : fin de bootstrap (premier chargement), `router.afterEach` (navigation),
  changement d'état d'affichage observé (ex. bascule Table ↔ Visionneuse, changement de page) ;
- ne démarre qu'aux jalons de la tâche `055` (`runtimeReady` au plus tôt ; règles lourdes à
  `passiveReady`) ;
- **déduplication par clé** : une ressource déjà en store ou déjà en vol n'est jamais
  redemandée ;
- **annulation** : une navigation ou un changement de contexte annule les préchargements
  devenus non pertinents (`AbortController`) ;
- priorité minimale : jamais en concurrence avec une requête déclenchée par l'utilisateur ;
- respect de `navigator.connection` (`saveData`, `effectiveType` 2g) : préchargement coupé.

Le résultat atterrit dans les **mêmes stores Pinia** que les chargements normaux — l'action
N+1 fait son accès store habituel et trouve la donnée déjà là ; aucun chemin de lecture
spécifique au prefetch.

## Invariant métier critique

**Un préchargement ne déclenche jamais une action à effet de bord.** En particulier : le
**dévoilement d'un candidat coûte des crédits** — précharger la Visionneuse ne doit JAMAIS
appeler une route de dévoilement, uniquement des lectures de données déjà acquises. Toute
route de prefetch doit être idempotente et gratuite. En cas de doute sur une route → pas de
prefetch.

## Invariants techniques

- Client uniquement (jamais en SSR), après `runtimeReady`.
- Passe par le BFF comme tout le reste ; les 401/403 du prefetch sont silencieux (pas de
  redirection login déclenchée par un préchargement).
- Un échec de prefetch est invisible : l'action N+1 retombe sur le chargement normal.
- Les règles vivent dans le registre, testables à plat (fonctions pures `when`/`predict`).
- Mesurable : compteur hit/miss (la donnée était-elle préchargée quand l'action N+1 est
  survenue ?) exposé en dev pour valider les règles.

## Règles initiales (à affiner pendant la tâche)

| Contexte N | Prédiction N+1 |
| --- | --- |
| Login réussi (entreprise) | première page du vivier |
| Table, page P affichée | page P+1 des résultats |
| Table, lignes visibles | détails des candidats visibles (bascule Visionneuse) |
| Visionneuse, candidat C | candidat suivant dans la shortlist/liste courante |

## Critères d'acceptation

- [ ] Bascule Table → Visionneuse : aucun appel réseau au moment de la bascule pour les
      candidats déjà visibles (donnée servie du store), vérifié onglet réseau.
- [ ] Pagination : la page suivante s'affiche sans requête au clic.
- [ ] Aucun appel de dévoilement (ni aucune route à effet de bord) émis par le prefetch —
      vérifié par test.
- [ ] Navigation pendant un prefetch en vol → requêtes annulées.
- [ ] `saveData` actif → aucun prefetch.
- [ ] Tests Vitest du registre de règles et de la déduplication.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Prédiction statistique/apprise (on reste sur des règles déclaratives explicites).
- Prefetch d'assets (images, routes JS) — `NuxtLink` prefetch et le bundler s'en chargent.
- Préchargement côté serveur (edge/SSR warmup).
