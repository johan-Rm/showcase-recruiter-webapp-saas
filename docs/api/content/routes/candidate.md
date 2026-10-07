---
title: "Routes Candidate"
description: "Profil candidat détaillé. Les coordonnées (contactEmail, phone, linkedinUrl) et le nom de\nfamille sont masqués tant que l'entreprise du recruteur n'a pas dévoilé le candidat\n(`revealed`). Cf. data-model.md / api-conventions.md."
---


# Routes Candidate
Profil candidat détaillé. Les coordonnées (contactEmail, phone, linkedinUrl) et le nom de
famille sont masqués tant que l'entreprise du recruteur n'a pas dévoilé le candidat
(`revealed`). Cf. data-model.md / api-conventions.md.
## GET /v1/candidates/{id}

**Resume** : Retrieves a Candidate resource.
Retrieves a Candidate resource.

- Operation ID : `api_candidates_id_get`
- Securite : non declaree sur la route

### Parametres

| Nom | Emplacement | Requis | Type | Description |
| --- | --- | --- | --- | --- |
| `id` | path | oui | string | Candidate identifier |

### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | Candidate resource | [`Candidate`](/schemas#candidate) |
| `404` | Not found | [`Error`](/schemas#error) |
