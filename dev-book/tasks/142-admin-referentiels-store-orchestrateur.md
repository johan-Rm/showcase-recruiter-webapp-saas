# TASK 142 — admin : store referentials + migration de /referentiels vers l'orchestrateur

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : domaine admin le plus riche migré vers l'orchestrateur `055b` — réutilise les
  patterns d'`entreprises` (`141`), avec en plus une **clé composite** (type/page/statut) et **deux
  actions**.
- **Dépend de** : `055b` (orchestrateur), `139`/`140` (preload + rendu d'un bloc), `120`/`124`
  (endpoints référentiels + relecture mot à mot).
- **Réf** : `webapp/spaces/admin/app/bootstrap/registry.ts` · `stores-standard.md`.

## Livré

- `spaces/admin/app/stores/referentials.ts` — `useReferentialsStore` (Options API). State brut
  `{ referential, loadedKey }` — le store ne tient qu'un triplet `type|page|statut` à la fois.
  Getter `getReferential` **passe-plat** (le référentiel est déjà prêt à l'affichage), `getLoadedKey`,
  `setReferential`/`reset`.
- `spaces/admin/app/bootstrap/loaders.ts` — `loadReferentials(type='METIER')` (préchargé METIER
  page 1).
- `spaces/admin/app/bootstrap/registry.ts` — `referentiels` : `critical: [loadReferentials]`.
- `spaces/admin/app/composables/useAdminReferentials.ts` — passerelle : porte `type`/`page`/`status`,
  `pending` (sur le chargement — la page affiche un loader en pagination/filtre), `error`, et les
  deux relectures **`review`** (concepts) / **`reviewLabels`** (libellés mot à mot) → POST + `reload`.
  Rechargement piloté par la clé composite.

## Décisions

- **Clé composite `type|page|statut`** (`loadedKey`) : le store ne tient qu'une page de référentiel ;
  le composable recharge dès que la clé courante n'est pas celle en store (changement de filtre/page,
  retour sur l'écran). Preload = METIER page 1.
- **Getter passe-plat** (pas de mapper) — contrairement à `entreprises`, le référentiel n'a pas
  besoin d'enrichissement.
- **`pending` sur le chargement** (pas seulement les actions) : la page montre un état de chargement
  pendant pagination/filtre/type — comportement préservé de l'existant.
- **Refetch après relecture = dans le composable** (`review`/`reviewLabels` → `reload`), store pur.

## Critères d'acceptation

- [x] `/referentiels` : METIER préchargé, nav instantanée.
- [x] Bascule type, changement de page, filtre statut → recharge (loader affiché).
- [x] Relecture concepts (valider/déprécier) et libellés (déprécier/restaurer) → liste rafraîchie.
- [x] Store pur, conforme à `stores-standard`. Aucun changement d'API.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Dernier domaine restant** : la fiche candidat `/candidats/:id` (route paramétrée → loader
  dépendant de l'`id`, non préchargeable tel quel). Après elle, la migration admin est complète.
- **Bug latent i18n** toujours présent sur cet écran : `$t('error.unexpected')` pointe une branche
  (`{ title, message }`) → clé littérale en cas d'erreur. Pré-existant, à traiter à part.
