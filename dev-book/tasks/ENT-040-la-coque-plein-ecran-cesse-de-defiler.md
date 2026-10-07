# TASK ENT-040 — la coque plein écran de la Visionneuse cesse de défiler

- **Créée le** : 2026-08-03.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-03 — le conteneur défilant de la pile est devenu un bloc conteneur,
  les quatre-vingt-un `sr-only` qu'il porte s'y rattachent au lieu de se rattacher au document, et la
  coque plein écran ne défile plus : `html.scrollHeight` retombe de 19 663 px à la hauteur exacte de
  la fenêtre, sans le moindre écart visuel.
- **Type** : Frontend espace entreprise — fix d'un défaut de mise en page.
- **Tests** : `sans objet` côté unitaire — la carte ne produit aucune fonction pure ni aucun code
  exécutable ; elle ajoute une propriété CSS. La preuve est une **mesure DOM avant / après** dans le
  navigateur authentifié : `document.documentElement.scrollHeight` comparé à `window.innerHeight`,
  sur une recherche portant assez de profils pour que l'écart soit franc. jsdom ne calcule pas de
  layout : un test de composant ne verrait rien.
- **Réf** : **amont `ENT-036`**, qui a refondu ce fichier et posé la règle que ce défaut viole ·
  [cockpit.vue](../../webapp/spaces/entreprise/app/layouts/cockpit.vue), qui porte le contrat de
  hauteur · [Stack.vue](../../webapp/spaces/entreprise/app/components/search/Stack.vue), seul fichier
  touché.

## Contexte

La Visionneuse est une **coque plein écran** : `cockpit.vue` est en `h-dvh` et `overflow-hidden`, et
la pile de profils est le seul défilement de l'écran — `Stack.vue` l'écrit en tête de fichier, et
`ENT-036` en a fait une règle explicite.

**Elle ne l'est plus.** Le document lui-même est devenu défilant sur environ 20 000 px : une barre de
défilement apparaît, et l'utilisateur peut faire sortir toute la coque du champ — topbar, barre de
critères et colonne comprises — pour n'atteindre que du vide.

### Le mécanisme

`sr-only` de Tailwind est un `position: absolute`. Un élément absolu se place dans son **bloc
conteneur**, c'est-à-dire son plus proche ancêtre positionné — et il n'y en a **aucun** sur tout le
chemin qui remonte du profil au document :

```
div.search-stack                    (flex h-full)          non positionné
  div[role=list]                    (overflow-y-auto)      non positionné  ← le scroller
    SearchProfileCard               (aucun relative)       non positionné
      dt.sr-only / span.sr-only     position: absolute     ← se rattache au DOCUMENT
```

Faute d'ancêtre positionné, leur bloc conteneur est le bloc conteneur initial : le document. Deux
conséquences, et c'est la seconde qui fait le défaut :

1. **`overflow-y-auto` ne les rogne pas.** Un ancêtre à débordement caché ne coupe un élément absolu
   que s'il est aussi son bloc conteneur. Ici il ne l'est pas, donc ils s'échappent.
2. **Ils ne défilent pas avec la pile.** Leur position statique est résolue une fois, en coordonnées
   de document. La carte au rang 25 pose donc le sien à quinze mille pixels sous le pli, et le
   document s'étire d'autant. Vingt-sept cartes d'un plein écran chacune ≈ les 20 000 px observés.

Le défaut est **invisible à l'œil** — ces éléments sont, par construction, réservés aux lecteurs
d'écran — et ne se manifeste que par la barre de défilement qu'ils font naître. Il grandit avec le
nombre de profils : une recherche à trois résultats ne le montre pas.

`ENT-036` a déplacé ces balises et ajouté `role="list"` sans que le défaut apparaisse alors : il
préexistait, porté par les `sr-only` de `ProfileCard` et de `MaskedValue`, que cette carte n'a pas
touchés.

## Périmètre

- **Donner un bloc conteneur au conteneur défilant de la pile** —
  [Stack.vue:88](../../webapp/spaces/entreprise/app/components/search/Stack.vue#L88) : le `div`
  `role="list"` devient positionné, ce qui referme les éléments absolus qu'il porte à l'intérieur de
  son débordement.
- **Écrire le pourquoi en commentaire**, à côté : `relative` sans motif se lit comme un reliquat et
  se fait retirer au prochain nettoyage. La raison n'est pas devinable depuis le fichier — elle vit
  dans des composants enfants.

## Hors périmètre

- **`table.vue`** et les autres écrans de la lane : non instruits ici. Le même mécanisme peut y jouer
  — c'est un motif, pas un accident — mais le constater demande une mesure par écran, et cette carte
  n'en fait qu'une.
- **`overscroll-contain` sur la pile**, laissé ouvert par `ENT-036`. Sujet voisin, cause distincte :
  celui-là traite le **chaînage** du défilement, celui-ci son **existence**. Les mêler dans un commit
  brouillerait la preuve.
- **La disparition de la colonne de droite sous `lg`** (`hidden lg:block`), relevée dans la même
  passe d'analyse : elle engage la grille de `main` et une décision produit — que voit-on sur un
  téléphone —, pas une propriété CSS. Sa propre carte.
- **Une règle de conventions dans `rules.yaml`** qui interdirait un scroller non positionné : elle
  protégerait les quatre espaces, mais `rules.yaml` est du socle et relève de la lane `WEB`. Nommée,
  pas écrite ici.
- **La divergence des deux coques** — `cockpit` fluide contre `dashboard` à couture fixe : lane `WEB`
  également.

## Décisions de cadrage

### Le confinement va sur le scroller, pas sur la carte

Positionner `SearchProfileCard` corrigerait aussi le symptôme, et de plus près. C'est le scroller qui
le reçoit, pour deux raisons.

La première est de responsabilité : le débordement et son confinement sont **la même décision**. Le
fichier qui déclare `overflow-y-auto` est celui qui doit déclarer ce que ce débordement contient ;
les séparer, c'est reproduire exactement le partage de mise en page entre deux fichiers qu'`ENT-036`
vient de défaire.

La seconde est de couverture : posé sur la carte, le confinement ne vaut que pour elle. Tout autre
enfant du scroller — un séparateur, un marqueur de fin de jeu — rouvrirait le défaut sans prévenir.
Posé sur le scroller, il vaut pour tout ce qui y entrera.

### Ce n'est pas la faute de `sr-only`, et on n'y touche pas

Retirer ou réécrire les `sr-only` de `ProfileCard` et `MaskedValue` ferait disparaître le symptôme en
supprimant ce qui rend le contenu masqué lisible aux lecteurs d'écran — on paierait un défaut de mise
en page avec de l'accessibilité. Ces éléments sont corrects ; c'est leur contenant qui ne les
contient pas.

### Aucun changement d'allure attendu

À la différence d'`ENT-036`, cette carte s'interdit tout écart visuel. Les éléments confinés sont
invisibles, et le confinement ne déplace rien de ce qui se voit. **Si l'écran bouge, c'est un défaut
de cette carte** — hors la barre de défilement du document, qui doit disparaître.

## Règles et cas limites

- **La pile redevient le seul défilement de l'écran** : le document ne défile plus, la coque reste
  bornée à la hauteur de la fenêtre.
- **`scroll-snap` n'est pas touché** — `snap-y snap-mandatory` sur le conteneur, `snap-start` sur la
  card. Un bloc conteneur n'a pas d'effet sur l'ancrage.
- **« Reprendre » et le rang suivi par la colonne restent justes** : `scrollTo` et `useVisibleProfile`
  interrogent le même élément qu'avant, et cet élément ne change ni de nature ni de place.
- **Effet de bord à connaître** : tout descendant absolu de la pile se rattachera désormais au
  scroller. Aucun n'existe aujourd'hui hors les `sr-only` — vérifié : ni `ProfileCard`, ni
  `MaskedValue`, ni `Card` ne portent de classe de positionnement. Mais un futur survol ou menu
  ancré dans une card ne pourra plus déborder de la pile ; il devra passer par un portail.
- **Les quatre états de la pile ne sont pas concernés** : ils ne rendent pas le scroller.
- **La colonne de droite est déjà étanche** — `SearchControlPanel` est `relative overflow-hidden` et
  gère son propre débordement. Elle n'est pour rien dans le défaut, et ne bouge pas.

## Documentation impactée

Aucune. Le commentaire de tête de `Stack.vue` affirme déjà que le défilement de la pile est le seul
de l'écran : cette carte le rend vrai, elle ne le réécrit pas.

## Définition de terminé

- [x] Le conteneur défilant de la pile est un bloc conteneur, et un commentaire dit pourquoi.
- [x] **Mesure avant** relevée dans le navigateur authentifié : `scrollHeight` du document très
      supérieur à `innerHeight`, sur une recherche à plusieurs dizaines de profils.
- [x] **Mesure après** sur le même écran et la même recherche : `scrollHeight` égal à `innerHeight`,
      le document n'a plus de débordement vertical.
- [x] La pile défile toujours, « Reprendre » ramène sur la bonne card, et le rang affiché par la
      colonne suit le profil visible.
- [x] Aucun autre écart visuel entre les deux mesures.
- [x] Les gates **impactées** sont vertes : conventions et format. **Lint et type-check n'ont pas
      été joués** — le diff n'ajoute ni script, ni type, ni identifiant : une classe utilitaire dans
      un template et un commentaire. Ils tourneront à l'ouverture de la PR, comme la règle le veut.

## Vérification

**La mesure, avant et après, sur le code servi.** Navigateur authentifié (Chromium, fenêtre
1600 × 900), recherche `019fc2ad-259b-7cef-9662-063923cf7363` — 27 profils :

| | `html.scrollHeight` | `body.scrollHeight` | le document défile ? | `sr-only` rattachés au scroller |
| --- | --- | --- | --- | --- |
| **Avant** | **19 663** px | 900 px | **oui** (18 763 px de trop) | **0 / 81** |
| **Après** | **900** px | 900 px | **non** | **81 / 81** |

`innerHeight` vaut 900 px dans les deux cas. Deux relevés désignent la cause sans ambiguïté :

- **`body` était déjà à 900 px pendant que `html` montait à 19 663.** Le débordement portait donc sur
  la boîte `html`, signature d'éléments absolus rattachés au bloc conteneur initial — pas d'un
  contenu trop long dans le flux.
- **Le `sr-only` le plus bas se trouvait à 19 663 px**, au pixel près la hauteur défilante du
  document. C'est lui qui l'étirait.

**L'hypothèse a été testée avant d'être écrite** : `position: relative` injecté à la volée sur le
scroller depuis la console, sur la page servie non modifiée. `html.scrollHeight` est passé de 19 663
à 900 et les 81 `sr-only` se sont rattachés au scroller — le correctif était démontré avant que le
fichier ne bouge.

**Ce qui n'a pas bougé, vérifié explicitement.** Le scroller garde ses **20 010 px** de contenu et
ses 27 cards : la pile défile exactement comme avant. `scrollIntoView` sur l'enfant de rang 5 amène
bien à `scrollTop = 3707`, donc « Reprendre » et le rang suivi par la colonne restent justes.

**Aucun écart visuel.** Captures avant / après du même écran, même recherche, même profil courant :
identiques au pixel — seul le badge Nuxt DevTools diffère (2,6 s / 619 ms), qui n'appartient pas à la
page.

**Gates impactées.** `prettier --check` sur le fichier ✔ — vérification due, la classe ayant été
placée à la main dans un ordre que le tri Tailwind contrôle. `quality-conventions-check` → *9 règles,
aucune violation* ✔.

## Livré

- `webapp/spaces/entreprise/app/components/search/Stack.vue` (M) — `relative` sur le conteneur
  défilant, et le commentaire qui dit pourquoi il est là. Sans ce commentaire, une classe de
  positionnement sans effet visible se lit comme un reliquat : la raison vit dans des composants
  enfants, elle n'est pas devinable depuis ce fichier.

## Reste à faire (hors périmètre)

Rien qui appartienne à cette carte. Quatre sujets ont été nommés en la cadrant et attendent chacun la
leur : le même mécanisme sur `table.vue`, non instruit ; `overscroll-contain` sur la pile, hérité
d'`ENT-036` ; la colonne de droite qui disparaît sous `lg` (lane `ENT`) ; une règle `rules.yaml`
interdisant un scroller non positionné, et la divergence des coques `cockpit` / `dashboard` (lane
`WEB`).
