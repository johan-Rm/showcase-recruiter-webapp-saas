# Critères de recherche — inventaire 3 couches sur profil réel

> **Document de travail** (préparation réunion filtres). Analyse d'un même profil témoin à
> travers les 3 couches : **API Jarvi** (source) → **BDD showcase-recruiter-webapp-saas** (après import) →
> **API showcase-recruiter-webapp-saas** (ce que voit le recruteur). Objectif : lister **tous les critères
> disponibles** et en tirer une **première liste de critères pertinents**.
>
> Profil témoin : **Mehdi R. — « Ingénieur Méthodes Maintenance »**,
> `jarviProfileId = 1412952b-467c-43ab-913e-01bbe7999793`, uuid local
> `019f473c-e274-77c6-8ddc-6ac2ee2d5394` (analyse du 2026-07-09, base locale ré-importée le
> jour même — l'uuid `019f4207-…` de la veille n'existe plus).
>
> Complète (sans le remplacer) le canonique
> [criteres_de_filtrage_profils.md](../../../../dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md).

---

## Couche 1 — API Jarvi (GraphQL `ShowcaseRecruiterWebappSaasProfile`) : ce qui existe à la source

Champs effectivement ramenés par le client (`JarviClient::PROFILE_QUERY`) et leur contenu
réel sur le profil témoin. Liste exhaustive REST (74 champs) :
[profil-jarvi-reference.md](../../../../dev-book/cadrage/candidat/api/profil-jarvi-reference.md).

| Champ Jarvi | Contenu réel (Mehdi) | Potentiel critère |
| --- | --- | --- |
| `firstName` / `lastName` | Mehdi / Remli | identité (masquée avant dévoilement) |
| `headline` | « Ingénieur Méthodes Maintenance » | ✅ recherche libre |
| `summary` | texte riche (~950 car. : secteurs nucléaire/pharma/pétrochimie, outils GMAO, AMDEC…) | ✅ recherche libre |
| `skills[].name` | **20 compétences** (« Maintenance préventive », « AMDE », « Site seveso »…) | ✅ filtre compétences + FTS |
| `positions[]` (`title`, `companyName`, `startDate`, `endDate`) | **10 expériences** datées (Sanofi, CEA, TotalEnergies, SPIE…) | ✅ intitulé/entreprise + **années d'exp. dérivables** |
| `educations[]` (`school.name`, `degreeName`, `fieldOfStudy`, dates) | **7 formations** (Arts et Métiers, ESIPE…) | 🟡 diplôme/domaine |
| `locales[]` (`language`, `proficiency`) | en = `PROFESSIONAL_WORKING`, **fr = `proficiency: null`** | ✅ langue + niveau — ⚠️ piège : langue maternelle LinkedIn souvent **sans** proficiency |
| `locations[]` (`locality`, `countryName`, `countryShortName`, `formattedAddress`, `search`) | **`locality: null`**, `countryShortName: FR`, `formattedAddress: « Lyon Metropolis, France »`, `search: « Greater Lyon Area »` | ✅ pays fiable ; ⚠️ **ville non normalisée** (locality souvent vide) |
| `updatedAt` | 2026-05-15 | ✅ fraîcheur du profil |
| `countResumes` / `files[]` | 0 déclaré / **1 CV PDF présent** (« CV Mehdi REMLI .pdf », pas d'URL binaire) | 🟡 « CV disponible » — ⚠️ incohérence countResumes vs files |
| `emailAddresses[]` / `phoneNumbers[]` | 1 email / 0 tél | coordonnées (masquage serveur, jamais filtre) |
| `linkedinId` / `profileUrl` / `source` | 624495150 / URL LinkedIn / LINKEDIN | provenance, pas un filtre |
| `historyEntries[]` | 2 messages LinkedIn (dont : *« candidature Ingénieur Méthodes Maintenance, free-lance, au Maroc »*) | 🔵 mine d'or contextuelle (projet Maroc explicite !) mais **sensible/confidentialité** — pas un filtre V1 |
| `fieldsValues[]` (custom fields) | **vide** sur ce profil | 🔵 dépend de la saisie Jarvi, non fiable |

## Couche 2 — BDD showcase-recruiter-webapp-saas après import : ce qui est réellement stocké

### `person` (identité / localisation)

`given_name` ✔ · `family_name` ✔ · `country` ✔ (FR) · **`city` = NULL** (locality Jarvi vide) ·
`contact_email` = NULL (⚠️ l'email Jarvi n'est pas monté dans `person`) · `phone` = NULL ·
`linkedin_url` = NULL · `origin_locale` = fr.

### `candidate` (différenciants ⭐, maître showcase-recruiter-webapp-saas) — **TOUS VIDES à l'import**

| Colonne | Valeur après import | Remarque |
| --- | --- | --- |
| `return_horizon`, `return_project` | NULL | rempli par le candidat uniquement |
| `availability` | NULL | idem |
| `geo_mobility`, `has_pied_aterre` | **`false`** (NOT NULL) | ⚠️ faux ≠ « non renseigné » — indistinguable |
| `salary_expected/min` | **0 EUR** (NOT NULL) | ⚠️ 0 ≠ « non renseigné » — piège pour tout filtre salaire |
| `desired_sector`, `desired_function` | NULL | ⚠️ **jamais alimentés par l'import** — c'est ce constat qui a fait supprimer le référentiel : ce sont désormais des valeurs libres, à alimenter depuis Jarvi |
| `star_interviewed_at`, `consent_given_at` | NULL | posés par l'admin / le magic link |
| `visibility` / `active` / `sync_status` | VISIBLE / true / SYNCED | filtres implicites serveur |
| `created_at` | date d'import | base du filtre « nouveaux » |

### `candidate_jarvi` (réplica lecture seule)

`headline` ✔ · `summary` ✔ · `skills` ✔ (20) · `work_experiences` ✔ (10) · `educations` ✔ (7) ·
`languages` ✔ (2) · `resumes` ✔ · `contacts` ✔ · `raw_payload` (source, locations, linkedinId,
profileUrl, countResumes, fieldsValues, historyEntries) · `synced_at`.

### Tables liées (jointures possibles)

`presence_period` (**0 ligne** pour Mehdi) · `favorite` · `revealed_profile` ·
`candidate_assignment` · `profile_note` · `candidate_option` · `profile_view`.

## Couche 3 — API showcase-recruiter-webapp-saas (vue recruteur) : ce qui est exposé

**Détail** `GET /v1/candidates/{uuid}` (`CandidateOutput`) : id, givenName, **familyName tronqué
(« R. »)**, headline, summary, city, country, contactEmail/phone/linkedinUrl (**null tant que non
dévoilé**), desiredSector/Function (id+label), salaryExpected/Min, availability, returnHorizon,
geoMobility, skills (noms plats), languages, workExperiences, educations, resumes, presencePeriods,
isStar (+ starInterviewedAt/Report), revealed, createdAt/updatedAt, locale.

**Carte** `GET /v1/search/candidates` (`CandidateCardOutput`) : sous-ensemble sans summary ni
workExperiences/educations/languages — id, identité masquée, headline, **currentTitle**, city,
country, sector/function labels, salaires, availability, returnHorizon, geoMobility, piedATerre,
presencePeriods, skills, isStar, revealed, dates.

`currentTitle` est le poste ACTUEL dérivé du parcours (task `API-030`), servi **même masqué** — un
intitulé qualifie sans nommer ; l'employeur, lui, reste derrière le dévoilement et n'est pas sur la
carte. **Clé omise** et non `null` quand aucun poste n'est dérivable (27 % du vivier) : le
normaliseur de la carte supprime les nulls. À ne pas confondre avec `headline`, l'accroche que le
candidat s'écrit — 49 caractères en médiane, 128 au 90ᵉ centile, 752 au maximum.

⚠️ La carte n'expose **ni années d'expérience ni langues** — or le mockup Table v18 affiche une
colonne « Exp. » : donnée à dériver et à ajouter à la carte si le critère est retenu.

---

## Constats structurants

0. 🐛 **Coquille avérée — contacts perdus au dévoilement** : le mapper expose
   `person.contactEmail/phone/linkedinUrl` quand `revealed = true` (`CandidateMapper.php:84-86`),
   mais l'ingestion ne remplit **jamais** ces colonnes (`ProfileIngestor.php:145` : seulement
   nom/pays/ville) — 5/1697 emails et **0/1697 LinkedIn** en base, alors que la donnée existe dans
   `candidate_jarvi.contacts` (email Mehdi ✔) et `raw_payload.profileUrl` (LinkedIn ✔). **Un
   recruteur qui paie le dévoilement obtient des coordonnées null.** À corriger côté ingestion
   (monter contacts → person) ou côté mapper (lire la réplica).
1. **Sur un vivier importé, les filtres différenciants ⭐ ne matchent rien** : horizon retour,
   disponibilité, présence Maroc, salaire, secteur/fonction souhaités, mobilité, pied-à-terre sont
   tous vides/défaut tant que le candidat n'a pas complété son espace (magic link → édition
   différenciants, tasks 033/016). Le vivier réel au lancement = essentiellement des données Jarvi.
2. **Pièges de valeurs par défaut** : `salary = 0 EUR`, `geo_mobility = false`,
   `has_pied_aterre = false` sont NOT NULL — impossible de distinguer « non » de « non renseigné ».
   Tout filtre sur ces colonnes doit traiter le défaut comme « inconnu », pas comme une valeur.
3. **Poste encore exercé : couverture 51 %** (1557/3052 profils visibles portent un
   `current_job_concept_id` résolu ; 2222 ont un intitulé actuel brut, donc **665 se perdent** faute
   de retomber sur un métier du dictionnaire). Mesuré sur « Développeur logiciel » : 293 l'ont
   exercé, **90 le font encore, 92 sont passés à autre chose, 111 ne disent rien**. Les inconnus sont
   donc plus nombreux que ceux qui ont réellement bougé → **afficher sans filtrer**, même verdict que
   la ville ci-dessous et pour une couverture pire. Le levier qui rendrait un filtre honnête est
   l'amélioration de la résolution **à l'ingestion**, pas l'élargissement de la recherche.
4. **Ville actuelle : couverture ~71 %** (1202/1697 `person.city` remplis sur le vivier importé).
   `locality` Jarvi est parfois null (cas Mehdi : « Lyon Metropolis » seulement dans
   `formattedAddress`/`search`, non normalisés) → filtre ville utilisable mais **~29 % de
   profils invisibles** pour ce critère ; le pays est fiable.
4. **Données riches présentes mais non normalisées** : années d'expérience (dérivables des dates
   de `positions` — nécessaires à la colonne « Exp. » du mockup), niveau de langue
   (`proficiency: null` = langue maternelle implicite), CV disponible (`files` fiable,
   `countResumes` non), messages `historyEntries` (le projet Maroc de Mehdi y est **explicite** —
   inexploitable en filtre V1 pour raisons de confidentialité, mais à garder en tête pour V2).
5. **`desiredSector/Function` jamais alimentés par l'import** : les filtres secteur/fonction ✅ V1
   supposent soit une saisie candidat, soit une qualification admin/staff, soit un mapping à créer.

## Tableau complet — tous les filtres possibles côté showcase-recruiter-webapp-saas

Couverture mesurée le 2026-07-09 sur le vivier importé (1695 profils avec réplica ; les 3 comptes
seedés faussent marginalement les différenciants). **État API** = paramètre de
`GET /v1/search/candidates` (`CandidateSearchCriteria`).

| # | Domaine | Filtre | Donnée | Type | Couverture | État API |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Recherche | Recherche libre (FTS) | headline + summary + skills + expériences + formations | texte | headline 99,6 % · **summary 2 %** | ✅ `q` = LIKE sur headline + skills + `candidate_job_title` (`API-040`) · la correspondance dit **lequel** a répondu (`matchedIn`), et la Visionneuse l'affiche au survol du critère (`ENT-120`) · **summary exclu** : 2 % de remplissage et pas d'index trigramme, il coûtait à lui seul autant que tout le reste · employeurs et descriptions exclus — ils attendent le dévoilement |
| 2 | Métier | Compétences | `candidate_jarvi.skills` | multi-sélection | 92 % | ❌ |
| 3 | Métier | Intitulé de poste | `candidate_job_experience` (le sens) **+ `candidate_job_title`** (la lettre, `API-037`) | texte | 2 697/3 052 ont un métier reconnu (**88 %**) · 2 957 ont un intitulé écrit (**97 %**) → **260 profils** n'entrent dans le critère que par le repli | ✅ dictionnaire, **avec repli littéral** |
| 3b | Métier | **Poste encore exercé** | `candidate_jarvi.current_job_concept_id` + `current_position_certainty` | booléen | **51 %** résolus, dont 410 ambigus → **~38 % d'inconnus** sur la population concernée | ⚠️ **affiché, jamais filtré** — `matchedWhen` sur la correspondance (`API-026`) |
| 4 | Métier | Entreprise | `work_experiences[].companyName` | texte | 97 % | ❌ |
| 5 | Métier | Années d'expérience | dérivé des dates `positions` | plage numérique | 97 % — **à normaliser à l'ingestion** | ❌ |
| 6 | Métier | Diplôme / domaine d'études | `educations[].degreeName/fieldOfStudy` | texte | 93 % | ❌ (🔵 V2 canonique) |
| 7 | Langues | Langue parlée | `languages[].language` | multi-sélection | 88 % | ❌ |
| 8 | Langues | Niveau minimum | `languages[].proficiency` (échelle Jarvi/LinkedIn) | enum | 88 % — **null = maternelle** | ❌ |
| 9 | Qualité | CV disponible | `resumes` non vide | booléen | **3,5 %** | ❌ |
| 10 | Qualité | Fraîcheur Jarvi | `candidate_jarvi.synced_at` / `updatedAt` Jarvi | date | 100 % | ❌ (non exposé — cf. task 060) |
| 11 | Localisation | Pays actuel | `person.country` | enum ISO | 99 % | ✅ `country` |
| 12 | Localisation | Ville actuelle | `person.city` | texte | 71 % | ❌ |
| 13 | Différenciant | Secteur souhaité | `desired_sector` (valeur libre) | texte | ~0 % (3/1697) | ✅ `sector` |
| 14 | Différenciant | Fonction souhaitée | `desired_function` (valeur libre) | texte | ~0 % | ✅ `function` |
| 15 | Différenciant | Disponibilité | `availability` | enum (IMMEDIATE/NOTICE_1M/3M) | ~0 % | ✅ `availability` |
| 16 | Différenciant | Horizon de retour | `return_horizon` | enum (M3/M6/Y1/THINKING) | ~0 % | ✅ `returnHorizon` |
| 17 | Différenciant | Mobilité géographique | `geo_mobility` | booléen | ~0 % — **défaut `false` = inconnu** | ✅ `geoMobility` |
| 18 | Différenciant | Pied-à-terre au Maroc | `has_pied_aterre` (+ ville) | booléen | ~0 % | ✅ `hasPiedATerre` |
| 19 | Différenciant | Salaire souhaité / minimum | `salary_expected/min` (montant + devise) | plage | ~0 % — **défaut 0 EUR = inconnu** | ❌ (conflit multi-devises à trancher) |
| 20 | Différenciant | Ville/région souhaitée au Maroc | à modéliser | — | — | ❌ (🔵 V2) |
| 21 | Présence ⭐ | Présent actuellement au Maroc | `presence_period` couvrant aujourd'hui | booléen | ~0 % (3 périodes) | ⚠️ approché par `presenceWithinDays` |
| 22 | Présence ⭐ | Fenêtre glissante 15/30/60 j | `presence_period` | fenêtre | ~0 % | ✅ `presenceWithinDays` |
| 23 | Présence ⭐ | Ville de présence | `presence_period.city` | texte | ~0 % | ✅ `presenceCity` |
| 24 | Présence ⭐ | Plage de dates libre | `presence_period` | plage de dates | ~0 % | ❌ (🟡) |
| 25 | Qualité | Profil vérifié (star) | `star_interviewed_at` | booléen | ~0 % (1) | ✅ `star` |
| 26 | Qualité | Nouveaux profils | `candidate.created_at` | fenêtre | 100 % | ❌ |
| 27 | Entreprise | Favoris | `favorite` (portée Organization) | booléen | usage (1) | ❌ |
| 28 | Entreprise | Dévoilés / non dévoilés | `revealed_profile` | booléen/tri-état | usage (1) | ❌ |
| 29 | Entreprise | Assignés à moi / un collègue | `candidate_assignment` | sélection membre | usage (0) | ❌ (🟡) |
| 30 | Entreprise | Avec note | `profile_note` | booléen | usage | ❌ (🔵 V2) |
| 31 | Entreprise | Statut interne | `candidate_option` | enum | usage | ❌ (🔵 V2) |

Rappel : `visibility = VISIBLE`, `active = true`, `deletedAt IS NULL` = filtres **implicites
serveur**, jamais exposés. Pagination `page`/`perPage` hors critères.

**Lecture du tableau** : 11 filtres implémentés côté API mais 9 d'entre eux portent sur des
données à ~0 % de couverture (différenciants/présence) ; à l'inverse, les données massivement
présentes (compétences 92 %, expériences 97 %, langues 88 %, ville 71 %, nouveaux 100 %) n'ont
**aucun filtre**. Le FTS cible summary (2 %) : le signal réel est dans skills + positions.

## Première liste de critères pertinents (proposition à discuter)

### Groupe A — opérationnels dès l'import (la donnée existe pour ~tout le vivier)

| # | Critère | Source | Note |
| --- | --- | --- | --- |
| A1 | **Recherche libre (FTS)** | headline + summary + skills + positions + educations | le cœur ; nécessite l'index tsvector (aujourd'hui : LIKE headline seul) |
| A2 | **Compétences** | `candidate_jarvi.skills` | riche (20+/profil) |
| A3 | **Poste / entreprise** | `positions.title` / `companyName` | couvert par A1 ou facettes dédiées |
| A4 | **Années d'expérience** | dérivé des dates `positions` | **à normaliser à l'ingestion** (colonne calculée) |
| A5 | **Langues (+ niveau)** | `locales` | règle : proficiency null = maternelle |
| A6 | **Pays actuel** | `person.country` | fiable ; ville = afficher sans filtrer (V1) |
| A7 | **Nouveaux / fraîcheur** | `created_at` / `updatedAt` Jarvi | compteur « nouveaux » de la console |
| A8 | **CV disponible** | `files` non vide | secondaire |

### Groupe B — différenciants ⭐ (s'activent avec l'onboarding candidat)

Horizon de retour · présence Maroc (actuelle / 15-30 j / ville) · disponibilité ·
mobilité géographique · pied-à-terre · salaire (**après** décision sur la sémantique du 0 et
le multi-devises) · secteur/fonction souhaités (**après** décision sur qui les remplit) ·
profil vérifié/star (posé par l'équipe showcase-recruiter-webapp-saas, indépendant de l'onboarding).

**Recommandation UI** : n'afficher un filtre B que si la donnée existe dans le vivier (ou
afficher son taux de couverture) — sinon 0 résultat garanti et frustration recruteur.

### Groupe C — portée entreprise (jointures, indépendant du profil)

Favoris (`favorite`) · déjà dévoilés / non dévoilés (`revealed_profile`) ·
assignés à moi / un collègue (`candidate_assignment`, 🟡).

### Écartés V1

Formations comme facette dédiée (couvert par A1) · custom fields Jarvi (non fiables, vides) ·
historyEntries (confidentialité) · ville actuelle en filtre dur (donnée trop lacunaire) ·
statut interne / notes (V2, cf. canonique §11).
