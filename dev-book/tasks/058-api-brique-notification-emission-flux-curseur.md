# TASK 058 — api : brique notification — émission transactionnelle + flux à curseur

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Back — API Symfony (notifications, temps réel).
- **Concrétise** : la « brique notification multi-canal » attendue par la tâche `019-B`
  (canal **in-app** ; le canal email viendra s'adosser à la même émission).
- **Débloque** : tâche `059` (livraison SSE webapp) · tâche `019-B`.
- **Réf** : tâche `019` (entités `Notification`/`ProfileView` existantes, invariants
  d'anonymisation) · `dev-book/cadrage/ESPACE_CANDIDAT.md` §1 ·
  `PARCOURS_UTILISATEUR_CANDIDAT.md` §8-9.

## Contexte

Décision du 2026-07-09 sur le transport temps réel : **pas de connexion tenue côté PHP**
(chaque requête parquée occupe un worker FPM — le long polling direct sur l'API saturerait le
pool). L'API reste en requête/réponse classique : elle **émet** des notifications en base et
expose un **flux à curseur** bon marché ; c'est le BFF Nuxt (tâche `059`) qui tient les
connexions clientes.

La répercussion inter-espaces (une action côté entreprise → notification côté candidat) se
règle ici, à l'émission : la BDD est le point de rendez-vous des espaces.

## Objectif

Toute action métier notifiable écrit sa `Notification` de façon transactionnelle, et chaque
utilisateur peut lire son flux depuis un curseur, à coût constant.

## Périmètre

- **Émission transactionnelle** : la `Notification` (destinataire, type, payload, `createdAt`,
  `readAt` null) est écrite **dans la même transaction** que l'événement métier. Événements
  V1 (cf. parcours candidat §8-9) :
  - profil **dévoilé** (`RevealedProfile`) → notification au candidat ;
  - profil **mis en favori** (`Favorite`) → notification au candidat ;
  - profil **consulté** (`ProfileView`) → notification au candidat (à arbitrer : agrégation
    pour éviter le spam — point ouvert).
- **Flux à curseur** : `GET /v1/notifications?after={id}` — self-only (voter), trié par id
  croissant, borné (`limit`), réponse vide = 200 rapide. **Index `(recipient, id)`** obligatoire :
  la route sera appelée toutes les ~3-5 s par utilisateur connecté, elle doit rester en
  quelques millisecondes.
- **Marquage lu** : `PATCH /v1/notifications/{id}` (ou endpoint de masse `read-all`) —
  self-only.
- Payload **plat** (conventions API) : uuid de relations, pas de graphe imbriqué.

## Invariants

- **Anonymisation** (hérité de la tâche `019`, appliqué côté serveur) : l'identité de
  l'entreprise n'est **jamais** exposée au candidat dans le payload de notification —
  « une entreprise a dévoilé votre profil », sans dire laquelle (pas de modèle premium en V1).
- Self-only strict : un utilisateur ne lit et ne marque que ses notifications.
- Émission et événement métier atomiques : pas de dévoilement sans notification, pas de
  notification orpheline (rollback commun).
- Aucun canal parallèle : cette brique est **l'unique** point d'émission (invariant `019` —
  « ne pas construire un tuyau dédié ») ; l'email s'y adossera (Messenger, async), pas de
  second circuit.
- La route flux ne tient jamais la connexion (pas de sleep/attente côté PHP).

## Critères d'acceptation

- [ ] Un dévoilement en BDD ⇒ une `Notification` candidat dans la même transaction (test :
      rollback de l'un annule l'autre).
- [ ] `GET /v1/notifications?after=X` : self-only (403 sinon), ordre stable, `EXPLAIN` passe
      par l'index, réponse vide < 10 ms en local.
- [ ] Payload candidat sans identité entreprise (test dédié).
- [ ] Marquage lu fonctionnel et self-only.
- [ ] `make api-quality` vert ; tests des trois émissions V1.

## Hors périmètre

- La livraison temps réel aux clients (SSE) → tâche `059`.
- Le canal email (Messenger) et les préférences de notification par utilisateur → carte
  dédiée à créer quand le canal in-app tourne (reliquat `019-B`).
- Push PWA → V2.
- Notifications côté entreprise (activité d'équipe) — à cadrer, la mécanique sera la même.

## Points ouverts

- `ProfileView` : notifier chaque consultation ou agréger (« 3 entreprises ont consulté votre
  profil aujourd'hui ») ? L'agrégation semble préférable (anti-spam) — trancher au démarrage.
- Politique de rétention/purge des notifications lues.
