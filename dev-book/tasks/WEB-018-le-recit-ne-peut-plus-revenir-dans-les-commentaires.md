# TASK WEB-018 — socle : le récit ne peut plus revenir dans les commentaires

- **Créée le** : 2026-08-09.
- **Session** : `3d83b5c5-c632-417d-967f-1345935b8392`.
- **Statut** : ✅ fait le 2026-08-09 — une **19ᵉ règle de conventions** refuse en CI les numéros de
  tâche, le vocabulaire de chronologie et les marqueurs de dette dans les commentaires des `.vue`
  d'`app/**`. **86 tests verts** sur le fichier de vérificateurs, dont **19 neufs**. Elle sort
  **rouge à dessein** sur cette branche — cf. *Vérification* et *Reste à faire*.
- **Type** : outillage de qualité — une règle CI, son vérificateur, ses tests et la convention
  écrite. Aucun code applicatif n'est touché.
- **Tests** : **exigés et écrits** — le README de `webapp/` impose qu'une nouvelle règle soit prouvée
  par une violation volontaire. Dix-neuf cas : neuf formes refusées (les deux numérotations de
  tâches, quatre familles de chronologie, `TODO`), la lecture au **milieu** d'un bloc HTML et d'un
  bloc JSDoc, et dix cas épargnés — le code portant les mêmes motifs, une URL, trois temps du passé.
- **Réf** : `ENT-074`, `ADM-019`, `WEB-017` — les trois cartes de nettoyage, **amont : elles doivent
  être mergées avant que la PR de celle-ci puisse être verte** ·
  [base/app/README.md](../../webapp/base/app/README.md) § *Ce qu'un commentaire ne raconte pas* ·
  `ENT-027`, `WEB-014` — précédents de vérificateurs structurels.

## Contexte

Les trois cartes amont ont retiré **203 lignes de journal** des commentaires `.vue` des quatre
espaces. Sans verrou, la prochaine tâche en réécrit : c'est exactement ce qui s'est passé pendant
soixante-quatorze tâches d'espace entreprise, chacune ajoutant son paragraphe, aucune n'en retirant.

Le verrou est ici la seule forme de mémoire qui tienne. Une règle écrite dans un README se lit une
fois ; une règle en CI se rappelle à chaque PR.

### Pourquoi un vérificateur, et pas une règle `forbid`

Le moteur de `rules.yaml` sait interdire un motif ligne à ligne — mais il **écarte explicitement les
lignes de commentaire** (`verify-conventions.ts`) :

```ts
if (trimmed.startsWith('//') || trimmed.startsWith('*') || trimmed.startsWith('<!--')) return
```

C'est délibéré et juste pour les huit règles existantes : le commentaire qui explique un piège cite
forcément la classe qu'il dénonce. Cette règle-ci fait exactement l'inverse — elle ne juge **que** le
commentaire. Elle demandait donc un vérificateur, comme `layouts-no-raw-spacing` et
`app-no-ambiguous-kit-class` avant elle, mais pour la raison symétrique.

Et le test d'un motif ancré en début de ligne était de toute façon perdu d'avance : c'est cette forme
qui a laissé **sept renvois de tâche** dormir en troisième ligne de commentaires HTML de six, dans
trois espaces qu'un `grep` avait déclarés propres. Le vérificateur suit l'ouverture et la fermeture
des blocs.

## Périmètre

- **Un vérificateur** `app-no-comment-journal` dans `checkers.ts` : extraction du texte réellement
  commenté (blocs HTML, blocs JS, commentaires de ligne), puis six motifs.
- **Une règle** dans `rules.yaml`, portée `base/app/**/*.vue` et `spaces/*/app/**/*.vue`.
- **Dix-neuf tests**, refus et épargnes.
- **La convention écrite** dans `base/app/README.md`, section neuve — une règle en CI sans sa page
  de README est une sanction sans loi.

## Hors périmètre

- **Les fichiers `.ts`.** Décision de cadrage commune aux quatre cartes : la portée reste `.vue`. Les
  **69 lignes de journal dans 29 fichiers `.ts`** ne sont donc ni nettoyées, ni couvertes. **Nommé
  ici, non traité** — c'est le trou connu de ce dispositif.
- **Les temps du passé** — cf. *Décisions de cadrage*.
- **`docs/`, `dev-book/`, `api/`** : la règle vise le code applicatif du webapp. Une carte de tâche
  DOIT parler du passé, c'est son objet.
- **Le nettoyage lui-même** — `ENT-074`, `ADM-019`, `WEB-017`.

## Décisions de cadrage

### La règle ne juge PAS les temps du passé, et c'est délibéré

C'est la seule décision discutable de la carte, et elle mérite d'être écrite parce qu'elle sera
questionnée : ce sont précisément les imparfaits qui ont le plus échappé au cadrage.

Mais « ce qui **a été** demandé » (les critères de la recherche), « le bouton qui **portait** le
focus vient de disparaître » (l'instant du clic), « les peser pareil **obligerait** à relire les
deux » sont des commentaires justes. **Douze occurrences de ce genre ont été relues et conservées**
dans les quatre espaces. Les refuser mécaniquement mettrait douze exceptions dans `rules.yaml` le
jour de la pose — et une règle qui commence sa vie avec une liste d'exceptions plus longue qu'elle
n'est pas une règle, c'est un bruit qu'on apprend à ignorer.

La règle prend donc **ce qui est mécaniquement décidable**, et le README nomme explicitement ce
qu'elle laisse à la relecture. Un verrou honnête sur sa portée vaut mieux qu'un verrou qui crie au
loup.

### Les deux numérotations, parce que la seconde a failli passer

`ENT-\d{3}` ne voit pas `(tâche 099)`. Les cartes 001 à 164 sont antérieures au découpage en
espaces : elles s'écrivent en numéros nus, et **sept écrans admin plus un écran entreprise** en
portaient. Le second motif, `(?:tâches?|tasks?|cartes?)\s+n?°?\s*#?\d{2,4}`, exige le mot ET le
nombre — « la carte du candidat » et « 280 pixels » passent, « carte 120 » non.

### Un défaut de la règle trouvé par ses propres tests

Le motif des participes s'écrivait `a été (retiré|supprimé|…)e?s?\b`. **Il ne mordait sur aucun
d'eux** : ces participes finissent tous par `é`, qui n'est pas un caractère de mot pour une regex JS
non-Unicode — la frontière `\b` ne se forme donc jamais après lui.

Ni la relecture, ni le lint, ni le type-check ne pouvaient le voir : la règle était syntaxiquement
valide, elle rendait simplement zéro. C'est le test « refuse *Une quatrième case a été retirée* » qui
l'a sorti. Le commentaire du motif porte désormais la raison, sinon la prochaine « harmonisation »
remet le `\b`.

## Règles et cas limites

- **Le `//` d'une URL n'ouvre pas un commentaire.** `https://…` est écarté par le caractère qui
  précède, sinon toute ligne citant une URL verrait sa fin jugée comme du commentaire.
- **Un hit par MOTIF, pas par ligne** : une ligne portant un numéro de tâche ET un « désormais » en
  rend deux — on répare ce qu'on voit, pas ce qui reste après.
- **Le code portant les mêmes chaînes est épargné.** `const REF = 'ENT-047'` n'est pas du journal.
  C'est le miroir exact de la limite des autres vérificateurs, qui, eux, ne lisent jamais un
  commentaire.
- **La règle est aveugle aux `.ts`** par décision, pas par oubli.
- **Elle ne juge pas la longueur** d'un commentaire : un paragraphe juste reste, même long.

## Documentation impactée

- **[base/app/README.md](../../webapp/base/app/README.md)** — section neuve *Ce qu'un commentaire ne
  raconte pas*, entre *Taille* et *Aucun texte en dur* : les trois familles refusées, la règle
  « le passé se réécrit, il ne se coupe pas » avec son avant/après, et **ce que le verrou ne juge
  pas**. C'est le `readme:` que la règle cite ; sans lui, le message d'erreur renverrait vers une
  section qui n'existe pas.

## Définition de terminé

- [x] La règle refuse les deux numérotations de tâches, quatre familles de chronologie et les
      marqueurs de dette.
- [x] Elle lit le **milieu** des commentaires multi-lignes, HTML comme JSDoc — prouvé par deux tests
      dédiés.
- [x] Elle épargne le code, les URL et les temps du passé — prouvé par dix tests.
- [x] La convention est écrite dans le README que la règle cite.
- [x] `vitest` : **86/86** sur le fichier de vérificateurs, contre 67 avant.
- [x] Format et lint verts sur les fichiers touchés.
- [x] Les cinq espaces sont **à zéro violation** sous cette règle, une fois les trois cartes amont
      appliquées.
- [x] Gates, en **une passe** à l'ouverture de la PR : **vitest 327/327**, type-check vert sur les
      quatre espaces, prettier vert, eslint **0 erreur** (20 avertissements pré-existants,
      identiques à `develop`). Les conventions sortent **rouge à dessein** — 58 violations, toutes
      rendues par la règle que cette carte pose, toutes sur `spaces/entreprise` et `spaces/admin`
      qu'aucune de ses lignes de diff ne touche. **Les dix-huit autres règles sont vertes.**

## Vérification

### La règle mord sur ce qu'on vient de retirer — le seul test qui compte

Appliquée à `develop`, c'est-à-dire à l'état d'AVANT les trois cartes amont :

```
58 violation(s) — 49 renvois de tâche, 9 chronologies
  spaces/entreprise  46
  spaces/admin       12
  base                0   ← déjà nettoyé sur cette branche
```

Ces 58 lignes sont celles que `ENT-074` et `ADM-019` retirent sur leurs branches. **La règle rattrape
donc exactement le défaut qu'elle existe pour empêcher**, y compris les sept renvois enfouis au
milieu de blocs que trois `grep` successifs avaient manqués.

### Les cinq espaces, une fois les trois cartes appliquées

Vérificateur de cette branche exécuté sur les fichiers des trois worktrees de travail :

```
base/               → 0 violation(s)
spaces/entreprise/  → 0 violation(s)
spaces/admin/       → 0 violation(s)
spaces/public/      → 0 violation(s)
spaces/candidat/    → 0 violation(s)
```

### Les tests

```
Test Files  1 passed (1)
     Tests  86 passed (86)
```

Soit **19 cas neufs** (67 → 86). Dont celui qui a trouvé le défaut de frontière de mot.

### La gate, sur cette branche

```
✖ app-no-comment-journal — 58 violation(s)
58 violation(s) sur 19 règles.
```

**Rouge, et c'est le comportement attendu ici** : ce worktree porte encore les `spaces/entreprise` et
`spaces/admin` de `develop`. Les dix-huit autres règles sont vertes — la nouvelle est la seule à
sortir, et uniquement sur des fichiers qu'aucune de ses trois lignes de diff ne touche.

## Livré

- `webapp/scripts/ci/checkers.ts` (M) — `commentLinesOf`, qui suit l'ouverture et la fermeture des
  blocs, et `appNoCommentJournal`, ses six motifs. Deux commentaires portent ce que le code ne dit
  pas : pourquoi le `:` avant un `//` écarte les URL, et pourquoi le motif des participes n'a pas de
  `\b` final.
- `webapp/scripts/ci/rules.yaml` (M) — la 19ᵉ règle, sa portée et son message : le message porte
  l'avant/après plutôt que l'interdit seul, parce que le réflexe naturel devant cette règle est de
  couper la phrase.
- `webapp/scripts/ci/checkers.vitest.ts` (M) — 19 cas.
- `webapp/base/app/README.md` (M) — la convention, et la limite du verrou.

## Reste à faire (hors périmètre)

- **L'ORDRE DES PR est contraint, et c'est le seul point d'attention de cette carte** : `ENT-074`,
  `ADM-019` et `WEB-017` doivent être **mergées d'abord**. Le script de conventions scanne l'arbre
  entier et non le diff de la branche : ouverte avant elles, la PR de cette carte sortirait rouge sur
  58 lignes qu'elle ne touche pas. Rebaser cette branche sur `develop` après leur merge suffit.
- **Les 29 fichiers `.ts`** porteurs du même défaut, 69 lignes : hors portée par décision. Tant
  qu'ils ne sont pas repris, la règle ne peut pas s'étendre à `**/*.ts` sans sortir rouge.
- **Les temps du passé** restent à la relecture humaine, par décision assumée.
- **`rules.yaml` porte lui-même un `(tâche 048)`** en tête de fichier, et `structure-rules.yaml` est
  à vérifier. Hors portée — la règle ne juge que les `.vue` —, mais c'est la même dette, dans le
  fichier qui la sanctionne.
