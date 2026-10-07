# TASK 016 — Espace Candidat : édition des différenciants showcase-recruiter-webapp-saas

- **Créée le** : 2026-06-23.
- **Statut** : 🟢 édition locale partiellement implémentée (2026-06-24) ; **à réaligner** avec la
  décision du 2026-07-01 : pas de sync montante des données showcase-recruiter-webapp-saas vers Jarvi.
- **Type** : fonctionnalité P4 (cœur du self-service candidat).
- **Dépend de** : tâche `013` (projection + frontière maître). S'articule avec la tâche `033`
  (Magic Link candidat + rattachement/consentement).
- **Réf cadrage** : [`dev-book/cadrage/candidat/vue_d_ensemble.md`](../cadrage/candidat/vue_d_ensemble.md),
  [`docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md).

## Décision 2026-07-01

Les différenciants showcase-recruiter-webapp-saas restent **locaux uniquement**. Ils ne sont pas poussés en custom fields
Jarvi et ne déclenchent pas de cycle de sync montante.

Le profil professionnel (`CV`, expériences, formations, compétences, langues, headline, résumé) est
**maître Jarvi** et descend dans `candidate_jarvi` par ingestion (tâche `020`). showcase-recruiter-webapp-saas ne l'édite
pas comme vérité locale.

## Périmètre

- Éditer les différenciants showcase-recruiter-webapp-saas :
  - `returnHorizon`
  - `returnProject`
  - `salaryExpected`
  - `salaryMin`
  - `geoMobility`
  - `availability`
  - `hasPiedATerre`
  - `piedATerreCity`
  - `desiredSector`
  - `desiredFunction`
- Stocker ces données localement sur `Candidate` ou les sous-entités dédiées.
- Appliquer le voter self-only : un candidat n'édite que son propre profil.
- Rendre ces champs consommables par la recherche locale showcase-recruiter-webapp-saas.

## Hors périmètre

- `PresencePeriod` → tâche `017`.
- Visibilité et consentement → tâche `018` + tâche `033`.
- Magic Link candidat, invitation des profils importés, rattachement compte/profil → tâche `033`.
- Création initiale d'un profil Jarvi absent → tâche `033`.
- Profil pro Jarvi, CV, expériences, formations, langues, compétences → tâche `020`.

## Invariants

- **Aucune sync montante des données showcase-recruiter-webapp-saas** vers Jarvi.
- **Self-only** ; toute écriture passe par l'API Symfony.
- L'ingestion Jarvi ne doit jamais écraser les différenciants showcase-recruiter-webapp-saas.
- `availability` (préavis) reste distinct de `returnHorizon` (horizon de retour).

## Critères d'acceptation

- [ ] Les différenciants sont éditables et persistés localement.
- [ ] Aucune écriture Jarvi n'est déclenchée lors de l'édition des différenciants.
- [ ] Les champs showcase-recruiter-webapp-saas restent intacts après une ingestion Jarvi.
- [ ] Tests self-only et validations métier utiles.
- [ ] `make api-quality` vert au point de contrôle.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `dev-book/cadrage/candidat/vue_d_ensemble.md`
