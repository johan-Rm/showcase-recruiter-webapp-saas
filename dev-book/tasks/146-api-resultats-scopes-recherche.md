# TASK 146 — API : le jeu de résultats d'une recherche, servi par la recherche

- **Créée le** : 2026-07-26.
- **Statut** : ✅ fait le 2026-07-26 — endpoint, compteur sur la carte, contrat régénéré.
- **Type** : API — surface de contrat (`api/openapi.json` régénéré).
- **Réf** :
  - `dev-book/cadrage/entreprise/ui/vue_la_table.md` · `console_contextuelle.md` (les 7 vues, les listes portées par la recherche)
  - task 072 (appartenance matérialisée, badge `+N`) · task 073 (favoris, shortlist)

## Contexte

L'API exposait deux choses sans lien : un **moteur sans état** (`GET /search/candidates?critères`) et
des **recherches** comme objets séparés. Le front devait donc **rejouer les critères** d'une recherche
pour reconstituer son jeu de résultats — alors que l'API le matérialise déjà
(`search_result_membership`, recalculé à chaque changement de critères et à chaque ingestion).

Deux vérités pour le même jeu : celui que le front rejouait à l'instant, et celui du membership qui
porte `newCount`. Ils divergent dès qu'un profil devient matchant entre deux recalculs — le badge
annonçait alors des nouveautés que la table ne montrait pas.

Et tout ce qui dépend du couple (recherche, candidat) restait hors de portée : la taille du jeu sur la
carte d'une recherche, les profils **nouveaux**, les **favoris** et la **shortlist** demandaient trois
appels de plus et autant d'intersections côté client.

## Objectif

Une seule question, une seule réponse : *« que contient cette recherche, et où en suis-je dessus ? »*

## Décisions

### `GET /v1/searches/{searchId}/results`

Le jeu de résultats servi **depuis l'appartenance matérialisée**, jamais rejoué. Chaque carte porte
son état de travail : `isNew`, `isFavorite`, `isShortlisted` — en plus de `revealed`, déjà là.

Lecture ouverte à qui peut lire la recherche : le destinataire d'un partage voit le même jeu, les mêmes
favoris et la même shortlist. Ses **nouveautés** restent les siennes (le repère de lecture est porté
par le couple recherche/recruteur).

### Les trois drapeaux sont nullables, et c'est le sens

`null` sur `/search/candidates`, qui interroge le moteur sans connaître aucune recherche. Un booléen y
répondrait « non » là où la question ne se pose pas. Ils sont portés par `CandidateCardOutput` plutôt
que par un second DTO : dupliquer vingt-cinq champs pour trois drapeaux aurait fait deux contrats à
maintenir pour une seule carte.

### Le repère de lecture se remet à jour sur les RÉSULTATS, plus sur la fiche

`GET /searches/{id}` était une lecture qui mutait : elle remettait le badge à zéro. Le front lisant la
fiche **avant** les résultats, `isNew` serait retombé à faux sur toutes les cartes avant d'avoir servi.

Le repère se remet donc à jour en servant les résultats, après les avoir marqués. La fiche redevient
une lecture pure. Le sens produit y gagne : **ouvrir une recherche, c'est regarder ses résultats** —
lire son nom et ses critères n'est pas les avoir vus.

### `resultCount` sur la recherche

La collection en affiche une par carte : un `COUNT` par recherche ferait autant d'allers-retours que
de lignes. Une requête groupée les sert toutes. La carte annonce ainsi **exactement** le jeu que la
Table servira, puisque les deux lisent la même table.

### Tri serveur, vérifiés en tête

Le tri est dans la requête, pas dans le navigateur : trié côté client il ne rangerait que la page
courante, et les profils vérifiés de la page 2 resteraient derrière les autres de la page 1.

### Les fixtures matérialisent le jeu de la recherche démo

Un chargement de fixtures ne passe ni par le processeur, ni par l'ingestion : sans ces deux lignes,
`/results` ne renvoyait rien en dev alors que la recherche a des critères — on aurait cherché le bug
côté front. Le recalculateur n'est pas injectable ici (deptrac : `DataFixtures` ne dépend que de
`Entity` et `Enum`), les appartenances sont donc posées comme de la donnée.

## Vérification

`SearchTest` : 15 tests, 114 assertions — dont l'état de travail servi sur la carte, les drapeaux
`null` hors recherche, `resultCount` égal au `total` des résultats, le 404 sur une recherche illisible,
et la fiche qui ne consomme plus la nouveauté. Suite complète, phpstan, php-cs-fixer, deptrac et
`lint:container` verts. `api/openapi.json` régénéré.

## Livré

- `SearchResultsProvider` + l'opération `/searches/{searchId}/results` sur `CandidateSearch`.
- `SearchResultMembershipRepository` : la page triée, et les totaux en une requête groupée.
- `CandidateCardOutput` : `isNew`, `isFavorite`, `isShortlisted` (nullables).
- `SavedSearchOutput.resultCount`, servi par les deux providers et les deux processeurs.
- `SavedSearchProvider` : plus de remise à zéro du repère.
- `SearchNewCountReader::markerFor`.
- Fixtures : appartenances de la recherche démo.

## Reste à faire (hors périmètre de cette tâche)

- **Vue « Favoris » et bloc shortlist côté front** : les drapeaux sont servis, l'écran reste à câbler
  (lane `feature/espace-entreprise`).
- `openToOpportunities` (colonne 12 de la Table) n'existe **pas dans le modèle** : question de contrat
  Jarvi avant toute tâche.
- Le front peut retirer sa traduction critères → query (`services/search/criteria.ts`) : elle
  n'existait que pour rejouer une recherche.
