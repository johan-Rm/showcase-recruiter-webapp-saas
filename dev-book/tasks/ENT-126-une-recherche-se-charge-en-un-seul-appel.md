# TASK ENT-126 — une recherche se charge en un seul appel

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — une recherche arrive en un seul appel à la taille servie par
  la configuration, le nombre réel s'affiche à côté du chargé, et toute la mécanique de page suivante
  a quitté le dépôt.
- **Type** : Frontend espace entreprise — **retrait** de mécanique, plus qu'ajout : la pagination du
  jeu disparaît, un affichage de compte la remplace.
- **Tests** : vitest sur la règle d'affichage du compte, dans la couche pure. **Les deux autres points
  annoncés au cadrage ne sont pas couverts par vitest, et ne peuvent pas l'être en l'état** : le dépôt
  n'a d'outillage de test ni pour les composables ni pour les composants — `vitest.config.ts` ne
  ramasse que des fonctions pures. « Un seul appel à l'ouverture » est prouvé par une **trace réseau
  réelle** (ci-dessous), « plus aucun appelant des fonctions retirées » par le **type-check des quatre
  espaces**. Monter un harnais de test de composable dépasse cette tâche.
- **Réf** : **amont bloquant `API-048`** (la borne et la limite exposée en configuration) ·
  `ENT-079` (le parcours sur le jeu entier, dont cette tâche simplifie la promesse) ·
  `webapp/spaces/entreprise/app/composables/useSearch.ts` ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` ·
  `webapp/services/search/stack.ts`.

## Contexte

**Tout le chargement par pages n'existe que parce que le jeu n'a pas de borne.** Le jeu arrive par
trente ; il faut donc demander la suite à l'approche du bout, insérer les arrivants sans déranger la
lecture en cours, et tirer le jeu entier quand un affinage est posé — sur la plus large recherche de
la base, cette dernière boucle fait **108 allers-retours séquentiels**.

`API-048` borne le jeu servi à cent profils. Cent fiches tiennent dans un appel — ≈ 135 Ko bruts,
26 Ko compressés — et toute cette mécanique perd son objet.

En échange, l'écran doit dire une chose qu'il ne disait pas : **le jeu montré n'est pas tout le jeu**.
Le contrat porte les deux nombres, `total` pour le réel et la longueur de `resultIds` pour le chargé.

## Périmètre

- **Demander le jeu en une fois**, à la taille exposée par la configuration — jamais un nombre en dur.
- **Afficher « 100 sur 352 »** partout où un compte s'affiche déjà : la carte de recherche, le rang de
  la Visionneuse, l'en-tête de la Table. Libellés en clés i18n.
- **Retirer la mécanique devenue sans objet** :
  - la demande de page suivante et ses états (`loadNextPage`, `nextPage`, `loadingMore`,
    `loadMoreFailed`) ;
  - le déclenchement à l'approche du bout (`needsNextPage`, `LOAD_AHEAD`) ;
  - l'extension de l'ordre gelé à l'arrivée d'une page (`growFrozenOrder`) ;
  - le « parcourir tout » de la console d'affinage et sa boucle (`loadAll`) ;
  - les états d'erreur et de reprise de page suivante dans la pile.

## Hors périmètre

- **Les listes d'état servies avec le jeu** : c'est `ENT-125`.
- **Le préchargement des recherches ouvertes** : c'est `ENT-127`.
- **La corbeille**, qui garde son appel : les écartés sont sortis du jeu côté serveur, leurs fiches ne
  sont donc pas dans les cent chargées.
- **Le gel de l'ordre lui-même** (`applyFrozenOrder`, `orderStack`) : il sert toujours, un jeu complet
  se réordonne quand même quand on change de tri.
- **Toute évolution de l'affinage** au-delà du retrait de son bouton « parcourir tout », devenu
  inutile puisque le jeu est entier.

## Décisions de cadrage

### « 100 sur 352 », pas « 100+ »

Dire le vrai nombre coûte le même espace et évite le contresens le plus cher : croire le vivier plus
pauvre qu'il n'est. Le recruteur qui voit 352 comprend que sa recherche est trop large ; celui qui
voit « 100+ » comprend qu'on lui cache quelque chose.

### Le rang porte sur ce qu'on parcourt

« 12 / 100 » décrit la pile qu'on est en train de traverser. Le total réel s'affiche **à côté**, pas à
la place : mêler les deux dans un même rapport donnerait « 12 / 352 » sur une pile qui s'arrête à 100.

### On retire, on ne neutralise pas

Le code devenu sans objet sort du dépôt plutôt que de rester derrière une condition « au cas où ».
L'historique git le garde ; une mécanique morte laissée en place se paie à chaque lecture ultérieure.

### La carte de recherche garde le nombre réel, seul — décidé à l'implémentation

Le cadrage demandait « 100 sur 352 » **aussi sur la carte de recherche** de la collection. Ça n'a pas
été fait, et ça ne doit pas l'être : à cet endroit **rien n'est chargé**. La carte annonce ce que la
recherche ramène ; y écrire un nombre de profils chargés parlerait d'un chargement qui n'a pas eu
lieu, et qui dépendrait de si l'on a ouvert la recherche ou non. Elle affiche donc toujours le compte
réel — ce qu'elle faisait déjà, et qui est juste.

La mention double vit là où un jeu est effectivement chargé : le rang de la Visionneuse et l'onglet
« Tous » de la Table.

### La règle d'affichage vit dans la couche pure — décidé à l'implémentation

Deux surfaces comparent le chargé au réel. La comparaison est partie dans `services/search/stack.ts`
plutôt que d'être écrite deux fois : c'est un signe qui aurait fini par diverger, et c'est aussi le
seul morceau de cette tâche que l'outillage de test du dépôt sait couvrir.

### La taille vient de la configuration

Le palier de métadonnées charge déjà `/api/config` au démarrage de l'espace, précisément pour éviter
que les écrans recopient des constantes serveur. La limite de chargement y entre.

## Règles et cas limites

- **Recherche courte** (80 profils) : le compte s'affiche seul, sans « sur » — il n'y a rien à
  distinguer.
- **Recherche exactement à cent** : idem, aucun signal particulier ; `total` vaut 100.
- **Affinage posé** : le rang suit l'affinage, le total réel ne bouge pas — règle existante,
  inchangée.
- **Écran étroit** : la mention du total ne doit pas pousser le rang hors de la barre ; mobile-first.
- **Lecteur d'écran** : le compte doit s'annoncer en une phrase compréhensible, pas comme deux nombres
  collés.
- **Contrat en retard** : tant qu'`API-048` n'est pas livrée, le jeu peut dépasser cent. L'écran ne
  doit pas casser — il affiche ce qu'il reçoit.

## Documentation impactée

**Aucune.** Le cadrage visait `composables-standard.md` et `services-standard.md` ; relecture faite,
ces pages portent des **conventions de couche** — responsabilité unique, frontières, placement des
types — et n'inventorient ni les fonctions d'un service ni les retours d'un composable. Rien de ce
que ce diff change ne les rend fausses. Même constat pour `i18n.md`, qui décrit la mécanique des
locales et non la liste des clés.

## Vérification

**Trace réseau réelle**, navigation client depuis la liste vers une recherche de **641
correspondances** (stack du worktree entreprise, `owner@demo.showcase-recruiter-webapp-saas.example`) :

```
appels au BFF à l'ouverture (4) :
   GET …/results?perPage=100        ← le jeu entier, en UN appel
   GET …/favorites                  ┐
   GET …/results?scope=favorites    ├ les trois que ENT-125 supprimera
   GET …/results?scope=dismissed    ┘

puis la pile déroulée jusqu'au bout (40 gestes de défilement) :
   aucun appel de plus — seul un POST …/reviews, le marquage des profils vus

100 cartes dans le document · rang affiché « 1 / 100  sur 641 »
lu à voix haute : « Profil 1 sur 100 chargés, 641 profils correspondent »
```

**Recherche courte (32 profils)** : rang « 1 / 32 », aucune mention ajoutée — les deux nombres se
confondent, la mention se tait.

**Onglet de la Table**, même recherche : `Retenus 0 · Tous 100 sur 641`.

**Tests et typage**

```
vitest services/search/ + parité des locales    17 fichiers, 311 tests, verts
bun run quality-type-check                      les quatre espaces, vert
```

## Livré

- `webapp/shared/types/config.ts` · `stores/referentials.ts` — la limite de chargement entre dans la
  configuration lue par les écrans.
- `composables/useSearch.ts` — le jeu se demande à cette taille ; `loadNextPage`, `nextPage`,
  `loadingMore` et `loadMoreFailed` disparaissent ; `matchCount` apparaît.
- `services/search/stack.ts` — `needsNextPage`, `LOAD_AHEAD` et `growFrozenOrder` retirés (154 lignes
  → 79), `overflowsLoaded` ajoutée.
- `pages/recherches/[uuid]/index.vue` — le déclenchement à l'approche du bout et la boucle
  « parcourir tout » retirés ; le gel se recalcule simplement.
- `components/search/` — `Stack.vue` perd son pied de pile et sa reprise après échec ; `Console.vue`,
  `ControlPanel.vue`, `RefinePanel.vue` et `RefineChoice.vue` perdent le mécanisme « déplier un volet
  tire le jeu » ; `Position.vue` porte la mention du nombre réel.
- `pages/recherches/[uuid]/table.vue` — l'onglet « Tous » dit les deux nombres.
- trois fichiers de langue — deux clés ajoutées, trois orphelines retirées.
- `services/search/stack.vitest.ts` — les tests des fonctions retirées s'en vont, ceux de la règle
  d'affichage arrivent.

## Reste à faire (hors périmètre)

- **Trois appels subsistent à l'ouverture** — `/favorites` et les deux `?scope=` : c'est `ENT-125`
  qui les traite.
- **Aucun harnais de test de composable ni de composant** dans le dépôt : deux des trois points
  annoncés au cadrage sont prouvés autrement (trace réseau, type-check). En monter un est un sujet
  de socle, pas de cette lane.
- **`search.viewer.panel.scope`**, une clé voisine, reste servie par les deux écrans hérités
  `search-v1` et `search-v2` — non touchés ici.

## Définition de terminé

- [x] Ouvrir une recherche ne déclenche qu'un seul appel de jeu.
- [x] Une recherche large affiche le chargé et le réel — sur le rang de la Visionneuse et l'en-tête de
      la Table. **Pas sur la carte de recherche**, où rien n'est chargé (cf. décisions).
- [x] Une recherche courte affiche son seul compte.
- [x] Plus aucun appelant des fonctions retirées ; le bouton « parcourir tout » a disparu.
- [x] La taille demandée vient de la configuration.
- [x] Test vitest sur la règle d'affichage du compte — les deux autres points ne sont pas
      couvrables par vitest en l'état, et leur preuve est ci-dessus.
- [x] Documentation : aucune page déclenchée, vérifié plutôt que supposé.
