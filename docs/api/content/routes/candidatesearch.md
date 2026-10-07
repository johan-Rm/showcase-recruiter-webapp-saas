---
title: "Routes CandidateSearch"
description: "Carte candidat en recherche (aperçu). Coordonnées jamais incluses ici ; nom de famille masqué\n(initiale) tant que non dévoilé. La présence Maroc ★ reste visible (hook produit)."
---


# Routes CandidateSearch
Carte candidat en recherche (aperçu). Coordonnées jamais incluses ici ; nom de famille masqué
(initiale) tant que non dévoilé. La présence Maroc ★ reste visible (hook produit).
## GET /v1/search/candidates

**Resume** : Retrieves a CandidateSearch resource.
Retrieves a CandidateSearch resource.

- Operation ID : `api_searchcandidates_get`
- Securite : non declaree sur la route

### Parametres

Aucun parametre.


### Body

Aucun body.


### Reponses

| Statut | Description | Schema |
| --- | --- | --- |
| `200` | CandidateSearch resource | [`CandidateSearch.CollectionOutput`](/schemas#candidatesearch-collectionoutput) |
| `404` | Not found | [`Error`](/schemas#error) |
