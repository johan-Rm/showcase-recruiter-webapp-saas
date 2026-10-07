# TASK ADM-005 — webapp admin : navigation → `UNavigationMenu`

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, `spaces/admin`).
- **Réf** : chantier Nuxt UI admin (suite de `ADM-004`) · `UNavigationMenu` (Nuxt UI v4).

## Contexte

`AdminSidebarNav` — la navigation partagée par la **sidebar desktop** et le **tiroir mobile**
(`USlideover`, ADM-004) — était une liste de `NuxtLink` faits main avec états actifs et ~40 lignes
de styles custom (`nav-link`, `nav-group`, `nav-ico`). Nuxt UI couvre ce cas avec `UNavigationMenu`,
qui gère l'état actif, les icônes et le responsive.

## Livré

- **`AdminSidebarNav` → `UNavigationMenu`** (`orientation="vertical"`) piloté par un tableau `items`
  typé `NavigationMenuItem[][]` : deux groupes (Pilotage / Supervision) avec en-têtes
  (`type: 'label'`), icônes `i-lucide-*`, `to` via `localePath`, `exact` sur le dashboard.
- Un second `UNavigationMenu` en pied pour le compte (`mt-auto`).
- `LayoutBrand` conservé en tête ; **bloc `<style>` custom supprimé** (état actif géré par Nuxt UI,
  thémé au vert de marque).

## Décisions

- **Item unique, deux usages** : la nav reste un seul composant, désormais rendu par Nuxt UI dans la
  sidebar ET le drawer — l'état actif et le responsive viennent du composant, pas de CSS maison.
- **Libellés en `script`** (`$i18n.t` dans un `computed`) : `UNavigationMenu` est piloté par données
  (`items`), pas par slots — les libellés migrent du template vers le tableau, réactifs à la locale.

## Critères d'acceptation

- [x] Les deux groupes + le compte s'affichent ; l'item de la route courante est actif (dashboard en
      exact).
- [x] Conventions (marqueur racine), lint, format, type-check des 4 espaces verts.
- [ ] Parité visuelle + états actifs à confirmer sur `:8480` (sidebar desktop ET drawer mobile).
- [ ] `webapp-quality` complet — au passage PR.

## Suite (chantier)

- **Tables → `UTable`** (candidats, entreprises, référentiels, utilisateurs) — grosse tranche,
  `UTable` déjà référencé dans l'entreprise.
- Wrappers `UiAvatar`/`UiInfoHint` : garder (métier/a11y) ou ré-implémenter sur Nuxt UI en interne.
- `UiCard` : trivial, à trancher.
