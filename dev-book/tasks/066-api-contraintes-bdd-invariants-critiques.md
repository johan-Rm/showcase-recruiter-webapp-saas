# TASK 066 — API : matérialiser en base les invariants critiques documentés

- **Créée le** : 2026-07-11.
- **Statut** : ⏸️ différée — base de dev réinitialisée avant stabilisation du DDL.
- **Type** : dette critique — intégrité PostgreSQL / DDL.
- **Dépend de** : tâche `002` (modèle de données), tâche `005` (crédits / reveal), tâche `017`
  (présence Maroc).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`

## Contexte

La documentation projet pose un invariant non négociable : l'intégrité est garantie en base
par FK, UNIQUE, NOT NULL et CHECK, pas seulement par le code applicatif.

Constat de review : plusieurs commentaires indiquent des `CHECK` garantis en base, mais le schéma
ne matérialise pas encore toutes les contraintes CHECK attendues.

Décision dev du 2026-07-12 : la base de développement sera détruite puis recréée de zéro. Aucune
migration corrective n'est nécessaire à ce stade ; ce sujet reste à traiter lors de la stabilisation
du DDL avant environnement partagé / préproduction.

## Objectif

Aligner le schéma PostgreSQL réel avec les invariants critiques documentés.

## Périmètre

- Matérialiser dans le DDL PostgreSQL les contraintes critiques :
  - `credit_wallet.balance >= 0` ;
  - `credit_transaction.amount <> 0` ;
  - montants embarqués `MonetaryAmount.amount >= 0` pour les salaires ;
  - cohérence salaire min / attendu si exprimable sur les colonnes réelles ;
  - `presence_period.end_date >= presence_period.start_date` ;
  - `candidate_option.ends_at >= candidate_option.starts_at` ;
  - CHECK enums si Doctrine/PostgreSQL ne les matérialise pas déjà.
- Vérifier les contraintes d'unicité documentées :
  - `candidate.jarviProfileId` ;
  - `candidate_jarvi.candidate` ;
  - `credit_wallet.organization` ;
  - `revealed_profile(organization, candidate)` ;
  - `favorite(organization, candidate)` ;
  - `category_code(inCodeSet, codeValue)` ;
  - `category_code_translation(categoryCode, locale)`.
- Ajouter ou mettre à jour les tests d'intégrité pertinents.
- Ajouter `doctrine:schema:validate` dans le point de contrôle qualité si absent du script global.

## Invariants

- Toute corruption contournant Symfony Validator doit être refusée par PostgreSQL.
- Si des migrations sont introduites plus tard, elles doivent préserver les données existantes ou
  documenter le nettoyage préalable.
- Le reveal reste protégé par transaction applicative + contrainte base.

## Critères d'acceptation

- [ ] Les CHECK critiques existent dans le schéma réel.
- [ ] `doctrine:schema:validate` passe.
- [ ] Des tests ou validations prouvent au moins `balance < 0`, `amount = 0` et période inversée refusés.
- [ ] `make api-quality` vert.

## Hors périmètre

- Procédure RGPD de hard delete.
- Refonte du modèle de données.
