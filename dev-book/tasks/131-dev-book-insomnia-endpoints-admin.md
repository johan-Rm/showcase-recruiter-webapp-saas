# TASK 131 — dev-book : aligner la collection Insomnia sur les endpoints admin réels

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25 — outillage seul (`dev-book/resources/`), aucun code touché.
- **Type** : Outillage / doc (collection Insomnia de test API).
- **Placement** : le groupe `Administration` de la collection reflétait un état de **cadrage**
  (routes « cibles depuis la doc »), pas l'API admin telle qu'implémentée. Remise à niveau sur la
  surface réelle.
- **Réf** :
  - `api/openapi.json` (contrat généré — source de vérité des chemins `/v1/admin/*`)
  - `dev-book/resources/insomnia/showcase-recruiter-webapp-saas.insomnia.yaml`

## Contexte

Le tour des données de l'espace admin a révélé un écart : le groupe `Administration` ne couvrait
que 3 routes réelles (dont 2 décrites « not implemented yet » alors qu'elles tournent) et 2 routes
fantômes jamais implémentées. La moitié des endpoints admin manquaient — dont `dashboard`,
`vivier`, `jarvi` et tout le domaine `referentials`.

## Vérification de la surface réelle

`grep '/v1/admin/' api/openapi.json` → **10 chemins admin** exactement :
`dashboard`, `vivier`, `candidates`, `candidates/{id}`, `organizations`, `users`, `jarvi`,
`referentials/{type}`, `referentials/reviews`, `referentials/labels/reviews`.

## Livré

- **7 routes ajoutées** au groupe `Administration` (token `req_auth_login_admin`, ordre logique
  par `sortKey`) : `GET /admin/dashboard`, `GET /admin/vivier`, `GET /admin/candidates/{id}`,
  `GET /admin/jarvi`, `GET /admin/referentials/{type}` (params `page`/`perPage`/`status`),
  `POST /admin/referentials/reviews`, `POST /admin/referentials/labels/reviews`.
- **2 routes fantômes supprimées** — absentes de l'OpenAPI : `GET /admin/profile-views`,
  `GET /admin/credit-transactions`.
- **2 descriptions corrigées** — ne prétendent plus « not implemented » : `GET /admin/organizations`,
  `GET /admin/users`.

## Décisions

- **Ancré sur l'OpenAPI**, pas sur ce que le front consomme : la collection teste l'API
  directement (port `18082`), elle doit refléter le contrat, pas le sous-ensemble câblé côté webapp.
- **`validate`/`reject` laissés sous `Espace Entreprise`** (déjà présents et corrects) : ce sont des
  gestes admin mais rattachés à la ressource `organizations` générique — pas de déplacement.
- **Chemin `referentials/{type}` figé sur `METIER`** dans l'URL de démo, `COMPETENCE` étant l'autre
  valeur ; `status` en paramètre désactivé par défaut (vide = tous).

## Critères d'acceptation

- [x] Le groupe `Administration` liste exactement les 10 endpoints admin de l'OpenAPI.
- [x] Aucune route fantôme, aucune description « not implemented » mensongère.
- [x] YAML valide (relu bloc par bloc ; folding des descriptions conforme à l'existant).
- [x] Aucun fichier de code touché.

## Points de vigilance

- La collection reste un **artefact manuel** : toute nouvelle route admin devra y être ajoutée à la
  main (pas de génération depuis l'OpenAPI à ce jour).
