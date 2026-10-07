---
title: "Routes Language"
description: "Référentiel des langues (code ISO 639-1). Lecture publique, non traduisible."
---


# Routes Language
Référentiel des langues (code ISO 639-1). Lecture publique, non traduisible.
## GET /v1/languages

**Resume** : Retrieves a Language resource.
Retrieves a Language resource.

- Operation ID : `api_languages_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Language resource | [`Language.CollectionOutput`](/schemas#language-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
