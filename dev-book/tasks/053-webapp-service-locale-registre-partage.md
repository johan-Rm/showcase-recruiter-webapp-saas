# TASK 053 — webapp : service de gestion de la langue (registre partagé)

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-12.
- **Type** : Front — Webapp Nuxt (i18n, architecture).
- **Dépend de** : rien. **S'articule avec** : tâche `046` (les fonctions pures du registre
  entrent dans le périmètre Vitest) et tâche `052` (contenu multilingue de la vitrine).
- **Réf** : `webapp/nuxt.config.ts` (bloc `i18n`) · projet de référence `blue-bay-mogador`
  (hors repo) : `shared/utils/locale.ts`, `shared/types/i18n.ts`.

## Contexte

Le catalogue des langues (FR/EN/AR, avec `dir: 'rtl'` pour l'arabe) vit **en dur dans le bloc
`i18n` de `nuxt.config.ts`**. Toute logique qui a besoin de connaître les langues supportées —
sélecteur de langue, alternates `hreflang` du sitemap (tâche `045`), contenu Markdown localisé
(tâche `052`), formats de dates — devra soit dupliquer la liste, soit interroger le module.

Le projet de référence `blue-bay-mogador` résout ça par un **registre unique framework-agnostic**
dans `shared/`, importé par `nuxt.config.ts` et consommé par l'app, le serveur et les helpers.

## Règles de résolution de la locale (documentées, non modifiées)

La séquence est **entièrement arbitrée par `@nuxtjs/i18n`** (config actuelle :
`strategy: 'prefix_except_default'`, `detectBrowserLanguage` avec cookie `showcase-recruiter-webapp-saas_locale`,
`redirectOn: 'root'`) :

1. **URL** — un préfixe de langue dans la route (`/en/...`, `/ar/...`) fixe la locale,
   toujours. Une URL = une langue (liens partageables, SEO).
2. **Cookie `showcase-recruiter-webapp-saas_locale`** — uniquement à l'arrivée sur la racine `/` : mémoire du choix
   précédent de l'utilisateur, redirection vers sa langue.
3. **Navigateur (`Accept-Language`)** — uniquement si pas de cookie (première visite) ;
   redirection puis pose du cookie.
4. **Défaut `fr`** — sans préfixe (`prefix_except_default` : `/` est déjà une page FR).

Soit : **URL > cookie > navigateur > défaut**. Le cookie prime sur le navigateur parce qu'un
choix explicite (sélecteur de langue) l'emporte sur une préférence système.

## Objectif

Une source de vérité unique pour le catalogue des langues, sans toucher aux règles de
résolution ci-dessus.

## Périmètre

- `webapp/shared/types/i18n.ts` : `LocaleCode` (union stricte `'fr' | 'en' | 'ar'`),
  `LocaleInfo` (`code`, `iso`, `name`, `dir: 'ltr' | 'rtl'`, fichier de messages).
- `webapp/shared/utils/locale.ts` — pur, zéro import framework :
  - `AVAILABLE_LOCALES` (`as const satisfies Record<LocaleCode, LocaleInfo>`) ;
  - `FALLBACK_LOCALE = 'fr'`, `SUPPORTED_LOCALES` (ordre stable) ;
  - `isLocaleCode()` (type guard), `normalizeLocale()` (`fr-FR` → `fr`, inconnu → fallback).
- `nuxt.config.ts` : le bloc `i18n.locales` est **généré depuis le registre**
  (`Object.values(AVAILABLE_LOCALES).map(...)`), `defaultLocale: FALLBACK_LOCALE`. La liste
  en dur disparaît.
- Tests colocalisés `shared/utils/locale.vitest.ts` (convention tâche `046`) : type guard,
  normalisation (`fr-FR`, `AR`, `en-US`, code inconnu, chaîne vide), stabilité de l'ordre.
- Documenter la séquence de résolution (section ci-dessus) dans `webapp/README.md`.

## Invariants

- **`@nuxtjs/i18n` reste le seul arbitre de la résolution.** Aucune détection navigateur ni
  lecture de cookie maison — le projet de référence duplique cette logique dans son
  `useLang()` (route > navigateur, en sautant le cookie) : deux arbitres aux règles
  différentes, **anti-modèle à ne pas copier**.
- Le registre est pur : importable par `nuxt.config.ts`, l'app, le serveur et les tests sans
  runtime Nuxt.
- `dir` fait partie du registre — le RTL arabe suit la locale partout (`/ar` → `dir="rtl"`).
- Une seule liste de langues dans tout le repo.

## Critères d'acceptation

- [ ] Plus aucune locale en dur dans `nuxt.config.ts` : le bloc `i18n` dérive du registre.
- [ ] Comportement inchangé : `/` en FR, `/en` et `/ar` servis, `/ar` en `dir="rtl"`,
      redirection racine par cookie puis `Accept-Language`, cookie `showcase-recruiter-webapp-saas_locale` posé.
- [ ] `shared/utils/locale.vitest.ts` vert, tous les cas de normalisation couverts.
- [ ] `webapp/README.md` documente la séquence URL > cookie > navigateur > défaut.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- **Locales activables au runtime** (par environnement, espace ou projet) : les trois langues
  sont fixes — l'étage `ProjectLocalesConfig` + plugin SSR + cache serveur du projet de
  référence est du YAGNI ici. À rouvrir si le besoin apparaît.
- Composable de routes localisées type `getLocalizedRoute()` : `useLocalePath()` de
  `@nuxtjs/i18n` couvre déjà le besoin.
- Sélecteur de langue UI (arrive avec le design system, tâche `051`).

## Mise à jour 2026-07-12 — livré

Registre pur en place (`shared/types/i18n.ts` + `shared/utils/locale.ts`), `nuxt.config` dérivé,
plus aucune liste de langues en dur — y compris le cast `as 'fr' | 'en' | 'ar'` qui traînait dans
`LocaleSwitcher.vue`.

**Ajout au périmètre initial** : `@nuxtjs/i18n` est désormais **encapsulé** derrière un wrapper
unique `useLocale()` (`{ current, dir, locales, setLocale, localePath }`), conformément à la règle
« Encapsulation des modules externes » (`base/app/README.md`). Les 7 usages directs de `useI18n()`
/ `useLocalePath()` ont été ramenés à ce seul point de contact. Exception assumée : `$t`.

**Piège rencontré** : le wrapper ne doit **pas** s'appuyer sur `useI18n()` (vue-i18n), qui exige
un contexte `setup()` de composant — `auth.global.ts` est un route middleware et plantait en 500
(« Must be called at the top of a `setup` function »). Il passe par `useNuxtApp().$i18n`,
disponible partout.

**Vitest installé** (le registre est le premier code pur testable) : `vitest.config.ts`, tests
colocalisés `*.vitest.ts`, script `quality-test` intégré à `quality-check`. 8 tests verts.
La tâche `046` peut désormais s'appuyer dessus.

Vérifié : `lang`/`dir` dérivés du registre (`/ar` → `dir="rtl"`), sélecteur alimenté par le
registre, redirection locale-aware du middleware intacte (`/ar` → `/ar/login`), quality-check vert.

