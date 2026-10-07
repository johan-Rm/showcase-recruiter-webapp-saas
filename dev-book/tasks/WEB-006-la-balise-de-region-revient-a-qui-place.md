# TASK WEB-006 — la balise de région revient à qui place

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — la convention est écrite là où on la lit, une neuvième règle
  la fait mordre, et six violations sont inscrites en dette avec leur propriétaire.
- **Type** : Socle webapp — convention de dossier et gate CI.
- **Tests** : `webapp/scripts/ci/checkers.vitest.ts` **(+)**, colocalisé comme `regions.vitest.ts`,
  sur des appâts en littéraux — **onze cas** : les trois balises de région refusées, les trois
  qualifiantes épargnées, le fragment dispensé, la `<section>` interne acceptée, la ligne rapportée,
  et **deux cas pour `components-root-marker`**, dont la lecture de racine a été extraite ici. C'est
  **le cœur de la carte** — une règle dont aucun test n'exige la détection est une règle dont
  personne ne saura qu'elle a cessé de mordre ([webapp/README.md](../../webapp/README.md)).
- **Réf** : **aval `ENT-036`** (la mise en conformité de la Visionneuse, que cette carte doit rendre
  nécessaire) · `ENT-027` → `ENT-028`, même chorégraphie règle → dette → retrait ·
  [base/app/pages/README.md](../../webapp/base/app/pages/README.md) — « Modèle de page lisible d'un
  coup d'œil » · [base/app/components/README.md](../../webapp/base/app/components/README.md) —
  « Marqueur de composant sur la racine » ·
  [responsibility-boundaries.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md) ·
  [webapp/README.md](../../webapp/README.md) — « Toute nouvelle règle doit être prouvée par une
  violation volontaire »

## Contexte

Personne ne dit **qui écrit la balise de région**, ni **qui porte les classes de placement**.

[responsibility-boundaries.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md)
fixe les frontières de **code** — qui récupère la donnée, qui la transforme, qui garde l'état — et
s'arrête là. Le **DOM** n'y figure pas. Les deux README concernés ne comblent pas le trou : celui des
pages dit qu'un template « ne contient que la composition de ses sections », phrase qui se lit dans
les deux sens ; celui des composants dit quelle balise choisir pour une racine, jamais laquelle
s'interdire.

**Les deux écrans jumeaux de la recherche ont tranché à l'opposé**, chacun avec de bonnes raisons :

- `recherches/[uuid]/table.vue` écrit sa `<section>` **dans la page**, et quatre lignes de
  commentaire justifient qu'elle est posée « ICI, sur une balise écrite dans ce fichier, et non sur
  le composant de table » ;
- `search/Stack.vue`, extrait par `ENT-028`, porte **sa propre** `<section>` — parce que
  `components-root-marker` exige une racine unique marquée et que le README interdit d'ajouter un
  `<div>` d'enveloppe pour l'avoir. La carte l'écrit noir sur blanc : « `<aside>` reste la racine de
  la colonne, `<section>` celle de la pile ».

Le raisonnement d'`ENT-028` est **local et correct** : il répond à « quelle balise pour ma racine ? ».
Il ne pouvait pas répondre à « à qui appartient la section du modèle de page ? » — cette question
n'est posée nulle part.

### Le flou ne coûte pas qu'une incohérence, il produit des bugs

`Stack.vue` porte en racine `min-h-0 min-w-0` : des classes qui ne décrivent pas le composant mais
**sa participation à la grille du parent**. Le composant légifère sur une grille qu'il ne voit pas,
et la mise en page se retrouve à cheval sur deux fichiers — donc personne ne l'a en entier.

Résultat observable : `SearchState` se centre avec `m-auto`, qui n'a d'effet vertical que dans un
conteneur flex ou grid. Sur la Table, le parent est `flex … flex-col` et le centrage marche ; dans
`Stack.vue` la `<section>` est un bloc nu, et **les quatre états de la pile sont collés en haut**.
Aucune gate ne le voit, et le README qui aurait pu l'empêcher n'existe pas.

## Périmètre

- **Écrire la convention** dans [base/app/pages/README.md](../../webapp/base/app/pages/README.md),
  avec renvoi depuis
  [base/app/components/README.md](../../webapp/base/app/components/README.md).
- **Ajouter la ligne manquante** au tableau des frontières de
  [responsibility-boundaries.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md)
  — colonne « Ne fait **pas** » de `app/components/`.
- **Poser la 9ᵉ règle** `components-no-page-region` : entrée dans `scripts/ci/rules.yaml`, checker
  structurel dans `scripts/ci/checkers.ts`, et son test.
- **Inscrire les six violations existantes** en dette `except:`, chacune avec son espace
  propriétaire.
- **Corriger le compte de règles** dans [webapp/README.md](../../webapp/README.md) : huit → neuf.

## Hors périmètre

- **Corriger le moindre écran.** Les six violations entrent en dette ; leur retrait appartient à
  la lane qui possède le fichier. `ENT-036` en retire trois.
- **`base/app/components/auth/Showcase.vue`**, pourtant sur cette lane : le corriger demande de
  toucher la page de connexion, donc un écran. Il entre en dette comme les autres — `ENT-027` avait
  fait exactement ce choix pour la violation de sa propre lane.
- **La seconde règle, sur les classes de placement** (`flex-1`, `min-h-0`, `col-span-*`, `self-*`).
  Elle vise le producteur direct du bug, mais `h-full` est parfois légitime en racine et sa liste
  demande un cadrage à elle seule. Remonter la balise de région emporte mécaniquement ces classes
  avec elle dans les cas connus — la règle n'a donc pas de prise à démontrer aujourd'hui.
- **Toute modification du comportement de `components-root-marker`.** Sa lecture de racine a bien été
  extraite — voir l'écart au cadrage ci-dessous —, mais ce qu'elle accepte et refuse n'a pas bougé
  d'un pouce.

## Décisions de cadrage

### Le critère n'est pas la balise, c'est le rôle

Un recensement des racines de composants donne **onze** balises de structure, et l'interdiction en
bloc serait fausse :

| Racine | Composants | Verdict |
| --- | --- | --- |
| `<section>` | `search/Stack`, `search/FavoriteList`, `account/CreditJournal`, `admin/FillRateSection` | région de modèle de page → fautif |
| `<aside>` | `search/ControlPanel`, `auth/Showcase` | région de modèle de page → fautif |
| `<header>` | `layout/AppHeader`, `admin/AdminTopbar`, `search/ProgressHeader` | en-tête **de leur propre contenu** → légitime |
| `<nav>` | `account/Nav`, `review/StatusFilter` | navigation → légitime |

`<section>`, `<aside>` et `<main>` **découpent un document** : les employer, c'est se déclarer partie
du plan de la page — et `<section>` nommée devient même un landmark ARIA. `<header>`, `<footer>` et
`<nav>` **qualifient un contenu** : ils gardent leur sens à l'intérieur de n'importe quel bloc.

La règle ne vise donc que les trois premières, en **racine** de composant.

### La formulation retenue : celui qui place, possède

> **La balise de région appartient à celui qui place, pas à celui qui est placé.**
> Une page, un layout ou un composant composeur écrit la `<section>` / `<aside>` / `<main>`, son nom
> accessible et **toutes** les classes qui décrivent la place dans sa grille. Le composant placé
> possède son contenu : sa racine est l'élément sémantiquement juste de **ce qu'il rend**, et il se
> dimensionne chez lui sans supposer la grille du parent.

« Celui qui place » plutôt que « la page » : `search/FavoriteList` n'est pas placée par une page mais
par `search/ControlPanel`. La formulation par la page aurait laissé la sous-composition sans règle,
et c'est un tiers des cas.

### La racine unique survit, et ce n'est pas un `<div>` d'enveloppe

Un composant qui rend sa `<section>` peut se retrouver à racines multiples — `Stack.vue` exposerait
ses cinq branches conditionnelles. `componentsRootMarker` dispense alors **en silence** (`roots.length
!== 1`), et la traçabilité DOM → fichier, qui est toute la raison d'être de la règle, disparaît sans
que rien ne le signale.

La convention demande donc de **garder une racine unique**, qui porte le marqueur, le contrat de
hauteur et la mise en page interne. Ce n'est pas le `<div>` que le README interdit : celui-là
n'existait que pour porter un marqueur, celui-ci a une fonction.

### Une règle, pas deux

La règle sur les classes de placement attraperait le producteur direct du bug de centrage. Elle est
écartée pour aujourd'hui : chaque règle coûte une violation volontaire **et** un test, et sur les six
cas connus, remonter la balise emporte les classes avec elle. KISS — on ajoute la seconde le jour où
un cas résiste à la première.

### Écart au cadrage — la lecture de racine est devenue commune

Le cadrage annonçait `components-root-marker` intouchée. À l'écriture, les deux vérificateurs se sont
retrouvés à poser **la même** question — quelle est la racine unique, et y a-t-il quelque chose à
juger — avec deux copies de la réponse. Deux définitions divergentes de « la racine » se seraient
contredites sur les mêmes fichiers, et rien ne l'aurait signalé.

La lecture part donc dans un `singleRootOf` commun. **Le comportement du marqueur est inchangé** :
mêmes fragments dispensés, même racine `<template>` écartée.

Conséquence assumée, et c'est elle qui justifie l'écart : ce refactor touche le corps d'une règle qui
**n'avait aucun test**, et une régression y serait **invisible** — un `singleRootOf` cassé rendrait
vert. Deux cas la couvrent désormais. Ne pas les écrire aurait été appliquer la doctrine du dépôt à
la règle neuve en l'oubliant sur celle qu'on venait de déplacer.

### La dette, mécanisme déjà éprouvé

`except:` porte déjà quatre lignes posées par `ENT-027`, chacune avec son espace propriétaire, et
`ENT-028` en a retiré une. Le mécanisme est compté et décroissant. Les six violations y entrent avec
la même discipline, et la carte n'en corrige aucune : sa preuve est que la règle **mord**, pas que le
dépôt soit propre.

## Règles et cas limites

- **Racine seulement.** Le checker lit la racine du template, comme `componentsRootMarker`. Une
  `<section>` **interne** à un composant est légitime : elle découpe le contenu que le composant
  possède (`ProfileCard` en porte deux).
- **Les fragments sont dispensés**, comme pour le marqueur — il n'y a pas de racine à juger. Deux
  règles qui traitent la racine différemment se contrediraient.
- **`<main>` est inclus** bien qu'aucun composant ne l'emploie aujourd'hui : c'est la balise de région
  la plus forte, l'omettre serait un oubli, pas une décision.
- **Les layouts ne sont pas dans le périmètre** de la règle (`scope` : `components/**`) : un layout
  **place**, il a le droit d'écrire ses régions.
- **Le message de la règle cite le README**, comme les huit autres, et nomme la balise trouvée.
- **La violation volontaire est jouée avant l'inscription de la dette** : la sortie qui liste les six
  violations réelles est la preuve que la règle voit, et elle est reportée dans la carte.

## Documentation impactée

- [webapp/base/app/pages/README.md](../../webapp/base/app/pages/README.md) — la convention, dans
  « Conventions techniques ».
- [webapp/base/app/components/README.md](../../webapp/base/app/components/README.md) — le renvoi,
  accroché à « Marqueur de composant sur la racine » qui a produit le malentendu.
- [docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md)
  — la ligne « Ne fait pas » de `app/components/`. La doc fixe la frontière, le README porte la
  convention : pas de recopie.
- [webapp/README.md](../../webapp/README.md) — « Huit règles actives » devient neuf.

## Définition de terminé

- [x] La convention est écrite dans le README des pages, avec son renvoi depuis celui des composants.
- [x] `responsibility-boundaries.md` porte la frontière DOM dans son tableau.
- [x] `components-no-page-region` existe dans `rules.yaml` et cite sa section de README.
- [x] Le checker refuse `<section>` / `<aside>` / `<main>` en racine, épargne `<header>` / `<nav>` /
      `<footer>`, et dispense les fragments.
- [x] `checkers.vitest.ts` exige ces quatre comportements sur des fichiers appâts.
- [x] **Violation volontaire jouée et reportée** : la sortie brute listant les six violations réelles
      figure dans « Vérification ».
- [x] Les six lignes de dette sont dans `except:`, chacune avec son espace propriétaire.
- [x] `webapp/README.md` annonce neuf règles.
- [x] `bun run quality-conventions-check` est vert avec la dette inscrite.

## Vérification

**La règle mord, et c'est le seul résultat qui compte.** Dette retirée le temps d'un run, le
vérificateur sort les **six** violations attendues — et aucune autre :

```
✖ components-no-page-region — 6 violation(s)
    base/app/components/auth/Showcase.vue:7 → <aside> en racine de composant
    spaces/admin/app/components/AdminFillRateSection.vue:16 → <section> en racine de composant
    spaces/entreprise/app/components/account/CreditJournal.vue:20 → <section> en racine de composant
    spaces/entreprise/app/components/search/FavoriteList.vue:20 → <section> en racine de composant
    spaces/entreprise/app/components/search/ControlPanel.vue:38 → <aside> en racine de composant
    spaces/entreprise/app/components/search/Stack.vue:54 → <section> en racine de composant

6 violation(s) sur 9 règles.
```

**Aucun faux positif** : les cinq composants à racine `<header>` ou `<nav>` — `layout/AppHeader`,
`admin/AdminTopbar`, `search/ProgressHeader`, `account/Nav`, `review/StatusFilter` — sont épargnés.
C'est ce que le recensement prévoyait, et c'est ce qui rendait l'interdiction en bloc fausse.

Dette inscrite, le vérificateur repasse au vert :

```
✔ conventions — 9 règles, aucune violation
```

**Les tests exigent réellement la détection.** 18 tests passent sur `scripts/ci/`. Mutation de
contrôle — `singleRootOf` forcé à rendre `null`, la panne silencieuse type, celle qui rendrait tout
vert : **5 cas tombent**, dont les deux du marqueur. Sans eux, le refactor serait passé inaperçu.

```
❯ scripts/ci/checkers.vitest.ts (11 tests | 5 failed)
```

Lint et format verts sur les fichiers touchés.

## Livré

- `webapp/base/app/pages/README.md` **(M)** — la convention « La balise de région appartient à celui
  qui place » : ce que possède celui qui place, ce que possède le composant placé, et pourquoi la
  racine unique survit.
- `webapp/base/app/components/README.md` **(M)** — le renvoi, accroché au paragraphe « racine
  sémantiquement juste » qui avait produit le malentendu.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/responsibility-boundaries.md` **(M)** — la frontière DOM
  dans la colonne « Ne fait pas » de `app/components/`.
- `webapp/scripts/ci/checkers.ts` **(M)** — `singleRootOf` partagé, et `componentsNoPageRegion`.
- `webapp/scripts/ci/checkers.vitest.ts` **(+)** — onze cas.
- `webapp/scripts/ci/rules.yaml` **(M)** — la 9ᵉ règle et ses six lignes de dette.
- `webapp/README.md` **(M)** — huit règles actives deviennent neuf.

## Reste à faire (hors périmètre)

`bun run quality-check` complet — type-check des quatre espaces et suite `vitest` entière — n'a pas
tourné : il appartient à l'ouverture de la PR, pas au commit. Les gates jouées ici sont celles que le
diff touche.

## Aval

- **`ENT-036`** — retire les trois lignes de dette de la Visionneuse. Sans cette carte, elle n'a rien
  à retirer et aucune preuve à produire.
- **`account/CreditJournal`** (entreprise), **`admin/FillRateSection`** (admin) et
  **`auth/Showcase`** (socle) — une carte par lane propriétaire, non écrites ici.
- **La règle sur les classes de placement** — à cadrer si un cas résiste à celle-ci.
