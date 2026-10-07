# TASK 037 — Webapp : auth fonctionnelle bout-en-bout (BFF)

- **Créée le** : 2026-07-06.
- **Statut** : 🚧 partiellement fait — login/logout livrés et vérifiés ; **refresh** et **preprod** restent.
- **Type** : Phase 1 — Socle technique / auth webapp.
- **Prolonge** : tâche `022` (BFF nuxt-auth-utils).
- **Dépend de** : tâche `036` (environnements déployés).
- **Réf ClickUp** : https://app.clickup.com/t/869e0nruy
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`

## Contexte

Le BFF `nuxt-auth-utils` est en place (JWT/refresh en session scellée httpOnly, relais
Bearer côté serveur). **TODO connu du spike** : le refresh token Gesdinet est stocké mais
**pas rejoué**. Cette tâche rend l'auth pleinement fonctionnelle.

## Objectif

Permettre login/logout et renouvellement transparent du JWT sur les sous-domaines, sans
jamais exposer de token au client.

## Périmètre

- Login / logout via le BFF (aucun token côté client).
- **Rejouer le refresh token** à l'expiration du JWT (renouvellement transparent).
- Host-routing 2 étages respecté (garde par espace, dispatcher `/`).
- Session scellée httpOnly ; CORS / cookie par variable d'env (cf. `036`).

## Invariants

- Aucun token JWT/refresh exposé au navigateur.
- Clé privée JWT côté serveur uniquement.

## Critères d'acceptation

- [ ] Se connecter sur `entreprise.preprod.showcase-recruiter-webapp-saas.example`.
- [ ] Rester connecté **après expiration + refresh**, sans re-login.
- [ ] Logout invalide la session scellée.

## Hors périmètre

- Contenu applicatif des espaces → tâche `038`. Webapp volontairement quasi vide ici.

## Mise à jour 2026-07-11

**Fait** — login / logout via le BFF, session scellée httpOnly, **aucun token côté client**.
Vérifié de bout en bout en dev Docker (`entreprise@showcase-recruiter-webapp-saas.example` → `ROLE_COMPANY_OWNER`,
cookie `HttpOnly` + `Secure` + `SameSite=Lax`). Le logout purge bien la session.

**Reste** :

- **Rejeu du refresh token** — non implémenté (le refresh Gesdinet est stocké mais jamais
  rejoué). ⚠️ **Doublon** : c'est exactement l'objet de la tâche `049`. À traiter là-bas, et à
  retirer du périmètre d'ici.
- **Vérification sur preprod** (`entreprise.preprod.showcase-recruiter-webapp-saas.example`) — **bloquée par la tâche
  `036`** : le modèle layers exige 4 services (un par espace), pas encore déployés.

Le point « host-routing 2 étages » du périmètre est **caduc** : remplacé par une build par
espace (cf. `022`).
