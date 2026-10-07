# TASK ADM-014 — l'espace admin reçoit son repère principal, et sa home rejoint l'échelle du socle

- **Créée le** : 2026-08-03.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-03 — l'espace admin expose enfin un repère principal, posé par sa
  coque et vérifié dans le DOM servi (0 → 1), sa dette est sortie de `rules.yaml`, et la page
  d'accueil rejoint l'échelle typographique du socle. **Le quatrième geste annoncé — le squelette de
  chargement — a été retiré : le défaut qu'il visait n'existe pas.**
- **Type** : Frontend espace admin — accessibilité (repère principal) et alignement sur l'échelle
  typographique du socle.
- **Tests** : `sans objet` côté unitaire — la carte ne produit **aucune fonction pure ni aucun code
  exécutable** : elle remplace des classes utilitaires et déplace des balises. Une couverture
  n'aurait rien à affirmer. La règle CI `layouts-single-main`, elle, est déjà testée par `WEB-010`.
  La preuve est une **lecture du DOM servi** — la liste des repères — doublée de captures avant et
  après.
- **Réf** : **amont `WEB-010`** (mergée le 2026-08-03), qui pose la règle du repère principal et
  inscrit la dette de cette lane · [layouts/README.md](../../webapp/base/app/layouts/README.md),
  qui porte la règle et le piège du `min-h` · [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md),
  qui énumère l'échelle typographique · fichiers touchés :
  [admin/app/pages/index.vue](../../webapp/spaces/admin/app/pages/index.vue),
  [admin/app/layouts/dashboard.vue](../../webapp/spaces/admin/app/layouts/dashboard.vue),
  [scripts/ci/rules.yaml](../../webapp/scripts/ci/rules.yaml).

## Contexte

Un défaut d'accessibilité qui vaut pour tout l'espace, et deux scories sur la page d'accueil. Ils
entrent ensemble parce qu'ils se prouvent d'un même passage au navigateur et se corrigent en deux
fichiers.

> **Un quatrième point a été retiré au cours de l'implémentation** — le squelette de chargement.
> Il reposait sur un défaut qui n'existe pas : voir *Décisions de cadrage*.

### L'espace n'a aucun repère principal

Aucun `<main>` dans tout `spaces/admin/` — sur ses trente-deux écrans, qui passent tous par la même
coque. Sans repère principal, le lien « aller au contenu » n'a pas de cible, et l'utilisateur qui
navigue au lecteur d'écran retraverse la barre latérale à chaque page.

`WEB-010` a posé la règle et la gate `layouts-single-main` ; `dashboard.vue` y est inscrit en dette,
à charge pour cette lane de la solder — c'est le geste qu'`ENT-036` a fait pour `WEB-006`.

### Deux scories, sur le trajet

- **Six `text-sm`** ([index.vue](../../webapp/spaces/admin/app/pages/index.vue), lignes 27, 34, 39,
  44, 58, 63) là où le socle expose `text-small`. `text-sm` est le palier Tailwind, figé à
  0,875 rem ; `text-small` est le palier du kit, fluide de 0,875 à 0,9375 rem.
  `design-system.md` énumère l'échelle officielle et `text-sm` n'y figure pas.
- **`text-text` manque sur trois des quatre valeurs de compteur** : la première tuile le porte
  (ligne 28), les trois autres non — même famille de tuiles, deux écritures.
- **Une enveloppe inerte** ([index.vue:51](../../webapp/spaces/admin/app/pages/index.vue#L51)) :
  `<div class="flex flex-col gap-block">` n'entoure que deux enfants **mutuellement exclusifs**
  (`v-if` / `v-else-if`). La colonne flex n'aura jamais plus d'un élément, et la zone C qui la
  contient est déjà un bloc.

## Périmètre

- **`dashboard.vue` pose le repère principal** autour de son seul `<slot />`, par `UMain` au `min-h`
  neutralisé, conformément à `layouts/README.md`.
- **Retirer la ligne de dette** `spaces/admin/app/layouts/dashboard.vue # admin` de l'`except:` de
  `layouts-single-main`, dans `rules.yaml`.
- **Les six `text-sm` de la page passent à `text-small`**, et les trois valeurs de compteur
  reçoivent le `text-text` qui leur manque.
- **Retirer l'enveloppe inerte**, en conservant le rendu.

## Hors périmètre

- **Les 145 autres `text-sm` du dépôt** — 48 ailleurs dans admin, 60 en entreprise, 28 dans le
  socle, 9 en public. On corrige l'écran qu'on touche : c'est la doctrine déjà écrite dans
  [components/README.md](../../webapp/base/app/components/README.md) pour la migration Nuxt UI,
  « pas de migration en masse : elle toucherait les quatre espaces d'un coup sans rien apporter à
  l'utilisateur ». Une règle CI qui interdirait `text-sm` relève de la lane `WEB`, et n'est pas
  écrite ici.
- **Les autres écrans admin** : ils reçoivent le repère par la coque, donc gratuitement, mais leurs
  propres `text-sm` et scories ne sont pas instruits ici.
- **La divergence des deux coques** — couture fluide de `cockpit` contre 4 px fixes de `dashboard`,
  rayons, ratios de grille 9/3 contre 2/1. Arbitrage de socle, lane `WEB`, sa propre carte.
- **Le lien « aller au contenu »**, qui n'a de sens qu'une fois la cible posée dans les quatre
  espaces. Il reste une ligne de dette côté entreprise après cette carte.
- **La colonne de droite de la Visionneuse sous `lg`**, lane `ENT`.

## Décisions de cadrage

### Écart au cadrage — le squelette de chargement sort du périmètre, le défaut n'existe pas

Le cadrage annonçait un quatrième geste : quatre tuiles en squelette pour que « la rangée de
compteurs garde sa place », le finding décrivant un écran qui **saute** quand la donnée arrive.
**Trois mesures l'ont réfuté**, dans cet ordre :

1. **Les positions ne bougent pas.** Titre, rangée de compteurs et bloc vivier relevés en début et en
   fin de chargement : `Δ 0 px` sur les trois.
2. **La donnée est déjà dans le HTML servi.** Le registre de bootstrap déclare `loadDashboard` en
   **critique** sur cette route, donc bloquant au rendu serveur : le HTML arrive avec les valeurs
   réelles des quatre compteurs et le bloc vivier. Au chargement d'une page, il n'y a **pas** d'état
   d'attente à couvrir.
3. **Un rideau plein écran existe déjà.** [AppBootShell](../../webapp/base/app/components/AppBootShell.vue)
   est un overlay `fixed inset-0 z-100`, présent dès le HTML SSR, qui couvre tout le boot puis
   s'efface en fondu. Il porte `role="status"`, `aria-busy` et `aria-live="polite"`. Un squelette de
   tuiles serait rendu **derrière lui** — invisible — et redirait moins bien ce qu'il annonce déjà.

Ce que le finding décrivait — « le bloc du vivier occupe sa place puis saute vers le bas » — supposait
que la zone C ait du contenu pendant le chargement. Elle n'en a pas : son bloc est conditionné par le
même `v-else-if="dashboard"`. Rien ne pouvait se déplacer.

**Reste un cas non prouvé**, et il est écrit ici plutôt qu'implémenté à l'aveugle : en navigation
**client** vers l'accueil depuis une autre page admin, le tableau de bord n'est pas en store et le
rideau, réservé au boot initial, ne se rejoue pas. La page rendrait alors son seul en-tête le temps
de l'appel. Deux tentatives de pilotage n'ont pas réussi à atteindre cet état — le rideau interceptait
le clic. Tant qu'il n'est pas observé, il ne justifie pas de code : le nommer suffit.

### Six occurrences et pas cent cinquante-et-une

Le finding initial décrivait une dérive propre à l'espace admin, en opposant sa page à celle de
l'entreprise. **C'est faux** : `text-sm` est employé 151 fois dans le dépôt, dont 60 en entreprise —
autant qu'ailleurs. La dette est générale, pas locale.

Corriger les six de cette page reste juste, pour la raison qui gouverne déjà la migration Nuxt UI :
on aligne l'écran qu'on ouvre, et la dette cesse d'y grandir. Corriger les 151 dans une carte d'un
espace serait un balayage transverse déguisé, et corriger les 54 d'admin noierait les deux vrais
défauts de cette carte dans un diff de neuf fichiers.

### Le repère et les deux scories dans une carte

Le repère manquant est un défaut d'espace ; les scories sont locales à une page. Ils entrent ensemble
parce que **la même session de navigateur les prouve** — on lit la liste des repères et l'allure de
l'écran dans le même DOM — et parce que les scories vivent dans un fichier que la vérification du
repère fait ouvrir de toute façon.

## Règles et cas limites

- **Le contrat de hauteur de la coque ne bouge pas.** `dashboard.vue` est en `h-[100dvh]` et
  `overflow-hidden`, avec une chaîne flex serrée : la colonne principale est
  `flex min-w-0 flex-1 flex-col gap-1` et `DashboardBody` s'y étire par `flex-1 min-h-0`. Insérer
  `UMain` ajoute un niveau, qui doit **porter lui-même** `flex min-h-0 flex-1 flex-col` — sinon le
  corps cesse de s'étirer et les zones défilantes s'effondrent.
- **Le `min-h` de thème d'`UMain` doit être neutralisé** par `min-h-0`, comme dans `WEB-010`. Sans
  ça, `min-h-[calc(100vh-4rem)]` dans une coque `h-[100dvh]` la fait déborder — et une chaîne vide
  n'y suffit pas, `UMain` ajoutant son thème au lieu de le remplacer.
- **Le repère entoure le seul `<slot />`** — ni la barre latérale, ni la barre haute, ni le tiroir
  mobile : ce ne sont pas le contenu principal.
- **Un seul `<main>` dans le document rendu.** Les pages admin n'en écrivent aucun aujourd'hui, la
  règle `pages-no-main` le garantit ; le vérifier tout de même dans le DOM servi.
- **Aucune clé i18n nouvelle**, aucun texte ajouté.
- **La tuile actionnable reste la seule actionnable** : « entreprises à revoir » garde son lien et
  son `ring-accent`, les trois autres restent en lecture.
- **Un seul écart visuel, et il est voulu.** Les six `text-sm` deviennent fluides — l'écart
  maximal est de 1 px au-delà de 1440 px de large — et l'ajout de `text-text` ne change rien là où
  la couleur héritée était déjà celle-là. Tout autre écart est un défaut de cette carte.
- **`rules.yaml` est partagé par les quatre lanes** : n'y retirer que **la ligne admin**, et se
  réaligner sur `develop` avant de committer.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`, et aucun `README.md` : cette carte **applique** des règles écrites
ailleurs — le repère principal par `layouts/README.md`, l'échelle typographique par
`design-system.md` — elle n'en fixe aucune.

`webapp/scripts/ci/rules.yaml` est modifié, mais pour **retirer** une dette, pas pour écrire une
règle. C'est le geste que `WEB-010` a prévu pour chaque lane, et il fait partie du livrable.

## Définition de terminé

- [x] Le DOM servi expose **un `<main>` et un seul** sur un écran admin — il en expose **zéro**
      aujourd'hui.
- [x] `documentElement.scrollHeight` reste égal à `innerHeight` : la coque plein écran n'a pas
      débordé, et `DashboardBody` s'étire toujours.
- [x] `quality-conventions-check` est verte **sans la ligne de dette admin** dans `except:`.
- [x] Les six `text-sm` de la page ont disparu, et les quatre valeurs de compteur s'écrivent
      identiquement.
- [x] L'enveloppe inerte est retirée, le rendu inchangé.
- [x] Captures avant / après : **aucun écart autre que** l'agrandissement des étiquettes dû au
      palier fluide, et le décalage de quelques pixels qu'il entraîne sous elles.
- [x] Les gates **impactées** sont vertes : conventions, format, type-check de l'espace admin.

## Vérification

**Le repère existe, et la coque n'a pas bougé.** Navigateur authentifié, 1600 × 900, accueil admin,
mêmes relevés avant et après le diff :

| Mesure | Avant | Après |
| --- | --- | --- |
| `document.querySelectorAll('main').length` | **0** | **1** |
| classes du repère | — | `flex min-h-0 flex-1 flex-col` |
| `min-height` calculé du repère | — | **0px** — le thème d'`UMain` est bien neutralisé |
| hauteur du repère / de `DashboardBody` | — / 834 px | **834 / 834 px** |
| `html.scrollHeight` / `innerHeight` | 900 / 900 | **900 / 900** |

Les deux dernières lignes sont l'essentiel du risque écrit au cadrage : le repère occupe **exactement**
la hauteur que `DashboardBody` occupait, et la coque plein écran ne déborde pas. La chaîne flex tient.

**La typographie est passée au palier du kit**, mesurée et non lue dans le code : la taille réelle
d'une étiquette de compteur passe de **14 px à 15 px** à cette largeur — `text-sm` est figé à
0,875 rem, `text-small` monte à 0,9375 rem au-delà de 1440 px.

**Les quatre valeurs de compteur s'écrivent identiquement** : une seule couleur distincte relevée sur
les quatre, avant comme après. L'ajout de `text-text` aux trois qui ne l'avaient pas est donc
**cosmétiquement neutre** — la couleur héritée était déjà celle-là. Il aligne l'écriture, pas le
rendu.

**La gate mord sans exception admin** : `✔ conventions — 11 règles, aucune violation`, la ligne
`spaces/admin/app/layouts/dashboard.vue # admin` retirée d'`except:`. `prettier --check` sur les
trois fichiers ✔.

**Captures avant / après** : identiques, à un détail près et il est voulu — les étiquettes gagnant
1 px, la rangée de compteurs grandit de deux à trois pixels et le bloc « Talent pool » descend
d'autant (313 → 315 px). C'est l'effet du palier fluide, pas un défaut.

## Livré

- `webapp/spaces/admin/app/layouts/dashboard.vue` (M) — `UMain` autour du seul `<slot />`, portant
  `flex min-h-0 flex-1 flex-col`. Ces classes ne sont pas décoratives : sans elles, le niveau
  supplémentaire casse la chaîne de hauteur et les zones défilantes s'effondrent.
- `webapp/spaces/admin/app/pages/index.vue` (M) — six `text-sm` → `text-small`, `text-text` sur les
  trois valeurs qui ne l'avaient pas, enveloppe inerte retirée.
- `webapp/scripts/ci/rules.yaml` (M) — la ligne de dette admin sort de `layouts-single-main`.

## Reste à faire (hors périmètre)

- **La dette entreprise** dans `layouts-single-main` : `cockpit.vue` et `inscription.vue`, plus les
  trois pages qui écrivent leur propre `<main>` dans `pages-no-main`. Lane `ENT`.
- **Le lien « aller au contenu »**, désormais possible sur admin mais qui n'a de sens qu'une fois la
  cible posée partout.
- **Le cas non prouvé** décrit en *Décisions de cadrage* : la navigation client vers l'accueil, où le
  rideau de boot ne se rejoue pas. À observer avant d'y toucher.
- **Les 145 autres `text-sm` du dépôt**, et la règle CI qui les interdirait — lane `WEB`.

