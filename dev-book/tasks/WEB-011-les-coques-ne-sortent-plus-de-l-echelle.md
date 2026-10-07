# TASK WEB-011 — une coque ne peut plus sortir de l'échelle d'espacement

- **Créée le** : 2026-08-04.
- **Session** : `6fd43f36-080f-4eea-8a26-39bcab938302`.
- **Statut** : ✅ fait le 2026-08-04 — l'échelle gagne un quatrième palier (`--spacing-pane`, 24→32 px),
  les deux coques du socle n'emploient plus aucun palier brut, et une règle CI l'interdit désormais
  dans tous les layouts. **La gate ne sera verte qu'après le merge d'`ADM-015`** : `dashboard.vue`
  porte encore sur `develop` les paliers qu'elle a retirés.
- **Type** : Socle — un palier ajouté à l'échelle, deux coques alignées, et la règle CI qui tient
  l'ensemble.
- **Tests** : le **checker CI** est du code exécutable et se teste, dans
  `webapp/scripts/ci/checkers.vitest.ts`, à la suite des existants — un layout qui porte `p-1`, un
  qui porte `gap-2`, un layout conforme, et le faux positif d'un palier cité dans un commentaire de
  template. Le reste — un palier ajouté à `sizing.css`, deux templates réécrits — n'est pas
  exécutable et se prouve par une **lecture des valeurs calculées dans le navigateur**.
- **Réf** : **amont `ADM-015`** (2026-08-04), qui a retiré le dernier palier brut d'une coque et
  écrit dans `design-system.md` qu'un palier brut vaut une improvisation · **`WEB-010`**, même
  chorégraphie (règle + gate + preuve) et même raison de passer par l'AST ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md), interdit n°3 ·
  [sizing.css](../../webapp/base/app/assets/css/sizing.css) · fichiers touchés :
  `base/app/assets/css/sizing.css`, `base/app/layouts/auth.vue`, `base/app/layouts/default.vue`,
  `scripts/ci/checkers.ts`, `scripts/ci/checkers.vitest.ts`, `scripts/ci/rules.yaml`,
  `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`.

## Contexte

**Le rythme d'un layout gouverne un espace entier.** Ce n'est pas une hypothèse : un `p-1` posé dans
`dashboard.vue` a fait diverger les trente-deux écrans de l'espace admin pendant des semaines, avec
son `--ui-radius` rabaissé, jusqu'à ce qu'`ADM-015` le corrige. Un palier brut dans un composant est
une tache locale ; dans une coque, c'est un fork.

`design-system.md` interdit déjà l'improvisation — l'échelle est **fermée**, un besoin non couvert
s'obtient en ajoutant un palier à `sizing.css` — et `ADM-015` vient de préciser que les paliers bruts
de Tailwind comptent au même titre qu'un `clamp()` écrit à la main. **Rien ne le vérifie.**

### Ce que portent les cinq coques aujourd'hui

```
base/app/layouts/auth.vue           p-2 · gap-2 · px-4 py-6 sm:px-10 sm:py-8 · py-10
base/app/layouts/default.vue        py-6
spaces/entreprise/…/inscription.vue mt-4
spaces/admin/…/dashboard.vue        p-1 · gap-1 ×2   ← retirés par ADM-015, pas encore mergée
spaces/entreprise/…/cockpit.vue     — propre
```

> ⚠️ **Le relevé de cadrage annonçait deux contrevenants et aucune exception.** Le vérificateur en a
> trouvé deux de plus : `inscription.vue`, que le grep avait manqué, et `dashboard.vue`, encore
> fautif sur `develop` faute de merge. Les deux corrections sont écrites en *Décisions de cadrage*.

Deux fichiers du socle, plus un de l'espace entreprise. Une règle bornée aux layouts naît donc avec
une seule exception — là où une règle étendue à tout `webapp/` en demanderait plus de deux cents,
c'est-à-dire une gate qui ment.

### Un trou dans l'échelle, et c'est `auth.vue` qui l'occupe

Confronté aux quatre paliers — `element` 8→10, `block` 16→22, `section` 28→48, `gutter` 16→32 —
l'écran de connexion tombe juste, sauf sur un point :

| Ce qui est écrit | Valeur | Palier |
| --- | --- | --- |
| `p-2`, `gap-2` | 8 px | `element` |
| `py-10` | 40 px | `section` |
| `px-4 sm:px-10` | 16 → 40 px | `gutter` (16→32), plafond plus bas |
| **`py-6 sm:py-8`** | **24 → 32 px** | **aucun** — `block` plafonne à 22, `section` démarre à 28 |
| `max-w-sm` | 24 rem | aucun token de largeur — déjà signalé dans le fichier |

L'échelle a donc un trou entre 22 et 28 px. Le combler est exactement ce que la règle prescrit.

## Périmètre

- **Ajouter un palier à `sizing.css`** entre `block` et `section`, autour de 24→32 px, avec le
  commentaire qui dit son rôle. Le commentaire du bloc annonce « 3 paliers + gouttière » et devient
  faux : il se met à jour.
- **Aligner `auth.vue`** : `p-2`/`gap-2` → `element`, `py-10` → `section`, `px-4 sm:px-10` →
  `gutter`, `py-6 sm:py-8` → le nouveau palier. Les variantes `sm:` disparaissent — un palier fluide
  fait le même travail sans point de rupture.
- **Aligner `default.vue`** : `py-6` → le nouveau palier.
- **Poser la règle CI** `layouts-no-raw-spacing`, bornée à `layouts/**/*.vue`, sur les seuls
  utilitaires d'espacement.
- **Tester le checker** dans `checkers.vitest.ts`.
- **Compléter `design-system.md`** : le nouveau palier rejoint la ligne *Espacement* du tableau des
  utilitaires.

## Hors périmètre

- **Les paliers bruts hors des layouts** — plus de deux cents occurrences dans `base/` et les quatre
  espaces. La doctrine incrémentale les couvre : on aligne l'écran qu'on ouvre
  ([components/README.md](../../webapp/base/app/components/README.md)). Une dette qui décroît à
  chaque passage n'a pas besoin d'un plan, et une gate à deux cents exceptions serait une gate qui
  ment.
- **La typographie** (`text-sm`, `text-lg`…) et **les largeurs** (`max-w-sm`) : la règle ne les vise
  pas. Les layouts n'en portent aucune aujourd'hui côté typographie ; côté largeur, `auth.vue`
  signale lui-même qu'aucun token du socle ne convient à sa colonne étroite, et créer ce token est
  une décision de design à part.
- **`max-w-sm` dans `auth.vue`** reste donc en place, avec son commentaire existant.
- **Le repère principal `<main>`** encore en dette sur `cockpit.vue`, `inscription.vue` et trois
  pages entreprise : lane `ENT`, et ce n'est pas la même règle.

## Décisions de cadrage

### La règle est bornée aux layouts, et ce n'est pas de la timidité

Cinq fichiers contre plus de deux cents. Le choix se justifie par **où le défaut fait mal**, pas par
la facilité : un layout impose son rythme à tous les écrans qu'il gouverne, un composant ne
contamine que lui-même. La seule divergence visible qu'on ait eue à traiter — deux espaces d'un même
produit qui se lisaient comme deux produits — venait d'un layout.

Corollaire assumé : la règle ne dira rien d'un `p-2` posé dans un composant demain. C'est la
doctrine incrémentale qui s'en charge, et elle est déjà écrite.

### Un palier plutôt qu'un rabotage

`py-6 sm:py-8` pouvait se replier sur `py-block`, sans rien créer. Ça retirait 2 à 10 px de
respiration verticale au **premier écran que voit un utilisateur**, pour une raison purement
mécanique — faire entrer une valeur dans une échelle trop courte. `design-system.md` prescrit
l'inverse en toutes lettres : « un besoin non couvert se règle en ajoutant un palier dans
`sizing.css` ». Le trou entre 22 et 28 px est réel, il resservira.

### Écart au cadrage — `inscription.vue` violait aussi, et la règle naît avec une dette

Le cadrage annonçait deux contrevenants et **aucune exception**. Le vérificateur en a trouvé un
troisième : `inscription.vue:46` espace la baseline de son panneau de marque par un `mt-4`. Le grep
de cadrage l'avait manqué — il énumérait `m`, `mx` et `my` sans les côtés individuels.

Il n'est **pas corrigé ici** : `spaces/entreprise/` est une autre lane, et écrire à sa place est
précisément ce que le dépôt interdit. Il part en `except:` avec le nom de sa lane, comme `WEB-006` et
`WEB-010` l'ont fait avant. Le défaut y est double, et le commentaire de la ligne le dit : palier
brut **et** marge individuelle, là où le socle demande d'espacer par le conteneur.

### Écart au cadrage — la gate ne peut pas être verte avant `ADM-015`

`dashboard.vue` porte encore `p-1` et `gap-1` **sur `develop`** : `ADM-015` les a retirés, mais elle
est commitée sur `espace/admin` et pas mergée. La règle sort donc trois violations de plus sur cette
branche, qui ne sont **pas une dette** — elles tombent au merge.

Décision : `ADM-015` passe devant, puis cette branche se rebase dessus. L'alternative — inscrire
`dashboard.vue` en exception transitoire — a été écartée : `rules.yaml` porterait une ligne
désignant un fichier déjà propre ailleurs, et quiconque la lirait irait chercher un défaut qui
n'existe plus.

### La valeur du palier, calée sur le mesuré

`clamp(1.5rem, 1.25rem + 0.75vw, 2rem)` n'est pas un arrondi : elle rend **24 px à 500 px de
fenêtre et 32 px à 1600**, soit exactement les deux valeurs que `py-6 sm:py-8` produisait. Le premier
essai (`1.35rem + 0.4vw`) plafonnait à 28 px et perdait 4 px — corrigé avant la mesure d'après.

### Le nom du palier, et la collision à connaître

Proposé : **`--spacing-pane`** — le rythme intérieur d'un panneau qui se suffit à lui-même, plus
généreux qu'un écart entre blocs, moins qu'une coupure de section.

⚠️ `--container-panel` existe déjà, et c'est une **largeur**. Les deux ne se marchent pas dessus dans
les utilitaires (`p-pane` vient de `--spacing-*`, `max-w-panel` de `--container-*`), mais la
proximité des noms peut tromper à la lecture. Le nom est le point le plus discutable de cette carte :
il se change en un mot si un meilleur se présente à l'implémentation, et le dire ici évite qu'on le
découvre après coup.

### L'AST, pas un motif interdit — et la raison est prouvée

Le moteur de `forbid` n'écarte que les lignes qui **commencent** par un délimiteur de commentaire.
Les templates de ce dépôt portent des commentaires de plusieurs lignes qui **citent des classes** :
`auth.vue` en contient un qui écrit `gap-2` en toutes lettres pour expliquer une animation. Un motif
interdit le compterait comme du code.

Un vérificateur structurel lit les **attributs `class`** de l'AST : il ne voit pas les commentaires
par construction, et il peut distinguer un palier d'une valeur arbitraire. C'est la démonstration
déjà faite par `WEB-010`, refaite ici pour une autre règle.

## Règles et cas limites

- **Le motif vise les paliers numériques**, pas les mots : `p-1`, `px-4`, `py-6`, `gap-2`,
  `space-y-3`, `mt-8`… et **pas** `p-element`, `gap-block`, `px-gutter`.
- **Les variantes comptent** : `sm:py-8`, `lg:gap-4`, `dark:p-2` sont visées comme leur forme nue.
  Une règle qui ne verrait que la forme nue offrirait une porte de sortie d'un préfixe.
- **Les valeurs arbitraires ne sont pas visées ici** — `w-[clamp(220px,18vw,272px)]` sur la barre
  latérale d'admin en est une, légitime et commentée. L'interdit n°3 les couvre déjà par ailleurs ;
  les rajouter à cette règle mélangerait deux sujets.
- **`p-0`, `m-0`, `gap-0` restent autorisés** : remettre à zéro n'est pas choisir une valeur hors
  échelle, et c'est parfois nécessaire pour défaire un défaut de bibliothèque.
- **Le rendu d'`auth.vue` ne doit quasiment pas bouger** : les paliers ont été choisis pour tomber
  sur les valeurs actuelles. Un écart de plus de quelques pixels est un défaut de cette carte, pas
  un effet du palier.
- **Les variantes `sm:` disparaissent d'`auth.vue`**, et c'est voulu : un palier fluide interpole en
  continu là où le point de rupture faisait sauter la valeur. Le comportement entre 640 px et le
  plafond change donc — en mieux, mais il change, et la preuve doit le montrer à deux largeurs.
- **Le CSS scopé d'`auth.vue` mentionne `gap-2`** dans un commentaire pour expliquer le décalage des
  panneaux (`calc(100% + 0.5rem)`). Si `gap-2` devient `gap-element`, **cette valeur en dur devient
  fausse** : l'écart n'est plus 0,5 rem fixe mais un `clamp()`. Le décalage doit passer par la
  variable, sinon les deux panneaux se chevauchent ou se décollent à l'échange.

## Documentation impactée

- [`docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)
  — le nouveau palier rejoint la ligne *Espacement* du tableau des utilitaires.
- `webapp/base/app/assets/css/sizing.css` — son commentaire de tête annonce « 3 paliers + gouttière »
  et doit suivre.

Aucun `README.md` de dossier : `base/app/layouts/README.md` porte la règle du repère principal, pas
celle de l'espacement, qui appartient au design system.

## Définition de terminé

- [x] `sizing.css` porte le nouveau palier, son commentaire dit son rôle, et le compte de paliers
      annoncé en tête est corrigé.
- [x] `auth.vue` et `default.vue` n'emploient plus aucun palier brut d'espacement.
- [x] Le décalage des panneaux d'`auth.vue` lit la variable d'écart au lieu de `0.5rem` en dur.
- [x] La règle `layouts-no-raw-spacing` existe, bornée aux layouts, avec **une** ligne de dette
      (`inscription.vue`, lane entreprise). `quality-conventions-check` ne pourra être vert qu'après
      le merge d'`ADM-015` et le rebase de cette branche — voir *Décisions de cadrage*.
- [x] **La gate mord, prouvé** : dette retirée à la main avant correction, elle sort `auth.vue` et
      `default.vue` avec leurs lignes ; elle épargne `w-[clamp(…)]` et `p-0`.
- [x] Les cas du checker sont couverts dans `checkers.vitest.ts`, y compris le palier cité dans un
      commentaire de template et les formes préfixées (`sm:py-8`).
- [x] **Rendu mesuré** sur `/login` et un écran de `default.vue`, **à deux largeurs** (sous et
      au-dessus de 640 px) : les paddings calculés correspondent aux paliers, et l'écart avec l'avant
      ne dépasse pas quelques pixels.
- [x] L'échange des panneaux d'`auth.vue` (variante `?inscription`) fonctionne toujours, aux deux
      largeurs.
- [x] Captures avant / après : aucun écart autre que celui, annoncé, du passage au fluide.
- [x] `design-system.md` liste le nouveau palier.
- [x] Les gates **impactées** sont vertes : conventions, format, tests du checker.

## Vérification

**La gate mord, prouvé avant toute correction.** Règle posée, coques encore dans leur état
d'origine : **12 violations**, dont les formes préfixées — un point de rupture n'ouvre aucune porte.

```
✖ layouts-no-raw-spacing — 12 violation(s)
    base/app/layouts/auth.vue:18 → <div class="… p-2 …">
    base/app/layouts/auth.vue:21 → <div class="… gap-2 …">
    base/app/layouts/auth.vue:29 → <UMain class="… px-4 …">      … py-6 … sm:px-10 … sm:py-8
    base/app/layouts/auth.vue:36 → <div class="… py-10 …">
    base/app/layouts/default.vue:16 → <UMain class="… py-6 …">
    spaces/admin/app/layouts/dashboard.vue:25 → … p-1 … gap-1     (×3, tombent au merge d'ADM-015)
```

**Le palier tombe exactement sur les valeurs mesurées.** Navigateur, `/login`, deux largeurs :

| | 500 px | 1600 px |
| --- | --- | --- |
| Panneau, padding **vertical** | 24 → **24 px** | 32 → **32 px** |
| Panneau, padding horizontal | 16 → 17,2 px | 40 → 30,4 px |
| Colonne du formulaire | 40 → 28 px | 40 → 38,4 px |
| Cadre et écart de grille | 8 → 8,18 px | 8 → 9,28 px |
| `default.vue`, vertical | 24 → 24 px | 24 → **32 px** |

Le vertical du panneau est **exact aux deux bouts** : c'est ce que le palier a été calé pour faire.
Un premier `clamp` (`1.35rem + 0.4vw`) plafonnait à 28 px et perdait 4 px — corrigé avant la mesure.

**Les deux écarts calculés les plus gros sont invisibles**, vérifié en capture et non déduit :

- le **−9,6 px horizontal** à 1600 n'a aucun effet, la colonne du formulaire étant plafonnée à
  `max-w-sm` et centrée : le padding ne mord qu'en dessous de ~416 px de fenêtre ;
- le **−12 px** de la colonne à 500 n'en a pas davantage : `my-auto` centre la boîte, donc réduire
  son padding symétriquement ne déplace pas son contenu. **Captures avant / après identiques au
  pixel.**

**Un seul écart visible, et il était annoncé** : l'accueil public descend de 8 px à 1600 px, le
passage de `py-6` (24 fixe) à `py-pane` (24→32 fluide).

**L'animation d'échange survit au passage à la variable** : décalage 795 → 794,4 px sur la variante
`?inscription`, et aucun chevauchement (panneau 805→1590, media 10→795).

**Tests** : `checkers.vitest.ts` → **41 passés**, dont 22 nouveaux — les huit paliers refusés, les
trois formes préfixées, le compte par palier et non par élément, et cinq cas de ce qui doit être
épargné (`p-0`, valeurs arbitraires, palier cité en commentaire, grille/largeur/typographie).
**Format** ✔ sur les six fichiers de `webapp/`. **Type-check** ciblé du checker ✔.

## Livré

- `webapp/base/app/assets/css/sizing.css` (M) — `--spacing-pane`, et le compte de paliers corrigé.
- `webapp/base/app/layouts/auth.vue` (M) — cinq paliers bruts remplacés, variantes `sm:` supprimées,
  et le décalage de l'animation branché sur `var(--spacing-element)` au lieu d'un `0.5rem` figé.
- `webapp/base/app/layouts/default.vue` (M) — `py-6` → `py-pane`.
- `webapp/scripts/ci/checkers.ts` (M) — `layoutsNoRawSpacing`, sur l'AST.
- `webapp/scripts/ci/rules.yaml` (M) — la règle et sa ligne de dette.
- `webapp/scripts/ci/checkers.vitest.ts` (M) — 22 cas, dans les deux sens.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — le palier rejoint le tableau.

## Reste à faire (hors périmètre)

- **Rebaser cette branche sur `develop` une fois `ADM-015` mergée** : c'est ce qui fera tomber les
  trois violations de `dashboard.vue` et rendra la gate verte. Sans ça, la CI de cette PR échoue.
- **`inscription.vue`** — son `mt-4` reste en `except:`, à solder par la lane `ENT`. Le défaut y est
  double : palier brut, et marge individuelle là où le socle demande d'espacer par le conteneur.
- **`max-w-sm` dans `auth.vue`** : le fichier signale lui-même qu'aucun token de largeur du socle ne
  convient à sa colonne étroite. Hors périmètre de cette règle, mais le besoin est nommé.
- **Les paliers bruts hors des layouts** — plus de deux cents. Doctrine incrémentale, pas de plan.

