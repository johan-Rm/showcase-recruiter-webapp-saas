# TASK API-016 — les suggestions cessent de buter sur les accents

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Backend API — recherche.
- **Réf** : `API-009` (les suggestions) · `API-013` (l'extension et les index, et la commande qui les
  pose) · `ENT-004` (le formulaire qui les consomme, où le défaut se voit).

## Contexte

L'auto-complétion ne trouve rien quand le terme est tapé sans accent. Mesuré sur données réelles, à
travers le BFF, pendant `ENT-004` :

```
JOB q=dev   → []                                 rien
JOB q=dév   → [('Développeur logiciel', 1)]      trouvé
```

`SuggestionFinder` compare en `LOWER(x) LIKE 'terme%'`. `LOWER` replie la casse, pas les accents :
`développeur` et `developpeur` restent deux chaînes différentes.

Sur un vivier francophone, c'est le défaut le plus visible du champ : **la plupart des intitulés
français portent un accent**, et personne ne tape « développeur » avec son accent dans un champ de
recherche. Le recruteur voit une liste vide et en conclut que le vivier ne contient personne.

Le cadrage d'`API-009` annonçait d'ailleurs une saisie « tolérante aux fautes et ouverte à la
similarité ». Ni l'une ni l'autre n'est livrée ; les accents sont la moitié la moins ambitieuse et
la plus rentable.

## Décisions

### `unaccent`, et rien de plus pour l'instant

L'extension est **déjà présente dans l'image Postgres** — constaté pendant `API-013`, aux côtés de
`pg_trgm` et `fuzzystrmatch`. Aucune image ni dépendance nouvelle : il s'agit de l'activer et de
comparer sur des chaînes normalisées des deux côtés.

La tolérance aux FAUTES (similarité trigramme, seuil, classement par distance) reste hors périmètre.
C'est un autre problème — il demande d'arbitrer un seuil, donc de décider ce qu'est une
« proposition raisonnable », et il se mesure. Les accents, eux, ne sont pas une approximation : ce
sont deux écritures du même mot.

### La commande d'`API-013` est l'endroit

`app:search:index` existe déjà pour poser ce que ni Doctrine ni le schéma ne savent créer, et tout ce
qu'elle fait est idempotent. L'extension s'y ajoute, plutôt que dans une seconde commande qui
poserait la même question à la prod deux fois. `--check` la signale absente au même titre que les
index — mais en disant autre chose : un index qui manque ralentit, `unaccent` qui manque fait manquer
des propositions.

### Tranché par la mesure — pas d'index, pas d'enveloppe

`unaccent()` est **STABLE et non IMMUTABLE** (elle dépend d'un dictionnaire, donc de la
configuration) : PostgreSQL refuse de l'utiliser telle quelle dans une expression d'index. Le remède
usuel est une fonction enveloppe déclarée `IMMUTABLE`.

C'est la seule vraie décision de cette tâche : **poser l'enveloppe, ou renoncer à l'index**.

- Sans index, le préfixe normalisé se résout par parcours. Sur 860 concepts et 3358 libellés, c'est
  sans conséquence ; sur les compétences des profils, ça mérite d'être mesuré avant de conclure.
- Avec enveloppe, on gagne l'index mais on inscrit dans la base une fonction dont l'immuabilité est
  une promesse qu'on fait au moteur — changer le dictionnaire derrière invaliderait silencieusement
  l'index.

**Mesuré, sur le dictionnaire réel (860 concepts, 3358 libellés) :**

```
lower(label) LIKE 'dev%'                        0,27 ms   Bitmap Index Scan    →  4 libellés
unaccent(lower(label)) LIKE unaccent('dev%')    3,0  ms   Seq Scan             → 45 libellés
```

Trois millisecondes, et **onze fois plus de libellés trouvés**. On ne paie pas une fonction enveloppe
`IMMUTABLE` — ni la promesse d'immuabilité qu'elle ferait au moteur, qu'un changement de dictionnaire
invaliderait en silence — pour ce prix-là. Le dictionnaire est une nomenclature curée : il ne grandira
pas d'un ordre de grandeur.

Le côté COMPÉTENCES n'a pas pu être mesuré à l'échelle : la base de ce worktree ne porte que trois
profils. Sa lenteur connue vient de l'explosion du JSON, pas de la comparaison — un index sur
`unaccent` ne l'aurait de toute façon pas corrigée.

## Ce que la mesure a confirmé

Sur la base réelle : `unaccent` était **absente** (seules `pg_trgm` et `plpgsql` étaient posées),
`CREATE EXTENSION` passe — elle est bien dans l'image — et `unaccent(text)` y est déclarée **STABLE**,
ce qui ferme la porte à un index sans enveloppe.

Vérifié de bout en bout, à travers le BFF et l'API :

```
JOB q=dev    → [('Développeur logiciel', 1)]
JOB q=dév    → [('Développeur logiciel', 1)]
JOB q=softw  → [('Développeur logiciel', 1)]
```

Le synonyme anglais continue de résoudre vers le libellé canonique : la normalisation n'a rien cassé
de ce qui marchait.

## Mise en œuvre

**La normalisation vit dans le SQL, des deux côtés.** Replier les accents en PHP aurait donné une
autre table de correspondance que celle de la base : les deux côtés doivent passer par le MÊME
dictionnaire, donc par `unaccent()`.

**La disponibilité de l'extension est demandée une fois** puis mémorisée : elle ne change pas pendant
la vie du processus, et une requête de catalogue à chaque frappe serait une de trop. Sans elle, on
retombe sur la comparaison accentuée d'avant — dégradée, pas cassée, comme pour `pg_trgm`.

**Le test pose l'extension lui-même** : elle ne fait pas partie du schéma Doctrine, la base de test ne
la reçoit donc pas. La transaction du test l'emporte avec elle, ce qui fait que tous les autres tests
exercent le repli accentué.

## Critères d'acceptation

- [x] `dev` propose `Développeur logiciel`, et `dév` continue de le proposer.
- [x] La normalisation s'applique **des deux côtés** de la comparaison — le terme cherché comme la
      valeur stockée — sur les intitulés comme sur les compétences.
- [x] `app:search:index` pose l'extension, et `--check` la signale absente.
- [x] Sans l'extension, les suggestions continuent de fonctionner : accentuées seulement, comme
      aujourd'hui. Une base de production qui n'a pas reçu la commande dégrade, elle ne tombe pas.
- [x] Le temps de réponse est mesuré avant et après, et la décision de NE PAS indexer est prise sur
      ce chiffre.
