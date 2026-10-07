# TASK 020 — Ingestion du périmètre Jarvi (réplica locale en lecture seule)

- **Créée le** : 2026-06-23.
- **Statut** : 🟢 cœur livré via la **tâche 034** (2026-07-01) — client GraphQL, upsert idempotent
  ancré `jarviProfileId`, pull initial + incrémental `updatedAt` avec curseur, réconciliation
  (désactivation/réactivation), `syncStatus`/`syncedAt` + comptage des erreurs. **Reste** : résilience
  client (retry/backoff), synchro asynchrone Scheduler/Messenger, refresh on-demand branché au
  dévoilement, budget quota. **Prérequis RGPD** maintenu avant prod.
- **Type** : socle d'intégration (le pipeline qui alimente la réplica locale).
- **Dépend de** : tâche `013` (Candidate = projection + tables réplica + drapeau d'activité) +
  **client Jarvi en lecture** (REST `where` + curseur `updatedAt` ; **GraphQL** pour la sélection de
  champs en masse). Débloque la **recherche locale** (tâche `014`).
- **Réf** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` (révision 2026-06-23, § « Réplica locale du
  périmètre »).

## Décision (cf. `jarvi-integration.md`, révision 2026-06-23)

showcase-recruiter-webapp-saas **rapatrie en masse** le profil pro de **tout le périmètre Jarvi de showcase-recruiter-webapp-saas** dans une
**réplica locale en lecture seule**. **Jarvi reste maître** du profil pro (jamais édité localement
comme vérité) ; la recherche tourne ensuite en local (quota 500 req/j dégagé). Les différenciants
restent maîtres showcase-recruiter-webapp-saas et **ne montent pas** vers Jarvi.

## Périmètre

- **Client Jarvi lecture** (clé privée `X-API-KEY`, **côté `api/` uniquement**) :
  - **GraphQL `POST /graphql`** comme chemin primaire : filtre projet, pagination `limit/offset`,
    agrégat `count`, sélection des champs utiles et incrémental `updatedAt > cursor`.
  - REST `GET /rest/v2/profiles` uniquement en fallback ponctuel si nécessaire.
  - Résilient : retry, backoff, ne casse pas si Jarvi indisponible.
- **Pull initial** : ingestion complète du périmètre (compte de sourcing showcase-recruiter-webapp-saas). Test réel
  2026-07-01 : 1676 profils, importable en 4 pages GraphQL de 500 avec le shape complet.
- **Incrémental** : rafraîchissement via le curseur **`updatedAt`** (confirmé dispo) — ne tire que
  les profils modifiés depuis le dernier passage ; persister le **curseur de dernier run**.
- **Synchro quotidienne globale** via **Symfony Scheduler / Messenger** (asynchrone, idempotente),
  hors environnement de développement.
- **Refresh on-demand** : rafraîchir **un** profil au **dévoilement** (1 requête), fraîcheur garantie
  là où c'est payant.
- **Upsert idempotent** ancré sur `jarviProfileId` ; remplit la réplica pro (identité, CV,
  expériences, formations, langues, compétences, localisation, headline) + le **snapshot** d'affichage.
- **Liste blanche de champs** (mapping config Jarvi → colonnes showcase-recruiter-webapp-saas) : ignorer les **métadonnées
  d'interface Jarvi** (`searchString`, `taskId`, étapes de pipeline, tags internes…).
- **Réconciliation quotidienne** : récupérer la liste des IDs du périmètre ; les profils **sortis du
  périmètre** sont **désactivés** (drapeau actif/hors-périmètre, task 013) — **jamais supprimés**
  (historique, dévoilés payés, preuve RGPD) ; symétriquement, un profil **revenu** dans le périmètre
  est **réactivé**. _(Désactivation + réactivation implémentées et testées, 2026-06-23.)_
- **Statut de sync** : `syncStatus` (PENDING/SYNCED/FAILED) + `syncedAt` par profil ; observabilité
  (compteurs, échecs).

## Hors périmètre

- **Recherche** sur la réplica → tâche `014` (consommateur).
- **Création initiale d'un profil Jarvi absent à l'inscription** → tâche `033`.
- **Édition des différenciants showcase-recruiter-webapp-saas** → tâche `016`, local uniquement.
- **Recherche IA / client MCP** → V2.
- **Webhooks Jarvi** (push événementiel) → option V2 si Jarvi les expose ; V1 = polling.

## Invariants

- **Clé privée Jarvi côté `api/` uniquement** ; jamais exposée au webapp.
- **Réplica en lecture seule** : l'ingestion ne fait qu'écrire la copie locale ; **aucune** édition
  locale du profil pro ne remonte comme vérité (Jarvi maître).
- **Aucune donnée maître showcase-recruiter-webapp-saas** (`PresencePeriod`, visibilité, consentement, projet, prétentions)
  n'est écrasée par l'ingestion Jarvi.
- **Sortie de périmètre = désactivation**, jamais suppression.
- **Périmètre isolé** : on n'ingère que le compte de sourcing showcase-recruiter-webapp-saas (filet de sécurité).

## ⚠️ Prérequis RGPD (bloquant fonctionnel)

Stocker d'emblée les données perso de tout le périmètre impose de cadrer **base légale**, **durée de
rétention**, **information / opposition / effacement** **avant** la mise en prod de l'ingestion.
→ item RGPD `TODO.md`. La V1 technique peut être développée en dev (base à vide) en parallèle du cadrage.

## Critères d'acceptation

- [ ] Client Jarvi lecture GraphQL résilient ; clé privée côté `api/`.
- [x] Pull initial + incrémental GraphQL `updatedAt` ; curseur persistant ; upsert idempotent sur `jarviProfileId`. _(034)_
- [x] Mapping liste blanche : métadonnées d'interface Jarvi non ingérées. _(034)_
- [ ] Synchro quotidienne globale (Scheduler/Messenger, hors dev) + refresh on-demand au dévoilement.
- [x] Réconciliation : hors-périmètre **désactivés** (non supprimés) **et retours réactivés** — fait + testé.
- [ ] `syncStatus`/`syncedAt` reflètent l'état ; échecs tracés + retry ; budget requêtes sous quota.
- [ ] `make api-quality` vert ; tests (incrémental, idempotence, désactivation, isolation périmètre).

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` : déjà révisé (référence) ; préciser le mapping final.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` : drapeau d'activité + statut sync sur la projection.
- `dev-book/cadrage/TODO.md` : item RGPD (prérequis prod).
