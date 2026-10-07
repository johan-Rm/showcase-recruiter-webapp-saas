# TASK ENT-041 — entreprise : la console passe à gauche et prend des onglets

- **Créée le** : 2026-08-03.
- **Session** : `cb761d33-c5ed-45b5-a3db-3340b185010f`.
- **Statut** : ✅ fait le 2026-08-04 — la console est à gauche, en trois onglets, la barre haute a
  disparu et le tiroir avec elle.
- **Type** : Frontend espace entreprise — refonte du plan d'un écran, sans nouvelle règle métier.
- **Tests** : `sans objet` côté unitaire — la carte déplace des balises et compose des onglets, elle
  ne produit **aucune fonction pure** ni aucun code exécutable testable (`webapp/vitest.config.ts` ne
  ramasse que du code pur). La preuve est double et exigeante : `bun run quality-conventions-check`
  vert **sans exception** pour les composants touchés, et des captures authentifiées avant / après,
  en clair et en sombre, montrant le panneau à gauche, les trois onglets, les puces rapatriées et la
  disparition de la barre haute. Précédents : `ENT-036`, `ENT-040`.
- **Réf** : **aval `ENT-042`** (l'onglet des filtres secondaires, qui remplit le troisième onglet) ·
  `ENT-040` (le défilement unique de l'écran, que cette carte entame — voir *Décisions*) ·
  `ENT-036` (la balise de région appartient à la page) · `ENT-038` (le contenu du panneau) ·
  [vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md) ·
  [base/app/pages/README.md](../../webapp/base/app/pages/README.md)

## Contexte

Sur l'écran de parcours des profils, **ce qui sert à chercher est éclaté en trois endroits**.

Les critères posés vivent en puces dans une barre haute, avec le nombre de résultats et un bouton
d'affinage. Le formulaire qui les modifie vit dans un **tiroir** qui se superpose à la pile — on
quitte des yeux ce qu'on ajuste pour l'ajuster. Et le panneau de droite, qui porte tout le reste du
travail de recherche, ignore les critères.

**Trois zones pour une seule question** — *sur quoi je cherche, et qu'est-ce que ça donne* —, dont
une qui se superpose et une qui n'existe que pour deux boutons.

Le panneau, lui, est déjà la console de la recherche : il porte le nom, l'avancement, la
confrontation aux critères, ce qu'on a retenu et la sortie. Il lui manque les critères eux-mêmes et
de quoi les modifier.

## Périmètre

- **Le panneau passe à GAUCHE**, la pile de profils à droite.
- **La barre haute disparaît** de cet écran : puces, compteur et bouton d'affinage avec elle.
- **Trois onglets dans le panneau** — *Recherche en cours* (ouvert par défaut), *Filtres classiques*,
  *Filtres secondaires*.
- **Les puces de critères** reviennent dans le premier onglet : **toutes**, retirables à l'unité.
- **Le formulaire du tiroir** emménage dans le deuxième onglet ; **le tiroir est supprimé**.
- **Le troisième onglet reste vide** — il est rempli par `ENT-042`.
- **Le titre de page**, qui vivait dans la barre haute, est relogé dans la page.
- **Les clés i18n** des trois onglets, dans les trois langues.
- **Le cadrage client** [vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md).

## Hors périmètre

- **La Table**, qui garde sa barre haute et ses puces. Les deux outils d'une même recherche cessent
  de se ressembler ; c'est assumé, et c'est la décision de ne traiter qu'un écran à la fois.
- **Les douze filtres secondaires** : `ENT-042`. Cette carte livre l'onglet, pas son contenu.
- **La largeur du panneau**, qui ne change pas — tranché.
- **Le contenu du panneau** (rang, avancement, confrontation aux critères, favoris, sortie), qui
  déménage sans être retouché.

## Décisions de cadrage

### Le panneau devient la console, et il passe à gauche

Un seul endroit répond à « sur quoi je cherche » : les critères posés, de quoi les modifier, et où
j'en suis. Le reste de l'écran ne montre plus que des profils.

À gauche parce que c'est le point de départ de la lecture : on part de ce qu'on cherche, on va vers
ce qu'on trouve. La barre haute occupait une bande pleine largeur pour deux boutons et une liste de
puces — de l'espace pris à la pile, sur un écran qui n'en a jamais assez.

### Les puces reviennent en entier, et restent retirables

Toutes les puces, y compris celles que les filtres secondaires poseront — une recherche complète
peut en afficher seize.

C'est le seul endroit du produit où l'on retire un critère **d'un clic**, sans rouvrir un formulaire
ni chercher dans quel onglet il se trouve. Les couper aux quatre classiques obligerait à défaire à
deux endroits selon le filtre, et personne ne retiendrait la règle.

### Le tiroir disparaît plutôt que de cohabiter

Le formulaire des filtres classiques existe à un seul endroit : son onglet.

Deux chemins vers le même formulaire, c'est deux occasions de diverger et un doublon à maintenir. Et
le tiroir avait un défaut propre : il se superposait à la pile, donc on ajustait sans voir l'effet.

### Sur la recherche d'un collègue, les onglets de filtres disparaissent

Seul *Recherche en cours* subsiste quand la recherche n'est pas modifiable.

Deux onglets qu'on ne peut pas utiliser occupent la barre pour rien et invitent à un geste qui
échouera. Les critères, eux, restent lisibles en puces — non retirables.

### Le panneau défile, et c'est une exception consciente

La Visionneuse pose qu'il n'existe **qu'un seul défilement** à l'écran, celui de la pile ; `ENT-040`
vient de renforcer ce principe en faisant du conteneur un bloc conteneur.

Douze filtres dans une colonne de deux cent quatre-vingts pixels ne tiennent pas dans une hauteur
d'écran. Le panneau défile donc **dans sa colonne**, sans jamais entraîner la pile — c'est un
défilement *interne à une zone*, pas un second défilement de page. La distinction est mince mais
réelle, et elle s'écrit ici pour que la prochaine carte ne la prenne pas pour un oubli.

## Règles et cas limites

- **La molette au-dessus de la pile ne défile jamais le panneau**, et réciproquement : deux zones,
  deux défilements indépendants.
- **Les onglets se parcourent au clavier** — flèches, `Home`, `Fin` — et l'onglet actif est annoncé.
  C'est le comportement natif du composant de la bibliothèque ; il se vérifie, il ne se suppose pas.
- **Le titre de page ne se perd pas.** Sans lui, on entrait dans un document sans en-tête de premier
  niveau, et la première rencontrée était celle d'une carte de profil.
- **Le nombre de résultats** n'est pas reperdu avec la barre haute : le panneau affiche déjà le rang
  sur le total.
- **Recherche sans critère** : les puces cèdent la place à la phrase qui invite à en poser un.
- **Aucun texte en dur**, `aria-label` compris ; trois langues, et l'arabe en RTL — le panneau change
  de côté, donc de bord d'ancrage.
- **La balise de région appartient à la page**, pas aux composants : c'est elle qui décide du plan
  (`WEB-006`, appliqué par `ENT-036`).

## Documentation impactée

- **[vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md)** — le §2 dessine
  l'anatomie actuelle (barre haute, panneau à droite), le §4.1 décrit la rangée d'actions et le §5 le
  panneau. Les trois deviennent faux. Page **écrite pour le client** : aucune note, aucune date,
  aucune mention de ce qui n'existe plus.
- **Vérifié, non impacté** : `criteres_de_filtrage_profils.md` porte les critères **fonctionnels** et
  dit lui-même ne pas porter le placement UI ; `console_contextuelle.md` décrit une zone que la
  Visionneuse masque déjà.

## Définition de terminé

- [x] La console est à gauche de la pile — comparé dans le DOM, pas à l'œil — et plus aucune barre
      haute n'existe sur cet écran.
- [x] Trois onglets, le premier ouvert par défaut ; flèche droite déplace le focus, `Entrée` active.
- [x] Toutes les puces de critères sont dans le premier onglet et se retirent à l'unité.
- [x] Le formulaire des filtres classiques est dans le deuxième onglet, **avec la pile visible à
      côté** ; `RefineDrawer.vue` est supprimé et n'a plus aucun appelant.
- [x] Sur une recherche non modifiable, seul le premier onglet est construit — et un droit d'écriture
      perdu en cours de route ramène sur lui.
- [x] La pile défile sans entraîner la console. **Réserve** : la console ne déborde pas encore, donc
      son propre défilement reste à constater — voir *Écart au cadrage*.
- [x] La page porte toujours son titre de premier niveau, relogé hors de la barre haute.
- [x] `quality-conventions-check` : **11 règles, aucune violation**.
- [x] Captures authentifiées : les trois onglets, en clair et en sombre.
- [x] Le cadrage décrit le nouvel écran.

## Écart au cadrage

Trois points où l'implémentation a divergé, dont un corrigé à l'écran.

### Les libellés d'onglets sont plus courts que prévu

Le cadrage annonçait *Recherche en cours*, *Filtres classiques*, *Filtres secondaires*. Posés dans
une colonne de deux cent quatre-vingts pixels, ils sortaient **tronqués** : on lisait « Recherche
en… », « Filtres classi… ». Trois onglets et trois libellés longs ne tiennent pas.

Ils deviennent *En cours*, *Classiques*, *Secondaires*. Le premier onglet répète de toute façon
« RECHERCHE EN COURS » en titre, deux lignes plus bas — le libellé court ne perd rien.

### Le défilement de la console n'est pas prouvé

La carte annonce que le panneau défile. La structure est en place, mais **aucune zone défilante
n'existe encore** dans la console : son contenu tient dans la hauteur d'écran. Le défilement ne se
constatera qu'avec les douze filtres d'`ENT-042`, qui le rendront nécessaire.

Ce qui est acquis ici : la console est bornée en hauteur et son contenu est prêt à déborder sans
entraîner la pile. Ce qui reste à voir : qu'il déborde bien.

### Une clé de traduction meurt avec la barre haute

`search.count` — « 27 profils » — n'avait qu'un seul appelant : la barre haute. Celle-ci partie,
la clé n'est plus lue nulle part, dans aucune des trois langues.

Elle est retirée plutôt que laissée : une clé morte dans trois catalogues est une dette gratuite, et
c'est cette carte qui l'a tuée. L'information, elle, n'est pas perdue — le rang « 1 / 27 » du premier
onglet porte le total.

### La console prend l'aplat que le panneau portait

`ControlPanel` portait sa surface, ses coins et son débordement ; il les cède à la console qui
l'onglette. C'est mécanique — les onglets et leur contenu doivent partager le même fond, sinon la
barre d'onglets flotte au-dessus d'un panneau détaché. Le panneau garde ce qui est à lui : remplir
la hauteur qu'on lui donne, et coller son pied au bord.

## Vérification

**Le plan, mesuré dans le DOM** — sortie lue le 2026-08-04 :

| Constat | Valeur lue |
| --- | --- |
| Console à gauche de la pile | vrai (comparaison des positions) |
| Barres hautes sur l'écran | **0** |
| Onglets | `En cours` · `Classiques` · `Secondaires` |
| Titre de premier niveau | « Parcourir les profils · 1 an et plus · Licence · vue » |

**Le clavier**, vérifié et non supposé : depuis *En cours*, la flèche droite porte le focus sur
*Classiques*, `Entrée` l'active — l'onglet sélectionné passe bien à *Classiques*.

**Le gain se voit sur la capture du deuxième onglet** : le formulaire est ouvert **et la pile reste
visible à côté**. C'est précisément ce que le tiroir empêchait, puisqu'il se superposait à elle.

**Conventions** : 11 règles, aucune violation, y compris celle qui interdit à un composant de porter
une balise de région.

## Livré

- `webapp/spaces/entreprise/app/components/search/Console.vue` **(+)** — les trois onglets, et la
  liaison locale de l'onglet actif qui interdisait de les composer dans la page.
- `webapp/spaces/entreprise/app/components/search/RefineDrawer.vue` **(D)** — sans appelant.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — accueille les puces,
  cède l'aplat à la console.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — grille inversée, barre
  haute supprimée, titre relogé.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — les libellés de la console, et le compteur de
  la barre haute qui n'a plus de lecteur.
- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` **(M)** — §2, §5, décisions validées, et la
  section des vues en barre haute qui décrivait une zone disparue.

## Aval

- **`ENT-042`** remplit le troisième onglet.
- **La Table**, qui garde une barre haute dont la Visionneuse s'est débarrassée : à réconcilier, ou à
  assumer durablement.
