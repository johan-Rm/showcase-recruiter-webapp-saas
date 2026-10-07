# TASK 014 — Recherche candidats sur la réplica locale (index local du périmètre)

- **Créée le** : 2026-06-22. **Révisée le** : 2026-06-23 (pivot ingestion complète).
- **Statut** : 🚧 amorce (2026-06-23) — implémentation **minimale** (exclusion hors-périmètre +
  test) ; la recherche FTS + filtres + différenciants reste à étoffer. Dépend de `020` (réplica).
- **Type** : correction (révise la **tâche 006**, qui servait la recherche depuis un index local
  abandonné le 2026-06-22, puis **réhabilité** le 2026-06-23).
- **Dépend de** : tâche `013` (Candidate = projection) + **tâche `020`** (ingestion du périmètre qui
  alimente la réplica locale). N'appelle **pas** Jarvi en direct.

## Décision (cf. `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`, révision 2026-06-23)

showcase-recruiter-webapp-saas ingère désormais **tout le périmètre** Jarvi dans une **réplica locale en lecture seule**
(profil pro) + les **différenciants locaux** (maîtres showcase-recruiter-webapp-saas). **La recherche recruteur V1 tourne
donc en Postgres**, pas sur Jarvi en direct : quota 500 req/j **dégagé**, recherche **déterministe et
reproductible** (base des `SavedSearch` / alertes).

## Périmètre

- **Recherche simple / filtres / plein-texte (V1)** → **requête Postgres** sur la réplica :
  filtres structurés (secteur, fonction, localisation, langues, compétences) **+** différenciants
  showcase-recruiter-webapp-saas (présence Maroc, horizon retour, salaire, mobilité, disponibilité) **+** recherche
  plein-texte (FTS Postgres ; Meilisearch en option si besoin — cf. point ouvert).
- **Mapping résultats → affichage** : `api/` renvoie des **cartes candidat masquées**
  (`CandidateCardOutput`), résout l'état **dévoilé/favori** via la projection showcase-recruiter-webapp-saas, applique le
  **masquage des coordonnées** et la **visibilité** candidat (task 018) **côté serveur**.
- **Filet de sécurité** : seuls les profils **actifs du périmètre** (non désactivés par la
  réconciliation, cf. task 020) apparaissent.

## Hors périmètre

- **Réécriture du `Candidate`** → tâche 013.
- **Pipeline d'ingestion** (pull initial, incrémental `updatedAt`, polling, réconciliation,
  désactivation hors-périmètre) → **tâche 020**. C'est elle qui parle à Jarvi.
- **Recherche IA (langage naturel)** → **MCP `advancedSearch`** — **V2**, reste un appel Jarvi/MCP.
- Achat de crédits et favoris (déjà livrés, re-câblés sur la projection en 013). Notes recruteurs
  reportées en V2.

## Ce qui change vs la version 2026-06-22

- ❌ La recherche **ne tape plus Jarvi REST `where` en direct** (c'était l'approche live abandonnée).
- ✅ L'**index local est réhabilité** : la réplica locale **est** l'index. Le moteur (FTS Postgres
  vs Meilisearch) est à confirmer (point ouvert `jarvi-integration.md`).

## Endpoints (révision de la tâche 006)

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/search/candidates` | 🧑‍💼 recruteur | **V1** — filtres + plein-texte → **Postgres (réplica)** ; cartes masquées |
| — | recherche IA à cadrer | 🧑‍💼 recruteur | **V2** — langage naturel → **MCP advancedSearch** |
| GET | `/v1/candidates/{jarviProfileId}` | 🧑‍💼 recruteur | **V1** — détail depuis la réplica + masquage ; snapshot figé si dévoilé |

## Invariants

- **Masquage + visibilité appliqués côté serveur** (jamais contournables client) ; cohérent task 006/018.
- Recherche sur **profils actifs du périmètre** uniquement.
- Aucune écriture vers Jarvi depuis la recherche (lecture seule sur la réplica).

## Critères d'acceptation

- [ ] `/v1/search/candidates` interroge **Postgres** (filtres + FTS), renvoie des cartes **masquées**.
- [ ] Différenciants showcase-recruiter-webapp-saas filtrables (présence Maroc incluse).
- [ ] Résolution dévoilé/favori via la projection ; coordonnées masquées si non dévoilé ; `HIDDEN` exclu.
- [ ] Profils hors-périmètre / désactivés exclus des résultats.
- [ ] `make api-quality` vert ; tests fonctionnels (masquage, visibilité, exclusion hors-périmètre).

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` / `api-conventions.md` : routes de recherche révisées.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` : déjà révisé (référence, 2026-06-23).
