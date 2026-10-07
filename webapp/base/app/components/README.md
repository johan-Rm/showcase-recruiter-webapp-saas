# base/app/components/

Briques de présentation et de composition visuelle.

## Rôle et responsabilités

**Rôle Nuxt :** `components/` auto-importe les composants Vue dans toute l'application ; le nom
d'import dérive du chemin (les dossiers servent de préfixe). Réf. : [doc Nuxt — `components/`](https://nuxt.com/docs/4.x/directory-structure/app/components).

**Rôle attendu :** le composant implémente le rendu dans le modèle `page → layout → composant` —
affichage et composition visuelle uniquement. Il consomme des données déjà préparées, reçues par
ses props ou via un composable, et reste **passif** : il ne récupère pas de données, ne mute pas
l'état global, ne construit pas de donnée métier dérivée. Il se concentre sur un rendu lisible,
typé, accessible et SSR-safe.

## Conventions techniques

### Organisation par feature, pas par forme

Le dossier désigne la **feature** (`auth/`, `vivier/`), jamais le type de widget (`card/`,
`toggle/`). Grouper par forme éparpille une feature sur plusieurs dossiers et transforme chaque
dossier en fourre-tout ; grouper par feature met au même endroit tout ce qu'on touche ensemble.

Trois axes se combinent :

- **la layer** porte le domaine : `base/` = commun aux 4 espaces, `spaces/<espace>/` = propre à un
  espace (le Vivier vit dans `spaces/entreprise/`) ;
- **le dossier** porte la feature (`auth/`, `layout/`, `vivier/`) ;
- **`ui/`** est réservé aux primitives génériques, **sans domaine**, réutilisables partout — c'est
  le design system (`<UiButton>`, `<UiCard>`).

### Nuxt UI et le kit maison

`@nuxt/ui` est installé et **branché sur les tokens du kit**
([`assets/css/nuxt-ui.css`](../assets/css/nuxt-ui.css)) : ses composants `U*` héritent du nuancier,
des deux thèmes et de l'échelle de rayons, exactement comme les `Ui*`.

**Les deux cohabitent.** Un écran neuf part des composants Nuxt UI ; un `Ui*` existant n'est
remplacé que lorsqu'on touche l'écran qui le porte. Pas de migration en masse : elle toucherait les
quatre espaces d'un coup sans rien apporter à l'utilisateur.

**L'exception se démontre, elle ne se décrète pas.** Un `Ui*` neuf ne se justifie que lorsque Nuxt UI
ne sait pas produire la **structure** voulue — pas quand son style ne plaît pas, que `:ui` et les
tokens couvrent déjà. La carte qui le crée doit nommer ce que la structure a d'impossible, sinon la
suivante relira « un écran neuf part de Nuxt UI » et refera le débat. Précédent : `UiTagPicker`,
parce que `UInputMenu` en mode `multiple` place ses chips **dans** le champ et qu'aucune valeur de
`:ui` ne les en sort.

### Nommage dérivé du chemin

Le nom d'import est dérivé du chemin par Nuxt. Le fichier **ne répète pas** le préfixe de son
dossier :

- `auth/LoginForm.vue` → `<AuthLoginForm>` (et non `auth/AuthLoginForm.vue`)
- `layout/Brand.vue` → `<LayoutBrand>`
- `vivier/Table.vue` → `<VivierTable>`

Fichiers en PascalCase. Un dossier se crée dès qu'une feature compte **au moins deux** composants ;
en deçà, un composant isolé peut rester à la racine.

### Un fichier ne répète pas le nom de son espace

**La coque d'un espace n'est pas une feature.** La barre haute, la barre latérale et le tiroir mobile
ne servent aucun domaine : ils sont le cadre dans lequel les domaines s'affichent. Ils restent donc à
la racine de `components/` — `Topbar.vue` → `<Topbar>` —, et la règle du dossier ci-dessus ne les
vise pas : « feature » y désigne un domaine fonctionnel (`review/`, `search/`, `account/`), pas
n'importe quel groupe de deux fichiers.

**Et le nom ne reprend jamais celui de l'espace.** `spaces/admin/app/components/AdminTopbar.vue` dit
deux fois la même chose : la layer porte déjà le domaine, c'est le premier des trois axes ci-dessus.
Le préfixe ne protège de rien non plus — chaque espace est compilé séparément, une build par
`SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE`, si bien que le `<Topbar>` de l'entreprise et celui de l'admin ne se rencontrent
jamais.

Règle CI `app-no-space-prefix`, qui couvre tout `spaces/*/app/` — composants, composables, stores,
utils : le principe ne dépend pas de la couche.

### Marqueur de composant sur la racine

Quand un composant a une **racine unique**, celle-ci porte en **première classe** un marqueur en
kebab-case reprenant son nom d'import : `auth/LoginForm.vue` → `auth-login-form`.

```vue
<button class="layout-color-mode-toggle cursor-pointer rounded-lg border border-line px-2 py-1.5">
```

Il rend le composant identifiable dans le **DOM servi** : depuis que les styles sont en utilitaires
Tailwind (plus de `<style scoped>`), Vue n'injecte plus d'attribut `data-v-*` — sans marqueur, rien
n'indique quel composant a produit quel nœud.

C'est un **marqueur, pas un hook** : aucun style ne s'y accroche jamais, sous peine de recréer du
CSS hors Tailwind.

La racine reste l'élément **sémantiquement juste** (`<form>`, `<button>`, `<select>`, `<nav>`…) —
on n'ajoute **jamais** un `<div>` d'enveloppe pour avoir une racine à marquer : ça casserait la
relation flex/grid avec le parent et la sémantique. Les composants à racine **multiple ou
conditionnelle** (fragments, `<template v-if>`) en sont **dispensés** — il n'y a pas d'élément à
marquer.

**Juste ne veut pas dire n'importe laquelle.** `<section>`, `<aside>` et `<main>` découpent le
document : ils appartiennent à celui qui **place** le composant, pas au composant lui-même — cf.
« La balise de région appartient à celui qui place » dans
[../pages/README.md](../pages/README.md), règle CI `components-no-page-region`. Le conteneur qui
porte le contrat de hauteur ou la mise en page interne, lui, est légitime : ce n'est pas le `<div>`
proscrit ci-dessus, qui n'existait que pour porter un marqueur.

### Wrappers de modules externes

Un wrapper encapsulant un module externe vit à la **racine**, préfixé `App*` (`<AppImage>`,
`<AppLink>`) — un seul par module, seul autorisé à toucher son API (cf. « Encapsulation des
modules externes » dans [../README.md](../README.md)).

Les autres conventions visant les composants (logique métier, fetch, mutation de store, taille)
sont portées par [../README.md](../README.md).
