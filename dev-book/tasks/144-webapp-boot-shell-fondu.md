# TASK 144 — webapp : rideau de boot en fondu (sortie douce, moins de bruit d'hydratation)

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/`).
- **Placement** : affine `AppBootShell` (`135`) — la disparition en `v-if` était brutale ; on passe à
  un fondu d'opacité + démontage tardif (pattern de référence blue-bay, version simple).
- **Dépend de** : `135` (rideau render-first), `055a` (jalons).
- **Réf** : `blue-bay-mogador` (`AppBootShell.vue`, fondu + démontage différé).

## Contexte

Le rideau se retirait via `v-if` → disparition **instantanée**. Un HAR de dev a aussi montré que le
Nuxt DevTools signale la différence pré/post-hydratation sur `AppBootShell` (rideau présent au SSR,
absent après). Un fondu adoucit la sortie et garde l'élément monté pendant la transition.

## Livré

- `base/app/components/AppBootShell.vue` — deux états : `booted` (l'app est prête → opacité 0) et
  `faded` (après le fondu → démontage `v-if`). Transition CSS `transition-opacity duration-500`. Le
  démontage différé passe par `useTimeoutFn` (VueUse), pas de timer brut.

## Décisions

- **Fondu + démontage tardif** plutôt que `v-if` sec : l'élément reste dans le DOM le temps de la
  transition, puis se retire — sortie douce.
- **Version simple** (deux flags, pas de failsafe/logger comme blue-bay) : `booted` se résout
  toujours (succès / erreur / espace non amorcé), aucun blocage possible.
- **`useTimeoutFn`** (convention VueUse) pour le délai de démontage.

## Critères d'acceptation

- [x] Refresh : rideau logo + badge, puis **fondu** vers le contenu (plus de disparition sèche).
- [x] Le rideau reste réservé aux espaces authentifiés et au boot initial.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Le hint DevTools peut subsister** : il compare le SSR au DOM final ; comme le rideau finit par
  partir, la différence reste signalée en **dev** — c'est dev-only, sans impact prod. Le fondu
  améliore surtout la sortie visuelle.
- Le vrai mismatch d'hydratation Vue restant est le **toggle de thème** (color-mode) — traité à part.
