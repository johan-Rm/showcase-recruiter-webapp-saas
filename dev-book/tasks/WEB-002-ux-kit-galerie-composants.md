# TASK WEB-002 — socle : galerie des composants UX (UX Kit)

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — type-check des 4 espaces, lint, conventions, format
  verts. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (socle, design system, `base/app/pages`).
- **Réf** : Nuxt UI v4 (thémé au vert de marque via `base/app/assets/css/nuxt-ui.css`).

## Contexte

Besoin d'un écran unique référençant **tous les éléments UX réutilisables** du socle (boutons,
badges, chips, liens, cartes, formulaires, tables, surcouches…), avec leurs variantes — une
**référence vivante du design system** et une surface de QA visuelle.

## Livré

- **Page `base/app/pages/ux-kit.vue`** — grâce aux Nuxt Layers, la route `/ux-kit` est disponible
  dans **tous les espaces** ; **dev-only** (`if (!import.meta.dev) throw createError(404)`),
  `layout: false`, bascule de thème intégrée.
- **Sections par famille** (vocabulaire réel du projet) :
  - Nuxt UI : `UButton` (variantes/couleurs/tailles/icônes/loading/disabled), `UBadge`, `UChip`,
    `ULink`, `UAvatar`, `UCard`, `UInput`/`USelect`/`UTextarea`/`UCheckbox`/`USwitch`/`UFormField`,
    `UAlert`, `UTabs`, `UTable`, `UTooltip`, `USlideover`, `UModal`.
  - Wrappers métier gardés : `UiAvatar`, `UiInfoHint`.
- Structurée pour être **étendue** (ajouter une section = un `<section>`).

## Décisions

- **Dev-only** : la galerie ne doit jamais fuiter en prod → 404 hors dev (la route existe, l'accès
  est refusé).
- **Sur `espace/socle-webapp`** : c'est du socle pour lui-même, aucun espace ne le consomme en
  propre. Visible dans n'importe quel espace au merge.
- **Enveloppée dans `<UApp>`** : `UTooltip` (composant Reka) exige le contexte `TooltipProvider`
  que fournit `<UApp>` — que **le projet n'a nulle part** (personne n'avait utilisé `UTooltip`
  avant). On l'ajoute **localement à la page** (couvre aussi slideover/modal), sans toucher le
  reste de l'app. À terme, un `<UApp>` racine dans `app.vue` débloquerait Tooltip/Toast/overlays
  partout — hors périmètre ici.

## Critères d'acceptation

- [ ] `/ux-kit` rend toutes les sections ; bascule de thème fonctionnelle ; 404 hors dev — à
      confirmer visuellement sur un serveur.
- [x] Conventions, lint, format, type-check des 4 espaces verts.
- [ ] `webapp-quality` complet — au passage PR.

## Suite

- Enrichir au fil de l'adoption Nuxt UI (nouvelles variantes, composants ajoutés).
- Envisager un lien discret vers `/ux-kit` en dev (footer/menu) pour la découvrabilité.
