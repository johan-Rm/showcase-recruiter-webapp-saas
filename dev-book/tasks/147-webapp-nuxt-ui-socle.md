# TASK 147 — webapp : Nuxt UI branché sur les tokens du kit

- **Créée le** : 2026-07-26.
- **Statut** : ✅ fait le 2026-07-26 — module installé, thème remappé, build vérifié.
- **Type** : Socle frontend transverse — aucun écran modifié.
- **Réf** :
  - `webapp/base/app/assets/css/nuxt-ui.css` (le remappage)
  - `webapp/base/app/assets/css/themes.css` · `palette.css` (les tokens du kit)
  - `dev-book/cadrage/requirements/brand_guidelines.md` (la charte)

## Contexte

La webapp portait un design system entièrement maison : cinq échelles de couleurs, des tokens
sémantiques par thème, une échelle typographique et d'espacement fluide, et onze primitives dans
`base/app/components/ui/`. Chaque composant supplémentaire (table, tabs, modale, combobox) était à
écrire, à rendre accessible et à maintenir — pour un cockpit qui en demande beaucoup.

## Objectif

Disposer d'une bibliothèque de composants complète **sans quitter la charte** : les composants Nuxt
UI doivent hériter du nuancier, des deux thèmes et des rayons du kit, sinon deux langages visuels
cohabiteraient dans le même écran.

## Décisions

### Cohabitation, pas migration

`@nuxt/ui` est installé et les `Ui*` maison restent en place. Un écran neuf part de Nuxt UI ; un
`Ui*` n'est remplacé que lorsqu'on touche l'écran qui le porte. Une migration en masse toucherait les
quatre espaces d'un coup sans bénéfice utilisateur.

### Les deux étages du thème sont remappés

Nuxt UI compose ses variantes depuis des **échelles** (`--ui-color-primary-50…950`,
`--ui-color-neutral-*`) et des tokens **sémantiques** (`--ui-bg`, `--ui-text`, `--ui-border`,
`--ui-primary`). Les deux sont branchés : sans les échelles, un `variant="soft"` retomberait sur le
vert par défaut de Tailwind, hors nuancier.

Les échelles puisent dans `palette.css` (sherwood-green + porcelain en clair, yellow-green + shark en
sombre, comme les deux thèmes du kit). Les tokens sémantiques puisent dans `themes.css`, seul
porteur des arbitrages de contraste : `--ui-color-primary-500` n'est pas l'accent du thème clair,
c'est le 900 qui l'est.

### `--ui-radius` reste à son défaut

Nuxt UI redéfinit **toute l'échelle de rayons de Tailwind** en fonction de `--ui-radius`
(`--radius-lg: calc(var(--ui-radius) * 2)`). À 0.25rem — sa valeur par défaut — l'échelle retombe
exactement sur celle de Tailwind et aucun `rounded-lg` déjà posé ne bouge. La pousser à 0.5rem
doublait silencieusement tous les rayons des quatre espaces.

### Deux dépendances retirées du périmètre direct

- `@tailwindcss/vite` : Nuxt UI pousse lui-même le plugin Vite de Tailwind (hook `vite:extend`).
  Deux instances traiteraient la même feuille deux fois — le plugin est donc retiré de
  `webapp/nuxt.config.ts`, et la dépendance directe avec lui.
- `@nuxt/fonts` (que Nuxt UI installerait) est désactivé par `ui: { fonts: false }` : Sora et Geist
  sont auto-hébergées, le module les ferait résoudre chez un fournisseur distant.

## Vérification

`bun run build` sur l'espace entreprise, puis lecture du CSS émis : `--ui-primary` → `var(--accent)`,
`--ui-color-primary-500` → sherwood-green en clair et yellow-green en sombre, `--ui-color-neutral-*`
→ porcelain / shark, `--ui-radius` → `.25rem`.

## Livré

- `@nuxt/ui@4.10` dans `webapp/package.json`, module déclaré dans `base/nuxt.config.ts`.
- `base/app/assets/css/nuxt-ui.css` : le remappage des deux étages, importé en dernier par
  `main.css` (il doit passer après `@nuxt/ui` et après `themes.css`).
- `base/app/components/README.md` : la règle de cohabitation.

## Reste à faire (hors périmètre de cette tâche)

- Les couleurs de statut de Nuxt UI (`success`, `info`, `warning`) restent sur les palettes Tailwind
  par défaut : la charte n'en définit pas. À trancher quand un écran en aura besoin.
- Après le merge : `make dev-reinstall` côté conteneurs de dev, qui portent leur propre
  `node_modules`.
