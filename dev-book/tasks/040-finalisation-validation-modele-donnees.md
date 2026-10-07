# TASK 040 — Finalisation et validation du modèle de données

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à valider.
- **Type** : Phase 1 — Finalisation du socle technique (passe de validation).
- **Valide** : tâches `002` (modèle de données) et `013` (projection candidat Jarvi).
- **Réf ClickUp** : https://app.clickup.com/t/869e0jfze
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`

## Contexte

Les entités et contraintes sont en place. Cette tâche fige le modèle et corrige la doc.

## À valider

- [ ] Entités `User` / `Person` / `Candidate` / `Recruiter` + IDs hybrides + **soft delete effectif**
      (filtre Doctrine global). Le référentiel `CategoryCode` a été supprimé le 2026-07-13.
- [ ] Intégrité BDD (contraintes, FK, unicité) et règle JSON (réplica Jarvi opaque).
- [ ] `Candidate` = projection ancrée sur `jarviProfileId` (réplica lecture seule + snapshot
      + drapeau d'activité).
- [ ] Frontière différenciants showcase-recruiter-webapp-saas (local) vs profil pro Jarvi (maître).

## Correctif documentaire (⚠️)

- [ ] `data-model.md` porte encore les **anciens noms de rôles**
      (`ROLE_COMPANY_ADMIN`/`RECRUITER`) → aligner sur le modèle réel (cf. tâche `027`).

## Invariants

- Aucune donnée maître showcase-recruiter-webapp-saas écrasée ; cohérence code / migrations / doc.
