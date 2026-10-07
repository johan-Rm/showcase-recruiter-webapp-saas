# TASK ENT-109 — entreprise : la jauge occupe la colonne qu'on lui a donnée

- **Créée le** : 2026-08-13.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-13 — la jauge occupe la colonne, le rang tient le bord droit, et
  les deux s'alignent sur le CONTENU de la colonne du verdict, pas sur sa boîte.
- **Type** : mise en page. Aucune donnée, aucune règle, aucun geste ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle change une répartition de place.
  La preuve est la **mesure des trois bords** — le rang doit finir au bord droit de la colonne, la
  jauge commencer à son bord gauche, et plus aucun vide ne doit subsister entre eux.
- **Réf** : `ENT-087` — qui a posé la jauge et fixé ses vingt paliers · `ENT-105` — qui a fait de la
  droite de la bande une colonne à l'aplomb du verdict, et créé le vide que celle-ci comble.

## Contexte

`ENT-105` a donné à la droite de la bande haute la largeur de la colonne du verdict. Le contenu, lui,
n'a pas suivi : la jauge et le rang restent **collés à gauche** de cette colonne, et le vide se
retrouve à droite.

Mesuré à 1920 px : la colonne fait 376 pixels, la jauge et le rang en occupent environ 170, et **deux
cents pixels restent inoccupés** entre le chiffre et le bord. Une colonne à laquelle on a donné une
largeur mais dont le contenu ne l'occupe pas ne se lit pas comme une colonne — elle se lit comme un
contenu mal placé.

## Périmètre

- **Le rang se colle au bord droit** de la colonne.
- **La jauge s'étire** pour occuper tout ce qui reste.
- **Un écart franc** les sépare.

## Hors périmètre

- **Le nombre de paliers**, qui reste à vingt.
- **Les six autres écrans** qui montent la même bande : ils n'ont pas de colonne de fin.
- **Le masquage sous 640 px** : la jauge disparaît, le rang reste — posé par `ENT-087`, inchangé.
- **La couleur, la hauteur et le remplissage** de la jauge.

## Décisions de cadrage

### Vingt cases qui s'élargissent, et non des cases fines plus nombreuses

**Arbitré.** Le nombre de paliers ne bouge pas : `ENT-087` l'a fixé à vingt parce qu'en dessous, le
tiers et le quart d'une recherche donnent le même dessin. Faire varier ce nombre avec la largeur
disponible rouvrirait cette décision, et surtout : **le même avancement ne donnerait plus le même
dessin** d'un écran à l'autre.

Ce sont donc les cases qui grandissent. Le dessin reste identique partout, seulement plus grand.

### Aucun plafond de largeur, parce que le risque ne se matérialise pas

L'objection à la décision ci-dessus est qu'une case élargie devient carrée et que la jauge se met à
ressembler à un damier. **Mesuré, ça n'arrive pas** aux largeurs réelles :

```
1440 px   ~12 × 16 px   verticale
1920 px   ~13 × 16 px   verticale
2560 px   ~19 × 16 px   légèrement large
```

Un plafond de largeur ferait par ailleurs **réapparaître le vide** qu'on vient de combler, au-delà
d'une certaine taille d'écran. On corrigerait un défaut en le recréant plus loin.

### Un écart franc entre la jauge et le rang

**Arbitré.** Quatre pixels les séparent aujourd'hui. Une jauge étirée va jusqu'au chiffre, et sans un
vrai blanc entre eux, **la dernière case et le rang se lisent comme un seul objet** — on ne voit plus
où le remplissage s'arrête. Un écart de l'ordre de douze pixels les sépare sans les éloigner.

### Sous 1280 px, la jauge reprend sa taille fixe

Il n'y a alors plus de colonne : la bande reprend une fin dimensionnée sur son contenu, et il n'y a
rien à remplir. La jauge revient à ses vingt cases fines, collées au rang — exactement ce qu'on voit
aujourd'hui sur un écran étroit.

L'alternative — s'étirer quand même — aurait fait pousser le rang vers la droite en mangeant la place
du nom de la recherche, qui cède déjà en premier.


### ⚠️ Écarts constatés à l'implémentation

Deux ajouts décidés sur rendu, hors de ce que la carte annonçait.

- **La cellule prend la marge intérieure de la colonne qu'elle surplombe.** Le cadrage parlait de
  remplir la colonne ; il ignorait que la colonne du verdict a **dix-huit pixels de marge** et que
  son contenu — les cartouches — commence donc en retrait de son bord. Sans cette marge, la jauge
  s'alignait sur la BOÎTE de la colonne au lieu de ce qu'elle contient, et l'écart se voyait à l'œil.
  Ce qu'on lit, c'est le contenu.
- **La jauge passe à soixante pour cent d'opacité.** Rien dans la carte ne parlait de couleur : c'est
  une décision de rendu prise après coup, sur demande, dans le même geste et sur le même objet.
  Étirée sur trois cent cinquante pixels, la jauge à pleine opacité disputait le regard au chiffre
  qu'elle accompagne — or le chiffre est le repère, elle n'en est que le doublon visuel.

  Elle est inscrite ici plutôt que dans une carte à part : même objet, même geste, et la scinder
  aurait fait deux commits pour une ligne.

## Règles et cas limites

- **Recherche sans résultat** : ni jauge ni rang, la bande ne les monte pas. La question ne se pose
  pas.
- **Sous 640 px** : la jauge est masquée, le rang reste seul et se colle à droite.
- **Recherche d'un collègue** : la bande est identique, cet écran ne change pas.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : la jauge n'y est décrite nulle part, et aucune convention de
dossier ne change.

## Vérification

### Le remplissage, à trois largeurs

```
                          1920 px     1440 px     1150 px
cases                        20          20          20
une case                  13 × 16     13 × 16      6 × 16
la jauge part du bord        0 px        0 px        0 px
écart jauge / rang          14 px       13 px       13 px
le rang finit au bord        0 px        0 px        0 px
vide restant                 0 px        0 px        0 px
```

Deux cents pixels étaient inoccupés à 1920 px avant ce changement. Il n'en reste aucun.

### L'aplomb avec le CONTENU de la colonne

```
                  bande      cartouches du verdict     écart
bord gauche       1554              1552                2 px
bord droit        1890              1890                0 px
```

Les deux pixels restants sont l'écart structurel nommé par `ENT-105` — la bande porte une marge à
gauche que la zone de travail n'a pas.

### La proportion des cases tient

Une case fait **13 × 16 px** à 1920 comme à 1440 : plus haute que large, jamais carrée. C'était le
seul risque de la décision « vingt cases qui s'élargissent », et il ne se matérialise pas.

Sous 1280 px elle revient à **6 × 16 px** : la colonne n'existe plus, la jauge reprend sa taille
fixe. Ce qui le permet est que `w-1.5` est devenu un **plancher** (`min-w-1.5`), jamais une largeur —
sans lui, vingt cases dans une bande étroite se réduiraient à des traits d'un pixel.

### L'opacité

```
opacité du bandeau            0.6
case entamée, rendue          oklab(… / 0.4)
```

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Progress.vue` | (M) les cases se partagent la place, `w-1.5` devient un plancher ; le bandeau passe à 60 % |
| `spaces/entreprise/app/components/ActionBar.vue` | (M) la cellule de fin prend la marge intérieure de la colonne |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) la jauge pousse, le rang tient le bord, l'écart devient franc |

## Reste à faire (hors périmètre)

- **L'icône d'un bouton dépasse le corps de son texte** sur sept boutons de cet écran. Ce n'est pas
  un défaut local mais un défaut de **thème** : Nuxt UI dimensionne l'icône indépendamment du texte.
  La règle appartient au système de design et se pose dans la configuration racine — **lane socle**,
  nommée et non traitée ici.

## Dépendances

- **Amont** : `ENT-105`, qui a créé la colonne — et le vide.
- **Aval** : aucune.

## Risques et reports assumés

- **Une case large se lit moins comme une jauge** qu'une case fine. Les mesures ci-dessus disent que
  la proportion reste verticale jusqu'à 1920 px, mais c'est un jugement d'œil : s'il se révèle faux à
  l'usage, c'est le nombre de paliers qu'il faudra rouvrir, pas la largeur des cases.

## Définition de terminé

- [x] Le rang finit au bord droit de la colonne.
- [x] La jauge commence au bord gauche et occupe tout l'espace restant.
- [x] Un écart franc sépare la dernière case du chiffre.
- [x] La jauge compte toujours vingt cases.
- [x] Sous 1280 px, elle reprend sa taille fixe.
