# TASK 088 — API : canal d'écriture Jarvi `/applicants` (extraction CV + LinkedIn)

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire.
- **Type** : API — intégration Jarvi (sync montante candidat).
- **Débloque** : tâches `089` (câblage onboarding candidat) · `091` (upload CV inscription).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/api/inscription.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`

## Contexte

Test réel du 2026-07-18 (voir mémoire `showcase-recruiter-webapp-saas-jarvi-applicants-extraction`) : l'endpoint
**`POST {url_api}/applicants`** de Jarvi extrait le profil à partir d'une **`linkedinUrl` seule**
(headline immédiat, puis positions/educations/skills/summary en **asynchrone ~4 min**, chaque bloc
estampillé `source: LINKEDIN`) **et** d'un CV base64 (`source: RESUME`).

Notre code actuel n'écrit que sur **`POST /profiles`** ([JarviProfileWriter.php](../../api/src/Domain/Jarvi/JarviProfileWriter.php)),
qui pousse identité + location + externalId **sans** déclencher d'extraction. Il manque donc le canal
`/applicants`, seul chemin qui fait extraire Jarvi.

## Objectif

Ajouter un writer dédié `/applicants` à côté de l'existant, sans toucher au chemin `/profiles`
(qui reste utilisé pour la sync entreprise/recruteur).

## Périmètre

- Nouveau DTO `JarviApplicant` (immutable) : `externalId`, `firstName`, `lastName`, `linkedinUrl?`,
  `emailAddresses?`, `phoneNumbers?`, `headline?`, `location?` (chaîne « Ville, Pays »), `projectId`,
  `resumesFiles?` (`[{ fileName, data }]`, `data` = `data:@file/pdf;base64,…`).
- Nouveau writer `JarviApplicantWriter` (+ interface) utilisant le client REST scopé `jarvi.client`
  (`config/packages/jarvi.yaml`), méthode `submit(JarviApplicant): JarviApplicantResult`.
- Parser la réponse `201 { message, taskId, profileId, fileErrors }` → retourner **`profileId`**
  (ancre Jarvi) + `taskId` + `fileErrors`.
- Respecter la contrainte : `externalId` de test préfixé `showcase-recruiter-webapp-saas-test-` uniquement hors production.
- Ne **pas** appeler l'endpoint dans ce périmètre (le câblage onboarding est la tâche `089`).

## Hors périmètre

- Câblage à l'inscription candidat → tâche `089`.
- Gestion du délai d'enrichissement async et du statut → tâche `090`.
- Encodage/validation du CV côté inscription → tâche `091`.

## Invariants

- Clé privée Jarvi côté `api/` uniquement.
- Le chemin `/profiles` existant (sync entreprise) n'est pas modifié.
- `profileId` renvoyé par Jarvi = future ancre `Candidate.jarviProfileId` (voir `089`).

## Critères d'acceptation

- [ ] `JarviApplicantWriter::submit()` poste sur `/applicants` avec le shape testé et renvoie
      `profileId` + `taskId`.
- [ ] Les `resumesFiles` sont acceptés optionnellement (0..n fichiers).
- [ ] Les erreurs HTTP Jarvi sont typées/journalisées, sans exposer la clé.
- [ ] Tests ciblés avec client HTTP mocké : payload minimal (URL seule), payload avec CV,
      réponse d'erreur.

## Points de vigilance

- **Quota Jarvi 500 req/j** : un applicant = 1 write ; ne pas boucler.
- **Async** : la réponse 201 ne signifie pas profil enrichi — ne rien lire dans la foulée.
- **RGPD** : le CV et l'URL LinkedIn sont des données personnelles ; base légale à couvrir en `089`.

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/inscription.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`
