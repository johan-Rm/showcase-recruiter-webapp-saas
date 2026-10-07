# TASK ENT-056 — entreprise : la carte se lit même quand elle n'a rien à annoncer

- **Créée le** : 2026-08-06.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-06 — la carte dit désormais ce qu'elle n'a pas, et sa barre
  d'avancement est enfin visible. 7 tests neufs.
- **Type** : Frontend entreprise — trois retouches d'affichage et une correction de couleur sur un
  composant, aucun contrat ni stockage touché.
- **Tests** : unitaires (vitest) sur ce qui se décide hors du rendu — le rail qui distingue « aucun
  nouveau » de « information indisponible », et la rangée d'étiquettes qui sait qu'elle est vide. Les
  formes et la barre se prouvent en capture, dans les deux thèmes ; la couleur de la barre se prouve
  en **mesure**, c'est ce qui a permis de la trouver.
- **Réf** : **`ENT-050` — amont** (le rail, la rangée d'étiquettes) · **`ENT-055` — amont** (le menu,
  dont le style part sur `WEB-013`) · `webapp/services/search/card.ts` (`railOf`, `criteriaToShow`) ·
  `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  `webapp/base/app/assets/css/themes.css` (`--muted`, `--surface-soft`) ·
  `webapp/base/app/assets/css/nuxt-ui.css` (ligne 77, `--ui-bg-muted`) ·
  **`WEB-014` — aval** (le garde-fou contre le conflit de noms qui a causé le défaut)

## Contexte

La carte d'une recherche sait dire ce qu'elle a. Elle ne sait pas dire ce qu'elle **n'a pas**, et les
trois endroits où ça manque produisent le même effet : un vide que le lecteur doit interpréter.

**Le bas du rail** aligne aujourd'hui deux chiffres dans une seule phrase — « 12 vus · 3 retenus ».
Deux mesures qui n'ont ni le même sujet ni la même durée de vie se lisent comme une énumération, et
le point médian ne dit pas laquelle est laquelle avant d'avoir lu les deux mots.

**La mention des nouveaux profils n'apparaît que s'il y en a.** Une recherche sans rien de neuf
n'affiche donc rien du tout à cet endroit — et rien du tout ressemble à « la carte n'a pas fini de
charger », pas à « j'ai regardé, il n'y a rien ». C'est précisément l'information qu'un recruteur
vient chercher en survolant sa liste le matin.

**La rangée d'étiquettes disparaît quand elle est vide.** Depuis `ENT-053`, le titre dit le métier,
le premier mot-clé, l'expérience et les études ; une recherche qui n'a que ça sous la main n'a plus
rien à afficher en dessous, et sa carte se creuse d'un blanc sans cause visible.

**Et la barre d'avancement est invisible**, dans les deux thèmes. Elle n'est pas cassée : la piste
fait bien toute la largeur, et le remplissage suit fidèlement le rapport vus/total. C'est sa
**couleur** qui est exactement celle du fond sur lequel elle est posée — mesuré au chiffre près :

```
                    remplissage        fond du rail
thème sombre        rgb(67, 67, 67)    rgb(67, 67, 67)
thème clair         rgb(205, 218, 207) rgb(205, 218, 207)
```

La conséquence est pire qu'une barre absente : ce qu'on voit est la piste **non remplie**, donc la
barre **se lit à l'envers**. Une recherche vue à 90 % montre un petit trait à droite, qui ressemble à
un début d'avancement.

L'origine est un **conflit de noms**. Le code écrit `bg-muted` en visant le gris de texte du nuancier
(`--muted`, `#a5a5a5` en sombre). Mais `bg-muted` est un nom que **Nuxt UI revendique**, et chez lui
ce n'est pas un gris de texte : c'est une couleur de fond, que notre propre fichier de raccordement
branche sur `--surface-soft` — c'est-à-dire le fond du rail. Rien ne signale l'erreur : le nom
demandé existe et répond, il répond simplement autre chose.

## Périmètre

- **Le bas du rail devient une structure à deux colonnes** — « vus » et « retenus » — séparées par un
  **trait vertical pointillé**. Chaque nombre au-dessus de son mot.
- **« Pas de nouveaux » s'affiche** à la place exacte où « N nouveaux » apparaîtrait, en gris
  atténué.
- **Un badge à bordure pointillée « pas de mots clés »** prend la place de la rangée d'étiquettes
  quand elle est vide.
- **La barre d'avancement redevient visible** : son remplissage prend le gris de texte du nuancier.
- Les clés i18n correspondantes.
- Les tests des deux règles qui se décident hors du rendu.

## Hors périmètre

- **Le style du menu déroulant** — sans bordure, ombre légère. Il part dans le thème du kit et touche
  les quatre espaces : c'est **`WEB-013`**, sur la lane du socle. Rien n'en est écrit ici.
- **La Visionneuse et la Table**, qui affichent aussi des compteurs : elles gardent leur forme.
  Tranché à l'arbitrage — cette tâche ne touche que la carte.
- **Le calcul des compteurs** : `vus`, `retenus` et `nouveaux` viennent de l'API et ne changent pas.
- **Le calcul de l'avancement** : le rapport vus/total est juste, vérifié sur onze cartes. Seule sa
  couleur change.
- **Le garde-fou contre le conflit de noms** : c'est **`WEB-014`**, sur la lane du socle. Cette
  tâche corrige l'occurrence, elle n'écrit pas la règle.
- **Le nombre d'étiquettes affichées** — deux puis le compteur, comme depuis `ENT-050`.
- **Le nom automatique** : c'est `ENT-053`, livrée.

## Décisions de cadrage

**Deux colonnes plutôt qu'une phrase, et un trait plutôt qu'un point.** Les deux nombres ne mesurent
pas la même chose : « vus » est **personnel** et grandit à mesure qu'on travaille, « retenus »
appartient à la **recherche** et se partage avec l'équipe. Les aligner dans une phrase les fait lire
comme deux moments d'une même progression. Deux colonnes séparées par un trait disent qu'ils sont de
nature différente, et le chiffre au-dessus de son mot se lit sans lecture séquentielle.

**Le trait est pointillé, et c'est un choix de ton.** Un trait plein sur ce fond deviendrait une
bordure, ce que le guide de marque écarte — la séparation se fait par le fond. Le pointillé sépare
sans découper : il marque une frontière de lecture, pas une cloison.

**Une seule colonne sur la carte d'un collègue.** L'avancement personnel n'y existe pas — le
« vus » d'un autre ne me regarde pas et l'API ne le sert pas. Y laisser une colonne barrée d'un tiret
dirait « pas d'information » alors que la bonne lecture est « cette information n'existe pas pour
vous ». La carte affiche donc « N retenus par l'équipe » en pleine largeur, sans trait, comme
aujourd'hui.

**« Pas de nouveaux » occupe la place, en gris atténué.** Occuper la place est le point : c'est ce qui
distingue *aucun nouveau* de *pas encore chargé*, et c'est ce qui rend la colonne de cartes lisible
d'un trait — chaque carte répond à la même question au même endroit. Le gris atténué, lui, empêche
l'absence de nouvelle de peser autant que la nouvelle : le vert signature reste réservé à ce qui
appelle un geste.

**Une recherche close garde « close », et ne dit pas « pas de nouveaux ».** Elle n'attend plus rien —
annoncer qu'elle n'a pas de nouveaux profils serait répondre à une question que personne ne se pose
sur une recherche terminée. La mention de clôture l'emporte donc à cet emplacement.

**Le badge se déclenche sur la rangée vide, pas sur l'absence de mots-clés.** La règle est celle de
l'écran, pas celle de la donnée : ce que le lecteur voit, c'est un espace vide sous le titre, et
c'est lui qu'il faut expliquer. Le déclencher sur l'absence stricte de mots-clés laisserait sans
explication le cas le plus fréquent depuis `ENT-053` — une recherche **qui a** des mots-clés, tous
déjà dits par le titre, et dont la rangée est vide quand même.

**Le badge est bordé de pointillés et vide de fond.** Les étiquettes ordinaires ont un fond plein et
pas de bordure ; l'inverse dit « ceci n'est pas une étiquette, c'est la place qu'elles auraient
prise ». C'est la convention visuelle du vide en attente, et elle se lit sans légende.

**La barre prend le gris de texte du nuancier, pas l'accent.** C'est le gris des chiffres posés
juste en dessous : la barre et ses nombres disent la même chose, ils appartiennent à la même famille
visuelle. Le vert a été écarté délibérément — l'avancement est une **information**, pas un appel à
l'action, et l'accent reste réservé à ce qui demande un geste. Il aurait de surcroît ajouté un
septième aplat vert sur l'accueil, question restée ouverte depuis `ENT-054`.

**Le token existe déjà, aucune valeur nouvelle n'est inventée.** Le correctif consiste à désigner
`--muted` explicitement au lieu de passer par un nom que Nuxt UI intercepte. C'est aussi ce qui rend
le défaut impossible à reproduire à cet endroit précis, indépendamment de la règle d'écriture que
`WEB-014` posera pour tout le dépôt.

**Aucune reprise de données.** Les trois retouches se calculent à l'affichage, à partir de ce que
l'API sert déjà. Elles s'appliquent rétroactivement à toutes les recherches.

## Règles et cas limites

- **Recherche à zéro profil** : le rail affiche « 0 profils », « pas de nouveaux », et les deux
  colonnes à zéro. Aucun cas particulier — dire zéro est une réponse.
- **Recherche close** : « close » reste à la place de la mention des nouveaux, et les colonnes du bas
  ne changent pas.
- **Carte d'un collègue** : une seule colonne, pas de trait, pas de barre d'avancement — inchangé
  par rapport à aujourd'hui, hormis la forme.
- **Le trait ne s'affiche jamais seul** : s'il n'y a qu'une colonne, il n'y a pas de trait. Un
  séparateur qui ne sépare rien est un trait décoratif.
- **Le badge « pas de mots clés » ne coexiste pas avec des étiquettes** : il les remplace, il ne s'y
  ajoute pas.
- **Le badge n'est pas cliquable** et n'annonce rien à la voix qu'un lecteur d'écran doive répéter
  après le titre : c'est un état, pas un geste.
- **La barre doit se distinguer de sa piste ET du fond du rail**, dans les deux thèmes. C'est la
  double condition que l'état actuel ne remplissait pas : la piste se voyait, le remplissage non.
- **La barre reste à zéro sur une recherche à zéro profil**, sans division par zéro — déjà couvert.
- **Toute chaîne passe par une clé i18n**, badge et libellés de colonnes compris.
- **Les deux thèmes** : le pointillé et le gris atténué doivent rester lisibles sur `porcelain` comme
  sur `shark`, où le contraste ne se comporte pas de la même façon.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit le contenu du rail — vérifié lors du cadrage d'`ENT-050`, et
rien n'a changé depuis. Aucun `README.md` ne change de convention : la règle vit dans
`services/search/`, dont le contrat « couche métier pure » est inchangé.

À revérifier au moment de livrer, la doc ayant bougé entre-temps.

### Écart : le contour du badge a dû être renforcé, sur mesure

Le badge est né avec le trait des séparations, `--line`, par cohérence avec le trait entre colonnes.
Mesuré, il tenait mal : **1,5:1 en thème sombre**, où l'encadré ne se lisait plus comme *la place
qu'auraient prise les étiquettes* mais comme du simple texte gris.

Les deux traits n'ont pas le même travail, et c'est ce qui a tranché. Entre deux colonnes voisines,
la **proximité** sépare déjà — le trait ne fait que suggérer une frontière, et il reste au token des
séparations. Le contour du badge, lui, doit tenir **seul** une forme : il prend donc le gris de
texte, atténué de moitié pour rester un contour et non un cadre. Porté à **2,68:1 en sombre** et
**2,12:1 en clair**.

### Écart : mes premiers chiffres de contraste étaient faux

La première mesure annonçait 1,05:1 pour ce contour. Elle divisait par 255 des canaux déjà exprimés
de 0 à 1 — le format que Chrome emploie pour `color-mix()`, là où un hexadécimal sort de 0 à 255.
Le **sens** était juste, les nombres non. C'est le deuxième passage de ce piège dans la même session ;
il est noté ici pour qu'une mesure de contraste ne soit plus jamais lue sans vérifier le format
qu'elle parse.

## Définition de terminé

- [x] Le bas du rail montre deux colonnes, « vus » et « retenus », séparées par un trait vertical
      pointillé, chaque nombre au-dessus de son mot.
- [x] Sur la carte d'un collègue, une seule colonne et aucun trait.
- [x] Une recherche sans profil neuf affiche « pas de nouveaux », en gris atténué, à la place où
      « N nouveaux » apparaîtrait.
- [x] Une recherche close continue d'afficher « close » à cet emplacement.
- [x] Une carte dont la rangée d'étiquettes est vide affiche un badge à bordure pointillée
      « pas de mots clés ».
- [x] Ce badge disparaît dès qu'au moins une étiquette est affichée.
- [x] La partie remplie de la barre se voit dans les deux thèmes, et se distingue de la piste.
- [x] Une recherche vue à 90 % montre une barre remplie aux neuf dixièmes, et non l'inverse.
- [x] Aucun texte en dur.
- [x] Les deux règles qui se décident hors du rendu sont couvertes par des tests unitaires.
- [x] Le rendu est prouvé en capture, dans les deux thèmes.

## Vérification

**Les quatre points, mesurés sur 59 cartes réelles**, dans les deux thèmes :

```
Ébéniste              pas de nouveaux   [0 vus | 0 retenus]  trait=dashed  barre   0/116px  badge « pas de mots clés »
Ingénieur DevOps      12 nouveaux       [0 vus | 0 retenus]  trait=dashed  barre   0/116px
Support informatique  pas de nouveaux   [9 vus | 9 retenus]  trait=dashed  barre  61/116px
Architecte (IT)       pas de nouveaux   [5 vus | 5 retenus]  trait=dashed  barre  21/116px
Data Scientist · nlp  2 nouveaux        [29 vus | 9 retenus] trait=dashed  barre 109/116px

59 cartes · 55 à deux colonnes · 4 cartes de collègues à UNE colonne, sans trait
6 recherches closes : mention « close », jamais « pas de nouveaux »
```

**La barre est visible, et sa couleur est bien celle du nuancier** — avant, elle valait le fond sur
lequel elle était posée, au chiffre près :

```
            remplissage          fond du rail
clair       rgb(71, 94, 78)      rgb(205, 218, 207)
sombre      rgb(165, 165, 165)   rgb(67, 67, 67)
```

`#475e4e` et `#a5a5a5` : le gris de texte dans chaque thème.

**Les deux cas absents du jeu de données ont été fabriqués par l'application**, pas en base : une
recherche à un seul critère — dont le titre absorbe tout, ce qui vide la rangée — et la clôture
d'une recherche.

**Les contrastes, après correction du calcul** :

```
                        avant     après
contour du badge        1,5:1     2,68:1   (sombre)
                        1,2:1     2,12:1   (clair)
trait entre colonnes    1,42:1    inchangé (sombre)
                        1,23:1    inchangé (clair)
```

**`card.vitest.ts` : 7 cas neufs**, et **173 tests** verts sur `services/search/`. Type-check vert
sur les quatre espaces.

**Deux tests ont échoué d'abord**, et c'est ce qui rend la preuve solide : un test existant affirmait
« n'annonce une nouveauté que lorsqu'il y en a une » — la règle même que cette tâche remplace ; et
**une de mes propres fixtures était fausse**, ses deux critères tombant tous les deux dans le titre
automatique, si bien que la rangée était vide alors que le test la voulait pleine.

## Livré

- `webapp/services/search/card.ts` **(M)** — `newCount` garde zéro au lieu de le remplacer par un
  vide, et `criteriaToShow` rend un drapeau `isEmpty` porté sur ce que le **lecteur verra**, pas sur
  ce que la recherche contient.
- `webapp/services/search/card.vitest.ts` **(M)** — 7 cas, dont la règle remplacée réécrite.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — les deux colonnes au trait
  pointillé, « pas de nouveaux » à la place qu'occuperait la nouvelle, le badge de rangée vide, et
  le remplissage de la barre qui cesse d'emprunter un nom que Nuxt UI intercepte.
- `webapp/base/i18n/locales/fr.yaml` **(M)** — `seen` et `retained` ne portent plus que le libellé,
  accordé sur un compte qu'elles n'affichent plus ; deux clés neuves pour les deux absences.

## Reste à faire (hors périmètre)

- **Les blocs `search.card` anglais et arabe sont très en retard** : il leur manque `profiles`,
  `newCount`, `seen`, `retained`, `retainedByTeam`, `closed`, `closedOn`, `sharedBy` — un écart
  antérieur à cette tâche, que je n'ai ni aggravé ni comblé. Il mérite sa propre carte.
- **`WEB-014`** — le garde-fou contre les noms que Nuxt UI intercepte, cause du défaut de la barre.
  Elle **ne peut pas être fusionnée avant celle-ci** : sa règle bloquerait la fusion tant que
  l'occurrence fautive existe.
- **L'équilibre des aplats accent sur l'accueil**, ouvert depuis `ENT-054`.
