---
title: "Routes Login Check"
---


# Routes Login Check
## POST /v1/auth/login

**Resume** : Creates a user token.
Creates a user token.

- Operation ID : `login_check_post`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

The login data
- Requis : oui
- Content-Type : `application/json`
- Schema : object

### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | User token created | object |
