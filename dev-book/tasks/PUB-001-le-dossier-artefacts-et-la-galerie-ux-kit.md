# TASK PUB-001 — le dossier artefacts, et la galerie UX Kit à l'abri

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — `artefacts/` existe et porte la galerie UX Kit ; un middleware
  global ferme le dossier en production, la preprod le protège par mot de passe.
- **Type** : Refactor — déplacement d'une page et protection d'accès ; aucune logique métier touchée.
- **Tests** : un test sur la garde — les routes `artefacts` répondent 404 quand le drapeau
  `NUXT_PUBLIC_ARTEFACTS` est absent. Le rendu de la galerie n'est pas testé : c'est une
  démonstration visuelle, elle se juge à l'œil.
- **Réf** :
  - `webapp/base/app/pages/README.md` (§ « Périmètre d'espace structurel »)
  - `docker/caddy/Caddyfile` (le motif `@docs` + `basic_auth`, déjà en place huit fois)
  - task `WEB-002` (création de la galerie UX Kit)

## Contexte

Huit maquettes vivent éparpillées dans trois couches : `ux-kit.vue` dans le socle, six
`template-*.vue` dans l'espace entreprise, une dans l'admin. Il n'existe aucun endroit où les
regarder ensemble, ni où en montrer une à quelqu'un.

Surtout, **sept d'entre elles sont livrées en production**. Seul `ux-kit.vue` porte une garde
`import.meta.dev` ; les sept `template-*` n'en ont aucune et sont des routes réelles des builds
entreprise et admin.

## Périmètre

- Un dossier `webapp/spaces/public/app/pages/artefacts/`, fermé en production et protégé en preprod.
- La galerie **UX Kit** y est déplacée.
- Une garde d'environnement : les routes n'existent qu'en développement et en preprod.
- Un `basic_auth` sur le chemin `/artefacts*` du vhost preprod.

## Hors périmètre

- **Les sept `template-*.vue` restent dans leur espace** — voir la décision ci-dessous : elles ne
  peuvent pas déménager avant leur refonte.
- La refonte des maquettes sur composants Nuxt UI, une tâche par page.
- La promotion des layouts `cockpit` et `dashboard` dans le socle.
- Le `basic_auth` en développement.
- Toute exposition en production.

## Décisions de cadrage

### `spaces/public/`, pas `base/`

La demande initiale disait « dans `base/app/pages` », mais `base/` est la layer commune aux quatre
espaces : les artefacts apparaîtraient aussi sur `entreprise.`, `candidat.` et `admin.`, où le
`basic_auth` du vhost public ne les couvrirait pas. `base/app/pages/README.md` a déjà tranché la
règle : « une page vit dans la layer de l'espace qui doit la porter ; son existence **est** son
périmètre ».

### Seule la galerie déménage — les maquettes d'écrans ne le peuvent pas encore

*Décision prise pendant l'implémentation, contre le cadrage initial qui prévoyait les 8 pages.*

Le cadrage avait acté « pages nues, assumé » : privées de leur coque `cockpit` ou `dashboard`, les
maquettes rendraient mal en attendant leur refonte. Le type-check a montré que le problème était
d'un autre ordre.

Les sept `template-*.vue` n'appellent pas seulement un layout : elles consomment **cinq composants
et un composable** qui vivent dans leur espace d'origine et qu'aucun autre n'auto-importe.

| Dépendance | Espace | Consommée par de **vraies** pages ? |
| --- | --- | --- |
| `Gutter` | entreprise | oui — 6 pages, dont l'accueil et le compte |
| `ActionBar` | entreprise | oui — 2 pages de recherche |
| `ProfilePanel` | entreprise | oui — la table de recherche |
| `useProfilePanel` | entreprise | oui — idem |
| `DashboardBody` | admin | oui — 9 pages et le layout |
| `WireframeZoneName` | entreprise | non — maquettes uniquement |

Les déplacer casserait les écrans de production qui les utilisent ; les copier dupliquerait du code
qui divergerait en silence ; les promouvoir dans `base/` y ferait entrer des composants d'écran
d'entreprise et d'admin, contre la règle qui a justement décidé du placement.

Aucune de ces trois issues n'est acceptable pour un gain purement organisationnel. Les maquettes
d'écrans rejoindront `artefacts/` **au moment de leur refonte sur Nuxt UI**, quand elles n'auront
plus de dépendance d'espace. `ux-kit.vue`, lui, ne consomme que Nuxt UI et deux composants du socle
(`UiAvatar`, `UiInfoHint`) : il migre sans rien casser.

> **Ce que le type-check n'aurait pas attrapé.** Il a signalé le composable — un identifiant
> TypeScript. Les cinq composants, eux, sont résolus par Vue au runtime : non trouvés, ils rendent
> du vide avec un simple avertissement. Les gates seraient passées au vert et les maquettes
> auraient été livrées cassées.

### Un drapeau runtime, pas `import.meta.dev`

La preprod builde en `NODE_ENV: production` : `import.meta.dev` y vaut `false`, et la garde
actuelle d'`ux-kit` y produirait un 404 — l'inverse du besoin. D'où `runtimeConfig.public.artefacts`,
piloté par `NUXT_PUBLIC_ARTEFACTS`, présent en dev et preprod, absent en production.

L'application sait donc elle-même si elle doit servir ces routes, indépendamment de son mode de
build.

### Un middleware global, et la décision isolée en code pur

*Tranché à l'implémentation, la carte ne disait que « une garde d'environnement ».*

**Global et par préfixe d'URL**, plutôt qu'un middleware nommé déclaré page par page : une maquette
ajoutée plus tard est protégée sans rien avoir à déclarer, alors qu'un `definePageMeta` oublié ne se
verrait qu'en production, là où il n'y a plus de filet.

**La décision vit dans `shared/utils/artefacts.ts`**, pure : le périmètre des tests du dépôt est
volontairement limité au code sans runtime Nuxt (cf. `webapp/vitest.config.ts`). Un middleware
testé directement aurait imposé `@nuxt/test-utils`, contre un choix documenté. Le middleware n'est
donc plus qu'une coquille qui lève le 404.

Le test porte sur le **segment complet** et non sur le préfixe brut : `startsWith('/artefacts')`
seul aurait fermé `/artefacts-de-la-marque` en production — une page publique sans rapport, cassée
sans que rien ne l'explique.

### Rien à faire pour le « non indexable »

Les vhosts preprod portent **déjà** `X-Robots-Tag: noindex, nofollow`, celui du public compris. En
production les routes n'existent pas. L'exigence est acquise sans une ligne de configuration.

### Les identifiants de la doc, pas les leurs

`DOCS_BASIC_AUTH_USER` / `DOCS_BASIC_AUTH_HASH`, déjà utilisés par `docs.`, `swagger.`, `dev-book.`
et `/v1/docs`. Même public — l'équipe et le client —, un seul secret à faire tourner, aucune
variable nouvelle à déployer.

## Règles et cas limites

- L'ancienne route `/ux-kit` **disparaît** du socle : elle devient `/artefacts/ux-kit` sur le
  public, et n'existe plus sur les trois autres espaces.
- Les sept `template-*` gardent leurs routes actuelles, et **restent livrées en production**. Le
  problème d'origine n'est donc que partiellement réglé — il le sera par leur refonte.
- Aucun rôle applicatif n'entre en jeu — l'espace public n'a pas d'authentification, la protection
  est au proxy.
- Aucune donnée n'est chargée par ces pages : ni état vide, ni erreur, ni chargement à traiter.
- i18n, mobile-first et accessibilité sont écartés ici : ces exigences reviendront avec la refonte,
  où elles ont un sens.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` et `index.md` — la galerie a changé de route.
- `.env.example` — le drapeau `NUXT_PUBLIC_ARTEFACTS`.
- `docker-compose.dev.yml`, `docker-compose.preprod.yml` — variable passée aux conteneurs, sur le
  modèle de `NUXT_PUBLIC_BASE_DOMAIN`.
- `docker/caddy/Caddyfile` — le motif `@artefacts` sur le vhost preprod.
- `docker-and-make.md` **écartée** après lecture : elle ne liste pas les variables une par une.

## Vérification

Gates lancées en une passe sur l'espace public, sorties fraîches :

| Gate | Résultat |
| --- | --- |
| `quality-lint-check` | exit 0 — 16 warnings, tous dans des fichiers non touchés |
| `quality-format-check` | exit 0 — « All matched files use Prettier code style! » |
| `quality-conventions-check` | exit 0 — « 8 règles, aucune violation » |
| `quality-test` | exit 0 — **8 fichiers, 61 tests** |
| `quality-type-check` | exit 0 après correction (voir ci-dessous) |

**Reprise après rebase sur `develop`, à l'ouverture de la PR.** La passe ci-dessus datait d'un
`develop` antérieur à `ENT-027`, qui a *rendu la vue au vérificateur de conventions*. Une fois ce
correctif remonté, la branche est passée **rouge** : 10 violations, toutes dans `ux-kit.vue`.

La cause n'était pas le contenu du fichier mais son chemin — `rules.yaml` exemptait
`base/app/pages/ux-kit.vue`, que cette tâche déplace sans déplacer l'exemption. Comparaison faite
des deux côtés : `develop` rendait « 8 règles, aucune violation », la branche 10 violations. Les
deux lignes suivent désormais le fichier. Gate rejouée, exit 0 :

| Gate rejouée | Résultat |
| --- | --- |
| `quality-lint-check` | 16 warnings, 0 erreur — aucun dans les fichiers de la tâche |
| `quality-format-check` | « All matched files use Prettier code style! » |
| `quality-type-check` | 4 espaces, aucune erreur |
| `quality-conventions-check` | « ✔ 8 règles, aucune violation » |
| `quality-test` | **10 fichiers, 72 tests** — le compte a monté avec les tâches remontées au rebase |

- **Le type-check a d'abord échoué**, et c'est lui qui a fait changer le périmètre :

  ```
  artefacts/entreprise/recherches-uuid.vue(12,18): error TS2304: Cannot find name 'useProfilePanel'
  artefacts/entreprise/recherches-uuid-table.vue(11,18): error TS2304: Cannot find name 'useProfilePanel'
  ```

  L'enquête a révélé cinq composants dans le même cas, invisibles pour lui.

- **Test de la garde**, six cas : le dossier et son contenu reconnus, une route homonyme
  (`/artefacts-de-la-marque`) qui ne l'est pas, la fermeture sans drapeau, l'ouverture avec, et le
  reste du site jamais conditionné.
- **Déplacement** : `git status` rapporte un renommage (`R`) pour `ux-kit.vue` — son historique est
  conservé.
- **Liens internes** : quatre renvois à l'ancienne route `/ux-kit` subsistaient — deux `ULink` dans
  les démos de la galerie elle-même, deux dans la doc du design system. Trouvés par `grep`, corrigés.

**Non vérifié** : le rendu réel de la galerie. Il demande la stack du worktree, qui n'a pas été
démarrée.

## Livré

- `webapp/spaces/public/app/pages/artefacts/ux-kit.vue` (R) — depuis `base/app/pages/`.
- `webapp/shared/utils/artefacts.ts` (+) — la décision, pure et testable sans runtime Nuxt.
- `webapp/shared/utils/artefacts.vitest.ts` (+) — six cas.
- `webapp/spaces/public/app/middleware/artefacts.global.ts` (+) — le 404 quand le drapeau manque.
- `webapp/spaces/public/nuxt.config.ts` (M) — `runtimeConfig.public.artefacts`.
- `docker-compose.dev.yml`, `docker-compose.preprod.yml` (M) — `NUXT_PUBLIC_ARTEFACTS`.
- `docker/caddy/Caddyfile` (M) — `@artefacts` + `basic_auth` sur le vhost preprod.
- `.env.example` (M) — les identifiants de doc servent aussi aux maquettes.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`, `index.md` (M) — route de la galerie.
- `webapp/scripts/ci/rules.yaml` (M) — les deux exemptions de `ux-kit.vue` suivent le fichier à son
  nouveau chemin, et changent d'espace responsable (`# socle-webapp` → `# public`). La dette
  d'ENT-027 est inchangée : même fichier, mêmes violations, seul le chemin bouge.

## Reste à faire (hors périmètre de cette tâche)

- **Les sept `template-*` sont toujours livrées en production.** C'était la moitié du problème
  d'origine, et elle reste entière : leur refonte sur Nuxt UI doit couper leurs dépendances
  d'espace, après quoi elles rejoindront `artefacts/` et tomberont sous la garde.
- **`docker-compose.prod.yml` n'a pas été touché**, et c'est le mécanisme même : c'est l'absence de
  `NUXT_PUBLIC_ARTEFACTS` qui ferme les routes. Un ajout par erreur les ouvrirait en production sans
  qu'aucun test ne le signale.
- **Le `basic_auth` ne couvre pas le dev** : en local les maquettes sont accessibles sans mot de
  passe, par choix.
- **Aucune gate ne détecte un composant non résolu.** C'est ce qui a failli faire livrer sept
  maquettes cassées ; seule une lecture manuelle l'a vu.

## Définition de terminé

- [x] `webapp/spaces/public/app/pages/artefacts/` existe et porte la galerie UX Kit.
- [x] `NUXT_PUBLIC_ARTEFACTS` absent → les routes répondent 404 ; présent → elles rendent.
- [x] Le drapeau est déclaré dans `.env.example` et passé par les deux compose.
- [x] Le vhost preprod protège `/artefacts*` par `basic_auth`.
- [x] Le test de la garde passe, et les cinq gates de l'espace public sont vertes.
- [x] Les sept maquettes d'écrans sont restées intactes dans leur espace.
