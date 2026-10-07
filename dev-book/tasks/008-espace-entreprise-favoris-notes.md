# TASK 008 — Espace Entreprise : favoris partagés

- **Créée le** : 2026-06-19.
- **Statut** : ✅ réalisée le 2026-06-19 (favoris idempotents + suppression, isolation
  multi-tenant). Accès révisé par `023` : Organization validée requise.
- **Périmètre V1** : shortlist de favoris partagée au niveau de l'`Organization`. Réutilise les
  patterns lecture (Output + Provider) et écriture (Input + Processor) + le voter multi-tenant
  (tâches 003-006).
- **Hors périmètre V1** : notes recruteurs (`ProfileNote`) → V2, assignation / pipeline de suivi
  (V2), invitations & membres (tâche 009), alertes `SavedSearch` (V2).

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{id}/favorites` | 🧑‍💼 membre validé | shortlist partagée de l'entreprise |
| POST | `/v1/organizations/{id}/favorites` | 🧑‍💼 membre validé | `{candidateId}` → ajoute (idempotent) |
| DELETE | `/v1/organizations/{id}/favorites/{candidateId}` | 🧑‍💼 membre validé | retire de la shortlist |

## Règles métier (cf. data-model.md)

- **Favoris partagés** au niveau de l'`Organization` (espace commun) ; on trace toujours **quel
  recruteur** (`addedBy`).
- `Favorite` : **unicité `(organization, candidate)`** → ajout **idempotent** (renvoie l'existant, pas
  de doublon). Suppression par `candidateId`.
- `ProfileNote` : modèle conservé pour V2, aucune route V1 exposée.
- Accès : **membre d'une `Organization` validée** (voter `OrganizationVoter::ACCESS`) ; isolation
  multi-tenant stricte.

## Structure (cf. api-structure.md)

- `ApiResource/Input/FavoriteInput` ; `Output/FavoriteOutput`.
- `State/Processor/FavoriteProcessor` (POST/DELETE) ;
  `State/Provider/FavoritesProvider` (enveloppe plate).
- Résolution `Candidate` via `CandidateRepository::findOneByUuid` ; recruteur courant via l'`Organization`.
- Pas de nouveau voter (réutilise `OrganizationVoter::ACCESS` depuis `023`).

## Tests (tests/Functional)

- Ajout favori → 201 ; **ré-ajout** du même candidat → idempotent (pas de doublon).
- Liste favoris (partagée) ; suppression → retiré.

## Critères d'acceptation

- [ ] Favoris : ajout idempotent (`(organization, candidate)` unique), liste, suppression.
- [ ] Isolation multi-tenant (403 cross-tenant).
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
