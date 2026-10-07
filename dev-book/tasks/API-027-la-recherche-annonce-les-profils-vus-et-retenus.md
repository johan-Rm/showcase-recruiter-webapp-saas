# TASK API-027 — api : la recherche annonce les profils vus et retenus

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — la carte d'une recherche annonce où en est son lecteur et
  ce qu'elle a produit, chacun avec sa règle de portée. 5 tests, 70 assertions.
- **Type** : Backend API — deux champs s'ajoutent au contrat, aucun résultat de recherche ne change.
- **Tests** : fonctionnels sur `GET /v1/searches` — les deux comptages justes, le cas à zéro, la
  recherche partagée où les vus sont ceux du lecteur et non ceux de l'auteur, et le profil sorti du
  jeu qui quitte `seenCount` sans quitter `favoriteCount`. S'y ajoute un test qui **compte les
  requêtes** sur un lot de recherches, pour tenir la règle anti-`N+1` que le code défend
  explicitement.
- **Réf** : **`ENT-050` — aval** (le rail de la carte qui affichera ces deux nombres) · `API-028`
  (clôture d'une recherche, indépendante) · `SavedSearchOutput` · `CollaborationMapper::toSavedSearch`
  · `SearchNewCountReader` (le motif de comptage groupé à suivre) ·
  `SearchResultMembershipRepository::countsFor` · `SearchProfileReviewRepository::statesFor` ·
  `FavoritesProvider` · `SearchMembershipRecalculator`

## Contexte

La réponse d'une recherche dit **combien de profils elle contient** (`resultCount`) et **combien y
sont entrés depuis la dernière visite** (`newCount`). Elle ne dit rien de ce que le recruteur en a
fait.

Deux questions restent donc sans réponse côté serveur, alors que les faits sont en base :

- **où en suis-je dans cette pile ?** — `search_profile_review.seen_at` date chaque profil que ce
  recruteur a examiné, et `SearchProfileReviewRepository::statesFor` sait déjà les lire en une
  requête, mais uniquement pour une recherche à la fois et sans jamais les compter ;
- **qu'est-ce que cette recherche a produit ?** — `favorite` porte une clé vers la recherche depuis
  la refonte de la portée, et `FavoritesProvider` sait les servir, mais rien ne les compte.

Conséquence : une collection de recherches se présente comme une liste d'objets tous identiques —
un titre, des critères, un volume — sans qu'on puisse distinguer celle qu'on a épuisée de celle
qu'on n'a pas ouverte, ni celle qui a donné huit profils de celle qui n'a rien donné.

## Périmètre

- Deux champs entiers sur `SavedSearchOutput` : `seenCount` et `favoriteCount`.
- `CollaborationMapper::toSavedSearch` les reçoit en paramètres, comme `newCount` et `resultCount`.
- Les **quatre** appelants les fournissent : `SavedSearchesProvider` (collection),
  `SavedSearchProvider` (recherche seule), `SavedSearchProcessor` (création et ajustement) et
  `SavedSearchDuplicateProcessor`.
- La lecture est **groupée par lot de recherches**, sur le motif de
  `SearchResultMembershipRepository::countsFor` : une requête par comptage pour toute la page, quel
  que soit le nombre de cartes.
- `api/openapi.json` régénéré (`make dev-openapi`) — contenu de commit, pas une gate.

## Hors périmètre

- **La clôture d'une recherche** — date, auteur, réouverture : c'est `API-028`, indépendante.
- **L'affichage** de ces deux nombres — le rail de la carte est `ENT-050`.
- **Un comptage des vus à l'échelle de l'équipe** : cf. *Décisions de cadrage*.
- **La correction d'[api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md)**, dont
  la ligne 83 documente encore `/v1/organizations/{uuid}/saved-searches` là où la route réelle est
  `/v1/searches`. Dérive **antérieure** à ce diff : la corriger ici mélangerait deux sujets dans un
  commit.
- **Toute migration** : aucune colonne, aucune table, aucun index à ajouter.

## Décisions de cadrage

**Les vus sont ceux du LECTEUR, pas ceux de l'équipe.** Le champ répond à « où en suis-*je* », et
c'est déjà exactement ce que fait `newCount` — dont le code écrit que deux recruteurs sur une même
recherche partagée ont des compteurs indépendants, le repère étant porté par `SearchView` et non par
la recherche. Un comptage d'équipe aurait aligné « vus » sur « favoris », qui sont partagés, mais au
prix de **deux règles contraires dans le même objet** : `newCount` personnel juste à côté d'un
`seenCount` collectif. Le repère personnel est aussi le seul que la carte peut offrir.

**Les champs prennent le vocabulaire du MODÈLE, pas celui de l'écran.** L'entité s'appelle
`Favorite`, la colonne `seen_at` : d'où `favoriteCount` et `seenCount`. L'écran, lui, envisage le mot
« retenus » — mais ce n'est à ce jour qu'une piste de vocabulaire non arbitrée, et l'API n'est pas
l'endroit où l'on tranche un mot produit. Renommer un libellé d'interface ne coûte rien ; renommer un
champ d'API est cassant.

**Les deux comptages sont servis PARTOUT où une recherche est sérialisée.** `SavedSearchOutput` est
unique et traversé par quatre chemins ; les remplir partout évite des champs nullables et le
« parfois présent » que le front devrait interpréter. Sur une recherche seule, le surcoût est d'une
requête groupée portant sur une seule ligne.

**`seenCount` est BORNÉ au jeu de résultats courant, `favoriteCount` ne l'est pas.** Les deux nombres
ne répondent pas à la même question, et le dépôt impose deux réponses différentes :

- le jeu de résultats **rétrécit** — `SearchMembershipRecalculator` retire la ligne d'un profil qui
  cesse de correspondre (« sorti du jeu de résultats ») — tandis que les lignes de revue, elles,
  survivent. Un `seenCount` non borné pourrait donc dépasser `resultCount`, et l'écran afficherait un
  avancement supérieur à 100 %. Il se joint donc à `search_result_membership` ;
- `FavoritesProvider` sert **tous** les favoris d'une recherche, sans filtrer sur l'appartenance. Or
  `SavedSearchOutput` porte déjà la règle en toutes lettres pour `resultCount` : *la carte annonce
  exactement ce que la Table servira*. Un `favoriteCount` borné afficherait 5 là où la Table montre
  8. Il ne se joint donc à rien.

**La documentation se limite au contrat.** Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit les champs
d'une recherche, et il n'existe aucun `README.md` sous `api/`. Seul `api/openapi.json` est rendu
faux par ce diff.

**Correction du critère de requêtes, après mesure.** Le cadrage exigeait « le même nombre de
requêtes pour dix recherches que pour une ». **La mesure l'a démenti, et cette exigence n'a jamais
été tenue** : `GET /v1/searches` passait déjà de 13 requêtes à 4 recherches à 21 à 12 recherches,
soit **une par recherche** — celle de `SearchNewCountReader`, qui fait un `COUNT` par recherche
délibérément et le justifie dans son propre code (« le repère de lecture diffère d'une recherche à
l'autre, il n'y a pas de seuil commun à passer en une requête »).

Comptées par table à 12 recherches : 13 sur `search_result_membership`, mais **une seule** sur
`search_profile_review` et **une seule** sur `favorite`. Les deux comptages ajoutés sont donc bien
groupés. Le critère devient ce qui est réellement exigible de cette tâche : **deux requêtes fixes de
plus**, et un rythme de croissance inchangé. Regrouper `newCount` serait une autre tâche, hors de ce
périmètre.

## Règles et cas limites

- **Recherche sans critère** — `resultCount` vaut déjà 0 ; les deux nouveaux comptages valent 0.
- **Recherche jamais ouverte** — aucune ligne de revue, `seenCount` vaut 0. À distinguer du cas
  précédent : la recherche peut être pleine et n'avoir jamais été parcourue.
- **Recherche partagée** — `seenCount` est celui du lecteur ; `favoriteCount` est celui de son
  auteur, et ce n'est pas une approximation : le partage est en **lecture seule**, personne d'autre
  n'a pu y retenir un profil.
- **Profil sorti du jeu** — il quitte `seenCount` (borné) et reste dans `favoriteCount` (non borné).
  C'est la conséquence assumée de la décision ci-dessus, et elle se teste.
- **Profil écarté** (`dismissed_at`) — il compte comme **vu** dès lors que `seen_at` est renseigné :
  le comptage lit `seen_at`, sans jamais consulter `dismissed_at`. Écarter n'annule pas d'avoir vu.
- **Lot vide** — les lecteurs groupés rendent un tableau vide sans lancer de requête, comme le font
  déjà `countsFor` et `SearchNewCountReader::countsFor`.
- **Permissions inchangées** — la visibilité d'une recherche reste gouvernée par `SavedSearchVoter` ;
  cette tâche n'ouvre ni ne ferme aucun accès.

## Documentation impactée

- `api/openapi.json` — régénéré par `make dev-openapi`. La CI `api-quality` vérifie qu'il est à jour
  par un `git diff --exit-code` que la commande locale ne couvre pas : régénérer **avant** de
  committer, sinon vert en local et rouge en CI.

Aucune page de `docs/showcase-recruiter-webapp-saas/` n'est rendue fausse par ce diff, et aucun `README.md` de dossier
n'existe sous `api/`.

## Définition de terminé

- [x] `GET /v1/searches` renvoie `seenCount` et `favoriteCount` sur chaque recherche.
- [x] Les trois autres chemins qui servent une recherche les renvoient aussi : recherche seule,
      création/ajustement, duplication.
- [x] Sur une recherche partagée, `seenCount` est celui du lecteur — deux recruteurs lisant la même
      recherche obtiennent deux valeurs différentes.
- [x] Un profil qui a quitté le jeu de résultats ne compte plus dans `seenCount` et compte toujours
      dans `favoriteCount`.
- [x] `favoriteCount` égale le nombre d'éléments servis par `GET /v1/searches/{id}/favorites`.
- [x] Les deux comptages n'ajoutent que **deux requêtes fixes**, quel que soit le nombre de
      recherches — prouvé par un test, pas par lecture du code.
- [x] `api/openapi.json` régénéré et à jour dans le commit.
- [x] Le jeu de recherches rendu, son ordre et tous les champs existants sont inchangés.

## Vérification

**Preuve comportementale, sur la stack de la lane.** Une recherche réelle, deux profils, les deux
marqués vus, un seul retenu — puis les critères resserrés sur un pays que personne ne satisfait :

| Moment | `resultCount` | `seenCount` | `favoriteCount` |
| --- | --- | --- | --- |
| jeu intact | 2 | 2 | 1 |
| après resserrement, jeu vide | 0 | **0** | **1** |

`GET /searches/{id}/favorites` sert **1** favori dans les deux cas : la carte annonce exactement ce
que la Table servira, y compris quand le profil retenu a quitté le jeu.

**`SearchCountsTest` : 5 tests, 70 assertions, vert.**

| Cas | Attendu |
| --- | --- |
| `favoriteCount` face à `/favorites` | **le même nombre** |
| Recherche qui vient d'être ouverte | `0` et `0` |
| Recherche partagée, deux lecteurs | l'avancement **diffère**, les retenus **non** |
| Profil sorti du jeu | quitte `seenCount`, **reste** dans `favoriteCount` |
| 1 recherche puis 6 | **1** requête sur `search_profile_review`, **1** sur `favorite` |

**Les 38 tests voisins des recherches passent** (337 assertions) : le DTO a gagné deux champs, il
n'a rien perdu.

**Requêtes comptées par table sur `GET /v1/searches`**, à 12 recherches : 13 sur
`search_result_membership`, **1** sur `search_profile_review`, **1** sur `favorite`.

**`api/openapi.json` régénéré : +6 lignes**, les deux champs et rien d'autre.

## Livré

- `api/src/Repository/FavoriteRepository.php` **(+)** — le comptage groupé des retenus, et la raison
  écrite de ne pas le borner au jeu de résultats.
- `api/src/Repository/SearchProfileReviewRepository.php` **(M)** — `seenCountsFor`, jointe à
  l'appartenance ; seul `seen_at` est consulté, jamais `dismissed_at`.
- `api/src/ApiResource/Output/SavedSearchOutput.php` **(M)** — les deux champs, chacun portant la
  règle de portée qui le distingue de l'autre.
- `api/src/State/CollaborationMapper.php` **(M)** — deux paramètres, et `seenCount` rejoint la liste
  de ce qui dépend du lecteur.
- `api/src/State/Provider/SavedSearchesProvider.php` **(M)** — deux lectures groupées pour toute la
  page.
- `api/src/State/Provider/SavedSearchProvider.php` **(M)** — les comptages unitaires.
- `api/src/State/Processor/SavedSearchProcessor.php` **(M)** — création et ajustement.
- `api/src/State/Processor/SavedSearchDuplicateProcessor.php` **(M)** — deux zéros assumés : une
  copie n'a ni revue ni favori.
- `api/tests/Functional/SearchCountsTest.php` **(+)** — 5 tests.
- `api/openapi.json` **(M)** — les deux champs sur le schéma `Search`.

## Reste à faire (hors périmètre)

- **`newCount` fait toujours un `COUNT` par recherche** — c'est la seule croissance de la page, elle
  est délibérée et documentée dans `SearchNewCountReader`. La regrouper demanderait de repenser le
  repère de lecture, qui diffère d'une recherche à l'autre : sa propre tâche, si le besoin se pose.
- **`ENT-050`** affichera ces deux nombres dans le rail de la carte.
