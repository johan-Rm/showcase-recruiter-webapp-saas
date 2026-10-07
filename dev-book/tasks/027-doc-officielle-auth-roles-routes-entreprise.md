# TASK 027 — Doc officielle : rôles & permissions + routes Espace Entreprise

- **Créée le** : 2026-06-29.
- **Statut** : ⏳ **à faire** — **bloquée** par les tâches **025**, **026**, **028**, **029** et **030**
  *testées et validées*.
- **Périmètre** : reporter dans la **documentation officielle `docs/`** (showcase-recruiter-webapp-saas **et** api) le
  modèle de **rôles & permissions** et **l'ensemble des routes de l'Espace Entreprise** une fois
  implémentées et validées — cycle de vie & membres (026), favoris & crédits/dévoilement (028),
  assignation responsable de suivi (029), recherches sauvegardées (030). La source de vérité reste le
  **code** ; cette tâche **officialise** ce qui est aujourd'hui dans le cadrage `dev-book/`.
- **Hors périmètre** : tout développement (→ 025 / 026) ; le cadrage `dev-book/` (déjà fait) ; la doc
  Insomnia.

## Pourquoi en aval

La doc officielle décrit l'**état livré**, pas l'intention. Tant que 025/026/028/029/030 ne sont pas
**validées en tests**, figer la doc créerait une dérive. Cette tâche se déclenche **après** la
validation.

## Travail

### `docs/showcase-recruiter-webapp-saas/content/2.architecture/`

- **Rôles & permissions** : créer une section dédiée (ou `roles-permissions.md`) — les 5 rôles
  canoniques, la `role_hierarchy`, la **matrice de permissions** (reprise de la tâche 025), la règle
  multi-rôles / switch de contexte et le « pas de court-circuit global » admin.
- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) : remplacer les anciens
  noms de rôles (§User l.91, §Recruiter) par les nouveaux ; ajuster `OrganizationRole`.
- [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) : ajouter/mettre à jour les
  routes entreprise (création, modif, validation, rejet, membres, changement de rôle, file de revue,
  **favoris**, **crédits/dévoilement**, **assignation responsable de suivi**, **recherches
  sauvegardées**).
- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) : officialiser la nouvelle
  entité `CandidateAssignment` (029) et l'exposition V1 de `SavedSearch` (030, alertes toujours V2) ;
  lever la mention « assignation / suivi → V2 » du cluster entreprise.
- Officialiser dans [jarvi-integration.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md)
  la règle **push entreprise unique à la validation, aucune synchro entreprise↔Jarvi ensuite**
  (cf. [inscription.md](../cadrage/entreprise/api/inscription.md) § Synchronisation Jarvi).

### `docs/api/content/`

- [routes/organization.md](../../docs/api/content/routes/organization.md) : documenter chaque route
  (méthode, payload, rôle requis, codes d'erreur — notamment `403` isolation).
- [schemas/index.md](../../docs/api/content/schemas/index.md) : aligner les schémas Input/Output et les
  valeurs de rôle.

## Critères d'acceptation

- [ ] Doc `docs/showcase-recruiter-webapp-saas` : rôles/permissions + data-model + api-routes à jour avec les noms livrés.
- [ ] Doc `docs/api` : routes organization + schémas à jour (rôles, codes d'erreur).
- [ ] Règle **push unique / aucune synchro entreprise↔Jarvi** officialisée dans `jarvi-integration.md`.
- [ ] Zéro occurrence des anciens noms de rôles dans `docs/`.
- [ ] Cohérence vérifiée vs code (gate anti-dérive doc/contrats).

## Références

- Amont : **025** (rôles & permissions) · **026** (cycle de vie & membres) · **028** (favoris &
  crédits) · **029** (assignation) · **030** (recherches sauvegardées) — doivent être *validées*.
- Cadrage source : [vue_d_ensemble.md](../cadrage/entreprise/vue_d_ensemble.md) · [inscription.md](../cadrage/entreprise/api/inscription.md) · mémoire projet « rôles & permissions ».
- Passe de cohérence doc : tâche 021.
