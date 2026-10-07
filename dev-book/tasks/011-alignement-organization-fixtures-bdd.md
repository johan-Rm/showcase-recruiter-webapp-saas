# TASK 011 — Alignement Organization, fixtures et préparation cohérence BDD

## Objectif

Aligner le modèle développé sur le vocabulaire schema.org `Organization`, enrichir les données de
démonstration pour couvrir quasiment toutes les tables, puis préparer la passe de cohérence BDD à
mener ensuite.

Cette tâche ne vise pas à ajouter une fonctionnalité produit. Elle consolide le socle de données et
pose le cadre de la revue structurelle suivante.

## Périmètre

- Renommer le vocabulaire interne `Company` vers `Organization` dans le code, la migration, les
  routes, les tests, Insomnia et la documentation générée.
- Conserver les références externes Jarvi qui utilisent encore leur propre vocabulaire.
- Enrichir `AppFixtures` avec des données cohérentes pour les principaux clusters : identité,
  candidats, organisations, crédits, collaboration, notifications, premium et éditorial.
- Ajouter une note de cadrage sur le modèle commercial futur : offre commerciale, commande,
  facture, organisation cliente et fournisseur.
- Créer le cadre de la future passe de cohérence BDD : entités Doctrine, relations, contraintes,
  fixtures et conventions Symfony.

## Hors périmètre

- Refonte du modèle commercial complet : offre, commande, facture, fournisseur.
- Implémentation Stripe détaillée.
- Refonte fonctionnelle des parcours recruteur, candidat ou admin.
- Ajout d'un nouveau module produit.

## Sources à relire

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md`
- `api/src/Entity/`
- `api/src/Enum/`
- `api/migrations/`
- `api/src/DataFixtures/AppFixtures.php`
- `dev-book/resources/insomnia/`
- `dev-book/resources/sql/`

## Critères de sortie

- Le code, les routes, les tests, Insomnia et la documentation générée exposent `Organization`.
- Les fixtures couvrent quasiment toutes les tables métier sans casser les tests fonctionnels.
- Le cadrage commercial futur est documenté hors V1.
- La tâche suivante de revue BDD peut partir d'un socle de données plus représentatif.

## Validations attendues

- `make api-quality`
- Régénération de la documentation API si les contrats changent :
  `make dev-docs-generate-api`
