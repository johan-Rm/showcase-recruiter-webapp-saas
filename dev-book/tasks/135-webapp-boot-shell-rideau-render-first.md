# TASK 135 — webapp : rideau de boot render-first (AppBootShell) — logo + badge d'espace

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — prouvé au run (rideau visible au refresh admin),
  type-check des 4 espaces vert, conventions vertes. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/`).
- **Placement** : affine l'`AppBootShell` livré nu par `055b`. Le rideau était piloté par le statut
  (nav) et **jamais présent au SSR** → aucun rideau au refresh. On le rend **render-first**.
- **Dépend de** : `055b` (orchestrateur + statut), `055a` (jalon `pageFinished`).
- **Réf** : projet de référence `blue-bay-mogador` (`AppBootShell.vue`, spec « render-first boot
  shell non-bloquant »).

## Contexte

Le comportement voulu : au **refresh**, un « rideau de cinéma » plein écran (logo + marque) attend
que l'app soit prête. Or l'implémentation `055b` était `v-if="status === 'loading'"` → au SSR le
statut finit à `ready`, le rideau n'était donc **jamais dans le HTML** : aucun rideau au refresh.

## Livré

- `base/app/components/AppBootShell.vue` — **render-first** : monté PAR DÉFAUT (`booted` démarre à
  `false`) → présent dans le HTML SSR → **visible dès le refresh**. Retiré quand l'app est prête
  (`pageFinished` **et** `status !== 'loading'` → `booted = true`). **Boot initial uniquement** (plus
  de rideau plein écran en navigation) et **espaces authentifiés seulement** (garde `space !== 'public'`).
- **Branding** : logo showcase-recruiter-webapp-saas (`LayoutLogo` = picto + mot-symbole) + badge d'espace (`UiBadge` +
  `$t(space.${space})`), sur fond `bg-bg`, pulsation douce.
- i18n `space.admin` harmonisé : « Administration » → **« Espace admin »** / « Admin area » /
  « فضاء الإدارة », pour aligner les trois espaces authentifiés sur le motif « Espace X ».

## Décisions

- **Render-first plutôt que status-driven** : monté par défaut → rideau au SSR → visible au refresh.
  Reprend le principe blue-bay, mais **volontairement simple** (pas de double flag, pas de failsafe,
  pas de logger) : la condition `booted` se résout toujours (succès, erreur, ou espace non amorcé),
  donc aucun blocage possible — le failsafe blue-bay devient inutile.
- **Boot initial uniquement** : le rideau plein logo à chaque navigation était trop lourd ; en nav,
  chaque page porte son propre état de chargement, et le préchargement N+1 (`056`) rendra ces
  navigations instantanées.
- **Pas de rideau sur le site public** : rendu SSR direct, aucune app à amorcer.
- **i18n harmonisée** : `space.admin` aligné sur « Espace X ». Impacte aussi le badge topbar/sidebar
  (`LayoutBrand`) et le titre de page admin — assumé, c'est plus cohérent.

## Critères d'acceptation

- [x] Refresh d'un espace authentifié : rideau logo + badge dès le 1er paint (présent au SSR),
  retiré une fois l'app prête.
- [x] Aucun rideau sur le site public.
- [x] Aucun rideau plein écran en navigation client.
- [x] Badge « Espace admin » (et l'équivalent par espace).
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- `space.admin` change aussi le **badge de la sidebar/topbar** et le **titre de page** admin (via
  `LayoutBrand` / `app.vue`) → « Espace admin ». Cohérent, mais à revenir si « Administration » est
  préféré dans la sidebar.
- Le rideau ne masquant plus la nav, les **états de chargement au niveau page** doivent être
  soignés (certaines pages peuvent flasher « vide » brièvement avant le préchargement `056`).
