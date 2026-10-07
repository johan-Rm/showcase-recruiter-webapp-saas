# TASK 078 — API : retrait des notions révoquées (assignation, salaire, écartés)

- **Créée le** : 2026-07-12.
- **Statut** : ⬜ à faire.
- **Type** : nettoyage / dette.
- **Dépend de** : tâches `073`, `074` (pour ne pas retirer un champ encore utilisé).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/les_2_outils.md` §6
  - `dev-book/cadrage/entreprise/vue_d_ensemble.md` §4

## Contexte

Trois notions sont **révoquées produit** (ni V1 ni V2). Elles survivent dans le code et le modèle —
c'est du code mort **qui a l'air d'une feature** : un développeur ou une IA qui lit `CandidateAssignment`
suppose que l'assignation existe, et la câble.

| Notion | Décision |
| --- | --- |
| **Assignation** (`CandidateAssignment`, verbe *Assigner*) | révoquée (2026-07-08) |
| **Salaire** (`salaryExpected`, `salaryMin`) | retiré du produit (2026-07-12) — plus filtrable, plus affiché |
| **Écartés** (`discarded`) | retiré (2026-07-12) — bouton, KPI, champ et filtre |

## Objectif

Que le code cesse de décrire un produit qui n'existe pas.

## Périmètre

- **Assignation** : retirer l'entité `CandidateAssignment`, `AssignmentProcessor`, les routes, les
  voters associés, les fixtures et les tests. Purger la notion des DTO et de l'OpenAPI.
- **Salaire** : retirer de la **sérialisation** et des **filtres**.
  ⚠️ **Les champs restent en base** (données déjà ingérées, coût de réingestion) — ils ne sont
  simplement **plus exposés ni filtrables**. Documenter ce choix : c'est une **exposition** qu'on
  retire, pas une donnée qu'on détruit.
- **Écartés** : aucune notion `discarded` ne doit apparaître (champ, filtre serveur implicite, KPI).
  Vérifier qu'elle n'a **jamais été implémentée** — elle ne vit peut-être que dans le mockup.
- **Notifications** : purger la notification « X a assigné le profil à Y » (donnée de démo périmée).
- Vérifier que rien dans le front (`webapp/`) ne référence ces notions.

## Invariants

- Aucune destruction de donnée en base **sans décision explicite** : le salaire est **dé-exposé**, pas
  supprimé.
- Les migrations restent réversibles.

## Critères d'acceptation

- [ ] `grep -ri "assignment\|assigner"` ne retourne plus rien dans `api/src` ni `webapp/`.
- [ ] Le salaire n'apparaît dans **aucune** réponse API ni **aucun** filtre.
- [ ] Aucune notion `discarded` / « écarté » dans le code.
- [ ] L'OpenAPI ne décrit plus ces capacités.
- [ ] Les tests fonctionnels qui les couvraient sont **supprimés** (pas commentés).
- [ ] `make api-quality` vert.

## Hors périmètre

- Suppression physique des colonnes de salaire (décision à part, si un jour elle se justifie).
