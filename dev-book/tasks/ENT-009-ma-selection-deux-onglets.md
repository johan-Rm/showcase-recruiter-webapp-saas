# TASK ENT-009 — entreprise : « Ma sélection » se réduit à deux onglets

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28 · task 162 (la sélection) ·
  task 154 (l'export porte sur une sélection).

## Contexte

L'écran affichait **tout le jeu** — 256 profils sur la recherche d'essai — découpé en sept vues
d'état : Tous · Nouveaux · Au Maroc · Disponibles · Favoris · Non dévoilés · Dévoilés.

Son rôle change : une recherche a maintenant **deux moments**, et celui-ci est le second — on y
regarde **ce qu'on a retenu**, pas la masse. Le premier écran s'occupe de parcourir et de trier.

## Décisions

### Deux onglets

**Tous** et **Favoris**. Les cinq autres disparaissent d'ici.

Ils servaient à découper une **population** ; sur une liste de dix ou vingt profils retenus, il n'y a
plus rien à découper. Les états correspondants deviennent des **badges sur le profil** (`ENT-006`),
là où ils informent sans structurer.

### Ce qui reste à cet écran

Ce que la task 162 lui a donné et qui garde son sens : la sélection en lot, l'export, et le
dévoilement de plusieurs profils d'un coup.

### Tranché — « Tous » est le jeu entier

L'écran garde une porte sur les profils non retenus : **retenir n'engage à rien**, et on peut se
rattraper. Les deux onglets ont alors chacun leur sens — la masse d'un côté, la récolte de l'autre —
là où « tout ce qui a été retenu » aurait fait de « Favoris » un sous-ensemble dont personne n'aurait
su dire lequel.

Conséquence : le premier écran trie, il ne condamne pas.

### Tranché — « Tous » s'arrête aux écartés

Le jeu arrive déjà privé de ce qui a été écarté : c'est la promesse d'`ENT-008`, une pile qui se
referme. Les faire réapparaître ici la défairait. La corbeille reste consultable, mais depuis
l'écran de parcours — c'est là qu'on écarte, c'est là qu'on se dédit.

## Critères d'acceptation

- [x] Deux onglets, et deux seulement.
- [x] Les compteurs des onglets portent sur le jeu entier, pas sur les seules lignes chargées.
- [x] La sélection, l'export et le dévoilement en lot continuent de fonctionner.

## Mise en œuvre

Les deux compteurs viennent de sources qui connaissent le jeu ENTIER, et non des lignes affichées :
`total` (porté par `resultIds`, que l'API sert en entier) et la **liste des retenus, servie à part et
sans pagination** — `GET /v1/searches/{id}/favorites`.

C'est ce second point qui a demandé du travail. Le jeu de résultats arrive par pages de trente ; sur
une recherche de plusieurs centaines de profils, un favori posé au-delà de la première page
n'apparaissait nulle part côté client, et l'onglet aurait compté ce qui était chargé plutôt que ce qui
était retenu. La liste devient donc la vérité du store, et remplace celle déduite des cartes.

**Limite connue.** Les compteurs disent vrai, les LIGNES restent celles de la page chargée : un favori
posé au-delà de la première page est compté sans être listé. Le combler demande un `?scope=favorites`
sur `/results`, sur le modèle du `?scope=dismissed` déjà en place — une carte côté API, hors du
périmètre de celle-ci.

`viewCounts` disparaît avec les cinq vues qu'il servait : compter sur le pool chargé était justement
ce qu'il ne fallait plus faire.
