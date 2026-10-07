# TASK ENT-036 — la pile rend sa section à la page

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-08-01 — la page a repris son squelette, les trois composants gardent
  une racine unique marquée, les états de la pile retrouvent leur centre et la dette de la
  Visionneuse est soldée.
- **Type** : Frontend espace entreprise — refactor de structure, et le défaut d'affichage qu'il
  emporte.
- **Tests** : `sans objet` côté unitaire — la carte ne produit aucune fonction pure ni aucun code
  exécutable testable, elle déplace des balises. La preuve est double et exigeante :
  `bun run quality-conventions-check` vert **sans exception** pour les trois composants, et des
  captures authentifiées avant / après, en clair et en sombre, montrant le centrage corrigé.
- **Réf** : **amont `WEB-006`** (sans la gate, rien ne sort rouge et la preuve n'existe pas) ·
  `ENT-028`, qui a extrait ces composants · `ENT-027` → `ENT-028`, même chorégraphie ·
  [base/app/pages/README.md](../../webapp/base/app/pages/README.md) ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md)

## Contexte

`ENT-028` a extrait sept composants de la Visionneuse et fait tomber son template de 310 à 90 lignes.
L'extraction est juste, mais elle a emporté avec elle **le squelette du modèle de page** : la pile et
la colonne possèdent chacune leur balise de région et les classes qui décrivent la grille du parent.

```
search/Stack.vue        → <section class="search-stack min-h-0 min-w-0" :aria-label="…">
search/ControlPanel.vue → <aside …>
search/FavoriteList.vue → <section …>
```

`min-h-0 min-w-0` ne décrit pas la pile : ça décrit **sa place dans la grille de `index.vue`**. La
mise en page se retrouve donc à cheval sur deux fichiers, et personne ne l'a en entier.

**C'est ce partage qui a produit un défaut visible.** `SearchState` se centre avec `m-auto`, qui n'a
d'effet vertical que dans un conteneur flex ou grid. La `<section>` de `Stack.vue` est un bloc nu :
les quatre états — chargement, erreur, aucun critère, jeu vide — sont centrés horizontalement et
**collés en haut de l'écran**. Sur la Table, où la `<section>` est écrite dans la page et porte
`flex … flex-col`, le même composant se centre correctement. Deux écrans jumeaux, deux rendus.

`WEB-006` écrit la convention qui manquait — *la balise de région appartient à celui qui place* — et
pose la gate `components-no-page-region`, qui inscrit ces trois composants en dette. Cette carte
retire leurs trois lignes.

**La Table n'est pas concernée** : sa `<section>` est déjà écrite dans la page, et aucun des
composants qu'elle utilise n'est en défaut. Elle sert de référence, pas de cible.

## Périmètre

- **Remonter les balises de région dans `recherches/[uuid]/index.vue`** : la `<section>` de la pile
  avec son `aria-label`, l'`<aside>` de la colonne avec le sien, et **toutes** les classes de
  placement qui les accompagnent.
- **Donner à `Stack`, `ControlPanel` et `FavoriteList` une racine unique** portant leur marqueur
  kebab-case, le contrat de hauteur et la mise en page interne.
- **`ControlPanel` écrit la `<section>` de ses favoris** — c'est lui qui place `FavoriteList`.
- **Corriger le centrage des quatre états** de la pile : c'est le défaut que la nouvelle racine
  emporte mécaniquement.
- **Accessibilité de la zone défilante** : le conteneur qui défile porte son nom et sa sémantique de
  liste, et l'état de chargement s'annonce.
- **Retirer les trois lignes de dette** posées par `WEB-006` dans `except:`.

## Hors périmètre

- **`table.vue`** : déjà conforme à la convention. Elle reste par ailleurs en dette sur
  `pages-no-interactive-template`, sujet distinct, sa propre carte.
- **La hauteur de la pile sous `lg`** — voir « Aval ». Instruit, pas corrigé ici.
- **`account/CreditJournal`**, autre violation de cette lane : elle appartient à l'écran du compte,
  pas à la recherche. Sa dette reste inscrite, sa carte reste à écrire.
- **Le découpage de `useSearch`** (22 valeurs rendues pour une limite de 8), toujours en attente
  depuis `ENT-028`.
- **Tout autre finding de la relecture** : l'index de la carte visible qui survit à un changement de
  jeu, l'écriture hétérogène des trois relais d'événements, `overscroll-contain`. Aucun n'a de
  rapport avec la frontière DOM.

## Décisions de cadrage

### Le rendu a le droit de bouger, et c'était le but

`ENT-028` s'était interdit tout changement de rendu — « si l'écran bouge, c'est un défaut de cette
carte ». Cette carte prend la position inverse, pour une raison précise : **le défaut de centrage est
la conséquence directe du partage de la mise en page**. Le corriger dans une carte séparée le
couperait de sa cause et laisserait celle-ci sans preuve visible.

Conséquence : la capture avant / après ne prouve pas l'immobilité, elle **documente l'écart voulu**.
Tout autre écart entre les deux captures est un défaut de cette carte.

### La racine unique n'est pas un `<div>` d'enveloppe

Rendre la `<section>` à la page laisserait `Stack.vue` avec cinq racines conditionnelles — quatre
états plus la pile. `componentsRootMarker` dispense alors **en silence** (`roots.length !== 1`), et
`search-stack` disparaîtrait du DOM servi : plus moyen de remonter d'un bloc au fichier qui le rend.

Les trois composants gardent donc **une racine unique**, qui porte le marqueur **et** une fonction
réelle : le contrat de hauteur, et le conteneur flex sans lequel `m-auto` ne centre pas. Ce n'est pas
le `<div>` que le README interdit — celui-là n'existait que pour porter un marqueur.

### Trois composants, pas un

`FavoriteList` et `ControlPanel` sont dans le même état que `Stack` et dans les mêmes fichiers. Les
laisser en dette pour n'en corriger qu'un obligerait à rouvrir `index.vue` une seconde fois, et à
lire deux conventions dans un même écran.

### Le nom accessible suit la balise

`search.viewer.stack.title` et le nom de la colonne remontent dans la page **sans changer de valeur**
— la clé i18n est la même, seul le fichier qui l'appelle change. **Aucun fichier de langue n'est
touché**, et les trois régions de l'écran gardent leur nom : c'était l'acquis d'`ENT-028`, il ne se
perd pas dans le déménagement.

### Écart au cadrage — le conteneur défilant ne prend pas de second nom

Le périmètre annonçait que « le conteneur qui défile porte **son nom** et sa sémantique de liste ».
Il a reçu `role="list"`, et ses cartes `role="listitem"` ; il n'a **pas** reçu d'`aria-label` à lui.

La `<section>` qui l'entoure porte déjà « Profils à examiner » : un second nom sur l'enfant immédiat
fait annoncer deux fois la même chose, et une liste sans nom propre hérite de sa région. Pour la même
raison, pas de `tabindex="0"` : les cartes contiennent des boutons, la zone est déjà atteignable au
clavier, et un point de tabulation supplémentaire sur un conteneur focusable de bout en bout gêne
plus qu'il n'aide.

### L'accessibilité de la zone défilante entre, la hauteur mobile non

Deux constats de la même relecture, deux traitements. Le conteneur défilant sans nom ni sémantique de
liste se corrige dans les lignes qu'on réécrit de toute façon — c'est le même geste. La hauteur sous
`lg` engage la grille de `main` et la colonne `hidden lg:flex` : `ENT-028` l'avait déjà renvoyée en
aval sans la carter, et la mêler ici noierait la preuve de la convention.

## Règles et cas limites

- **Les quatre états survivent** — chargement, erreur, aucun critère, jeu vide — avec leurs libellés,
  leur ordre de priorité et leur `role="alert"` sur l'erreur.
- **Le centrage se prouve sur les quatre**, pas seulement sur le vide : les quatre passent par le
  même conteneur.
- **La pile reste le seul défilement de l'écran**, et `scroll-snap` continue de gouverner la lecture :
  `snap-y snap-mandatory` sur le conteneur, `snap-start` sur la card. Le déménagement ne doit toucher
  ni l'un ni l'autre.
- **`scrollTo` reste exposé** et continue de viser les enfants du conteneur défilant. Si la racine
  unique s'intercale, la référence doit rester sur **l'élément qui défile**, pas sur la nouvelle
  racine — sinon « Reprendre » vise dans le vide.
- **`useVisibleProfile` interroge le même élément** que `scrollTo` : les deux se déplacent ensemble ou
  aucun.
- **La recherche partagée reste en lecture seule** : `editable` continue de commander le retrait d'un
  critère, le geste de retenir et le retrait d'un favori.
- **Aucun geste ne change de nature** : les composants émettent, la page décide.
- **Les attributs lus par les lecteurs d'écran passent par `$t`** — `app-no-hardcoded-text` les vise
  explicitement.

## Documentation impactée

Aucune : cette carte **applique** la convention qu'`WEB-006` écrit, elle n'en fixe aucune. Les README
concernés sont justes à la date où elle démarre — s'ils ne le sont pas, c'est que `WEB-006` n'est pas
passée, et cette carte n'a pas lieu de commencer.

## Définition de terminé

- [x] `index.vue` porte la `<section>` de la pile et l'`<aside>` de la colonne, avec leurs noms
      accessibles et leurs classes de placement.
- [x] `Stack`, `ControlPanel` et `FavoriteList` ont chacun une racine unique portant leur marqueur.
- [x] Aucun des trois ne porte plus de classe décrivant la grille du parent.
- [x] Les états de la pile sont centrés — **trois des quatre capturés** : erreur, jeu vide, aucun
      critère, tous mesurés au même centre. Le chargement, transitoire, partage le même conteneur et
      les mêmes classes ; il n'a pas été capturé, et c'est dit en « Vérification ».
- [x] Le conteneur défilant porte sa sémantique de liste ; le chargement s'annonce par `aria-busy`.
      **Pas de nom propre** — voir l'écart au cadrage ci-dessus.
- [x] « Reprendre » ramène toujours sur la bonne card, et le rang suivi par la colonne reste juste.
- [x] Les trois lignes de dette sont retirées d'`except:`, et `components-no-page-region` est verte
      **sans exception pour elles**.
- [x] Captures avant / après, connecté, clair et sombre : **aucun écart autre que le centrage**.
- [x] Les gates **impactées** sont vertes : conventions, type-check des quatre espaces, lint, format.
      *(Le cadrage écrivait ici « `bun run quality-check` est vert » — critère corrigé : le dépôt
      réserve la passe complète à l'ouverture de la PR, une branche empilant N tâches pour un seul
      passage en CI. La carte demandait donc une chose que la règle interdit à ce moment.)*

## Vérification

**La gate mord sur ces trois fichiers.** Dette retirée, avant toute correction :

```
✖ components-no-page-region — 3 violation(s)
    spaces/entreprise/app/components/search/FavoriteList.vue:20 → <section> en racine de composant
    spaces/entreprise/app/components/search/ControlPanel.vue:38 → <aside> en racine de composant
    spaces/entreprise/app/components/search/Stack.vue:54 → <section> en racine de composant
```

Après correction : `✔ conventions — 9 règles, aucune violation`, **sans exception** pour la
Visionneuse. C'est la démonstration qu'`ENT-027` → `ENT-028` avait faite pour l'autre règle, refaite
ici entre deux lanes.

**Le DOM servi dit qui possède quoi**, lu dans le navigateur et non dans le code :

| Élément | Balise | Son parent |
| --- | --- | --- |
| `.search-stack` | `DIV` | `SECTION` nommée « Profils à examiner », `aria-busy` |
| `.search-control-panel` | `DIV` | `ASIDE` |
| `.search-favorite-list` | `DIV` | `SECTION` |

Les trois marqueurs ont survécu au déménagement, et la pile expose `role="list"` avec **30**
`listitem`.

**Le centrage, mesuré.** Même écran, même état, seule la colonne flex de la racine diffère :

| | Haut de l'état | Haut de la région | Verdict |
| --- | --- | --- | --- |
| Avant | 157,9 px | 157,9 px | collé en haut |
| Après | 458,9 px (h. 131) | 157,9 px (h. 733) | centre à 524,4 des deux côtés |

Les trois états capturés — erreur, jeu vide, aucun critère — tombent **tous** à `y = 458,9` pour une
hauteur de 131 : le centre est le même, seule la largeur du texte change.

**« Reprendre » vise toujours l'élément défilant** : `scrollTop` 0 → 742 et rang 1 → 2 au clic. La
chaîne page → `scrollTo` → conteneur défilant → `useVisibleProfile` → colonne est intacte.

**Écran peuplé inchangé**, connecté sur une recherche de 256 profils, en clair et en sombre : même
mise en page, mêmes chiffres (`1 / 256`, `29 à voir`), pied plein toujours collé au bord de la
colonne.

**Type-check vert** sur les quatre espaces ; lint et format verts sur les cinq fichiers.

### Deux limites de ces preuves, dites franchement

- **Le témoin « avant » est reconstitué, pas historique.** La colonne flex a été retirée
  temporairement de la racine pour reproduire l'ancienne condition — un bloc de même hauteur —, puis
  restaurée. Fidèle, mais ce n'est pas un cliché d'avant la tâche.
- **Le quatrième état, le chargement, n'a pas été capturé** : il est transitoire. Les trois autres
  passent par le même conteneur et les mêmes classes.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — la `<section>` de la
  pile et l'`<aside>` de la colonne, leurs noms accessibles, leurs classes de grille, et `aria-busy`.
- `webapp/spaces/entreprise/app/components/search/Stack.vue` **(M)** — racine unique en colonne
  flex, `role="list"` sur le conteneur défilant et `role="listitem"` sur les cartes.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — racine unique, et elle
  écrit désormais la `<section>` de ses favoris, qu'elle place.
- `webapp/spaces/entreprise/app/components/search/FavoriteList.vue` **(M)** — racine unique, contenu
  seul.
- `webapp/scripts/ci/rules.yaml` **(M)** — les trois lignes de dette de la Visionneuse retirées.

## Reste à faire (hors périmètre)

- **Une recherche jetable subsiste** dans la base du worktree entreprise
  (`019fbc0b-d876-78aa-816f-38bae87f051d`, sans critère) : elle a servi à obtenir deux des états.
  Créée par le produit, elle disparaît au prochain rechargement des fixtures.
- `bun run quality-check` complet appartient à `/creer-pr`.

## Aval

- **La hauteur de la pile sous `lg`.** En `grid-cols-1`, les lignes de `main` sont dimensionnées par
  leur contenu : `h-full` sur le conteneur défilant et `min-h-full` sur les cards n'ont plus de
  hauteur définie à quoi se rapporter, et la pile risque de déborder d'un `main` que la coque
  `h-dvh overflow-hidden` clippe — au-delà de la première card, plus rien d'atteignable. À instruire
  en capture avant de carter, avec la colonne `hidden lg:flex` que `ENT-028` avait déjà signalée.
- **`account/CreditJournal`** — dernière violation de la lane après cette carte.
- **`table.vue`** — 301 lignes de template, toujours en dette sur `pages-no-interactive-template`.
- **Le découpage de `useSearch`.**
