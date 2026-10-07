# TASK 049 — webapp : rejeu du refresh token côté BFF

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-12.
- **Type** : Front — Webapp Nuxt (auth, sécurité).
- **Prolonge** : tâche `037` (auth fonctionnelle BFF).
- **Réf** : `webapp/server/utils/showcase-recruiter-webapp-saas-api.ts` · `webapp/server/api/auth/login.post.ts` ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` §5 · API : Lexik + Gesdinet.

## Contexte

Le login BFF stocke bien les deux jetons dans la zone `secure` de la session scellée
(`apiToken`, `apiRefreshToken` — `login.post.ts`). Mais `apiFetchForUser()`
(`showcase-recruiter-webapp-saas-api.ts`) se contente d'attacher le Bearer et **ne rejoue jamais le refresh** : à
l'expiration du JWT Lexik, l'API répond 401 et l'utilisateur est éjecté alors que son refresh
token Gesdinet est encore valide en session.

C'est le trou fonctionnel le plus net du socle webapp (identifié dans `webapp/README.md`,
section « Reste à brancher »).

## Objectif

Renouveler le JWT de façon transparente côté serveur Nuxt, sans jamais exposer de jeton au
client.

## Périmètre

- Dans `apiFetchForUser()` : sur `401`, appeler l'endpoint de refresh de l'API avec
  `session.secure.apiRefreshToken`, mettre à jour la session (`setUserSession`), puis **rejouer
  la requête une seule fois**.
- Si le refresh échoue (expiré, révoqué) : détruire la session et renvoyer un `401` propre,
  que le client traduit en redirection `/login`.
- **Anti-stampede** : sérialiser les refresh concurrents pour une même session (plusieurs
  appels API parallèles ne doivent déclencher qu'un seul refresh).
- Ne pas boucler : un seul rejeu par requête, jamais de retry sur l'appel de refresh lui-même.
- Rafraîchir aussi les `roles` de la session depuis le nouveau JWT (`rolesFromJwt`) — un rôle
  révoqué côté API doit se propager.

## Invariants

- **Le client ne voit jamais le JWT ni le refresh token.** Ils restent dans la zone `secure`
  de la session scellée (cookie httpOnly).
- Aucun token de service : l'API applique toujours l'autorisation de l'utilisateur.
- Le cookie de session reste scopé au domaine parent (SSO inter-sous-domaines).
- Aucun jeton en clair dans les logs.

## Critères d'acceptation

- [ ] Avec un JWT expiré et un refresh valide, un appel API réussit de façon transparente et
      la session porte le nouveau JWT.
- [ ] Avec un refresh invalide, la session est détruite et le client redirigé vers `/login`.
- [ ] Cinq appels API parallèles sur un JWT expiré ne déclenchent **qu'un seul** refresh.
- [ ] Aucun jeton présent dans une réponse au client (vérifié sur le payload de `/api/me`).
- [ ] Tests Vitest sur la logique de rejeu (cf. tâche `046`).

## Hors périmètre

- Rotation / révocation des refresh tokens côté API (Gesdinet) — à cadrer côté backend.
- Déconnexion sur tous les sous-domaines (single logout).

## Mise à jour 2026-07-11

Cette tâche est la **maison canonique du rejeu du refresh token**. La tâche `037` (auth BFF
bout-en-bout) portait le même point dans son périmètre : il en a été **retiré** et renvoyé ici,
pour éviter le doublon.

État constaté : le refresh Gesdinet est bien **stocké** dans la zone `secure` de la session
scellée (`webapp/types/index.d.ts` → `SecureSessionData.apiRefreshToken`), mais **jamais rejoué**
— `apiFetchForUser()` (`webapp/base/server/utils/api.ts`) ne retente pas sur `401`. Conséquence
aujourd'hui : un JWT expiré déconnecte l'utilisateur au lieu d'être renouvelé silencieusement.

## Mise à jour 2026-07-12 — livré

`apiFetchForUser()` rejoue désormais le refresh : sur `401`, il échange le refresh token contre
un JWT neuf, réécrit la session scellée et **rejoue la requête une seule fois**. Si le refresh
échoue, la session est détruite et l'appelant reçoit un `401` propre.

**Prouvé de bout en bout** (TTL du JWT temporairement abaissé à 5 s, puis restauré) :

- JWT expiré → `/api/me` répond **200** (sans le refresh : 401) ;
- refresh **révoqué en base** → **401 propre**, session détruite ;
- deux refresh successifs → toujours 200, rôles intacts.

### Bug de sécurité attrapé par la preuve

Premier essai : après un refresh, la session portait
`['ROLE_COMPANY_OWNER', 'ROLE_USER', 'ROLE_COMPANY_OWNER', 'ROLE_USER']`.

`setUserSession()` **fusionne** — les tableaux se concatènent. Deux conséquences : la liste de
rôles enfle à chaque renouvellement, et surtout **un rôle révoqué survit à la fusion**, ce qui
annule exactement l'objectif « un rôle révoqué doit se propager ». Corrigé par
`replaceUserSession()`, dans le refresh **et dans le login** (une reconnexion sans logout
préalable avait le même défaut).

### Contrat : le refresh était absent de l'OpenAPI

`/v1/auth/refresh` (route Gesdinet pure) n'apparaissait **nulle part** dans le contrat — comme
le `refresh_token` du login. Une route que le front appelle mais que le contrat ignore ne peut
être ni typée ni protégée de la dérive. Le décorateur `AuthEndpointsDecorator` déclare
désormais les deux, et les types TS en sont générés.

### Anti-stampede

Dédoublonnage **par requête** (`WeakMap<H3Event>`) : un rendu SSR lance plusieurs appels API en
parallèle, qui expireraient ensemble. La clé est l'événement — pas le jeton — pour que la
session réécrite soit bien celle de *cette* requête. Gesdinet étant configuré `single_use: false`
(pas de rotation), des refresh concurrents entre requêtes distinctes sont de toute façon inoffensifs.

### Constaté au passage

TTL du JWT = **1 h**, TTL du refresh = **30 j**, `single_use: false`, `ttl_update: false`. Le
refresh token est donc réutilisable pendant 30 jours. Il ne quitte jamais le serveur (zone
`secure` de la session scellée), mais **activer la rotation** (`single_use: true`) serait plus sûr
— à arbitrer côté API.

