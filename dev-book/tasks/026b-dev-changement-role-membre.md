# TASK 026b — DEV : changement de rôle d'un membre

- **Créée le** : 2026-06-29.
- **Statut** : ⏳ **à faire** — sous-tâche de dev de **026** (validation). *À faire après 025a* (rôles).
- **Périmètre** : livrer la route qui permet à un `COMPANY_OWNER` de **changer le rôle** d'un membre
  de son entreprise (promotion `MEMBER → OWNER` / rétrogradation), avec garde-fous.
- **Hors périmètre** : invitation / acceptation / retrait (déjà livrés, tâche 009) ; **transfert
  d'ownership complet** (V2) ; tests & validation de bout en bout (→ **026**).

## Route à livrer

| Méthode | Route | Rôle | Payload |
| --- | --- | --- | --- |
| PATCH | `/v1/organizations/{id}/members/{recruiterId}` | `COMPANY_OWNER` | `{role}` (`ROLE_COMPANY_OWNER` \| `ROLE_COMPANY_MEMBER`) |

## Règles

- Réservé au `COMPANY_OWNER` (`OrganizationVoter::MANAGE`) ; cible = un membre de **sa** `Organization`.
- **Garde « ≥ 1 owner »** : interdit de rétrograder le **dernier `COMPANY_OWNER`** (sinon 409/422).
- Promotion `MEMBER → OWNER` autorisée ; plusieurs owners possibles. (Transfert exclusif = V2.)
- Met à jour `Recruiter.organizationRole` **et** les rôles du `User` associé (cohérence avec 025a).

## Structure (cf. api-structure.md)

- `ApiResource/Input/MemberRoleUpdateInput`.
- `State/Processor/MemberRoleProcessor`.
- `Domain/Organization/MemberRoleChanger` (garde « ≥ 1 owner ») + exception dédiée.
- Réutiliser `RecruiterOutput`, `OrganizationVoter` (aligné **025a**).

## Définition de terminé

- [ ] Route livrée, gardée `COMPANY_OWNER`, isolée multi-tenant.
- [ ] Garde « ≥ 1 owner » effective (rétrograder le dernier owner → refus).
- [ ] `Recruiter.organizationRole` + `User.roles` cohérents après changement.
- [ ] `make api-quality` vert (validation fonctionnelle = 026).

## Références

- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) §Recruiter · tâche 009 (membres).
- Parent : **026** (validation). Dépend de **025a** (rôles). Sœur : **026a** (file de revue).
