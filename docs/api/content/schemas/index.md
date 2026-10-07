---
title: "Schemas API showcase-recruiter-webapp-saas"
description: "Schemas OpenAPI exposes par showcase-recruiter-webapp-saas API."
---


# Schemas

Source OpenAPI : `http://localhost:18082/v1/docs.json`

## Candidate
Profil candidat détaillé. Les coordonnées (contactEmail, phone, linkedinUrl) et le nom de
famille sont masqués tant que l'entreprise du recruteur n'a pas dévoilé le candidat
(`revealed`). `isStar` indique un entretien showcase-recruiter-webapp-saas réalisé ; le compte rendu d'entretien
(`starInterviewReport`) est masqué tant que le profil n'est pas dévoilé. Cf. data-model.md /
api-conventions.md.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `id` | string | - | - |
| `givenName` | string | - | - |
| `familyName` | string \| null | - | - |
| `headline` | string \| null | - | - |
| `city` | string \| null | - | - |
| `country` | string \| null | - | - |
| `contactEmail` | string \| null | - | - |
| `phone` | string \| null | - | - |
| `linkedinUrl` | string \| null | - | - |
| `desiredSectorId` | string \| null | - | - |
| `desiredSectorLabel` | string \| null | - | - |
| `desiredFunctionId` | string \| null | - | - |
| `desiredFunctionLabel` | string \| null | - | - |
| `salaryExpected` | array<string \| null> | - | - |
| `salaryMin` | array<string \| null> | - | - |
| `availability` | string \| null | - | - |
| `returnHorizon` | string \| null | - | - |
| `geoMobility` | boolean | - | - |
| `skills` | array<string \| null> | - | - |
| `languages` | array<string \| null> | - | - |
| `presencePeriods` | array<string \| null> | - | - |
| `isStar` | boolean | - | - |
| `starInterviewedAt` | string:date-time \| null | - | - |
| `starInterviewReport` | string \| null | - | - |
| `revealed` | boolean | - | - |
| `createdAt` | string:date-time | - | - |
| `updatedAt` | string:date-time | - | - |
| `requestedLocale` | string | - | - |
| `locale` | string | - | - |
| `fallback` | boolean | - | - |
## CandidateSearch.CollectionOutput
Carte candidat en recherche (aperçu). Coordonnées jamais incluses ici ; nom de famille masqué
(initiale) tant que non dévoilé. La présence Maroc ★ et le statut Star restent visibles.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## CategoryCode.CollectionOutput
Référentiel des vocabulaires métier (secteurs, fonctions, compétences) distingués par
`inCodeSet`. Lecture publique avec libellé i18n résolu (?locale, fallback FR).
Filtre : ?inCodeSet=sector|job_function|skill.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## Config
Valeurs métier publiques consommées par le webapp (coût d'un dévoilé, crédits offerts à la
création, locales activées + locale par défaut, taille de page). Cf. api-routes.md /v1/config.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `revealCost` | integer | - | - |
| `welcomeCredits` | integer | - | - |
| `locales` | array<string \| null> | - | - |
| `defaultLocale` | string | - | - |
| `perPage` | integer | - | - |
## ConstraintViolation
Unprocessable entity
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `status` | integer | - | - |
| `violations` | array<object> | - | - |
| `detail` | string | `readOnly` | - |
| `type` | string | `readOnly` | - |
| `title` | string \| null | `readOnly` | - |
| `instance` | string \| null | `readOnly` | - |
## CreditTransaction.CollectionOutput
Ligne du journal de crédits (lecture membre). Le solde courant = somme des montants.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## CreditWallet
Solde de crédits courant d'une entreprise (lecture membre).
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `organizationId` | string | - | - |
| `balance` | integer | - | - |
| `updatedAt` | string:date-time | - | - |
## Error
A representation of common errors.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `title` | string \| null | `readOnly` | A short, human-readable summary of the problem. |
| `detail` | string \| null | `readOnly` | A human-readable explanation specific to this occurrence of the problem. |
| `status` | integer \| null | - | - |
| `instance` | string \| null | `readOnly` | A URI reference that identifies the specific occurrence of the problem. It may or may not yield further information if dereferenced. |
| `type` | string | `readOnly` | A URI reference that identifies the problem type |
## Favorite
Favori (shortlist partagée de l'entreprise). Ajout idempotent (unicité organization/candidate),
suppression par candidateId. `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `id` | string | - | - |
| `candidateId` | string | - | - |
| `addedById` | string | - | - |
| `createdAt` | string:date-time | - | - |
## Favorite.CollectionOutput
Favori (shortlist partagée de l'entreprise). Ajout idempotent (unicité organization/candidate),
suppression par candidateId. `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## Favorite.FavoriteInput
Favori (shortlist partagée de l'entreprise). Ajout idempotent (unicité organization/candidate),
suppression par candidateId. `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `candidateId` | string:uuid | `required` | - |
## Language.CollectionOutput
Référentiel des langues (code ISO 639-1). Lecture publique, non traduisible.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## Me
Utilisateur courant : identité (Person) + rôles + présence des contextes candidat/recruteur.
Protégé par JWT. Cf. api-routes.md /v1/me.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `id` | string | - | - |
| `email` | string | - | - |
| `givenName` | string | - | - |
| `familyName` | string | - | - |
| `roles` | array<string \| null> | - | - |
| `candidate` | boolean | - | - |
| `recruiter` | boolean | - | - |
## Organization
Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_ADMIN),
lecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `id` | string | - | - |
| `name` | string | - | - |
| `legalName` | string \| null | - | - |
| `taxId` | string \| null | - | - |
| `country` | string \| null | - | - |
| `city` | string \| null | - | - |
| `billingAddress` | string \| null | - | - |
| `website` | string \| null | - | - |
| `logo` | string \| null | - | - |
| `createdAt` | string:date-time | - | - |
| `updatedAt` | string:date-time | - | - |
## Organization.CollectionOutput
Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_ADMIN),
lecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## Organization.OrganizationInput
Fiche entreprise (réponse plate). Création réservée à un user sans entreprise (devient ROLE_COMPANY_ADMIN),
lecture/édition soumises au voter multi-tenant. Cf. data-model.md / api-routes.md.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `name` | string | `required`, `maxLength=255` | - |
| `legalName` | string \| null | `maxLength=255` | - |
| `taxId` | string \| null | `maxLength=50` | - |
| `country` | string \| null | `minLength=2`, `maxLength=2` | - |
| `city` | string \| null | `maxLength=100` | - |
| `billingAddress` | string \| null | - | - |
| `website` | string \| null:uri | `maxLength=255` | - |
## RevealedProfile
Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le
débit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `id` | string | - | - |
| `candidateId` | string | - | - |
| `revealedById` | string | - | - |
| `revealedAt` | string:date-time | - | - |
## RevealedProfile.CollectionOutput
Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le
débit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
## RevealedProfile.RevealInput
Profil dévoilé par une entreprise (espace « déjà dévoilés » partagé). Le POST déclenche le
débit d'un crédit (idempotent). `id` non-identifiant : pas d'IRI/Location à générer.
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `candidateId` | string:uuid | `required` | - |
## StarProfileCard.CollectionOutput
Cartes « profils stars » de la landing (éditorial traduisible). Lecture publique, actives
uniquement, triées par displayOrder, avec champs i18n résolus (?locale, fallback FR).
| Propriete | Type | Contraintes | Description |
| --- | --- | --- | --- |
| `items` | array<string \| null> | - | - |
| `total` | integer | - | - |
| `page` | integer | - | - |
| `perPage` | integer | - | - |
