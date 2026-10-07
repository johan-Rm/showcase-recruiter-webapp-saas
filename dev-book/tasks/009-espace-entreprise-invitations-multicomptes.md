# TASK 009 — Espace Entreprise : invitations & multi-comptes

- **Créée le** : 2026-06-19.
- **Statut** : 🟢 livrée (2026-06-19).
- **Périmètre** : onboarding de l'équipe d'une entreprise — le `ROLE_COMPANY_OWNER` **invite** des
  collègues, le collègue **accepte** (création de son compte recruteur), le `ROLE_COMPANY_OWNER`
  peut **retirer** un membre.
  Complète l'Espace Entreprise (la lecture des membres existe déjà — tâche 004).
- **Hors périmètre** : droits fins personnalisables (V2), envoi d'email réel (V1 : le `token` est
  retourné / journalisé ; l'email partira via Messenger ultérieurement), invitation **candidat**
  (mécanisme distinct, espace candidat).

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| POST | `/v1/organizations/{id}/invitations` | `ROLE_COMPANY_OWNER` | `{email, role}` → crée une invitation (token) |
| GET | `/v1/organizations/{id}/invitations` | `ROLE_COMPANY_OWNER` | invitations en cours (PENDING/…) |
| POST | `/v1/invitations/{token}/accept` | 🌐 | `{password, givenName, familyName}` → crée le membre |
| DELETE | `/v1/organizations/{id}/members/{recruiterId}` | `ROLE_COMPANY_OWNER` | retire un membre recruteur |

## Règles métier (cf. data-model.md)

- **Inviter** : réservé à `ROLE_COMPANY_OWNER` (`OrganizationVoter::MANAGE`). `Invitation` :
  `organization`, `email`, `role` (`ROLE_COMPANY_MEMBER` par défaut), `token` (unique),
  `status` (`PENDING`/`ACCEPTED`/`EXPIRED`), `invitedBy`, `expiresAt`. Refus si l'email est déjà
  membre / déjà invité (PENDING).
- **Accepter** (public, par token) : token `PENDING` non expiré requis (sinon 404/410). Crée
  `Person` + `User` (`ROLE_COMPANY_MEMBER`, mot de passe hashé) + `Recruiter`(organization, role, `ACTIVE`) ;
  passe l'invitation à `ACCEPTED`. Atomique. Si l'email a déjà un `User` → à cadrer (V1 : refuser).
- **Retirer un membre** : `ROLE_COMPANY_OWNER` uniquement ; **interdit de retirer le dernier admin
  entreprise** / soi-même en tant qu'unique admin entreprise. V1 : retrait d'un
  `ROLE_COMPANY_MEMBER` (désactivation `status=DISABLED` ou suppression
  — choisir : **désactivation** recommandée pour préserver l'audit des actions passées).
- **1 Recruiter = 1 Organization** (V1) : l'accepteur ne doit pas déjà être recruteur ailleurs.

## Structure (cf. api-structure.md)

- `ApiResource/Input/{InvitationInput, AcceptInvitationInput}` ;
  `Output/{InvitationOutput}` (+ réutilise `RecruiterOutput`).
- `State/Processor/{InvitationProcessor, AcceptInvitationProcessor, MemberRemovalProcessor}` ;
  `State/Provider/InvitationsProvider`.
- `Domain/Organization/MemberInviter` (création invitation + garde-fous) et `InvitationAcceptor`
  (création compte membre, atomique) ; exceptions domaine dédiées.
- `Repository/InvitationRepository` (`findOneByToken`, recherche par organization).
- Génération de token sécurisé (aléatoire, non devinable).

## Sécurité

- Invitations & retrait = **ROLE_COMPANY_OWNER** (`OrganizationVoter::MANAGE`). Acceptation = **publique** (token),
  donc route ajoutée à `security.yaml` `access_control` (PUBLIC_ACCESS) — valider le token strict.

## Tests (tests/Functional)

- `ROLE_COMPANY_OWNER` invite → 201 (token) ; `ROLE_COMPANY_MEMBER`/non-membre invite → 403.
- Acceptation token valide → 201, membre créé (login OK ensuite) ; token inconnu/expiré → 404/410.
- Double invitation même email PENDING → refus.
- Retrait membre par `ROLE_COMPANY_OWNER` → ok ; tentative de retrait par un
  `ROLE_COMPANY_MEMBER` → 403 ; retrait du dernier admin entreprise → refus.

## Critères d'acceptation

- [x] Cycle complet invite → accept → membre actif (connectable).
- [x] `ROLE_COMPANY_OWNER` only sur invite/retrait ; acceptation publique sécurisée par token.
- [x] Garde-fous : pas de doublon d'invitation, pas de retrait du dernier admin entreprise, 1 recruteur = 1 entreprise.
- [x] `make api-quality` vert (phpstan, cs, deptrac, phpunit) — 43 tests, 238 assertions.

## Notes d'implémentation

- Retrait de membre = **désactivation** (`RecruiterStatus::DISABLED`), pas de suppression (audit préservé).
- Acceptation : token inconnu → **404**, invitation expirée/déjà acceptée → **410**, email déjà inscrit → **409**.
- Token aléatoire non devinable (`bin2hex(random_bytes(24))`), expiration à **+14 jours**. En V1 le token
  est retourné dans la réponse / journalisé (email réel via Messenger ultérieurement).
- Les opérations `Invitation` (POST/GET invitations, POST accept) vivent sur `InvitationOutput` ; le
  `DELETE members/{recruiterId}` est porté par `OrganizationOutput` (sous-ressource membres).
