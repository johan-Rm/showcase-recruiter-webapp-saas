# TASK API-037 — chercher un métier que le dictionnaire ignore

- **Créée le** : 2026-08-10.
- **Session** : `9d0eb3f2-2064-4ffd-94a9-dbc606ce1450`.
- **Statut** : ✅ fait le 2026-08-10 — un intitulé que le dictionnaire ignore est désormais
  cherchable et proposé ; 260 profils visibles n'entrent dans le critère métier que par ce chemin.
- **Type** : feat — nouvelle voie de recherche. Table nouvelle et index ; la réponse JSON des
  suggestions gagne une clé, sans que le contrat OpenAPI en rende compte (cf. décisions de cadrage).
- **Tests** : fonctionnels sur la recherche (un intitulé absent du dictionnaire ramène les profils
  qui le portent, et le repli ne se déclenche **pas** quand le dictionnaire répond) et sur
  l'auto-complétion (la seconde section n'apparaît qu'en l'absence de concept).
- **Réf** : amont `API-024` (les intitulés cherchés que le dictionnaire ignore), `API-035`
  (reprojection — même moment de calcul), task `095` (métiers exercés), `125` (expansion par le
  vocabulaire) · aval : aucune · `api/src/Repository/CandidateRepository.php` ·
  `api/src/Domain/Search/SuggestionFinder.php` · `api/src/Entity/CandidateJobExperience.php`.

## Contexte

Le critère « métier » ne sait chercher qu'à travers le dictionnaire. `CandidateRepository` compare le
terme au libellé canonique d'un concept ou à l'un de ses libellés vivants, **par égalité stricte**,
puis retient les profils rattachés à ce concept. Et `candidate_job_experience` ne stocke aucun
texte : rien qu'un identifiant de candidat et un identifiant de concept. Un intitulé que le
dictionnaire ne connaît pas ne produit donc **aucune ligne cherchable** — le profil qui le porte est
hors d'atteinte, définitivement, quoi que tape le recruteur.

Ce n'est pas un cas marginal. Mesuré sur le vivier de développement :

| | |
|---|---|
| profils visibles | 3 052 |
| **sans aucun métier rattaché** | **357 (12 %)** |
| intitulés de poste non classés dans le dictionnaire | 6 480 |
| dont termes n'apparaissant **qu'une fois** | 5 439 |
| part des occurrences couverte par les 500 termes les plus fréquents | 22 % |

La forme de cette distribution décide de la solution. Ce n'est pas un tas qu'on finit de ranger,
c'est une traîne : ranger les 500 premiers termes laisserait encore 78 % des occurrences dehors.
Aucune campagne de regroupement — manuelle ou assistée — ne fermera cet écart. Il faut un filet en
dessous du dictionnaire, pas un dictionnaire plus gros.

`API-036` en donne l'illustration : un architecte d'intérieur du vivier n'était rattaché à rien, et
il a fallu créer un concept pour lui. On ne peut pas créer un concept pour chacun des 5 439 termes
qui n'apparaissent qu'une fois.

## Périmètre

- **Rendre l'intitulé brut cherchable** : une table dédiée `(candidat, intitulé canonique)`,
  alimentée au même moment que les métiers exercés — à l'ingestion et à la reprojection —, avec un
  index trigramme posé par `app:search:index`.
- **Repli du filtre métier** : si le terme cherché ne correspond à **aucun** concept, la recherche
  interroge les intitulés bruts. Sinon, rien ne change.
- **Repli de l'auto-complétion** : une seconde section « intitulés exacts » listant les intitulés
  bruts portés par des profils visibles, sous les concepts.
- **Le compteur de volume et l'explication de correspondance** (`CriterionMatch`) suivent : un profil
  retenu par le repli doit savoir dire par quoi.

## Hors périmètre

- **L'expansion.** Le repli ne cherche pas de synonyme et ne traduit pas : c'est le rôle du
  dictionnaire, et c'est précisément ce qu'on n'a pas quand il ne connaît pas le terme. Le repli
  trouve ce qui est écrit, littéralement.
- **Le rangement du dictionnaire.** Le filet ne dispense pas de créer des concepts ; il fait que
  l'absence d'un concept cesse d'être une impasse.
- **L'écran d'administration des intitulés non résolus.** `app:vocabulary:missing` liste déjà en
  console ce que les recruteurs cherchent sans résultat (`API-024`) ; l'exposer à l'écran est un
  autre sujet, à carter séparément.
- **Les mots-clés (compétences).** Ils interrogent déjà les compétences réellement portées par les
  profils, pas des concepts : le manque décrit ici ne les concerne pas.
- **Le front de l'espace Entreprise.** La seconde section de la liste déroulante devra être
  distinguée à l'écran — c'est une tâche `ENT`, elle remonte, elle ne se traite pas ici.

## Décisions de cadrage

- **Le repli ne se déclenche qu'en l'absence de concept**, jamais en complément. C'est la règle déjà
  éprouvée du rattrapage aux fautes de frappe (`SuggestionFinder`, task `API-023`) : le cas nominal
  ne change pas, aucun bruit ne peut polluer une recherche qui marchait, et le coût n'est payé que
  lorsque le recruteur est déjà bloqué. L'union systématique aurait rendu le décompte annoncé
  inexplicable — un terme large ramènerait deux populations aux règles différentes.
- **L'auto-complétion propose aussi les intitulés bruts.** Sans ça, personne ne découvre le repli :
  la liste ne propose que des concepts, donc le recruteur ne saura jamais qu'il peut taper
  « architecte d'intérieur ». Une voie de recherche que rien n'annonce n'existe pas.
- **Une table dédiée plutôt qu'une colonne texte sur `candidate_job_experience`.** Cette table porte
  une ligne par **concept reconnu** : un intitulé non reconnu n'y a pas de ligne où s'écrire. Les
  deux objets ne comptent pas la même chose.
- **L'intitulé stocké est la forme canonique**, celle que produit `TermNormalizer::canonicalizeTitle()`
  — la même que voit la reconnaissance. Deux formes différentes du même intitulé compteraient deux
  fois, et l'écart entre ce que la recherche compare et ce que le dictionnaire compare deviendrait
  invisible.
- **Alimentation au même moment que les métiers exercés**, dans le même objet : une troisième source
  de vérité sur « ce que ce profil a fait » divergerait des deux autres. L'objet extrait par
  `API-035` s'appelle désormais `JobProjectionSynchronizer` — il pose deux projections, son ancien
  nom n'en annonçait qu'une.

### Écarts au cadrage, tranchés à l'implémentation

- **Les propositions portent leur nature (`kind`), ce que la carte n'avait pas prévu.** La carte
  demandait « une seconde section » à l'écran sans dire comment le front la reconnaîtrait : rien
  dans la réponse ne distinguait un métier du dictionnaire d'un intitulé écrit, et les deux ne
  promettent pas la même chose — l'un élargit aux synonymes, l'autre non. Sans cette clé, le
  recruteur croit choisir un métier du référentiel et l'absence de synonymes passe pour un défaut du
  moteur. `SuggestionKind` vaut donc `CONCEPT` ou `LITERAL`, et il est nommé par ce qu'il **promet**
  plutôt que par la table d'origine — la promesse reste vraie si la source change, et elle
  s'applique aussi aux compétences, qui se cherchent littéralement.
- **Le contrat OpenAPI n'en rend pourtant pas compte, et c'est un constat, pas un choix.** Comme
  pour la carte de recherche (`API-030`), les propositions n'existent pas en tant que schéma :
  `Suggestion.CollectionOutput` type ses `items` en `(string | null)[]`. Régénérer le contrat après
  l'ajout ne produit qu'un changement de description. La clé est donc **servie en JSON mais absente
  des types générés** ; son report côté front est manuel et appartient à l'espace Entreprise.
- **L'ordre des passages de l'auto-complétion se départage au NOMBRE DE MOTS.** La carte disait
  « le repli ne se déclenche qu'en l'absence de concept », sans distinguer la correspondance exacte
  du rattrapage aux fautes de frappe. Mesuré à l'implémentation, cette lecture rendait le filet
  inatteignable : le rattrapage compare le terme au meilleur **extrait** d'un libellé, donc
  « architecte d'intérieur » ressemblait assez à « architecte » pour remonter « Architecte
  (IT/logiciel) » — le contresens même que la tâche corrige. Inverser purement et simplement coûtait
  l'autre côté, mesuré aussi : « developeur » tombait sur l'unique profil ayant écrit « Dévelopeur
  backend » au lieu du concept à 294 profils. Le départage retenu tient en une phrase — **une faute
  de frappe déforme un mot, un métier plus précis en ajoute** : à partir de deux mots le filet passe
  devant, en dessous le rattrapage garde la priorité.

## Règles et cas limites

- **Volume** : 3 053 profils, environ 8 400 intitulés de poste distincts. L'index trigramme est
  nécessaire ; sans lui la recherche dégrade en `LIKE`, comme le reste de la recherche le fait déjà.
- **Terme très court** : le repli doit se refuser en deçà d'une longueur minimale, sinon « dev »
  ramène tout. `SuggestionFinder` porte déjà ce seuil pour le rattrapage aux fautes.
- **Profil masqué** : le repli ne change rien à la visibilité. Il ajoute une manière de trouver, pas
  un droit de voir.
- **Profil sans réplica Jarvi** : aucun intitulé, donc absent du repli. Ce n'est pas une régression,
  il n'était joignable par aucune voie.
- **Concurrence avec la reprojection** : la table se réécrit au même moment que les métiers exercés,
  donc les mêmes précautions s'appliquent.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` — le critère « métier »
  y est décrit comme passant par le dictionnaire ; il gagne une seconde voie.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` — la limite écrite en fin de page
  (« la longue traîne échappe au dictionnaire ») change de portée : elle reste vraie pour l'expansion,
  elle cesse de l'être pour l'atteignabilité.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` — la table nouvelle.

## Vérification

**Le cas qui a ouvert le sujet**, par appel HTTP réel authentifié en `entreprise@showcase-recruiter-webapp-saas.example`
(`ROLE_COMPANY_OWNER`), sur les 3 052 profils visibles du worktree :

| `GET /v1/search/candidates?job=…` | Avant | Après |
| --- | ---: | ---: |
| `architecte d'intérieur` | 0 | **2** |
| `interior architect` | 0 | **1** |
| `architecte paysagiste` | 0 | **1** |

**La non-régression, mesurée et non supposée.** `job=architecte` rend **37** profils — exactement le
nombre portant ce concept en base — alors que **31** profils ont un intitulé écrit contenant ce mot :
si le repli s'ajoutait, le total serait différent. `job=développeur logiciel` rend **294**, là encore
le compte exact du concept. Et `job=dev`, trop court et inconnu, rend **0** : le repli ne se
déclenche pas sous cinq caractères.

**L'ordre des trois passages de l'auto-complétion** (`GET /v1/suggestions?type=JOB`) :

```
q = archi                   → Architecte (IT/logiciel)                 CONCEPT
q = architecte d'intérieur  → architecte d'intérieur, …               LITERAL
q = interior architect      → interior architect, …                    LITERAL
q = developeur              → Développeur logiciel, …                  CONCEPT   ← rattrapage préservé
q = javascrip               → développeur javascript, …                LITERAL
q = soudeur                 → soudeur, alternant soudeur, …            LITERAL
```

**L'explication de correspondance**, sur une recherche enregistrée portant le critère
`job=architecte d'intérieur` :

```
Audrey → matchedOn: "architecte d'intérieur-chargé de projet"
Laïla  → matchedOn: "architecte d'intérieur"
```

Elle rend **ce que la personne a écrit**, plus long que ce qui a été tapé. Sur un terme connu du
dictionnaire, elle rend toujours le libellé du concept : `matchedOn: "Architecte (IT/logiciel)"`.

**L'amorçage du vivier déjà chargé** — `app:vocabulary:reproject`, sans aucun appel à Jarvi :

```
Reprojection : 3053 profils parcourus, 15421 ligne(s) de projection posée(s) ou retirée(s),
0 poste(s) actuel(s) rerattaché(s).
```

**Couverture obtenue**, lue en base : 2 697 profils visibles portent un métier reconnu (88 %),
2 957 portent un intitulé écrit (97 %), et **260 profils n'entrent dans le critère métier que par le
repli**.

**Tests impactés** — `php bin/phpunit --filter CandidateJobTitleFallbackTest --testdox` :

```
✔ An unknown title finds the profiles that wrote it
✔ A known term ignores the written titles entirely
✔ A too short term does not trigger the fallback
✔ The match reports the written title
✔ Written titles come before the typo rescue only for multi word terms

OK (5 tests, 12 assertions)
```

Voisins rejoués (`VocabularyReprojection`, `CandidateJobExpansion`, `JarviIngestion`, `SearchCounts`,
`CandidateJobTenure`) : `OK (32 tests, 172 assertions)`.

**Frontières de couche** — `composer deptrac` : `Violations 0`. **Analyse statique** — phpstan :
`No errors`.

**Contrat** — `api:openapi:export` régénéré et commité ; seules deux descriptions changent, la clé
`kind` n'y figurant pas (cf. décisions de cadrage). `webapp/shared/types/api.ts` régénéré dans le
même commit, pour que la gate `quality-contracts-check` reste verte.

**Non vérifié, et dit comme tel** : la dégradation en l'absence de `pg_trgm`. L'index n'accélère
qu'un `LIKE` qui fonctionne sans lui — c'est structurel, mais je ne l'ai pas éprouvé en désinstallant
l'extension.

## Livré

- `api/src/Entity/CandidateJobTitle.php` (+) — la table des intitulés écrits.
- `api/src/Entity/Candidate.php` (M) — la collection `jobTitles`, remplacée à chaque projection.
- `api/src/Domain/Jarvi/JobProjectionSynchronizer.php` (M, renommé depuis
  `JobExperienceSynchronizer`) — pose les DEUX projections, le sens et la lettre.
- `api/src/Domain/Jarvi/ProfileIngestor.php` · `VocabularyReprojector.php` ·
  `VocabularyReprojectReport.php` · `api/src/Command/VocabularyReprojectCommand.php` (M) — suivent
  le renommage et le compteur, qui couvre désormais les deux projections.
- `api/src/Repository/CandidateRepository.php` (M) — `applyJobFilter()` porte le dictionnaire et son
  repli dans une seule requête, avec le seuil de cinq caractères.
- `api/src/Domain/Search/SuggestionFinder.php` (M) — `jobTitles()`, et les trois passages de `jobs()`
  départagés au nombre de mots.
- `api/src/Domain/Search/CriterionMatcher.php` (M) — l'explication rend l'intitulé écrit.
- `api/src/Enum/SuggestionKind.php` (+) · `api/src/ApiResource/Output/SuggestionOutput.php` (M) ·
  `api/src/State/Provider/SuggestionProvider.php` (M) — la nature de chaque proposition.
- `api/src/Command/SearchIndexCommand.php` (M) — l'index trigramme sur `lower(title)`.
- `api/tests/Functional/CandidateJobTitleFallbackTest.php` (+) — cinq tests.
- `api/tests/Functional/VocabularyReprojectionTest.php` (M) — l'assertion suit le libellé du compte
  rendu, devenu « ligne(s) de projection ».
- `api/openapi.json` · `webapp/shared/types/api.ts` (M) — contrat et types régénérés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` ·
  `profile-processing.md` · `data-model.md` (M).

## Reste à faire (hors périmètre)

- **La seconde section à l'écran appartient à l'espace Entreprise.** La clé `kind` est servie, mais
  n'étant pas dans le contrat généré, son report dans les types front est **manuel** — même exception
  que la carte de recherche, décrite dans `data-contracts.md`. Tant qu'`ENT` ne l'a pas faite, le
  recruteur voit les intitulés écrits sans savoir qu'ils n'élargissent pas.
- **`app:vocabulary:missing`** liste déjà en console les intitulés cherchés sans résultat (`API-024`).
  Le filet en réduit le volume sans le vider : ce qui y reste mérite toujours un concept. L'exposer à
  l'écran reste à carter.
- **Les compétences, les entreprises et les formations** gardent le même manque : elles n'ont pas de
  filet. Le besoin n'est pas démontré, il n'est pas cardé.

## Définition de terminé

- [x] Taper un intitulé absent du dictionnaire ramène les profils qui le portent.
- [x] Taper un intitulé que le dictionnaire connaît donne **exactement** le même résultat qu'avant :
      le repli ne s'est pas déclenché — 37 et 294, les comptes exacts des concepts.
- [x] La liste déroulante montre les intitulés bruts, et seulement quand aucun concept ne correspond.
      La « seconde section » est en réalité une liste **homogène** portant `kind: LITERAL` : les
      passages ne se mélangent jamais.
- [x] Un profil retenu par le repli sait dire par quoi il a été retenu.
- [x] L'index trigramme est posé par `app:search:index` (5 index). Sa dégradation sans `pg_trgm` est
      **raisonnée, non éprouvée** — cf. Vérification.
- [x] Tests fonctionnels sur la recherche et sur l'auto-complétion.
- [x] Les trois pages de documentation à jour.
