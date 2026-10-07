# webapp — Nuxt Layers (Public Website + SaaS Platform)

## Ce que c'est

L'app Nuxt des 4 surfaces, en **architecture layers** (reprise du cadrage tâche 022) : un
**socle commun** (`base/`) et **une layer par espace** (`spaces/{public,candidat,entreprise,admin}`).
**Une build = un espace**, sélectionnée par `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE` : chaque host (`showcase-recruiter-webapp-saas.example`,
`candidat.`, `entreprise.`, `admin.`) est servi par sa build. L'espace n'est plus résolu par
host à chaque requête (`resolveSpace` supprimé) : il est **structurel** — une page absente de
la layer d'espace répond 404 nativement, sans `definePageMeta({ spaces })` ni garde middleware.

```txt
nuxt.config.ts        # extends: ['./spaces/${SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE}', './base'] — la 1re entrée gagne
shared/utils/space.ts # SPACES, Space, SPACE_ROLE_PREFIX (racine → alias #shared intact)
base/                 # layer socle : modules, thème, i18n, layout, login, BFF, middleware auth
└── app/ · server/ · i18n/
spaces/<espace>/      # pages et surcharges propres à l'espace (shadowing fichier par fichier)
```

Règles layers (doc 4.x, vérifiées) : ne **pas** créer de dossier `layers/` (auto-enregistré →
les 4 espaces actifs ensemble) ; `nuxt.config.ts` requis dans chaque layer même vide ; dans le
`nuxt.config` d'une layer, les chemins relatifs résolvent vers la racine →
`fileURLToPath(new URL(...))` ; en Nuxt 4 l'`app.config.ts` d'une layer vit dans `<layer>/app/`.

L'auth reste en **BFF** (`nuxt-auth-utils`) : le client ne voit jamais le JWT — login délégué à
l'API Symfony (`/v1/auth/login`), session scellée en cookie httpOnly scopé domaine parent (SSO
inter-sous-domaines), Bearer attaché côté serveur (`base/server/utils/showcase-recruiter-webapp-saas-api.ts`). La garde
de rôle par espace vit dans `base/app/middleware/auth.global.ts` (`ROLE_CANDIDATE` /
`ROLE_COMPANY_*` / `ROLE_ADMIN_*`). i18n FR/EN/AR (AR = RTL), dark mode, fonts auto-hébergées
(Exo 2 / Manrope, self-hébergées en `@font-face`), Tailwind CSS 4 en standalone (plugin Vite,
sans Nuxt UI), icônes Lucide via `@nuxt/icon` bundlées en local (aucun CDN — `<Icon
name="lucide:x" />`), Pinia, VueUse, compat Nuxt 5 activée.

Réf : [architecture.md §5](../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md) ·
[docker-and-make.md](../docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md) ·
tâche `022` (cadrage layers).

## Comment on s'en sert

```bash
bun install                   # deps (bun = PM officiel du projet)
bun run dev:entreprise        # dev server :3000 sur l'espace entreprise
bun run dev:public            # idem pour public / dev:candidat / dev:admin
bun run dev                   # espace via SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE (défaut : public)
bun run quality-check         # lint + format + type-check — gate CI webapp-quality
bun run build                 # build SSR d'UN espace : SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=entreprise bun run build
```

Ces commandes `bun run dev:*` lancent **un seul espace** : le host ne pilote pas l'espace, c'est
`SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE` qui le fige au démarrage. Un serveur unique sert donc la même build sur tous les
hosts.

**En dev Docker, `make dev-up` lance les 4 espaces derrière Caddy** — un host, une build, comme en
prod : `localhost`, `candidat.localhost`, `entreprise.localhost`, `admin.localhost` (port
`DEV_PROXY_PORT`, 8080 par défaut). Les liens inter-espaces (`useSpaceUrl`) fonctionnent donc en
dev, et le cloisonnement des espaces s'y vérifie pour de vrai.

Deux points à connaître :

- **Écart assumé avec la prod : le dev est en HTTP**, donc le cookie de session y est
  `Secure: false` (`NUXT_SESSION_COOKIE_SECURE` dans `docker-compose.dev.yml`) et il n'y a pas de
  SSO inter-espaces par cookie parent.
- **Après un changement de dépendances JS**, relancer `make dev-reinstall` (et non `dev-up-build`) :
  `node_modules` est un volume anonyme, jamais mis à jour par un simple rebuild — il masquerait les
  nouvelles deps.

Variables d'environnement : `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE` (espace de la build), `NUXT_PUBLIC_BASE_DOMAIN`
(liens inter-espaces `useSpaceUrl`, `localhost` en dev), `NUXT_PUBLIC_API_BASE` /
`NUXT_API_INTERNAL_BASE` (API vue du navigateur / du réseau Docker), `NUXT_SESSION_PASSWORD`
(≥ 32 car., **partagé entre les 4 builds** pour le SSO) et `NUXT_SESSION_COOKIE_DOMAIN`
(`.showcase-recruiter-webapp-saas.example` en prod, vide en dev).

## Conventions vérifiées en CI

Les conventions d'architecture ne sont pas que des READMEs : elles sont **exécutées**.

```bash
bun run quality-conventions-check   # scripts/ci/rules.yaml → violations, fichier et ligne
```

Le README d'une couche porte le **pourquoi** ; `scripts/ci/rules.yaml` porte le **comment
vérifier**. Chaque règle cite la section de README qui la fonde. **Treize règles actives**, dans
l'ordre du fichier : frontière de `services/` (zéro import framework), pas de `$fetch` dans un
composant, pas de `definePageMeta({ spaces })`, aucun texte utilisateur en dur, `@nuxtjs/i18n`
encapsulé derrière `useLocale()`, une page qui ne porte pas de code interactif, le marqueur de
composant sur la racine, pas de région de page en racine de composant, un repère principal par
layout et un seul, pas de palier d'espacement brut dans une coque, pas de repère principal dans une
page, les primitives VueUse plutôt que les API DOM brutes, et aucun fichier qui répète le nom de son
espace.

**Toute nouvelle règle doit être prouvée par une violation volontaire** avant d'être commitée :
une gate qui ne mord jamais est pire qu'inutile — elle rassure sans protéger.

Cette exigence a longtemps été écrite sans être tenue, et ça s'est vu : l'isolation de la région
`template` s'arrêtait à la première `</template>`, donc à celle du premier slot nommé. Deux règles
lisaient une fraction des fichiers et rendaient vert. Les preuves vivent désormais **à côté du code
qu'elles protègent** — `scripts/ci/*.vitest.ts`, joués par `bun run quality-test`. Une règle dont
aucun test n'exige la détection est une règle dont personne ne saura qu'elle a cessé de mordre.

Une règle peut porter une **dette** dans son champ `except:` : les fichiers qui la violaient avant
qu'elle ne morde. Chaque ligne nomme **l'espace responsable** et le bloc porte sa date d'ouverture ;
elle se retire par une carte de cet espace. Toute page nouvelle ou modifiée, elle, est prise
immédiatement — la dette est comptée et décroissante, jamais un permis d'ignorer la règle.

## Contrat de données (types générés)

**L'API est la source de vérité.** Elle produit son contrat (`api/openapi.json`, commité) via
`make dev-openapi` ; le front en **génère** ses types TypeScript — jamais écrits à la main.

```bash
bun run quality-generate-schemas   # api/openapi.json → shared/types/api.ts
bun run quality-contracts-check    # régénère et échoue si le résultat diffère du commité
```

`shared/types/api.ts` est **généré** : ne jamais l'éditer (il est exclu d'ESLint et de Prettier).
La source est paramétrable par `SHOWCASE_RECRUITER_WEBAPP_SAAS_OPENAPI_SOURCE` (une URL d'API en dev, l'artefact commité
par défaut — la CI et le build tournent ainsi **sans API disponible**).

Consommation :

```ts
import type { operations } from '#shared/types/api'

type LoginResponse = operations['login_check_post']['responses'][200]['content']['application/json']
```

**Deux gates CI, et il en faut deux** :

1. `api-quality` régénère `api/openapi.json` et échoue s'il diffère → le **producteur** garde son
   contrat à jour ;
2. `webapp-quality` régénère `shared/types/api.ts` et échoue s'il diffère → le **consommateur**
   reste en phase.

Sans la première, on changerait l'API sans régénérer le contrat, et la seconde validerait un
artefact périmé.

## Langues

Le catalogue des langues vit dans un **registre unique et pur** (`shared/utils/locale.ts` +
`shared/types/i18n.ts`) : `AVAILABLE_LOCALES`, `FALLBACK_LOCALE`, `isLocaleCode()`,
`normalizeLocale()`. Il est importé par le `nuxt.config` (qui en dérive son bloc `i18n`), par
l'app, par le serveur et par les tests — **aucune liste de langues en dur nulle part**.

`@nuxtjs/i18n` reste le **seul arbitre de la résolution** de la locale, dans cet ordre :

1. **URL** — un préfixe (`/en/…`, `/ar/…`) fixe la langue, toujours. Une URL = une langue.
2. **Cookie `showcase-recruiter-webapp-saas_locale`** — à l'arrivée sur `/` uniquement : mémoire du choix précédent.
3. **`Accept-Language`** — première visite (pas de cookie) : redirection puis pose du cookie.
4. **Défaut `fr`** — sans préfixe (`prefix_except_default`).

Soit **URL > cookie > navigateur > défaut**. Le cookie prime sur le navigateur : un choix
explicite l'emporte sur une préférence système. **Ne jamais écrire de détection maison** (cookie,
`Accept-Language`, middleware de langue) : ce serait un second arbitre aux règles divergentes.

Côté app, `@nuxtjs/i18n` est **encapsulé** derrière `useLocale()` (`{ current, dir, locales,
setLocale, localePath }`) — seul point de contact autorisé avec le module. Exception assumée :
`$t`, utilisé tel quel dans les templates. Le wrapper s'appuie sur `useNuxtApp().$i18n` et non
sur `useI18n()`, qui exige un contexte `setup()` et casserait dans un route middleware.

## Impacts déploiement (à traiter avec la tâche 036)

Preprod/prod passent de 1 service `webapp` à **4 services** (une image par espace,
`--build-arg SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=...`) ; le Caddyfile route chaque host vers son service au lieu de
tout envoyer sur `webapp:3000`. Compose preprod/prod et CI (matrice de builds) à adapter —
non couvert par cette branche.

**Reste à brancher** (inchangé) : refresh token BFF, types TS générés depuis l'OpenAPI (gate
`contracts-drift`), design system (tokens `brand_guidelines.md` → thème Tailwind), contenu
Markdown du site public.
