# Profil Jarvi — échantillon réel & mapping `candidate_jarvi`

> **But** : figer la **forme réelle** d'un profil renvoyé par `GET /rest/v2/profiles` (jusqu'ici
> hypothétique, cf. les `TODO(020)` de `api/src/Domain/Jarvi/JarviClient.php`) et valider la
> décision **`candidate_jarvi` = réplica JSON opaque, lecture/synchro only**.
>
> **Source** : compte de sourcing showcase-recruiter-webapp-saas, tiré en réel le 2026-06-28 (`GET …/profiles`,
> `total = 7148`). Échantillon : un profil **tech** avec **CV extrait** + **compte rendu
> d'entretien**.
>
> **RGPD** : le profil brut (vraie personne) **n'est jamais versionné**. La version commitée
> [`profile-sample-tech.anonymise.json`](../../resources/jarvi/profile-sample-tech.anonymise.json)
> est **anonymisée** (identité/contacts/coordonnées GPS/employeurs factices ; `searchString` et
> `lastMessages` vidés car hors liste blanche). Seule la **structure** est fidèle.

## Enveloppe

```
GET /v1/public-api/rest/v2/profiles?limit=N   →   { "data": [ {profil}, … ], "total": 7148 }
GET …/profiles/{id}                            →   un profil (même forme)
```

Non paginé (cf. doc d'intégration) ; `total` = nombre filtré. Curseur d'ingestion = `updatedAt`.

## Le profil témoin (anonymisé)

- **Headline** : « Technicien spécialisé en Cloud computing et IT support »
- **CV** : `files[0]` parsé (`parsedAt`), alimente `positions` / `educations` / `skills` / `locations`.
- **Compte rendu d'entretien** : présent à **deux endroits** (constat structurant ci-dessous).

## Constat majeur — le compte rendu d'entretien n'est pas une entité

Il vit dans deux champs Jarvi, tous deux **opaques côté showcase-recruiter-webapp-saas** :

| Emplacement Jarvi | Forme | Contenu observé |
| --- | --- | --- |
| `fieldsValues[]` (et son miroir `customFieldsValues{}`) | custom field **« Récap Entretien »**, `type: richtext` (HTML) | synthèse structurée : dispo, prétentions, compétences, **« Avis recruteur »** |
| `manualHistoryEntries[]` | notes chronologiques typées (`subType`: `PHONECALL`, `Informations diverses`…) | « Entretien de 15 min. Candidat qualifié… Français 6/10… » + notes de shortlisting |

➜ Dans `candidate_jarvi`, ces deux blocs tombent **tels quels** dans des colonnes JSONB. showcase-recruiter-webapp-saas
ne les modélise pas champ par champ et ne les édite jamais.

> ⚠️ Le « Récap Entretien » et l'historique contiennent l'**avis subjectif du recruteur** et des
> données sensibles (niveau de langue, prétentions). **Décision (2026-07-02) : exposition recruteur
> uniquement** — **jamais côté candidat**. Base légale + rétention RGPD à formaliser avant ingestion prod.

## Mapping des 74 champs de premier niveau

### A. Profil pro — ingéré dans `candidate_jarvi` (réplica lecture seule)

| Champ Jarvi | Cible | Note |
| --- | --- | --- |
| `id` | `Candidate.jarviProfileId` (ancre) | clé d'upsert |
| `externalId` | `Person.uuid` (si inscrit) | sinon `null` (profil sourcé non inscrit) |
| `firstName`, `lastName` | `Person.givenName/familyName` | identité |
| `headline`, `summary` | `candidate_jarvi.headline` (jsonb/text) | accroche + résumé |
| `locations[]` | `Person.city/country` (lecture) + JSON | ville/pays ; **coordonnées GPS = bruit**, non retenues |
| `emailAddresses[]`, `phoneNumbers[]` | `candidate_jarvi` (JSON) | coordonnées — **dévoilées sur crédit** + snapshot ; inclut `whatsappIdentifier` |
| `positions[]` | `candidate_jarvi.workExperiences` (jsonb) | `title, companyName, startDate, endDate, source` |
| `educations[]` | `candidate_jarvi.educations` (jsonb) | `school.name, title, start/endDate` |
| `skills[]` | `candidate_jarvi.skills` (jsonb) | `{ name }` (jusqu'à 100+ par profil) |
| `locales[]` | `candidate_jarvi.languages` (jsonb) | `{ language, proficiency }` (`NATIVE_OR_BILINGUAL`…) |
| `files[]` | `candidate_jarvi.resumes` (jsonb, **métadonnées seules**) | `fileId, name, mimeType, parsedAt` — **binaire non copié**, servi à la demande via l'URL de download Jarvi (cf. § CV) |
| `lastPosition`, `lastResumeUpdatedAt` | JSON | dérivés pratiques |
| `profileUrl`, `publicIdentifier`, `linkedinId` | `Person.linkedinUrl` + JSON | `sameAs` |
| `image.publicUrl` | `candidate_jarvi.photoUrl` (text) | URL publique du storage Jarvi, **binaire non copié** — **dévoilée sur crédit**. `imageId` n'est PAS l'identifiant du fichier de cette URL : sans `publicUrl`, elle est irreconstructible |
| `birthday`, `primaryLocale`, `source` | JSON | source (`INDEED`/`LINKEDIN`…) |
| `createdAt`, `addedAt`, `updatedAt` | sync meta | `updatedAt` = **curseur d'ingestion** |

### B. Interactions / compte rendu d'entretien — ingérés comme payload sensible Jarvi

`historyEntries[]`, le custom field **« Récap Entretien »** (`fieldsValues[]`) et les autres champs
riches Jarvi sont conservés dans `candidate_jarvi.rawPayload`.

> **Décision révisée (2026-07-01)** : le **compte rendu Jarvi est ingéré** comme donnée maître Jarvi
> sensible dans `candidate_jarvi.rawPayload.historyEntries[]` / `fieldsValues[]`. Il n'est pas
> normalisé champ par champ, n'est jamais édité par showcase-recruiter-webapp-saas et ne devient pas
> `Candidate.starInterviewReport`.
>
> Les **custom fields Jarvi** ne portent pas les différenciants showcase-recruiter-webapp-saas en V1. Les différenciants
> restent locaux côté showcase-recruiter-webapp-saas ; s'ils apparaissent un jour dans Jarvi, ils ne devront pas être
> ré-ingérés comme vérité sans nouvelle décision d'architecture.

### C. Maître showcase-recruiter-webapp-saas — **jamais** ingéré depuis Jarvi

`returnHorizon`, `PresencePeriod` ★, `salaryExpected/Min`, `availability`, `geoMobility`,
**mobilité / pied-à-terre**, **projet Maroc**, `visibility`, compte (`User`), crédits / dévoilés /
favoris. → portés par `Candidate` (normalisé) + tables différenciantes, pas par `candidate_jarvi`.

### D. Métadonnées d'interface Jarvi — **liste noire** (jamais ingérées)

`searchString`, `lastMessages`, `unreadMessagesNotifications`, `pendingExecuteTriggerTasks`,
`distance`, `canSendInMail`, tous les `count*` (`countResumes`, `countMessages*`, `countApplications…`),
les flags Jarvi (`isTalent`, `isContact`, `isHiring`, `isInfluencer`, `isPremium`,
`isOpenToNewOpportunities`), `talentIdentifier`, `linkedinSalesNavigatorIdentifier`,
`linkedinSignificantIdentifier`, `firstName/lastNameParsed|Manual`, `groupId`, `memberId`, `userId`,
`createdByUserId`, `protectedCompaniesWorksIn`, `*InteractionDate`, `__typename`, `deletedAt`.

## Verdict de précision

- ✅ **`candidate_jarvi` opaque (JSONB) est le bon choix.** Le payload réel = **74 champs**,
  fortement imbriqués, truffés de `__typename` et de métadonnées d'interface. Le mapping normalisé du
  scaffold (`JarviClient`) ne couvre que **5 champs plats** et reste en `TODO`. Normaliser
  champ-par-champ serait fragile ; ingérer une **liste blanche d'objets** en JSONB est précis et
  robuste aux évolutions Jarvi.
- ✅ **Expériences / formations / langues / compétences / CV / récap entretien** existent bien et
  sont **structurés** → fondés à vivre en objets `candidate_jarvi`.
- ✅ **CV — résolu (2026-07-02)** : le binaire est **servi à la demande** par le storage Jarvi via
  `https://qimsyaozqntinmrokopq.storage.eu-west-2.nhost.run/v1/files/<fileId>` (le `fileId` provient de
  `files[]`). showcase-recruiter-webapp-saas **ne stocke aucune copie** — la règle « pas de copie » est **confirmée**.
  ⚠️ À valider à l'implémentation : le **mode d'authentification** de cette URL (publique par `fileId`
  vs jeton/JWT) → conditionne un éventuel **proxy côté API** pour ne pas exposer un accès direct non
  contrôlé au storage Jarvi.
- ✅ **Récap entretien / historique — décision révisée (2026-07-01)** : ingérés comme payload
  sensible maître Jarvi, non exposé par défaut et distinct du compte rendu Star showcase-recruiter-webapp-saas.
