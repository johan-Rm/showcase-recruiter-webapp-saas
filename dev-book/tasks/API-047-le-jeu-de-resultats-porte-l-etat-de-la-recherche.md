# TASK API-047 — le jeu de résultats porte l'état de la recherche

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — l'ouverture d'une recherche porte `favoriteIds`,
  `dismissedIds` et `seenIds` en plus de `resultIds` ; au-delà de la première page les quatre listes
  sont absentes, et les périmètres `?scope=` ne portent que la leur.
- **Type** : Backend API — enrichissement du contrat de sortie, sans changement de règle métier ni de
  modèle de données.
- **Tests** : fonctionnels phpunit sur `GET /v1/searches/{id}/results` — la page 1 porte les quatre
  listes, la page 2 n'en porte aucune, les deux périmètres `?scope=` restent inchangés, `favoriteIds`
  exclut un profil écarté, `seenIds` ne porte que le lecteur courant.
- **Réf** : `ENT-015` (qui garde `GET /favorites` en vie faute de cette liste) · `API-014` (le
  périmètre `?scope=` à valeur unique, inchangé ici) · `API-002` et `API-006` (l'ordre unique du jeu
  et de `resultIds`) · aval : `ENT-125`, qui consomme ce contrat ·
  `api/src/State/SearchResultsBuilder.php`.

## Contexte

**Ouvrir une recherche coûte quatre appels, en deux vagues.** Le client demande d'abord le jeu, puis,
sa réponse arrivée, trois appels de rattrapage : `GET /favorites`, `?scope=favorites`,
`?scope=dismissed`.

Ces trois-là n'existent que pour combler un trou du contrat : **l'API ne sait dire l'état de travail
d'une recherche que carte par carte** — `isFavorite`, `isDismissed`, `isSeen` voyagent sur les fiches
servies. Une page en sert trente. Un profil retenu qui figure en page cinq est donc **compté sans
être connu** : `ENT-015` a dû conserver `GET /favorites` pour cette seule raison — peindre l'étoile
d'un retenu hors de la page chargée — et le compteur « Vus » vit aujourd'hui d'un rafistolage côté
client, faute d'une liste complète.

Le jeu, lui, est déjà servi en entier : `resultIds` porte l'ordre complet depuis `API-002`. **Il
manque les trois listes qui disent ce qu'on en a fait.**

**Mesure, sur la recherche la plus large de la base de démonstration (3 239 profils) :**

```
/results, page 1        167,1 Ko brut  →  59,6 Ko compressée
  dont resultIds        126,5 Ko brut  →  51,5 Ko compressée   ← 87 % du poids
  dont 30 fiches         40,5 Ko brut  →   7,8 Ko compressée
```

Ce bloc **repart identique à chaque page demandée**, alors qu'aucun client ne le relit : le front ne
verse que les fiches dans son pool au-delà de la première page. Les identifiants se compressent mal —
il n'y a rien à factoriser dans de l'aléatoire —, si bien que la répétition se paie aussi sur le
réseau.

Volumes réels, pour situer : **109 profils par recherche en moyenne, 149 au 90ᵉ centile**, et 3 239
pour la valeur aberrante ci-dessus. Au 90ᵉ centile, `resultIds` pèse moins de 6 Ko.

> **Le motif a changé en cours de route.** `API-048` est passée avant celle-ci et borne le jeu servi à
> cent profils : `resultIds` ne pèse plus 51,5 Ko compressés mais moins de deux. **L'argument de poids
> a donc fondu, et ce n'est plus lui qui porte cette tâche** — ce qui reste, et qui la justifie, ce
> sont les **trois appels supprimés à chaque ouverture**, et un compteur qui cesse de dépendre de ce
> qui a été chargé.

## Périmètre

- **`GET /v1/searches/{id}/results`, page 1 du périmètre par défaut**, porte trois listes
  supplémentaires **à plat**, à côté de `resultIds` :
  - `favoriteIds` — les profils retenus dans cette recherche, **écartés déduits** ;
  - `dismissedIds` — les profils écartés par le lecteur courant ;
  - `seenIds` — les profils vus par le lecteur courant.
- **Les pages 2 et suivantes ne portent aucune de ces listes, `resultIds` compris.** Les champs sont
  **absents**, jamais servis vides.
- **`api/openapi.json` régénéré** (`make dev-openapi`) : les quatre champs deviennent optionnels dans
  le schéma de sortie.

## Hors périmètre

- **`?scope=favorites` et `?scope=dismissed` restent inchangés** : leurs `items`, `total` et
  `resultIds` ne bougent pas, et ils ne portent aucune des nouvelles listes.
- **Les fiches des retenus et des écartés** : ce contrat ne sert que des identifiants.
- **Un endpoint d'état séparé** (`/searches/{id}/state`) : écarté, cf. décisions.
- **La limite du nombre de profils par recherche.** Aucun plafond n'existe aujourd'hui —
  `MAX_PER_PAGE = 200` ne borne que la pagination, pas le jeu matérialisé. Le sujet est réel mais
  n'est pas technique : il demande de dire ce qu'une recherche de 3 000 profils veut dire pour un
  recruteur. Il se cadre à part.
- **Le poids de `GET /v1/searches`**, la collection non paginée des recherches (43 Ko pour 64 cartes).
- **Toute suppression d'appel côté client** : c'est `ENT-125`.

## Décisions de cadrage

### Les listes sont à plat, pas dans un objet `sets`

`resultIds` vit déjà à ce niveau. Regrouper les quatre listes dirait mieux qu'elles forment un tout,
mais **déplacerait `resultIds`** — donc casserait le contrat pour tous ses consommateurs actuels, pour
un gain de lisibilité. La forme suit ce que la réponse fait déjà.

### La première page seulement, `resultIds` compris

Le client demande **toujours** la page 1 en ouvrant une recherche : il reçoit l'état complet à ce
moment-là. Les pages suivantes ne servent qu'à remplir des fiches, et **le front ignore déjà
`resultIds` au-delà de la première** — il ne verse que les cartes. Retirer ces listes des pages
suivantes n'a donc aucun consommateur à casser, et économise 51,5 Ko compressés par page sur une
recherche large.

**Absents, et non vides.** Un `favoriteIds: []` en page 2 signifierait « aucun retenu », ce qui serait
faux. L'absence du champ dit « cette réponse ne parle pas de ça » ; c'est la seule forme qui ne mente
pas.

### `favoriteIds` déduit les écartés

`GET /favorites` ne les déduit pas, `?scope=favorites` si — et `ENT-015` a déjà payé cet écart : deux
sources qui répondent à la même question finissent par diverger, cette fois d'un profil écarté. La
nouvelle liste **s'aligne sur le périmètre**, pas sur l'ancienne route. Retenir et écarter sont deux
verdicts opposés, le dernier posé l'emporte (`ENT-011`).

### `seenIds` entre dans le lot

Il n'était pas dans la demande initiale, et il souffre exactement du même trou : le compteur « Vus »
du client additionne aujourd'hui le `seenCount` de la fiche et un delta tenu en mémoire pour la
session, faute de liste complète. Une liste de plus ici supprime un rafistolage là-bas.

### Ce qui est écarté, et pourquoi

- **Servir les fiches des retenus et des écartés dans la même réponse** : on regonflerait le payload
  pour du contenu affiché seulement si l'utilisateur ouvre un onglet ou un tiroir, et on dupliquerait
  des cartes déjà présentes dans le jeu.
- **Un endpoint d'état séparé**, qui garderait `/results` en collection paginée pure : plus propre en
  principe, mais il ajoute l'aller-retour que cette tâche existe pour supprimer. Il reste le repli si
  la réponse composite se révélait intenable — `/results` porte déjà `resultIds`, elle l'est à moitié.

### Les trois listes ne sont PAS bornées à cent — décidé à l'implémentation

`API-048` borne ce qu'on donne à **travailler** ; ces listes disent ce que le recruteur a **fait** de
sa recherche entière. Les couper à cent ferait mentir le compteur qu'elles existent pour rendre
juste — un profil retenu au rang 150 disparaîtrait du compte. Elles restent petites : on retient et
on écarte par dizaines, pas par milliers.

### `resultIds` quitte les pages suivantes de TOUS les périmètres — décidé à l'implémentation

La règle « au-delà de la première page, plus de listes » porte sur les **pages**, celle des trois
nouvelles listes porte sur le **périmètre**. Un `?scope=favorites` demandé en page 1 garde donc son
`resultIds` — c'est lui qui peint les lignes de l'onglet depuis `ENT-015` — mais ne reçoit aucune des
trois autres.

## Règles et cas limites

- **Ordre.** Les trois listes suivent l'**ordre du jeu**, celui de `resultIds` — c'est déjà ce que
  `?scope=dismissed` produit, et le client n'a alors rien à retrier.
- **Listes vides.** Sur la page 1 d'une recherche sans aucun retenu, `favoriteIds` vaut `[]`.
  L'absence est réservée aux pages suivantes.
- **Les écartés ne sont pas dans `resultIds`** : règle existante (`ProfileScope::excluding`),
  inchangée. `dismissedIds` est donc la seule façon de les connaître, et les deux listes ne
  s'intersectent jamais.
- **Portée du lecteur.** `favoriteIds` est celui de la **recherche** — le destinataire d'un partage
  voit les retenus de l'auteur, comme aujourd'hui. `seenIds` et `dismissedIds` sont ceux du **lecteur
  courant** : son avancement, pas celui de ses collègues.
- **Page demandée explicitement à 1** (`?page=1`) ou absente : même comportement.
- **Recherche close, recherche partagée en lecture seule** : rien de spécifique, les listes sont
  servies comme pour les autres.
- **Coût serveur.** Les trois listes se lisent sur les mêmes agrégats que les compteurs déjà servis
  par la fiche ; en tirer les identifiants ne doit pas ajouter un aller-retour base par recherche.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md` — l'enveloppe de collection y est décrite
  (`items` / `total` / `page` / `perPage`) ; y ajouter la règle **« les listes de relations
  accompagnent la première page, et elle seule »**, avec son motif.
- `api/openapi.json` — régénéré, contenu de commit et non gate.
- `dev-book/resources/insomnia/` — la collection ne portait **aucune** des routes de recherche
  actuelles ; les cinq requêtes du périmètre y entrent, avec la variable `search_id`.

## Vérification

**Requêtes réelles**, stack du worktree API, sur une recherche **partagée de 135 profils, 14 retenus,
74 vus** (`atlas.owner@demo.showcase-recruiter-webapp-saas.example`) :

```
GET …/results                  total=135 items=30 resultIds=100  favoriteIds=14  dismissedIds=0  seenIds=74
GET …/results?page=2           total=135 items=30 resultIds=ABSENT favoriteIds=ABSENT dismissedIds=ABSENT seenIds=ABSENT
GET …/results?scope=favorites  total=14  items=14 resultIds=14   favoriteIds=ABSENT dismissedIds=ABSENT seenIds=ABSENT
GET …/results?scope=dismissed  total=0   items=0  resultIds=0    favoriteIds=ABSENT dismissedIds=ABSENT seenIds=ABSENT
```

Absents et non vides : la page 2 ne porte aucune des quatre clés.

**La règle qui avait coûté un compteur faux** (`ENT-015`) vérifiée en écrivant vraiment, puis base
remise en l'état :

```
DISMISS d'un profil retenu → HTTP 201
   favoriteIds 14 → 13 (l'écarté n'y est plus) · dismissedIds 0 → 1 · total 135 → 134
RESTORE                    → HTTP 201
   favoriteIds=14 · dismissedIds=0 · total=135
```

**Tests joués**

```
phpunit --filter SearchWorkStateTest                          OK (5 tests, 55 assertions)
phpunit Search{LoadLimit,FavoritesScope,ProfileReview,Lists,,Counts}Test
                                                              OK (40 tests, 379 assertions)
```

## Livré

- `api/src/State/SearchResultsBuilder.php` — la notion d'**ouverture** (première page de la pile), et
  la conversion des identifiants internes en identifiants publics dans l'ordre du jeu, par le même
  chemin que `resultIds`.
- `api/src/ApiResource/Output/SearchResultsOutput.php` — trois listes nullables, `resultIds` nullable,
  et le pourquoi de l'absence plutôt que du vide.
- `api/tests/Functional/SearchWorkStateTest.php` — cinq cas, dont la portée par lecteur sur une
  recherche partagée.
- `api/openapi.json` — régénéré.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md` — la règle des listes de relations.
- `dev-book/resources/insomnia/` — cinq requêtes et la variable `search_id`.

## Reste à faire (hors périmètre)

- **Le front appelle toujours `/favorites`** à l'ouverture : c'est `ENT-125` qui supprimera l'appel et
  lira ces listes. Jusque-là, l'état voyage deux fois.
- **La collection Insomnia garde ses requêtes périmées** sur `/organizations/{id}/saved-searches`, une
  route qui n'existe plus. Les nouvelles sont ajoutées à côté ; le ménage appartient à une autre
  tâche.
- **Trois requêtes de plus à l'ouverture côté serveur** — une par liste. Elles sont indexées et
  petites, et le regroupement n'a pas été cherché : il se justifierait si `API-050` en faisait le
  patron pour cinquante-cinq recherches d'un coup.

## Définition de terminé

- [x] La page 1 du périmètre par défaut porte `resultIds`, `favoriteIds`, `dismissedIds`, `seenIds`.
- [x] Les pages suivantes ne portent aucun de ces quatre champs.
- [x] `?scope=favorites` et `?scope=dismissed` rendent exactement ce qu'ils rendaient avant.
- [x] Un profil retenu puis écarté ne figure pas dans `favoriteIds`.
- [x] `seenIds` et `dismissedIds` ne portent que l'avancement du lecteur courant.
- [x] Les listes suivent l'ordre du jeu.
- [x] Tests fonctionnels phpunit couvrant les cinq points ci-dessus.
- [x] `api/openapi.json` régénéré et à jour dans le commit.
- [x] `api-conventions.md` porte la règle de la première page.
