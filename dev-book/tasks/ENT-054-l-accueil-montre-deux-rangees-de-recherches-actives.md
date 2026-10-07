# TASK ENT-054 — entreprise : l'accueil tient en deux rangées

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — l'accueil montre deux rangées pleines à chaque largeur, et
  son lien ne compte plus que les actives. 4 tests neufs.
- **Type** : Frontend entreprise — une règle d'affichage d'écran, aucun contrat touché.
- **Tests** : unitaires (vitest) sur le découpage — quatre recherches à deux colonnes, six à trois,
  et le lien qui ne compte que les actives. Le rendu se prouve en capture, aux trois largeurs.
- **Réf** : **`ENT-051` — amont** (elle a exclu les closes de l'accueil) ·
  `webapp/spaces/entreprise/app/pages/index.vue` (`RECENT_SEARCHES`) ·
  `webapp/services/search/collection.ts` (`recentActive`) ·
  `webapp/base/app/assets/css/sizing.css` (`--breakpoint-xwide`)

## Contexte

L'accueil montre **six** recherches sur une grille qui n'a **deux colonnes** qu'en dessous de
1920 px : cela fait **trois rangées**, et l'écran déborde. Le commentaire du code affirme pourtant
que « deux rangées suffisent » — la valeur a été choisie pour une grille à trois colonnes, qui
n'existe que sur les très grands écrans.

Second défaut, introduit par `ENT-051` : le lien du bas annonce « voir mes N recherches » en comptant
la collection **entière**, closes comprises, sous une liste qui les exclut désormais. Deux cartes
affichées, « voir mes 3 recherches » en dessous. Et sa condition d'apparition a changé de sens au
passage : elle voulait dire « il y en a de plus anciennes », elle peut maintenant dire « il y en a
des closes ».

## Périmètre

- **Deux rangées pleines, quelle que soit la largeur** : quatre recherches en dessous de 1920 px,
  six au-delà, où la grille passe à trois colonnes.
- **Le lien ne compte que les actives**, et disparaît quand il n'en reste aucune à voir.

## Hors périmètre

- **L'équilibre des aplats accent sur cet écran** : cf. *Décisions de cadrage*. C'est le seul point
  de cette page qui reste ouvert, et il est nommé plutôt que tu.
- **Le contenu de la carte** — c'est `ENT-050`, livrée.
- **Le tri**, qui reste par date de dernière modification.

## Décisions de cadrage

**Deux rangées pleines plutôt qu'un nombre fixe.** Quatre cartes sur une grille à trois colonnes
laisseraient une rangée de trois et **une carte orpheline** avec deux cases vides à côté. Le nombre
suit donc la largeur, et l'invariant tenu est celui qu'on veut réellement : jamais de rangée
bancale, jamais de défilement sur cet écran.

**Le lien compte ce que l'écran montre.** Annoncer un total qui inclut ce qu'on vient de masquer est
un décalage que le lecteur remarque immédiatement — c'est d'ailleurs ainsi qu'il a été repéré. Le
lien porte donc le nombre d'**actives** et disparaît quand toutes sont déjà affichées. **Conséquence
assumée** : plus rien, depuis l'accueil, ne signale qu'il existe des recherches closes à relire. Elles
vivent dans « Mes recherches », derrière leur pli, et c'est là qu'on va les chercher.

**L'équilibre des aplats accent n'est pas tranché ici.** Depuis `ENT-050`, chaque carte porte un
aplat vert ; l'accueil en compte donc plusieurs en plus des deux boutons de moteur, qui sont sa
raison d'être. La question est réelle — le moteur doit rester dominant — mais elle **se juge sur
pièce**, sur une maquette, pas sur une carte de tâche. La trancher au jugé dans cette tâche serait
décider sans voir.

### Écart : trois paliers, pas deux

Le cadrage parlait de deux largeurs — « quatre en dessous de 1920 px, six au-delà ». La grille en a
**trois** : elle n'a qu'**une seule colonne** sous 640 px. La règle « deux rangées pleines » s'y
applique naturellement et donne **deux cartes** sur téléphone ; ce palier est traité plutôt
qu'ignoré, et la définition de terminé s'en trouve élargie.

### Écart : le découpage est porté par le GABARIT, pas par la largeur de fenêtre

L'écran rend les six recherches et masque le surplus par palier CSS. Calculer le nombre depuis
`window.innerWidth` aurait fait diverger le rendu serveur du rendu client à l'hydratation — le
serveur ne connaît pas la largeur. Le lien suit la même mécanique : il se masque par palier au lieu
de se calculer.

**Conséquence assumée** : les cartes masquées restent dans le document. Elles sont retirées par
`display: none`, donc invisibles aussi pour un lecteur d'écran — mais elles sont bien téléchargées.
Le coût est nul à cette échelle : deux cartes de plus au maximum.

## Règles et cas limites

- **Sous 640 px la grille n'a qu'une colonne** : deux rangées y font **deux** cartes.
- **Le seuil est celui du kit**, `--breakpoint-xwide` (120 rem), et non une valeur réécrite dans la
  page : la grille et le nombre doivent basculer **ensemble**, sinon la rangée se casse au premier
  ajustement du palier.
- **Moins de recherches que de places** : la grille se remplit de ce qu'il y a, sans place vide
  ajoutée.
- **Aucune recherche active, mais des closes** : l'accueil affiche son message vide et le lien
  n'apparaît pas — c'est « Mes recherches » qui porte les closes.
- **Le découpage reste une règle d'ÉCRAN** : aucun paramètre nouveau sur la collection, qui continue
  de tout servir. C'est déjà la posture du tri.
- **Le nombre ne se lit PAS côté client** : c'est le gabarit qui masque le surplus, cf. *Écart*.
  La largeur ne se devine pas au rendu serveur, et la lire en JavaScript ferait diverger les deux
  rendus à l'hydratation.

## Documentation impactée

Aucune. Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit le contenu de l'accueil, et aucun `README.md` ne
change de convention.

## Définition de terminé

- [x] En dessous de 1920 px, l'accueil montre au plus **quatre** recherches, sur deux rangées.
- [x] Au-delà, il en montre **six**, toujours sur deux rangées.
- [x] Aucune rangée incomplète, à aucune largeur.
- [x] L'écran ne défile pas quand la place est prise.
- [x] Le lien annonce le nombre de recherches **actives**, et disparaît quand elles sont toutes là.
- [x] Le basculement suit le palier du kit, pas une valeur recopiée.
- [x] Le découpage est couvert par des tests unitaires.

## Vérification

**Mesuré aux trois paliers**, sur neuf recherches actives et une close :

| Largeur | Colonnes | Cartes visibles | Lien |
| --- | --- | --- | --- |
| 500 px | 1 | **2** | « Voir mes 9 recherches » |
| 1280 px | 2 | **4** | « Voir mes 9 recherches » |
| 2100 px | 3 | **6** | « Voir mes 9 recherches » |

**Deux rangées pleines partout, aucune rangée bancale**, et l'écran ne défile à aucune des trois
largeurs. Le compte annoncé est **9** alors que la collection en porte **10** : la recherche close
est bien exclue du décompte comme de l'affichage.

**`collection.vitest.ts` : 4 cas neufs** — la capacité à chaque palier, l'invariant du multiple de
deux, la garde contre un nombre négatif, et surtout le **garde-fou de correspondance** entre les
colonnes déclarées et celles du gabarit. **154 tests** verts sur `services/search/`, type-check vert
sur les quatre espaces.

## Livré

- `webapp/services/search/collection.ts` **(M)** — `HOME_ROWS`, `HOME_COLUMNS` et `homeCapacity` :
  les colonnes vivent **une seule fois**, avec l'avertissement qu'elles doivent suivre le gabarit.
- `webapp/services/search/collection.vitest.ts` **(M)** — 4 cas.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** — masquage du surplus par palier, et le
  lien qui compte les actives et se masque quand il n'y a plus rien à voir.

## Reste à faire (hors périmètre)

- **L'équilibre des aplats accent sur cet écran.** À 2100 px, la capture montre **sept aplats
  verts** — six cartes plus le bouton de création. La question n'est plus théorique, et elle se
  juge sur une maquette, pas sur une carte.
- **`ENT-055`** — renommer, dupliquer, partager, supprimer — reste à mener.
