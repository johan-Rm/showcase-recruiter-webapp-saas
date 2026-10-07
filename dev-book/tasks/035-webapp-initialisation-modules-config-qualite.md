# TASK 035 — webapp : initialisation du projet Nuxt (modules, config, qualité)

- **Créée le** : 2026-07-04.
- **Statut** : ✅ terminé le 2026-07-04 (écarts documentés en fin de fichier).
- **Type** : Front — Webapp Nuxt (Public Website + SaaS Platform).
- **Dépend de** : tâche `022` (scaffold webapp, host routing, auth BFF, i18n).
- **Réf** : `webapp/README.md` · `dev-book/cadrage/requirements/brand_guidelines.md` ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md`.

## Contexte

Le scaffold de la tâche `022` a posé le socle (host routing 4 surfaces, auth BFF
`nuxt-auth-utils`, i18n FR/EN/AR, dark mode via `@nuxtjs/color-mode`, fonts auto-hébergées via
`@nuxt/fonts`, ESLint + vue-tsc). Cette tâche complète l'initialisation : modules UI/état/outillage
manquants, configuration cible et commandes qualité harmonisées.

## Modules à installer

- Tailwind CSS
- Nuxt UI
- Nuxt i18n *(déjà en place — `@nuxtjs/i18n` posé en tâche 022 ; vérifier la config)*
- VueUse *(à privilégier systématiquement avant de développer un composable personnalisé)*
- `@pinia/nuxt`
- `@nuxt/image`
- `@nuxtjs/device`
- `@nuxt/hints`

## Configuration

### Nuxt

- Désactiver les polices intégrées de Nuxt UI.
- Activer `typescript.strict = true`.
- Activer le mode de compatibilité Nuxt 5.
- Utiliser **Bun** comme package manager *(déjà le PM officiel du projet)*.

### Tailwind CSS

- Mettre en place une configuration **multi-thèmes**.
- Centraliser l'ensemble des transitions CSS dans un fichier dédié.

### Architecture

- Revoir et harmoniser la configuration des alias.
- Mettre en place les commandes de contrôle qualité (voir ci-dessous).

## Site public

- Consommer le contenu depuis des fichiers **Markdown** lorsque cela est pertinent.

## Qualité de code

```text
═══════════════════════════════════════════════════════════════
 🚀 WEBAPP NUXT — COMMANDES DISPONIBLES
═══════════════════════════════════════════════════════════════

🔧 Commandes principales

quality-check               Lint + Format + Type Check
quality-lint-check          ESLint
quality-lint-fix            ESLint --fix
quality-format-check        Prettier
quality-type-check          Vue TSC

🔜 À mettre en place ultérieurement

quality-conventions-check   Vérification des conventions du projet
quality-generate-schemas    Génération des schémas
```

> État actuel (tâche 022) : `lint:check` / `lint:fix` / `type-check` / `quality` existent déjà dans
> `webapp/package.json`, mais **sans Prettier** et sans le nommage `quality-*` ci-dessus →
> harmoniser les scripts et aligner la CI `webapp-quality`.

## Définition de terminé

- [x] Modules listés installés et configurés (ou écart documenté).
- [x] Configuration Nuxt cible appliquée (fonts Nuxt UI désactivées via `ui.fonts: false`,
      TS strict, `future.compatibilityVersion: 5`).
- [x] Tailwind multi-thèmes en place (`app/assets/css/themes.css` — `:root` clair / `.dark`
      sombre, extensible `[data-theme]`), transitions centralisées
      (`app/assets/css/transitions.css`).
- [x] Alias harmonisés : `~/` → `app/`, `#shared` → `shared/`, variantes `@/` / `~~/` / `@@/`
      interdites par ESLint (`no-restricted-imports`).
- [x] Scripts `quality-*` disponibles (`bun run quality-check` vert : ESLint 0, Prettier OK,
      vue-tsc 0) et CI `webapp-quality` + `Makefile.common` alignés.

## Réalisé le 2026-07-04 — notes & écarts

- Modules ajoutés : `@nuxt/ui` 4 (embarque Tailwind CSS 4, CSS-first), `@vueuse/nuxt`,
  `@pinia/nuxt` + `pinia`, `@nuxt/image`, `@nuxtjs/device`, `@nuxt/hints`, `prettier`.
  `@nuxtjs/i18n` était déjà en place (tâche 022). `<UApp>` ajouté dans `app.vue`, locale
  Nuxt UI synchronisée sur la locale i18n (FR/EN/AR, RTL inclus).
- Ajout non listé dans le spec : `quality-format-fix` (pendant `--write` du check Prettier).
- **Écart — site public Markdown** : non branché ; `@nuxt/content` pressenti, décision au
  développement de la vitrine (noté dans `webapp/README.md`).
- **Écart — mapping brand → thème Nuxt UI** : les tokens `brand_guidelines.md` restent des
  variables CSS (`themes.css`) consommables en utilitaires arbitraires Tailwind 4
  (`bg-(--color-surface)`) ; le mapping complet vers `--ui-*` arrive avec le design system.
- Vérifié : `bun run quality-check` vert, build SSR OK, smoke test du build (apex 200,
  `entreprise.` → 302 `/login`, `/le-vivier` hors espace → 404, `/ar` → `dir="rtl"`,
  tokens thème/transitions présents dans le CSS compilé).
