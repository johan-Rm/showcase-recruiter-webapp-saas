# TASK 026a — DEV : file de revue admin des entreprises

- **Créée le** : 2026-06-29.
- **Statut** : ✅ livré le 2026-07-13 — `GET /v1/admin/organizations` (première route du namespace
  `/admin/*`), gardée `ROLE_ADMIN`, filtrable par `status`, paginée. Tests fonctionnels verts.
- **Périmètre** : livrer la route admin qui **liste les entreprises** filtrables par statut de
  validation, pour traiter la **file de revue** (valider / rejeter).
- **Hors périmètre** : les actions `validate`/`reject` (déjà livrées, tâche 023) ; tests & validation
  de bout en bout (→ **026**) ; toute action métier entreprise côté admin.

## Route à livrer

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/admin/organizations?status=EN_REVUE` | `ROLE_ADMIN_*` | **lecture seule** cross-tenant ; filtrable par `validationStatus` ; paginé |

> Convention **namespace `/admin/*`** (cohérent avec `/admin/users`, `/admin/profile-views`,
> `/admin/credit-transactions` déjà présents dans Insomnia) : séparation nette des routes admin
> cross-tenant et des routes membre.

## Règles

- **Lecture seule** : `ROLE_ADMIN_*` (consultation cross-tenant) ; **aucune** action métier entreprise.
- Filtre `status` = valeur de `OrganizationValidationStatus` (`EN_REVUE`, `VALIDEE`, `REFUSE`,
  `INCOMPLET`) ; sans filtre = comportement à trancher (tout vs `EN_REVUE` par défaut).
- Pagination standard API Platform.

## Structure (cf. api-structure.md)

- `State/Provider/OrganizationReviewProvider` (filtre `validationStatus`, garde accès admin).
- Réutiliser `OrganizationOutput` ; garde via `OrganizationVoter`/role admin (aligné **025a**).

## Définition de terminé

- [x] Route `GET /admin/organizations` livrée, gardée `ROLE_ADMIN` en lecture seule, filtrable + paginée.
- [x] Un `COMPANY_OWNER`/`COMPANY_MEMBER` → 403.
- [ ] `make api-quality` vert (à passer au point de contrôle commit ; validation fonctionnelle = 026).

## Décisions de livraison

- **Sans filtre `status`, la route rend toutes les entreprises** (le point laissé ouvert au cadrage) :
  c'est l'écran admin qui choisit sa file de travail — `EN_REVUE` par défaut — plutôt que la route
  qui la présume et cache le reste.
- **Statut inconnu → 400**, jamais un filtre silencieusement ignoré qui ferait croire à une file vide.
- Tri **du plus ancien au plus récent** : une file de revue se traite dans l'ordre d'attente.
- Pagination maison (`CollectionOutput`, `perPage` par défaut 20, plafonné à 100), conformément à la
  convention des autres collections (`paginationEnabled: false` côté API Platform).

## Références

- [OrganizationValidationProcessor](../../api/src/State/Processor/OrganizationValidationProcessor.php) · [OrganizationOutput](../../api/src/ApiResource/Output/OrganizationOutput.php).
- Parent : **026** (validation). Dépend de **025a** (rôles). Sœur : **026b** (rôle membre).
