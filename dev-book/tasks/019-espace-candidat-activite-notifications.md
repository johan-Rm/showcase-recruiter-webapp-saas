# TASK 019 — Espace Candidat : activité & notifications

- **Créée le** : 2026-06-23.
- **Statut** : 🚧 **019-A implémenté (2026-06-24)** — `GET /v1/candidates/{id}/activity` (self-only) :
  compteurs agrégés `profileViews` (`ProfileView`), `reveals` (`RevealedProfile`),
  `interestedCompanies` (`Favorite`). **Anonymisation** : compteurs seuls, **identité entreprise
  jamais exposée** ; la branche « identité visible selon abonnement » (parcours §8) est **différée** —
  aucun modèle premium/abonnement candidat n'existe en V1 (`Subscription` est lié à `Organization`).
  **019-B notifications reporté** : dépend de la brique notification multi-canal (en cadrage, cf. TODO).
- **Type** : fonctionnalité P4 — engagement candidat.
- **Dépend de** : tâche `013` (projection) ; **brique notification multi-canal** pour le
  volet notifs — **cadrée le 2026-07-09** : émission + flux à curseur → tâche `058`, livraison
  temps réel SSE (canal in-app) → tâche `059` ; canal email = carte à créer (reliquat B).
  Le volet **activité (lecture `ProfileView`)** est livrable indépendamment.
- **Réf cadrage** : [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) §1, [`PARCOURS_UTILISATEUR_CANDIDAT.md`](../cadrage/PARCOURS_UTILISATEUR_CANDIDAT.md) §8-9, data-model §437/453.

## Contexte

Le candidat **mesure l'intérêt** généré par son profil (vues, dévoilements, entreprises intéressées)
et **reçoit des notifications** (« une entreprise a débloqué votre profil »). Les entités existent
(`ProfileView`, `Notification`) ; il manque l'exposition côté candidat.

## Périmètre

### A. Activité (livrable sans la brique notif)
- **Lecture `ProfileView`** côté candidat : compteurs agrégés (vues, dévoilements, intérêt).
- **Anonymisation selon abonnement** : l'identité de l'entreprise est **masquée ou visible** selon le
  niveau (cf. parcours §8) — règle appliquée **côté serveur**.

### B. Notifications (dépend de la brique multi-canal)
- **Émission** d'événements candidat (profil consulté / dévoilé / recommandé) **via la brique
  notification multi-canal** (in-app / email / push PWA) — **ne pas construire un tuyau dédié**.
- **Préférences par utilisateur** (héritées de la brique).

## Hors périmètre

- Construction de la brique notification multi-canal elle-même → tâche dédiée (TODO 🟠).
- Diffusion d'offres (émetteur de la même brique) → V2.

## Invariants

- **Masquage de l'identité entreprise** appliqué **côté serveur** selon abonnement.
- **Self-only** ; lecture via API Symfony.
- Réutiliser la brique notif unique (pas de second canal).

## Critères d'acceptation

- [x] Compteurs d'activité exposés au candidat (self-only) ; identité entreprise **masquée** (compteurs
  seuls). Branche « visible selon abonnement » différée (pas de modèle premium candidat en V1).
- [ ] Événements candidat émis via la brique notif (dès qu'elle existe) ; préférences respectées.
- [ ] `make api-quality` vert ; tests (agrégats, anonymisation serveur).

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` : usages `ProfileView`/`Notification` côté candidat.
- [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) : statut fonctionnalités.
