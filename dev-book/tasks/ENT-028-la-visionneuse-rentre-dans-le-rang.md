# TASK ENT-028 — la visionneuse rentre dans le rang

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — le gabarit de la page passe de **310 à 90 lignes**, sept
  composants portent ses sections, et la règle est verte sans exception.
- **Type** : Frontend espace entreprise — refactor de structure.
- **Tests** : `services/search/candidate.vitest.ts` — **quatre cas**. Le cadrage annonçait « sans
  objet » ; l'extraction a produit une fonction pure (`describeDecision`), donc testable, donc
  testée. La preuve principale reste double : la règle repasse au vert **sans exception**, et une
  capture avant / après montre un écran inchangé.
- **Réf** : **amont `ENT-027`** (sans elle, rien ne sort rouge et la preuve n'existe pas) ·
  [base/app/pages/README.md](../../webapp/base/app/pages/README.md) —
  « Modèle de page lisible d'un coup d'œil » ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md) ·
  [base/app/README.md](../../webapp/base/app/README.md) — « Logique métier dans les composants »,
  « Taille »

## Contexte

`recherches/[uuid]/index.vue` porte **310 lignes de template** : quatre listes, une liste de
définitions, six boutons et deux tiroirs. Le README du dossier dit qu'une page « ne contient que la
composition de ses sections » et que « le code d'un formulaire, d'une liste ou de toute brique
interactive vit dans un composant dédié, **jamais dans la page** ».

La colonne de droite à elle seule fait ~120 lignes — en-tête de recherche, rang, filet de
progression, compteurs, ce qui manque au profil, liste des favoris avec son retrait par ligne, et le
pied. C'est une brique interactive complète écrite dans une page.

**`ENT-023` a aggravé la situation** : elle a réécrit toute cette colonne *dans le template de la
page* au lieu de l'extraire, avec le README à portée de main. C'est cette carte qui répare.

Second manque, visible à l'inspection du DOM : **seul l'`<aside>` porte un nom accessible**. Le
`<main>` et la `<section>` qui porte la pile n'en ont pas — deux des trois zones principales de
l'écran ne se désignent pas.

## Périmètre

- Extraire quatre composants dans `spaces/entreprise/app/components/search/`.
- Donner un nom accessible à chaque section principale de la page.
- Déplacer les deux compteurs dérivés dans `useSearch`.
- **Retirer `index.vue` de la liste `except:`** posée par `ENT-027`.

## Hors périmètre

- **`table.vue`**, qui viole la même règle et garde sa propre carte.
- **Le découpage de `useSearch`** — voir « Aval ».
- **La colonne invisible sous `lg`** (`hidden lg:flex`) : manquement au mobile-first, signalé depuis
  `ENT-023`, toujours pas carté. Le corriger ici le noierait dans un refactor de structure.
- **Tout changement de rendu.** Si l'écran bouge, c'est un défaut de cette carte, pas une
  amélioration.

## Décisions de cadrage

### Les composants reçoivent des props et émettent des événements

Le README des pages dit que la page « établit le contexte, charge les données, résout les
composants à afficher » ; celui des composants qu'un composant « consomme des données déjà
préparées » et reste **passif**. Les deux se rejoignent : la page garde l'orchestration.

L'alternative — chaque composant appelant `useSearch(uuid)` — supprimait la tuyauterie mais rendait
les composants dépendants de la route et impossibles à rendre hors contexte.

### Sept composants — le cadrage en prévoyait quatre

| Composant | Ce qu'il porte | Lignes |
| --- | --- | --- |
| `search/ControlPanel.vue` | La colonne : elle assemble | 90 |
| `search/ProgressHeader.vue` | Rang, filet, compteurs, gestes | 126 |
| `search/FavoriteList.vue` | Ce qu'on a retenu | 63 |
| `search/Stack.vue` | La pile et ses quatre états | 95 |
| `search/CriteriaBar.vue` | Les puces de critères retirables | 45 |
| `search/RefineDrawer.vue` | Le bouton « Affiner » et son tiroir | 58 |
| `search/DismissedDrawer.vue` | La corbeille des écartés | 56 |

**Deux écarts, et tous deux dictés par une règle plutôt que par un goût.**

`RefineDrawer` n'était pas au cadrage : celui-ci avait inventorié les blocs par **zone visuelle**,
et le tiroir d'affinage n'en est pas une — c'est un bouton dans la barre haute. Or il porte le
**second `v-model`** de la page, donc l'une des deux violations. Sans lui, la page restait rouge. Le
bouton et le tiroir partent ensemble parce qu'ils sont un seul geste.

Le sous-découpage de la colonne était explicitement renvoyé à « en la voyant vivre ». Vue vivre,
elle faisait **214 lignes** — au-dessus des ~200 que le README autorise, avec sa propre explication :
« au-delà, découper — c'est le signe de plusieurs responsabilités agrégées ». Elle en portait deux,
elles sont sorties.

### Une fonction pure en est sortie, et elle est testée

`describe()` servait **deux** listes — les favoris et les écartés. La dupliquer dans les deux
composants les aurait fait diverger au premier ajustement. Elle part donc dans
`services/search/candidate.ts` sous le nom de `describeDecision`, avec ses quatre tests : c'est la
seule chose de cette carte qu'un test unitaire pouvait attraper, et la couche `services/` existe
précisément pour ça.

**Racines sémantiques, jamais de `<div>` d'enveloppe** : `<aside>` reste la racine de la colonne,
`<section>` celle de la pile. Le README l'interdit explicitement — un div ajouté pour porter un
marqueur casserait la relation flex/grid avec le parent. Chacun porte en **première classe** son
marqueur kebab-case (`search-control-panel`…), seul moyen de remonter du DOM servi au fichier.

### Les compteurs descendent dans `useSearch`

`counters` combine une source globale et un `.filter()` — ce que le README interdit **dans un
composant**. Toléré dans la page, il deviendrait une violation en descendant tel quel.

Il rejoint donc `unseenCount` et `firstUnseenIndex`, déjà présents et calculés exactement de la même
façon sur la même source. Les mettre ailleurs les séparerait de leurs jumeaux.

**Dette assumée et mesurée** : `useSearch` rend déjà **20 valeurs** là où le README en autorise 8,
et passera à 22. La limite est franchie depuis longtemps, et son découpage est un sujet à part
entière — il touche les deux écrans, dont celui que cette carte laisse de côté.

### Un `aria-label` par section, pas de titre caché

Le `<main>`, la pile et la colonne portent chacun leur nom. Des `h2` en `sr-only` construiraient un
plan de document plus riche, mais ajouteraient des titres invisibles à un écran qui en a déjà un
(`h1` en `sr-only`) — on a préféré nommer les régions.

### La ligne d'`except:` se retire ici

C'est la démonstration complète : `ENT-027` fait sortir la page rouge et l'inscrit en dette ; cette
carte l'extrait, **retire sa ligne**, et la règle repasse au vert **sans exception**. Si elle ne
repasse pas au vert, c'est que l'extraction n'est pas allée assez loin.

Conséquence assumée : cette carte **dépend** d'`ENT-027` et ne peut pas la précéder.

## Règles et cas limites

- **Les quatre états de la pile** — chargement, erreur, aucun critère, jeu vide — survivent tous à
  l'extraction, avec leurs libellés.
- **La recherche partagée reste en lecture seule** : `editable` continue de commander le retrait
  d'un critère, le geste de retenir et le retrait d'un favori.
- **Les deux tiroirs n'ont pas d'adresse** : les fermer ramène exactement où l'on en était.
- **La pile est le seul défilement** de l'écran ; aucune card ne défile en interne.
- Les `aria-label` passent par `$t` — la règle `app-no-hardcoded-text` vise explicitement les
  attributs lus par les lecteurs d'écran.
- **Aucun geste ne change de nature** : les composants **émettent**, la page décide. C'est déjà le
  contrat de `SearchProfileCard`.

## Documentation impactée

Aucune : cette carte **applique** des conventions existantes, elle n'en change aucune. Les trois
README concernés sont justes et restent tels quels.

## Définition de terminé

- [x] Les composants extraits portent chacun leur racine sémantique et leur marqueur kebab-case.
- [x] Le template d'`index.vue` ne compose plus que des sections — **310 → 90 lignes**.
- [x] `<main>`, la pile et la colonne portent chacun un nom accessible, traduit.
- [x] `seenCount` et `revealedCount` vivent dans `useSearch`.
- [x] La ligne `index.vue` est retirée d'`except:`, et `pages-no-interactive-template` est verte.
- [x] Capture avant / après : **l'écran n'a pas bougé**, dans les deux thèmes.
- [x] Aucun composant ne dépasse ~200 lignes — le plus gros en fait 126.
- [x] `bun run quality-check` est vert.

## Vérification

**La bascule est complète.** `ENT-027` avait inscrit cette page en dette ; sa ligne est retirée, et
`pages-no-interactive-template` sort verte **sans exception pour elle**. C'est la démonstration que
la gate mord vraiment — si l'extraction avait été incomplète, la règle l'aurait dit.

**La gate a d'ailleurs attrapé une erreur en cours de route** : `DismissedDrawer` portait son
marqueur sur la liste interne, alors que sa racine unique est le tiroir lui-même.

```
✖ components-root-marker — 1 violation(s)
    DismissedDrawer.vue:28 → <USlideover> sans class statique — attendu "search-dismissed-drawer"
```

C'était exactement son travail, et personne ne l'aurait vu à l'œil.

**Chaîne complète** : lint 0 erreur, format, type-check vert sur les quatre espaces, conventions
8 règles sans violation, **9 fichiers de test, 66 tests**.

**Rendu inchangé** — captures réelles avant / après, connecté, sur la recherche de 1 076 profils,
en clair et en sombre : même mise en page, mêmes chiffres (`13 vus`, `1 / 1076`, `Reprendre · 17 à
voir`), mêmes mots-dièse. Le seul écart d'octets vient du badge des devtools Nuxt.

## Livré

- `webapp/services/search/candidate.ts` **(+)** et son `.vitest.ts` **(+)** — `describeDecision`.
- `webapp/spaces/entreprise/app/components/search/` **(+)** — `ControlPanel`, `ProgressHeader`,
  `FavoriteList`, `Stack`, `CriteriaBar`, `RefineDrawer`, `DismissedDrawer`.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — ne compose plus que
  ses sections.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` **(M)** — `seenCount`, `revealedCount`.
- `webapp/scripts/ci/rules.yaml` **(M)** — la ligne de dette retirée.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — le nom accessible de la pile.

## Aval

- **Le découpage de `useSearch`** — 22 valeurs rendues pour une limite de 8, quatre responsabilités
  agrégées (état du jeu, avancement, gestes, sélection). Touche les deux écrans.
- **`table.vue`**, même violation, 301 lignes de template.
- **La colonne sous `lg`** — mobile-first, toujours pas cartée.
