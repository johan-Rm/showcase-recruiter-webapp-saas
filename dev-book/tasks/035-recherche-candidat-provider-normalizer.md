# TASK 035 — Recherche candidat : provider, repository, normalizer + tests

- **Créée le** : 2026-07-06.
- **Statut** : 🚧 en cours (code en working tree, PR `feat/api-recherche-candidat`).
- **Type** : Phase 2 — Espace entreprise / socle vivier candidats.
- **Évolution de** : tâche `014` (recherche candidats sur la réplica locale).
- **Dépend de** : tâche `013` (projection candidat), tâche `020`/`034` (ingestion Jarvi).
- **Réf ClickUp** : https://app.clickup.com/t/869e0jkxy
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/recherche_et_masquage.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`

## Contexte

La tâche `014` a posé la recherche sur la réplica locale. Cette tâche en prolonge
l'implémentation côté API : provider, critères, repository et projection de sortie
dédiée, sans re-trancher la décision « filtres front vs API » (voir 014).

## Objectif

Fournir un endpoint de recherche candidat exploitable par l'espace entreprise, servant
des cartes masquées du périmètre, avec une sérialisation de sortie propre.

## Périmètre

- `CandidateSearchProvider` / `CandidateSearchCriteria` : critères + orchestration du
  provider API Platform.
- `CandidateRepository` : requêtes de recherche/filtrage sur la réplica.
- `CandidateMapper` + `CandidateOutputNormalizer` (nouveau) : projection dédiée (carte
  candidat + fiche) sérialisée via normalizer.
- `ProfileIngestor` : alimentation des champs exploités par la recherche.
- Tests fonctionnels : `CandidateTest`, `JarviIngestionTest`.

## Invariants

- Masquage / visibilité / candidats actifs restent garantis **côté serveur**.
- Jarvi reste maître du profil professionnel ; la recherche lit la réplica locale.

## Critères d'acceptation

- [ ] La recherche renvoie les cartes masquées du périmètre.
- [ ] La sérialisation de sortie passe par le normalizer dédié.
- [ ] Les invariants masquage/visibilité/actifs sont couverts par des tests.
- [ ] Gate `api-quality` vert.

## Hors périmètre

- UI de recherche et filtres (webapp) → tâche `038` (mockup) puis `043` (branchement API).
- Recherche assistée par IA → cadrage `entreprise/recherche_assistee_ia.md` (V2).
