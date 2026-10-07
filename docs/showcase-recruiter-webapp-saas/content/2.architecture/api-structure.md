# showcase-recruiter-webapp-saas — Structure interne de l'API (`api/src/`)

> Organisation du code Symfony 8 + API Platform. Met en œuvre les décisions de
> [api-conventions.md](./api-conventions.md) (réponses plates, DTO), [data-model.md](./data-model.md),
> [data-integrity.md](./data-integrity.md) et [i18n.md](./i18n.md). Frontières vérifiées par
> **Deptrac** (cf. [testing-and-ci.md](../3.quality/testing-and-ci.md)).

## Couches (`src/`)

| Dossier | Rôle |
| --- | --- |
| `Entity/` | Entités Doctrine (persistance) — `id` bigint + `uuid`, FK, contraintes, soft delete |
| `Enum/` | Enums PHP backed (états/petits sets fixes : availability, returnHorizon, visibility, organizationRole, type transaction, niveau langue…) — source des enums OpenAPI |
| `Repository/` | Repositories Doctrine (requêtes, recherche plein texte du vivier) |
| `ApiResource/Input/` | DTO d'entrée (payloads validés) |
| `ApiResource/Output/` | DTO de sortie porteurs des attributs API Platform (`#[ApiResource]`, opérations, `uriTemplate`) : réponses plates, relations `uuid`, value objects en sous-objet |
| `State/Provider/` | Lecture (GET) : Repository → Entity → **Output DTO** |
| `State/Processor/` | Écriture (POST/PUT/PATCH/DELETE) : **Input DTO** → Domain → Entity |
| `Domain/` | Logique métier (cœur sensible, testable) — voir ci-dessous |
| `Security/` | Voters (isolation multi-tenant), authentification JWT |
| `Translation/` | Résolution de locale + fallback (pattern i18n) |
| `Serializer/` | Normalizers de sortie (mise en forme fine d'un Output DTO) |
| `OpenApi/` | Décorateurs de la spec (documentation des endpoints d'auth gérés hors API Platform) |
| `Controller/` | Contrôleurs Symfony hors API Platform (`/v1/health`) |
| `Command/` | Commandes CLI (ingestion et réconciliation Jarvi, seed des comptes d'équipe) |

## Flux d'une requête

```
HTTP → API Platform → ApiResource (Output DTO)
  GET  : State Provider  → Repository → Entity → Output DTO (plat) → JSON
  POST : Input DTO → Validator → State Processor → Domain (invariants) → Entity → persist → Output DTO
  CLI  : Command → Domain (ingestion Jarvi, réconciliation)
```

## Couche Domain — pragmatique

- `Domain/` = **services métier framework-agnostic + value objects**. Les **entités restent
  Doctrine** (pas de double modèle ni de mapping domaine↔persistance — choix MVP).
- Y vit le **cœur sensible** (test-first) :
  - `Domain/Credit/` — wallet, transaction, **solde ≥ 0**, et déblocage profil **atomique**
    (débit + `RevealedProfile` + wallet dans la même transaction)
  - `Domain/Jarvi/` — client Jarvi, ingestion descendante, création initiale de profil si absent,
    réconciliation de périmètre, synchro company
  - `Domain/Auth/` — magic link, définition et réinitialisation de mot de passe, invalidation des
    refresh tokens
  - `Domain/Candidate/` — onboarding, politique d'exposition, lecture d'activité
  - `Domain/Organization/` — création d'entreprise, invitations, membres, assignations
- Les règles transverses partagées par plusieurs Providers/Processors vivent aussi dans `Domain/`
  sous forme de petits services explicites, par exemple résolution du recruteur courant actif ou
  politique d'exposition d'un candidat. Elles ne traduisent pas d'erreur HTTP : la couche `State/`
  reste responsable des `404`/`403`/`422`.
- Règle : `Domain/` ne dépend **jamais** de `ApiResource/`, `State/`, ni du framework HTTP. Il peut
  utiliser `Entity/` et les repositories (via interface quand utile).

## Pattern State (API Platform)

- **Provider** (lecture) : récupère l'entité (repo), **résout la locale** (`?locale`/`Accept-Language`)
  + fallback, et **mappe vers l'Output DTO plat** (uuid, champs localisés résolus, métadonnées
  `requestedLocale`/`locale`/`fallback`).
- **Processor** (écriture) : reçoit l'**Input DTO** (déjà validé), appelle le **service Domain**
  (qui porte les invariants), persiste l'entité, retourne l'**Output DTO**. Les opérations
  multi-tables (reveal) sont **transactionnelles**.

## DTO (entrée / sortie)

- **Output DTO** : forme exacte de la réponse (cf. api-conventions) — relations en `uuid`
  (`organizationId`), value objects en sous-objet (`salaryExpected: { value, currency }`), pas
  d'entité imbriquée. Le **JSON Schema** des tests dérive de ces DTO.
- **Input DTO** : champs autorisés en écriture uniquement → **protège du mass-assignment** (les
  champs comme `balance`, `roles`, `organizationRole`, `jarviProfileId` n'existent pas dans l'Input).

## Sécurité & isolation

- **Voters** (`Security/`) : appliquent l'**isolation multi-tenant** (un recruteur n'accède qu'aux
  données de sa `Organization`) et les rôles (`ROLE_COMPANY_OWNER`/`ROLE_COMPANY_MEMBER`/
  `ROLE_ADMIN`). Invoqués par les State Providers/Processors et les opérations API Platform.

## Frontières Deptrac (dépendances autorisées)

```
ApiResource  → State, Enum
State        → Domain, Repository, Entity, ApiResource, Enum, Translation, Security
Domain       → Entity, Repository, Message, Enum
Repository   → Entity, Enum
Security     → Domain, Entity, Enum
Command      → Domain, Enum
Controller   → ApiResource, State, Domain
Entity       → Enum
```

Interdits notables : `Domain → ApiResource/State/Controller` ; `Entity → Domain` ; toute
dépendance vers le framework HTTP depuis `Domain`.

## Conventions de placement

- Une ressource API = un ensemble cohérent : Output DTO porteur des opérations (`ApiResource/Output/`)
  + `State/Provider/{Resource}Provider` + `State/Processor/{Resource}Processor` + Input DTO.
- Tests : `tests/Unit/Domain/…` (cœur sensible), `tests/Functional/{Resource}Test.php`
  (cf. [api-testing.md](../3.quality/api-testing.md)).

## Règles structurantes

- **ApiResource = DTO** (≠ Entity) ; State Providers/Processors font le pont.
- **Domain pragmatique** : services framework-agnostic + entités Doctrine (pas d'hexagonal strict).
- **Input DTO** = garde-fou anti mass-assignment ; **Output DTO** = forme plate (= JSON Schema des tests).
- **Voters** pour l'isolation multi-tenant.
- Frontières verrouillées par **Deptrac**.
