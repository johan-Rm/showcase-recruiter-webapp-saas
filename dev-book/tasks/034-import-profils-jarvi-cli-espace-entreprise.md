# TASK 034 — Import CLI des profils Jarvi pour le vivier entreprise

- **Créée le** : 2026-07-01.
- **Statut** : ✅ terminé (RGPD à trancher avant ouverture recruteurs — cf. points de vigilance).
- **Type** : Phase 2 — Espace entreprise / socle vivier candidats.
- **Dépend de** : tâche `013` (projection candidat), tâche `014` (recherche locale), tâche `020`
  (ingestion Jarvi), tâche `033` (consentement candidat).
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/import_profils_jarvi.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`

## Décision API Jarvi

Utiliser **GraphQL comme source primaire** pour l'import.

Tests réels du 2026-07-01 :

- projet showcase-recruiter-webapp-saas Jarvi : `00000000-0000-4000-8000-000000000001` ;
- total observé : **1676 profils** ;
- REST `/profiles` : `limit=100` OK, `limit=500/1000` en HTTP 500 sur le filtre projet ;
- REST `updatedAfter` non fiable sur le filtre projet testé ;
- GraphQL : `profiles_aggregate` OK, `limit=500` OK avec le shape complet d'import, incrémental
  `updatedAt > cursor` OK. `limit=1000` est réservé aux requêtes légères ; avec les relations utiles
  il renvoie HTTP 500.

Conséquence : import initial en **4 pages GraphQL** (`500 + 500 + 500 + 176`) ; synchro journalière via GraphQL
avec `where: {_and: [{associations: {projectId: {_eq: $projectId}}}, {updatedAt: {_gt: $cursor}}]}`.

## Contexte

L'espace entreprise doit exploiter un vivier de profils sans interroger Jarvi en live à chaque
recherche. Le vivier showcase-recruiter-webapp-saas est donc alimenté par un import CLI des profils Jarvi vers la base
locale.

## Objectif

Créer une commande CLI Symfony permettant d'importer, rafraîchir et réconcilier les profils Jarvi
dans showcase-recruiter-webapp-saas de façon idempotente, relançable et observable.

## Périmètre

- Créer une commande CLI côté `api/`, nom indicatif :

```bash
bin/console app:jarvi:profiles:import
```

Implémentation démarrée :

- `bin/console app:jarvi:profiles:import --full --limit=500`
- `bin/console app:jarvi:profiles:import`
- `bin/console app:jarvi:profiles:import --since=2026-07-01T00:00:00+00:00`
- `bin/console app:jarvi:profiles:import --profile-id=<uuid>`
- `bin/console app:jarvi:profiles:import --dry-run --limit=10`
- `bin/console app:jarvi:profiles:import --reconcile`

- Supporter les modes :
  - import complet ;
  - import incrémental GraphQL via curseur `updatedAt` ;
  - import ciblé par `jarviProfileId` ;
  - dry-run ;
  - réconciliation des profils sortis du périmètre.
- Upsert des projections :
  - créer ou retrouver `Candidate` par `jarviProfileId` ;
  - créer ou mettre à jour `CandidateJarvi` ;
  - créer un `Person` minimal si nécessaire ;
  - ne pas créer de `User`.
- Persister le curseur d'import et les métriques utiles via `JarviSyncState` ou équivalent.
- Appliquer la liste blanche des champs Jarvi à importer.
- Conserver la provenance Jarvi des blocs importés (`source` globale, sources par bloc, CV parsé,
  LinkedIn présent) dans la réplica pour distinguer les profils LinkedIn only, CV only, hybrides et
  manuels.
- Conserver le nom du fichier CV dans `candidate_jarvi.resumes[].name` quand Jarvi l'expose.
- Conserver les comptes rendus et notes maîtres Jarvi dans `candidate_jarvi.rawPayload` :
  `historyEntries[]` pour l'historique chronologique et `fieldsValues[]` pour les champs riches
  comme « Récap Entretien ». Ces données restent sensibles, non éditées et non exposées par défaut.
- Marquer les profils sortis du périmètre comme inactifs sans suppression.
- Préserver toutes les données maîtres showcase-recruiter-webapp-saas.

## Hors périmètre

- Recherche recruteur et filtres UI → tâche `014`.
- Magic Link candidat, invitation et consentement → tâche `033`.
- Édition des différenciants showcase-recruiter-webapp-saas → tâche `016`.
- Workflow de dévoilement, crédits, favoris → tâches entreprise dédiées.
- Recherche IA MCP → V2.

## Invariants

- Jarvi reste maître du profil professionnel.
- showcase-recruiter-webapp-saas importe une réplica locale en lecture seule.
- Aucune donnée maître showcase-recruiter-webapp-saas n'est écrasée par l'import.
- Aucun compte candidat n'est créé par l'import.
- Import idempotent : relancer la commande ne crée pas de doublons.
- Clé privée Jarvi côté `api/` uniquement.
- Un profil importé sans consentement ne doit pas être exposé aux recruteurs sans règle RGPD validée.

## Critères d'acceptation

- [x] La CLI peut importer un lot complet de profils Jarvi.
- [x] La CLI peut reprendre en incrémental depuis un curseur `updatedAt` avec GraphQL.
- [x] La CLI peut rafraîchir un profil unique par `jarviProfileId`.
- [x] La CLI dispose d'un mode dry-run sans écriture.
- [x] L'upsert est ancré sur `jarviProfileId` et ne duplique pas les profils.
- [x] Les données `candidate_jarvi` sont mises à jour sans écraser les différenciants showcase-recruiter-webapp-saas.
- [x] Les profils sortis du périmètre sont désactivés, jamais supprimés automatiquement.
- [x] La provenance Jarvi utile est conservée pour qualifier le type de profil.
- [x] Le nom du fichier CV est conservé dans les métadonnées CV.
- [x] Les comptes rendus / champs riches Jarvi sont conservés comme payload sensible non exposé.
- [x] Les métriques d'exécution sont lisibles : créés, mis à jour, ignorés, en erreur, curseur.
- [x] Les erreurs partielles sont journalisées et n'empêchent pas la reprise (curseur figé au dernier profil sain).
- [x] Tests ciblés : import complet, incrémental, idempotence, profil ciblé, dry-run, réconciliation.

## Points de vigilance

- **RGPD** : cadrer la base légale avant production.
- **Quota Jarvi** : limiter les appels et prévoir `--limit` pour les runs contrôlés.
- **Mapping Jarvi** : partir d'une liste blanche, jamais du payload complet.
- **Observabilité** : la CLI doit être exploitable par un humain en environnement de production.

## Docs à tenir à jour

- `dev-book/cadrage/entreprise/api/import_profils_jarvi.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`
