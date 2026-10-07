# TASK 044 — webapp : nettoyage des modules Nuxt (color-mode, image, fonts)

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-11 — **prémisse caduque**, résultats atteints (voir « Mise à jour »).
- **Type** : Front — Webapp Nuxt (hygiène de configuration).
- **Revient sur** : tâche `035` (initialisation modules) — voir « Écart assumé » ci-dessous.
- **Réf** : `webapp/nuxt.config.ts` · `webapp/package.json` · `webapp/README.md`.

## Contexte

Nuxt UI 4 enregistre lui-même une partie des modules que la webapp déclare en double. Vérifié
dans `webapp/node_modules/@nuxt/ui/dist/module.mjs` (l. 28-52) : il installe `@nuxt/icon`
(toujours), `@nuxt/fonts` (sauf `ui.fonts: false`), `@nuxtjs/color-mode` (sauf
`ui.colorMode: false`) et `@nuxtjs/mdc` (en optionnel). Les trois sont déjà présents en
transitif via `@nuxt/ui` (`@nuxt/fonts` ^0.14.0, `@nuxtjs/color-mode` ^4.0.1).

Par ailleurs `@nuxt/image` a été retiré des `modules` par la PR #47 (son serveur `ipx` crashait
le SSR prod : `Cannot find package 'ofetch'`) mais reste dans `dependencies`.

## Objectif

Réduire `nuxt.config.ts` et `package.json` aux déclarations réellement nécessaires, sans
changer le comportement runtime.

## Périmètre

- Retirer `'@nuxtjs/color-mode'` du tableau `modules` **et** de `dependencies`. Conserver le
  bloc `colorMode: { classSuffix: '', preference: 'system', fallback: 'light' }` : Nuxt UI ne
  pose que des *defaults* (`classSuffix: ''`, `disableTransition: true`), la config projet
  continue de primer.
- Retirer `@nuxt/image` de `dependencies` (déjà hors `modules`). Le commentaire d'explication
  dans `nuxt.config.ts` reste, il documente la réactivation future.
- Retirer `'@nuxt/fonts'` du tableau `modules` et de `dependencies`, **et** retirer le bloc
  `fonts: { families: [...] }`. **Conserver `ui: { fonts: false }`** — sans lui, Nuxt UI
  réenregistrerait son propre `@nuxt/fonts` (il l'a en dépendance). Décision du 2026-07-09 :
  **aucun module de fonts** ; les polices sont chargées manuellement en `@font-face` depuis des
  `.woff2` locaux (`font-display: swap`) — mise en place détaillée dans la tâche `054`
  (ambiances). Transition : tant que la `054` n'est pas livrée, poser un `@font-face` minimal
  Exo 2 + Manrope dans `app/assets/css/` pour ne pas casser le rendu.

## Invariants

- Aucune requête réseau externe pour les polices, ni au runtime **ni au build** (fichiers
  `.woff2` versionnés dans le repo).
- Exo 2 (titres) / Manrope (UI) restent les polices effectives — `brand_guidelines.md`.
- `ui: { fonts: false }` reste posé : Nuxt UI ne doit jamais réenregistrer `@nuxt/fonts`.
- Le mode sombre continue de suivre la préférence système avec repli clair.

## Écart assumé vs tâche 035

La tâche `035` avait installé `@nuxt/fonts` (auto-hébergement au build, provider Google).
Décision du 2026-07-09 : **retrait complet du module** au profit de `@font-face` manuels sur
`.woff2` locaux — maîtrise totale du chargement (`font-display: swap`), zéro dépendance au
provider Google au build, et prérequis du système d'ambiances (tâche `054`) où les duos
typographiques sont générés depuis un YAML. `ui.fonts: false` (posé par la `035`) est conservé.

## Critères d'acceptation

- [ ] `bun run quality-check` vert (lint, format, type-check).
- [ ] Build SSR OK ; `dir="rtl"` toujours présent sur `/ar`.
- [ ] Exo 2 et Manrope chargées via `@font-face` depuis des `.woff2` du repo (aucune requête
      vers `fonts.googleapis.com` / `fonts.gstatic.com`, ni au build ni au runtime).
- [ ] Bascule clair/sombre fonctionnelle, classe `.dark` posée sans suffixe.
- [ ] `@nuxtjs/color-mode`, `@nuxt/fonts` et `@nuxt/image` absents de `package.json` ;
      `ui: { fonts: false }` toujours présent dans `nuxt.config.ts`.

## Hors périmètre

- Réactivation de `@nuxt/image` (à traiter quand l'optimisation d'images sera nécessaire,
  en réglant le bundling `ipx`/`ofetch`).
- Mapping des tokens brand vers `--ui-*` → tâche `051`.

## Mise à jour 2026-07-11 — prémisse caduque

Cette tâche partait du constat que **Nuxt UI enregistrait ces modules en double**. Or **Nuxt UI
a été retiré** : le design system s'appuie désormais sur **Tailwind CSS 4 seul** (plugin Vite
`@tailwindcss/vite`). La prémisse tombe — mais les résultats visés sont atteints :

- `@nuxt/image` : retiré des dépendances ✅
- `@nuxt/fonts` : retiré (modules + deps + bloc `fonts:`) ✅ — les polices sont chargées en
  **`@font-face` manuels** depuis des `.woff2` locaux (`base/app/assets/css/fonts.css`,
  `font-display: swap`, sous-ensembles latin + latin-ext)
- `ui: { fonts: false }` : **sans objet** (plus de Nuxt UI)
- ⚠️ **`@nuxtjs/color-mode` est CONSERVÉ**, contrairement au périmètre initial. Il n'était à
  retirer *que parce que* Nuxt UI le fournissait ; sans Nuxt UI, il doit être déclaré
  explicitement.

Les polices posées sont **Sora + Geist** (le brand, cf. `brand_guidelines.md`) — et non
Exo 2 / Manrope, valeurs périmées du spike `022`.

Commit : `d513414`.
