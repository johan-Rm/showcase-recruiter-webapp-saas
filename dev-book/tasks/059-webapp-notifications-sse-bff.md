# TASK 059 — webapp : notifications & données temps réel — flux SSE tenu par le BFF

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (temps réel, BFF).
- **Dépend de** : tâche `058` (émission + flux à curseur côté API) · tâche `049` (refresh BFF)
  · tâche `055` (jalons `useDeferredRuntime` : le flux démarre à `runtimeReady`).
- **Réf** : décision transport du 2026-07-09 (analyse long polling vs SSE vs Mercure) ·
  `webapp/server/utils/showcase-recruiter-webapp-saas-api.ts` · h3 `createEventStream` · nginx (`docker/`).

## Contexte

Décision du 2026-07-09 : **SSE tenu par le BFF Nuxt**, retenu contre le long polling (à coût
serveur égal, `EventSource` offre nativement la reconnexion automatique et la reprise par
`Last-Event-ID` — moins de code client), contre Mercure (vrai push mais +1 conteneur et une
connexion directe client→hub qui casserait l'invariant BFF — chemin d'upgrade V2), et contre
le long polling direct sur Symfony (**exclu** : une requête parquée = un worker FPM occupé).

Architecture : le client ouvre **une** connexion SSE vers le BFF ; le BFF interroge l'API
(`GET /v1/notifications?after={cursor}`, Bearer de l'utilisateur) toutes les ~3-5 s et pousse
les nouveautés dans le flux. Le polling interne est le point remplaçable (Mercure,
LISTEN/NOTIFY) **sans changer le contrat client**.

La répercussion inter-espaces est acquise par construction : l'émission (tâche `058`) écrit en
BDD ; le candidat connecté sur `candidat.` reçoit ce que l'entreprise a déclenché sur
`entreprise.` — même code de flux sur tous les sous-domaines, session SSO domaine parent.

## Objectif

Un utilisateur connecté voit ses notifications arriver sans recharger, quel que soit son
espace ; les données d'équipe du cockpit (crédits, favoris) se rafraîchissent par le même
canal.

## Périmètre

- **Route BFF `server/api/notifications/stream.get.ts`** (h3 `createEventStream`) :
  - session requise (`requireUserSession`) ; boucle de polling interne via `apiFetchForUser`
    avec curseur ; push des événements `id: {notificationId}` + `data: {modèle mappé}` ;
  - reprise : lit `Last-Event-ID` à la (re)connexion et repart du curseur ;
  - **heartbeat** (commentaire SSE toutes les ~25 s) pour traverser les proxies ;
  - nettoyage strict à la déconnexion (arrêt de la boucle, pas de fuite d'intervalle) ;
  - un 401 de l'API pendant la boucle → tentative refresh (tâche `049`), sinon fermeture
    propre du flux (le client cessera de se reconnecter après un 401 du BFF).
- **Client** : `useNotificationsStream()` — `EventSource` ouvert à `runtimeReady` (jalons
  `055`), jamais en SSR ; les événements alimentent `stores/notifications.ts` (badge topbar,
  liste, marquage lu via route BFF dédiée).
- **Événements non-notification** (crédits d'équipe, favoris) : même flux, champ `type`
  discriminant ; les stores concernés s'abonnent (le mapper `057` transforme les payloads).
- **Proxy nginx** (preprod/prod) : sur la route du flux — `proxy_buffering off` (+ header
  `X-Accel-Buffering: no` en ceinture), `proxy_read_timeout` élevé. À documenter dans
  `docker-and-make.md`.

## Invariants

- **Le client ne voit jamais de JWT** : la seule connexion sort vers le BFF, cookie de session
  scellée — aucun token dans les événements SSE.
- Aucune connexion tenue côté PHP : le BFF interroge l'API en requêtes courtes.
- Le flux est un canal de **lecture** : aucune mutation déclenchée par un événement reçu.
- Dégradation propre : sans flux (proxy hostile, `EventSource` indisponible), l'app
  fonctionne — repli en short polling de la route liste (~30 s).
- Un utilisateur = ses notifications (le self-only de la `058` fait foi ; le BFF n'élargit
  jamais le scope).
- SSR-safe : rien du flux ne s'exécute côté serveur de rendu.

## Critères d'acceptation

- [ ] Scénario inter-espaces de bout en bout : dévoilement déclenché sur `entreprise.` ⇒
      badge + notification visibles sur `candidat.` en < 10 s sans rechargement.
- [ ] Coupure réseau puis retour : `EventSource` se reconnecte seul et rattrape les manqués
      (`Last-Event-ID` → curseur), aucun doublon dans le store.
- [ ] Fermeture d'onglet / navigation : plus aucun polling interne pour cette session côté
      BFF (vérifier l'arrêt des boucles).
- [ ] Session expirée non refreshable : le flux se ferme, pas de tempête de reconnexions.
- [ ] Heartbeat présent ; flux fonctionnel derrière le nginx preprod (buffering off vérifié).
- [ ] Tests Vitest : logique de curseur/reprise, déduplication du store, mapping des types
      d'événements.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Émission et modèle des notifications → tâche `058`.
- UI riche du centre de notifications (page dédiée, préférences) — V1 = badge + liste
  déroulante + marquage lu.
- Push PWA / notifications navigateur → V2.
- Mercure / LISTEN-NOTIFY : upgrade du pont interne si l'échelle l'exige — le contrat client
  ne changera pas.
