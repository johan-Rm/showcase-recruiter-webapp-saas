# TASK 067 — API : durcir les endpoints publics magic link et reset password

- **Créée le** : 2026-07-11.
- **Statut** : ⬜ à faire.
- **Type** : sécurité applicative — endpoints publics.
- **Dépend de** : tâches `033` (magic link candidat), `025b` (mot de passe self-service).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` § Authentification & accès
  - `docs/showcase-recruiter-webapp-saas/content/3.quality/code-review-checklist.md` § Périmètre & sécurité

## Contexte

Les endpoints publics d'authentification manipulent des jetons correctement hashés et à usage
unique, mais le flux magic link crée aujourd'hui un `Person` + `User` pour tout email inconnu.

Ce comportement est trop permissif pour un endpoint public : il permet de créer des comptes
fantômes, de déclencher des emails arbitraires et complique le modèle produit du magic link
candidat, censé rattacher un compte à une projection existante ou authentifier un parcours cadré.

## Objectif

Limiter les effets de bord des endpoints publics : aucune création de compte non cadrée, pas
d'énumération utilisateur, et garde-fous anti-abus.

## Périmètre

- `MagicLinkIssuer` :
  - ne créer un compte candidat que si l'email correspond à une projection candidate importée
    ou à un parcours explicitement autorisé ;
  - sinon répondre de façon neutre sans création d'entité.
- `PasswordResetIssuer` / magic link / password setup :
  - conserver les réponses neutres anti-énumération ;
  - documenter la règle dans les tests.
- Ajouter une protection anti-abus :
  - rate limit Symfony sur IP + email normalisé, ou mécanisme équivalent adapté au projet ;
  - borne sur le nombre de tokens actifs par email, avec invalidation des anciens tokens si besoin.
- Tests fonctionnels :
  - demande magic link pour email inconnu : réponse neutre, aucun `User` créé ;
  - demande magic link pour projection importée : rattachement attendu ;
  - token consommé non réutilisable ;
  - password reset ne révèle pas l'existence d'un compte.

## Invariants

- Les tokens restent hashés en base.
- Les endpoints publics ne révèlent pas si un email existe.
- Le magic link ne vaut jamais consentement implicite.
- Pas de secret ou token en clair dans les logs applicatifs.

## Critères d'acceptation

- [ ] Un email inconnu ne crée plus de `User` / `Person` via magic link.
- [ ] Les réponses publiques restent neutres.
- [ ] Un garde-fou anti-abus est en place et testé ou documenté.
- [ ] Les tests magic link, invitation et reset password restent verts.
- [ ] `make api-quality` vert.

## Hors périmètre

- SSO social login.
- Refonte complète du BFF Nuxt.
