# TASK ADM-008 — webapp admin : carte de revue entreprise → `UCard`

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, `spaces/admin`).
- **Réf** : chantier Nuxt UI admin (dernière primitive, suite de `ADM-007`) · `UCard` (Nuxt UI v4,
  utilisé dans `spaces/entreprise`).

## Contexte

Dernière primitive custom de l'admin à un swap propre : `review/OrganizationCard` (la carte mobile
de la file de revue) utilisait `UiCard`, un simple `rounded-lg bg-surface p-6`. `UCard` couvre le
cas et apporte le look Nuxt UI (bord + ombre légère), cohérent avec les tables déjà migrées.

## Livré

- **`UiCard` → `UCard`** dans `OrganizationCard` : `as="article"` conservé (sémantique), contenu
  dans le slot par défaut (body), disposition `flex flex-col gap-4` portée sur le body via `:ui`.
- L'admin n'a plus **aucun** `UiCard` (le composant `base/` reste, d'autres espaces l'utilisent
  encore — leur migration est un autre chantier).

## Décisions

- **Look Nuxt UI accepté** : `UCard` ajoute un bord/ombre là où `UiCard` était plat — cohérent avec
  le reste de la migration (tables, boutons), validé visuellement.
- **`as="article"` gardé** : `UCard` accepte `as`, la sémantique du bloc de revue est préservée.

## Critères d'acceptation

- [x] La carte de revue s'affiche (mobile) ; contenu et actions inchangés.
- [x] Plus aucun `UiCard` dans `spaces/admin`.
- [x] Conventions, lint, format, type-check des 4 espaces verts.
- [ ] Parité visuelle à confirmer sur `:8480` (`/entreprises`, vue mobile).
- [ ] `webapp-quality` complet — au passage PR.

## Bilan du chantier Nuxt UI admin

Après cette tranche, l'admin ne garde en `Ui*` que **deux wrappers volontaires** : `UiAvatar`
(thumbnail Jarvi + initiales) et `UiInfoHint` (bulle d'aide accessible) — composants **métier/a11y**,
pas des primitives. Tout le reste est passé sur Nuxt UI (`UButton`, `UBadge`, `UCard`, `UTable`,
`USlideover`, `UNavigationMenu`). `referentiels` garde sa table custom (sélection + édition inline).
