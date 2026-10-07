# TASK ENT-078 — entreprise : le portrait et son verdict ne font plus qu'une carte

- **Créée le** : 2026-08-10.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-10 — la couture entre la fiche du profil et sa colonne de verdict a
  disparu. L'aplat, les coins et l'anneau du profil retenu appartiennent désormais au **bloc qui
  réunit les deux**, et un filet horizontal prend le relais pour séparer deux profils qui se suivent.
- **Type** : mise en page. Aucune donnée, aucune règle, aucun geste ne change ; seuls se déplacent
  l'aplat, les coins, l'anneau et les écarts.
- **⚠️ Cadrée après coup.** Demandée en conversation, schématisée puis validée avant écriture. Un
  arbitrage a été posé à l'humain : ce qui sépare deux profils une fois la fiche devenue transparente.
- **Tests** : `sans objet` — aucune logique, aucun service, aucune clé de traduction n'est touchée.
  Trois fichiers changent, et uniquement leurs classes de présentation. La preuve est **une mesure
  des styles calculés** et **un rendu réel** aux trois paliers de largeur.
- **Réf** : `ENT-076` — la colonne de verdict, dont cette carte soude l'apparence au portrait ·
  `ENT-077` — les deux archives, **qui ne reçoivent pas ce changement** et gardent leur couture.

## Contexte

Depuis `ENT-076`, la Visionneuse pose côte à côte deux surfaces distinctes : la fiche du profil et la
colonne de verdict, chacune avec son aplat, ses coins arrondis et un écart entre les deux.

À l'écran, cela se lit comme **deux objets posés l'un à côté de l'autre**, alors que ce sont les deux
faces d'une même chose : ce profil, et ce que la recherche en dit. L'anneau accent du profil retenu
n'entourait d'ailleurs que la fiche, laissant son verdict en dehors.

## Périmètre

- **Un conteneur nouveau** dans `pages/recherches/[uuid]/index.vue`, qui enveloppe la pile **et** le
  verdict, et porte l'aplat, les coins, le rognage et l'anneau accent.
- **La fiche devient transparente** : plus d'aplat, plus de coins, plus d'anneau.
- **La colonne de verdict devient transparente** : elle garde sa marge intérieure, elle perd sa
  surface.
- **Un filet en haut de chaque fiche sauf la première**, et **l'écart entre fiches disparaît**.
- **L'anneau suit le profil qu'on regarde** et non plus celui que porte chaque fiche.

## Hors périmètre

- **La console de gauche.** Elle garde son aplat et son écart : c'est le seul trait de séparation qui
  subsiste à l'écran, et il sépare deux choses qui n'ont rien à voir — où l'on en est, et qui l'on
  regarde.
- **`data-zone="content"`**, qu'il était tentant de charger de l'aplat. Il contient aussi la console :
  lui donner la surface aurait soudé les trois colonnes en un seul bloc.
- **Les deux archives `recherches-v1/` et `recherches-v2/`.** Elles gèlent un état, elles ne suivent
  pas la ligne vivante — c'est leur raison d'être.

## Décisions de cadrage

### L'aplat descend d'un cran, pas de deux

`data-zone="content"` porte les **trois** zones. Le bon élément est un cran plus bas : un conteneur
qui n'enveloppe que la pile et le verdict, posé dans la deuxième colonne de la grille de page. La
console reste à côté, séparée par l'écart qui existait déjà.

Conséquence : la grille de page passe de trois colonnes à **deux** — console et bloc — et c'est le
bloc qui se partage en deux colonnes à partir de 1280 px.

### L'anneau change de sujet

Il entourait la fiche, donc le profil qu'elle portait. Il entoure maintenant un bloc qui **ne défile
pas**, et doit donc suivre le profil qu'on regarde — la même source que le verdict et son étoile. Les
trois disent désormais la même chose du même profil, ce qui n'était pas le cas avant.

Il reste INTÉRIEUR (`inset`) : posé en dehors, il décalerait tout le bloc d'un pixel à chaque bascule
de l'étoile, et l'écran sauterait sous le curseur.

### Un filet, parce que l'aplat ne sépare plus rien

Arbitré avec l'humain. Sans aplat propre, deux fiches qui se suivent se touchent sans rien entre
elles. Le filet reprend le trait déjà employé par les quatre encadrés du portrait (`border-line`).

**Il ne coûte rien au repos** : une fiche occupe exactement un écran, sa limite haute tombe hors
champ. Il n'apparaît que pendant le défilement, au moment précis où deux profils se partagent la vue
— et c'est là qu'on a besoin de savoir où l'un finit.

**Il ne traverse pas le verdict** : il appartient à la fiche, qui n'occupe que la colonne du
portrait. Le verdict, lui, ne se rattache à aucun des deux profils en transition.

### L'écart entre fiches disparaît

Il montrerait maintenant l'aplat du bloc : une bande vide sous laquelle rien ne se passe, juste
au-dessus du filet qui, lui, dit quelque chose. Deux marques pour une seule limite.

## Règles et cas limites

- **Rognage** : le conteneur porte `overflow-hidden`, sans quoi la pile déborderait par les angles
  arrondis.
- **Sous 1280 px** : le verdict n'a plus sa colonne et retombe en pied de fiche. Le conteneur
  n'enveloppe alors que la pile — l'aplat et l'anneau restent les siens, et l'effet est le même.
- **Aucun profil** : le bloc garde son aplat et affiche l'état de la pile (chargement, erreur, jeu
  vide). L'anneau ne se pose pas, `isRetained(null)` étant faux.
- **Recherche d'un collègue** : rien ne change, l'anneau reste un constat et non un geste.

## Documentation impactée

Aucune. La carte de navigation décrit **où** vivent les choses, pas leur habillage, et rien ne se
déplace ici : la console reste à gauche, le verdict à droite de la pile.

## Définition de terminé

- [x] Plus aucune couture entre le portrait et son verdict.
- [x] L'anneau accent entoure les deux, et suit le profil regardé.
- [x] Un filet sépare deux profils qui se suivent, et ne traverse pas le verdict.
- [x] Les deux archives sont inchangées.

## Vérification

### Les styles calculés

```
filet sur la 1re fiche   : 0px
filet sur la 2e fiche    : 1px      couleur : color(srgb 1 1 1 / 0.12)   ← le trait du projet
aplat de la fiche        : rgba(0, 0, 0, 0)                             ← transparente
aplat du bloc            : rgb(33, 33, 33)                              ← la surface
coins du bloc            : 8px
anneau du bloc           : rgb(198, 231, 107)                           ← l'accent
écart entre fiches       : normal                                       ← plus d'écart
```

### Le rendu, au geste réel

Défilement à la molette entre le premier et le deuxième profil, accrochage neutralisé le temps de la
mesure :

```
après molette   fenêtre:523 px   regardé:Zahira Hedri   panneau:Zahira Hedri   rang:2 / 19
```

Le filet apparaît entre les deux profils et s'arrête au bord de la colonne du portrait ; le verdict
et le rang basculent ensemble sur le profil qui couvre le milieu de l'écran.

⚠️ **Une capture antérieure montrait le panneau en désaccord** — artefact de mesure : le défilement y
était imposé par affectation directe dans la même opération qu'un changement de style, et l'événement
n'atteignait pas la page. Au geste réel, la visée est juste ; rejoué trois fois.

### Les trois adresses restent distinctes

```
/recherches      fil:0  critèresDansLaConsole:false  colonneDroite:true   ← ligne vivante
/recherches-v1   fil:1  critèresDansLaConsole:true   colonneDroite:false  ← archive, intacte
/recherches-v2   fil:0  critèresDansLaConsole:false  colonneDroite:true   ← archive, intacte
```

Trois largeurs capturées : **1920** (deux colonnes dans le bloc), **1150** (verdict en pied, un seul
aplat), **800** (empilé).

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) le bloc, l'aplat, l'anneau ; la grille de page passe à deux colonnes |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) transparente, sans coins ni anneau, un filet en haut |
| `spaces/entreprise/app/components/search/Stack.vue` | (M) plus d'écart entre les fiches |

## Reste à faire (hors périmètre)

- **Les archives ne suivent pas**, et c'est voulu : `recherches-v1/` et `recherches-v2/` gardent
  leurs surfaces séparées.
- **Le découpage de `ProfileCard.vue`** — 449 lignes pour 200 autorisées.
- **Le compteur « Favoris » de la console**, qui affiche 0 alors que des profils sont retenus — défaut
  préexistant décrit dans `ENT-076`.
