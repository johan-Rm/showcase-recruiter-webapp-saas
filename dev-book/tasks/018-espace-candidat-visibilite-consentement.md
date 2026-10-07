# TASK 018 — Espace Candidat : visibilité & consentement (RGPD)

- **Créée le** : 2026-06-23.
- **Statut** : 🚧 visibilité self-service + horodatage consentement **implémentés** (2026-06-23) ;
  volet RGPD complet (rétention, effacement) **en attente du cadrage**.
- **Type** : fonctionnalité P4 — sensible (contrôle d'exposition + base légale).
- **Dépend de** : tâche `013` (projection). Interagit avec la recherche/masquage (task 006/014).
- **Réf cadrage** : [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) §2, [`PARCOURS_UTILISATEUR_CANDIDAT.md`](../cadrage/PARCOURS_UTILISATEUR_CANDIDAT.md) §7, item RGPD [`TODO.md`](../cadrage/TODO.md).

## Contexte

Le candidat **garde le contrôle** de l'exposition de son profil. `visibility` (enum
`VISIBLE`/`HIDDEN`/`RESTRICTED`/`PREMIUM_ONLY`) est un différenciant **maître showcase-recruiter-webapp-saas**, appliqué
**côté serveur** dans la recherche recruteur et le masquage. C'est aussi un point **RGPD** : opt-in,
consentement, droit à l'oubli, rétention.

## 🔓 Cadrage à compléter (prérequis)

L'item **RGPD** du [`TODO.md`](../cadrage/TODO.md) (consentement, rétention, hard delete) doit être
cadré **avant** d'implémenter au-delà du `visibility` simple. Cette tâche livre le contrôle de
visibilité V1 ; le volet consentement complet suit le cadrage RGPD.

## Périmètre

- **Gestion `visibility`** : endpoint self-only pour basculer l'exposition.
- **Application serveur** : la recherche recruteur (task 014) et le masquage **excluent / restreignent**
  selon `visibility` — **appliqué côté `api/`, jamais côté client**. `HIDDEN` = hors résultats ;
  `RESTRICTED`/`PREMIUM_ONLY` = règles d'accès à définir.
- **Cohérence ingestion** : ne pas exposer côté showcase-recruiter-webapp-saas un profil importé Jarvi sans règle de
  consentement/visibilité applicable. L'opt-in est appliqué côté API showcase-recruiter-webapp-saas, pas en poussant un état
  vers Jarvi.
- **Trace de consentement** (V1 minimal) : horodatage du choix de visibilité.

## Hors périmètre

- Hard delete RGPD complet → runbook dédié (TODO 🟣).
- Visibilité « premium uniquement » liée aux options payantes → dépend Stripe (V2).

## Invariants

- **Filtre opt-in / consentement appliqué côté serveur** (jamais contournable client).
- **Self-only** ; écriture via API Symfony.
- Cohérence avec le masquage existant (task 006/014).

## Critères d'acceptation

- [x] `visibility` modifiable self-only (GET/PUT `/v1/candidates/{id}/visibility`) — fait + testé (self/403).
- [~] Application **serveur** : la recherche filtre déjà `VISIBLE` uniquement (`CandidateRepository::search`)
      → `HIDDEN` exclu par construction ; **test d'exclusion dédié à ajouter**.
- [x] Trace de consentement horodatée (`visibilityUpdatedAt`).
- [ ] `make api-quality` vert ; tests RGPD (rétention/effacement) à venir avec le cadrage.

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` : respect visibilité dans la recherche
  locale et le rattachement candidat.
- [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) ; item RGPD [`TODO.md`](../cadrage/TODO.md).
