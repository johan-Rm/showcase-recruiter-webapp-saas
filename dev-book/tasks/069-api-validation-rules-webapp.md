# TASK 069 — API : exposer les règles Symfony Validator au webapp

- **Créée le** : 2026-07-12.
- **Statut** : ✅ implémentée.
- **Type** : contrat API / DX frontend.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`

## Contexte

Le webapp doit éviter de recopier les règles de validation déjà déclarées côté API sur les DTO
`ApiResource/Input`. L'OpenAPI expose les contraintes simples, mais ne couvre pas toutes les règles
Symfony Validator, notamment les règles croisées et les groupes métier.

## Objectif

Ajouter un endpoint public permettant au webapp de récupérer les règles de validation runtime à
partir des métadonnées Symfony Validator.

## Livré

- `GET /v1/validation-rules/{form}`.
- Mapping des DTO d'entrée API connus vers des noms stables de formulaires webapp.
- Pseudo-DTO `LoginInput` pour exposer les règles du login, même si `/v1/auth/login` reste géré par
  `json_login`.
- Normalisation des contraintes courantes : `notBlank`, `notNull`, `email`, `length`, `url`,
  `uuid`, `positiveOrZero`, `greaterThanOrEqual`, `choice`, `type`, `valid`.

## Points de vigilance

- L'API reste l'autorité finale : le webapp doit afficher les erreurs `422 ConstraintViolation`.
- Toute nouvelle contrainte custom devra être ajoutée au normaliseur si le webapp doit l'exploiter
  autrement que par son nom de classe.
