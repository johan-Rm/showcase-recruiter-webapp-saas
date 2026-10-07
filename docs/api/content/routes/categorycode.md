---
title: "Routes CategoryCode"
description: "Référentiel des vocabulaires métier (secteurs, fonctions, compétences) distingués par\n`inCodeSet`. Lecture publique avec libellé i18n résolu (?locale, fallback FR).\nFiltre : ?inCodeSet=sector|job_function|skill."
---


# Routes CategoryCode
Référentiel des vocabulaires métier (secteurs, fonctions, compétences) distingués par
`inCodeSet`. Lecture publique avec libellé i18n résolu (?locale, fallback FR).
Filtre : ?inCodeSet=sector|job_function|skill.
## GET /v1/category-codes

**Resume** : Retrieves a CategoryCode resource.
Retrieves a CategoryCode resource.

- Operation ID : `api_category-codes_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | CategoryCode resource | [`CategoryCode.CollectionOutput`](/schemas#categorycode-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
