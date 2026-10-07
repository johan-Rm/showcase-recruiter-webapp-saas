# TASK API-011 — API : les mots-clés cherchent dans les compétences, et se relient en OU

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 42 tests, 340 assertions.
- **Type** : API — moteur de recherche, changement de comportement.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28 · task 164 (la mesure qui a révélé
  le défaut).

## Contexte

Le champ « Mots-clés » **ne fait pas ce que l'écran promet**. L'aide annonce « Recherche libre sur
l'ensemble du profil » ; le code exécute :

```php
LOWER(j.headline) LIKE '%…%'
```

Une sous-chaîne sur le **titre seul**, sans index plein texte ni trigramme. C'est toute l'explication
de la surprise du 2026-07-27 : `développeur` ramenait 256 profils, `développeur` + `frontend` en
ramenait **1** — un seul titre contenait la chaîne.

## Décisions

### Les mots-clés cherchent là où vit la matière

Le `summary` est vide sur 98,5 % des profils. « L'ensemble du profil » se réduit donc au **titre** et
aux **compétences** — ces dernières étant présentes sur 2 379 profils, avec **27,8 entrées en
moyenne**. C'est là que se trouve ce qu'un mot-clé cherche.

### Plusieurs mots-clés se relient en OU

Décision produit. Elle **inverse le comportement actuel** : le champ élargira au lieu de rétrécir.

Deux conséquences à traiter dans la même tâche :

- **le OU vaut entre les mots-clés entre eux**, pas face aux autres critères — poste, expérience et
  études continuent de restreindre ;
- **le jeu va gonfler vite.** Compétences (27,8 par profil en moyenne) plus OU : sans hiérarchie, le
  recruteur reçoit un grand jeu indifférencié. C'est pourquoi `API-005` et `API-006` — l'explication
  de correspondance et l'ordre qui en découle — ne sont pas des compléments mais la **contrepartie**
  de ce changement.

### Une structure déjà connue du produit

La recherche assistée produit des groupes de mots-clés avec **ET entre les groupes, OU à
l'intérieur**. Le formulaire classique vise la même structure plutôt que d'en inventer une seconde.

### La seule facette multivaluée

Le traducteur de critères applique « le dernier gagne » : deux valeurs sur une même facette, la
seconde remplace la première. Les mots-clés font **exception** — ils s'accumulent. Poser un second
mot-clé élargit la recherche, là où poser un second de n'importe quelle autre facette la remplace.

### Une fonction DQL pour atteindre les compétences

Les compétences vivent dans une colonne **JSON**, et Postgres n'a pas de `lower(json)`. DQL ne
connaissant pas `CAST`, elles étaient tout simplement inatteignables depuis le QueryBuilder. Une
fonction `TEXT(expr)` d'une quinzaine de lignes le règle, et évite d'ouvrir une requête native rien
que pour ce critère.

**Ce n'est pas une façon d'indexer une recherche** : lire un JSON en texte interdit tout index et
impose un parcours complet. C'est tenable sur 2 561 profils, ça ne le restera pas — la recherche
plein texte décidée pour ce projet (Postgres FTS + `pg_trgm`) reste à construire, et aucun index GIN
n'existe aujourd'hui.

## Vérification

**La mesure refaite sur le vivier de dev** (2 561 profils, visibles et consentants) :

| Requête | Avant | Après |
| --- | --- | --- |
| `développeur` | 35 | 35 |
| `développeur` + `frontend` | **0** | **36** |

Deux mots-clés ramenaient **zéro** profil : aucun titre ne contenait les deux chaînes. Ils en
ramènent 36 — le champ élargit désormais au lieu de rétrécir.

**Correction d'une attribution** : le « 256 → 1 » consigné en task 164 venait du champ **Intitulé de
poste** (`job`, qui passe par le dictionnaire de concepts), pas des mots-clés. Sur `q` seul, les
chiffres étaient 35 → 0. Le défaut était le même, en pire.

`CandidateKeywordSearchTest` (3 tests) : un mot-clé trouve une compétence que le titre ne dit pas ;
un second mot-clé ajoute des profils sans en retirer ; un critère d'un autre ordre continue de
restreindre malgré un mot-clé satisfait. Avec `SearchTest` et `CandidateTest` : **42 tests, 340
assertions**.

## Reste à faire

- **L'aide du champ à l'écran** décrit encore « une recherche libre sur l'ensemble du profil » : à
  reprendre côté espace entreprise, en disant que plusieurs mots **élargissent**.
- **L'ordre du jeu** (`API-006`) devient maintenant nécessaire : les correspondances partielles
  existent enfin, et sans hiérarchie le recruteur reçoit un grand jeu indifférencié.
