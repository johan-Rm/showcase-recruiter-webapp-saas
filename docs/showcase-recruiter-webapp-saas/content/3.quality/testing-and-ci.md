# showcase-recruiter-webapp-saas — Tests, qualité & CI/CD

> Source de vérité pour l'outillage de test et les gates avant merge.

## Principe : TDD ciblé sur le cœur sensible

Pas de TDD dogmatique (cf. socle KISS / YAGNI). **Test-first sur le code qui porte le risque
métier et financier**, tests ciblés *après* sur le reste. Pas de tests sur l'UI pixels ni le
scaffolding.

| Zone                                              | Approche               | Pourquoi                              |
| ------------------------------------------------- | ---------------------- | ------------------------------------- |
| Système de crédits (solde, consommation, idempotence) | **test-first**, unit pur | bug = perte d'argent / litige         |
| Déblocage profil (paywall, accès conservé)        | **test-first**         | cœur monétisable                      |
| ACL multi-comptes / isolation entreprise          | functional (API Platform) | fuite de données entre boîtes         |
| Sync Jarvi (upsert idempotent via `externalId`, retry) | **test-first**     | divergence base ↔ Jarvi               |
| Auth JWT / refresh                                | functional             | tests critiques standards             |
| Règles de visibilité candidat                     | unit pur               | logique métier différenciante         |
| UI, pages, scaffolding                            | tests *après*, ciblés  | faible risque, coût élevé             |

**Pyramide** : beaucoup d'unit rapides sur le Domain → couche functional **exhaustive par route**
de l'API (chaque route × payload × réponse) → webapp vitest **uniquement sur `services/` et
composables** (pas les composants d'affichage).

> Détail de la stratégie de test de l'API (matrice par endpoint, JSON Schema, isolation
> multi-tenant, mass-assignment, idempotence/concurrence, query-count, perf) :
> [api-testing.md](./api-testing.md).

## Outillage par périmètre

### `api/` (Symfony + API Platform)

- **PHPUnit** — suites `Unit` (Domain pur, rapide) + `Functional` (**couverture exhaustive par
  route**, recrée schéma + fixtures par classe, jamais de données insérées à la main) →
  [api-testing.md](./api-testing.md)
- **PHPStan niveau 5** + extensions Symfony/Doctrine/PHPUnit + strict rules, avec
  **baseline** (`phpstan-baseline.neon`) pour la dette existante, **php-cs-fixer**, **Rector**
- **Deptrac** — frontières d'architecture (Domain / ApiResource / Repository / MessageHandler…)
- **Infection** — mutation testing sur le cœur sensible (crédits, sync)
- `composer audit`, `lint:container`, `doctrine:schema:validate`
- Orchestration **Makefile** : `quality-fast` (non destructif) et `quality` (avec PHPUnit)

### `webapp/` (Nuxt)

- **vitest** — logique pure (`services/`, composables), pas les composants pixels
- **ESLint** (flat config, `--max-warnings 0`), **Prettier**, **vue-tsc** (`type-check`)
- **Scripts de conventions** maison (`scripts/ci/verify-conventions.ts` + règles déclaratives
  `scripts/ci/rules.yaml`) : pas de logique métier dans les composants, séparation des
  responsabilités, `services/` sans import framework, etc.
- **Drift de contrats** : `quality-contracts-check` régénère les types TS depuis l'OpenAPI et
  échoue en cas d'écart (cf. [data-contracts.md](../2.architecture/data-contracts.md))
- Script agrégat `quality-check` = `quality-lint-check` + `quality-format-check` +
  `quality-type-check` + `quality-conventions-check` + `quality-test`

## Gates : qui bloque quoi, et quand

Trois moments, alignés sur le socle (« ne pas lancer les suites complètes après chaque modif ») :

| Moment                                   | Ce qui tourne                                                        | Bloquant ? |
| ---------------------------------------- | ------------------------------------------------------------------- | ---------- |
| Pendant le dev (IA / petite correction)  | validations **ciblées** liées au changement uniquement              | non        |
| Branche de travail (push)                | rien en CI ; en local, `make quality-fast` (contrôles rapides non destructifs) | non |
| Pré-merge / PR vers `develop`            | **suite complète** : phpstan + deptrac + cs + phpunit functional + conventions webapp | **oui (gate)** |

La suite complète (PHPUnit functional, Infection) est réservée au **pré-merge** et aux merges
vers `develop`.

## Baselines & seuils (politique ratchet)

- **Baseline** `phpstan-baseline.neon` : documente la dette existante. **Toute nouvelle erreur hors
  baseline bloque** et se traite dans le scope du changement.
- **MSI Infection** (`infection.json5`) : politique **ratchet progressif** — les seuils **ne
  redescendent jamais** et se **remontent au fil des tests** sur le cœur sensible (crédits,
  déblocage, sync Jarvi, ACL).

## Feedback local

Les cibles Make donnent la boucle courte avant de pousser : `make quality-fast` (contrôles rapides
non destructifs), `make quality` (api + webapp), `make test`. La **CI reste l'autorité** : le local
accélère la boucle, il ne remplace pas les gates de PR.

## Conventions de test

- **`api/`** : `tests/Unit` (Domain pur, rapide, sans I/O) et `tests/Functional` (contrats API
  Platform). Les tests fonctionnels **recréent le schéma + chargent les fixtures par classe** ;
  ils ne dépendent **jamais** de données insérées manuellement. `APP_ENV=test`, base dont le nom
  finit par `_test`.
- **`webapp/`** : fichiers `*.vitest.ts` co-localisés avec la logique testée (`services/`,
  composables). Pas de test sur les composants d'affichage purs.

## CI — GitHub Actions filtrée par chemin

Monorepo → jobs indépendants déclenchés selon le périmètre modifié, délégant aux Makefile/scripts
(cohérence local ↔ CI).

```
.github/workflows/
  api-quality.yml      # paths: api/**    → make ci-api-quality (phpstan, cs, deptrac, phpunit) + openapi à jour
  webapp-quality.yml   # paths: webapp/** → lint, format, type-check, conventions, vitest, drift de contrats, build SSR
  commit-check.yml     # commitlint — Conventional Commits
```

Déclenchement : **`pull_request` (vers `develop` / `main`) uniquement** — la qualité et les tests
**ne se déclenchent qu'avant un merge** (gate de pré-merge). Pas de run sur les `push` de branche de
travail : le **feedback pendant le dev** vient des cibles Make en local.

**Checks transverses — chacun couvre `webapp/` ET `api/` :**

| Check | `webapp/` (TS) | `api/` (PHP) | Sévérité |
| --- | --- | --- | --- |
| **commit-check** | repo-global (commitlint, Conventional Commits) | ← idem | bloquant |
| **no-debug** | `console.log`/`warn` interdits (ESLint) ; `console.error` → service de logging | `dump()`/`dd()`/`var_dump()`/`error_log` interdits (php-cs-fixer/phpstan) | bloquant |
| **drift de contrats** | types TS régénérés depuis l'OpenAPI, échec si écart | `api/openapi.json` régénéré, échec si écart | bloquant |

**commit-check** est un contrôle **repo-global** (un seul job) ; **no-debug** et **drift de
contrats** ont un **équivalent par contexte** (dans `webapp-quality.yml` / `api-quality.yml`).

### Règles d'exécution

- **Qualité & tests = pré-merge uniquement** : les workflows `*-quality` se déclenchent sur
  **`pull_request`**, **pas** sur les `push` de branche de travail. Sur une PR, jobs **filtrés par
  chemin** (`api/**`, `webapp/**`) + check **global** (commit-check).
- **Feedback pendant le dev** = cibles Make en local, pas la CI.
- **Pas de checks sur tag** ni lors d'un **déploiement**.
- **Désactivation ponctuelle** : `[skip ci]` dans le message de commit (usage exceptionnel).

## CD — déploiement VPS (Docker + Caddy)

Cibles `Makefile` : `preprod-deploy` / `prod-deploy` = build images → `up` → génération JWT
(skip si existe) → healthcheck HTTP. **Le schéma de base est hors du déploiement** : explicite en
preprod (`preprod-schema` / `preprod-reset`, destructifs), via migrations Doctrine en prod.

**Trois environnements :**

| Env | Source | Déploiement | Base de données |
| --- | --- | --- | --- |
| **dev** | local | `make dev-up` / `make dev-setup` | `make dev-schema` (mapping Doctrine) |
| **preprod** | `develop` sur le VPS | `make preprod-deploy` | hors deploy (`preprod-schema` / `preprod-reset`, explicites) |
| **prod** | **tag `vX.Y.Z`** sur le VPS | `make prod-deploy` | migrations Doctrine (`prod-migrate`, dans le deploy) |

- **Preprod** : miroir jetable de la prod, redéployé à chaque intégration sur `develop` → valider
  avant la prod.
- **Prod** : toujours déployée depuis un **tag** (traçabilité de version).
- Reverse proxy **Caddy** (TLS auto) en frontal, dans son propre projet compose (`make proxy-*`).
  Variables/secrets **séparés par environnement**, dans les `.env` du VPS (jamais commités).

### Garde-fous de déploiement

Un déploiement ne part que d'un état **validé et tracé** :

**Avant `main` (branch protection)**
- `main` / `develop` protégées : **merge via PR uniquement**, pas de push direct.
- **Required status checks** : CI verte obligatoire (quality, tests, Deptrac, drift-gate contrats).

**Au tag**
- Tags `vX.Y.Z` au format **SemVer**, posés sur un commit de `main` dont la CI est verte.

**Au déploiement (`*-deploy`)**
- Séquence : **build → up → JWT (skip-if-exists) → healthcheck** (+ `migrate` en prod).
- **Healthcheck** post-déploiement sur l'API (`/v1/health`, via le proxy frontal).
- **Le déploiement preprod ne touche jamais la base** : la remise à zéro est une opération
  explicite et séparée.
