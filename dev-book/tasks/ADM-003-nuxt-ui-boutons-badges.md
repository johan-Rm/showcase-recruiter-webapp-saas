# TASK ADM-003 — webapp admin : migrer boutons & badges custom vers Nuxt UI

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — type-check des 4 espaces vert, conventions, format,
  lint verts. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, `spaces/admin`).
- **Réf** : Nuxt UI v4 (déjà installé, module `base/nuxt.config.ts`, thémé via
  `base/app/assets/css/nuxt-ui.css` → échelle `sherwood-green`). Espace **entreprise** = référence
  d'adoption. Audit du 2026-07-28.

## Contexte

Nuxt UI est installé, thémé aux couleurs de marque et déjà adopté dans l'entreprise, mais l'espace
**admin est resté 100% sur les composants custom `Ui*`** (`base/app/components/ui/`). Deux systèmes
coexistaient pour les mêmes primitives → incohérence visuelle et double maintenance. Première tranche
du chantier : les deux primitives à fort volume et **swap propre**.

## Livré

- **`UiButton` → `UButton`** (14 usages) : `variant="primary"`/défaut → `solid` (défaut) ;
  `variant="secondary"` → `variant="soft"` ; toggle `? 'primary' : 'secondary'` → `? 'solid' : 'soft'`.
- **`UiBadge` → `UBadge color="primary" variant="soft"`** (9 usages) — fidèle à l'ancien
  `bg-accent-soft`/`text-accent`.
- 9 fichiers touchés (pages `referentiels`, `utilisateurs`, `jarvi`, `candidats/[id]` ;
  composants `AdminCandidateTable`, `review/OrganizationCard`, `review/OrganizationTable`,
  `review/StatusFilter`).

## Décisions

- **Handlers `@click` d'assignation → `void (…)`** : `UButton` type son `onClick` en `() => void`
  (l'ancien `UiButton`, natif, l'ignorait). `@click="page -= 1"` renvoyait un `number` → refusé.
  `void (…)` renvoie `undefined`, en une ligne, sans méthode ni arrow-block éclaté par prettier.
- **Non migrés (bespoke, décision séparée)** : `UiCard` (simple surface, `UCard` change la
  structure), `UiAvatar` (vignette Jarvi + initiales), `UiInfoHint` (bulle a11y soignée tap/focus/
  escape). Ils restent en place.

## Critères d'acceptation

- [x] Plus aucun `UiButton`/`UiBadge` dans `spaces/admin` (`UiCard`/`UiAvatar`/`UiInfoHint` gardés).
- [x] Type-check des 4 espaces vert ; lint (0 erreur), format, conventions verts.
- [ ] Parité visuelle à confirmer sur le serveur (`admin.wt-admin.localhost:8480`).
- [ ] `webapp-quality` complet — au passage PR.

## Suite (chantier Nuxt UI admin)

- `UiCard`/`UiAvatar`/`UiInfoHint` : évaluer `UCard`/`UAvatar`/`UTooltip` au cas par cas.
- Structurels : `UTable` (tables candidats/orga/référentiels), `USlideover` (drawer mobile —
  règle l'a11y ADM-002 nativement), `UNavigationMenu` (nav).
- Une fois admin stabilisé : migrer base/public/candidat, puis **supprimer** les `Ui*` redondants.
