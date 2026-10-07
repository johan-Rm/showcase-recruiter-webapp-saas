# TASK 076 — API : dévoilement groupé, plafond de lot et imputation par recherche

- **Créée le** : 2026-07-12.
- **Statut** : ⬜ à faire.
- **Type** : cœur sensible (crédits).
- **Dépend de** : tâche `005` (dévoilement unitaire), `065` (candidat exposable), `072` (recherche).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/credits_et_devoilement.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md` § Atomicité

## Contexte

Le dévoilement **unitaire** est livré (task `005`). Le cockpit en ajoute deux formes groupées :
**dévoiler la sélection** (profils cochés) et **« Tout dévoiler »** un sous-ensemble (tab d'état).

Sur un tab « Tous » d'une recherche large, **« Tout dévoiler » peut viser des centaines de profils
d'un clic**. Le tout-ou-rien protège d'un solde insuffisant — **pas d'un solde suffisant dépensé par
erreur**.

## Objectif

Un **endpoint batch** atomique, plafonné, dont le coût est **recalculé serveur**, et dont la
consommation est **imputée à la recherche** courante.

## Périmètre

- **Endpoint batch** : `POST /v1/organizations/{id}/revealed-profiles` avec `{ candidateIds: [...],
  searchId }` — **jamais N appels client**.
- **Coût recalculé serveur** : les candidats **déjà dévoilés** du lot sont **gratuits** (idempotence).
- **Tout-ou-rien** : solde insuffisant pour le lot → **`402`, aucun débit**.
- **Atomicité** : une **transaction unique**, **verrou pessimiste** sur le wallet partagé.
- **Plafond de lot** : au-delà de **N** candidats, l'API **refuse** (`422`). Valeur portée par
  `CreditPolicy` et exposée via `/v1/config` — **jamais en dur côté front**. Valeur à fixer produit.
- **Imputation par recherche** : rattacher la `CreditTransaction` à la **recherche** (en plus du
  recruteur) → alimente le compteur « crédits utilisés par cette recherche » du panneau de contrôle.
  ⚠️ Le **solde reste unique et partagé** : il n'y a **pas d'enveloppe par recherche**.
- **Répartition par membre** : endpoint d'agrégation (qui a dévoilé combien) pour la modale crédits —
  s'appuie sur la traçabilité déjà en place.
- **Solde exposé** : un seul vocabulaire côté produit — **le solde restant** (la consommation vit dans
  la modale).

## Invariants

- Débit + `CreditTransaction` + `RevealedProfile` dans **une seule transaction**.
- **Idempotent** : un candidat déjà dévoilé ne redébite jamais.
- **Solde ≥ 0** (backstop `CHECK` en base).
- Le dévoilement reste **partagé `Organization`**, quelle que soit la recherche d'origine.
- Seuls des candidats **exposables** peuvent être dévoilés (task `065`).

## Critères d'acceptation

- [ ] Un lot mixte (dévoilés + non dévoilés) ne débite que les non-dévoilés.
- [ ] Solde insuffisant → `402`, **aucun profil dévoilé**, aucun débit.
- [ ] Lot au-delà du plafond → `422`, aucun débit.
- [ ] Deux appels concurrents sur le même wallet ne provoquent pas de double-dépense.
- [ ] La `CreditTransaction` porte le **recruteur** et la **recherche**.
- [ ] Le compteur « crédits par recherche » est cohérent avec le journal.
- [ ] `make api-quality` vert.

## Hors périmètre

- Achat / recharge de crédits (billing Stripe, `ROLE_COMPANY_OWNER`).
- **Double confirmation** au-delà d'un seuil : garde-fou **UI**, à porter côté front.
