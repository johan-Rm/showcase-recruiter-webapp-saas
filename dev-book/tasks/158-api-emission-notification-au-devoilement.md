# TASK 158 — api : émission transactionnelle d'une notification au dévoilement (tranche 2 de 058)

- **Créée le** : 2026-07-27.
- **Statut** : ✅ implémenté le 2026-07-27 — tests fonctionnels dédiés ; `make api-quality` : au
  passage PR.
- **Type** : Back — API Symfony (notifications, cœur sensible crédits).
- **Tranche 2 de la carte `058`**, au-dessus de la tranche 1 (`152`, flux + marquage lu, déjà en
  develop) et du dévoilement « trois accès » (`155`).
- **Débloque** : le contenu réel du flux `059` (SSE) — désormais des notifications naissent.
- **Réf** : `058` · `ProfileRevealer` (task 155) · entité `Notification` (task 019).

## Contexte

Le dévoilement « trois accès » (155) débite un crédit par accès (CONTACT / RESUME /
INTERVIEW_REPORT), atomique dans `ProfileRevealer.reveal()`. 058 veut qu'un accès payé **prévienne
le candidat**, dans la **même transaction** — pas de dévoilement sans notification, pas de
notification orpheline.

## Livré

- **`NotificationEmitter`** (`Domain/Notification`) — **point d'émission unique** (invariant 058 :
  aucun tuyau parallèle ; l'email s'y adossera). `persist` **sans `flush`** → l'émission s'inscrit
  dans la transaction de l'appelant → atomicité par construction.
- **Hook** dans `ProfileRevealer.reveal()`, sur le **chemin nouveau-dévoilement** (le chemin
  idempotent rend la main avant, sans re-notifier).
- **Une notification par accès payé** : type `PROFILE_REVEALED`, destinataire = `Person` du
  candidat, `data: {access: <CONTACT|RESUME|INTERVIEW_REPORT>}`. `relatedOrganization` renseignée
  en **interne** (email à venir) — **jamais** exposée par l'Output du flux (anonymisation, task 152).

## Décisions

- **Émission dans le domaine, pas dans le processor** : l'atomicité exige la même transaction que
  `RevealedProfile` ; seul `ProfileRevealer.reveal()` la porte (`wrapInTransaction`).
- **Une notif par accès** (pas une par candidat) : chaque accès est un événement facturé distinct ;
  le candidat sait *ce qui* a été consulté (« vos coordonnées », « votre CV »). Agrégation possible
  plus tard si trop bruyant.
- **`data.access` porte le type** — info sur le candidat lui-même, pas sur l'entreprise : compatible
  avec l'anonymisation.

## Critères d'acceptation

- [x] Un dévoilement HTTP ⇒ une `Notification` `PROFILE_REVEALED` au candidat, `data.access` = type.
- [x] Un accès = une notif ; deux accès = deux notifs ; dévoilement idempotent ⇒ pas de doublon.
- [x] Crédits insuffisants ⇒ 402 et **aucune** notification (émission solidaire du débit).
- [x] phpstan / cs / deptrac verts (`ProfileRevealer → NotificationEmitter` = Domain→Domain).
- [ ] `make api-quality` complet — au passage PR (flake JWT d'isolation connu, non lié au diff).

## Hors périmètre (suite)

- **Favori → notification** : sorti volontairement — l'enum n'a pas de cas « favori » et notifier un
  favori sur profil masqué est un signal faible à trancher côté produit. Carte dédiée.
- **`ProfileView` / vu-souvent**, **canal email** (Messenger), **rétention/purge** : inchangés.
