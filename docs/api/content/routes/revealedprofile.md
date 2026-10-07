---
title: "Routes RevealedProfile"
description: "Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le\ndébit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer."
---


# Routes RevealedProfile
Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le
débit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer.
## GET /v1/organizations/{organizationId}/revealed-profiles

**Resume** : Retrieves a RevealedProfile resource.
Retrieves a RevealedProfile resource.

- Operation ID : `api_organizations_organizationIdrevealed-profiles_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | RevealedProfile identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | RevealedProfile resource | [`RevealedProfile.CollectionOutput`](/schemas#revealedprofile-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |

## POST /v1/organizations/{organizationId}/revealed-profiles

**Resume** : Creates a RevealedProfile resource.
Creates a RevealedProfile resource.

- Operation ID : `api_organizations_organizationIdrevealed-profiles_post`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | RevealedProfile identifier |

### Body

The new RevealedProfile resource
- Requis : oui
- Content-Type : `application/json`
- Schema : [`RevealedProfile.RevealInput`](/schemas#revealedprofile-revealinput)

### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `201` | RevealedProfile resource created | [`RevealedProfile`](/schemas#revealedprofile) |
| `400` | Invalid input | [`Error`](/schemas#error) |
| `422` | An error occurred | [`ConstraintViolation`](/schemas#constraintviolation) |
