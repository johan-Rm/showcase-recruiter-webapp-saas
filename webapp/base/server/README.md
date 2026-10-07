# base/server/

Couche serveur de l'application (Nitro) — le BFF.

## Rôle et responsabilités

**Rôle Nitro :** `server/` enregistre les routes et handlers exécutés par Nitro — routes API
(`api/`), middlewares et utilitaires serveur. Chaque handler est un `defineEventHandler` ;
l'arborescence des fichiers reflète les URLs exposées. Réf. : [doc Nuxt — `server/`](https://nuxt.com/docs/4.x/directory-structure/server).

**Rôle attendu :** `server/` est le BFF (Backend for Frontend) entre le frontend et l'API Symfony.
Il **possède les secrets et les accès privilégiés**, valide les entrées, orchestre les appels vers
l'API et normalise les réponses. Il détient la session scellée (`nuxt-auth-utils`) : le JWT et le
refresh token restent côté serveur, jamais exposés au client — le login délègue à
`/v1/auth/login`, les appels API partent avec le Bearer de l'utilisateur. En SSR, un `$fetch` vers
un endpoint interne appelle le handler directement, sans requête HTTP.

## Conventions techniques

### Un refus qui ferme la session se nomme

Quand le BFF constate qu'une session ne peut plus servir — jeton rejeté par l'API, refresh
impossible —, il ne se contente pas de répondre `401` : il joint le motif `session_expired` dans
`data`. Le code seul ne suffirait pas à conclure, puisqu'un appel mal formé ou un endpoint qui
refuse pour une autre raison portent le même ; s'y fier déconnecterait quelqu'un dont la session est
vivante. C'est ce motif que `useSessionExpiry` attend côté application pour refermer la session du
navigateur et ramener au login.

La purge faite ici ne suffit pas non plus : émise depuis un appel interne pendant un rendu serveur,
elle n'atteint pas le cookie du navigateur. Elle nettoie la session côté serveur ; c'est
l'application qui la referme pour de bon.
