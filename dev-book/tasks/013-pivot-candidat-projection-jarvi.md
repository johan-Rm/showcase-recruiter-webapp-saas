# TASK 013 — Pivot du modèle candidat : `Candidate` = projection Jarvi

- **Créée le** : 2026-06-22.
- **Statut** : ✅ implémenté (2026-06-23, commit local non poussé). _Vérifier la complétude du
  réalignement de 005/006/008 sur la projection._
- **Type** : correction de fondation (révise les tâches 002 / 005 / 006 / 008).
- **Contexte dev** : on est en développement, **base recréée à vide** à volonté → **aucune migration
  de données** à écrire. On reshape les entités + le seed, on relance. Pas de stratégie de bascule.

## Décision (cf. `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`, révision 2026-06-22)

**Jarvi est maître du profil pro et porte le vivier de recherche.** showcase-recruiter-webapp-saas est maître des
**différenciants**, des **comptes candidats** et des **interactions métier**. Le `Candidate` showcase-recruiter-webapp-saas
n'est **plus un miroir** du profil pro : c'est une **projection** ancrée sur `jarviProfileId`.

## Périmètre

- **Redéfinir `Candidate`** comme projection :
  - `jarviProfileId` (UUID) **unique, non nullable** = ancre/clé de la projection.
  - **Différenciants maîtres showcase-recruiter-webapp-saas** : `PresencePeriod`, `returnHorizon`, `salaryExpected`/`salaryMin`,
    `availability`, `geoMobility`, `visibility`, options premium/badges → **conservés**.
  - **Compte** : lien `Person`/`User` **optionnel** (présent pour les inscrits, absent pour un profil
    seulement matérialisé par interaction).
  - **Réplica pro en lecture seule** (décision 2026-06-23) : les tables structurées du profil pro
    (`work_experience`, `education`, `candidate_language`, `candidate_skill`, identité, CV, headline,
    secteur/fonction) sont **conservées** mais **reclassées** — elles ne sont **plus source de vérité
    éditable** côté showcase-recruiter-webapp-saas : **Jarvi reste maître**, et elles sont **alimentées par l'ingestion**
    (tâche `020`). Aucun endpoint d'édition pro showcase-recruiter-webapp-saas ne les écrit comme vérité.
  - **Snapshot figé** : en plus de la réplica (qui suit l'état courant), champs figés au **dévoilement**
    (`firstName`, `lastName`, `headline`, `location`, CV) — garantie « payé = conservé », historique, RGPD.
  - **Drapeau d'activité / périmètre** : porter l'état **actif / hors-périmètre** (désactivation à la
    sortie de périmètre, jamais de suppression — cf. task `020`).
- **Création de la projection** : (1) **ingestion de masse** du périmètre (tâche `020`) → la majorité
  des projections ; (2) à l'**inscription** candidat (différenciants + compte) ; (3) à la **première
  interaction** si absente (dévoilement / favori) → référence + snapshot.
- **Réaligner les interactions V1** : `RevealedProfile`, `Favorite`, `OrganizationStatus`
  référencent la projection `Candidate` ancrée sur `jarviProfileId` (unicité `(organization, candidate)`
  conservée). Au reveal/favori, **matérialiser** la projection si absente.
- **Pas de sync montante des différenciants** : les données maîtres showcase-recruiter-webapp-saas restent locales
  (présence Maroc, projet, prétentions, disponibilité, visibilité, consentement). Seule exception :
  si un candidat s'inscrit sans profil Jarvi existant, showcase-recruiter-webapp-saas crée un profil Jarvi minimal pour
  établir l'ancre `jarviProfileId`, puis le profil pro redescend par ingestion (cf. task `020`).
- **Seed/fixtures** : adapter — un `Candidate` de démo = `jarviProfileId` + différenciants (+ compte
  pour les inscrits) + réplica pro peuplée (en dev, via fixtures ; en réel, via l'ingestion `020`).

## Hors périmètre

- La recherche sur la réplica locale → **tâche 014**.
- Le **pipeline d'ingestion** (pull initial, incrémental `updatedAt`, polling, réconciliation,
  désactivation hors-périmètre) + le client Jarvi REST/GraphQL → **tâche 020**. 013 ne fait **aucun
  appel réseau** : en dev, la réplica est peuplée par **fixtures**.
- Le détail du client MCP → V2.
- Options premium candidat / Stripe → backlog `MODELE_COMMERCIAL_FUTUR`.

## Invariants à préserver

- **Cœur sensible crédits inchangé** (task 005) : débit atomique, idempotent, solde ≥ 0. Seule la
  **cible** du `RevealedProfile` change (projection ancrée sur `jarviProfileId`), pas la mécanique.
- **Filet de sécurité** : on ne matérialise/affiche que des profils issus du **périmètre Jarvi de
  showcase-recruiter-webapp-saas** (compte de sourcing isolé en prod).
- **RGPD (renforcé)** : avec l'ingestion de masse (`020`), on stocke d'emblée les données perso de
  **tout le périmètre** (pas seulement à l'interaction) → base légale + rétention + opposition
  deviennent un **prérequis** (item RGPD du backlog), pas un garde-fou de fin.

## Critères d'acceptation

- [ ] `Candidate` ancré sur `jarviProfileId` unique ; différenciants + compte optionnel + snapshot.
- [ ] Plus de champ profil pro traité comme **source de vérité éditable** côté showcase-recruiter-webapp-saas.
- [ ] `RevealedProfile`/`Favorite`/`OrganizationStatus` matérialisent la projection si
      absente, avec snapshot ; unicité `(organization, candidate)` OK.
- [ ] Tests 005 (crédits) toujours verts après réalignement de la cible.
- [ ] Fixtures/seed cohérents avec le nouveau modèle ; `make api-quality` vert.

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` : remplacer les bandeaux de révision par le modèle réaligné.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` : déjà révisé (référence).
