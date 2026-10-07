# TASK 070 — webapp : kit `ui/` (primitives du design system)

- **Créée le** : 2026-07-12.
- **Statut** : 🚧 amorcé le 2026-07-12 (Button, Card, Badge livrés ; le reste suit le Vivier).
- **Type** : Front — Webapp Nuxt (design system).
- **Dépend de** : tâche `051` (tokens brand → thème Tailwind, ✅).
- **Débloque** : tâches `038` / `043` (Le Vivier).
- **Réf** : `dev-book/cadrage/requirements/brand_guidelines.md` ·
  `webapp/base/app/components/README.md` (organisation par feature, `ui/` = primitives sans domaine).

## Contexte

Les tokens brand sont posés (`051`), mais **aucune brique visuelle** n'existait : `.btn` et
`.card` étaient des classes `@apply` dans `main.css`. Tailwind recommande lui-même d'extraire les
motifs réutilisables en **composants**, pas en `@apply` — et le Vivier (table, onglets, badges,
cases à cocher) va multiplier les briques. Sans kit, chaque page rebricole les siennes et le
design dérive.

## Objectif

Un kit `ui/` de primitives **génériques et sans domaine**, dérivées des tokens brand — la seule
source des briques visuelles.

## Livré (2026-07-12)

- `ui/Button.vue` — variantes `primary` (accent plein, `--on-accent`) et `secondary` (pas de fond
  plein), état désactivé (opacité 40 %, curseur neutre), `focus-visible`, enfoncement `1px` à
  l'appui. **Racine polymorphe** : `<button>` par défaut, `NuxtLink` si `to`, `<a>` si `href`.
- `ui/Card.vue` — flat (fond `--surface`, `rounded-lg`, ni bordure ni ombre), prop `as` : la carte
  n'impose pas sa sémantique, la page choisit (`<section>`, `<article>`…).
- `ui/Badge.vue` — recette brand : texte dans la couleur d'usage, fond mélangé à ~14 %.

`.btn` et `.card` supprimés de `main.css` ; les 12 usages migrés. `.container` reste (primitive de
mise en page, pas une brique visuelle).

### Piège rencontré

`<component :is="'NuxtLink'">` (une **chaîne**) **ne résout pas** le composant : Vue rend une
balise `<NuxtLink>` littérale — un élément inconnu, donc **un lien qui ne clique pas**. Il faut
passer la **référence** (`import { NuxtLink } from '#components'`). Les balises natives (`'a'`,
`'button'`) n'ont pas ce problème.

## Reste à faire (au fil des besoins, jamais en avance)

Les briques du Vivier, quand la Table les demandera — et **seulement alors** :

- `ui/Tabs` (segments Tous / Vérifiés / Favoris / Non dévoilés / Dévoilés) ;
- `ui/Table` (lignes, sélection en lot) ;
- `ui/Checkbox`, `ui/Dropdown`, `ui/Input` (barre de recherche, filtres) ;
- teintes du **nuancier** dans `ui/Badge` (couleurs de vues) — les échelles 50-950 existent déjà
  dans `palette.css`.

## Invariants

- `ui/` ne contient **aucune notion de domaine** : ni candidat, ni vivier, ni crédit. Une primitive
  qui connaît le métier n'est pas une primitive.
- Les briques dérivent des **tokens** (`bg-accent`, `text-muted`…), jamais de valeurs en dur.
- Un composant `ui/` **n'impose pas son placement** : ni marge extérieure, ni largeur fixe — c'est
  le parent qui le place.
- YAGNI : on n'ajoute une primitive que lorsqu'un écran réel la demande.

## Critères d'acceptation

- [x] `.btn` / `.card` supprimés de `main.css`, tous les usages migrés.
- [x] Les trois racines de `UiButton` rendent la bonne balise (`<button>`, `<a>`).
- [x] `quality-check` vert.
- [ ] Les primitives du Vivier, à mesure que la Table les exige.
