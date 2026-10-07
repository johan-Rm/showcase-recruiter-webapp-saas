# TASK 001 — Scaffolding de l'API (Symfony 8 + API Platform 4 + FrankenPHP)

- **Créée le** : 2026-06-16.
- **Statut** : ✅ **réalisée le 2026-06-17** (Symfony 8.1 + API Platform 4.3 + FrankenPHP).
  Critères d'acceptation tous verts : `make api-quality` OK (lint:container, phpstan 5 + baseline,
  cs, deptrac, phpunit), `/v1/health` + `/v1/docs` + OpenAPI `/v1/docs.json` servis en Docker,
  auth (login/refresh) câblée + keypair JWT, `schema:validate` en sync, migration initiale jouée.
  Reste à brancher au modèle de données : entity provider `User`, voters.
- **Périmètre** : initialiser `api/` (skeleton, bundles, tooling, config, auth, structure `src/`).
- **Hors périmètre** : **le modèle de données** (entités showcase-recruiter-webapp-saas) — défini par
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`, implémenté dans une étape ultérieure (cadrage phase 2).
- Recette transposée d'un projet Symfony 8 éprouvé, **adaptée FrankenPHP** + nos conventions.

## 1. Stack & init

- **PHP 8.4** · **Symfony 8.0** · **API Platform 4.3** · **FrankenPHP** (serveur, cf.
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`).
- Init : skeleton Symfony + `composer require api-platform/core`, ou composer create-project.
- IDs : `symfony/uid` → **UUIDv7** pour la colonne publique `uuid` (PK interne = `bigint` ;
  cf. `data-model.md`).

## 2. Bundles (require)

```
api-platform/core ^4.3
doctrine/orm ^3.4  doctrine/dbal ^4.2  doctrine/doctrine-bundle  doctrine/doctrine-migrations-bundle
lexik/jwt-authentication-bundle ^3.1   gesdinet/jwt-refresh-token-bundle ^2.0
nelmio/cors-bundle ^2.5                # CORS sous-domaines candidat./entreprise./admin./apex
symfony/security-bundle  symfony/serializer  symfony/validator  symfony/uid
symfony/messenger  symfony/doctrine-messenger          # sync Jarvi, emails, notifs (async)
symfony/form  symfony/expression-language  symfony/twig-bundle  symfony/asset
vich/uploader-bundle ^2.8              # uploads (CV, logos, cartes stars) → disque VPS
```
Dev : `api-platform/symfony`, `symfony/maker-bundle`, `symfony/http-client`,
`symfony/browser-kit`, `symfony/css-selector`, `symfony/phpunit-bridge`.

## 3. Tooling qualité (require-dev + config)

- **phpstan ^2** + `phpstan-doctrine`, `phpstan-phpunit`, `phpstan-strict-rules`, `phpstan-symfony`
  → `phpstan.neon` (**niveau 5** + `phpstan-baseline.neon`).
- **deptrac ^2** → `deptrac.yaml` + `deptrac-baseline.yaml` (couches du §6).
- **php-cs-fixer** → `.php-cs-fixer.php`.
- **rector ^2** → `rector.php`.
- **infection ^0.33** → `infection.json5` (MSI ratchet, démarrage à 0).
- **phpunit ^12** → `phpunit.xml.dist` (suites `Unit` + `Functional`, `APP_ENV=test`, base `_test`).
- **composer scripts** : `analyse`, `cs:check`/`cs:fix`, `deptrac`, `rector:check`/`fix`,
  `infection`, `test`, **`quality`** (= `lint:container` + `analyse` + `cs:check` + `deptrac` +
  `test`), **`quality:fast`** (sans phpunit). Branchés sur les cibles `make api-quality` /
  `quality-fast` existantes.

## 4. Structure `src/` (couches Deptrac)

Conforme à `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-structure.md` :
```
ApiResource/ (Input/ Output/)  State/ (Provider/ Processor/)  Domain/  Repository/  Entity/
Enum/  Message/  MessageHandler/  Security/  Serialization/  Translation/  Uploader/
Command/  Controller/  Seed/
```
Frontières Deptrac : `Domain` ne dépend jamais de `ApiResource`/`State`/HTTP.

## 5. Config (`config/packages/`)

- `api_platform.yaml` : OpenAPI sur `/v1/docs`, **format JSON plat** (cf. `api-conventions.md` —
  DTO de sortie, relations `uuid`), pagination en enveloppe plate, exposition JSON Schema pour les
  tests.
- `doctrine.yaml` + `doctrine_migrations.yaml` : Postgres, migrations portant **FK / UNIQUE /
  NOT NULL / CHECK** (cf. `data-integrity.md`).
- `security.yaml` : firewalls **`login`** (`json_login` sur `/v1/auth/login`, stateless),
  **`refresh`** (`/v1/auth/refresh`), **`api`** (`jwt`, stateless) ; `access_control` public pour
  `/v1/auth/login|refresh|local-users`, `/v1/docs`, sinon `IS_AUTHENTICATED_FULLY`. **Voters**
  pour l'isolation multi-tenant + rôles (`ROLE_COMPANY_OWNER`/`ROLE_COMPANY_MEMBER`/
  `ROLE_CANDIDATE`/`ROLE_ADMIN_*`).
- `lexik_jwt_authentication.yaml` + `gesdinet_jwt_refresh_token.yaml` + routes
  (`/v1/auth/login`, `/v1/auth/refresh`).
- **`nelmio_cors.yaml`** : origines `https://{candidat,recruteur,admin}.showcase-recruiter-webapp-saas.example` + apex,
  `allow_credentials: true` (cookie BFF).
- `messenger.yaml` (transport doctrine async), `vich_uploader.yaml` (disque VPS),
  `serializer.yaml`, `validator.yaml`, `cache.yaml`, `framework.yaml`.

## 6. Auth (consommée par le BFF Nuxt)

- Endpoints : `POST /v1/auth/login` (→ `{token, refreshToken}`), `POST /v1/auth/refresh`,
  `POST /v1/auth/local-users` (création compte). Génération du **keypair JWT** (`make dev-jwt` /
  `lexik:jwt:generate-keypair --skip-if-exists`).
- Côté webapp : ces endpoints sont appelés par le **BFF `nuxt-auth-utils`** ; le JWT user vit dans
  la session scellée, jamais exposé au client (cf. `architecture.md` §5).

## 7. Docker / Makefile

- **`api/Dockerfile`** (FrankenPHP, extensions : `intl`, `opcache`, `pdo_pgsql`, `pcov` pour la
  couverture) + **`api/Dockerfile.prod`** (multi-stage, **worker mode** FrankenPHP).
- Brancher sur `docker-compose.{dev,preprod,prod}.yml` + les cibles `Makefile` existantes
  (`dev-setup`, `dev-migrate`, `dev-jwt`…). Healthcheck `/v1/health`.

## 8. Conventions à respecter (pointeurs)

- **API** : réponses plates, relations `uuid`, value objects en sous-objet, DTO Input/Output →
  `api-conventions.md`.
- **Contrats** : OpenAPI source → types TS générés + gate anti-dérive → `data-contracts.md`.
- **i18n** : modèle traduisible Sylius-style (`CategoryCode`/éditorial + `*Translation`),
  `?locale` + fallback FR, AR=RTL → `i18n.md`.
- **Tests** : exhaustif par route, JSON Schema (généré), isolation multi-tenant, mass-assignment,
  idempotence crédits, query-count → `api-testing.md`.

## Critères d'acceptation

- [ ] `make dev-up` lance l'api (FrankenPHP) + db ; `/v1/docs` et `/v1/health` répondent.
- [ ] `make api-quality` passe (phpstan 5, cs, deptrac, phpunit) ; baselines en place.
- [ ] Auth : `login` + `refresh` fonctionnels ; keypair JWT généré ; CORS OK depuis un sous-domaine.
- [ ] `doctrine:schema:validate` OK ; migrations jouables.
- [ ] Structure `src/` + frontières Deptrac conformes à `api-structure.md`.

> **Le modèle de données (entités, migrations métier) n'est PAS dans cette tâche** — il suit, guidé
> par `data-model.md` + `data-integrity.md`, après la **phase 2 de cadrage**.
