# TASK ENT-057 — entreprise : l'ordre du parcours quitte la colonne pour la gouttière

- **Créée le** : 2026-08-06.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-06 — le choix de l'ordre a quitté la colonne pour la gouttière, à
  droite, en menu à icône ; la colonne ne porte plus que deux titres de section.
- **Type** : Frontend entreprise — un contrôle qui change de place et de forme, et une bande qui sort
  de sa veille.
- **Tests** : `sans objet — aucun code exécutable nouveau`. L'ordre lui-même ne change pas : le
  regroupement, le gel et le retour au profil courant restent ceux d'`ENT-049`, couverts par
  `services/search/stack.vitest.ts`. Ce qui bouge est le **lieu et la forme** du contrôle, et le
  webapp n'a aucun harnais de test de composant. La preuve attendue est une **capture** dans les
  deux thèmes, plus la vérification que l'ordre actif s'annonce sans la couleur.
- **Réf** : **`ENT-049` — amont** (elle a posé le sélecteur dans la console, cette carte l'en sort) ·
  `ENT-041` (la console et ses onglets) · `Gutter.vue` · `OrderPicker.vue` · `ControlPanel.vue` ·
  `Console.vue` · `recherches/[uuid]/index.vue`

## Contexte

**Le sélecteur d'ordre s'est arrogé un rang de section.** `ENT-049` l'a posé dans la console, sous
l'entête et au-dessus des critères, avec son intitulé en petites capitales. La colonne en porte
désormais **trois** — « RECHERCHE EN COURS », « ORDRE DU PARCOURS », « CE PROFIL FACE À VOS
CRITÈRES » — de poids typographique identique. Or le choix de l'ordre est le geste le plus rare des
trois : on le pose une fois, souvent jamais, quand la liste des critères se relit à chaque profil.

**Et il coupe une continuité.** Le bloc du haut dit « où j'en suis », la liste dit « ce que ce profil
vaut ». Le sélecteur s'est planté entre les deux alors qu'il ne parle ni de l'un ni de l'autre : il
gouverne la **pile de droite**.

**La bande sous la barre du haut, elle, ne sert à rien sur cet écran.** La Visionneuse la met en
veille — elle garde sa place et se réduit, faute de contenu à porter. Sur la Table, en face, elle
porte une phrase de guidage et les actions de sélection.

## Périmètre

- **Le sélecteur quitte la console** : `OrderPicker.vue` n'est plus assemblé par `ControlPanel.vue`,
  et la chaîne de relais qui traverse `Console.vue` disparaît avec lui.
- **Il se pose dans la gouttière de la Visionneuse**, dans son emplacement **`action`** (à droite).
  La bande sort de sa veille sur cet écran.
- **Il prend la forme d'un menu déroulant à icône** plutôt que de deux liens à bascule.
- **La page reste seule à ranger la pile** : elle tient l'ordre, le gel et le retour au profil
  courant, et ne fait que placer le contrôle ailleurs.

## Hors périmètre

- **L'ordre lui-même.** Le regroupement non-vus-d'abord, la préservation de la pertinence dans
  chaque groupe, le gel qui empêche la pile de bouger sous le curseur, le retour au profil courant :
  tout cela vient d'`ENT-049` et ne bouge pas d'une ligne.
- **La gouttière de la Table**, qui garde son guidage et ses actions de sélection. La Table n'a pas
  de pile à parcourir, l'ordre n'a rien à y faire.
- **Un troisième ordre** — par expérience, par ville, par date. Deux suffisent, comme au cadrage
  d'`ENT-049`.
- **Les trois encadrés de l'entête**, qui font l'objet d'`ENT-058`.
- **Le libellé « sur N profils chargés »**, hors des deux cartes — voir `ENT-058`, *Risques*.

## Décisions de cadrage

### Deux décisions d'`ENT-049` sont renversées, et le motif est le même

La carte amont écrivait, textuellement :

> « Deux liens à bascule **plutôt qu'une liste déroulante** : à deux options, ouvrir un menu pour en
> choisir une est un clic de trop. »

> « Le sélecteur va dans la console. La console dit déjà "sur quoi je cherche et où j'en suis" ;
> l'ordre du parcours en fait partie. »

Les deux raisonnements se tenaient **sur le papier**. À l'écran, le coût réel n'était pas le clic en
trop : c'était le **rang de section** que le bloc s'arrogeait dans une colonne de deux cent
quatre-vingts pixels. Un cadrage ne voit pas un poids typographique ; une capture, si.

C'est le même mécanisme qui avait fait rater le contraste des couleurs de verdict sur `ENT-047`, et
c'est pourquoi le verdict sur une preuve reste humain.

### Un réglage d'écran remonte au niveau de l'écran

La gouttière est la bande d'écran : elle dit où l'on est et ce qu'on peut y faire, au-dessus des
deux colonnes. Un ordre de parcours gouverne la pile entière, pas la colonne qui la commente — sa
place est donc là, pas dans un bloc qui parle du profil courant.

**Bénéfice mesurable :** la bande est déjà présente, simplement réduite. Y poser le contrôle ne coûte
**aucune hauteur** à la pile de profils, alors que dans la colonne il en prenait à la liste des
critères.

### À droite, dans l'emplacement `action`

C'est là que la Table met ses commandes ; la gauche de cette bande est réservée au guidage. Le
recruteur qui passe d'un outil à l'autre retrouve les commandes au même endroit.

### Un menu, parce que la contrainte a changé

L'argument « un clic de trop » valait pour deux liens posés à plat dans une colonne, où ils ne
coûtaient que leur largeur. Dans une bande d'écran partagée avec d'autres commandes, deux libellés
étalés occupent une place qui n'est pas à eux. Le menu rend cette place et ne coûte qu'un clic sur un
geste rare.

## Règles et cas limites

- **L'ordre actif reste lisible sans déplier.** L'icône seule ne dit rien : le libellé de l'ordre
  courant s'affiche sur le bouton, ou à défaut dans son étiquette accessible. Règle héritée
  d'`ENT-049` — « pas seulement par la graisse et la couleur ».
- **Le menu est atteignable au clavier**, et l'option active s'y annonce comme telle.
- **Il disparaît sans profils** : ordonner une pile vide n'a pas d'objet. La gouttière retourne alors
  en veille plutôt que de garder une commande morte.
- **Il n'apparaît que sur la Visionneuse.** La Table garde sa gouttière inchangée.
- **Aucun texte en dur** : les trois clés d'`ENT-049` (`order.label`, `order.unseen`,
  `order.relevance`) sont réutilisées telles quelles, dans les trois langues.
- **Changer d'ordre depuis la gouttière garde le profil courant sous les yeux**, exactement comme
  depuis la console : c'est la page qui traite l'intention, elle ne change pas de main.
- **Mobile-first** : la console n'apparaît qu'à partir de 1024 px, mais la gouttière existe à toutes
  les largeurs. Le menu doit donc tenir sur une bande étroite — c'est un gain, l'ordre devenant
  accessible là où la console ne l'était pas.

## Documentation impactée

- **Les commentaires de `OrderPicker.vue`** (il ne vit plus dans la console et n'est plus deux liens)
  et de `ControlPanel.vue` (elle n'assemble plus le sélecteur).
- **Le commentaire de `Gutter.vue`** si la Visionneuse est le premier écran à y poser une commande —
  à vérifier avant d'écrire.
- **`arborescence-entreprise.md`** — à vérifier : elle décrit les écrans, pas leurs bandes ; elle
  pourrait n'être pas concernée.
- **Vérifié à l'écriture, non impacté** : `i18n.md` (aucune clé nouvelle), `services-standard.md`
  (aucun service touché), `design-system.md` (aucun jeton nouveau).

## Risques et reports assumés

**Un geste rare devient moins visible qu'il ne l'était.** Deux liens à plat s'offrent à la lecture ;
un menu fermé demande qu'on le remarque. C'est le prix assumé du rang qu'on lui retire — et il reste
au niveau de l'écran, donc atteignable sans faire défiler quoi que ce soit.

À rouvrir si l'usage montre que personne ne change jamais d'ordre.

## Définition de terminé

- [x] Le sélecteur a disparu de la colonne de gauche, qui ne porte plus que **deux** titres de
      section — capture à l'appui.
- [x] Il apparaît à droite de la bande sous la barre du haut, sous forme de menu à icône.
- [x] L'ordre actif se lit **sans déplier le menu**, et pas seulement à la couleur.
- [x] Le menu s'ouvre et se parcourt au clavier ; l'option active s'y annonce.
- [x] Changer d'ordre depuis la gouttière garde le profil courant sous les yeux.
- [x] Sans profils, la commande disparaît et la bande retourne en veille.
- [x] La gouttière de la Table est inchangée.
- [x] Aucune clé i18n nouvelle ; les trois clés existantes servent telles quelles.
- [x] Aucun code mort : la chaîne de relais de l'ordre à travers `Console.vue` et
      `ControlPanel.vue` ne laisse rien derrière elle.

## Vérification

### Le déménagement, mesuré dans un navigateur réel

Recherche de 293 profils, relevé identique en thème clair et en thème sombre :

```
1. sélecteur encore dans la colonne : ✓ (absent)
2. dans la gouttière                : ✓
   libellé du bouton                : « Non vus d'abord »
   étiquette accessible             : « Ordre du parcours »
   à DROITE de la bande             : ✓ (bouton à 1330 px, bande de 9 à 1491)
   hauteur de la bande              : 36 px
3. options du menu                  : « Non vus d'abord » (cochée) · « Pertinence »
4. après choix                      : le bouton affiche « Pertinence »
   menu ouvert au clavier           : ✓
```

**L'ordre actif se lit sans déplier** : il est écrit sur le bouton, l'icône ne le porte pas seule.
C'est la règle héritée d'`ENT-049`, tenue malgré le changement de forme.

### La colonne ne porte plus que deux titres

Relevé sur les intitulés de section de la console : `["CE PROFIL FACE À VOS CRITÈRES"]` — plus celui
de la recherche en cours, qui n'est pas un `h2` et échappe à ce relevé. Le troisième, « ORDRE DU
PARCOURS », a disparu avec le bloc.

### Aucun code mort

`canResume`, `firstUnseenIndex`, `unseenCount` avaient déjà été retirés par `ENT-049`. La chaîne de
relais de l'ordre — prop et événement à travers `Console.vue` puis `ControlPanel.vue` — est retirée
en entier : recherche sur `order` dans les deux composants, aucune trace restante.

### Sorties fraîches

```
vitest        19 fichiers, 257 tests passés
conventions   13 règles, aucune violation
```

Aucune gate qualité complète ici : elle appartient à l'ouverture de la PR.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `components/search/OrderPicker.vue` | Devient un menu à icône, avec sa propre racine et son marqueur |
| `pages/recherches/[uuid]/index.vue` | Pose le menu dans la gouttière, qui sort de veille — et y retourne sans profils |
| `ControlPanel.vue` | N'assemble plus le sélecteur ; deux membres de contrat en moins |
| `Console.vue` | Ne relaie plus l'ordre |
| `arborescence-entreprise.md` | L'ordre du parcours rejoint la table « ce qui n'est pas une route » |

## Reste à faire (hors périmètre)

- **La stack a été re-seedée par une autre session** pendant l'implémentation : les recherches de
  démonstration ont changé de compte. Sans conséquence sur le code — signalé parce que toute preuve
  ultérieure devra repartir de recherches recréées.

## Écart au cadrage

### Le composant a dû se donner une racine

Non prévu par le cadrage. Le menu déroulant de la bibliothèque **ne rend aucun élément propre** : il
transmet ses attributs au déclencheur. Le marqueur de composant — la classe qui permet de remonter
d'un bloc du DOM au fichier qui le rend — atterrissait donc sur le bouton, et la règle
`components-root-marker` perdait son objet.

Le composant est désormais enveloppé dans son propre élément. C'est aussi ce qui a fait échouer ma
première mesure : la sonde cherchait un bouton *dans* le marqueur, alors que le marqueur **était** le
bouton.

### La documentation d'arborescence est impactée, contrairement à ce que le cadrage prévoyait

La carte disait « à vérifier : elle décrit les écrans, pas leurs bandes ». Vérification faite, elle
tient une table « **ce qui n'est pas une route** » qui recense précisément les éléments d'écran et
leur emplacement — les critères en barre haute, l'export dans la Table, le profil complet en
surcouche. L'ordre du parcours y a sa place, et son absence aurait laissé la table incomplète.
