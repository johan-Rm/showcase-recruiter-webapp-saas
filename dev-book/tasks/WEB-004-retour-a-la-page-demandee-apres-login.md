# TASK WEB-004 — socle webapp : revenir à la page demandée après connexion

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-29 — `safeRedirectTarget` testé (vitest vert).
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (socle `base/` + `shared/`), aucun changement d'API.
- **Réf** :
  - `webapp/base/app/middleware/auth.global.ts` (la garde qui renvoie au login)
  - `webapp/base/app/composables/useLogin.ts` (la redirection d'après-login)
  - task 037 (auth fonctionnelle BFF) · task 049 (refresh token BFF)
  - `webapp/shared/README.md` (code pur, testable en `*.vitest.ts`)

## Contexte

Une URL profonde tapée ou partagée (`/candidats/<id>`, `/entreprises?statut=en-revue`, un lien
collé dans une conversation) est perdue dès qu'on n'a pas de session : la garde renvoie vers
`/login` **sans mémoriser la cible**, et `useLogin` conclut invariablement par
`navigateTo(localePath('/'))`. L'utilisateur se connecte, atterrit sur l'accueil de son espace, et
doit refaire à la main la navigation qu'il avait demandée.

Le même trou s'ouvre à l'expiration de session en cours de navigation : le rebond vers `/login`
efface la page sur laquelle on travaillait.

Rien dans le socle ne porte aujourd'hui cette notion — `grep -ri redirect` sur `base/` ne remonte
que les redirections OAuth du BFF, sans rapport.

## Décisions

### La cible voyage dans l'URL (`/login?redirect=…`), pas dans un store ni un cookie

Un login se joue souvent après un rechargement complet (lien ouvert dans un nouvel onglet, session
expirée, retour d'e-mail) : un `ref` de store ne survivrait pas, un cookie survivrait **trop** — une
cible oubliée dedans détournerait une connexion volontaire, des jours plus tard, vers une page que
personne n'a demandée. La query est SSR-safe, expire avec l'écran, et reste lisible dans la barre
d'adresse.

### On capture `to.fullPath`, et on y retourne SANS `localePath`

`fullPath` porte déjà le préfixe de locale (`strategy: 'prefix_except_default'`) et la query. Le
repasser à `localePath` au retour produirait `/en/en/candidats/…`. La cible est donc consommée telle
quelle par `navigateTo`.

### Une garde d'assainissement, en code pur et testé

Seul un chemin **interne** est accepté : commence par `/`, mais pas `//` ni `/\` (URL protocol-relative),
pas de schéma (`http:`, `javascript:`), pas d'hôte. Tout le reste → `/`. C'est la protection contre
l'open redirect, et c'est ce qui interdit au passage de sauter d'un espace à l'autre (chaque espace
est un host distinct : une cible cross-espace est forcément absolue, donc rejetée).

La fonction vit dans `webapp/shared/utils/` — zéro import framework, testable en `*.vitest.ts` sans
runtime Nuxt, conformément à la convention des couches.

### Ce qu'on ne capture jamais

`/login` lui-même, les routes auth-free (`auth-magic-link`, `auth-define-password`), le tunnel
`inscription` et la racine `/`. Capturer l'accueil n'apporte rien et alourdit l'URL ; capturer un
écran d'auth produirait une boucle.

### La garde reste l'autorité, la cible ne contourne rien

Après connexion, `auth.global` rejoue sur la cible : un candidat qui se reconnecte sur la build
entreprise rebondit vers `/login` comme aujourd'hui. Le `redirect` est une commodité de navigation,
jamais un droit d'accès.

### Le login garde son état d'inscription

`/login?inscription` est déjà porté par la query (`useRegistration`). Le paramètre `redirect` doit
cohabiter avec lui sans l'écraser dans un sens ni dans l'autre.

### La déconnexion n'emporte pas de cible

`useLogout` continue d'aller sur `/login` nu : quitter est volontaire, renvoyer l'utilisateur sur la
page qu'il vient de fermer à la reconnexion serait une surprise, pas un service.

### Le magic link garde sa destination métier — hors périmètre

`/auth/magic-link?token=…` est construit par l'API et arrive par e-mail : il ne portera pas de
`redirect`, et sa destination (`/inscription/entreprise` ou `/`) est une règle métier, pas une
reprise de navigation. Idem pour `define-password`, qui renvoie au login par un lien statique.
Traiter ces deux chemins supposerait de faire transiter la cible par l'API — un autre sujet.

## Livré attendu

- **`webapp/shared/utils/redirect.ts`** — `safeRedirectTarget(value: unknown): string | null`, pure
  et sans dépendance framework.
- **`webapp/shared/utils/redirect.vitest.ts`** — cas nominal, `//evil.tld`, `/\evil.tld`,
  `https://evil.tld`, `javascript:…`, valeur absente, valeur multiple (`?redirect=a&redirect=b` →
  Vue Router rend un tableau), chemin avec query et hash.
- **`webapp/base/app/middleware/auth.global.ts`** — la branche « session requise » construit
  `localePath({ path: '/login', query: { redirect: to.fullPath } })`, sauf pour les cas exclus
  ci-dessus.
- **`webapp/base/app/composables/useLogin.ts`** — lit `route.query.redirect`, l'assainit, et
  redirige vers la cible ou `/` à défaut.

## Critères d'acceptation

- [ ] Ouvrir une URL profonde d'un espace protégé sans session → `/login?redirect=<chemin>` ; après
      connexion, on atterrit **sur cette URL**, query et hash compris.
- [ ] Aller sur `/login` directement puis se connecter → accueil de l'espace (aucun `redirect`
      résiduel).
- [x] `?redirect=https://evil.tld`, `?redirect=//evil.tld` → accueil de l'espace, aucune sortie du
      domaine. _(couvert par `redirect.vitest.ts`)_
- [ ] En locale non par défaut (`/en/...`), la cible est restituée avec **un seul** préfixe de
      locale.
- [ ] `/login?inscription` fonctionne inchangé, avec ou sans `redirect` dans l'URL.
- [ ] Un compte au rôle étranger à l'espace visé rebondit toujours vers `/login`, cible ou pas.
- [ ] Déconnexion → `/login` sans `redirect`.
- [ ] `bun run quality-check` vert (dont `quality-test`, et le type-check des 4 espaces).

## Hors périmètre

- Reprise de navigation après **magic link** et **définition de mot de passe** (cf. décision).
- Reprise de navigation **entre espaces** (hosts distincts) — refusée par construction.
- Rebond automatique à l'expiration de session pendant un appel API (le 401 du BFF) : c'est un
  chantier propre, qui réutilisera la même garde une fois celle-ci en place.
