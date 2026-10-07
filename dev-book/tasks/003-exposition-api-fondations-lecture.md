# TASK 003 — Exposition API : fondations lecture (référentiels + contexte)

- **Créée le** : 2026-06-17.
- **Statut** : ✅ implémenté (2026-06-18) — exposition lecture + préfixe `/v1` + config env.
  **Caduc en partie (2026-07-13)** : `GET /v1/category-codes` et le référentiel `CategoryCode` ont été
  supprimés (l'ingestion Jarvi ne les alimentait jamais) — secteur et fonction sont des valeurs libres.
- **Périmètre** : première tranche de l'exposition API Platform. **Pose une fois pour toutes** le
  pattern de lecture conforme à [api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md) et
  [api-structure.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-structure.md) : **Output DTO plats** (relations
  `uuid`, value objects en sous-objet), **State Providers** (Entity → Output DTO), **enveloppe de
  pagination plate** (`{ items, total, page, perPage }`), **résolution i18n** (`?locale` + fallback FR
  + métadonnées `requestedLocale`/`locale`/`fallback`). Endpoints **publics / lecture** → pas de
  voters → tranche mergeable rapidement, base des tranches suivantes.
- **Hors périmètre** (tranches 004+) : cœur recruteur (candidats, recherche), entreprise & crédits
  (reveal atomique, **TDD cœur sensible**), écritures (Input DTO + Processors), endpoints admin,
  endpoints `/translations`, GitHub Actions/CI.

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/languages` | 🌐 | codes ISO (`{ items: [{ id, code }], total, page, perPage }`) |
| GET | `/v1/category-codes?inCodeSet=&locale=` | 🌐 | secteurs/fonctions/compétences, **label i18n résolu** |
| GET | `/v1/star-profile-cards?locale=` | 🌐 | cartes éditoriales landing, **i18n résolu** |
| GET | `/v1/config` | 🌐 | valeurs métier (coût dévoilé, crédits offerts, locales, pagination) |
| GET | `/v1/me` | 🔒 | utilisateur courant (person, rôles, contextes candidat/recruteur) |

## Structure (cf. api-structure.md)

- `ApiResource/Output/` : `CollectionOutput` (enveloppe), `LanguageOutput`, `CategoryCodeOutput`,
  `StarProfileCardOutput`, `ConfigOutput`, `MeOutput`. Le `#[ApiResource]` porte les opérations
  (uriTemplate + `provider` + `output`).
- `State/Provider/` : un provider par ressource ; Entity (repo) → résolution locale → Output DTO plat.
- `Translation/LocaleResolver` : locales activées (FR/AR/EN), source = FR, résolution `?locale`.
- Frontières Deptrac respectées : `ApiResource → State`, `State → Entity/Repository/ApiResource/
  Translation/Enum`, `Translation → Entity/Enum`.

## Conventions appliquées

- Réponses **plates**, relations en **uuid scalaire**, enums en **string**, dates ISO 8601,
  `deletedAt` non exposé.
- i18n : champ localisé **résolu et aplati** (`label`, `headline`…) + `requestedLocale`/`locale`/
  `fallback`. Écriture i18n = tranche ultérieure.
- Routes publiques ajoutées à `security.yaml` (`access_control`) ; `/v1/me` derrière JWT.

## Tests

- `tests/Functional/` : un test par endpoint (statut, `Content-Type: application/json`, forme JSON).
- Base de test `showcase-recruiter-webapp-saas_test` : Foundry `ResetDatabase` (création + schéma via migrations) +
  chargement `AppFixtures` en `setUp`.

## Critères d'acceptation

- [ ] Les 5 endpoints répondent ; `/v1/docs` (Swagger) les expose.
- [ ] `category-codes` : `?locale=ar` renvoie le label arabe ; locale absente → fallback FR
      (`fallback: true`).
- [ ] `/v1/me` : 401 sans token, 200 avec un `User` des fixtures.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
