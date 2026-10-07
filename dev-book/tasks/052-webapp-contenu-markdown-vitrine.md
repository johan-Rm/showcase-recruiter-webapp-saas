# TASK 052 — webapp : contenu Markdown de la vitrine publique

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (Public Website, contenu).
- **Dépend de** : tâche `051` (design system) pour le rendu.
- **Réf** : écart « site public Markdown » documenté en fin de tâche `035` ·
  `webapp/README.md` · `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` §5.

## Contexte

La tâche `035` demandait de « consommer le contenu depuis des fichiers Markdown lorsque cela
est pertinent ». L'écart est resté ouvert : `@nuxt/content` était *pressenti*, la décision
renvoyée au développement de la vitrine. Rien n'est branché.

Deux options, à trancher **dans cette tâche** :

- **`@nuxt/content`** — collections typées, requêtes, navigation générée. Plus lourd, module à
  installer.
- **`@nuxtjs/mdc`** — rendu Markdown seul. Déjà porté par Nuxt UI en optionnel : il suffit de
  poser `ui: { mdc: true }`, aucune dépendance nouvelle. C'est le choix qu'a fait le projet de
  référence `blue-bay-mogador`.

Note : dans ce projet de référence, un `content.config.ts` importe `@nuxt/content` sans que le
paquet soit dans `package.json` — fichier mort, à ne pas prendre pour un précédent.

## Objectif

Servir le contenu éditorial de l'apex depuis des fichiers Markdown versionnés, indexables et
traduisibles.

## Périmètre

- **Trancher `@nuxt/content` vs `@nuxtjs/mdc`** et consigner la décision (avec sa raison) dans
  cette carte et dans `webapp/README.md`. Critère : si la vitrine a besoin de listes, de
  filtres ou d'une navigation générée → `@nuxt/content` ; si ce n'est que du rendu de pages →
  `mdc`.
- Brancher les pages publiques existantes (`index`, `projet`, `entreprises`, `le-vivier` côté
  public) sur des sources Markdown.
- Contenu **multilingue** cohérent avec i18n (FR/EN/AR, `prefix_except_default`, AR en RTL).
- Métadonnées SEO par page (title, description) portées par le front-matter, alimentant le
  sitemap de la tâche `045`.

## Invariants

- SEO by design : rendu **SSR**, pas de contenu injecté côté client uniquement.
- Structure sémantique correcte (un seul `h1`, hiérarchie des titres).
- RTL préservé sur `/ar`.
- Aucune donnée candidat réelle dans le contenu public.

## Critères d'acceptation

- [ ] Décision `@nuxt/content` vs `@nuxtjs/mdc` écrite et justifiée.
- [ ] Les pages publiques rendent leur contenu depuis Markdown, en SSR (vérifié dans le HTML
      source, réseau désactivé).
- [ ] Les trois locales rendent leur contenu ; `/ar` en `dir="rtl"`.
- [ ] Les métadonnées de front-matter apparaissent dans les balises `<head>` et dans le sitemap.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Éditeur WYSIWYG / « Rédacteur » (`nuxt-tiptap-editor`) — hors périmètre, V2
  (décision du 2026-07-08, « les 2 outils »).
- CMS externe.
