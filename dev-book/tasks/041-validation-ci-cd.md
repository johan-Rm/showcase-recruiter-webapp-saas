# TASK 041 — Validation CI/CD

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à valider.
- **Type** : Phase 1 — Finalisation du socle technique (passe de validation).
- **Valide** : tâche `012` (socle CI GitHub Actions).
- **Réf ClickUp** : https://app.clickup.com/t/869e0jfzf
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`

## Contexte

Les gates CI sont en place. Cette tâche valide la chaîne qualité. La **mise en ligne**
(workflows de déploiement) est portée par la tâche `036`, pas ici.

## À valider

- [ ] CI par chemin : gate `api-quality` + `commit-check` (commitlint).
- [ ] Gate `webapp-quality` (lint + `vue-tsc` + build) issu du scaffold `022`.
- [ ] CD via cibles `Makefile.prod` sur VPS.

## Frontière avec la tâche 036

- Les workflows `deploy-preprod.yml` (auto depuis `develop`) + cible prod sont **cadrés
  dans `036`**. Ici = validation des **gates qualité** uniquement.

## Critères d'acceptation

- [ ] Toute PR vers `develop` passe les gates.
- [ ] Pipeline vert documenté.
