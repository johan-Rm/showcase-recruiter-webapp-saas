---
title: "Routes CreditTransaction"
description: "Ligne du journal de crédits (lecture membre). Le solde courant = somme des montants."
---


# Routes CreditTransaction
Ligne du journal de crédits (lecture membre). Le solde courant = somme des montants.
## GET /v1/organizations/{organizationId}/credit-transactions

**Resume** : Retrieves a CreditTransaction resource.
Retrieves a CreditTransaction resource.

- Operation ID : `api_organizations_organizationIdcredit-transactions_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `organizationId` | path | oui | string | CreditTransaction identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | CreditTransaction resource | [`CreditTransaction.CollectionOutput`](/schemas#credittransaction-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
