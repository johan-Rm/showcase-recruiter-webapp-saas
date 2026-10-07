---
title: "Routes Me"
description: "Utilisateur courant : identité (Person) + rôles + présence des contextes candidat/recruteur.\nProtégé par JWT. Cf. api-routes.md /v1/me."
---


# Routes Me
Utilisateur courant : identité (Person) + rôles + présence des contextes candidat/recruteur.
Protégé par JWT. Cf. api-routes.md /v1/me.
## GET /v1/me

**Resume** : Retrieves a Me resource.
Retrieves a Me resource.

- Operation ID : `api_me_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Me resource | [`Me`](/schemas#me) |
| `403` | Forbidden | [`Error`](/schemas#error) |
| `404` | Not found | [`Error`](/schemas#error) |
