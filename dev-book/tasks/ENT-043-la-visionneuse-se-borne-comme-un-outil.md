# TASK ENT-043 — les coques entreprise deviennent de vraies coques d'outil

- **Créée le** : 2026-08-04.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-04 — les deux coques posent leur repère principal, ce qui couvre
  **quinze écrans** dont douze n'en avaient aucun ; la Visionneuse se borne à 2560 px puis se centre ;
  deux classes mortes partent. **L'espace entreprise était le dernier à porter de la dette** sur
  `layouts-single-main` et `pages-no-main` : les deux règles n'ont désormais plus aucune exception.
- **Type** : Frontend espace entreprise — accessibilité (repère principal), alignement sur le
  gabarit « outil » du design system, et retrait d'une classe sans effet.
- **Tests** : `sans objet` côté unitaire — la carte ne produit **aucune fonction pure ni aucun code
  exécutable** : elle déplace des balises et ajoute trois classes utilitaires. jsdom ne calculant pas
  de mise en page, un test de composant ne verrait ni une largeur ni un repère. Les deux règles CI
  qu'elle solde, `layouts-single-main` et `pages-no-main`, sont **déjà testées** par `WEB-010`. La
  preuve est une **lecture du DOM servi** — comptage des repères, mesure de largeur à deux tailles
  de fenêtre — doublée de captures.
- **Réf** : **amont `WEB-010`** (mergée le 2026-08-03), qui pose la règle du repère principal et
  inscrit les cinq lignes de dette que cette carte solde · **`ADM-014`**, qui a fait le même geste
  côté admin · **`WEB-011`** (mergée le 2026-08-04), dont la règle d'espacement laisse une dette
  distincte sur `inscription.vue` · [layouts/README.md](../../webapp/base/app/layouts/README.md),
  qui porte la règle et le piège du `min-h` · [pages/README.md](../../webapp/base/app/pages/README.md) ·
  [sizing.css](../../webapp/base/app/assets/css/sizing.css), pour `--container-shell`.

## Contexte

Trois défauts, deux coques, une même session de navigateur pour les prouver.

### Douze écrans sur quinze n'ont aucun repère principal

Ni `cockpit.vue` ni `inscription.vue` ne posent de `<main>`. Sur les **15 pages** que gouverne la
coque cockpit, **12 n'ont donc aucun repère** — l'accueil, la Table, le compte, les crédits, la liste
des recherches… — et **3 en ont un par accident**, parce qu'elles l'écrivent elles-mêmes : la
Visionneuse et deux wireframes. Ces trois-là produiraient un **doublon** dès que la coque aurait le
sien, et deux repères valent zéro.

Sans repère, le lien « aller au contenu » n'a pas de cible et un lecteur d'écran retraverse la barre
haute à chaque page.

`WEB-010` a posé la règle et les deux gates ; l'espace entreprise est le **dernier** à porter de la
dette dessus — cinq lignes, que cette carte retire. `ADM-014` a fait le même geste côté admin.

### La coque de la Visionneuse ne se borne pas

`sizing.css` expose `--container-shell: 160rem` et écrit à quoi il sert — *« borne des outils pleine
largeur »*. `design-system.md` le redit : `.shell` est le gabarit **outil**, *« pleine largeur, bornée
puis centrée »*. La Visionneuse et la Table **sont** des outils pleine largeur, et ne se bornent pas :
au-delà de 2560 px, une ligne de profils traverse tout l'écran. L'espace admin, lui, se borne déjà.

### `max-h-dvh` ne sert à rien

Vérifié sur toute la chaîne des parents : `body` ne porte ni hauteur ni `flex` (`main.css`), `#__nuxt`
et la racine d'`app.vue` sont des `div` nus. Aucun ancêtre n'est un conteneur flex ou grid, donc rien
ne peut étirer cette boîte au-delà de sa `height` — et `overflow-hidden` empêche le contenu de la
pousser. `max-height: 100dvh` ne peut mordre que si la hauteur dépassait, ce qui est impossible ici.

Inoffensif, mais trompeur : ça **fait croire à une garantie qui n'existe pas**. Le jour où un ancêtre
deviendra flex, ni `h-dvh` ni `max-h-dvh` ne sauveront quoi que ce soit.

## Périmètre

- **`cockpit.vue`** — poser `<UMain>` autour du seul `<slot />`, borner par
  `mx-auto w-full max-w-shell`, retirer `max-h-dvh`, et commenter la borne.
- **`inscription.vue`** — le panneau du formulaire devient le repère principal, et sa classe morte
  `max-h-[100dvh]` part avec celle de `cockpit` (extension assumée, cf. *Décisions de cadrage*).
- **Les trois pages de la Visionneuse** — `recherches/[uuid]/index.vue`, `template-le-vivier.vue`,
  `template-recherches-uuid.vue` : leur `<main>` cède la place, en conservant classes de grille et
  nom accessible.
- **`rules.yaml`** — retirer les **cinq** lignes de dette : deux sur `layouts-single-main`, trois sur
  `pages-no-main`. Les deux règles n'auront alors plus **aucune** exception.

## Hors périmètre

- **Le `mt-4` d'`inscription.vue`**, dette laissée par `WEB-011` sur `layouts-no-raw-spacing`. Même
  fichier, même lane, mais **autre règle et autre preuve** — un espacement se mesure, un repère se
  compte. Il reste en `except:`. Le signaler plutôt que l'emporter en silence : c'est une ligne, elle
  se solde quand on décidera de la solder.
- **La divergence des coques sur la couture** : réglée par `ADM-015` côté admin, `cockpit` était déjà
  conforme.
- **Le lien « aller au contenu »**, qui devient possible une fois cette carte passée — tous les
  espaces auront leur cible. Sa propre carte, lane `WEB`.
- **Les autres pages entreprise** : elles n'écrivent pas de repère, la gate le confirme.

## Décisions de cadrage

### Écart au cadrage — le repère rejoint cette carte

Le premier cadrage écartait le repère principal, au motif que « ce n'est pas la même preuve de
rendu ». **C'était faux** : la preuve de la borne est une lecture du DOM à deux largeurs, celle du
repère un comptage de balises **dans le même DOM, sur la même page, dans la même session**. Les
séparer payait deux fois le montage complet — relance de la stack, login, préchauffage — pour deux
diffs dans le même fichier.

Ce qui l'avait fait paraître séparable, c'est que le repère n'est pas une ligne isolée : le poser
dans `cockpit` **oblige** les trois pages à retirer le leur dans le même commit, sinon chaque
document en porte deux — et deux repères valent zéro. C'est un geste à cinq fichiers, pas un ajout.

Décision prise par l'utilisateur le 2026-08-04, après que l'écart a été signalé.

### Les trois utilitaires plutôt que la classe `.shell`

`main.css` expose `.shell`, qui fait `mx-auto w-full max-w-shell`. Elle ajoute cependant
`padding-inline: var(--spacing-gutter)`, dont une coque plein écran ne veut pas : son cadre est un
`p-element` uniforme, et les deux règles se marcheraient dessus. `.shell` est le gabarit d'une page
**de contenu** bornée ; la coque est un **cadre** qui gère son propre padding. C'est déjà le choix
qu'a fait `dashboard.vue`, sans le dire — cette carte l'écrit.

### Ce que devient le `<main>` des pages

Il ne disparaît pas, il **change de balise**. Sur `recherches/[uuid]/index.vue`, il porte un
`aria-label` et la grille de l'écran : il devient une `<section>` nommée, qui reste un repère de
région et garde son nom. Sur les deux wireframes, sans nom accessible, une `<section>` anonyme
n'annoncerait rien — un `<div>` dit la vérité : c'est un conteneur de grille.

### Écart au cadrage — le relevé du contexte était inexact

Le cadrage écrivait « les deux coques n'ont aucun repère », ce qui laissait croire à trois pages
concernées. Le comptage réel est **12 écrans sans repère sur 15**, et c'est ce que la mesure a montré :
l'accueil du cockpit en rendait **zéro**. Poser le repère dans la coque ne corrige donc pas trois
pages mais quinze — le gain est plus large que la carte ne l'annonçait.

### Écart au cadrage — `max-h` retiré aussi d'`inscription.vue`

Le périmètre ne prévoyait de retirer la classe morte que dans `cockpit.vue`. `inscription.vue`
portait **exactement la même**, `max-h-[100dvh]`, morte pour exactement le même motif — aucun ancêtre
flex ou grid, `overflow-hidden` en dessous. La retirer dans le même commit coûte un mot ; la laisser
aurait rendu la carte incohérente avec elle-même sur un fichier qu'elle ouvre de toute façon.

### Écart au cadrage — `inscription.vue` n'est pas prouvée au rendu

La route `/inscription/entreprise` **redirige vers `/login?inscription`** sans magic link valide :
la coque n'est atteignable qu'au bout du tunnel, avec un jeton. Trois tentatives l'ont confirmé — ce
que je mesurais était la coque `auth`.

Son repère est donc prouvé **autrement** : par la gate `layouts-single-main`, qui passe sur ce fichier
**sans exception** et qui vérifie exactement « exactement un repère », et par la lecture du diff. Le
geste est par ailleurs identique à celui d'`auth.vue`, dont le rendu a été prouvé par `WEB-010`. Le
dire plutôt que de le laisser croire prouvé.

### La borne s'applique, elle ne s'arbitre pas

Le token existe, son intention est écrite dans `sizing.css` **et** dans `design-system.md`, et
l'espace admin l'applique déjà. Cette carte rattrape le seul espace qui ne suivait pas.

**Conséquence visible, et seulement là** : au-delà de 2560 px, l'écran se centre et l'excédent devient
marge. En dessous — donc sur la quasi-totalité du parc — **rien ne change d'un pixel**.

## Règles et cas limites

- **Un seul `<main>` par document rendu.** C'est l'invariant : la coque le pose, les pages n'en
  écrivent aucun. Les deux règles CI le tiennent, chacune sur les fichiers qu'elle peut juger.
- **Le contrat de hauteur de `cockpit` ne bouge pas.** La coque est `h-dvh` + `overflow-hidden`, la
  topbar `flex-none`, et la page attend de remplir le reste. `UMain` s'intercale : il doit **porter
  lui-même** `flex min-h-0 flex-1 flex-col`, sinon la chaîne casse et la pile de profils s'effondre.
- **Le `min-h` de thème d'`UMain` doit être neutralisé** — il suppose un header Nuxt UI de 4 rem et
  ferait déborder une coque bornée à la fenêtre. Une chaîne vide n'y suffit pas : `UMain` ajoute son
  thème au lieu de le remplacer, il faut une classe du même groupe.
- **`inscription.vue` est une coque à deux couches**, pas deux colonnes : le panneau du formulaire
  et la marque se superposent sur un fond unique. Le repère va sur le **panneau**, qui porte le
  contenu ; la marque n'est qu'une image.
- **Sous 2560 px, aucun écart de largeur.** C'est le critère le plus important : la borne ne doit se
  voir qu'au-delà. Un déplacement à 1600 px serait un défaut de cette carte.
- **La pile de la Visionneuse reste le seul défilement de l'écran** — acquis d'`ENT-040` :
  `documentElement.scrollHeight` doit rester égal à `innerHeight`.
- **Le centrage suit le sens de lecture** : `mx-auto` est symétrique, l'arabe en RTL est couvert.
- **Aucune clé i18n**, aucun texte : les noms accessibles existants sont conservés tels quels.

## Documentation impactée

Aucune. Cette carte **applique** deux règles déjà écrites — le repère principal dans
`layouts/README.md` et `pages/README.md`, le gabarit « outil » dans `design-system.md` — et un token
déjà commenté dans `sizing.css`. Elle n'en fixe aucune.

`rules.yaml` est modifié, mais pour **retirer** de la dette, pas pour écrire une règle : c'est le
geste que `WEB-010` a prévu pour chaque lane, et il fait partie du livrable.

## Définition de terminé

- [x] `cockpit.vue` pose `<UMain>` autour de son seul `<slot />`, borné par `mx-auto w-full
      max-w-shell`, sans `max-h-dvh`, et un commentaire dit pourquoi une coque plein écran se borne.
- [x] `inscription.vue` pose son repère sur le panneau du formulaire, et perd sa classe morte.
- [x] Les trois pages n'écrivent plus de `<main>`, et gardent leur grille et leur nom accessible.
- [x] `rules.yaml` ne porte **plus aucune exception** sur `layouts-single-main` ni `pages-no-main`.
- [x] **Le DOM servi expose un `<main>` et un seul** sur les écrans cockpit — l'accueil en exposait
      **zéro**. Pour `inscription`, la preuve est la gate sans exception : la route est derrière un
      magic link, cf. *Décisions de cadrage*.
- [x] **Mesure de largeur à deux tailles de fenêtre** : à 1600 px, la largeur occupée est identique
      avant et après ; à 3440 px, elle tombe à 2560 px et le contenu est centré.
- [x] `documentElement.scrollHeight` reste égal à `innerHeight`, et la pile défile toujours — le
      retrait de `max-h-dvh` n'a rien changé.
- [x] Captures avant / après aux deux largeurs : **aucun écart à 1600 px**, borne visible à 3440 px.
- [x] `quality-conventions-check` est vert, et les deux règles du repère sont **sans exception**.
- [x] Les gates **impactées** sont vertes : conventions et format. **Type-check non joué** — le diff
      ne contient ni script, ni type, ni identifiant : des balises et des classes utilitaires. Il
      tournera à l'ouverture de la PR.

## Vérification

**Le repère, compté dans le DOM servi.** Navigateur authentifié, deux largeurs, avant et après :

| Écran | Avant | Après |
| --- | --- | --- |
| Accueil du cockpit | **0 repère** | **1** |
| Visionneuse | 1, classe `grid min-h-0` — **posé par la page** | 1, classe `flex min-h-0` — **posé par la coque** |

La seconde ligne est celle qui comptait : le repère **change de propriétaire sans jamais se
dédoubler**. C'était le risque principal — deux repères valent zéro. Et les onze autres écrans
cockpit gagnent le leur par la même coque, sans être touchés.

**La borne, mesurée.** Sur la Visionneuse, largeur réelle de la coque :

| Fenêtre | Avant | Après |
| --- | --- | --- |
| 1600 px | 1600 px | **1600 px — inchangé** |
| 3440 px | **3440 px** | **2560 px**, décalée de **440 px** |

440 est exactement `(3440 − 2560) / 2` : la coque est centrée. Le `max-width` calculé vaut bien
`2560px`, et `--container-shell` résout à `160rem`. **À 1600 px rien ne bouge d'un pixel** — c'était
le critère le plus important de la carte, et la capture le confirme.

**Le contrat de hauteur tient** : `documentElement.scrollHeight` égal à `innerHeight` (900 / 900) aux
deux largeurs, avant comme après, et la pile de profils est toujours présente. Le niveau `UMain`
inséré n'a pas cassé la chaîne, et le retrait de `max-h-dvh` n'a rien changé — ce qui était la
prédiction.

**La gate, sans exception.** `✔ conventions — 12 règles, aucune violation`, avec les **cinq** lignes
de dette retirées : `layouts-single-main` et `pages-no-main` n'ont plus **aucun** `except:`.
`prettier --check` sur les six fichiers ✔.

**Ce qui n'a pas pu être prouvé au rendu** : `inscription.vue`. Sa route redirige vers
`/login?inscription` sans magic link valide — trois tentatives, chacune mesurant en réalité la coque
`auth`. Son repère est établi par la gate, qui vérifie « exactement un » sur ce fichier et passe sans
exception, et par la lecture du diff. C'est une preuve plus faible que le rendu, et elle est nommée
comme telle.

## Livré

- `webapp/spaces/entreprise/app/layouts/cockpit.vue` (M) — `UMain` autour du seul `<slot />` avec
  `flex min-h-0 flex-1 flex-col`, borne `mx-auto w-full max-w-shell`, `max-h-dvh` retiré, et deux
  commentaires qui disent pourquoi une coque plein écran se borne et pourquoi ces classes-là.
- `webapp/spaces/entreprise/app/layouts/inscription.vue` (M) — le panneau du formulaire devient le
  repère ; `max-h-[100dvh]` part avec son jumeau.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` (M) — `<main>` → `<section>`
  nommée, grille et nom accessible conservés.
- `webapp/spaces/entreprise/app/pages/template-le-vivier.vue` (M) et
  `template-recherches-uuid.vue` (M) — `<main>` → `<div>` : sans nom accessible, une `<section>`
  n'annoncerait rien de plus.
- `webapp/scripts/ci/rules.yaml` (M) — **cinq** lignes de dette retirées, et les deux blocs de
  commentaire qui les portaient.

## Reste à faire (hors périmètre)

- **Le `mt-4` d'`inscription.vue`** reste en `except:` sur `layouts-no-raw-spacing` — dette de
  `WEB-011`, autre règle, autre preuve. Même fichier, même lane : une ligne, quand on décidera.
- **Le lien « aller au contenu »** devient enfin possible : les quatre espaces ont désormais leur
  cible. Lane `WEB`, sa propre carte.
- **`inscription.vue` mériterait une preuve de rendu** le jour où un magic link de test sera
  disponible — le geste est identique à celui d'`auth.vue`, prouvé par `WEB-010`, mais ce n'est pas
  la même chose que l'avoir vu.

