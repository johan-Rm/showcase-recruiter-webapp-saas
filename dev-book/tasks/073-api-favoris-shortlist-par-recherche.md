# TASK 073 — API : favoris et shortlist rattachés à une recherche (refonte de la task 008)

- **Créée le** : 2026-07-12.
- **Statut** : ✅ faite (2026-07-14).
- **Type** : refonte de modèle / rupture avec l'existant livré.
- **Dépend de** : tâche `072` (recherche persistée), `005` (dévoilement), `065` (candidat exposable).
- **Remplace** : tâche `008` (short list partagée `Organization`) — **révoquée**.
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/les_2_outils.md` §4
  - `dev-book/cadrage/entreprise/vue_d_ensemble.md` §2

## Contexte

La task `008` a livré une **short list unique, partagée `Organization`** (entité `Favorite`, route
`/favoris`). La décision du **2026-07-12 la révoque** : ce sont désormais **deux listes distinctes**,
**portées par la recherche**.

| Liste | Où | Sur quoi | Contrainte |
| --- | --- | --- | --- |
| **Favoris** | La Table | profils **masqués** OK | aucune — filet large |
| **Shortlist** | La Visionneuse | profils **dévoilés** uniquement | **`shortlist ⊆ dévoilés`** |

Seuls le **dévoilement** et le **solde de crédits** restent partagés entreprise.

## Objectif

Deux collections **indépendantes**, rattachées à une **recherche**, avec l'invariant serveur
`shortlist ⊆ dévoilés` garanti **à la source** (et non filtré à l'export).

## Périmètre

- `Favorite` : ajouter la clé vers la **recherche** (`SavedSearch`) ; unicité `(search, candidate)`.
- Nouvelle collection **`Shortlist`** (nom à confirmer) : `(search, candidate, addedBy)`, unicité
  `(search, candidate)`.
- **Refus serveur `409`/`422`** si le candidat visé par la shortlist **n'est pas dévoilé** pour
  l'organisation — l'invariant ne dépend pas de l'UI.
- Routes scopées à la recherche :
  - `GET/POST/DELETE /v1/searches/{id}/favorites`
  - `GET/POST/DELETE /v1/searches/{id}/shortlist`
- **Autorisation** : écriture réservée à l'**auteur** de la recherche (une recherche partagée est en
  **lecture seule**, cf. `072`).
- **Migration de l'existant** : les `Favorite` actuels (portée `Organization`) doivent être rattachés
  à une recherche — **stratégie à trancher** (recherche « Reprise » par recruteur ? abandon des
  données de recette ?). Documenter le choix dans la migration.
- Retirer les routes `/favoris` et `/devoiles` du contrat s'il y a lieu (ce sont désormais des **tabs
  d'état** côté front, calculés dans le périmètre d'une recherche).

## Invariants

- **`shortlist ⊆ dévoilés`** — garanti serveur, à l'écriture.
- **Favoriser fonctionne sur un profil masqué** ; shortlister non.
- Favoris et shortlist sont **indépendants** : aucun état progressif, aucune hiérarchie entre eux.
- Un profil sort du périmètre Jarvi → les listes le conservent (historique), mais aucune **nouvelle**
  entrée n'est possible (règle « candidat exposable », task `065`).

## Critères d'acceptation

- [ ] Un favori peut être posé sur un profil **masqué**.
- [ ] Shortlister un profil **non dévoilé** est **refusé par l'API** (pas seulement désactivé en UI).
- [ ] Favoris et shortlist d'une recherche sont indépendants (l'un n'implique pas l'autre).
- [ ] Un membre **non auteur** ne peut écrire dans les listes d'une recherche partagée (403).
- [ ] Migration des `Favorite` existants exécutée et **documentée**.
- [ ] `make api-quality` vert.

## Décisions de mise en œuvre

- **Migration des `Favorite` existants : abandon des données de recette.** Le projet n'a aucune
  migration versionnée — le schéma est recréé depuis le mapping Doctrine (`make dev-schema`,
  « migrations différées à la prod »). Les `Favorite` de portée `Organization` ne vivaient qu'en
  fixtures et en recette : la table est redéfinie (`search_id` remplace `organization_id`, unicité
  `(search, candidate)`) et `AppFixtures` rattache désormais les deux listes à la recherche de démo.
  Aucune reprise à écrire ; **la preprod perd ses favoris de recette** — c'était le choix retenu
  contre une recherche « Reprise » par recruteur.
- **Invariant `shortlist ⊆ dévoilés` gardé dans `SearchListWriter`** (Domain), à l'écriture. La
  question « ce candidat est-il dévoilé ? » était jusqu'ici reposée en ligne à **trois endroits**
  sans service commun : elle est centralisée dans `RevealedProfileRepository::isRevealed()`.
- **Effet de bord traité** : `interestedCompanies` (activité candidat, tâche 019-A) comptait des
  lignes `Favorite`. Un candidat pouvant désormais être favori dans plusieurs recherches d'une même
  entreprise, le compteur sur-comptait — il compte maintenant les **organisations distinctes**.
- Les routes `/organizations/{id}/favorites` sont **supprimées** ; `/organizations/{id}/revealed-profiles`
  est conservée (le dévoilement reste une notion d'entreprise, pas de recherche).

## Hors périmètre

- Export de la shortlist → tâche `077`.
- Notes et statuts internes (🔭 V2).
