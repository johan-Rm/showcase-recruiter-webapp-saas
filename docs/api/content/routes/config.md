---
title: "Routes Config"
description: "Valeurs métier publiques consommées par le webapp (coût d'un dévoilé, crédits offerts à la\ncréation, locales activées + locale par défaut, taille de page). Cf. api-routes.md /v1/config."
---


# Routes Config
Valeurs métier publiques consommées par le webapp (coût d'un dévoilé, crédits offerts à la
création, locales activées + locale par défaut, taille de page). Cf. api-routes.md /v1/config.
## GET /v1/config

**Resume** : Retrieves a Config resource.
Retrieves a Config resource.

- Operation ID : `api_config_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Config resource | [`Config`](/schemas#config) |
| `404` | Not found | [`Error`](/schemas#error) |
