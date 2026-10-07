# TASK 050 — webapp : types TS générés depuis l'OpenAPI + gate `contracts-drift`

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-12.
- **Type** : Front — Webapp Nuxt (contrats de données).
- **Réf** : `webapp/README.md` (« Reste à brancher ») · `webapp/types/` ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/` · API : `/v1/docs` (OpenAPI, API Platform).

## Contexte

Le principe de contrats du projet est arrêté : **l'API est la source de vérité**, les types
TypeScript du front en sont *générés*, et une gate CI empêche la dérive. Rien n'est branché :
les types de `webapp/` sont écrits à la main et peuvent diverger silencieusement du schéma
Symfony.

Le `webapp/README.md` annonce déjà le script `quality-generate-schemas` (renommé
`quality:generate-schemas` par la tâche `047`) et la gate `contracts-drift`.

## Objectif

Générer les types TS depuis l'OpenAPI de l'API et faire échouer la CI dès que le code généré
ne correspond plus au schéma.

## Périmètre

- Choisir l'outil de génération (p. ex. `openapi-typescript`) et le figer dans
  `webapp/package.json`.
- Script `quality:generate-schemas` : lit l'OpenAPI de l'API, écrit les types dans un
  répertoire dédié, clairement marqué **généré — ne pas éditer à la main**.
- Source de l'OpenAPI **paramétrable par variable d'environnement** (URL de l'API selon
  l'environnement), avec repli sur un artefact commité pour que la CI et le build tournent
  sans API disponible.
- Gate CI `contracts-drift` : régénère et échoue si `git diff` n'est pas vide.
- Consommer ces types là où le front parle à l'API (`server/utils/showcase-recruiter-webapp-saas-api.ts`, routes
  `server/api/`), en remplacement des interfaces manuelles (`LoginResponse` par exemple).
- Même principe pour les **constantes** venant de l'API (`CategoryCode`…) : lues depuis l'API,
  jamais recopiées.

## Invariants

- Aucune donnée externe typée par `as Type` : valider ou typer à la source.
- Le répertoire généré n'est jamais édité à la main.
- Aucun domaine d'API en dur.

## Critères d'acceptation

- [ ] `bun run quality:generate-schemas` produit des types à jour et idempotents (rejouer le
      script ne change rien).
- [ ] La gate `contracts-drift` échoue sur une modification volontaire d'un DTO côté API non
      régénérée côté front.
- [ ] `LoginResponse` et les autres interfaces manuelles du BFF sont remplacées par les types
      générés.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Génération d'un client API complet (on ne veut que les types).
- Types des endpoints Jarvi (contrat externe, réplica JSON opaque — cf. `candidate_jarvi`).

## Mise à jour 2026-07-12 — livré

Chaîne complète : **code API → `api/openapi.json` (commité) → `shared/types/api.ts` (généré)**,
verrouillée par **deux** gates CI.

- **Artefact côté API** (le producteur possède son contrat) : `make dev-openapi` →
  `api:openapi:export`. Vérifié **déterministe** (deux exports octet-identiques), condition sine
  qua non d'une gate sans faux positifs.
- **Génération front** : `openapi-typescript` → `shared/types/api.ts` (3411 lignes, 37 routes,
  54 schémas). Exclu d'ESLint et de Prettier, sinon ils le reformateraient et la gate
  produirait des faux positifs.
- **Source paramétrable** : `SHOWCASE_RECRUITER_WEBAPP_SAAS_OPENAPI_SOURCE` (URL d'API), **repli sur l'artefact commité**
  → la CI et le build tournent sans API disponible.
- **Consommation** : `LoginResponse` (écrit à la main dans `base/server/utils/api.ts`) est
  désormais dérivé du contrat.

### Défaut réel débusqué au passage

L'OpenAPI **mentait** : le login déclarait `{ token }` alors que l'API renvoie
`{ token, refresh_token }` — un champ dont le front dépend déjà, et sur lequel la tâche `049`
(refresh) allait être bâtie.

Cause : le login est un `json_login` Symfony ; **LexikJWT** contribue le path à l'OpenAPI (avec
`token` seul) tandis que **Gesdinet** ajoute `refresh_token` à la réponse **au runtime**, sans le
déclarer.

Correctif : `api/src/OpenApi/LoginResponseDecorator.php`. ⚠️ **Sa priorité négative est
indispensable** — sans elle, le décorateur s'exécute *à l'intérieur* de celui de Lexik, donc
**avant** que le path n'existe : il patche dans le vide et Lexik écrase ensuite.

### Deux gates, et il en faut deux

1. `api-quality` : régénère `api/openapi.json`, échoue s'il diffère → le producteur garde son
   contrat à jour.
2. `webapp-quality` : régénère `shared/types/api.ts`, échoue s'il diffère → le consommateur reste
   en phase.

Sans la première, on changerait l'API sans régénérer le contrat, et la seconde validerait un
artefact périmé.

Gate prouvée : un champ ajouté au contrat sans régénération des types → `quality-contracts-check`
échoue. Vitest a aussi été ajouté à la CI webapp (il n'y tournait pas).

### Reste

Les **constantes** venant de l'API (`CategoryCode`…) ne sont pas encore lues depuis le contrat.

