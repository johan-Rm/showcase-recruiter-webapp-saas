---
title: "Routes StarProfileCard"
description: "Cartes « profils stars » de la landing (éditorial traduisible). Lecture publique, actives\nuniquement, triées par displayOrder, avec champs i18n résolus (?locale, fallback FR)."
---


# Routes StarProfileCard
Cartes « profils stars » de la landing (éditorial traduisible). Lecture publique, actives
uniquement, triées par displayOrder, avec champs i18n résolus (?locale, fallback FR).
## GET /v1/star-profile-cards

**Resume** : Retrieves a StarProfileCard resource.
Retrieves a StarProfileCard resource.

- Operation ID : `api_star-profile-cards_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | StarProfileCard resource | [`StarProfileCard.CollectionOutput`](/schemas#starprofilecard-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
