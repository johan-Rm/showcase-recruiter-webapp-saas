# TASK ADM-006 — webapp admin : table du vivier → `UTable`

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, `spaces/admin`).
- **Réf** : chantier Nuxt UI admin (suite de `ADM-005`) · `UTable` (Nuxt UI v4, déjà utilisé dans
  `spaces/entreprise/.../table.vue` — pattern de référence).

## Contexte

Première table du chantier `UTable` — et **pattern-setter** pour les suivantes (`OrganizationTable`,
tables inline de `referentiels`/`utilisateurs`). `AdminCandidateTable` était un `<table>` fait main
avec `overflow-x-auto` + `min-w-[720px]` : `UTable` gère nativement le **scroll horizontal
responsive** et l'a11y du tableau (raison d'être du chantier).

## Livré

- **`AdminCandidateTable` → `UTable`** (`:data` / `:columns` typés `TableColumn<AdminCandidate>`,
  quatre colonnes en `id` + `header: () => t(...)`, cellules en slots `#<col>-cell="{ row }"`).
- Contenu des cellules préservé : `UiAvatar` (wrapper conservé) + lien fiche + étoile, localisation,
  icônes contact (mail/tél/LinkedIn), badge visibilité + « inactif ».
- Wrapper `<table>` fait main + `overflow-x-auto`/`min-w` supprimés — `UTable` les remplace.

## Décisions

- **Pattern calqué sur l'entreprise** : colonnes `id`/`accessorKey` + `header` fonction, cellules en
  slots `#<col>-cell`, `row.original` = la donnée. Établi ici, réutilisé pour les tables suivantes.
- **`UiAvatar` gardé dans la cellule** : c'est le wrapper métier (thumbnail Jarvi + initiales), pas
  une primitive à réassembler.

## Critères d'acceptation

- [x] La table s'affiche avec les 4 colonnes ; le nom ouvre la fiche ; scroll horizontal sur écran
      étroit géré par `UTable`.
- [x] Conventions (marqueur racine), lint, format, type-check des 4 espaces verts.
- [ ] Parité visuelle à confirmer sur `:8480`.
- [ ] `webapp-quality` complet — au passage PR.

## Suite (tables)

- `review/OrganizationTable` (actions valider/rejeter en cellule).
- `utilisateurs` (table inline, badges rôle/statut).
- `referentiels` : table inline **très custom** (sélection par cases + dépréciation de libellés mot à
  mot) — à évaluer, `UTable` n'y apporte peut-être pas plus qu'il n'y coûte.
