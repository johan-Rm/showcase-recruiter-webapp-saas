# TASK WEB-010 — chaque écran a un repère principal, et c'est le layout qui le pose

- **Créée le** : 2026-08-03.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-03 — la règle est écrite dans les deux README, les deux coques du
  socle posent leur repère par `UMain` au `min-h` neutralisé (ce qui couvre candidat et public, qui
  n'ont pas de layout à eux), et deux vérificateurs structurels tiennent l'invariant avec la dette
  d'`admin` et d'`entreprise` inscrite à leur nom.
- **Type** : Frontend socle — accessibilité, convention de dossier, et la gate qui l'outille.
- **Tests** : le **checker CI** est du code exécutable et se teste, dans
  `webapp/scripts/ci/checkers.vitest.ts`, à la suite des existants — un layout sans `<main>`, un
  layout à deux `<main>`, un layout conforme, une page qui en écrit un. C'est la seule pièce
  testable : le reste est du template et deux README. Le rendu se prouve autrement, par la liste des
  repères lue dans le DOM servi.
- **Réf** : **amont `WEB-006`**, même chorégraphie — une règle écrite, une gate qui la tient, et la
  dette des contrevenants inscrite en `except:` pour que chaque lane la solde chez elle ·
  **`ENT-040`**, dont le piège de hauteur revient ici par `UMain` ·
  [pages/README.md](../../webapp/base/app/pages/README.md), qui porte déjà « la balise de région
  appartient à celui qui place » · [layouts/README.md](../../webapp/base/app/layouts/README.md), qui
  n'a encore aucune règle propre.

## Contexte

**Trois espaces sur quatre n'ont aucun `<main>`.** Relevé le 2026-08-03 sur l'ensemble de `webapp/` :

| Où | `<main>` |
| --- | --- |
| `base/app/layouts/default.vue` | ✅ un, en balise nue |
| `base/app/layouts/auth.vue` | ❌ aucun — le `<slot/>` est dans une `<section>` |
| `spaces/admin/` | ❌ **aucun, nulle part** |
| `spaces/candidat/` | ❌ aucun |
| `spaces/public/` | ❌ aucun |
| `spaces/entreprise/` | ⚠️ trois, **écrits par les pages** — dont deux wireframes |

Un document sans `<main>` n'a pas de **repère principal** : le lecteur d'écran n'a rien vers quoi
sauter, et le « aller au contenu » n'a pas de cible. L'utilisateur qui navigue par repères doit
traverser la navigation à chaque changement de page. Sur l'espace admin, dont trente-deux écrans
passent tous par `DashboardBody`, le manque est intégral.

Ce n'est pas un oubli local : c'est une **doctrine absente**. `pages/README.md` dit à qui appartient
la balise quand on l'écrit ; rien ne dit qu'il faut l'écrire, ni où. Chaque écran neuf refait donc
l'arbitrage, et l'oublie.

### Le piège que `UMain` rapporte avec lui

Nuxt UI expose `UMain`, qui rend `<main>` — c'est la primitive attendue, `components/README.md`
posant qu'un écran neuf part de Nuxt UI. Mais son thème porte **une classe, et elle est nuisible
ici** :

```
min-h-[calc(100vh-var(--ui-header-height))]   avec --ui-header-height: 4rem par défaut
```

Dans une coque plein écran — `cockpit` est en `h-dvh overflow-hidden`, avec une topbar de 54 à 76 px
et son padding —, un `<main>` à `min-height: 100vh - 4rem` dépasse d'une quarantaine de pixels la
place qu'on lui laisse. C'est **exactement le défaut qu'`ENT-040` vient de fermer** : une coque
censée tenir dans la fenêtre qui la déborde et rend le document défilant. La classe se neutralise,
elle ne se subit pas.

## Périmètre

- **Écrire la règle dans [layouts/README.md](../../webapp/base/app/layouts/README.md)**, dont le
  chapitre *Conventions techniques* dit aujourd'hui « aucune règle propre à ce dossier » : le layout
  pose le repère principal autour de son `<slot/>`, exactement un par document, et les pages n'en
  écrivent pas.
- **Préciser [pages/README.md](../../webapp/base/app/pages/README.md)** en une phrase : `<main>` sort
  de la liste des balises qu'une page arbitre, puisqu'elle n'en écrit plus. `<section>` et `<aside>`
  ne bougent pas.
- **Migrer les deux layouts du socle** — `default.vue` passe de sa balise nue à `UMain`, `auth.vue`
  en reçoit un qu'il n'a pas. **Ils couvrent candidat et public**, qui n'ont aucun layout à eux.
- **Neutraliser le `min-h` de `UMain` à la pose**, et écrire pourquoi à côté.
- **Poser la gate**, en deux règles de fichier qui donnent ensemble l'invariant : un layout rend
  **exactement un** `<main>`, une page n'en rend **aucun**.
- **Inscrire la dette en `except:`** pour les contrevenants qui appartiennent à d'autres lanes, avec
  le commentaire de lane, comme `WEB-006` l'a fait : `spaces/admin/app/layouts/dashboard.vue`,
  `spaces/entreprise/app/layouts/cockpit.vue`, `spaces/entreprise/app/layouts/inscription.vue`, et
  les trois pages entreprise qui écrivent leur `<main>`.
- **Tester le checker** dans `checkers.vitest.ts`.

## Hors périmètre

- **L'application dans les quatre espaces.** Chaque lane retire sa ligne d'`except:` chez elle, avec
  le code et la preuve — c'est ce qu'`ENT-036` a fait pour `WEB-006`. Trois cartes filles sont
  ouvertes en aval, aucune n'est écrite ici : une carte de lane écrite depuis le socle atterrirait
  sur `develop` sans le code qu'elle décrit.
- **`skip to main`**, le lien d'évitement. Il n'a de sens qu'une fois la cible posée partout, donc
  après les cartes filles. Nommé, pas fait.
- **Les autres repères** — `<header>`, `<footer>`, `<nav>` — et le nommage des `<section>`
  existantes : sujet voisin, pas celui-ci.
- **Le reste du thème Nuxt UI.** On neutralise une classe sur un composant, on ne révise pas
  `--ui-header-height` globalement : la variable est lue par d'autres composants, et la toucher
  produirait des effets qu'aucune capture ne relierait à cette carte.
- **La divergence des coques `cockpit` / `dashboard`** (couture fluide contre 4 px fixes, ratios de
  grille 9/3 contre 2/1) relevée dans la même passe d'analyse : lane `WEB` aussi, sa propre carte.

## Décisions de cadrage

### Le layout, et pas la page

Les deux sont défendables — `pages/README.md` autorise déjà la page à placer la balise. Le layout
l'emporte pour une raison de couverture : il y a **cinq layouts et des dizaines de pages**. Posé dans
le layout, le repère existe sur tout écran, y compris ceux qu'on n'a pas encore écrits ; posé dans la
page, il manque à la première qu'on oublie — et c'est précisément ce qui s'est produit.

Conséquence acceptée : le layout ne connaît pas le contenu, donc le `<main>` n'a pas de nom
accessible propre. Il n'en a pas besoin — un document n'ayant qu'un seul repère principal, il est
désigné sans ambiguïté.

### `UMain` plutôt qu'une balise nue, mais désarmé

Choix de l'utilisateur, et il est cohérent avec le dépôt : `components/README.md` pose qu'un écran
neuf part de Nuxt UI, et l'habitude est déjà en place sur un autre projet. On garde donc la primitive
— et on coupe son `min-h`, qui est un défaut connu et mesuré, pas une préférence.

La neutralisation se pose **une fois par layout**, à l'endroit où le `<main>` est écrit. Elle n'a pas
à remonter en configuration globale : elle appartient à la décision de mise en page, comme tout ce
que porte un layout.

### Écart — le neutralisant est `min-h-0`, pas une chaîne vide

Le cadrage annonçait `:ui="{ base: '' }"`. **Ça ne neutralise rien** : `UMain` ne remplace pas son
thème par ce qu'on lui passe, il l'**ajoute** — `ui({ class: [props.ui?.base, props.class] })` — et
c'est `tailwind-merge` qui départage. Une chaîne vide n'entre en conflit avec aucune classe, donc le
`min-h` du thème survit intact.

Il faut une classe **du même groupe** pour que le tri la remplace : `:ui="{ base: 'min-h-0' }"`.
Vérifié dans le navigateur, `getComputedStyle(main).minHeight` vaut `0px` sur les deux coques.

### Écart — `auth.vue` convertit sa `<section>` au lieu d'ajouter un élément

Le cadrage disait « en reçoit un qu'il n'a pas », ce qui laissait entendre une balise de plus. La
`<section>` du panneau de formulaire **était déjà** le contenu principal de l'écran, l'autre panneau
n'étant qu'une image de marque : la convertir dit la même chose plus précisément, et laisse le DOM
inchangé — même élément, mêmes classes, même place dans la grille. Un élément de plus aurait ajouté
un niveau d'imbrication pour rien.

Effet à vérifier, et vérifié : le CSS **scopé** de la coque anime `.auth-split__pane`, et l'attribut
de portée doit continuer d'atteindre un élément désormais rendu par un composant. Il l'atteint —
Vue applique la portée du parent à la racine de l'enfant.

### Deux règles de fichier plutôt qu'une règle de document

L'invariant visé — *un document rend exactement un `<main>`* — est **inter-fichiers** : il se compose
d'un layout et d'une page, et aucun checker qui lit un fichier à la fois ne peut le vérifier
directement. Il se décompose sans perte en deux règles locales, chacune vérifiable :

- un fichier de `layouts/` contient **exactement un** `<main>` ;
- un fichier de `pages/` n'en contient **aucun**.

Leur conjonction donne l'invariant, et chaque violation pointe le fichier fautif — ce qu'une règle de
document, même faisable, ne saurait pas faire.

### La dette s'inscrit, elle ne se corrige pas ici

Poser la gate la fait mordre sur cinq fichiers de deux autres lanes. Les corriger depuis le socle
serait sortir de son périmètre, et mêlerait dans un commit du code que ni `admin` ni `entreprise`
n'ont relu. `WEB-006` a établi le geste : la dette s'écrit en `except:` avec le nom de sa lane, et
elle se solde là où elle vit. Le retrait de chaque ligne est la preuve de la carte fille.

## Règles et cas limites

- **Exactement un `<main>` par document rendu.** Deux repères principaux valent zéro : les
  technologies d'assistance n'en désignent alors plus aucun.
- **`inscription.vue` et `auth.vue` comptent comme les autres.** Un parcours d'inscription est un
  document ; ce n'est pas parce qu'il n'a pas de navigation qu'il n'a pas de contenu principal.
- **Le `<main>` du layout enveloppe le `<slot/>`, et rien d'autre** — ni la topbar, ni la sidebar, ni
  les overlays, qui ne sont pas le contenu principal.
- **Le contrat de hauteur des coques plein écran est préservé** : après la migration, la coque tient
  toujours dans la fenêtre et le document ne défile pas. C'est la mesure d'`ENT-040`, à refaire ici
  — `documentElement.scrollHeight` égal à `innerHeight`.
- **Aucun écart visuel attendu** : `<main>` n'a pas de rendu propre, et la classe neutralisée est la
  seule que `UMain` apportait.
- **Le sélecteur des règles CI ne mord pas sur les commentaires** : `<!-- <main> -->` dans une
  explication ne doit pas compter, sous peine de faux positifs sur des fichiers abondamment
  commentés — ils le sont tous ici.
- **La ligne d'`except:` porte le nom de sa lane** en commentaire, comme les quatre familles
  existantes de `rules.yaml` : sans lui, personne ne sait à qui la dette revient.

## Documentation impactée

- [`webapp/base/app/layouts/README.md`](../../webapp/base/app/layouts/README.md) — la règle, dans son
  chapitre *Conventions techniques* aujourd'hui vide.
- [`webapp/base/app/pages/README.md`](../../webapp/base/app/pages/README.md) — la précision sur
  `<main>`, dans « La balise de région appartient à celui qui place ».
- `webapp/scripts/ci/rules.yaml` — les deux règles et leurs `except:`. Il est autant de la convention
  que de l'outillage : c'est lui qui rend la règle opposable.

Rien dans `docs/showcase-recruiter-webapp-saas/` : aucun déclencheur du tableau ne s'applique — pas de surface d'API, pas
de contrat, pas de composable ni de store, et le design system n'est pas touché (`<main>` n'est ni un
token ni un composant de charte).

## Définition de terminé

- [x] `layouts/README.md` porte la règle, avec le pourquoi et le piège du `min-h`.
- [x] `pages/README.md` précise que `<main>` n'appartient plus à la page.
- [x] `default.vue` et `auth.vue` posent un `UMain` au `min-h` neutralisé autour de leur `<slot/>`.
- [x] Les deux règles CI existent, et `quality-conventions-check` est vert **avec** les lignes de
      dette et leur lane. **Six lignes et non cinq** : le cadrage comptait trois layouts et « les
      trois pages entreprise » sans les additionner.
- [x] Les cas du checker sont couverts dans `checkers.vitest.ts`, y compris le faux positif du
      `<main>` en commentaire.
- [x] **Preuve de rendu** : sur un écran de `default.vue` et deux de `auth.vue`, le DOM servi expose
      un `<main>` et un seul, et `documentElement.scrollHeight` reste égal à `innerHeight`.
- [x] Aucun écart visuel avant / après.
- [x] Les gates **impactées** sont vertes : conventions, format, tests du checker, et un type-check
      ciblé sur `checkers.ts`. Lint non joué — il tournera à l'ouverture de la PR.

## Vérification

**La gate mord, et sur les bons fichiers.** Dette retirée à la main avant toute correction, elle
sort exactement les six contrevenants attendus, avec leurs lignes :

```
✖ layouts-single-main — 3 violation(s)
    spaces/admin/app/layouts/dashboard.vue:1 → aucun <main> — le layout doit poser le repère principal
    spaces/entreprise/app/layouts/inscription.vue:1 → aucun <main> — …
    spaces/entreprise/app/layouts/cockpit.vue:1 → aucun <main> — …
✖ pages-no-main — 3 violation(s)
    spaces/entreprise/app/pages/template-le-vivier.vue:39 → <main> dans une page — le layout le porte déjà
    spaces/entreprise/app/pages/template-recherches-uuid.vue:41 → …
    spaces/entreprise/app/pages/recherches/[uuid]/index.vue:200 → …
```

Elle **épargne `default.vue` et `auth.vue`** dans le même passage : c'est la démonstration qu'elle
reconnaît `UMain` comme un repère, et pas seulement la balise nue. Dette remise :
`✔ conventions — 11 règles, aucune violation`.

**Le rendu, mesuré dans le navigateur** — Chromium, 1600 × 900, sur les écrans réels des deux
coques, avant et après le diff :

| Écran | layout | `<main>` avant | `<main>` après | `min-height` calculé | `html.scrollHeight` / `innerHeight` |
| --- | --- | --- | --- | --- | --- |
| Accueil public | `default` | 1 (balise nue) | **1** | **0px** | 900 / 900 |
| Connexion | `auth` | **0** | **1** | **0px** | 900 / 900 |
| Inscription (panneaux échangés) | `auth` | **0** | **1** | **0px** | 900 / 900 |

Le `0px` est la preuve que la neutralisation opère : sans elle, le thème imposerait
`calc(100vh - 4rem)`, soit 836 px à un `<main>` qui n'en occupe que 188 sur l'accueil public — le
document déborderait.

**L'animation de la coque `auth` survit au changement de balise.** Le CSS scopé anime
`.auth-split__pane`, désormais rendu par un composant : l'attribut de portée l'atteint toujours
(`attributScope: true`) et l'échange s'applique — `transform: matrix(1, 0, 0, 1, 795, 0)` sur la
variante `?inscription`, `none` sans elle.

**Aucun écart visuel.** Captures avant / après des trois écrans, identiques — seul le badge Nuxt
DevTools diffère, qui n'appartient pas à la page.

**Tests** : `checkers.vitest.ts` → **19 passés**, dont les cinq nouveaux cas. **Format** :
`prettier --check` sur les sept fichiers ✔.

**Le type-check ciblé a rattrapé une erreur que Vitest ne voyait pas** : `vue/compiler-sfc`
n'exporte ni `ElementNode` ni `TemplateChildNode` — ils vivent dans `@vue/compiler-core`. Les tests
passaient quand même, les types étant effacés à l'exécution. Ils sont désormais dérivés de la
signature de `parse`, sans importer une dépendance transitive.

## Livré

- `webapp/base/app/layouts/README.md` (M) — la règle « Le repère principal appartient au layout »,
  dans un chapitre qui disait « aucune règle propre à ce dossier ».
- `webapp/base/app/pages/README.md` (M) — `<main>` sort de ce qu'une page arbitre.
- `webapp/base/app/layouts/default.vue` (M) — `UMain` au `min-h` neutralisé, et le motif en tête.
- `webapp/base/app/layouts/auth.vue` (M) — la `<section>` du panneau de formulaire devient le repère.
- `webapp/scripts/ci/checkers.ts` (M) — `layoutsSingleMain` et `pagesNoMain`, sur l'AST.
- `webapp/scripts/ci/checkers.vitest.ts` (M) — cinq cas, dans les deux sens.
- `webapp/scripts/ci/rules.yaml` (M) — les deux règles et les six lignes de dette.

## Reste à faire (hors périmètre)

- **Trois cartes filles**, non écrites ici : `ADM` pour `dashboard.vue`, `ENT` pour `cockpit.vue`,
  `inscription.vue` et ses trois pages. Chacune retire ses lignes d'`except:`, avec sa preuve de
  rendu — c'est le geste qu'`ENT-036` a fait pour `WEB-006`.
- **Le lien « aller au contenu »**, qui n'a de sens qu'une fois la cible posée partout.
- **La divergence des coques `cockpit` / `dashboard`** — couture fluide contre 4 px fixes, ratios de
  grille 9/3 contre 2/1, relevés le 2026-08-03. Lane `WEB`, sa propre carte.
