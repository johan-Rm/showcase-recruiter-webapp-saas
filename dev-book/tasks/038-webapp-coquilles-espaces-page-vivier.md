# TASK 038 — Webapp : coquilles espaces + page « Le Vivier » (mockup Nuxt statique)

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à faire.
- **Type** : Phase 2 — Espace entreprise / première surface produit.
- **Prolonge** : tâche `022` (socle webapp) ; s'appuiera plus tard sur `035` (recherche).
- **Dépend de** : tâches `036` (déploiement) et `037` (auth).
- **Réf ClickUp** : https://app.clickup.com/t/869e0nt08
- **Réf cadrage** :
  - Maquettes vivier (`dev-book` : cockpit v9 + « La table »)
  - `dev-book/cadrage/requirements/brand_guidelines_simplifie.md`

## Contexte

Au sortir du socle, la webapp est volontairement **quasi vide**, sauf l'espace entreprise
qui reçoit une première page produit : « Le Vivier », en mockup, pour valider le rendu.

## Objectif

Livrer des coquilles authentifiées minimales et une page « Le Vivier » en mockup Nuxt
statique conforme aux maquettes, sans branchement API.

## Périmètre

- **Coquilles quasi vides** apex + candidat + admin (écran authentifié placeholder), pour
  valider routing/auth.
- Espace entreprise → page **« Le Vivier »** en **mockup Nuxt statique** : reprise fidèle
  des maquettes (cockpit v9 + « La table »), **données factices**, **aucun branchement API**.
- Ligne design respectée : aération, divulgation progressive, « moins mais mieux », accent
  argile, palettes claires/sombres, fonts Exo 2 / Manrope.

## Invariants

- Aucune donnée candidat réelle en mockup (données factices uniquement).
- Accessibilité clavier, labels, focus.

## Critères d'acceptation

- [ ] Page « Le Vivier » rendue et navigable en mockup statique sur `entreprise.preprod`.
- [ ] Rendu conforme aux maquettes de référence.
- [ ] Autres espaces = coquilles vides authentifiées.

## Hors périmètre

- Branchement API réel (`/search/candidates`) + recherche/filtres/pagination → tâche `043`
  (s'appuie sur `035`).
- Recherche assistée IA → V2.
