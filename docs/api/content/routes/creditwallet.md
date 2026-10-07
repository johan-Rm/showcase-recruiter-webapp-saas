---
title: "Routes CreditWallet"
description: "Solde de crédits courant d'une entreprise (lecture membre)."
---


# Routes CreditWallet
Solde de crédits courant d'une entreprise (lecture membre).
## GET /v1/organizations/{organizationId}/credit-wallet

**Resume** : Retrieves a CreditWallet resource.
Retrieves a CreditWallet resource.

- Operation ID : `api_organizations_organizationIdcredit-wallet_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | CreditWallet identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | CreditWallet resource | [`CreditWallet`](/schemas#creditwallet) |
| `404` | Not found | [`Error`](/schemas#error) |
