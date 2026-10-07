# TASK 048 — webapp : scripts de validation des conventions (CI)

- **Créée le** : 2026-07-09.
- **Statut** : ✅ terminé le 2026-07-13.
- **Type** : Front — Webapp Nuxt (gouvernance de code, CI).
- **Dépend de** : l'existence d'une couche métier `webapp/services/`.
- **Réf** : `webapp/README.md` (« à venir : `quality-conventions-check` ») ·
  projet de référence `blue-bay-mogador` (hors repo), `scripts/ci/`.

## Contexte

Le projet de référence `blue-bay-mogador` protège son architecture par une dizaine de scripts
exécutables en CI, pilotés par un `rules.yaml` et un workflow `conventions.yml` dédié :

- `verify-no-business-logic` — pas de logique métier dans les composants ;
- `verify-soc-components` — séparation des responsabilités ;
- `verify-types-placement` — type partagé → `shared/types/`, type local → fichier ;
- `verify-script-setup-standard` — forme canonique des SFC ;
- `verify-solid`, `verify-code-quality`, `verify-no-redundant-guards` ;
- `verify-no-framework-imports` — `services/` n'importe jamais `vue` / `nuxt` / `pinia`.

Le `webapp/README.md` annonce déjà un `quality-conventions-check` « à venir ».

## Pourquoi c'est différé

Ces scripts défendent une frontière qui **n'existe pas encore** dans `webapp/` : 22 fichiers,
pas de couche `services/`, pas de logique métier à isoler. Les importer maintenant serait de la
sur-ingénierie (YAGNI) : on paierait la maintenance de règles qui ne protègent rien.

Décision du 2026-07-09 : carte créée, exécution suspendue.

## Déclencheur de reprise

Reprendre cette tâche dès que **l'une** de ces conditions est vraie :

- une couche `webapp/services/` (métier pur, framework-agnostic) est introduite ;
- des appels API ou du mapping de données apparaissent dans des composants `.vue` ;
- la webapp dépasse ~80 fichiers dans `app/` + `server/` + `shared/`.

## Objectif (quand reprise)

Rendre les invariants d'architecture frontend vérifiables par la CI plutôt que par la relecture.

## Périmètre (esquisse)

- Poser `webapp/services/` avec son `README.md` de contrat (zéro import framework, pas d'accès
  au store, entrées en paramètres, sorties en données).
- Porter d'abord **deux** scripts seulement, les plus rentables :
  `verify-no-framework-imports` (frontière de la couche) et `verify-no-business-logic`
  (composants). Le reste à la demande.
- Script `quality:conventions` + intégration à `quality:check` ; gate CI dédié.

## Invariants

- Ne pas importer les 10 scripts en bloc. Chaque règle ajoutée doit citer un défaut réel
  observé dans le code du projet.
- Les règles restent déclaratives (YAML) et testées.

## Critères d'acceptation

- [ ] `webapp/services/README.md` décrit le contrat de la couche.
- [ ] `bun run quality:conventions` échoue sur un import `vue` volontairement ajouté dans
      `services/`, et passe sinon.
- [ ] Gate CI actif sur les PR touchant `webapp/`.

## Hors périmètre

- SonarQube.
- Portage des scripts `verify-solid` / `verify-code-quality` (jugés trop généraux à ce stade).

## Mise à jour 2026-07-11 — déclencheur satisfait, périmètre précisé

Le déclencheur de cette tâche (« l'existence d'une couche métier `webapp/services/` ») est
**désormais satisfait** : la couche existe (`webapp/services/`), et les conventions qu'elle
défendrait sont **écrites** — 12 READMEs de couche portés du standard graines-digitales
(`webapp/base/app/**/README.md`, `webapp/{services,shared}/README.md`).

Elle absorbe la tâche `069` (créée par erreur, doublon — supprimée).

### Adaptation au modèle layers

Le moteur doit scanner **`base/**` ET `spaces/*/**`** (pas un unique `app/`) : chaque layer a
son propre `app/`.

### Premiers verifiers, par ordre de confiance

1. `pages-no-space-meta` — interdit `definePageMeta({ spaces })` dans `**/pages/**/*.vue` (le
   périmètre d'espace est **structurel** en layers). Faux positifs ≈ 0.
2. `pages-no-interactive-template` — interdit `<form>`, `<input>`, `<textarea>`, `<select>`,
   `v-model`, `@submit` dans le `<template>` d'une **page** (le code interactif vit dans un
   composant). Proxy de la règle « modèle de page lisible d'un coup d'œil ».
3. `services-no-framework-imports` — aucun import `vue`/`nuxt`/`#app`/`#imports`/`pinia` dans
   `services/`.
4. `app-no-fetch-in-components` — pas de `$fetch`/`useFetch` dans `**/components/**/*.vue`.
5. `app-composable-many-returns` — un composable n'expose pas plus de 8 valeurs.
6. `app-no-hardcoded-text` — aucun texte utilisateur en dur dans un `<template>` : littéral entre
   balises, ou attribut annoncé aux lecteurs d'écran (`aria-label`, `title`, `placeholder`,
   `alt`) sans `$t`. Les valeurs techniques (`class`, `data-*`, `role`, `aria-hidden`) sont hors
   périmètre. Un simple grep ne suffit pas : lors de la première passe manuelle (2026-07-12), la
   recherche à la main avait **raté un `<h1>` sur trois** — d'où l'intérêt d'un verifier.
7. `components-root-marker` — un composant à **racine unique** porte en première classe le
   marqueur kebab-case de son nom (`auth/LoginForm.vue` → `auth-login-form`). Les fragments
   (`<template v-if>`, racines multiples) sont dispensés — il n'y a pas d'élément à marquer.
8. `encapsulation-*` — un verrou par module externe encapsulé (usage direct interdit hors de son
   wrapper) : `@nuxtjs/i18n`, `@nuxtjs/color-mode`, `@nuxt/icon`, `nuxt-auth-utils`.

### Non vérifiable (à garder en guideline)

- « une page ne compose que des sections » (littéral) — subjectif ; couvert partiellement par le
  proxy `pages-no-interactive-template`.
- « une page est dans la bonne layer d'espace » — décision produit, et **auto-vérifiant** par le
  placement du fichier.

### Rappel

Sans verrou CI, la règle « encapsulation des modules externes » (`webapp/base/app/README.md`)
**ne compte pas** comme une encapsulation — c'est le texte même de la règle.

## Mise à jour 2026-07-12 — moteur livré, 4 règles actives

Moteur générique et **déclaratif** (`webapp/scripts/ci/`) : un `rules.yaml` (scope, région,
motifs interdits, message, README de référence) + un runner unique. Le README porte le
*pourquoi*, le verifier le *comment vérifier*. Adapté aux layers : il scanne le socle **et**
chaque layer d'espace.

`quality-conventions-check` est branché dans `quality-check` et dans la CI `webapp-quality`.

**Actives** : `services-no-framework-imports`, `app-no-fetch-in-components`,
`pages-no-space-meta`, `app-no-hardcoded-text`.

**Chaque règle a été prouvée** : violation introduite → gate rouge, fichier et ligne exacts.

### Bug attrapé par cette preuve

Deux règles sur quatre **ne mordaient pas**. Le moteur testait `if (!match?.index)` pour savoir
s'il avait trouvé la région — or `match.index === 0` quand le bloc `<script>` **ouvre le
fichier**, et `!0` est vrai : la région était considérée introuvable et **la règle sautée**.
Faux négatif silencieux, sur exactement les fichiers les plus courants. Une gate qui ne mord
jamais est **pire qu'inutile** : elle rassure sans protéger. D'où la règle : toute nouvelle
règle doit être prouvée par une violation volontaire.

### Reste

`components-root-marker`, `app-composable-many-returns`, `pages-no-interactive-template`.

**Un seul verrou d'encapsulation** : `encapsulation-i18n` (usage direct de `useI18n()` /
`useLocalePath()` interdit hors de `useLocale`). Décision du 2026-07-12 : la règle
d'encapsulation exige désormais que le wrapper **absorbe quelque chose de concret** (un piège,
une duplication, un comportement transverse, un trou de typage, un swap plausible) — un critère
vérifiable aujourd'hui, pas une prédiction. `nuxt-auth-utils`, `@nuxtjs/color-mode` et
`@nuxt/icon` restent donc en **usage direct assumé** : ils n'absorbent rien. Seul i18n le
méritait (`useI18n()` casse en route middleware, et la liste des langues se dupliquait dans
l'UI). Cf. `base/app/README.md` — « Encapsulation des modules externes ».

## Mise à jour 2026-07-13 — les 3 verifiers restants

**Livrés : `components-root-marker` et `pages-no-interactive-template`.** Le moteur compte
désormais **7 règles**.

### Un second type de règle : `check`

`components-root-marker` ne se vérifie pas ligne à ligne. Le tag racine s'étale sur plusieurs
lignes dès qu'il porte trois classes Tailwind — un scan qui lirait la première ligne du
`<template>` raterait le marqueur de `Button.vue` comme de `Badge.vue`. Le moteur aiguille donc
sur deux types : `forbid` (scan de lignes, inchangé) et `check` (vérificateur structurel,
`scripts/ci/checkers.ts`), qui lit l'AST du SFC via `vue/compiler-sfc` — **déjà livré avec Vue,
aucune dépendance ajoutée**.

Les fragments (racines multiples, `<template v-if>`) sont dispensés : il n'y a pas d'élément à
marquer. C'est le cas d'`auth/Nav.vue`.

### `app-composable-many-returns` : abandonnée

La règle contredisait l'invariant de cette tâche — « chaque règle ajoutée doit citer un défaut
réel observé ». Les 5 composables du projet retournent au plus 5 valeurs ; le seuil de 8 n'a
jamais été approché. Une règle sans défaut observé est une prédiction, pas un garde-fou.
Conservée en guideline dans `composables/README.md`, à verrouiller le jour où un composable
obèse apparaît.

### Chaque règle prouvée par une violation délibérée

- marqueur **absent** (`ui/Card.vue`) → détecté ;
- marqueur **faux** (`ui-button` → `btn`), sur un tag racine **multi-ligne** → détecté ;
- marqueur **pas en première classe** (`layout/Brand.vue`) → détecté ;
- `<form>` + `v-model` + `@submit` dans `pages/login.vue` → 3 violations ;
- dépôt propre → vert.

Le premier essai du 4ᵉ test **n'a rien détecté** — mais le test était faux, pas la règle : le
`sed` visait `<UiCard>` là où la page écrit `<UiCard as="section">`, donc rien n'était injecté.
Un test de garde-fou qui passe sans avoir rien exercé est le même faux négatif que celui qui
avait rendu 2 règles muettes en première passe. **Vérifier que l'injection a bien eu lieu fait
partie de la preuve.**

