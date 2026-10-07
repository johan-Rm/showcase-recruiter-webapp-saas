# base/app/

Couche frontend de la webapp (Nuxt).

## Rôle et responsabilités

**Rôle Nuxt :** `app/` est le répertoire source de l'application Nuxt 4 (`srcDir`) — routing,
composants, état, composition réactive, gabarits et point d'entrée `app.vue`, auxquels Nuxt
applique ses conventions d'auto-import et de scan par dossier.

**Rôle attendu :** `app/` orchestre le rendu (SSR puis hydratation), la navigation et l'état
d'interface, et s'appuie sur les couches framework-agnostic (`services/`, `shared/`) pour la
logique métier et sur `server/` (BFF) pour l'accès aux données. En architecture layers, ces
conventions gouvernent le `app/` de chaque layer (socle `base/` et espaces `spaces/*`). Chaque
sous-couche porte une responsabilité unique, décrite dans son propre README.

## Conventions techniques

### Sur-vérification des variables

Les gardes défensives (`if (!x)`, `x?.y`, `x && x.y`) ajoutées quand une structure était instable
doivent être retirées une fois le contrat de type stabilisé. Un type non-nullable, une prop
requise ou une valeur garantie par le contexte ne se vérifie pas dans le template ni dans
`<script setup>`. Une vérification légitime appartient à la couche de validation (boundary API,
formulaire, middleware), pas à l'affichage.

### Ordre des blocs dans un fichier Vue

`<script setup lang="ts">`, puis `<template>`, puis `<style scoped>` (script-first). Non verrouillé
en ESLint à ce jour.

### Structure interne de `<script setup>`

Ordre : imports, types et constantes, props et emits, composables et stores, état local, data
inputs, helpers purs, computed UI-ready, handlers, watchers, métadonnées de page, lifecycle. Le
template consomme des valeurs déjà préparées ; la logique métier ne fuit pas dans l'affichage.

### Données prêtes pour le template

Le template ne contient pas de transformation non triviale — il consomme des labels, nombres
formatés, listes filtrées et objets de présentation déjà préparés. Le placement de la
transformation dépend de sa portée :

- strictement locale et UI → helper pur ou `computed` du composant ;
- réutilisée ou réactive transverse → composable ;
- dérivée d'un état global → getter de store ;
- règle métier, mapping ou normalisation → `services/`.

### Logique métier dans les composants

Un composant ne construit pas de donnée dérivée à partir de l'état global. Un `computed` ne
combine pas une source globale (store, composable sans argument) avec une transformation
(`.map`/`.filter`/`.reduce`). Les données dérivées vivent dans les getters de store ou les
composables.

### Réseau dans les composants

Un composant n'appelle jamais `$fetch` / `useFetch` / `useLazyFetch`, ni ne mute un store
directement. Il reçoit ses données via props ou composable, lit l'état, et délègue les mutations
aux actions de store ou composables.

### Écoute d'événements et minuteries (VueUse)

L'écoute d'événements passe par `useEventListener` (VueUse — `@vueuse/nuxt` est installé), jamais
par `addEventListener` / `removeEventListener` bruts : la version VueUse est SSR-safe par
construction et se retire seule (fonction `stop` rendue, ou au démontage du scope), sans
bookkeeping manuel. Même esprit pour les minuteries (`useTimeoutFn`, `useIntervalFn`) et les
observers (`useIntersectionObserver`, `useResizeObserver`) : préférer la primitive VueUse à l'API
DOM brute.

Un **verrou CI** interdit `addEventListener` / `removeEventListener` dans `app/**`. Exception
assumée : les API sans équivalent VueUse — `requestAnimationFrame`, `requestIdleCallback` — restent
directes (cf. le plugin `deferred-runtime.client.ts`).

### Taille

Un composant ne dépasse pas ~200 lignes ; un composable n'expose pas plus de 8 valeurs dans son
`return`. Au-delà, découper — c'est le signe de plusieurs responsabilités agrégées.

### Ce qu'un commentaire ne raconte pas

**Un commentaire décrit un état présent.** Il dit pourquoi le code est ainsi, jamais par quel chemin
il y est arrivé : `git log` porte la chronologie, `dev-book/tasks/` porte la décision et son motif.
Le fichier est le seul des trois qui ne soit pas fait pour ça — et le seul dont rien ne vérifie qu'il
dit encore vrai.

Trois choses n'y ont donc pas leur place :

- **Un numéro de tâche.** Ni `ENT-047`, ni l'ancienne numérotation en numéros nus (`tâche 099`,
  `carte 120`). Il coûte un aller-retour vers `dev-book/` pour retrouver, au mieux, la phrase qu'on
  vient de lire.
- **Une chronologie** — « désormais », « auparavant », « a été retiré », « il a vécu là ». Ces
  phrases ne se corrigent jamais : on corrige le code, pas le récit du code.
- **Un `TODO`, `FIXME`, `HACK` ou `XXX`**, et par extension toute promesse de tranche future. Rien ne
  les retire quand elles sont tenues : un écran a annoncé pendant des mois une fiche dédiée « à
  venir » que le fichier voisin rendait déjà.

**Le passé se réécrit, il ne se coupe pas.** Beaucoup de ces phrases cachent une contrainte toujours
vraie, dont le passé n'était que la formulation maladroite — et la couper emporte l'avertissement
avec le récit :

```
❌  Avant, elle poussait : `flex-none` sans hauteur maximale, elle chassait les favoris hors
    de la colonne dès six critères.

✅  `min-h-0` avec `flex-1`, jamais `flex-none` sans hauteur maximale — sous cette forme elle
    chasse les autres blocs hors de la colonne dès six critères.
```

Un **verrou CI** (`app-no-comment-journal`) refuse les numéros de tâche, le vocabulaire de
chronologie et les marqueurs de dette. Il lit le texte réellement commenté — blocs multi-lignes
compris — et pas seulement les lignes qui commencent par un délimiteur.

**Il couvre tout le code du produit**, et pas seulement les gabarits : `.vue` et `.ts` d'`app/**`,
le serveur intermédiaire, `services/` et `shared/`. La règle vaut partout où l'on écrit un
commentaire, et un fichier hors de sa portée devient un endroit où le récit revient — les deux tiers
des renvois de tâche du dépôt vivaient dans les `.ts`, hors d'atteinte, pendant que les gabarits
étaient tenus. Les deux fichiers **générés** de `shared/types/` en sont exclus : personne ne les
écrit à la main.

Son nom garde le préfixe `app-` de sa première portée. Le renommer coûterait plus de bruit qu'il
n'en lèverait.

Ce qu'il ne juge pas, et qui reste à la relecture : les **temps du passé**. « Ce qui a été demandé »,
« le bouton qui portait le focus vient de disparaître » sont des états présents ; « elle poussait »
est un récit. Aucune machine ne les sépare, et une règle qui crie au loup finit avec une liste
d'exceptions plus longue qu'elle.

### Aucun texte en dur

Tout texte destiné à l'utilisateur passe par le catalogue i18n (`$t`) — **y compris les attributs
annoncés par les lecteurs d'écran** : `aria-label`, `title`, `placeholder`, `alt`. Un littéral
dans un template est une chaîne non traduisible, invisible du catalogue, et qui restera en
français dans les trois langues.

Ne sont pas concernées les valeurs purement techniques, jamais lues à l'utilisateur (`class`,
`data-*`, `role`, `aria-hidden`).

### Placement des types

Un type utilisé par plus d'un fichier vit dans `shared/types/` ; un type local non exporté reste
dans son fichier.

### Encapsulation des modules externes

Un module externe — librairie tierce ou module Nuxt — est encapsulé derrière un unique wrapper
interne (composant, composable ou service) **quand ce wrapper absorbe quelque chose de concret**.
Au moins l'un de :

- un **piège** du module (un comportement qui casse selon le contexte d'appel) ;
- une **duplication** qu'il éparpillerait sinon (une liste, un cast, une constante) ;
- un **comportement transverse** qu'on ajoute (retry, journalisation, défauts imposés) ;
- un **trou de typage** ;
- un **swap réellement plausible** — pas une possibilité théorique.

**Aucun de ces motifs → consommation directe**, et c'est une décision assumée, pas un oubli. Un
wrapper qui n'absorbe rien n'est pas une protection : c'est une indirection de plus, à maintenir
et à traverser à chaque lecture.

Le critère est **vérifiable aujourd'hui** — un défaut observé — et non une prédiction sur l'avenir
du module.

Quand l'encapsulation est décidée, la frontière devient ferme : le wrapper est **seul autorisé** à
toucher l'API du module, et un **verrou CI** dédié interdit l'usage direct ailleurs. **Sans verrou,
l'encapsulation ne compte pas.**

État actuel :

| Module               | Décision                                                                                                                 |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------ |
| `@nuxtjs/i18n`       | **encapsulé** (`useLocale`) — `useI18n()` **casse en route middleware**, et la liste des langues se dupliquait dans l'UI |
| `nuxt-auth-utils`    | usage direct — API minuscule et stable (`loggedIn`, `user`, `clear`), rien à absorber                                    |
| `@nuxtjs/color-mode` | usage direct — un seul consommateur (`LayoutColorModeToggle`)                                                            |
| `@nuxt/icon`         | usage direct — `<Icon>` est déjà le contrat                                                                              |
