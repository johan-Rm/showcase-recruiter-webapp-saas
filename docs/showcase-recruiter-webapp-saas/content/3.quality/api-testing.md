# showcase-recruiter-webapp-saas — Stratégie de tests de l'API

> Détaille les tests de l'`api/` (Symfony + API Platform). Complète
> [testing-and-ci.md](./testing-and-ci.md) (stratégie globale TDD/CI) et s'appuie sur
> [api-conventions.md](../2.architecture/api-conventions.md) et
> [data-integrity.md](../2.architecture/data-integrity.md).

## Deux niveaux complémentaires

- **Unitaire** — logique domaine pure (crédits, sync Jarvi, règles de visibilité). **Test-first**
  sur le cœur sensible (cf. TDD ciblé).
- **Fonctionnel** — **couverture exhaustive du contrat d'API** : **chaque route**, **chaque
  payload** (chaque règle de validation / chaque statut), **chaque réponse** (forme exacte).

> « Chaque payload » = chaque **chemin** (règle de validation, statut possible), **pas** le produit
> cartésien de toutes les combinaisons.

## Matrice par endpoint

Pour **chaque** endpoint :

| Dimension | Cas |
| --- | --- |
| Happy path | payload valide → statut (200/201/204) + **forme de réponse** (JSON Schema) |
| Auth | 401 sans token · 403 sans droit |
| Validation | payload invalide → 422 + violations (chaque champ requis / contrainte) |
| Not found | uuid inconnu → 404 |
| Règles métier | ex. reveal sans crédit → erreur · double reveal → idempotent · solde jamais négatif |
| i18n | `?locale` + fallback (ressources traduisibles) |
| Pagination / filtres / tri | enveloppe `{ items, total, page, perPage }`, bornes, tri, filtres |
| **Query-count (N+1)** | nombre de requêtes SQL ≤ budget (déterministe) |
| Format d'erreur | 4xx/5xx conformes (Problem Details / RFC 7807) |

## Dimensions transverses critiques

### Autorisation & isolation multi-tenant
- **Matrice de droits** : `ROLE_COMPANY_ADMIN` / `ROLE_COMPANY_RECRUITER` / `ROLE_ADMIN` /
  `ROLE_CANDIDATE`.
- **Isolation inter-entreprises** : l'`Organization` A ne voit **jamais** les dévoilés / crédits /
  favoris de l'`Organization` B. (Test le plus critique du multi-comptes.)
- **JWT** : token expiré, altéré, flux de refresh.

### Mass-assignment / over-posting (critique — crédits)
- Les champs protégés ne sont **jamais** modifiables via l'API : `balance`, `roles`,
  `organizationRole`, `jarviProfileId`, `creditWallet`, `syncStatus`. Test : envoyés dans un payload,
  ils sont **ignorés/rejetés**.

### Idempotence & concurrence (critique — crédits)
- **Reveal en double / en parallèle** → **un seul débit** (double-clic, race condition). Verrou
  optimiste sur le `CreditWallet`. Le solde reste cohérent.
- Réconciliation `balance == Σ CreditTransaction` après opérations concurrentes.

## Assertion des réponses — JSON Schema

- Chaque réponse est validée contre un **JSON Schema par ressource** (champs, types, requis) :
  contrat strict, toute dérive de forme casse le test.
- **JSON Schema généré par API Platform** (`assertMatchesResourceItemJsonSchema()` /
  `...CollectionJsonSchema()`), depuis les **mêmes métadonnées** que l'OpenAPI — **jamais écrit à
  la main**. Source unique = les Output DTO, qui alimentent tests + OpenAPI + types webapp
  (cf. [data-contracts.md](../2.architecture/data-contracts.md)).

## Organisation

- **`ApiTestCase` de base** : joue la batterie standard par ressource (auth 401/403, 404,
  validation, pagination, forme JSON Schema, query-count). Hérité par chaque ressource.
- Chaque ressource ajoute ses **cas métier** spécifiques.
- **Fixtures** : schéma recréé + fixtures chargées **par classe** ; jamais de données insérées à
  la main. `APP_ENV=test`, base `_test` (cf. testing-and-ci.md).
- Emplacement : `api/tests/Functional/{Resource}Test.php`, `api/tests/Unit/…`.

## Performance

- **Query-count (anti N+1)** — **déterministe, en CI**, intégré aux tests fonctionnels : un budget
  de requêtes SQL par endpoint ; un N+1 (tueur de perf des listes avec relations à plat) **casse
  le test**. C'est la mesure perf la plus rentable.
- Pas de budget de latence dans la suite fonctionnelle : l'horloge murale y serait flaky.

## Gates (rappel)

Suite fonctionnelle complète + query-count = **pré-merge** (cf. testing-and-ci.md). Unit rapides =
en continu.

## En résumé

- Fonctionnel **exhaustif par route** (happy + auth + validation + 404 + métier + i18n +
  pagination + query-count + format d'erreur).
- Réponses assertées par **JSON Schema** par ressource.
- **`ApiTestCase` de base** + cas métier par ressource.
- Dimensions critiques obligatoires : **isolation multi-tenant**, **mass-assignment**,
  **idempotence/concurrence** des crédits.
