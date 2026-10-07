# TASK ADM-015 — la coque admin rejoint l'échelle du socle

- **Créée le** : 2026-08-04.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-04 — la coque admin emploie l'échelle du socle (`p-element`,
  `gap-element`) et a rendu son écrasement de `--ui-radius` : les coutures passent de 4 à 9,28 px et
  **toute** l'échelle de rayons de l'espace de 6 à 8 px, boutons compris. La chaîne de hauteur
  d'`ADM-014` tient, et `design-system.md` dit désormais qu'un palier brut de Tailwind vaut une
  improvisation.
- **Type** : Frontend espace admin — alignement sur le design system, et la précision de doc qui
  évite que l'écart se recrée.
- **Tests** : `sans objet` côté unitaire — la carte ne produit **aucune fonction pure ni aucun code
  exécutable** : elle remplace quatre classes utilitaires et retire un écrasement de variable. Une
  couverture n'aurait rien à affirmer, et jsdom ne calculant pas de mise en page, un test de
  composant ne verrait ni un padding ni un rayon. La preuve est une **lecture des valeurs calculées
  dans le navigateur** — padding, gap, `border-radius` — doublée de captures sur plusieurs écrans.
- **Réf** : **amont `ADM-014`** (mergée le 2026-08-03), qui a posé `UMain` dans cette coque et dont
  la chaîne de hauteur ne doit pas casser · **aval `ENT-043`**, qui borne cockpit et fait l'autre
  moitié de l'homogénéité · [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md),
  dont l'interdit n°3 porte déjà la règle · [nuxt-ui.css](../../webapp/base/app/assets/css/nuxt-ui.css),
  qui documente le couplage `--ui-radius` → échelle de rayons · fichiers touchés :
  [admin/app/layouts/dashboard.vue](../../webapp/spaces/admin/app/layouts/dashboard.vue),
  `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`.

## Contexte

**Les deux coques des espaces authentifiés posent le même cadre plein écran de quatre façons
différentes.** Relevé le 2026-08-03 sur leurs racines :

| | cockpit (entreprise) | dashboard (admin) |
| --- | --- | --- |
| Épaisseur du cadre | `p-element` — 8→10 px, fluide | **`p-1` — 4 px, figé** |
| Écart entre panneaux | `gap-element` | **`gap-1`** |
| Rayon des panneaux | hérité du kit — 8 px | **`[--ui-radius:0.1875rem]` — 6 px** |
| Orthographe de la hauteur | `h-dvh` | `h-[100dvh]` |

Sur un même produit, deux espaces se lisent comme deux produits : l'admin colle ses panneaux à 4 px
avec des coins secs, l'entreprise les écarte du double avec des coins arrondis.

### La règle existait déjà, et elle nomme ce qui a été fait

Ce n'est pas un arbitrage à ouvrir : `design-system.md` l'a tranché, dans ses interdits.

> **Aucun `clamp()` ni `min-[…px]:` à la main** — l'échelle typographique et le rythme d'espacement
> sont **fermés**. Un besoin non couvert se règle en **ajoutant un palier dans `sizing.css`**, pas en
> improvisant dans un écran.

`p-1` et `gap-1` sont une improvisation dans un écran. Le raisonnement qui les a posés est bon — le
commentaire de la coque explique très bien qu'une **couture** n'a pas à s'écarter sur un grand écran,
là où une **gouttière** le doit —, mais il a été appliqué en **sortant** de l'échelle au lieu de
**l'étendre**, ce que la règle demande explicitement.

### Le rayon suit, et ce n'est pas un effet de bord surprise

Le socle documente le couplage dans `nuxt-ui.css` : Nuxt UI recalcule toute l'échelle de rayons de
Tailwind depuis `--ui-radius` (`--radius-lg: calc(var(--ui-radius) * 2)`), et le socle le laisse à
son défaut de 0,25 rem **précisément pour que l'échelle retombe sur celle de Tailwind**. L'admin l'a
poussé à 0,1875 rem, ce qui donne 6 px là où le kit en pose 8.

Retirer l'écrasement ramène donc les panneaux admin à la valeur du kit. Ce n'est pas un dégât
collatéral : c'est la seconde moitié de la même divergence.

## Périmètre

- **`p-1` → `p-element`** sur la racine de `dashboard.vue`.
- **`gap-1` → `gap-element`** aux **deux** endroits : la racine, et la colonne principale.
- **Retirer `[--ui-radius:0.1875rem]`** — les rayons reviennent à ceux du kit.
- **`h-[100dvh]` → `h-dvh`** : même calcul, orthographe du socle.
- **Réécrire le commentaire de neuf lignes** qui ouvre le template. Il argumente **le contraire** de
  ce qu'on écrit ; le supprimer en silence ferait rejouer le débat à la prochaine session.
- **Préciser l'interdit n°3 de `design-system.md`** : sa lettre vise les `clamp()` et les
  `min-[…px]:`, pas les paliers **bruts de Tailwind** (`p-1`, `gap-2`…) qui produisent exactement le
  même écart. Une phrase, pas une section.

## Hors périmètre

- **`cockpit.vue`** : le bornage en largeur et le retrait de `max-h-dvh` sont la carte `ENT-043`.
  Les deux cartes se complètent mais ne partagent pas un commit.
- **Les autres coques** — `inscription`, `auth`, `default` : elles ne sont pas des outils plein
  écran, et rien n'indique qu'elles divergent.
- **`max-w-shell` côté admin** : il y est déjà et ne bouge pas. C'est cockpit qui le rejoint.
- **Ajouter un palier « couture » à `sizing.css`** : c'était la troisième voie, elle n'a pas été
  retenue — voir *Décisions de cadrage*.
- **Le reste des `text-sm` du dépôt**, sujet distinct, déjà nommé par `ADM-014`.

## Décisions de cadrage

### L'échelle du socle gagne, et le palier « couture » n'est pas créé

Trois voies étaient ouvertes : admin rejoint l'échelle existante, le socle gagne un palier
`--spacing-seam` que les deux coques emploient, ou l'exception est documentée et conservée.

**La deuxième était la plus fidèle au raisonnement d'admin** — la règle dit elle-même qu'un besoin
non couvert s'ajoute à `sizing.css`. Elle n'est pas retenue : c'est la décision de l'utilisateur, et
elle a le mérite de ne créer aucun token pour un besoin qu'un seul fichier exprimait.

**Conséquence assumée, et elle est visible** : les trente-deux écrans de l'espace admin passent de
coutures serrées et coins secs à des coutures aérées et coins arrondis. Ce n'est pas un nettoyage de
code, c'est un changement d'allure — la preuve doit donc porter sur plusieurs écrans, pas seulement
sur la home.

### Le commentaire se remplace, il ne se supprime pas

Neuf lignes argumentent la couture de 4 px et le rayon qui en découle. Les effacer laisserait le
fichier muet sur une question qui s'est posée et qui se reposera. Le nouveau commentaire dit ce qui
est décidé **et** ce qui a été écarté : la coque emploie l'échelle du socle comme tout le reste, et
le besoin d'une couture plus serrée, s'il revient, passera par un palier dans `sizing.css`.

### Une précision de doc, pas une règle nouvelle

L'interdit n°3 couvre déjà le fond — l'échelle est fermée. Sa **lettre** ne cite que `clamp()` et
`min-[…px]:`, or ce qui a été écrit ici est `p-1` : un palier Tailwind parfaitement ordinaire, qui
n'a pas l'air d'une improvisation. La précision tient en une phrase et elle est ce qui empêche le
prochain espace de reforker exactement de la même façon.

## Règles et cas limites

- **La chaîne de hauteur d'`ADM-014` ne doit pas casser.** `UMain` porte
  `flex min-h-0 flex-1 flex-col` et `DashboardBody` s'y étire ; changer le padding de la racine
  change la place restante, pas la mécanique. À revérifier tout de même : le repère et le corps
  doivent rester de même hauteur, et le document ne doit pas déborder.
- **`--ui-radius` gouverne bien plus que les panneaux.** Toute l'échelle de rayons de Tailwind en
  dérive, donc **chaque `rounded-*` de l'espace** bouge, pas seulement les quatre coques de premier
  niveau — boutons, champs, cartes, menus. C'est voulu, mais la preuve doit le montrer ailleurs que
  sur un panneau.
- **La coque reste plein écran** : `h-dvh` + `overflow-hidden`, aucun défilement de page.
- **La barre latérale garde sa largeur** `clamp(220px,18vw,272px)` : elle n'est pas concernée.
- **Sous `lg`**, la barre latérale est masquée et le tiroir mobile prend le relais — le changement de
  couture ne doit pas déplacer ce basculement.
- **Aucune clé i18n**, aucun texte, aucune donnée : le diff est exclusivement de la mise en forme.

## Documentation impactée

- [`docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)
  — la précision à l'interdit n°3. Elle part dans le **même commit** que le code qui la rend vraie.

Aucun `README.md` de dossier : `base/app/layouts/README.md` porte la règle du repère principal, pas
celle de l'espacement, qui appartient au design system.

## Définition de terminé

- [x] La racine de `dashboard.vue` emploie `p-element`, `gap-element` et `h-dvh`, sans écrasement de
      `--ui-radius`.
- [x] La colonne principale emploie `gap-element`.
- [x] Le commentaire dit la décision **et** ce qui a été écarté.
- [x] **Valeurs calculées relevées dans le navigateur**, avant et après : `padding` et `gap` de la
      racine passent de 4 px à la valeur fluide, et le `border-radius` d'un panneau de 6 px à 8 px.
- [x] **Un `rounded-*` hors coque** est mesuré lui aussi — un bouton ou un champ —, pour montrer que
      l'échelle entière a bougé.
- [x] La chaîne de hauteur tient : repère et corps de même hauteur,
      `documentElement.scrollHeight` égal à `innerHeight`.
- [x] Captures avant / après sur **au moins trois écrans admin** de familles différentes, pas
      seulement la home.
- [x] `design-system.md` porte la précision sur les paliers bruts.
- [x] Les gates **impactées** sont vertes : conventions et format. **Type-check non joué** — le
      diff ne contient ni script, ni type, ni identifiant : des classes utilitaires dans un template
      et un paragraphe de Markdown. Il tournera à l'ouverture de la PR.

## Vérification

**Valeurs calculées relevées dans le navigateur**, authentifié, 1600 × 900, sur la home admin, avant
et après le diff :

| Mesure | Avant | Après |
| --- | --- | --- |
| `padding` de la racine | 4 px | **9,28 px** |
| `gap` de la racine · `gap` de la colonne | 4 px · 4 px | **9,28 px · 9,28 px** |
| `--ui-radius` lu sur la coque | `0.1875rem` | **`0.25rem`** — le défaut du socle, retrouvé |
| Rayon de la barre latérale | 6 px | **8 px** |
| Rayon d'une tuile de compteur | 6 px | **8 px** |
| **Rayon d'un bouton** | **6 px** | **8 px** |
| Hauteur du repère / du corps | 834 / 834 px | **818 / 818 px** |
| `html.scrollHeight` / `innerHeight` | 900 / 900 | **900 / 900** |

Deux relevés portent l'essentiel. **`9,28 px` est exactement la valeur du palier fluide à cette
largeur** (`clamp(0.5rem, 0.48rem + 0.1vw, 0.625rem)` → 7,68 + 1,6) : l'échelle opère, ce n'est pas
une valeur figée qui en remplace une autre. Et **le bouton bouge comme les panneaux**, ce que la
carte exigeait de montrer : l'écrasement ne rabaissait pas que les quatre coques de premier niveau,
il tenait toute l'échelle de rayons de l'espace.

**La chaîne de hauteur d'`ADM-014` tient** : repère et corps restent égaux, à 818 px au lieu de 834
— les 16 px partis dans le cadre épaissi et l'écart de la colonne. Le document ne déborde pas.

**Captures avant / après sur trois écrans** de familles différentes — home, file de revue,
utilisateurs. Le changement est net et uniforme : la coque se détache du bord de la fenêtre, l'écart
entre la barre latérale et la colonne double, les coins s'arrondissent. Aucun autre écart : même
contenu, mêmes positions relatives.

**Gates impactées** : `prettier --check` sur `dashboard.vue` ✔ · `quality-conventions-check` →
*11 règles, aucune violation* ✔.

## Livré

- `webapp/spaces/admin/app/layouts/dashboard.vue` (M) — `p-element`, `gap-element` aux deux endroits,
  `h-dvh`, et l'écrasement `[--ui-radius:0.1875rem]` retiré. Le commentaire de neuf lignes qui
  argumentait la couture de 4 px est remplacé par la décision **et** ce qui a été écarté.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — quatre lignes à l'interdit n°3 : un palier
  brut de Tailwind compte pour une improvisation au même titre qu'un `clamp()` écrit à la main.

## Reste à faire (hors périmètre)

- **`ENT-043`**, l'autre moitié de l'homogénéité : cockpit se borne à `max-w-shell` et perd son
  `max-h-dvh` mort. Cadrée, non implémentée — sa lane était occupée au moment du cadrage.
- **Le repère principal `<main>`** reste en dette sur `cockpit.vue`, `inscription.vue` et trois pages
  entreprise, dans les `except:` de `rules.yaml`. Lane `ENT`.
- **Les 145 `text-sm` restants** du dépôt, et la règle CI qui les interdirait. Lane `WEB` — la
  précision écrite ici la prépare sans la poser.

