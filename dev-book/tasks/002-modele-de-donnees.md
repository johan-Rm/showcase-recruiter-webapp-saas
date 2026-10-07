# TASK 002 — Modèle de données V1 (entités, intégrité BDD, fixtures, auth)

- **Créée le** : 2026-06-17.
- **Statut** : ✅ implémenté (2026-06-18) — entités, intégrité BDD, fixtures, auth. _Cluster Candidate
  révisé par `013` (projection)._
  **Révisé le 2026-07-13** : le référentiel `CategoryCode` (+ `CategoryCodeTranslation`, `CodeSet`,
  `CategoryCodeStatus`) est **supprimé** — l'ingestion Jarvi ne l'alimentait jamais. Secteur et
  fonction sont des **valeurs libres** ; les listes se déduisent du vivier (facettes). Le **soft
  delete**, lui, devient effectif (filtre Doctrine global) au lieu d'une simple colonne.
- **Périmètre** : implémenter le **modèle de données V1 complet** défini par
  [`docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) — entités Doctrine,
  enums, traductions, **migration unique avec intégrité garantie en base** (FK / UNIQUE / NOT NULL /
  CHECK), **fixtures** de démo, et **câblage de l'auth réelle** (`User` → entity provider, le login
  cesse d'utiliser le provider mémoire). Suite directe de la tâche 001 (scaffolding).
- **Hors périmètre** (→ tâche 003) : l'**exposition API Platform** des entités (DTO Input/Output
  plats, relations `uuid`, providers/processors), les **voters** fins et les **tests par route**
  — en particulier le **cœur sensible crédits** (idempotence, solde jamais négatif) qui exige du
  **TDD** avant toute exposition. Voir [data-integrity.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md).

## 1. Conventions transverses (cf. data-model.md § « Conventions communes »)

- **Identifiants hybrides** : chaque entité porte une **PK `id` `bigint` auto-incrément** (interne,
  jamais exposée) **et** un **`uuid`** (UUIDv7 via `symfony/uid`, unique + indexé, public, sert
  d'`externalId` Jarvi). Les FK référencent l'`id` interne. → trait `IdentifiableTrait`.
- **Timestamps + soft delete** : `createdAt`, `updatedAt`, `deletedAt` (nullable). → trait
  `TimestampableTrait` (lifecycle `PrePersist`/`PreUpdate`). Filtre soft-delete SQL : noté, activable
  ultérieurement (ne pas bloquer la V1).
- **Valeurs de référence** : vocabulaires métier (secteurs, fonctions, compétences) → `CategoryCode`
  (+ `CategoryCodeTranslation`). États techniques / petits sets fixes → **enums PHP natifs** (string
  backed) sous `App\Enum`.
- **Montants** : value object `MonetaryAmount` (`amount` int en centimes + `currency` string) en
  `#[ORM\Embeddable]`. **Dates de présence** = `date` (pas datetime).

## 2. Enums PHP (`src/Enum/`)

`UserStatus(INVITED,ACTIVE,DISABLED)` · `OriginLocale(FR,AR,EN)` ·
`ReturnHorizon(M3,M6,Y1,THINKING)` · `Availability(IMMEDIATE,NOTICE_1M,NOTICE_3M)` ·
`ProfileVisibility(VISIBLE,HIDDEN,RESTRICTED,PREMIUM_ONLY)` · `SyncStatus(PENDING,SYNCED,FAILED)` ·
`LanguageLevel(A1,A2,B1,B2,C1,C2,NATIVE)` ·
`OrganizationRole(ROLE_COMPANY_OWNER,ROLE_COMPANY_MEMBER)` ·
`RecruiterStatus(INVITED,ACTIVE,DISABLED)` ·
`CreditTransactionType(GRANT_FREE,PURCHASE,CONSUMPTION,REFUND,ADJUSTMENT)` ·
`InvitationStatus(PENDING,ACCEPTED,EXPIRED)` ·
`SubscriptionStatus(ACTIVE,PAST_DUE,CANCELED)` · `CategoryCodeStatus(ACTIVE,INACTIVE)` ·
`NotificationType(...)` · `NotificationChannel(IN_APP,EMAIL)` ·
`CandidateOptionType(BADGE_PREMIUM,HOMEPAGE_FEATURE,SEARCH_BOOST,RECRUITER_SELECTION)` ·
`CandidateOptionStatus(ACTIVE,EXPIRED,CANCELED)`.

## 3. Entités (`src/Entity/`)

Regroupées par cluster (cf. data-model.md) :

- **Identité & comptes** : `User` (implémente `UserInterface`+`PasswordAuthenticatedUserInterface`,
  1-1 `Person`, `roles[]` json, `status`), `Person` (🌐 Person, identité partagée, push Jarvi).
- **Candidat** : `Candidate` (1-1 `Person`), `WorkExperience`, `Education`, `PresencePeriod` ★,
  liaison `candidate_language` (entité `CandidateLanguage` : `language` + `level`), N-N compétences
  → `CategoryCode`.
- **Entreprise** : `Organization` (🌐 Organization), `Recruiter` (1-1 `Person`, FK `Organization`,
  `organizationRole`), `CreditWallet` (1-1 `Organization`, cache solde), `CreditTransaction` (journal, source
  de vérité du solde), `RevealedProfile` (unique `(organization,candidate)`), `Favorite` (unique
  `(organization,candidate)`), `ProfileNote` (V2), `Invitation`, `Subscription`.
- **Référentiels** : `CategoryCode` (+ `CategoryCodeTranslation`, unicité `(categoryCode,locale)`),
  `Language` (code ISO, unique).
- **Notifications & activité** : `Notification` (recipient = `Person`), `ProfileView`,
  `SavedSearch` (structure V1, alertes V2).
- **Premium & éditorial** : `CandidateOption`, `StarProfileCard` (+ `StarProfileCardTranslation`).

## 4. Intégrité en base (migration — cf. data-integrity.md)

- **FK** explicites (`onDelete` adapté) ; **UNIQUE** : `user.email`, `person.uuid` (+ tous les
  `uuid`), `(organization_id, candidate_id)` sur `revealed_profile` et `favorite`,
  `(category_code_id, locale)` sur les traductions, `(in_code_set, code_value)` sur `category_code`,
  `language.code`, `(candidate_id, language_id)`.
- **NOT NULL** sur les colonnes obligatoires ; **CHECK** : `credit_wallet.balance >= 0`,
  `credit_transaction.amount <> 0`.
- Migration **unique** régénérée (`make:migration`) après écriture des entités, **relue à la main**
  pour ajouter les `CHECK` que Doctrine ne déduit pas.

## 5. Auth réelle

- `App\Entity\User` + `App\Repository\UserRepository` ; `security.yaml` : remplacer
  `app_user_provider: { memory: null }` par
  `{ entity: { class: App\Entity\User, property: email } }`.
- Le login `POST /v1/auth/login` authentifie un vrai `User` (hash de mot de passe `auto`).

## 6. Fixtures (`src/DataFixtures/`, Foundry)

- **Référentiels seedés** : `Language` (`fr`, `ar`, `en`) ; `CategoryCode` minimal par `inCodeSet`
  (`sector`, `job_function`, `skill`) + traductions FR/AR/EN.
- **Démo** : 1 `Organization` + son `CreditWallet` + 1 `CreditTransaction` `GRANT_FREE` +2 (2 crédits
  offerts à la création, cf. data-model.md) ; 1 `User`/`Person`/`Recruiter`
  `ROLE_COMPANY_OWNER` ; quelques
  `Candidate` de démo (avec `Person`, expériences, présence Maroc, langues).
- Chargement : `make dev-api CMD="doctrine:fixtures:load --no-interaction"`.

## Critères d'acceptation

- [ ] `make dev-migrate` joue la migration ; `doctrine:schema:validate` **en sync**.
- [ ] Contraintes d'intégrité présentes **en base** (FK/UNIQUE/NOT NULL/CHECK ; `balance >= 0`).
- [ ] Fixtures chargées sans erreur (référentiels + Organization + admin entreprise + candidats).
- [ ] Login `POST /v1/auth/login` fonctionne avec un `User` issu des fixtures (provider entity).
- [ ] `make api-quality` vert (lint:container, phpstan, cs, deptrac, phpunit).

> **L'exposition API Platform (Swagger qui se remplit), les voters fins et les tests du cœur
> crédits sont la tâche 003** — séparés pour garder une PR relisible et respecter le TDD sur le
> cœur sensible.
