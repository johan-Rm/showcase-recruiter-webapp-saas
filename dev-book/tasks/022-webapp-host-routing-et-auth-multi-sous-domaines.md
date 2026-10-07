# TASK 022 — webapp : host-routing multi-sous-domaines + auth (2 étages)

- **Créée le** : 2026-06-24.
- **Statut** : ✅ terminé le 2026-07-11 — livré **autrement** que ce cadrage (voir « Mise à jour »).
- **Périmètre** : définir comment **une seule app Nuxt** (`webapp/`) sert ses **4 hôtes** (apex public +
  candidat + recruteur + admin) avec des **URLs propres par sous-domaine**, et comment l'auth couvre
  les **7 hôtes authentifiés sur 8**. Inclut un **spike** pour verrouiller l'hydratation avant scaffold.
- **Hors périmètre** : le contenu fonctionnel de chaque espace (pages métier), le scaffold complet
  (vient après le spike), la doc API (`docs/api`).

## Contexte

`webapp/` est **vide**. Le Caddyfile route déjà 4 hôtes vers `webapp:3000` :
`{apex}, candidat., entreprise., admin.`. Sur les **8 hôtes**, **seul l'apex `showcase-recruiter-webapp-saas.example`
(vitrine SEO) est public** ; les **7 autres sont authentifiés** : `api.`, `candidat.`,
`entreprise.`, `admin.`, **`docs.`, `swagger.`, `dev-book.`**. Cf. `architecture.md` §5.

## Déjà tranché

- **Une seule app Nuxt** sert les 4 faces, routée par **host + rôle** (cf. `architecture.md` §5).
- **Organisation en Nuxt Layers** (`extends`) : une couche `shared` + une couche par espace.
  Les layers fusionnent à la compilation et **n'apportent pas** le routage par host (voir ci-dessous).
- **BFF `nuxt-auth-utils`** (Nitro) : session scellée server-only, **cookie httpOnly sur
  `.showcase-recruiter-webapp-saas.example`** → **SSO inter-sous-domaines**.
- **Routage = option A** : URLs propres par sous-domaine (pas de préfixe visible type `/candidat`).

## Le point dur : URL propre + hydratation (à valider par spike)

Dans une seule app Nuxt, une même **URL client `/`** ne peut pas **naturellement résoudre vers 4
pages différentes** sans une **logique de routage dépendante du host**. Un **rewrite serveur
transparent** (host → préfixe interne) **casse l'hydratation** (le client résout l'URL
non-préfixée). Nuxt n'a **pas** d'équivalent documenté à `NextResponse.rewrite`.

→ Approche retenue **A1 — préfixe host-dérivé symétrique** : pages namespacées
`pages/{public,candidat,recruteur,admin}/…`, et le préfixe est ajouté/retiré **de la même façon
côté serveur et client** (les deux dérivent le host : `useRequestURL()` / `window.location.host`),
via un remap dans `app/router.options.ts`. **À prouver par spike** (≠ A2 catch-all « contenu par
host », adapté au CMS multi-tenant mais pas à nos 4 jeux de features distincts).

> ⚠️ **Zone fragile.** `router.options.ts` permet bien de customiser les routes, mais **modifier les
> routes à la main** a des effets de bord à vérifier : `definePageMeta`, layouts, middlewares,
> navigation `<NuxtLink>`, et **génération des noms de routes**. C'est précisément ce que le spike
> doit dérisquer (cf. checklist ci-dessous).

## Détection du host (brique commune, SSR-safe)

Plugin/middleware **Nitro** : `getRequestHost(event)` → `event.context.space`
(`public|candidat|recruteur|admin`). Sert **deux fois** : routage **et** garde d'accès.

## Auth : 3 mécanismes distincts (à ne pas confondre)

Le SSO couvre les **7 hôtes authentifiés** **côté expérience utilisateur**, mais **techniquement**
`api.` n'est pas gardé comme les autres. Trois mécanismes :

```text
api.showcase-recruiter-webapp-saas.example           → auth JWT API Platform (Bearer, pas le cookie de session)
webapp (candidat/recruteur/admin) → auth session BFF Nuxt (middleware par space + rôle)
docs / swagger / dev-book  → auth Caddy forward_auth (délègue la décision à l'API)
```

1. **webapp (BFF Nuxt)** : middleware par `space` — `public` (apex) libre ; `candidat`/`recruteur`/
   `admin` = session requise + **rôle** correspondant (`ROLE_CANDIDATE`,
   `ROLE_COMPANY_OWNER` ou `ROLE_COMPANY_MEMBER`, `ROLE_ADMIN_*`).
2. **Services Docus `docs.`/`swagger.`/`dev-book.`** (statiques, **ouverts aujourd'hui**) : garde au
   niveau **Caddy `forward_auth`** vers un endpoint API qui valide le cookie de session (Caddy est
   prévu pour déléguer la décision d'accès à un service d'auth). Rejoint le « stratégie d'auth privée
   dev-book » noté hors-scope en tâche 007.
3. **`api.`** : reste gardé par **JWT API Platform** (Bearer attaché par le BFF), pas par le cookie.

> ⚠️ **Cookie `.showcase-recruiter-webapp-saas.example`** : `nuxt-auth-utils` stocke une **session scellée dans le cookie**,
> avec la limite classique **~4096 octets**. N'y mettre que le **strict nécessaire** : id user, rôle,
> space autorisé, expiration. **Pas** le profil complet (à charger via l'API à la demande).

## Spike (avant scaffold complet)

PoC sur **2 faces** (apex public + candidat authed) avec le remap `router.options.ts` host-dérivé.
Le spike n'est validé que si **tous** ces cas passent (la fragilité du remap les impose) :

- [ ] **URL propre** par host (`candidat.showcase-recruiter-webapp-saas.example/profil`, sans `/candidat` visible).
- [ ] **Navigation directe URL** (entrée au clavier / lien externe).
- [ ] **Navigation `<NuxtLink>`** interne (client-side) cohérente avec le host.
- [ ] **Refresh navigateur** sur une page profonde (SSR re-rendu correct).
- [ ] **Hydratation clean** — aucun mismatch SSR/client.
- [ ] **Middleware d'auth par `space`** (redirection login si non authentifié sur `candidat.`).
- [ ] **Layout différent par espace** (public vs candidat) résolu correctement.
- [ ] **Erreur 404 par espace** (page inconnue → 404 du bon espace, pas fuite cross-space).
- [ ] **Redirection login / logout** (retour au bon host après auth).
- [ ] **Noms de routes / `definePageMeta`** non cassés par le remap.
- [ ] **Détection host SSR-safe en dev local** (sous-domaines `*.localhost` ou équivalent — **DX à décider**).

## Définition de terminé (de cette tâche de cadrage)

- Spike A1 concluant **ou** bascule documentée vers une alternative.
- Structure des layers arrêtée (contenu de `shared` vs chaque espace).
- Stratégie `forward_auth` Caddy esquissée pour les 3 Docus.
- Stratégie de dev local des sous-domaines décidée.

## Références

- `architecture.md` §5 (sous-domaines, auth, SSO) · `docker/caddy/Caddyfile` · tâche 007 (auth dev-book).
- Nuxt Layers : <https://nuxt.com/docs/4.x/getting-started/layers>
- Nuxt custom routing (`router.options.ts`) : <https://nuxt.com/docs/4.x/guide/recipes/custom-routing>
- `nuxt-auth-utils` (session scellée, limite cookie) : <https://nuxt.com/modules/auth-utils>
- Caddy `forward_auth` : <https://caddyserver.com/docs/caddyfile/directives/forward_auth>
- Multi-tenant Nuxt (host detection) : <https://www.adamdehaven.com/articles/powering-multi-tenant-applications-with-nuxt>
- Discussion subdomains Nuxt : <https://github.com/nuxt/nuxt/discussions/16142>

## Mise à jour 2026-07-11 — livré, mais autrement

L'approche **A1** (pages namespacées + remap `router.options.ts`) n'a **pas** été retenue : trop
fragile (hydratation, noms de routes), c'est ce que le spike devait éprouver. La webapp a été
livrée en **Nuxt Layers avec une build par espace** — ce cadrage envisageait les layers comme
*organisation*, ils sont devenus le *mécanisme* :

- l'espace est **figé au build** (`SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE`), plus résolu par host à chaque requête —
  `resolveSpace()` a été supprimé ; chaque host est servi par sa propre build ;
- **l'existence d'une page EST son périmètre d'espace** (404 natif), donc plus de
  `definePageMeta({ spaces })` ni de garde 404 par middleware ;
- socle dans `webapp/base/`, un espace par layer dans `webapp/spaces/<espace>/`.

Livrés : auth BFF (session scellée httpOnly), garde par rôle, i18n FR/EN/AR, dark mode.
**Reste** : le déploiement en 4 services + Caddy → tâche `036`.

Commits : `4d9e429` (migration layers), `d513414` (design system + atomisation).
