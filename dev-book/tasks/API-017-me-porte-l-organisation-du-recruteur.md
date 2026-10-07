# TASK API-017 — `/v1/me` porte l'organisation du recruteur

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Backend API.
- **Réf** : `ENT-013` (le dévoilement côté front, que ce manque bloque).

## Contexte

Tout ce qui touche aux crédits et au dévoilement est adressé **par organisation** :

```
POST /v1/organizations/{organizationId}/revealed-profiles
POST /v1/organizations/{organizationId}/revealed-profiles/batch
GET  /v1/organizations/{organizationId}/credit-wallet
GET  /v1/organizations/{organizationId}/credit-transactions
```

Or **le client ne peut pas connaître son organisation**. `/v1/me` rend l'identité, les rôles et le
contexte candidat, mais pas l'organisation du recruteur ; et aucune route ne liste les organisations
de l'appelant — `GET /v1/organizations` n'existe pas, seul `POST` est exposé.

Le front ne peut donc construire aucune de ces quatre URL. Le dévoilement, qui est le geste central
du produit, est inatteignable pour cette seule raison.

## Décisions

### L'organisation rejoint le contexte de l'utilisateur courant

`organizationId` sur `MeOutput`, `null` pour qui n'est pas recruteur — comme `candidateId` l'est déjà
pour qui n'est pas candidat. C'est le même besoin et la même forme : `/v1/me` est l'endroit où l'on
apprend de quels contextes on dispose.

### L'organisation de la casquette ACTIVE

Résolue par `CurrentRecruiterResolver::resolveFirstActive`, le même chemin que les providers de
recherche et de suggestions. Un recruteur désactivé n'a pas d'organisation à annoncer : il n'en a
plus les droits, et l'annoncer quand même ferait afficher un solde qu'il ne peut pas dépenser.

### Rien d'autre pour l'instant

Pas de nom d'organisation, pas de statut de validation, pas de rôle dans l'organisation. `ENT-013` a
besoin d'une adresse, pas d'une fiche. Le menu du compte note qu'il aimerait le nom — ce sera sa
carte, avec son besoin d'affichage.

## Critères d'acceptation

- [x] `/v1/me` porte `organizationId` pour un recruteur actif.
- [x] `null` pour qui n'a pas de casquette recruteur active.
- [x] `api/openapi.json` régénéré dans le même commit.

## Piège rencontré

Le conteneur de services compilé de l'environnement **test** ne s'est pas reconstruit après l'ajout
d'un argument au constructeur du provider : les tests voyaient l'ancienne version et `organizationId`
revenait `null`, alors que l'endpoint répondait juste sur la stack de dev. Un `cache:clear` en
`APP_ENV=test` a suffi. À retenir : un échec de test qui contredit un appel réel n'accuse pas
forcément le code.
