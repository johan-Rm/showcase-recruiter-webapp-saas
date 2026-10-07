# TASK 004 — Espace Entreprise : onboarding Organization (écriture + voter)

- **Créée le** : 2026-06-18.
- **Statut** : ✅ implémenté (2026-06-18) — création Organization + voter multi-tenant + pattern écriture.
  Révisé par `023` : une Organization créée passe en `EN_REVUE` et doit être validée par admin pour
  accéder aux fonctions sensibles. Révisé par `024` : après validation, le déclarant reçoit un lien
  de définition de mot de passe avant d'utiliser l'authentification classique.
- **Périmètre** : première tranche de l'**Espace Entreprise** (phase **P2** de la ROADMAP).
  **Pose le pattern ÉCRITURE** (Input DTO → State Processor → service Domain → entités) et le
  **premier voter multi-tenant**. Complète le pattern lecture posé en tâche 003.
- **Hors périmètre** (tranches suivantes) : crédits/dévoilement (cœur sensible TDD), recherche &
  lecture candidats, favoris, notes recruteurs V2, invitations/acceptation, lookup `by-owner-email`,
  admin.

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| POST | `/v1/organizations` | 🔒 déclarant authentifié par Magic Link | crée l'`Organization` en `EN_REVUE` ; le créateur devient **ROLE_COMPANY_OWNER** ; wallet + **2 crédits offerts** |
| GET | `/v1/organizations/{uuid}` | 🧑‍💼 membre | fiche entreprise (Output plat) |
| PUT | `/v1/organizations/{uuid}` | `ROLE_COMPANY_OWNER` | maj fiche (champs autorisés via Input DTO) |
| GET | `/v1/organizations/{uuid}/members` | 🧑‍💼 membre | recruteurs de l'entreprise |

## Règles métier (cf. data-model.md)

- **1 `Recruiter` = 1 `Organization`** en V1 : un `Person` déjà recruteur **ne peut pas** créer une
  seconde entreprise → 409/422.
- À la création (atomique, transactionnelle) : `Organization` + `Recruiter`(`ROLE_COMPANY_OWNER`) lié au `Person`
  courant + `CreditWallet`(balance = crédits offerts) + `CreditTransaction`(`GRANT_FREE`, +N,
  `balanceAfter`=N). Le `User` reçoit `ROLE_COMPANY_OWNER`.
- **Crédits offerts** : centralisés dans `Domain\Credit\CreditPolicy::WELCOME_CREDITS`.
- **Validation** : la lecture de la fiche reste possible pour l'admin entreprise pendant la revue, mais crédits,
  dévoilements, favoris et notes exigent `OrganizationVoter::ACCESS`, donc une `Organization` validée.
- **Mot de passe** : le créateur peut créer l'entreprise avec le JWT issu du Magic Link. Après
  validation admin, il reçoit un lien à usage unique pour définir son mot de passe, puis utilise
  `POST /v1/auth/login` pour les actions recruteur suivantes.

## Structure (cf. api-structure.md)

- `ApiResource/Input/OrganizationInput` (anti mass-assignment : pas de `balance`, `stripeCustomerId`…).
- `ApiResource/Output/OrganizationOutput`, `RecruiterOutput`.
- `State/Processor/OrganizationProcessor` (POST/PUT) → `Domain\Organization\OrganizationCreator` / mise à jour.
- `State/Provider/OrganizationItemProvider`, `OrganizationMembersProvider`.
- `Domain/Organization/OrganizationCreator` (invariants + transaction) ; `Domain/Credit/CreditPolicy`.
- `Security/OrganizationVoter` : `VIEW` = membre de l'`Organization` **ou** admin ; `MANAGE` (PUT) = `ROLE_COMPANY_OWNER`
  **ou** admin ; `ACCESS` = membre d'une Organization `VALIDEE` **ou** admin pour les fonctions sensibles.
  **Pas de court-circuit global** (l'accès cross-tenant admin reste explicite).

## Sécurité / isolation

- Multi-tenant : un recruteur n'accède qu'à **sa** Organization (voter sur `organization.uuid` ↔ recruteur
  du `Person` courant). `ROLE_ADMIN_*` autorisé explicitement.

## Tests (tests/Functional)

- Création par un user sans entreprise → 201, devient `ROLE_COMPANY_OWNER`, wallet à 2, +1 transaction GRANT_FREE.
- Création par un user **déjà** recruteur → refus (409/422).
- Lecture/édition par un **non-membre** → 403 (isolation). Édition par un `ROLE_COMPANY_MEMBER` → 403 (`ROLE_COMPANY_OWNER` only).
- Fixture ajoutée : un `User` **sans** entreprise (`nadia.cherkaoui@entreprise-abc.test`).

## Critères d'acceptation

- [ ] Les 4 endpoints répondent ; voter multi-tenant effectif (403 cross-tenant).
- [ ] Création atomique (Organization+admin entreprise+wallet+grant) ; `balance >= 0` (CHECK) respecté.
- [ ] Input DTO protège du mass-assignment.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
