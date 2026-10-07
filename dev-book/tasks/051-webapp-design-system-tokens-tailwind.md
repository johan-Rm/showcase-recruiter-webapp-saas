# TASK 051 — webapp : design system, tokens brand → thème Tailwind

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-11 — **réalisé en Tailwind, pas Nuxt UI** (voir « Mise à jour »).
- **Type** : Front — Webapp Nuxt (design system).
- **Dépend de** : tâche `044` (nettoyage modules, fonts en `@font-face` manuels) ·
  s'articule avec la tâche `054` (ambiances : `--ui-primary` dérivera d'un `--accent` variable).
- **Débloque** : tâche `038` (page « Le Vivier ») et les maquettes cockpit v15/v16/v17.
- **Réf** : `dev-book/cadrage/requirements/brand_guidelines.md` ·
  `webapp/app/assets/css/themes.css` · `webapp/app/assets/css/transitions.css` ·
  écart documenté en fin de tâche `035`.

## Contexte

La tâche `035` a laissé un écart explicite : les tokens de `brand_guidelines.md` vivent en
variables CSS dans `themes.css` (`:root` clair, `.dark` sombre, extensible `[data-theme]`) et
sont consommés via les utilitaires arbitraires de Tailwind 4 (`bg-(--color-surface)`). Le
**mapping vers les variables de thème Nuxt UI (`--ui-*`) n'existe pas**. Résultat : les
composants Nuxt UI ignorent le brand.

C'est le verrou qui conditionne toute construction d'interface : les maquettes cockpit v15 /
v16 / v17 en cours attendent ce socle.

## Objectif

Faire que les composants Nuxt UI rendent nativement l'identité showcase-recruiter-webapp-saas, en clair comme en
sombre, sans surcharge locale.

## Périmètre

- Mapper les tokens brand vers les variables de thème Nuxt UI (`--ui-primary`, `--ui-bg`,
  `--ui-text`, `--ui-border`, `--ui-radius`…) — accent **argile `#C8A882`** comme couleur
  primaire.
- Conserver `themes.css` comme **source unique** des tokens ; `--ui-*` en dérive, jamais
  l'inverse.
- Les fonts sont hors modules (décision 2026-07-09, `@font-face` manuels — tâches `044`/`054`) :
  vérifier que les composants Nuxt UI héritent bien de `--font-title` / `--font-ui` et que les
  graisses utilisées (400–700) existent en `.woff2` local.
- Toutes les transitions restent centralisées dans `transitions.css`.
- Ligne design à respecter (feedback du 2026-07-05) : **aération, divulgation progressive,
  « moins mais mieux »** — le cockpit v9 avait été jugé trop compact.
- Page de démonstration interne listant les composants Nuxt UI aux couleurs du brand, pour
  arbitrage visuel.

## Invariants

- Contraste conforme **WCAG AA** sur les deux palettes (clair et sombre).
- Focus visible au clavier sur tous les composants interactifs.
- Aucune couleur en dur dans les composants : tout passe par les tokens.
- Extensibilité `[data-theme]` préservée.

## Critères d'acceptation

- [ ] Un `<UButton>` sans classe rend l'accent argile, en clair et en sombre.
- [ ] La page de démonstration couvre boutons, champs, cartes, tables, modales, badges.
- [ ] Contraste AA vérifié sur texte principal, texte secondaire, bordures et anneau de focus.
- [ ] Aucune régression sur `/ar` (RTL).
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Refonte des maquettes elles-mêmes.
- Composants métier (`StarProfileCard`, table du vivier) → tâche `038`.

## Mise à jour 2026-07-11 — réalisé en Tailwind

**Deux prémisses de cette tâche étaient périmées :**

1. **Nuxt UI a été retiré** → il n'y a plus de variables `--ui-*` à mapper. Le design system
   s'appuie sur **Tailwind CSS 4** : les tokens brand sont exposés en utilitaires via
   `@theme inline` (`bg-surface`, `text-muted`, `bg-accent`, `text-on-accent`, `bg-accent-soft`,
   `border-line`…), qui **se remappent automatiquement** en `.dark`.
2. **L'accent n'est plus l'argile `#C8A882`** mais le **cèdre `#054a29`** (clair) / **`#1a8f56`**
   (sombre), avec `--on-accent: #ffffff` — ambiance « Cèdre » (`brand_guidelines.md`).

**Livré** (`base/app/assets/css/`) :

- `themes.css` — tokens brand, **source unique** comme prévu : neutres clair/`.dark`, accent,
  `--accent-soft` et `--line` dérivés par `color-mix`, `--alert` (Carnation).
- `main.css` — `@theme inline` (tokens → utilitaires Tailwind) + `@custom-variant dark`.
- `palette.css` — le **nuancier** (11 couleurs × échelles 50-950).
- `fonts.css` — **Sora / Geist** self-hostées en `@font-face`.
- `transitions.css` — transitions centralisées ; `app.pageTransition` activé.

**Devenu sans objet** : la « page de démonstration des composants Nuxt UI ». Les primitives
`.btn` / `.card` (aujourd'hui en `@apply`) seront extraites en composants `ui/` (`<UiButton>`,
`<UiCard>`) — convention dans `webapp/base/app/components/README.md`.

Commit : `d513414`.
