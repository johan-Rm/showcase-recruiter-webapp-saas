# TASK ADM-017 — l'État de la base se découpe et rejoint l'échelle du socle

- **Créée le** : 2026-08-06.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-06 — la page ne compose plus que son plan, ses vingt-sept chiffres
  s'écrivent en français, ses colonnes montent à six sur grand écran, ses palmarès occupent la zone
  secondaire que la coque réservait depuis le début, et une erreur de chargement se voit enfin. Un
  écart au cadrage : le composant de section est devenu un composant de **grille**, parce qu'une
  règle CI interdit à un composant d'avoir `<section>` pour racine — cf. *Décisions de cadrage*.
- **Type** : refactor — découpage en composants et alignement sur le design system, avec deux
  corrections de défaut au passage (format des nombres, message d'erreur sans couleur).
- **Tests** : unitaires (vitest) sur le **code pur** que la carte produit ou déplace — le formatage
  des nombres selon la langue courante, et `vivierRatioDenominator`, qui existe déjà dans
  `utils/vivierMetrics.ts` et n'a **aucun test** alors qu'il calcule un dénominateur lu dans un
  objet imbriqué. Le reste — colonnes, sémantique, zone secondaire — ne se teste pas en unitaire :
  jsdom ne calcule aucune mise en page. La preuve est la **lecture des valeurs calculées dans le
  navigateur** aux quatre largeurs (390, 768, 1280, 1920), doublée de captures, comme `ADM-014` et
  `ADM-015` l'ont fait.
- **Réf** : **`WEB-015` — parallèle, non bloquante** (la bulle d'aide qui sort de l'écran ; la page
  continue d'appeler le même composant et héritera de sa réparation) · **amont `ADM-015`**, qui a
  posé l'échelle du socle sur la coque et précisé l'interdit n°3 · **aval, non écrites** : le
  rebranchement du rythme des deux tableaux admin, et la traduction des libellés techniques ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) § *Ce qu'on n'écrit pas
  dans un composant* · [pages/README.md](../../webapp/base/app/pages/README.md) § *Modèle de page
  lisible d'un coup d'œil* · [sizing.css](../../webapp/base/app/assets/css/sizing.css) ·
  [table.vue](../../webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue) lignes 627-649,
  le précédent d'adoption d'un composant Nuxt UI · fichiers touchés :
  [admin/app/pages/vivier.vue](../../webapp/spaces/admin/app/pages/vivier.vue),
  [admin/app/components/FillRateSection.vue](../../webapp/spaces/admin/app/components/FillRateSection.vue),
  `webapp/base/i18n/locales/{fr,en,ar}.yaml`.

## Contexte

Un administrateur ouvre **« État de la base »** — la page nommée `/vivier` dans le code, atteinte
par la barre latérale, groupe *Supervision*. Elle lui présente 27 chiffres en cinq sections, puis
trois palmarès. Quatre choses lui nuisent, et elles ont été **mesurées le 2026-08-06** sur la stack
du worktree admin, base à 2 561 candidats.

### Les nombres sont écrits en anglais

L'écran affiche **« 2,561 »** et **« 14,943 »**. En français on écrit « 2 561 » et « 14 943 ».
`vivier.vue` appelle `value.toLocaleString()` **sans lui passer de langue** : le navigateur retombe
alors sur sa propre langue, qui n'est pas celle de l'application. Visible à l'œil nu sur **tous** les
chiffres de la page.

### Une erreur de chargement ne se voit pas

La page écrit `class="text-danger"`. Ce jeton **n'existe pas** : `main.css` ne déclare que
`--color-alert`, et il n'y a nulle part de `--color-danger`. La classe est donc **inerte** — si le
chargement échoue, le message s'affiche dans la couleur du texte courant, sans rien qui signale une
erreur. Ce n'était qu'une incohérence supposée ; c'est un défaut réel. `referentiels.vue` porte le
même, hors périmètre ici.

### Au-delà de 1 280 px, la page cesse de s'adapter

Les grilles sont figées à `grid-cols-2 md:grid-cols-4`. Mesuré :

| Largeur de fenêtre | Colonnes | Largeur d'une carte |
| --- | --- | --- |
| 390 px | 2 | 183 px |
| 768 px | 4 | 181 px |
| 1 280 px | 4 | 249 px |
| 1 920 px | 4 | **398 px** |

Passé 768 px le nombre de colonnes ne bouge plus : les cartes **grossissent** au lieu de se
multiplier, jusqu'à 398 px pour afficher un nombre à quatre chiffres. Et les trois palmarès du bas
n'attendent `lg:` (1 024 px) pour passer sur trois colonnes, si bien qu'une tablette de 768 px les
empile sur une seule alors que la largeur est là.

### La page est écrite en un seul bloc

[pages/README.md](../../webapp/base/app/pages/README.md) est explicite : « Le `<template>` d'une page
ne contient que la composition de ses sections. Le code d'un formulaire, d'**une liste** ou de toute
**brique interactive** vit dans un composant dédié, jamais dans la page. » `vivier.vue` écrit
tout à l'intérieur — les cartes de chiffres, les palmarès, les appels à la bulle d'aide. Et la coque
admin réserve une **zone secondaire** à droite que cette page laisse vide.

### Enfin, l'écran emploie des paliers bruts de Tailwind

L'interdit n°3 du design system les nomme depuis `ADM-015` : « **Les paliers bruts de Tailwind
comptent pour de l'improvisation** : `p-1`, `gap-2`, `text-sm` sont figés là où les nôtres sont
fluides. » Relevé :

| Fichier | Ligne | Écrit | Jeton du socle |
| --- | --- | --- | --- |
| `vivier.vue` | 58 | `text-sm`, `gap-1` | `text-small`, `gap-element` |
| `vivier.vue` | 64, 79, 84 | `text-sm` ×3 | `text-small` |
| `FillRateSection.vue` | 22, 24, 28 | `gap-1`, `text-sm` ×2 | `gap-element`, `text-small` |

`text-small` est employé **94 fois** dans le dépôt : c'est un remplacement à l'identique, pas une
invention.

## Périmètre

- **Découper la page en trois composants**, dans `spaces/admin/app/components/` :
  - la **carte de chiffre** — libellé, bulle d'aide, valeur, ratio. C'est elle qui porte le format
    des nombres et la sémantique : **un seul endroit à corriger pour 27 chiffres** ;
  - la **grille de chiffres** — la grille seule ; le titre et la balise de section restent à la page,
    cf. *Décisions de cadrage* ;
  - la **liste de répartition** — un axe (secteurs, métiers ou langues).

  La page ne garde que son en-tête et l'ordre des blocs.
- **Les huit paliers bruts** de `vivier.vue` et `FillRateSection.vue` passent aux jetons du socle.
- **`xwide:grid-cols-6`** sur les grilles de chiffres — le palier grand écran existe déjà
  (`--breakpoint-xwide: 120rem`, employé 51 fois) et remplace les `min-[1920px]:`.
- **Les trois palmarès passent dans la zone secondaire** de la coque, via le slot `#aside` de
  `DashboardBody`. Sous 1 024 px la coque les redescend seule, en une colonne, sans media query à
  écrire.
- **Le format des nombres reçoit la langue courante.**
- **L'erreur passe à `UAlert`**, dont le rythme est rebranché sur les jetons du socle selon le
  précédent de l'espace entreprise.
- **Les cartes de chiffres deviennent des listes de définitions** (`<dl>` / `<dt>` / `<dd>`), comme
  la page d'accueil admin et la fiche candidat le font déjà pour un couple terme + valeur.
- **Un palmarès vide affiche une ligne sobre** sous son titre, avec sa clé de traduction — comme
  `/candidats` et `/utilisateurs` le font (« Aucun candidat. », « Aucun utilisateur. »).

## Hors périmètre

- **La bulle d'aide qui sort de l'écran** — c'est `WEB-015`, sur la lane socle. La page continue
  d'appeler le même composant et hérite de sa réparation ; rien ici n'en dépend.
- **Les libellés techniques** — `CERTAIN`, `AMBIGUOUS`, `ABSENT` et les noms de métriques émis par
  le serveur, affichés tels quels. Voir *Décisions de cadrage*.
- **Le rythme des deux tableaux admin** — `candidate-table` et `admin-users-table` portent une
  classe marqueur **sans aucune règle CSS**, donc tournent sur les espacements par défaut de Nuxt UI
  pendant que le tableau entreprise tourne sur l'échelle du projet. Même défaut de famille, autres
  écrans : carte séparée, à ouvrir avec la pagination de `/candidats` et `/utilisateurs`.
- **Un palier de largeur de carte dans `sizing.css`** — il permettrait une grille qui se remplit
  toute seule et supprimerait les rangées trouées. `sizing.css` est dans le socle : ce serait une
  troisième carte, sur une troisième branche. Voir *Décisions de cadrage*.
- **Le squelette de chargement** — écarté ; le « Chargement… » actuel reste.
- **`text-danger` dans `referentiels.vue`** — même jeton inexistant, autre écran.
- **La pagination et la recherche du vivier** — bloc A de l'audit du 2026-08-06, cartes distinctes.

## Décisions de cadrage

### Conformité **et** structure, en une seule passe

Trois voies étaient ouvertes : la conformité seule (huit classes à changer), la structure seule, ou
les deux. Les deux ont été retenues, et pour une raison simple : **restructurer sans corriger
reviendrait à réécrire les lignes fautives sans les corriger.** Les paliers bruts vivent précisément
dans les blocs qu'on extrait en composants. Les séparer, c'est passer deux fois au même endroit.

### On s'en tient à `xwide:`, aucun palier n'est créé

La grille qui se remplit toute seule demande une **largeur minimale de carte**, donc un jeton qui
n'existe pas. Le design system dit quoi faire dans ce cas — « un besoin non couvert se règle en
ajoutant un palier dans `sizing.css` » — mais `sizing.css` est dans le socle partagé, ce qui sort
cette carte de sa lane.

**Ce qui est retenu** : `xwide:grid-cols-6`, qui n'emploie que des jetons existants et règle le
gâchis des grands écrans (6 colonnes d'environ 262 px au lieu de 4 de 398 px).

**Ce qui est assumé** : les **rangées trouées en fin de section** restent. Les sections comptent 3,
7, 4, 8 et 5 chiffres sur 4 colonnes, ce qui laisse cinq emplacements vides — trois d'affilée sur
« Référentiels ». C'est le prix de ne créer aucun jeton, et c'est visible.

### Un seul composant de bibliothèque, et son rythme est rebranché

`UProgress` sur les ratios, `UCollapsible` pour replier les sections et `USkeleton` pour le
chargement ont tous été **écartés** : le premier alourdit chaque carte, le deuxième cache de
l'information à un opérateur qui vient justement tout voir, le troisième répare un confort et non un
défaut. Seul **`UAlert`** entre, parce qu'il corrige un défaut réel.

Et il entre selon le précédent maison : `nuxt-ui.css` ne rebranche que **les couleurs et le rayon**,
rien sur l'espacement ni la typographie. Adopter un composant sans plus fait donc rentrer ses
espacements figés par la fenêtre. L'espace entreprise a rencontré exactement ce cas sur son tableau
et l'a résolu — `table.vue` lignes 627-649 rebranche le rembourrage des cellules sur
`--spacing-cell-y` / `--spacing-cell-x`, et le commentaire dit lui-même que la bibliothèque « laisse
cohabiter `p-4` avec `px-cell-x` ». C'est ce modèle qu'on suit.

### Les libellés techniques sortent, et ce n'est pas un oubli

`CERTAIN`, `AMBIGUOUS` et `ABSENT` s'affichent tels quels, et les noms de métriques viennent du
serveur en français. Les traduire suppose de décider **qui est maître du libellé** — le serveur qui
l'émet, ou le front qui l'affiche. Or `utils/vivierMetrics.ts` accroche ses définitions et ses
ratios **sur ces chaînes littérales**, accents et tirets cadratins compris : déplacer la maîtrise
ferait disparaître les bulles **en silence**, sans erreur. Ça mérite son propre cadrage, croisé avec
la lane API.

### ÉCART AU CADRAGE — le composant de section est devenu un composant de grille

Le cadrage annonçait un composant « section de chiffres — un titre et sa grille ». **Il ne pouvait
pas exister** : la règle CI `components-no-page-region` interdit à un composant d'avoir `<section>`
pour racine, parce que cette balise découpe le document et appartient à **celui qui place**. Le seul
autre chemin aurait été de s'ajouter à la liste d'exceptions du fichier de règles, c'est-à-dire
d'ouvrir une dette pour contourner une règle qu'on venait d'invoquer.

**Ce qui a été fait** : la page garde ses `<section>` et ses `<h2>` — le plan du document est à elle
—, et le composant ne porte que la grille. Il s'appelle donc `MetricGrid` et non `MetricSection`. Le
découpage reste à trois composants et la page ne compose plus que le plan et l'ordre, ce que le
cadrage visait ; seule la frontière entre la page et le composant a bougé d'un cran.

### La zone secondaire plutôt qu'un seuil abaissé

Deux voies pour les palmarès : les laisser en bas en abaissant leur seuil de `lg:` à `md:`, ou les
déplacer dans la zone secondaire de la coque. La seconde est retenue : elle donne son sens à la page
— les chiffres à gauche, les palmarès à droite, chaque zone défilant pour elle-même sur ordinateur —
et elle emploie une zone que la coque réserve depuis le début et que cette page n'a jamais utilisée.

## Règles et cas limites

- **Le vivier alimente deux écrans.** La même réponse serveur nourrit cette page **et** le « Taux de
  remplissage » en haut de `/candidats`, via `FillRateSection`. Toucher au composant partagé impose
  de vérifier les deux écrans, pas seulement celui qu'on refait.
- **Aucun changement du contrat serveur** — donc **pas de `make dev-openapi`**. Si l'implémentation
  se surprend à vouloir modifier la réponse, elle sort du périmètre et remonte.
- **La chaîne de hauteur de la coque ne doit pas casser.** `DashboardBody` bascule en deux colonnes
  dès qu'un `#aside` existe (`lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]`) et rend chaque zone
  défilante pour elle-même. Cette page n'avait jamais emprunté ce chemin : à vérifier au-dessus
  **et** au-dessous de 1 024 px.
- **Ordre de lecture sur mobile** : sous 1 024 px la coque empile la zone principale puis la zone
  secondaire. Les palmarès restent donc après les chiffres, comme aujourd'hui — pas de régression
  d'ordre, mais à confirmer à la capture.
- **Le chargement reste lourd** : le loader du vivier est marqué `eager: false` parce que l'API
  agrège 29 métriques dont des parcours de tables entières. La carte ne change rien à ça, et ne doit
  pas rendre ce chargement bloquant sur d'autres routes.
- **La bulle d'aide se dégrade proprement** : un libellé sans entrée au registre n'a ni bulle ni
  ratio, jamais d'erreur. Le découpage en composants doit préserver ce comportement.
- **Trois langues** : toute clé nouvelle (la ligne « aucune donnée ») part dans `fr`, `en` **et**
  `ar`. Le dépôt a déjà 25 clés qui manquent en anglais et en arabe ; ne pas en ajouter une 26ᵉ.
- **Le marqueur de composant** : chaque composant extrait porte une classe marqueur sur sa racine
  unique, règle CI `components-root-marker`.
- **La balise de région appartient à celui qui place** : la page possède ses `<section>` et son
  `<aside>` et **toutes** les classes qui décrivent leur place dans la grille ; le composant placé
  possède son contenu. Ne pas écrire de `<main>` — il appartient au layout, règle CI `pages-no-main`.

## Documentation impactée

**Aucune.** Cette carte **applique** des règles déjà écrites — `pages/README.md` pour le modèle de
page, `components/README.md` pour le marqueur et la racine unique, `design-system.md` pour l'échelle
fermée — elle n'en change aucune. `arborescence-admin.md` décrit `/vivier` en une ligne qui reste
vraie après la reprise.

Si l'implémentation fait émerger une règle nouvelle — par exemple « comment on adopte un composant
Nuxt UI en rebranchant son rythme », qui n'est aujourd'hui écrit nulle part et ne vit que dans le
code de l'espace entreprise — elle se signale, elle ne s'écrit pas ici sans décision.

## Définition de terminé

- [x] La page ne contient plus que son en-tête et l'ordre de ses blocs ; les trois composants sont
      extraits, chacun avec sa racine unique et son marqueur.
- [x] Aucun palier brut de Tailwind ne subsiste dans `vivier.vue` ni dans `FillRateSection.vue` —
      vérifié par relecture du diff, pas supposé.
- [x] Les chiffres s'affichent au format français (« 2 561 », pas « 2,561 »), constaté à la capture.
- [x] Les grilles de chiffres passent à 6 colonnes au-delà de 1 920 px, largeur de carte mesurée.
- [x] Les trois palmarès occupent la zone secondaire au-dessus de 1 024 px et redescendent sous ce
      seuil, les deux états capturés.
- [x] Un palmarès vide affiche sa ligne « aucune donnée », clé présente dans les trois langues.
- [x] Une erreur de chargement s'affiche en `UAlert`, dont le rythme est rebranché sur les jetons du
      socle ; l'état est provoqué pour être vu, pas déduit.
- [x] Chaque chiffre est un couple `<dt>` / `<dd>` dans une liste de définitions.
- [x] Le « Taux de remplissage » de `/candidats` est vérifié **aussi** — il lit la même donnée.
- [x] Tests vitest verts sur le formatage des nombres et sur `vivierRatioDenominator`.
- [x] Captures aux quatre largeurs (390, 768, 1 280, 1 920), dans les deux thèmes.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Toutes les mesures ci-dessous sont **relevées dans le navigateur**, authentifié, sur la stack du
worktree admin (2 561 candidats), aux quatre largeurs et dans les deux thèmes.

### Les nombres, vérifiés au codepoint et pas à l'œil

| Mesure | Avant | Après |
| --- | --- | --- |
| « Profils actifs » | `2,561` | **`2 561`** — espace fine insécable, le groupement français |
| « Postes du parcours » | `14,943` | **`14 943`** |
| Dénominateur d'un ratio | `/2561` | **`/2 561`** — formaté lui aussi |
| Chiffres portant une virgule anglaise | 27 sur 27 | **0 sur 27**, aux quatre largeurs |

Le dénominateur **paraît collé** sur une capture : l'espace fine est très étroite au corps
`text-small`. Le relevé des points de code lève le doute — il est bien là.

### Les colonnes, et le gâchis des grands écrans

| Largeur | Avant | Après |
| --- | --- | --- |
| 390 px | 2 col × 183 px | 2 col × 183 px |
| 768 px | 4 col × 181 px | 4 col × 181 px |
| 1 280 px | 4 col × 249 px | 4 col × **161 px** |
| 1 920 px | **4 col × 398 px** | **6 col × 170 px** |

Le rétrécissement à 1 280 px est **attendu et voulu** : la zone secondaire prend 230 px que la zone
principale n'a plus. `scrollWidth` égale la largeur de fenêtre aux quatre paliers — aucun
débordement horizontal.

### La sémantique, les palmarès, les états

- **5 grilles rendues en `<dl>`, 27 `<dt>`, 27 `<dd>`** : chaque libellé est lié à son chiffre.
- **3 palmarès dans la zone secondaire**, large de 230 px sur un portable et 272 px sur un grand
  écran ; sous 1 024 px la coque les redescend seule, en une colonne.
- **Palmarès vides** — provoqués en servant un rapport aux listes vidées, sans toucher au code : les
  trois affichent « Aucune donnée sur cet axe. »
- **Erreur** — provoquée en forçant une réponse 500 : l'alerte s'affiche, titre et description, en
  `rgb(242, 85, 88)` — `#f25558`, la couleur `--alert` du socle.

### Le rythme de l'alerte, rebranché et démontré

C'est le point que la carte demandait de prouver, valeurs calculées à 1 280 px :

| | Nuxt UI par défaut | Relevé après rebranchement |
| --- | --- | --- |
| Rembourrage | `p-4` → 16 px, figé | **18,24 px** — `--spacing-block`, fluide |
| Écart | `gap-2.5` → 10 px, figé | **8,96 px** — `--spacing-element` |
| Titre et description | `text-sm` → 14 px, figé | **14,784 px** — `--text-small` |

Les trois valeurs sont celles des paliers du socle à cette largeur, pas celles de la bibliothèque :
la méthode empruntée au tableau de l'espace entreprise opère ici aussi.

### L'écran voisin, qui lit la même donnée

`/candidats` rend son bloc « Taux de remplissage » avec ses 10 champs, libellé à **14,784 px**
(`--text-small`, contre `text-sm` avant) et écart de carte à **8,96 px** (`--spacing-element`, contre
`gap-1` à 4 px). Rien d'autre n'a bougé sur cet écran.

### Tests et paliers

- `bun run vitest run spaces/admin/app/utils/` → **2 fichiers, 13 tests, verts en 411 ms** — dont
  **6 neufs** ; les 7 autres sont ceux de `navRoute`, que la carte ne touche pas.
- Aucun palier brut ne subsiste dans le périmètre — la seule occurrence de `p-4` restante est **dans
  un commentaire**, qui explique précisément pourquoi on ne l'emploie pas.

## Livré

- `webapp/spaces/admin/app/pages/vivier.vue` (M) — la page ne compose plus que son plan : l'en-tête,
  les `<section>` et leurs titres, l'ordre des blocs, et le partage entre zone principale et zone
  secondaire. L'erreur passe à `UAlert`, dont le rythme est rebranché en CSS local sur les jetons du
  socle, par les `data-slot` que le composant émet lui-même — pas par ses classes utilitaires, qui
  changeront de version en version.
- `webapp/spaces/admin/app/components/vivier/MetricCard.vue` (+) — un chiffre : libellé, bulle,
  valeur, ratio. La brique qui porte le **format des nombres** et le couple `<dt>` / `<dd>`.
- `webapp/spaces/admin/app/components/vivier/MetricGrid.vue` (+) — la grille d'une section, en
  `<dl>`, colonnes jusqu'à `xwide`. Elle résout les dénominateurs : c'est elle qui a le rapport
  entier sous la main.
- `webapp/spaces/admin/app/components/vivier/DistributionList.vue` (+) — le palmarès d'un axe, et sa
  ligne « aucune donnée » quand l'axe est vide.
- `webapp/spaces/admin/app/utils/vivierMetrics.ts` (M) — `formatCount`, et le type du rapport
  exporté pour que la grille le nomme sans le redéclarer.
- `webapp/spaces/admin/app/utils/vivierMetrics.vitest.ts` (+) — 6 tests. Deux méritent d'être
  nommés : celui qui vérifie qu'aucune virgule anglaise ne sort en français **sans affirmer quel
  séparateur ICU choisit** (l'affirmer rendrait le test fragile d'une version à l'autre), et celui
  qui parcourt le registre des définitions pour vérifier qu'aucun ratio ne pointe vers une
  population introuvable — une clé mal orthographiée y ferait disparaître un ratio en silence.
- `webapp/spaces/admin/app/components/FillRateSection.vue` (M) — trois paliers bruts aux jetons du
  socle.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` (M) — la clé `vivier.empty`, dans les trois langues.

## Reste à faire (hors périmètre)

- **`FillRateSection.vue` figure dans la liste des dettes** de la règle CI
  `components-no-page-region` : il a `<section>` pour racine, et la dette se retire « par une carte
  de l'espace nommé ». Cette carte a touché ses paliers, **elle n'a pas retiré sa dette** — c'est un
  autre geste, sur un composant que `/candidats` porte.
- Les trois reports déjà nommés en *Hors périmètre* tiennent : les libellés techniques, le rythme des
  deux tableaux admin, et le palier de largeur de carte qui supprimerait les rangées trouées.
