---
title: "Routes Favorite"
description: "Favori (shortlist partagée de l'entreprise). Ajout idempotent (unicité organization/candidate),\nsuppression par candidateId. `id` non-identifiant : pas d'IRI/Location à générer."
---


# Routes Favorite
Favori (shortlist partagée de l'entreprise). Ajout idempotent (unicité organization/candidate),
suppression par candidateId. `id` non-identifiant : pas d'IRI/Location à générer.
## GET /v1/organizations/{organizationId}/favorites

**Resume** : Retrieves a Favorite resource.
Retrieves a Favorite resource.

- Operation ID : `api_organizations_organizationIdfavorites_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | Favorite identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Favorite resource | [`Favorite.CollectionOutput`](/schemas#favorite-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |

## POST /v1/organizations/{organizationId}/favorites

**Resume** : Creates a Favorite resource.
Creates a Favorite resource.

- Operation ID : `api_organizations_organizationIdfavorites_post`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | Favorite identifier |

### Body

The new Favorite resource
- Requis : oui
- Content-Type : `application/json`
- Schema : [`Favorite.FavoriteInput`](/schemas#favorite-favoriteinput)

### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `201` | Favorite resource created | [`Favorite`](/schemas#favorite) |
| `400` | Invalid input | [`Error`](/schemas#error) |
| `422` | An error occurred | [`ConstraintViolation`](/schemas#constraintviolation) |

## DELETE /v1/organizations/{organizationId}/favorites/{candidateId}

**Resume** : Removes the Favorite resource.
Removes the Favorite resource.

- Operation ID : `api_organizations_organizationIdfavorites_candidateId_delete`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | Favorite identifier |
| `candidateId` | path | oui | string | Favorite identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `204` | Favorite resource deleted | - |
| `404` | Not found | [`Error`](/schemas#error) |
