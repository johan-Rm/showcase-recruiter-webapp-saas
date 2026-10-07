# TASK 146 — webapp : préchargement prédictif N+1 (usePrefetch) + règle admin fiches candidats

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/` mécanisme + `spaces/admin` règle).
- **Placement** : carte `056`. Dernière pièce d'anticipation au-dessus de `055b`/`139`/`140`.
  Développée dans un **worktree admin isolé** (WIP entreprise en cours dans le dépôt principal).
- **Dépend de** : `055a` (jalon `passiveReady`), `055b` (loaders/registre), `143` (loadCandidate),
  `139` (preload de base).
- **Réf** : carte `056` · `webapp/base/app/composables/usePrefetch.ts`.

## Contexte

L'admin précharge déjà tous ses domaines au repos (`139`) — **sauf** les routes paramétrées
(`/candidats/:id`, id inconnu d'avance). Le préchargement **prédictif** comble ce trou : sur la
liste des candidats, anticiper la fiche de chaque candidat visible → clic instantané.

## Livré

**Mécanisme générique (`base/`, réutilisable par tous les espaces)**
- `shared/types/bootstrap.ts` — `PrefetchRule` (`(ctx) => BootstrapLoader[]`), `PrefetchRegistry`.
- `base/app/utils/prefetchRegistry.ts` — holder des règles par espace (patron `bootstrapRegistry`).
- `base/app/composables/usePrefetch.ts` — `prefetchRoute()` : résout la règle de la route, charge
  les loaders manquants (dédup `isLoaded`, échecs silencieux, non bloquant), gardé aux espaces
  authentifiés.
- `base/app/app.vue` — déclenché à **`passiveReady`** puis à chaque navigation.

**Règle admin (démonstrateur)**
- `spaces/admin/app/bootstrap/prefetch.ts` — `candidats` → anticipe `loadCandidate(id)` pour chaque
  candidat de la liste en store.
- `spaces/admin/app/plugins/bootstrap-registry.ts` — pose aussi le registre de prefetch.

## Décisions

- **Cadence `passiveReady`** (et non `runtimeReady`) : le prédictif est du « lourd » qui attend
  l'engagement utilisateur, jamais en concurrence avec le rendu ni le preload de base.
- **Mécanisme dans `base/`, règles par espace** : l'admin n'a qu'un cas réel (fiches candidats),
  mais le moteur est prêt pour l'entreprise (grosses listes paginées), vrai bénéficiaire de `056`.
- **Réutilise `BootstrapLoader`** (clé/isLoaded/load) : un prefetch = les mêmes loaders que le
  bootstrap, exécutés en anticipation.

## Critères d'acceptation

- [x] Sur `/candidats`, après engagement (`passiveReady`), les fiches des candidats visibles sont
      préchargées ; cliquer une fiche est instantané (donnée déjà en store).
- [x] Aucun prefetch avant `passiveReady`, jamais bloquant, échecs silencieux.
- [x] Gardé aux espaces authentifiés ; dédup par id (loader déjà chargé ignoré).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Volume** : ~25 fiches anticipées par page de candidats. Acceptable (petites requêtes, à
  `passiveReady`, dédupliquées) ; affinable en « prefetch au survol » si trop agressif.
- **Entreprise** : le mécanisme attend ses règles (Table/Visionneuse) — à faire sur sa lane quand
  la refonte de stores en cours sera stabilisée.
