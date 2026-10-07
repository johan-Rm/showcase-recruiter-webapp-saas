# dev-book

Journal de développement du projet showcase-recruiter-webapp-saas.

| Dossier ou fichier    | Contenu                                              |
| --------------------- | ---------------------------------------------------- |
| [`roadmap.md`](./roadmap.md) | Les phases du projet et leurs jalons          |
| [`todo.md`](./todo.md) | Le reste à faire, par thème                         |
| [`cadrage/`](./cadrage/)   | Cadrage produit par espace : parcours, écrans, règles métier |
| [`tasks/`](./tasks/)       | Feature ou correction formalisée                     |
| [`tasks/journal/`](./tasks/journal/) | Compte rendu hebdomadaire de ce qui a été livré |
| [`runbooks/`](./runbooks/) | Opération exceptionnelle / maintenance contrôlée     |
| [`resources/`](./resources/) | Ressources techniques (collections Insomnia, échantillons, SQL) |

`roadmap.md` et `todo.md` sont **à la racine** parce qu'ils portent sur le projet entier, pas sur un
espace : les ranger dans `cadrage/` les aurait mis au même niveau que le cadrage d'un écran.

Trois choses ont quitté ce dossier et n'y reviennent pas : les **maquettes** ont leur atelier à la
racine ([`ui-ux-design/`](../ui-ux-design/)), le **brief et les questionnaires** aussi
([`requirements/`](../requirements/)), et la **charte de marque** vit avec le design system dans
[`docs/showcase-recruiter-webapp-saas/content/4.design/`](../docs/showcase-recruiter-webapp-saas/content/4.design/).

## Conventions

- **cadrage/** — sources de cadrage du projet : brief client, brainstorm, parcours
  utilisateurs, roadmap, infrastructure. Documents d'entrée, peu modifiés.
- **tasks/** — une tâche = un fichier markdown décrivant le besoin, le périmètre, les
  critères d'acceptation et les étapes. Sert de spec avant implémentation.
- **runbooks/** — une procédure = un fichier markdown reproductible (préconditions,
  étapes, rollback, vérifications) pour une opération sensible ou exceptionnelle.
- **tasks/journal/** — une semaine = un fichier `AAAA-SNN.md` (ex. `2026-S29.md`) listant ce qui
  a été livré, regroupé par thème. Sous-dossier de `tasks/` : le journal raconte ce que les
  tâches ont produit, et suit le même chemin de publication.
- **resources/** — ressources techniques consommées par l'outillage ou la recette : collections
  Insomnia, échantillons de payload, requêtes SQL.

Nommage des **tasks** : **un préfixe et un compteur par espace**, `PRÉFIXE-NNN-slug-court.md`
(ex. `ENT-001-selection-de-la-table.md`) — cf.
[git-workflow.md](../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md#numérotation-des-tâches--un-compteur-par-espace).
La **date** (création, jalons) vit **dans** le document, pas dans le nom. Le sous-dossier
`journal/` fait exception : il est daté par semaine, pas numéroté.
