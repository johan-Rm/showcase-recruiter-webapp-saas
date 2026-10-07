# showcase-recruiter-webapp-saas — Conventions API

> Conventions de l'API `api/` (Symfony + API Platform). Source de vérité pour la forme des
> requêtes/réponses. Complète [data-model.md](./data-model.md), [i18n.md](./i18n.md), [data-integrity.md](./data-integrity.md).

## Principes

- **Réponses plates** : pas de graphes d'entités imbriquées. Pas de JSON-LD/Hydra dans l'API app
  (le **SEO/JSON-LD schema.org est géré par le webapp Nuxt**, pas par l'API).
- **Relations = `uuid` scalaire** : une relation est exposée par l'**uuid** de la cible
  (`organizationId`, `desiredSectorId`), jamais en objet imbriqué ni en IRI.
- **Value objects = sous-objet nommé** : les petits objets de valeur restent groupés
  (`salaryExpected: { value, currency }`), pas éclatés en champs scalaires.
- **Collections de jointure = tableaux d'objets minimaux** : ex. `skills: [{ id, label }]`,
  `languages: [{ code, level }]` — uuid/clé + attributs, pas l'entité complète.
- **DTO de sortie dédiés** : la forme de réponse est définie par des Output DTO, découplés des
  entités Doctrine.

## Identifiants

- Interne : PK `bigint` (jamais exposée). Public : **`uuid` (UUIDv7)**, exposé comme `id`.
- Les UUIDv7 sont ordonnés dans le temps (tri/pagination cohérents).

## Format des réponses

### Item

```json
{
  "id": "019e8f02-…",
  "givenName": "Karim",
  "familyName": "Benali",
  "headline": "Directeur Supply Chain",
  "city": "Lyon",
  "country": "FR",
  "organizationId": null,
  "desiredSectorId": "019e…",
  "desiredSectorLabel": "Supply Chain",
  "salaryExpected": { "value": 75000, "currency": "EUR" },
  "availability": "NOTICE_3M",
  "returnHorizon": "M6",
  "skills": [{ "id": "019e…", "label": "SAP" }],
  "languages": [{ "code": "fr", "level": "NATIVE" }, { "code": "en", "level": "C1" }],
  "presencePeriods": [{ "startDate": "2026-09-12", "endDate": "2026-09-20", "city": "Casablanca" }],
  "createdAt": "2026-06-15T10:00:00+00:00",
  "updatedAt": "2026-06-15T10:00:00+00:00",
  "requestedLocale": "fr",
  "locale": "fr",
  "fallback": false
}
```

- **Dénormalisation ciblée** : à côté d'un `…Id`, on peut exposer un `…Label`/`…Name` résolu
  (locale courante) quand l'affichage en a besoin — pour éviter un appel supplémentaire, sans
  imbriquer l'entité.

### Collection paginée

Enveloppe **plate** (pas de Hydra) :

```json
{ "items": [ … ], "total": 248, "page": 1, "perPage": 30 }
```

- Les listes fermées et courtes (référentiels) utilisent la même enveloppe, sans pagination.

### Le jeu d'une recherche : servi en entier, page par page

Une recherche se parcourt **jusqu'à son dernier profil**, quelle que soit sa taille. L'API n'en borne
rien : elle pagine.

```json
{ "items": [ … 30 cartes … ], "total": 352, "page": 1, "perPage": 30, "resultIds": [ … 352 … ] }
```

`resultIds` porte le jeu **entier** dans l'ordre du serveur, et `total` en donne la taille — les deux
se correspondent toujours. Cette liste est servie à la **première page seulement** : elle ne change
pas d'une page à l'autre, la répéter serait du poids pour rien. C'est elle qui permet à un client de
savoir où il va avant d'y arriver.

Deux règles qui en découlent :

- une page **au-delà de la fin** revient vide, jamais en erreur — un client qui déroule ne doit pas
  rencontrer d'échec là où il n'y a plus rien ;
- `?perPage` reste plafonné : au-delà, ce n'est plus une liste qu'on lit mais un export.

#### Cent profils : un conseil, pas une limite

Le nombre cent subsiste, mais il ne coupe plus rien : c'est le **seuil au-delà duquel une recherche
est dite large**, et à partir duquel on conseille au recruteur d'ajouter un critère — trier plusieurs
centaines de profils un par un est un travail que personne ne mène jusqu'au bout.

Il voyage dans `/v1/config` (`searchLoadLimit`), pour que les écrans ne le recopient pas, et il est
**le même** pour le conseil affiché au recruteur et pour l'inventaire d'administration : deux seuils
finiraient par signaler à l'un ce qu'ils taisent à l'autre.

### Les listes de relations accompagnent la première page, et elle seule

L'**ouverture** d'une recherche — première page du périmètre par défaut — porte aussi son **état de
travail**, en trois listes d'identifiants : `favoriteIds` (écartés déduits), `dismissedIds`, `seenIds`.

```json
{ "items": [ … ], "total": 352, "page": 1, "perPage": 30,
  "resultIds": [ … 100 … ], "favoriteIds": [ … 14 … ], "dismissedIds": [ … 3 … ], "seenIds": [ … 74 … ] }
```

Elles portent sur la recherche **entière** et non sur la page servie : un profil retenu en
cent-cinquantième position serait sinon compté sans être connu, et c'est précisément le compteur faux
qu'elles existent pour supprimer. Elles suivent l'ordre du jeu, celui de `resultIds`.

Trois règles :

- **au-delà de la première page, ces listes sont absentes — `resultIds` compris.** Absentes et non
  vides : `favoriteIds: []` en page 2 signifierait « aucun retenu », ce qui serait faux ;
- **les périmètres `?scope=` ne les portent pas** : ils sont demandés après l'ouverture, quand le
  client connaît déjà cet état. Leur `resultIds`, lui, reste servi ;
- **la portée n'est pas la même pour les trois** : `favoriteIds` appartient à la **recherche** — le
  destinataire d'un partage voit les retenus de l'auteur —, tandis que `dismissedIds` et `seenIds`
  appartiennent au **lecteur courant**.

## Internationalisation (lecture & écriture)

Reprend le modèle traduisible (cf. [i18n.md](./i18n.md)) :

- **Lecture** `?locale=fr` (ou `Accept-Language`) : les champs localisés sont **résolus et aplatis**
  sur la ressource (ex. `label`), + métadonnées `requestedLocale`, `locale`, `fallback`.
- Nommage des champs traduits : **cohérent** — le champ traduit s'appelle `label`.

## Authentification

- `POST /v1/auth/login` → `{ email, password }` ⇒ `{ token, refreshToken }` (JWT bearer).
- `POST /v1/auth/refresh` → `{ refreshToken }` ⇒ nouveau JWT.
- `POST /v1/auth/magic-link/request` → `{ email }` ⇒ `{ sent: true }`.
- `POST /v1/auth/magic-link/consume` → `{ token }` ⇒ `{ token, email, userId }` ; token à usage
  unique pour authentifier le déclarant et créer l'entreprise en revue.
- `POST /v1/auth/password-setup/consume` → `{ token, password }` ⇒ `{ passwordSet: true, email }` ;
  token à usage unique émis après validation entreprise pour définir le mot de passe. L'`email` du
  compte est renvoyé pour que le client ouvre une session dans la foulée (le token prouve déjà la
  possession de la boîte).
- `POST /v1/auth/password-reset/request` puis `/v1/auth/password-reset/consume` → réinitialisation
  par token à usage unique.
- Header : `Authorization: Bearer <token>`.

Le Magic Link ne valide jamais l'entreprise et ne remplace pas l'authentification classique durable.
Après validation admin de l'`Organization`, le déclarant définit son mot de passe et **est connecté
automatiquement** : le BFF rejoue un `login` interne avec le mot de passe qui vient d'être posé, ce
qui ouvre une session durable (JWT + refresh) sans écran de connexion intermédiaire.

### Anti-énumération et e-mail déjà connu

- `magic-link/request` renvoie **toujours** `{ sent: true }`, que l'adresse soit connue ou non, et
  **ne crée aucun compte** : le `User` naît à la *consommation* du lien, quand la personne a prouvé
  qu'elle possède la boîte. Une campagne d'envois sur cette route publique ne peut donc ni révéler
  quels e-mails ont un compte, ni peupler la table des comptes. **Aucun écran ne doit indiquer qu'un
  e-mail existe déjà** — cette différenciation ne se fait que dans la boîte mail (canal privé).
- E-mail **déjà rattaché à une entreprise** : le lien le reconnecte et le dépose dans son espace ; il
  ne repasse pas par le formulaire d'inscription. La création d'une seconde entreprise par un
  déclarant déjà propriétaire est refusée en **`409 Conflict`** (« déjà rattaché à une entreprise »).

## Multi-comptes entreprise

- Membres d'une `Organization` : `GET /v1/organizations/{id}/members`,
  `POST /v1/organizations/{id}/invitations { email, role }`, `GET /v1/invitations/{token}` pour le
  contexte public, puis `POST /v1/invitations/{token}/accept` pour créer le compte membre.
- Le retrait d'un membre passe par `DELETE /v1/organizations/{id}/members/{recruiterId}`.

## Routing

- Ressources : `/v1/{resource}` (kebab/pluriel), items par `uuid`.
- Sous-ressources : `/v1/{parent}/{id}/{children}` (ex. `/organizations/{id}/members`,
  `/candidates/{id}/presence-periods`).
- Routes métier explicites : `/organizations/{id}/validate`, `/invitations/{token}/accept`, etc.
- **OpenAPI** auto exposé sur `/v1/docs` (Swagger).

## Divers

- **Enums** = `string` (`status: "published"`, `availability: "NOTICE_3M"`).
- **Dates** = ISO 8601 (`timestamptz`).
- `createdAt` / `updatedAt` exposés ; `deletedAt` non exposé (soft delete interne).

## Règles structurantes

- Réponses **plates**, relations en **uuid scalaire**, value objects en **sous-objet nommé**,
  collections de jointure en tableaux d'objets minimaux.
- **DTO de sortie** dédiés ; pagination en **enveloppe plate** (`items`/`total`/`page`/`perPage`).
- i18n : champs résolus à plat + `?locale` + métadonnées fallback.
- Auth JWT (`/auth/login`, `/auth/refresh`) ; membres entreprise par email + rôle.
- `uuid` public = **UUIDv7** ; OpenAPI sur `/v1/docs`.
