# TASK ADM-007 — webapp admin : tables revue entreprises & utilisateurs → `UTable`

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, `spaces/admin`).
- **Réf** : chantier Nuxt UI admin (suite de `ADM-006`, même patron `UTable`).

## Contexte

Deuxième vague `UTable`, au même patron que `ADM-006` (validé visuellement). Deux tables encore en
`<table>` fait main (`overflow-x-auto` + `min-w`) → `UTable` (scroll horizontal responsive + a11y).

## Livré

- **`review/OrganizationTable` → `UTable`** (`TableColumn<OrganizationReviewCard>`, 6 colonnes) :
  nom + raison sociale + site, taxId, localisation, canal d'acquisition, statut (badge), **actions**
  (valider / rejeter avec confirmation par ligne — l'état `rejecting` et le flux sont conservés).
- **`utilisateurs` (table inline) → `UTable`** (`TableColumn<AdminUser>`, 4 colonnes) : utilisateur
  (nom + email), rôles (badges), statut (badge), dernière connexion.
- Wrappers `<table>` faits main + `overflow`/`min-w` supprimés dans les deux.

## Décisions

- **Ids de colonne en minuscules** (`taxid`, `lastlogin`) : les slots `#<id>-cell` matchent l'id à
  l'identique — on évite tout écart de casse.
- **`referentiels` volontairement NON migré** : sa table porte une **sélection par cases + la
  dépréciation de libellés mot à mot** (édition inline) ; `UTable` n'y apporte pas plus qu'il n'y
  coûterait en réécriture. Elle reste un `<table>` custom.

## Critères d'acceptation

- [x] Les deux tables s'affichent ; actions de revue (valider/rejeter + confirmation) fonctionnelles ;
      scroll horizontal géré par `UTable`.
- [x] Conventions, lint, format, type-check des 4 espaces verts.
- [ ] Parité visuelle à confirmer sur `:8480` (`/entreprises`, `/utilisateurs`).
- [ ] `webapp-quality` complet — au passage PR.

## Suite (chantier Nuxt UI admin)

- Primitives restantes (décision) : `UiCard` (trivial), `UiAvatar`/`UiInfoHint` (wrappers métier/a11y
  gardés, ré-implémentables sur Nuxt UI en interne).
- `referentiels` : laissé custom (cf. décision ci-dessus).
