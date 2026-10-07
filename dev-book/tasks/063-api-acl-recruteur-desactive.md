# TASK 063 — API : verrouiller les accès des recruteurs désactivés

- **Créée le** : 2026-07-11.
- **Statut** : ⬜ à faire.
- **Type** : correction sécurité — API Symfony / ACL multi-tenant.
- **Dépend de** : tâche `009` (invitations / retrait membre), socle rôles tâche `025`.
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` § Authentification & accès
  - `docs/showcase-recruiter-webapp-saas/content/3.quality/code-review-checklist.md` § ACL multi-comptes

## Contexte

Le retrait d'un membre entreprise est implémenté comme une désactivation logique :
`MemberRemovalProcessor` passe `Recruiter.status` à `DISABLED` pour préserver l'audit.

Problème : `OrganizationVoter` ne vérifie aujourd'hui que l'existence d'un `Recruiter` lié à
l'`Organization`. Un recruteur désactivé reste donc autorisé sur `VIEW`, `ACCESS` et,
si son rôle est owner, `MANAGE`, tant que son compte utilisateur et son JWT restent valides.

## Objectif

Un recruteur retiré de l'entreprise ne doit plus pouvoir lire ni modifier aucune ressource de
cette entreprise, même avec un JWT encore valide.

## Périmètre

- `OrganizationVoter` :
  - exiger `RecruiterStatus::ACTIVE` pour tout accès entreprise (`VIEW`, `ACCESS`, `MANAGE`) ;
  - conserver le cas admin cross-tenant explicite.
- Providers / processors qui récupèrent un recruteur courant :
  - harmoniser la règle si nécessaire (`currentRecruiter`, recherche, favoris, reveal, invitations).
- Tests fonctionnels :
  - retirer un membre ;
  - réutiliser le token du membre retiré ;
  - vérifier `403` sur lecture organisation, favoris, crédits, recherche/operations entreprise selon routes pertinentes.

## Invariants

- Un membre `DISABLED` reste historisé mais ne dispose d'aucun droit applicatif.
- Le dernier owner actif reste protégé contre la suppression.
- Aucun raccourci par rôle Symfony seul : l'appartenance active à l'organisation reste nécessaire.

## Critères d'acceptation

- [ ] Après `DELETE /v1/organizations/{id}/members/{recruiterId}`, le recruteur désactivé reçoit
      `403` sur les routes entreprise de son ancienne organisation.
- [ ] `OrganizationVoter` refuse explicitement les recruteurs `DISABLED`.
- [ ] Les tests existants de retrait membre restent verts.
- [ ] `make api-quality` vert.

## Hors périmètre

- Désactivation du compte `User` global si la personne a plusieurs casquettes.
- Invalidation immédiate des JWT/refresh tokens du membre retiré — à cadrer séparément si le risque
  l'exige.
