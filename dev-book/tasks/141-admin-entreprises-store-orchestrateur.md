# TASK 141 — admin : store organizations (file de revue) + migration de /entreprises

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — front seul (`spaces/admin`), aucun changement d'API.
  Type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`).
- **Placement** : premier domaine admin **riche** migré vers l'orchestrateur `055b` — il pose trois
  patterns (getter enrichi, fraîcheur sur action, filtre) que `referentiels` réutilisera.
- **Dépend de** : `055b` (orchestrateur), `139` (preload), `140` (nav rendue d'un bloc), `083`
  (endpoints file de revue), `068`/services (`toReviewCard`).
- **Réf** : `webapp/services/organization/review.ts` · `stores-standard.md`.

## Contexte

`/entreprises` était le seul écran admin déjà « vivant » (file de revue + validate/reject). Sa
migration introduit ce que les domaines simples n'avaient pas : une **liste enrichie** (mapper),
une **file filtrée** par statut, et des **actions** qui doivent rafraîchir la file.

## Livré

- `spaces/admin/app/stores/organizations.ts` — `useOrganizationsStore` (Options API). State brut
  `{ queue, loadedStatus }` (le store ne tient qu'un filtre à la fois). **Getter `getOrganizations`
  enrichi** : délègue le mapping DTO → carte à `toReviewCard` (`#services/organization/review`) —
  premier vrai cas « état brut → getter enrichi ». `getTotal`, `getLoadedStatus`, `setQueue`/`reset`.
- `spaces/admin/app/bootstrap/loaders.ts` — `loadOrganizations(status='EN_REVUE')` (préchargé sur
  la file de travail).
- `spaces/admin/app/bootstrap/registry.ts` — `entreprises` : `critical: [loadOrganizations]`.
- `spaces/admin/app/composables/useOrganizationReview.ts` — devient une passerelle : lit le store,
  porte le **filtre** (`status` + `reload` + rattrapage via `watch`), et **`decide` → POST →
  reload** (fraîcheur). Contrat de retour inchangé pour la page.

## Décisions

- **Refetch après action = dans le composable** (`decide → reload`), **le store reste pur** (jamais
  de réseau) — conforme à `stores-standard`.
- **Getter enrichi dans le store** : `toReviewCard` est appelé par le getter, pas dans la page ni le
  composable — l'enrichissement métier vit à la frontière donnée, pas à l'affichage.
- **Le store ne tient qu'un filtre** + `loadedStatus` : au changement de filtre (ou au retour sur
  l'écran après en avoir changé), le composable recharge si le store ne porte pas le bon statut. Le
  preload charge `EN_REVUE`, donc pas de fetch au 1er affichage.

## Critères d'acceptation

- [x] `/entreprises` : file `EN_REVUE` préchargée, nav instantanée, liste enrichie identique.
- [x] Changement de filtre → recharge pour le nouveau statut.
- [x] Valider / refuser → l'entreprise sort de la file (refetch), compteur à jour.
- [x] Store pur (getter enrichi via service), conforme à `stores-standard`.
- [x] Aucun changement d'API. Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **`pending`/`error`** du composable restent l'état de la **décision** (comme avant) ; le
  rechargement de filtre n'a pas d'indicateur dédié (rapide, comportement identique à l'existant).
- **Temps réel (`059`)** : la file de revue est partagée entre admins — une décision d'un autre
  admin ne se reflète qu'au prochain reload tant que le SSE n'est pas branché.
- Domaines restants : `referentiels` (même patron + pagination), fiche candidat `/candidats/:id`.
