---
title: "Routes Organization"
description: "Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_ADMIN),\nlecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md."
---


# Routes Organization
Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_ADMIN),
lecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md.
## POST /v1/organizations

**Resume** : Creates a Organization resource.
Creates a Organization resource.

- Operation ID : `api_organizations_post`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

The new Organization resource
- Requis : oui
- Content-Type : `application/json`
- Schema : [`Organization.OrganizationInput`](/schemas#organization-organizationinput)

### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `201` | Organization resource created | [`Organization`](/schemas#organization) |
| `400` | Invalid input | [`Error`](/schemas#error) |
| `422` | An error occurred | [`ConstraintViolation`](/schemas#constraintviolation) |

## GET /v1/organizations/{id}

**Resume** : Retrieves a Organization resource.
Retrieves a Organization resource.

- Operation ID : `api_organizations_id_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `id` | path | oui | string | Organization identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Organization resource | [`Organization`](/schemas#organization) |
| `404` | Not found | [`Error`](/schemas#error) |

## PUT /v1/organizations/{id}

**Resume** : Replaces the Organization resource.
Replaces the Organization resource.

- Operation ID : `api_organizations_id_put`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `id` | path | oui | string | Organization identifier |

### Body

The updated Organization resource
- Requis : oui
- Content-Type : `application/json`
- Schema : [`Organization.OrganizationInput`](/schemas#organization-organizationinput)

### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Organization resource updated | [`Organization`](/schemas#organization) |
| `400` | Invalid input | [`Error`](/schemas#error) |
| `404` | Not found | [`Error`](/schemas#error) |
| `422` | An error occurred | [`ConstraintViolation`](/schemas#constraintviolation) |

## GET /v1/organizations/{id}/members

**Resume** : Retrieves a Organization resource.
Retrieves a Organization resource.

- Operation ID : `api_organizations_idmembers_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `id` | path | oui | string | Organization identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Organization resource | [`Organization.CollectionOutput`](/schemas#organization-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
