# showcase-recruiter-webapp-saas — Intégration Jarvi

> Intégration de l'API Jarvi (source : https://api-docs.jarvi.tech) et frontière des responsabilités.
> Complète [architecture.md](./architecture.md) (section 3 — frontière Jarvi).

## Endpoints & accès

| Élément        | Valeur                                                          |
| -------------- | -------------------------------------------------------------- |
| API REST v2    | `https://functions.prod.jarvi.tech/v1/public-api/rest/v2/`     |
| Serveur MCP    | `https://mcp.jarvi.tech`                                        |
| Auth REST      | header **`X-API-KEY`**                                          |
| Rate limit     | **500 requêtes / jour** (au-delà : contacter le support)       |
| Ressources     | `profiles`, `applicants`, `projects`, `companies`, `offers`, GraphQL |

### Clés API — règle de sécurité

- **Clé publique** : autorise uniquement la **création** de profils / candidatures.
- **Clé privée** : accès complet (lecture incluse). **Ne doit jamais être exposée côté client**
  (« pas présente dans le code javascript »), ni committée.

➡️ **La clé privée vit uniquement côté `api/` Symfony.** Le webapp Nuxt ne contacte jamais Jarvi
en direct : tout passe par un proxy serveur. (Conséquence : un proxy serveur Jarvi est de toute
façon nécessaire, indépendamment de la décision BFF/SSR.)

## Fonctionnement opérationnel showcase-recruiter-webapp-saas ↔ Jarvi

> **La frontière tient en une phrase : Jarvi est maître du profil professionnel, showcase-recruiter-webapp-saas est maître
> de tout le reste.** showcase-recruiter-webapp-saas réplique le profil pro localement, en lecture seule, et garde la
> maîtrise du consentement, des comptes candidats, des différenciants, de la présence Maroc et des
> interactions métier. **Les données maîtres showcase-recruiter-webapp-saas ne sont jamais synchronisées vers Jarvi.**
>
> Seule écriture montante : si un candidat s'inscrit alors qu'aucun profil Jarvi ne lui correspond,
> showcase-recruiter-webapp-saas crée un profil Jarvi minimal pour obtenir l'ancre `jarviProfileId` — après quoi la
> fraîcheur professionnelle redescend (`Jarvi → showcase-recruiter-webapp-saas`).

### Partage de la source de vérité

| Tranche de donnée | Maître | Côté showcase-recruiter-webapp-saas |
| --- | --- | --- |
| **Profil pro** : identité professionnelle, coordonnées pro, CV, expériences, compétences, langues, headline, résumé | **Jarvi** | **réplica locale complète (lecture seule)** alimentée par ingestion du périmètre + **snapshot figé** au dévoilement |
| **Différenciants** : présence au Maroc, projet de retour, prétentions, disponibilité, visibilité | **showcase-recruiter-webapp-saas** | stockés en base showcase-recruiter-webapp-saas uniquement |
| **Compte candidat** : login, consentement, visibilité | **showcase-recruiter-webapp-saas** | natif (Espace Candidat) |
| **Interactions** : crédits, dévoilés, favoris, statuts, historique des profils visités | **showcase-recruiter-webapp-saas** | natif (cœur métier) |
| Artefacts internes Jarvi (`searchString`, `taskId`, métadonnées d'interface) | Jarvi | **jamais ingérés** (liste blanche de champs) |

**Règle anti-incohérence** : **un seul maître par tranche**. La sync est **bidirectionnelle mais
partitionnée par cas d'usage** : le profil pro **descend** de Jarvi (ingestion → réplica lecture
seule). showcase-recruiter-webapp-saas peut seulement **créer** un profil Jarvi minimal s'il est absent au moment de
l'inscription. Les différenciants et consentements showcase-recruiter-webapp-saas restent locaux.

### Réplica locale du périmètre — ingestion de masse

Le vivier de recherche = le **compte de sourcing Jarvi de showcase-recruiter-webapp-saas** (plus large que les seuls
candidats inscrits). showcase-recruiter-webapp-saas maintient une **réplica locale en lecture seule** du **profil pro de
tout ce périmètre**, alimentée par un **pipeline d'ingestion** (tâche `020`) :

- **Pull initial** du périmètre + **rafraîchissement incrémental** via le curseur **`updatedAt`**
  (confirmé dispo côté Jarvi) — on ne tire que les profils modifiés depuis le dernier passage.
- **Synchro quotidienne globale** (Symfony Scheduler/Messenger, hors dev), **upsert idempotent**
  ancré sur `jarviProfileId`.
- **Refresh on-demand** d'un profil **au dévoilement** (action payante → fraîcheur garantie là où
  ça compte, ~1 requête).
- **Réconciliation quotidienne** : les profils **sortis du périmètre** côté Jarvi sont **désactivés**
  en base (drapeau d'activité / hors-périmètre), **jamais supprimés** (historique, dévoilés payés,
  preuve RGPD).
- **Liste blanche de champs** : on n'ingère que les champs de domaine utiles (identité, photo, CV,
  expériences, formations, langues, compétences, localisation, headline) — **pas** les métadonnées
  propres à l'interface Jarvi.
- **Aucun binaire copié** : photo comme CV restent chez Jarvi ; la réplica ne garde que l'URL
  publique (photo) ou les métadonnées de fichier (CV).

> **RGPD.** Répliquer les données personnelles de **tout le périmètre** — et pas seulement au moment
> d'une interaction — engage la posture RGPD : base légale de la collecte, durée de rétention, droit
> d'opposition et d'effacement sont des **prérequis du pipeline**, pas des garde-fous de fin de
> chantier.

> **Les coordonnées sont rares à la source, et ce n'est pas un défaut d'import.** Mesuré en
> recoupant notre base et l'API Jarvi, au profil près : **e-mail ~4 %**, **téléphone ~3 %**,
> **CV ~4 %**, **compte rendu d'entretien < 1 %**. Ce que Jarvi détient, on l'importe intégralement
> — Jarvi ne détient simplement pas ces coordonnées pour la grande majorité du périmètre, qui vient
> du sourcing LinkedIn passif. Ce qui est solide : prénom, nom, **LinkedIn (99 %)** et **employeurs
> (96 %)**. Conséquence produit : une promesse de « profil joignable » ne peut pas s'appuyer sur
> l'e-mail ou le téléphone.

**Snapshot figé au dévoilement** : distinct de la réplica (qui suit l'état courant Jarvi). Au
**dévoilement** / **mise en favori**, on fige en plus un **snapshot** des champs affichés — garantie
« tu as payé, tu gardes ce que tu as vu » même si le profil change/sort du périmètre ensuite.

### Snapshot au dévoilement / favori — pourquoi

Le déblocage est **payant** : ce qui a été montré doit rester **stable et accessible** même si le
profil change ou disparaît côté Jarvi. Le snapshot sert quatre besoins d'un coup :

- **stabilité** : « tu as payé, tu gardes ce que tu as vu » ;
- **historique des profils visités** (demandé par l'admin) ;
- **performance** : l'espace « Déjà dévoilés / Favoris » s'affiche sans re-fetch Jarvi (ni quota) ;
- **preuve RGPD** de ce qui a été présenté à un instant T.

### Différenciants showcase-recruiter-webapp-saas — locaux uniquement

Les champs cœur de la valeur showcase-recruiter-webapp-saas (**présence au Maroc**, projet de retour, prétentions,
disponibilité, visibilité, consentement) sont **maîtres côté showcase-recruiter-webapp-saas** et **ne sont pas poussés vers
Jarvi**. La recherche exploite donc la base showcase-recruiter-webapp-saas : réplica professionnelle Jarvi + différenciants
locaux. Toute recherche qui exploiterait ces différenciants se cadre côté showcase-recruiter-webapp-saas, sans jamais faire
de Jarvi le maître de ces champs.

### Implications pour le modèle de données

- **`Candidate` showcase-recruiter-webapp-saas = projection** ancrée sur `jarviProfileId` (unique), portant les
  **différenciants + le compte (optionnel) + une réplica pro en lecture seule + un snapshot figé**.
  Le profil pro est alimenté par l'ingestion (Jarvi maître), jamais édité localement comme source de
  vérité. Voir l'impact détaillé dans [data-model.md](./data-model.md).
- **`person`/`user` optionnel** : le périmètre contient des profils **non inscrits** (sourcés) ; le
  compte n'existe que pour les candidats qui s'inscrivent.
- **`RevealedProfile` / `Favorite` / `OrganizationStatus`** référencent la projection
  `Candidate` (ancrée `jarviProfileId`).
- **Inscription candidat** : rattachement à une projection existante si le profil Jarvi a déjà été
  importé ; sinon création d'un profil Jarvi minimal, puis ingestion descendante.
- **Filet de sécurité** : on n'ingère et on n'expose que les profils du **périmètre Jarvi de
  showcase-recruiter-webapp-saas** (compte de sourcing isolé en prod). Hors périmètre ⇒ **désactivé**, jamais supprimé.

## Création initiale d'un profil Jarvi si absent — `POST /v2/profiles`

Endpoint utilisé uniquement quand un candidat s'inscrit dans showcase-recruiter-webapp-saas et qu'aucun profil Jarvi
correspondant n'existe encore. Cette écriture ne crée pas une sync montante permanente : elle sert à
obtenir l'ancre `jarviProfileId` pour rattacher ensuite la réplica descendante.

- **Clés d'unicité** : `id` (UUID) → `externalId` (notre ID public) → sinon création.
- **Stratégie showcase-recruiter-webapp-saas** : pousser avec **`id` = `Candidate.jarviProfileId`** (UUIDv7 minté côté
  showcase-recruiter-webapp-saas à l'inscription, honoré par Jarvi à la création) **et `externalId` = `Person.uuid`**.
- **Contrat write réel** (`POST /profiles`) — **honorés** : `id`, `externalId`,
  `firstName`/`lastName` (normalisés title-case par Jarvi), `location` (envoyé en **chaîne**
  « Ville, Pays », géocodé en `locations[]`). **Ignorés par l'API publique** : les **custom fields**
  (`customFieldsValues` map, `fieldsValues` array, `customFields` — les trois formes) et **`headline`**.
  Une montée de différenciants par cette voie est donc impossible.
- Réponse : `{ message, taskId, profileId }` ; codes `201` / `400` / `500`.

**Résilience** : cette création ne doit pas bloquer durablement l'inscription. Si Jarvi est
indisponible, showcase-recruiter-webapp-saas conserve l'état local et doit permettre une reprise ou une tentative différée.
Une fois le profil créé chez Jarvi, la fraîcheur professionnelle repasse par l'ingestion
descendante.

## Recherche recruteur

**La recherche interroge la réplica locale (Postgres), jamais Jarvi en direct.** showcase-recruiter-webapp-saas réplique
tout le périmètre (profil pro) et détient les différenciants : la recherche tourne donc entièrement
sur la base showcase-recruiter-webapp-saas. Elle est ainsi **déterministe et reproductible** — condition des recherches
enregistrées — et **ne consomme pas le quota** de 500 requêtes par jour.

```
Recruteur ─► api/ ─► Postgres (réplica locale + différenciants)

Réplica locale ◄─ ingestion ◄─ Jarvi REST/GraphQL (`where` + curseur `updatedAt`)
Résultats ─► api/ applique masquage + visibilité + état dévoilé/favori (projection)
```

- **La réplica locale EST l'index de recherche** : filtres structurés et recherche plein-texte
  s'appuient dessus, différenciants showcase-recruiter-webapp-saas compris.
- **Seule l'ingestion parle à Jarvi** (REST `where` + curseur `updatedAt` ; GraphQL pour sélectionner
  les champs en masse). Le quota pèse donc sur elle, et elle est incrémentale — donc sobre.
- **Limites du REST Jarvi**, qui justifient ce choix : `GET /v2/profiles` n'a **pas** de paramètre
  plein-texte (`q`), n'est **pas paginé** (il renvoie tout, `limit` max 1000) et filtre via `where`
  (objet GraphQL JSON). La recherche en **langage naturel** n'existe que côté **MCP**
  (`advancedSearch`).

## Entreprises — filtrage géographique « Maroc »

> Structure établie par exploration du `GET /rest/v2/companies` réel (compte de sourcing showcase-recruiter-webapp-saas).
> Elle évite d'avoir à la redeviner à chaque besoin.

**La localisation d'une entreprise n'est PAS dans `legaldata`** (toujours `null` sur le compte
showcase-recruiter-webapp-saas). Le pays/la ville vivent dans **`fieldsValues`**, via un custom field *Localisation* de
type `singlelocation` (relation imbriquée `location` → `search` / `formattedAddress` / `locality`,
ex. `"Casablanca, Maroc"`, `"Tanger, Maroc"`, `"Maroc"`).

**Filtre canonique « entreprises localisées au Maroc »** (`where`, filtre Hasura, `_ilike`
insensible à la casse) :

```json
{"fieldsValues":{"location":{"formattedAddress":{"_ilike":"%maroc%"}}}}
```

`location.locality` donne la ville pour un filtrage plus fin. Comme `GET /companies`, le résultat
est **non paginé** (le champ `total` = nombre filtré).

**Deux signaux « Maroc » distincts — ne pas confondre :**

| Signal | Chemin | Sémantique |
| --- | --- | --- |
| **Géo** | `fieldsValues.location` (champ *Localisation*, `6e053153-a7e6-47c3-96d5-2ac7abc7944b`) | Où se passe le recrutement / le site |
| **Tag métier** | `fieldsValues.fieldValue` (champ *Type de client*, `5143c2c4-82fd-4926-9022-7ffd02eee7c4`) → « Recrutement Maroc », « Partneraire Maroc », « showcase-recruiter-webapp-saas » | Catégorisation commerciale (curation recruteur) |

⚠️ **Caveat facturation** : « localisation = Maroc » **≠** « entité juridiquement marocaine ». Les
résultats incluent des groupes internationaux avec un site/recrutement au Maroc (ex. PLANASA
espagnole, Safran française). `legaldata` étant `null`, **l'entité facturée n'est pas déductible de
façon fiable depuis Jarvi**. Le brief pose *« l'entreprise marocaine pour la partie facturation »* :
le **rattachement entreprise ↔ facturation reste maître côté `api/` showcase-recruiter-webapp-saas**, jamais dérivé de
Jarvi. Les UUID de custom fields ci-dessus sont propres au compte de sourcing et doivent être
traités comme de la **config mappée** (cf. mapping custom fields).

### Création company Jarvi depuis showcase-recruiter-webapp-saas

showcase-recruiter-webapp-saas reste maître de l'`Organization` recruteur. La company Jarvi est une représentation externe
créée uniquement après validation admin de l'entreprise showcase-recruiter-webapp-saas, jamais au simple submit du
formulaire.

Flux :

1. `POST /v1/organizations` crée l'entreprise showcase-recruiter-webapp-saas en `EN_REVUE`, son owner et son wallet local.
2. L'admin vérifie l'entreprise puis appelle `POST /v1/organizations/{uuid}/validate`.
3. showcase-recruiter-webapp-saas envoie au déclarant un lien de définition de mot de passe à usage unique. Ce flux reste
   strictement local et n'est jamais transmis à Jarvi.
4. L'`api/` pousse `POST /companies` côté Jarvi avec le minimum `name`, alimenté par
   `Organization.legalName` si disponible, sinon `Organization.name`. `linkedinUrl` reste facultatif.
5. La réponse renseigne `jarviCompanyId`.
6. L'`api/` pousse ensuite `POST /projects` avec `projectId = JARVI_RECRUITER_PROJECT_ID` et le
   `companyId` retourné, pour associer l'entreprise au projet recruteur showcase-recruiter-webapp-saas défini à l'avance.
7. Si les deux écritures réussissent, `jarviCompanySyncStatus = SYNCED`. En échec, le statut devient
   `FAILED` sans supprimer ni invalider l'entreprise showcase-recruiter-webapp-saas.

Garde-fou local : la synchro company est derrière `JARVI_COMPANY_SYNC_ENABLED=false` par défaut. Les
tests de process complet peuvent donc valider l'orchestration showcase-recruiter-webapp-saas sans déclencher d'inscription
réelle dans Jarvi. Le passage à `true` nécessite un feu vert explicite et une clé privée Jarvi côté
`api/`. En dev, le projet de test autorisé est configuré via
`JARVI_RECRUITER_PROJECT_ID=00000000-0000-4000-8000-000000000003`.

## Serveur MCP — capacités

`advancedSearch` (recherche langage naturel sur profils / entreprises / projets),
`getEntityData`, créations/mises à jour, messagerie (email, LinkedIn, WhatsApp, SMS), upload,
GraphQL. Auth : OAuth2 (Claude Desktop) ou clé privée `x-api-key` (autres clients → notre cas).


## Références

- Auth / clés : https://api-docs.jarvi.tech/fr/api-keys.md
- Profiles (upsert) : https://api-docs.jarvi.tech/fr/api-reference/profiles/post.md
- Profiles (list) : https://api-docs.jarvi.tech/fr/api-reference/profiles/get.md
- MCP : https://api-docs.jarvi.tech/fr/mcp.md
- OpenAPI : https://api-docs.jarvi.tech/openapi.json
