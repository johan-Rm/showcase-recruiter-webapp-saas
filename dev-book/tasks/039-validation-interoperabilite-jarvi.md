# TASK 039 — Validation de l'interopérabilité Jarvi

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à valider (implémentation largement livrée).
- **Type** : Phase 1 — Finalisation du socle technique (passe de validation).
- **Valide** : tâches `020` (ingestion du périmètre) et `034` (import CLI robuste).
- **Réf ClickUp** : https://app.clickup.com/t/869e0jfzm
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/import_profils_jarvi.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`

## Contexte

L'interopérabilité Jarvi est livrée (CLI d'import, réplica opaque, robustesse `034`). Cette
tâche en vérifie le bon fonctionnement bout en bout avant de figer le socle.

## À valider

- [ ] CLI `app:jarvi:profiles:import` (`--full/--since/--profile-id/--dry-run/--limit/--reconcile`),
      client GraphQL, `ProfileIngestor`, `PerimeterReconciler`.
- [ ] Réplica **opaque** `candidate_jarvi.rawPayload` (provenance : `source`, `linkedinId`,
      `countResumes`, `historyEntries`, `fieldsValues`).
- [ ] Robustesse `034` : try/catch par profil, curseur figé au dernier profil sain,
      `IngestionReport.errors`.
- [ ] Contrats read/write : le write n'accepte **ni custom fields ni headline** → pas de sync
      montante des différenciants.

## Reste (backlog, hors validation)

- Retry/backoff, Scheduler/Messenger async, refresh on-demand au dévoilement, quota.

## Invariants

- Jarvi maître du profil pro ; réplica locale en lecture seule ; import idempotent.
