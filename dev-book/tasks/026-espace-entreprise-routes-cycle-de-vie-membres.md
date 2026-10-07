# TASK 026 — Espace Entreprise : tests & validation des routes (cycle de vie & membres)

- **Créée le** : 2026-06-29.
- **Statut** : ⏳ **validation** — **dédiée uniquement aux tests & à la validation**. Le dev restant
  est porté par les sous-tâches **026a** (file de revue admin) et **026b** (changement de rôle membre) ;
  les autres routes sont déjà codées (tâches 004 / 009 / 023).
- **Périmètre** : valider **de bout en bout** l'ensemble des routes du **cycle de vie d'une entreprise
  et de son équipe** — création, lecture, modification, validation, rejet, invitation / acceptation /
  liste / retrait de membres, **changement de rôle**, **file de revue admin** — conformes à la
  **matrice de permissions** (025a) et au cadrage
  [inscription.md](../cadrage/entreprise/api/inscription.md) (§ Synchronisation Jarvi).
- **Hors périmètre** : tout dev (routes existantes ; gaps → 026a/026b ; renommage → 025a) ; crédits /
  dévoilement (005), recherche (006/014), favoris (008), billing Stripe, détail sync Jarvi (023),
  doc officielle `docs/` (→ 027), front (webapp).

## Dépendances

- **Après 025** (rôles validés) : les routes doivent être gardées par `ROLE_COMPANY_OWNER` /
  `ROLE_COMPANY_MEMBER` / `ROLE_ADMIN_*`.
- **Sous-tâches dev à livrer avant validation complète** : **026a** (`GET /admin/organizations?status=`),
  **026b** (`PATCH .../members/{recruiterId}`).

## Périmètre de validation (routes)

| Méthode | Route | Rôle | Origine |
| --- | --- | --- | --- |
| POST | `/v1/organizations` | déclarant (Magic Link) → `COMPANY_OWNER` | ✅ 004 |
| GET | `/v1/organizations/{id}` | membre / admin | ✅ 004 |
| PUT | `/v1/organizations/{id}` | `COMPANY_OWNER` | ✅ 004 |
| POST | `/v1/organizations/{id}/validate` | `ROLE_ADMIN_*` | ✅ 023 |
| POST | `/v1/organizations/{id}/reject` | `ROLE_ADMIN_*` | ✅ 023 |
| GET | `/v1/organizations/{id}/members` | membre / admin | ✅ 004 |
| POST | `/v1/organizations/{organizationId}/invitations` | `COMPANY_OWNER` | ✅ 009 |
| GET | `/v1/organizations/{organizationId}/invitations` | `COMPANY_OWNER` | ✅ 009 |
| POST | `/v1/invitations/{token}/accept` | 🌐 public (token) → `COMPANY_MEMBER` | ✅ 009 |
| DELETE | `/v1/organizations/{id}/members/{recruiterId}` | `COMPANY_OWNER` | ✅ 009 |
| GET | `/v1/admin/organizations?status=EN_REVUE` | `ROLE_ADMIN_*` | 🆕 **026a** (namespace `/admin/*`) |
| PATCH | `/v1/organizations/{id}/members/{recruiterId}` | `COMPANY_OWNER` | 🆕 **026b** |

## Règles à vérifier (cf. data-model.md & cadrage)

- **Création** : `COMPANY_OWNER` unique = déclarant ; `1 Recruiter = 1 Organization` ; atomique ;
  entreprise en `EN_REVUE`.
- **Modification (PUT)** : `COMPANY_OWNER` only ; identité (`name`/`legalName`) **modifiable librement**
  même si `VALIDEE` — **aucune re-validation, aucun re-push Jarvi** (push unique à la validation).
- **Validation/rejet** : `ROLE_ADMIN_*` ; validation → `VALIDEE` + **push Jarvi unique** (flag) + lien
  password-setup au `COMPANY_OWNER` ; rejet → `REFUSE`, aucun appel Jarvi.
- **Membres** : inviter / retirer / changer de rôle = `COMPANY_OWNER` ; **interdit** de retirer ou
  rétrograder le **dernier owner** ; retrait = désactivation (audit préservé).
- **Revue admin** : `ROLE_ADMIN_*` en **lecture seule** ; aucune action métier entreprise.

## Plan de tests (tests/Functional)

- Cycle complet : création `EN_REVUE` → admin valide → `COMPANY_OWNER` reçoit password-setup → login.
- PUT identité sur entreprise validée → **200** (modifiable sans re-validation) ; PUT champ non-identité → **200**.
- Invitation / accept / retrait : `COMPANY_OWNER` ok ; `COMPANY_MEMBER` → 403.
- PATCH rôle (026b) : promotion ok ; rétrograder le dernier owner → refus.
- GET revue admin (026a) : `ADMIN_MEMBER` liste ok ; `COMPANY_OWNER` → 403.
- Isolation multi-tenant : un membre d'une autre entreprise → 403 sur chaque route.

## Critères de validation

- [ ] Toutes les routes du tableau répondent et sont gardées par les **nouveaux** rôles.
- [ ] Gaps 026a / 026b livrés et couverts par des tests (garde-fous inclus).
- [ ] Identité modifiable sans re-validation ; **push Jarvi unique à la validation**, aucune synchro ensuite.
- [ ] Isolation multi-tenant + admin lecture seule vérifiées (403 attendus).
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).

## Références

- Cadrage : [vue_d_ensemble.md](../cadrage/entreprise/vue_d_ensemble.md) · [inscription.md](../cadrage/entreprise/api/inscription.md).
- Existant : [OrganizationOutput](../../api/src/ApiResource/Output/OrganizationOutput.php) · [OrganizationProcessor](../../api/src/State/Processor/OrganizationProcessor.php) · [OrganizationValidationProcessor](../../api/src/State/Processor/OrganizationValidationProcessor.php).
- Sous-tâches : **026a**, **026b**. Liées : **025/a/b** (rôles & auth) · **027** (doc) · 004 · 009 · 023.
