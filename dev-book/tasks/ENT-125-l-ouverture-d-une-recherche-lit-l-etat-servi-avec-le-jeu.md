# TASK ENT-125 — l'ouverture d'une recherche lit l'état servi avec le jeu

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — l'ouverture lit les trois listes servies avec le jeu, l'appel
  dédié aux retenus ne part plus, et le compteur « Vus » se lit sur la liste au lieu d'une addition
  tenue à la main.
- **Type** : Frontend espace entreprise — consommation d'un contrat enrichi, un appel réseau
  supprimé, un compteur remis sur sa source.
- **Tests** : vitest sur le service d'éclatement — l'état servi l'emporte sur celui des cartes, le
  repli tient pour les périmètres `?scope=`, `isNew` continue de se lire sur les cartes, et une liste
  servie **vide** écrase au lieu de se taire. **Le composable n'est pas couvert** : le dépôt n'a pas
  de harnais de test de composable (`vitest.config.ts` ne ramasse que des fonctions pures), constat
  déjà posé en `ENT-126`. La disparition de l'appel et la justesse des compteurs sont prouvées par
  une trace réseau et une lecture du store, ci-dessous.
- **Réf** : **amont bloquant `API-047`** (le contrat qui sert les listes) · `ENT-015` (qui avait
  conservé `loadFavorites` faute de cette liste) · `ENT-079` (le parcours sur le jeu entier) ·
  `webapp/spaces/entreprise/app/composables/useSearch.ts` ·
  `webapp/spaces/entreprise/app/stores/searches.ts` · `webapp/services/search/normalize.ts`.

## Contexte

**Ouvrir une recherche déclenche quatre appels, en deux vagues.** Le premier ramène le jeu ; les trois
autres n'existent que pour reconstituer ce que le contrat ne disait pas — qui est retenu, qui est
écarté, où en est la lecture. Trace réelle, navigation depuis l'accueil :

```
+1464 ms  GET /api/searches/{id}/results
+2298 ms  GET /api/searches/{id}/favorites
+2298 ms  GET /api/searches/{id}/results?scope=favorites
+2298 ms  GET /api/searches/{id}/results?scope=dismissed
```

`API-047` sert désormais `favoriteIds`, `dismissedIds` et `seenIds` avec la première page du jeu.

Deux conséquences directes côté écran. **`GET /favorites` n'a plus de raison d'être** : le composable
n'en gardait que les `candidateId` — `id`, `createdAt` et `addedById` étaient jetés —, et c'est
exactement ce que la nouvelle liste apporte. **Le compteur « Vus » peut redescendre sur sa source** :
il additionne aujourd'hui le `seenCount` de la fiche, figé après son chargement, et un delta de
session tenu en mémoire ; ce montage existe uniquement parce qu'aucune liste complète n'arrivait.

## Périmètre

- **Ranger les trois listes servies** avec la page 1 du jeu : le service d'éclatement les lit, le
  store des recherches les pose sur le jeu de la recherche (`favoriteIds`, `dismissedIds`,
  `seenIds`).
- **Supprimer `loadFavorites`** et son appel `GET /api/searches/{id}/favorites` à l'ouverture.
- **Le compteur « Vus » se lit sur `seenIds`**, dont la taille grandit déjà toute seule quand un
  profil est marqué vu pendant le parcours. Suppression du `seenThisSession` et de l'addition avec le
  `seenCount` de la fiche.
- **Types regénérés** depuis `api/openapi.json`.

## Hors périmètre

- **La stratégie de chargement des fiches retenues et écartées.** `?scope=favorites` et
  `?scope=dismissed` restent où ils sont — les différer à l'ouverture de l'onglet et du tiroir relève
  de la revue du chargement des profils, décidée juste après ce cadrage. Cette tâche ne déplace aucun
  de ces deux appels ; elle les rend seulement facultatifs pour l'exactitude des compteurs.
- **La pagination de `?scope=favorites`** : au-delà de trente retenus dans une même recherche,
  l'onglet peut compter juste et afficher court. Limite connue, non atteinte par les données
  actuelles, non traitée ici.
- **Le poids de la collection `/api/searches`**, rechargée par les écrans de recherche pour le seul
  besoin de renommer.
- **La mise en parallèle des appels restants.**

## Décisions de cadrage

### Le compteur « Vus » change de source, les autres non

« Retenus » et « Écartés » lisaient déjà des listes du store ; seul leur contenu devient complet.
« Vus » est le seul à changer de mécanisme, et c'est ce qui permet de retirer un montage — deux
sources pour un même chiffre — plutôt que d'en ajouter une troisième.

### `loadFavorites` disparaît, `?scope=favorites` reste

Ils ne répondent pas à la même question : le premier disait **combien et lesquels**, le second
**avec quoi les afficher**. Seul le premier est remplacé par une liste d'identifiants ; les lignes de
l'onglet continuent de venir du périmètre, comme `ENT-015` l'a établi.

### Rien ne se déduit localement

La tentation serait de reconstituer les listes depuis les drapeaux des cartes chargées
(`isFavorite`, `isSeen`). C'est précisément le défaut d'origine : trente cartes ne savent rien des
trois cents autres. Les listes servies **remplacent**, elles ne fusionnent pas.

## Règles et cas limites

- **Les listes n'arrivent qu'avec la page 1.** Les pages suivantes ne les portent pas : le rangement
  ne doit ni les écraser, ni les remettre à vide en chargeant la suite du jeu.
- **La sélection et les retenus survivent à un jeu repeint** : règle existante du store, à ne pas
  casser — un jeu rejoué filtre ces listes sur ce qui reste dans le jeu, il ne les vide pas.
- **Les gestes restent optimistes.** Retenir, écarter, marquer vu s'affichent avant la réponse
  serveur ; les listes servies sont un point de départ, pas une autorité qui contredirait un geste en
  cours.
- **Écarter retire des retenus** (`ENT-011`) : la règle vaut aussi sur les listes servies, que l'API
  applique déjà de son côté.
- **Recherche partagée en lecture seule** : les retenus affichés sont ceux de l'auteur, l'avancement
  affiché est celui du lecteur — le contrat le garantit, l'écran n'a rien à arbitrer.
- **Contrat manquant** : tant qu'`API-047` n'est pas livrée, les champs sont absents. Le rangement ne
  doit pas produire d'écran vide face à une réponse sans ces listes.

## Documentation impactée

**Aucune.** Relecture faite : `composables-standard.md` porte des conventions de couche — nommage,
responsabilité unique, frontières — et ne décrit ni les appels de `useSearch` ni ses retours ; rien
n'y devient faux. `data-contracts.md`, lui, énonce déjà la règle appliquée ici — « reporter le champ
dans le miroir écrit à la main, dans la tâche de l'espace consommateur » — et cette tâche la suit
plutôt qu'elle ne la change.

## Vérification

**Trace réseau réelle**, navigation client vers « Preuve architecte » (32 profils, 3 retenus) :

```
appels au BFF (3, contre 4 avant) :
   200 GET …/results?perPage=100      ← porte les trois listes d'état
   200 GET …/results?scope=dismissed  ┐ les CARTES, que le jeu ne porte pas
   200 GET …/results?scope=favorites  ┘

GET …/favorites  →  ne part plus
```

**L'état rangé dans le store**, lu en direct dans la page :

```json
{ "resultIds": 32, "favoriteIds": 3, "retainedIds": 3, "seenIds": 2, "dismissedIds": 0 }
```

Les compteurs de la console affichent « Vus 2 · Favoris 3 · Écartés 0 », et l'onglet « Retenus » de
la Table **3 lignes pour 3 retenus**.

**Le compteur « Vus » monte pendant le parcours sans delta tenu à côté** : 2 à l'ouverture, 3 après
avoir parcouru la pile — c'est la liste du store qui grandit, `markSeen` l'alimentant à chaque profil
franchi.

**Tests et typage**

```
vitest services/search/          16 fichiers, 306 tests, verts
bun run quality-type-check       les quatre espaces, vert
```

## Livré

- `webapp/shared/types/search.ts` — `SearchResults` accueille les trois listes, facultatives.
- `webapp/services/search/normalize.ts` — les listes servies l'emportent sur celles déduites des
  cartes ; le repli reste pour les périmètres.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` — `loadFavorites` et son appel disparaissent,
  « Vus » se lit sur la liste du store, `seenThisSession` est retiré.
- `webapp/services/search/normalize.vitest.ts` — quatre cas sur la préférence, le repli, la
  nouveauté et la liste vide.

## Reste à faire (hors périmètre)

- **Deux appels subsistent à l'ouverture** — `?scope=favorites` et `?scope=dismissed` : ils portent
  des **cartes**, que le jeu ne transporte pas, et celles des écartés n'y sont pas du tout puisque
  l'API les sort de la pile. Les différer à l'ouverture de l'onglet et du tiroir n'appartient pas à
  cette carte.
- **Aucun harnais de test de composable** dans le dépôt : c'est un sujet de socle, nommé pour la
  deuxième fois.

## Définition de terminé

- [x] À l'ouverture d'une recherche, `GET /api/searches/{id}/favorites` ne part plus.
- [x] Les compteurs « Retenus », « Écartés » et « Vus » sont justes sur le jeu entier — ils lisent
      les listes servies, non les cartes chargées.
- [x] L'étoile suit la même source que le compteur : la liste des retenus du store.
- [x] « Vus » monte au rythme du parcours, sans `seenThisSession`.
- [x] Charger la page suivante du jeu ne vide aucune des trois listes — **sans objet depuis
      `ENT-126`**, il n'y a plus de page suivante ; le repli sur les cartes protège les périmètres
      `?scope=`, et un test le retient.
- [x] Tests vitest sur le service d'éclatement. **Pas sur `useSearch`** : non couvrable en l'état,
      cf. la ligne `Tests` ci-dessus.
- [x] Types à jour — le miroir écrit à la main accueille les trois listes.
- [x] Documentation : aucune page déclenchée, vérifié plutôt que supposé.
