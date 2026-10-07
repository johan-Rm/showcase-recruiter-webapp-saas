# TASK API-022 — api : « nouveau » cesse de vouloir dire « pas encore vu »

- **Créée le** : 2026-08-03.
- **Session** : `cb761d33-c5ed-45b5-a3db-3340b185010f`.
- **Statut** : ✅ fait le 2026-08-03 — « nouveau » est devenu l'âge du profil dans le vivier, sept
  jours, et ne s'éteint plus quand on recharge. Gates vertes en une passe.
- **Type** : Backend API — changement de sémantique d'un champ de sortie, sans changement de forme.
- **Tests** : la règle est une comparaison de dates dans `SearchResultsBuilder`, donc un test
  unitaire la tient — profil arrivé hier / il y a huit jours / aujourd'hui, et la borne exacte du
  septième jour. S'y ajoute un test fonctionnel sur `GET /v1/searches/{id}/results` prouvant que
  **deux ouvertures successives** rendent le même `isNew`, ce que l'implémentation actuelle ne fait
  pas.
- **Réf** : `ENT-039` (les états du profil côté entreprise, qui affiche ce badge) ·
  `SearchResultsBuilder::isNew` · `SearchNewCountReader` ·
  [api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md)

## Contexte

`isNew` compare l'entrée d'un profil dans une recherche au **repère de lecture du recruteur** :
`firstMatchedAt > lastViewedAt` (`SearchResultsBuilder::isNew`). Le badge « Nouveau » de la carte
profil signifie donc « ce profil est arrivé depuis ta dernière ouverture ».

**C'est un doublon de ce que la colonne dit déjà.** La Visionneuse affiche « 15 vus · 12 à voir » :
ce qu'on n'a pas encore regardé y est compté, nommé et cliquable. Un badge qui redit la même chose
sur chaque carte n'ajoute rien, et lui donne un second vocabulaire.

**Et il s'éteint tout seul.** Le repère est remis à jour au moment même où les résultats sont servis
(`SearchResultsProvider:83`, après construction des cartes). Les badges de la page reçue sont donc
justes, mais **un simple rechargement les efface** — sans qu'aucun profil ait été regardé. Un état
qui disparaît quand on recharge n'est pas un état, c'est un artefact.

Ce que le badge doit dire, c'est un **fait de fraîcheur** : ce profil vient d'arriver. Un fait, pas
un repère de lecture — donc identique pour tous les membres de l'organisation, et qui s'éteint en
vieillissant, pas en étant consulté.

## Périmètre

- **`SearchResultsBuilder::isNew`** : la comparaison passe de « après mon repère de lecture » à
  « entré dans le vivier il y a moins de sept jours ».
- **La date de référence est celle du CANDIDAT** (`Candidate::createdAt`), pas celle de son
  appartenance à la recherche.
- **Le seuil vit dans le domaine**, en constante nommée — pas un `7` en dur au milieu d'une
  comparaison.
- **`api/openapi.json` régénéré** si la description du champ change dans le schéma de sortie.
- **La documentation du champ** dans `CandidateCardOutput`, qui dit aujourd'hui l'inverse.

## Hors périmètre

- **Le compteur `newCount` d'une recherche** (« Nouveaux 5 » sur la carte de recherche). Il garde le
  repère de lecture, et c'est justifié — voir *Décisions de cadrage*.
- **`SearchView` / `markViewed`** : le mécanisme reste en place pour ce compteur, il n'est ni
  supprimé ni déplacé.
- **L'affichage du badge** — libellé, couleur, place : lane `espace/entreprise`, carte `ENT-039`.
- **Le nom du champ**, qui ne change pas : le front lit `isNew` sans savoir d'où il vient, et le
  renommer imposerait une régénération de contrat pour un gain nul.

## Décisions de cadrage

### La fraîcheur se mesure sur le VIVIER, pas sur la recherche

Deux dates pouvaient porter le badge : l'entrée du profil dans le vivier (`Candidate::createdAt`) ou
son entrée dans cette recherche (`SearchResultMembership::firstMatchedAt`).

C'est le **vivier** qui est retenu : « nouveau » doit vouloir dire « nouveau chez showcase-recruiter-webapp-saas », un fait
qui appartient au profil et se lit pareil dans toutes les recherches. Un même profil ne peut pas
être neuf ici et ancien là.

**Le risque, et pourquoi il est assumé.** Un import de masse fait naître des milliers de profils à la
même seconde, et donc autant de badges d'un coup — dans la base de développement, les 3 053
candidats portent tous la même date. Mais c'est un **artefact d'environnement** : la base y est rasée
et rechargée d'un bloc, alors que la production tourne sur un pipeline **quotidien et incrémental**,
qui n'amène qu'une poignée de profils par jour. Le badge sera donc rare là où il compte. À surveiller
au premier import massif en production, qui devra être signalé comme tel.

### Sept jours, et le seuil est absolu

Un profil cesse d'être nouveau au bout de **sept jours**, quoi qu'il arrive — consulté ou non.

Le rythme d'une veille est hebdomadaire : ce qui est nouveau, c'est ce qu'on n'a pas pu voir la
semaine dernière. Trente jours en aurait fait un signal quasi permanent sur un vivier qui bouge peu,
et un badge que presque tous portent ne distingue plus rien.

Le seuil est **absolu et non glissant par recruteur** : c'est ce qui rend le badge partageable. Deux
membres qui regardent la même recherche voient les mêmes profils neufs, et peuvent en parler.

### Le compteur de la recherche garde le repère de lecture

« Nouveaux 5 » sur la carte d'une recherche répond à une autre question : *qu'est-ce qui a bougé
depuis que je suis passé ?* C'est une invitation à revenir, personnelle par nature, et le repère de
lecture est le bon mécanisme pour ça.

Les deux cohabitent donc, avec deux sens distincts. C'est acceptable **à condition qu'ils ne portent
pas le même mot à l'écran** : le compteur d'une recherche parle d'activité depuis ma dernière visite,
le badge d'un profil parle de son âge. Le libellé du compteur relève de la lane entreprise ; la
distinction est nommée ici pour qu'elle ne se perde pas.

## Règles et cas limites

- **La borne est stricte** : un profil créé il y a exactement sept jours n'est plus nouveau. Une
  borne floue produit un badge qui clignote d'une requête à l'autre.
- **L'horloge est celle du SERVEUR**, jamais celle du client — comme `isInMorocco`, dont le verdict
  dépend déjà de « quand ».
- **La date est injectée**, pas lue de `new DateTimeImmutable()` au fond de la méthode : sans quoi le
  test du huitième jour demande d'attendre huit jours. `CriterionMatcher::match` a déjà ce motif avec
  son paramètre `$at`.
- **`isNew` reste `null` hors d'une recherche** : la question n'y a pas d'objet, et ce contrat ne
  change pas.
- **Aucune requête supplémentaire** : `Candidate::createdAt` voyage déjà avec le candidat chargé pour
  la page. Un `N+1` ici toucherait chaque ligne d'une page de deux cents.
- **Recherche partagée** : deux membres voient désormais les **mêmes** badges. C'est l'effet
  recherché, et c'est un changement de comportement observable.

## Documentation impactée

- **`CandidateCardOutput`** — le commentaire du champ décrit le repère de lecture ; il devient faux.
- **`api/openapi.json`** régénéré si cette description remonte dans le schéma (`make dev-openapi`).
  C'est du **contenu de commit**, pas une gate : la CI a un `git diff --exit-code` sur ce fichier que
  le `make` local ne couvre pas.
- **Vérifié, non impacté** : `api-routes.md` (aucune route ne bouge), `data-model.md` (aucune colonne
  ajoutée — `created_at` existe déjà sur `candidate`), `data-contracts.md` (la forme du champ est
  inchangée, seule sa sémantique l'est).

## Définition de terminé

- [x] `isNew` ne consulte plus le repère de lecture — `markerFor` n'avait plus d'appelant et a été
      supprimée.
- [x] Deux appels successifs à `GET /v1/searches/{id}/results` rendent le **même** `isNew`
      (`testNewnessSurvivesAReload`).
- [x] Un profil vieilli de huit jours ne porte pas le badge ; un profil créé du jour le porte
      (`testAProfileOlderThanAWeekIsNoLongerNew`, et la bascule à la seconde près en unitaire).
- [x] Le seuil est `ProfileFreshness::NEW_FOR_DAYS`, et `build()` reçoit une date injectable.
- [x] `newCount` de la carte de recherche est **inchangé** : ses deux tests passent sans retouche.
- [x] `api/openapi.json` régénéré — **identique**, la carte candidat n'apparaît pas nommément dans
      le contrat public.
- [x] `make api-quality` vert à l'ouverture de la PR : conteneur, phpstan, style et deptrac sans
      erreur, **405 tests / 2021 assertions**, 0 violation d'architecture.

## Vérification

**La suite entière est verte** — `make api-quality`, sortie lue le 2026-08-03 : **405 tests, 2021
assertions**, plus phpstan, php-cs-fixer et deptrac sans une erreur. Aucun flake sur les tests de
recherche, contrairement à ce que cette classe fait parfois.

Parmi eux, les **9 nouveaux** de `ProfileFreshnessTest` et les **2** de `SearchTest`.

Les trois cas qui tiennent réellement la règle, et qu'aucune capture ne montrerait :

| Cas | Attendu | Où |
| --- | --- | --- |
| Six jours et vingt-trois heures | nouveau | `ProfileFreshnessTest` |
| **Exactement sept jours** | plus nouveau | `ProfileFreshnessTest` — la borne est stricte |
| Deux lectures d'affilée du même jeu | verdict identique | `testNewnessSurvivesAReload` |

Le troisième est le défaut d'origine : servir les résultats remet le repère de lecture à jour, et
l'ancienne règle éteignait donc les badges à la requête suivante, sans qu'aucun profil ait été
regardé.

**La bascule a d'abord été constatée sur l'API réelle**, sur la stack de ce worktree, avant d'être
figée en tests — c'est ce constat qui a été validé.

**Le compteur de recherche n'a pas bougé** :
`testNewCountCountsProfilesThatJoinedSinceLastVisitAndResetsOnOpeningResults` et
`testTwoRecruitersHaveIndependentNewCountsOnTheSameSharedSearch` passent sans retouche. Les deux
mécanismes cohabitent, comme le cadrage l'exigeait.

**Un piège d'environnement, sans lien avec le code, qui a failli faire embarquer une régression.**
Le cache Symfony du conteneur était périmé : la première régénération d'`api/openapi.json` a
réintroduit `revealedContact`, champ supprimé par `API-018` deux jours plus tôt. Régénération
annulée, cache vidé, régénération refaite — le fichier ressort alors identique à `develop`. À
retenir : sur une stack de worktree qui vient d'être démarrée, **vider le cache avant
`make dev-openapi`**, sans quoi le contrat régénéré décrit le code d'avant.

## Livré

- `api/src/Domain/Search/ProfileFreshness.php` **(+)** — la règle, en couche pure : une constante
  nommée, une comparaison, une date injectée.
- `api/tests/Unit/ProfileFreshnessTest.php` **(+)** — neuf cas, dont trois qui encadrent la borne.
  Un dixième, qui comparait la constante à sa propre valeur, a été retiré : phpstan l'a signalé
  comme toujours vrai, et il l'était — ce sont les cas de bascule qui prouvent le seuil, pas une
  égalité recopiée.
- `api/src/State/SearchResultsBuilder.php` **(M)** — `isNew` lit l'âge du candidat ; la dépendance
  au lecteur de compteur et la méthode privée disparaissent ; `build()` accepte une date.
- `api/src/Domain/Search/SearchNewCountReader.php` **(M)** — `markerFor` supprimée (sans appelant),
  et le doc-block dit désormais que ce compteur est le seul usage du repère.
- `api/src/ApiResource/Output/CandidateCardOutput.php` **(M)** — le champ décrit sa vraie règle, et
  se distingue explicitement du `newCount` d'une recherche.
- `api/tests/Functional/SearchTest.php` **(M)** — deux tests de bout en bout, et une assertion
  retournée qui encodait l'ancienne sémantique.

## Écart au cadrage

Quatre points où l'implémentation a divergé de la carte, tous dans le même sens : le cadrage était
plus prudent que nécessaire.

### La documentation d'architecture prescrivait déjà cette règle

Le cadrage présentait le choix entre le vivier et la recherche comme ouvert. Il ne l'était pas :
deux pages d'architecture l'avaient déjà tranché, et dans le même sens.
`filtres-vivier-tableau-partage.md` inscrit « Nouveaux profils — arrivés cette semaine », et
`inventaire-criteres-3-couches.md` désigne nommément `candidate.created_at` avec un filtre
« fenêtre ».

C'est le **code** qui était en décalage avec la documentation, pas l'inverse. Ces deux pages ne
deviennent donc pas fausses : elles deviennent vraies, et n'ont rien à corriger.

### `markerFor` est supprimée, pas seulement délaissée

La carte demandait que `SearchResultsBuilder` cesse de l'appeler. Elle n'avait aucun autre appelant :
la laisser en place aurait laissé une méthode publique morte dans le domaine, et le doc-block qui la
présentait comme « la même vérité, lue à deux granularités » aurait continué de décrire un lien qui
n'existe plus.

`SearchNewCountReader` garde tout le reste — le compteur d'une recherche, lui, est intact.

### Le contrat public ne bouge pas

La carte prévoyait de régénérer `api/openapi.json` « si la description du champ remonte dans le
schéma ». Vérification faite après régénération : le fichier est **identique**. La carte candidat
n'apparaît pas nommément dans l'OpenAPI — elle n'y existe que nichée dans des collections dont les
`items` sont opaques. Rien à embarquer.

### Un test existant encodait l'ancienne sémantique

`testWritingASearchReturnsItsResultSetInTheSameResponse` affirmait « Rien n'est nouveau dans une
recherche qu'on vient de composer ». C'était vrai tant que créer une recherche posait le repère de
lecture ; ça ne l'est plus, puisque la nouveauté d'un profil ne dépend plus de la recherche qui le
ramène.

L'assertion est **retournée**, pas supprimée : les profils des fixtures ont moins de sept jours, donc
ils sont nouveaux — dans une recherche neuve comme dans une ancienne. C'est exactement la propriété
qu'on voulait, et le test la tient désormais.

## Aval

- **Le libellé du compteur « Nouveaux »** d'une recherche, qui dit désormais autre chose que le
  badge : à distinguer côté entreprise.
- **Le premier import massif en production** devra être annoncé : il allumera le badge sur tout ce
  qu'il crée.
