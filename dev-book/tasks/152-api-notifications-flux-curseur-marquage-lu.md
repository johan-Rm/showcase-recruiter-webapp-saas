# TASK 152 — api : flux de notifications à curseur + marquage lu (tranche 1 de 058)

- **Créée le** : 2026-07-27.
- **Statut** : ✅ implémenté le 2026-07-27 — tests fonctionnels dédiés ; `make api-quality` : au
  passage PR.
- **Type** : Back — API Symfony (notifications, lecture temps réel).
- **Tranche 1 de la carte `058`** : le **côté lecture** (flux + marquage lu). L'**émission**
  transactionnelle (dévoilement, favori) est la tranche 2, à faire quand la WIP `reveal` en cours
  côté `feature/api` sera posée (pour ne pas se télescoper).
- **Débloque** : tâche `059` (livraison SSE webapp) — le BFF interrogera cette route.
- **Réf** : `058` · entité `Notification` (existante, tâche 019) · `AbstractEntity`
  (id bigint interne + uuid v7 public).

## Contexte

058 demande un **flux à curseur bon marché** : pas de connexion tenue côté PHP, la route est
appelée toutes les ~3-5 s par utilisateur connecté (le BFF SSE de `059`). Cette tranche livre la
lecture et le marquage lu, **sans toucher à l'émission** — elle est donc testable seule, en semant
directement des `Notification`.

## Livré

- **Flux** `GET /v1/notifications?after={uuid}&limit=N` — provider `NotificationsProvider`,
  **self-only par construction** (aucun id en URL, on lit `user->getPerson()`). Tri croissant,
  borné (défaut 50, max 100), lot vide = 200 rapide. Renvoie l'enveloppe `NotificationFeedOutput`
  `{ items, nextCursor }` — `nextCursor` = uuid de la dernière notification à repasser en `after`
  (distincte de `CollectionOutput`, dont le total/page/perPage ne conviennent pas à un curseur).
- **Marquage lu** — `POST /v1/notifications/{uuid}/read` (unitaire, idempotent) et
  `POST /v1/notifications/read-all` (masse, 204), self-only, via `NotificationReadProcessor`.
- **Index** `(recipient_id, uuid)` sur l'entité + `NotificationRepository`
  (`findForRecipientAfter`, `markAllReadForRecipient`).
- **Anonymisation** : l'`Output` porte `id`/`type`/`data`/`createdAt`/`readAt` — **jamais** la FK
  `relatedOrganization`. « Une entreprise a dévoilé votre profil », sans dire laquelle.
- **Contrat** `api/openapi.json` régénéré (3 routes + schémas `Notification`/`NotificationFeedOutput`) :
  la CI `api-quality` vérifie ce fichier par `git diff --exit-code`.

## Décisions

- **Curseur = uuid, pas l'id bigint.** L'`id` interne n'est jamais exposé (convention
  `AbstractEntity`) ; l'uuid **v7** est chronologiquement trié → il sert de curseur ET de clé de tri.
  Le spec 058 écrit « id », mais la lecture correcte vu l'invariant, c'est l'uuid.
- **POST-action plutôt que PATCH.** Le projet n'a aucun idiome « PATCH + processor » ; tout
  changement d'état est un `Post(read:false, deserialize:false)` (cf. `organizations/{id}/validate`).
  Marquer lu est une **action sans corps** — POST est cohérent et sémantiquement plus juste ici.
- **404 (pas 403)** sur la notification d'autrui : son existence ne se déduit pas.
- **Self-only par construction** côté flux : préféré à un voter, car la route n'accepte aucun id de
  cible — impossible de lire celles d'un autre.

## Critères d'acceptation

- [x] `GET /v1/notifications` : self-only, ordre stable (uuid v7 croissant), curseur `after`,
      réponse vide = tableau `[]`.
- [x] Payload sans identité entreprise (test dédié).
- [x] Marquage lu unitaire (idempotent) et de masse, self-only (404 sur autrui).
- [x] Curseur invalide → 400.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit) — au passage PR.
- [ ] `EXPLAIN` passe par l'index `(recipient, uuid)` — à vérifier au passage PR (schéma appliqué).

## Hors périmètre (tranches suivantes de 058)

- **Émission transactionnelle** (dévoilement → notif, favori → notif) : tranche 2, après la WIP
  reveal en cours. C'est là que vivra le `NotificationEmitter` (point d'émission unique, dans la
  transaction métier).
- **`ProfileView` / vu-souvent** : aucun flux runtime ne crée de `ProfileView` aujourd'hui (fixtures
  seules) — carte dédiée quand la consultation sera enregistrée.
- **Canal email** (Messenger) et **rétention/purge** des notifications lues.
