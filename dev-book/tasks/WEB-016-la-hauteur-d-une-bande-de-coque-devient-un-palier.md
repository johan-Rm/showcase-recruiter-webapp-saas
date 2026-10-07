# TASK WEB-016 — socle : la hauteur d'une bande de coque devient un palier

- **Créée le** : 2026-08-07.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042`.
- **Statut** : ✅ fait le 2026-08-07 — les trois hauteurs de bande de l'espace entreprise vivent dans
  `sizing.css` et **plus une seule n'est écrite à la main** dans `webapp/`. Le rendu est inchangé :
  **9 mesures avant, 9 après, identiques une à une**, aux trois hauteurs de fenêtre qui font mordre
  le plancher, la proportion puis le plafond ; et 40 px dans les trois langues, arabe RTL compris.
  Deux écarts au cadrage : une **troisième bande** est entrée (la barre d'actions, que le cadrage
  avait manquée), et le **mécanisme de consommation** a été tranché par un fait plutôt que par ma
  préférence — cf. *Décisions de cadrage*.
- **Type** : refactor — une valeur écrite à la main dans un écran remonte dans l'échelle du socle.
  Aucun rendu ne change : les valeurs posées sont **exactement** celles qui existent déjà.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable. Elle déplace trois `clamp()`
  de deux composants vers `sizing.css` et rebranche leurs consommateurs sur les paliers. La preuve
  est une **comparaison de rendu** : les bandes de l'espace entreprise mesurent la même hauteur
  avant et après, dans tous leurs états (gouttière active, gouttière en veille, barre d'actions), à
  au moins deux hauteurs de fenêtre — dont une sous le plancher du `clamp` et une au-dessus de son
  plafond, pour vérifier que les deux bornes sont bien portées.
- **Réf** : **`ADM-018` — aval, bloquée par celle-ci** (l'espace admin consomme ce palier pour la
  bande de titre de son écran « Le vivier ») ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) § *Ce qu'on n'écrit pas
  dans un composant*, interdit n°3, et § *Étendre le système* ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) ·
  fichiers touchés : [base/app/assets/css/sizing.css](../../webapp/base/app/assets/css/sizing.css),
  [entreprise/app/components/Gutter.vue](../../webapp/spaces/entreprise/app/components/Gutter.vue),
  [entreprise/app/components/ActionBar.vue](../../webapp/spaces/entreprise/app/components/ActionBar.vue).

## Contexte

Les coques de l'application posent des **bandes horizontales fixes** : une barre fine, haute de
quelques dizaines de pixels, qui ne défile pas et porte un repère ou une action. L'espace entreprise
en a une sous sa barre haute — la *gouttière* —, qui affiche un guide contextuel à gauche et une
action facultative à droite.

Sa hauteur est écrite à la main, dans le composant, en deux valeurs :

```
min-h-[clamp(32px, 4vh, 46px)]      ← la bande active
min-h-[clamp(12px, 1.5vh, 18px)]    ← la bande en veille, quand la page n'a rien à y dire
```

**Le composant assume l'entorse et l'explique** : son commentaire dit que la hauteur est en
`clamp(min, vh, max)`, que c'est la « seule entorse assumée à `sizing.css` », et que la bande
« n'appartient qu'à la coque cockpit — un token global l'exporterait à tort ».

**L'argument tenait. Il ne tient plus.** Il reposait entièrement sur l'unicité du consommateur : une
valeur utilisée à un seul endroit peut légitimement vivre là où elle sert. L'espace admin a
désormais besoin de la **même** bande, pour la même raison — cf. `ADM-018`. Dès qu'il y a deux
consommateurs, la valeur recopiée devient exactement ce que l'échelle du socle existe pour empêcher :
la prochaine session qui ajuste l'une des deux oubliera l'autre, et les deux espaces divergeront sur
un même produit.

La charte tranche déjà le cas, sans nous laisser de marge : *« Aucun `clamp()` ni `min-[…px]:` à la
main — l'échelle typographique et le rythme d'espacement sont fermés. Un besoin non couvert se règle
en ajoutant un palier dans `sizing.css`, pas en improvisant dans un écran. »*

### Pourquoi la hauteur se mesure sur la fenêtre, et pas sur la largeur

Le détail compte, parce qu'il est contre-intuitif et que la carte aval en dépend. Tous les paliers
existants de `sizing.css` grandissent avec la **largeur** de l'écran (`vw`) : c'est juste pour un
corps de texte ou un rythme d'espacement, qui suivent la place horizontale disponible.

Une bande, elle, coûte de la **hauteur**. Sur un écran bas — une fenêtre de 600 px, un portable avec
ses barres d'outils —, une bande calculée sur la largeur reste grasse pendant que l'écran manque de
vertical. Calculée sur la hauteur, elle se serre là où ça compte. C'est ce que la gouttière fait
déjà, correctement ; le palier ne fait que déplacer cette formule, il ne la révise pas.

## Périmètre

- **Trois paliers dans `sizing.css`**, aux valeurs strictement identiques à celles en place :
  - la gouttière active — `clamp(32px, 4vh, 46px)` ;
  - la gouttière en veille — `clamp(12px, 1.5vh, 18px)` ;
  - la barre d'actions — `clamp(44px, 5.5vh, 64px)`. *Ajoutée en cours d'implémentation, cf.
    Décisions de cadrage.*
- **Un commentaire de palier** qui dit ce que les valeurs ne disent pas : pourquoi `vh` et non `vw`,
  pourquoi un plancher (le contenu doit tenir : une ligne de texte et un bouton) et un plafond
  (au-delà, la bande n'est plus qu'un vide), pourquoi le mode veille réduit sans supprimer, et
  pourquoi la barre d'actions est plus haute que la gouttière.
- **Les deux bandes entreprise consomment les paliers** au lieu de les écrire. C'est leur unique
  changement : une expression de classe chacune, rien d'autre. Leurs commentaires d'en-tête, qui
  justifient l'entorse, deviennent faux et se corrigent dans le même changement.
- **La charte est mise à jour dans ce changement**, pas dans un autre : la règle d'extension du
  système l'impose en toutes lettres — « un token qui existe dans le code mais pas dans la charte
  est un token que personne ne trouvera ».

## Hors périmètre

- **Les barres hautes**, qui portent le même genre de `clamp` écrit à la main : l'admin
  (`clamp(48px, 6vh, 64px)`) et le cockpit entreprise (`clamp(54px, 6.5vh, 76px)`). Elles ne
  bloquent rien, et leurs deux valeurs **diffèrent** — les remonter ensemble déplacerait la
  divergence au lieu de la résoudre. Nommées ici, traitées ailleurs.
  **Relevé au passage** : celle du cockpit est écrite **deux fois**, dans `layouts/cockpit.vue` et
  dans `layouts/recherche.vue`. La duplication est donc déjà là, à l'intérieur d'un seul espace.
- **La remontée du composant `Gutter` vers `base/`.** Elle donnerait une bande partagée pour de bon,
  mais renommerait le composant dans une huitaine de pages de l'espace entreprise — donc une
  troisième lane et un troisième commit, pour un bénéfice qui n'est pas celui qu'on cherche ici.
  L'admin construit sa bande avec les utilitaires du socle ; ce qui est partagé, c'est la **valeur**,
  et c'est elle seule que le socle doit posséder.
- **Le mode veille appliqué ailleurs** : il reste une affaire de l'espace entreprise, qui seul sait
  quand sa page n'a rien à dire.
- **Toute autre valeur de `sizing.css`.** Cette carte ajoute une famille, elle n'en révise aucune.
- **L'écran admin lui-même** — c'est `ADM-018`, sur sa lane, avec son commit.

## Décisions de cadrage

### Le palier plutôt que le composant

Trois voies étaient ouvertes : remonter la seule valeur, remonter la valeur **et** une primitive de
barre de proportion, ou remonter le composant `Gutter` entier.

La troisième est écartée pour son coût : elle transforme une carte de socle en refactor de l'espace
entreprise, et ce refactor n'apporte rien à l'utilisateur. La deuxième est écartée pour une raison
que le README des composants du socle écrit lui-même — un composant du kit « ne se justifie que
lorsque la bibliothèque ne sait pas produire la structure voulue », et « l'exception se démontre,
elle ne se décrète pas ». Une barre de proportion n'a aujourd'hui qu'un seul consommateur, encore à
écrire ; la créer ici serait décréter.

Reste la première, qui est aussi la plus petite : **ce qui est réellement partagé entre deux espaces,
c'est une valeur, pas une structure.** Le socle possède la valeur ; chaque espace compose sa bande.

### La migration du consommateur existant fait partie de la pose du palier

On aurait pu poser les paliers et laisser l'espace entreprise sur son `clamp` écrit à la main,
jusqu'à ce qu'une carte de sa lane le reprenne. C'est refusé : la valeur vivrait alors à **deux**
endroits, ce qui est précisément le défaut que le palier corrige. Un palier qu'aucun ancien
consommateur ne lit n'a rien centralisé, il a seulement ajouté une troisième copie.

Le changement dans l'espace entreprise se limite à deux expressions de classe et à un commentaire
devenu faux. Ce n'est pas un refactor hors périmètre : c'est la seconde moitié du geste.

### ÉCART AU CADRAGE — une troisième bande, que le cadrage avait manquée

Le cadrage n'avait inventorié que la gouttière et son mode veille. **La barre d'actions du cockpit
portait le même `clamp`**, et je ne l'avais pas vue parce que j'avais cherché les *valeurs* de la
gouttière au lieu de l'*archétype*. Elle est apparue à la lecture de la feuille de style générée, où
un `min-h-[clamp(44px,5.5vh,64px)]` subsistait après le changement.

Elle est entrée dans la carte, sur décision prise en cours d'implémentation, et pour une raison qui
n'est pas la symétrie : **son commentaire invoquait explicitement « la même entorse assumée que la
gouttière »**. La laisser dehors aurait laissé dans le dépôt une phrase se réclamant d'une entorse
qui venait de disparaître — c'est-à-dire une fausse piste écrite de ma main.

Son palier n'est pas aligné sur celui de la gouttière, et c'est voulu : elle porte des contrôles
qu'on manipule, là où la gouttière porte un guide qu'on lit. Un contrôle demande une cible qu'on
puisse viser.

### Le palier se consomme par la variable, et non par une directive `@utility`

Le cadrage laissait ce point ouvert : Tailwind 4 dérive des utilitaires des familles qu'il connaît
(`--text-*`, `--spacing-*`, `--container-*`), mais **n'a aucune famille pour les hauteurs**. Le
palier ne produit donc pas de nom tout seul.

**La référence de variable l'emporte** — `min-h-(--band-gutter)` —, et un fait relevé à
l'implémentation a tranché plus sûrement que ma préférence : la bibliothèque de composants emploie
**déjà** cette écriture pour sa propre hauteur d'en-tête (`min-h-(--ui-header-height)` est dans la
feuille servie). Le mécanisme n'était donc neuf que pour notre code, pas pour le bundle. Une
directive `@utility` aurait ajouté un troisième endroit où une bande est décrite — le palier,
l'utilitaire, le point d'usage — pour deux consommateurs.

Vérifié et non supposé : les trois utilitaires sont générés dans la feuille servie, avec la bonne
déclaration.

## Règles et cas limites

- **`min-height`, jamais `height`.** C'est déjà le cas et ça doit le rester : une bande à hauteur
  imposée rogne son contenu en silence dès que le texte grandit — zoom du navigateur, traduction plus
  longue. Réservée, elle grandit et prend sur ce qui défile en dessous, ce qui est une dégradation
  visible et acceptable.
- **Le plancher n'est pas décoratif** : 32 px, c'est ce qu'il faut pour une ligne de texte et un
  bouton. Un palier qui descendrait plus bas casserait le contenu de la bande, pas seulement son
  allure. Le noter dans le commentaire du palier, sinon quelqu'un l'abaissera.
- **Le rendu ne change pas.** Toute différence de hauteur constatée après le changement est un défaut,
  pas une amélioration : les valeurs sont reprises à l'identique.
- **Les deux états se vérifient**, pas seulement l'actif : le mode veille a sa propre valeur et se
  rate facilement, parce qu'aucune page ne l'affiche par défaut.
- **Les trois langues.** La gouttière porte du texte traduit, et l'arabe s'écrit de droite à gauche.
  Vérifier que la bande garde sa hauteur dans les trois locales — la hauteur ne devrait pas dépendre
  du sens de lecture, et c'est justement pour ça qu'il faut le constater plutôt que le supposer.
- **La règle CI `layouts-no-raw-spacing` ne couvre pas ce fichier** — elle vise les layouts, et
  `Gutter.vue` est un composant. L'entorse corrigée ici n'était donc bloquée par aucune gate : elle
  ne se verra pas partir au vert d'un script, seulement à la lecture.

## Documentation impactée

- **[design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)** — la ligne de
  `sizing.css` dans le tableau des fichiers annonce « Échelle typographique, rythme d'espacement,
  largeurs de gabarit, palier `xwide` » : une quatrième famille s'y ajoute. Le tableau *Consommer un
  token* gagne sa ligne. Et l'interdit n°3, qui est la raison d'être de cette carte, mérite d'y voir
  citée la bande comme précédent — c'est le premier cas où une entorse assumée est reprise parce
  qu'un second espace est arrivé.
  S'y ajoute une **sous-section propre à cette famille**, qui n'était pas anticipée au cadrage : elle
  est la seule du fichier consommée par la variable et non par un utilitaire nommé, alors que la page
  ouvre sur la règle inverse — « on écrit l'utilitaire, jamais la variable ». Une exception non écrite
  se lit comme une erreur, et le prochain la « corrigerait ».
- **[brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)** — la charte,
  **dans le même changement**. C'est une étape de la procédure *Étendre le système*, pas une
  politesse.
- Aucun `README.md` de dossier : ni le rôle de `assets/`, ni celui des composants de l'espace
  entreprise ne changent.

## Définition de terminé

- [x] **Plus aucune bande n'est écrite à la main** dans `webapp/`, vérifié par une recherche de
      `min-h-[clamp` sur tout l'arbre — et non par les seules valeurs de la gouttière, qui est
      précisément ce qui avait laissé passer la barre d'actions au cadrage.
- [x] Le commentaire des paliers dit pourquoi `vh` et non `vw`, pourquoi le plancher existe, et
      pourquoi la barre d'actions est plus haute que la gouttière.
- [x] Les deux bandes entreprise consomment leur palier ; leurs commentaires d'en-tête ne
      revendiquent plus une entorse qui n'existe plus.
- [x] **Le rendu est identique** : hauteur des trois bandes mesurée avant et après, à trois hauteurs
      de fenêtre encadrant le plancher et le plafond des `clamp`.
- [x] Les trois utilitaires sont **générés** dans la feuille servie — c'est le point où le mécanisme
      retenu pouvait échouer en silence.
- [x] Les bandes gardent leur hauteur dans les trois langues, arabe compris.
- [x] Le mécanisme de consommation retenu est nommé dans la carte, avec sa raison.
- [x] `design-system.md` et la charte décrivent les paliers.
- [x] Les gates passent sur les fichiers touchés — lancées à l'ouverture de la PR, en une passe :
      **vitest 308/308**, type-check des quatre espaces, prettier, et eslint sans erreur ni
      avertissement sur les trois fichiers de cette carte. La vérification des conventions sort
      **rouge**, sur deux violations qui ne viennent **pas** de cette branche : elles portent sur
      `spaces/entreprise/app/pages/recherches/nouvelle.vue`, fichier absent du diff et identique à
      `develop`, où la même vérification rend les deux mêmes violations. Correctif dû à la lane
      entreprise, nommé et non traité ici.

## Vérification

Mesures relevées dans un navigateur réel (Chromium), **connecté** en recruteur sur la stack du
worktree socle, dont `DEV_SPACE` est justement `entreprise` — l'espace qui porte les trois bandes.
La base de référence a été remesurée **sur cette même stack** en remisant le diff, pas déduite des
valeurs sources.

### Le rendu, avant et après

Trois hauteurs de fenêtre, choisies pour faire mordre chacune des trois zones du `clamp` :

| Bande | 700 px · *le plancher mord* | 1000 px · *la proportion joue* | 1300 px · *le plafond mord* |
| --- | --- | --- | --- |
| Gouttière | 32 px | 40 px | 46 px |
| Gouttière · veille | 12 px | 15 px | 18 px |
| Barre d'actions | 44 px | 55 px | 64 px |

**Ces neuf valeurs sont celles d'avant ET celles d'après**, à l'unité près, hauteur réservée comme
hauteur rendue. Le seul écart mesuré entre les deux états est celui qu'on cherchait : les trois
paliers passent de `(absent)` à déclarés sur l'élément racine.

Le mode veille n'est rendu par **aucun écran du produit** aujourd'hui. Il a été mesuré en permutant
la classe sur la gouttière réelle de la page — le DOM obtenu est celui que le composant produirait
avec sa propriété activée, la seule différence entre les deux états étant précisément cette classe.

### Les utilitaires sont générés — le point où ça pouvait casser en silence

C'était le risque du mécanisme retenu. Relevé dans la feuille servie par le serveur de dev :

```
.min-h-\(--band-gutter\)          { min-height: var(--band-gutter); }
.min-h-\(--band-gutter-standby\)  { min-height: var(--band-gutter-standby); }
.min-h-\(--band-action\)          { min-height: var(--band-action); }
```

Et, dans la même feuille, `.min-h-\(--ui-header-height\)` — la bibliothèque de composants emploie
déjà cette écriture. C'est ce constat qui a tranché contre la directive `@utility`.

### Les trois langues

À 1000 px de fenêtre, sur `/recherches` :

| Langue | Sens de lecture | Gouttière |
| --- | --- | --- |
| Français | `ltr` | 40 px |
| Anglais | `ltr` | 40 px |
| Arabe | `rtl` | 40 px |

L'arabe rend bien son texte cursif dans la bande, en sens inverse, sans effet sur la hauteur.

### Balayage final

Aucun `min-h-[clamp` ne subsiste dans `webapp/`. Les trois `h-[clamp` restants sont les barres
hautes, hors périmètre par décision.

## Livré

- `webapp/base/app/assets/css/sizing.css` (M) — trois paliers et une quatrième famille dans le
  fichier. Le commentaire porte ce que les valeurs ne disent pas : pourquoi `vh` et non `vw`
  (une bande coûte de la hauteur, pas de la largeur), pourquoi les deux bornes existent, pourquoi la
  veille réduit sans supprimer, et l'homonymie à ne pas confondre avec `--spacing-gutter`.
- `webapp/spaces/entreprise/app/components/Gutter.vue` (M) — consomme les deux paliers de gouttière.
  Son commentaire ne revendique plus une entorse disparue ; il raconte pourquoi elle est tombée.
- `webapp/spaces/entreprise/app/components/ActionBar.vue` (M) — idem pour `--band-action`.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — trois endroits, plus une sous-section
  neuve. La page ouvre sur « on écrit l'utilitaire, jamais la variable » ; cette famille est la
  **seule** à faire l'inverse, et une exception non écrite se lit comme une erreur. L'interdit n°3
  gagne son précédent : une entorse assumée porte sa date de péremption dans son propre motif.
- `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` (M) — section « Les bandes d'une coque » : les
  trois paliers, leurs rôles, et pourquoi l'écart entre eux se justifie par ce qu'ils portent.

## Reste à faire (hors périmètre)

- **La famille des barres hautes** — `clamp(48px,6vh,64px)` côté admin, `clamp(54px,6.5vh,76px)` côté
  cockpit. Écartée par décision : les deux valeurs diffèrent, et les remonter ensemble déplacerait la
  divergence au lieu de la résoudre. **Relevé au passage** : celle du cockpit est écrite **deux
  fois**, dans `layouts/cockpit.vue` et dans `layouts/recherche.vue` — la duplication est donc déjà
  là, à l'intérieur d'un seul espace.
- **Le `px-1.5` de la gouttière** — palier brut de Tailwind, que l'interdit n°3 range dans
  l'improvisation. Dans un fichier que cette carte ouvre, mais hors de son périmètre : elle traite
  une hauteur, pas un rythme horizontal.
- **`ADM-018`**, que cette carte débloque : l'espace admin consomme `--band-gutter` pour la bande de
  titre de son écran « Le vivier ». Sa carte attend, cadrée et non commencée.
