# TASK 032 — Espace Entreprise : réactivation d'un membre désactivé

- **Créée le** : 2026-06-30.
- **Statut** : ⏳ **à faire**.
- **Périmètre** : permettre à un `COMPANY_OWNER` de réactiver un membre précédemment retiré de son
  organisation, sans supprimer ni recréer les données d'audit.
- **Hors périmètre** : invitation d'un nouveau membre (→ `031`) ; changement de rôle (→ `026b`) ;
  reset mot de passe (→ `025b`) ; suppression physique d'un membre.

## Décision produit

Un membre retiré n'est pas supprimé. Son `Recruiter.status` passe à `DISABLED`.

Pour le faire revenir, on ne le réinvite pas : on réactive le `Recruiter` existant. Cette décision
préserve les relations existantes vers le membre : invitations, favoris, dévoilements,
transactions de crédits, assignations, notes, consultations et recherches sauvegardées.

La réinvitation sert uniquement à créer un nouveau membre qui n'a pas encore accepté d'invitation.
Elle ne doit pas servir à recréer un mot de passe. Si un membre réactivé ne connaît plus son mot de
passe, il utilise le reset password standard.

## Route à livrer

| Méthode | Route | Rôle | Effet |
| --- | --- | --- | --- |
| POST | `/v1/organizations/{id}/members/{recruiterId}/reactivate` | `COMPANY_OWNER` | repasse le recruteur en `ACTIVE` |

## Règles

- Réservé à `COMPANY_OWNER` (`OrganizationVoter::MANAGE`).
- La cible doit être un `Recruiter` de la même `Organization`.
- Si le membre est déjà `ACTIVE`, répondre de façon idempotente (`204` ou `200` selon le contrat
  retenu) sans effet secondaire.
- Si le membre n'existe pas dans l'organisation cible → 404.
- La réactivation ne modifie pas le rôle (`organizationRole`) existant.
- La réactivation ne génère pas de nouveau mot de passe.
- Option V1 recommandée : envoyer un e-mail d'information au membre réactivé, avec lien vers
  `/login` et rappel du reset password si besoin.

## Tests

- Owner réactive un membre `DISABLED` → statut `ACTIVE`.
- Owner réactive un membre déjà `ACTIVE` → idempotent.
- `COMPANY_MEMBER` tente de réactiver → 403.
- Owner d'une autre organisation tente de réactiver → 404/403 selon convention retenue.
- Le rôle du membre reste inchangé.
- Le login fonctionne si le membre connaît son mot de passe ; sinon le reset password reste le
  parcours prévu.

## Définition de terminé

- [ ] Route de réactivation exposée et gardée par `OrganizationVoter::MANAGE`.
- [ ] Aucune suppression/recréation de `Person`, `User` ou `Recruiter`.
- [ ] Aucune perte d'historique sur les tables liées au `Recruiter`.
- [ ] Documentation API/Insomnia mise à jour.
- [ ] Quality gates exécutés au point de validation.

## Références

- [inscription.md](../cadrage/entreprise/inscription.md)
- [031-espace-entreprise-email-invitation-membre.md](./031-espace-entreprise-email-invitation-membre.md)
- [025b-dev-mot-de-passe-libre-service.md](./025b-dev-mot-de-passe-libre-service.md)
