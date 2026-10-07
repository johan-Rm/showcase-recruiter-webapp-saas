# TASK ENT-077 — entreprise : trois visionneuses en parallèle, le temps d'arbitrer

- **Créée le** : 2026-08-10.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-10 — l'écran de parcours existe désormais à **trois adresses** :
  `/recherches-v1/:uuid` gèle l'état d'avant `ENT-076`, `/recherches-v2/:uuid` gèle celui
  d'`ENT-076`, et `/recherches/:uuid` — la ligne vivante — **poursuit sur la v2**.
- **Type** : échafaudage de comparaison. Aucune règle métier n'est touchée ; l'écran est dupliqué à
  l'identique, deux fois, et la ligne vivante reste où `ENT-076` l'avait laissée.
- **⚠️ Temporaire, et à supprimer d'un bloc.** Les deux archives ne sont pas des écrans du produit :
  aucun lien n'y mène, elles ne sont pas nommées à l'écran, et leur suppression est décrite plus bas.
- **⚠️ Cadrée après coup.** Demandée en conversation et écrite directement ; la carte est rédigée au
  commit. Trois arbitrages ont été posés à l'humain — le point de départ de la ligne vivante,
  l'étendue archivée, la durée de vie des archives — et **le premier a été retourné** après coup :
  la ligne vivante devait repartir de la v1, elle repart finalement de la v2.
- **Tests** : `sans objet` — la carte **ne produit aucun code nouveau**. Elle copie du code existant
  sans en modifier une ligne de logique. La preuve est **le rendu des trois adresses**, qui doivent
  différer sur les points exacts qu'`ENT-076` changeait.
- **Réf** : `ENT-076` — la v2, conservée intacte à son adresse et poursuivie sur la ligne vivante.

## Contexte

`ENT-076` a déplacé la liste des critères et les deux gestes « retenir » / « écarter » dans une
colonne de droite pleine hauteur. Le résultat n'est pas ce qui était demandé, mais il ne se jette
pas : il faudra peut-être y revenir.

Plutôt que de choisir entre garder et défaire, on garde les deux états côte à côte, à deux adresses
gelées, et on continue de travailler sur la ligne vivante.

## Le piège : une route ne fige rien

C'est le seul point technique de cette carte, et il commande tout le reste.

Une page `recherches-v1/[uuid]/index.vue` recopiée telle quelle **importerait les mêmes composants
que la page d'aujourd'hui** — `SearchProfileCard`, `SearchStack`, `SearchConsole`. Trois routes, un
seul écran. Ce qui porte l'apparence, ce ne sont pas les pages, ce sont les composants : figer une
version, c'est dupliquer **tout son arbre**.

D'où le décompte réel, mesuré et non estimé — **dix composants et un composable sont exclusifs à cet
écran**, donc duplicables sans risque :

```
recherches/[uuid]/index.vue
├── Console ──► ControlPanel ──► ProgressHeader
│                           └──► MatchList            (v1 : la console porte les critères)
├── Stack ────► ProfileCard ──► Verdict ──► MatchList (v2 : la fiche porte le verdict)
├── Verdict                                           (v2 : la colonne de droite)
├── DismissedDrawer · OrderPicker · Position · Progress
└── useVisibleProfile
```

Quatre autres sont partagés avec d'autres écrans. **Trois le restent** — `SearchShellName` (aussi sur
« nouvelle recherche » et la Table), `SearchState` (cinq pages) et `Gutter` : aucun n'a bougé entre
les deux versions, les geler n'apporterait rien. Conséquence assumée : une modification future de
l'un d'eux bougera dans les trois versions à la fois.

**`ActionBar`, lui, a dû être copié** — cf. *Décisions de cadrage*.

## Périmètre

- **`pages/recherches-v1/[uuid]/index.vue`** + **`components/search-v1/`** (10 composants **+ sa
  copie d'`ActionBar`**) + **`composables/useVisibleProfileV1.ts`** — l'état de `ef15d7f`.
- **`pages/recherches-v2/[uuid]/index.vue`** + **`components/search-v2/`** (10 composants) +
  **`composables/useVisibleProfileV2.ts`** — l'état d'`ENT-076`.
- **La clé `search.viewer.panel.progress`** est rétablie dans les trois langues : le fil
  d'avancement n'existe plus que dans l'archive v1, mais il y existe, et il a besoin de son libellé.

## Hors périmètre

- **La ligne vivante `/recherches/:uuid`** — elle reste telle qu'`ENT-076` l'a laissée. Aucun de ses
  fichiers n'est touché par cette carte.
- **Les trois autres pages du dossier** — la liste des recherches, « nouvelle recherche », la Table.
  Elles restent uniques et partagées : ce n'est pas leur mise en page qu'on arbitre, et les
  dupliquer imposerait de réécrire tous les liens internes version par version.
- **La navigation.** Aucune entrée de menu, aucun lien : on atteint les archives par l'adresse.
- **La documentation de cadrage** (`dev-book/cadrage/`) : rien n'est dupliqué pour un échafaudage.

## Décisions de cadrage

### La ligne vivante poursuit sur la v2

Arbitré avec l'humain, **puis retourné**. Le premier arbitrage disait de repartir de la v1 — la ligne
vivante y a effectivement été ramenée, avant d'être remise sur la v2 à la demande. La carte garde la
trace des deux : ce qui compte pour la suite est que **les prochaines modifications s'appliquent à
l'état d'`ENT-076`**, et que la v1 ne survive que comme archive.

### On archive l'écran de parcours, pas le dossier

Arbitré avec l'humain. Copier les quatre pages triplerait le volume et obligerait à réécrire chaque
lien interne pour qu'une version ne renvoie pas dans une autre. La Table et la liste sont hors du
sujet arbitré.

### `ActionBar` a dû rejoindre l'archive v1 — la démonstration du piège

C'est le seul composant partagé qui **diverge** entre les deux versions : `ENT-076` lui a retiré la
fente qui portait le fil d'avancement. Tant que la ligne vivante était en v1, le partage tenait ;
en la remettant sur la v2, **le fil a disparu de l'archive v1** — vérifié au rendu, `fil:0` là où il
devait valoir 1.

L'archive porte donc sa propre `search-v1/ActionBar.vue`, et sa page appelle `<SearchV1ActionBar>`.
C'est la règle générale illustrée : **un composant partagé qui diverge n'est plus partageable**.

### Les archives sont autonomes

Chacune porte sa copie des composants exclusifs **et** son composable de géométrie
(`useVisibleProfileV1` / `V2`). Sans quoi une retouche de la ligne vivante modifierait l'archive
qu'elle est censée figer.

Le renommage des références internes est la partie délicate de la copie : dans `search-v1/Stack.vue`,
`<SearchProfileCard>` doit devenir `<SearchV1ProfileCard>`, sans quoi la copie appelle le composant
partagé. Fait pour les dix noms, du plus long au plus court — `SearchProgress` est un préfixe de
`SearchProgressHeader`, et l'ordre inverse aurait corrompu le second.

### Les marqueurs de page distinguent les trois

`data-page` vaut `search-viewer`, `search-viewer-v1`, `search-viewer-v2`. C'est ce qui permet de
prouver qu'on regarde la bonne. Ces trois pages sont **hors du périmètre** de la règle CI
`pages-root-marker`, dont la portée est une liste explicite de fichiers qui ne les inclut pas.

## Comment supprimer les archives, le jour venu

Cinq gestes, et rien d'autre ne les référence :

1. `pages/recherches-v1/` et `pages/recherches-v2/`
2. `components/search-v1/` et `components/search-v2/`
3. `composables/useVisibleProfileV1.ts` et `useVisibleProfileV2.ts`
4. la clé `search.viewer.panel.progress` dans les trois catalogues — plus rien ne l'emploiera
5. le paragraphe d'avertissement dans `arborescence-entreprise.md`

## Documentation impactée

- **`2.architecture/arborescence-entreprise.md`** : l'encadré d'avertissement sur les deux routes
  d'archive et la façon de les retirer, plus la Visionneuse décrite dans son état d'aujourd'hui.
- **Aucun `README.md`** : aucune convention de dossier ne change. Les archives sont des copies, elles
  n'inventent pas de règle.

> Section ajoutée par `ENT-080` : elle manquait, et le contrôle d'ouverture de PR l'a relevé.

## Définition de terminé

- [x] Les trois adresses répondent et rendent trois écrans **distincts**.
- [x] `/recherches/:uuid` est identique à l'état d'`ENT-076`.
- [x] `/recherches-v1/:uuid` est identique à l'état d'avant, **fil d'avancement compris**.
- [x] Aucune référence orpheline entre les trois arbres de composants.

## Vérification

### Les trois adresses, sondées sur ce qui les distingue

`ENT-076` changeait trois choses observables : le fil d'avancement disparaissait, les critères
quittaient la console, une colonne de droite apparaissait. C'est donc exactement ce qu'on mesure.

```
/recherches      marqueur:"search-viewer"     fil:0  critèresDansLaConsole:false  colonneDroite:true
/recherches-v1   marqueur:"search-viewer-v1"  fil:1  critèresDansLaConsole:true   colonneDroite:false
/recherches-v2   marqueur:"search-viewer-v2"  fil:0  critèresDansLaConsole:false  colonneDroite:true
```

La ligne vivante et la v2 coïncident, comme attendu ; la v1 s'oppose aux deux sur les trois points.

C'est ce même banc qui a **attrapé la régression de la barre haute** : avant la copie d'`ActionBar`,
la v1 rendait `fil:0`.

Recherche témoin : `019fd61b-…` (Product Owner · scrum · jira, 19 profils, 6 critères), session
`atlas.owner`, thème sombre.

### Les suites impactées

Le rétablissement d'une clé dans les trois catalogues appelle le test de parité.

```
$ bunx vitest run base/i18n/locales/parity.vitest.ts services/search/criteria.vitest.ts
 Test Files  2 passed (2)
      Tests  66 passed (66)
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/pages/recherches-v1/[uuid]/index.vue` | (+) l'archive d'avant `ENT-076` |
| `spaces/entreprise/app/components/search-v1/*.vue` | (+) ses 10 composants **+ `ActionBar`** |
| `spaces/entreprise/app/composables/useVisibleProfileV1.ts` | (+) sa géométrie |
| `spaces/entreprise/app/pages/recherches-v2/[uuid]/index.vue` | (+) l'archive d'`ENT-076` |
| `spaces/entreprise/app/components/search-v2/*.vue` | (+) ses 10 composants |
| `spaces/entreprise/app/composables/useVisibleProfileV2.ts` | (+) sa géométrie |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) `progress` rétablie pour l'archive v1 |
| `docs/…/2.architecture/arborescence-entreprise.md` | (M) l'encadré des archives, et la Visionneuse décrite dans son état d'aujourd'hui |

## Reste à faire (hors périmètre)

- **Arbitrer**, puis **supprimer les deux archives** — la procédure est ci-dessus.
- **Les archives ne reçoivent aucun correctif.** Les deux défauts que la v2 corrige — le profil
  courant qui basculait 211 px trop tôt, et le compteur « Favoris » perdu au passage du rendu serveur
  au navigateur — restent présents dans `search-v1/`. C'est ce qu'on attend d'une archive : elle
  montre l'état d'alors, défauts compris.
- **Toute modification d'un des trois composants encore partagés** — `SearchShellName`, `SearchState`,
  `Gutter` — **touchera les trois versions à la fois**. Si l'une doit diverger, il faudra la copier
  dans son archive, comme `ActionBar` vient de l'être.
