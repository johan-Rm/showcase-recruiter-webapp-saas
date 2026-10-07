# TASK 153 — api : import Jarvi `--full` par lots, sans charger tout le périmètre en mémoire

- **Créée le** : 2026-07-27.
- **Statut** : ✅ implémenté le 2026-07-27 — suite API 337/339, phpstan, cs-fixer, deptrac et
  `lint:container` verts. Reste le rejeu réel de `--full` sur preprod.
- **Type** : Back — API Symfony (correctif mémoire, CLI d'ingestion).
- **Réf** : `app:jarvi:profiles:import --full` sur preprod (VPS).

## Contexte

`make preprod-api CMD="app:jarvi:profiles:import --full"` meurt en fatal PHP :

```
Allowed memory size of 134217728 bytes exhausted … in doctrine/orm/src/UnitOfWork.php on line 642
```

L'image preprod (`api/Dockerfile.prod`) laisse le `memory_limit` par défaut de PHP, soit 128 M —
seule l'image dev le relève à 512 M. Deux accumulations remplissaient ce budget :

1. `JarviClient::fetchProfiles()` paginait par 500 mais **empilait** tous les profils dans un
   tableau et ne rendait la main qu'à la fin : le périmètre entier (~2 500 profils, avec parcours,
   formations, langues, compétences et payload brut) vivait en mémoire avant la moindre écriture.
2. `ProfileIngestor::ingestProfiles()` faisait un **unique `flush()`** après la boucle. L'Unit of
   Work retenait donc les ~2 500 × (Person + Candidate + CandidateJarvi + trois collections),
   **plus** une copie des données d'origine de chacune pour le calcul du change-set — c'est cette
   copie, dans `computeChangeSet()`, qui a fait sauter la limite.

Le mode incrémental quotidien ne le voyait pas : il ne traite qu'une poignée de profils.

## Livré

- Le contrat de lecture devient un **flux** : `JarviClientInterface::fetchProfiles()` retourne un
  `iterable`, implémenté en générateur qui `yield` page par page. Une page reste vivante le temps
  d'être consommée, plus le périmètre entier.
- L'ingestion **écrit par lots** : `flush()` + `clear()` tous les 100 profils (`batchSize`,
  paramètre de constructeur). Le curseur de synchronisation est relu **après** la boucle, sinon le
  `clear()` détacherait l'entité chargée avant.
- `fetchProfilesUpdatedSince()`, sans appelant dans `src/`, est retirée de l'interface : elle
  déléguait à `fetchProfiles()` et n'aurait survécu au passage en flux qu'en le rematérialisant.
- `Makefile.preprod` donne 512 M à la CLI seule (`php -d memory_limit=…`) : une commande de lot
  décode des pages entières de JSON Jarvi, alors que le runtime web garde le défaut.

## Décision

- **Vider l'Unit of Work, et pas seulement flusher.** Flusher sans `clear()` écrit mais ne libère
  rien : Doctrine garde entités et copies d'origine. C'est le `clear()` qui borne la mémoire.
- **Dernier lot laissé attaché.** La boucle vide entre les lots ; le `flush()` final ne vide pas.
  Sur un import court (tests, `--limit`), l'appelant garde ainsi des entités gérées, comme avant.
- **`refreshOne()` intouchée.** Elle sert le rafraîchissement à la volée au dévoilement, dans un
  contexte web : y vider l'Unit of Work détacherait les entités de la requête en cours.
- **Aucun garde-fou ajouté sur l'échec de flush.** Si un lot échoue, l'EntityManager se ferme et la
  commande sort en erreur, comme avant. La différence : les lots précédents sont déjà écrits, sans
  que le curseur avance — le passage suivant les rejoue, et l'upsert est idempotent.
- **`PAGE_SIZE` inchangée (500).** Le pic de décodage d'une page passait déjà dans 128 M ; la
  réduire aurait multiplié les allers-retours HTTP sans preuve de gain.

## Critères d'acceptation

- [x] `--full` ne retient plus que le lot courant : `assertLessThan(10, UnitOfWork::size())` après
      cinq profils par lots de deux — 16 entités retenues sans les lots, vérifié en inversant.
- [x] Le curseur de synchronisation est persisté malgré les `clear()` intermédiaires.
- [x] Import idempotent au rerun, doublons de lot ignorés, dry-run inchangés (tests existants).
- [x] `JarviIngestionTest` (14), `JarviClientMappingTest` (2), `CandidateJarviSubmissionTest`.
- [x] Suite API complète : 339 tests, 2 échecs — `CandidateTest::testSearchFilterByCountry` et
      `SearchListsTest::testShortlistingARevealedProfileSucceedsAndIsIdempotent`, tous deux en 401
      JWT et **verts en run isolé** : le flake d'ordonnancement déjà connu sur les tests de
      recherche, sans rapport avec ce diff.
- [x] phpstan, cs-fixer, deptrac, `lint:container` verts.
- [ ] Rejeu réel de `--full` sur preprod.

## Fichiers

- (M) `api/src/Domain/Jarvi/JarviClientInterface.php`
- (M) `api/src/Domain/Jarvi/JarviClient.php`
- (M) `api/src/Domain/Jarvi/ProfileIngestor.php`
- (M) `api/tests/Functional/JarviIngestionTest.php`
- (M) `api/tests/Functional/CandidateJarviSubmissionTest.php`
- (M) `api/tests/Unit/JarviClientMappingTest.php`
- (M) `Makefile.preprod`
- (+) `dev-book/tasks/153-api-import-jarvi-memoire-lots.md`
