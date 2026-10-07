# TASK API-013 — API : l'outillage de recherche, extension et index trigrammes

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28.
- **Type** : API — infrastructure de base de données, aucun changement de comportement.
- **Réf** : `API-009` (les suggestions) · `API-011` (les mots-clés) · décision de cadrage du
  2026-07-06 (« recherche serveur Postgres FTS + `pg_trgm` »), restée sans suite.

## Contexte

Trois recherches livrées cette semaine cherchent par **sous-chaîne** — `LIKE '%…%'` —, la forme
qu'aucun index classique ne sait servir. Mesuré sur le vivier de dev (2 561 profils) :

| Requête | Avant |
| --- | --- |
| Recherche par mot-clé | **92 ms**, parcours complet |
| Suggestion sur un poste | 90 ms |
| Suggestion sur une compétence | **667 ms** |

La décision de juillet prévoyait `pg_trgm`. **Rien n'avait été fait** : la seule extension présente
était `plpgsql`.

Bonne surprise en la vérifiant : l'utilisateur de base est **superuser** et `pg_trgm` 1.6 est déjà
dans l'image, avec `unaccent` et `fuzzystrmatch`. Le verrou supposé n'existait pas.

## Décisions

### Une commande, parce que ni Doctrine ni le schéma ne savent le faire

`app:search:index`. Doctrine déduit les tables des entités, mais il ne crée **ni extension, ni index
sur une expression** (`lower(headline)`) — et le projet n'a aucune migration. Sans un endroit d'où
ça se rejoue, l'index existe sur la machine de celui qui l'a tapé, et nulle part ailleurs.

Tout est en `IF NOT EXISTS` : la commande se rejoue sans risque, ce qui permet de la brancher dans
l'amorçage plutôt que de compter sur une intervention.

### Ce n'est PAS une dépendance dure

Sans l'extension, tout continue de fonctionner : les recherches utilisent `LIKE`, qui n'a besoin
d'aucun index — elles sont seulement plus lentes. Une base de production qui ne l'aurait pas encore
reçue **dégrade, elle ne tombe pas**.

C'est ce qui rend le déploiement non bloquant, et c'est aussi pourquoi cette tâche ne change aucun
comportement : elle ne fait qu'accélérer ce qui existe.

### `--check` est la moitié utile

L'écart entre la production et le développement ne se voit pas au déploiement — il se voit à la
**première recherche**, quand une requête s'effondre. `app:search:index --check` dit ce qui manque
sans rien créer, et sort en échec : de quoi le constater avant, et l'intégrer à une supervision.

### Où ça se rejoue

| Environnement | Comment |
| --- | --- |
| dev | `make dev-schema` l'appelle — donc `dev-setup` aussi |
| preprod | `make preprod-reset` l'appelle (elle se rase, elle rejouera) |
| **prod** | **manuel** : `make prod-...` n'existe pas pour ça, la commande se passe à la main |

La prod est tenue par Johan. La commande à y passer, une fois :

```bash
docker compose -f docker-compose.prod.yml exec -T api php bin/console app:search:index
docker compose -f docker-compose.prod.yml exec -T api php bin/console app:search:index --check
```

La seconde ligne n'est pas décorative : c'est elle qui prouve que la première a pris.

## Ce que ça règle, et ce que ça ne règle pas

**Réglé** — la recherche par mot-clé passe de **92 ms à 6 ms**, Postgres abandonnant le parcours
complet pour un `Bitmap Index Scan` sur les index trigrammes. Quinze fois plus rapide, mesuré.

**NON réglé — les suggestions sur les compétences.** Leurs 667 ms ne viennent pas de la comparaison
de chaînes mais de l'**éclatement du JSON** : 2 561 lignes explosées en 71 000 compétences à chaque
frappe. Aucun index trigramme ne peut aider cette forme de requête. Le vrai remède est un **index
matérialisé des compétences distinctes** avec leur nombre de profils, rafraîchi à l'ingestion — qui
apporterait en prime le regroupement bilingue qui manque à `API-009`. C'est une autre tâche.

## Ce que ça ouvre

Les index trigrammes rendent possible la **similarité** (`similarity()`, opérateur `%`) : c'est la
tolérance aux fautes que `API-009` promettait et n'a pas livrée. Elle devient une tâche courte, à
condition de garder le repli sur préfixe quand l'extension est absente.

## Vérification

- `app:search:index --check` sur une base neuve : signale l'extension et les quatre index manquants,
  et sort en échec.
- `app:search:index` : les pose, puis `--check` passe.
- Gain mesuré sur le vivier de dev avec les index en place : **92 ms → 6 ms**, plan passé en
  `Bitmap Index Scan`.
