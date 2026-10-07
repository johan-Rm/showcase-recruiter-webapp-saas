# base/app/pages/

Points d'entrée de route (routing par fichiers).

## Rôle et responsabilités

**Rôle Nuxt :** `pages/` active le routing par fichiers. Chaque fichier devient une route et choisit notamment son layout via `definePageMeta`.

**Rôle attendu :** la page est le **modèle visible de l'écran** :

`Route → Gabarit (layouts/) → Modèle de page (pages/) → Éléments (components/)`

Son `<template>` expose directement les grandes zones et leur ordre. Il compose les sections et place les composants métier ; il ne délègue pas toute cette composition à un composant-coque intermédiaire.

## Conventions techniques

### Modèle de page lisible d'un coup d'œil

Le `<template>` d'une page contient la composition de ses sections. Le code d'un formulaire, d'une liste ou de toute brique interactive complexe vit dans un composant dédié.

La racine est un élément HTML réel et unique. Un composant Vue en racine qui absorbe toute la structure devient un mini-layout implicite et masque le modèle de page.

**Racine unique, commentaires compris.** Un commentaire de premier niveau compte comme un nœud : il se place dans la racine.

### Marqueurs structurels

Les modèles exposent leur architecture dans le DOM :

- `data-page` sur la racine de la page ; sa valeur est dérivée de son chemin et écrite en kebab-case ;
- `data-section` sur les grandes zones structurelles définies par la page.

Exemple : `pages/recherches/[uuid]/table.vue` → `data-page="recherches-uuid-table"`.

`data-section` est statique, en kebab-case et unique dans le modèle. Il décrit une zone stable (`navigation`, `content`, `identity`…), jamais une donnée runtime ou un identifiant métier. Il ne se pose pas sur un élément inline ou décoratif.

Aucun élément HTML n'est ajouté uniquement pour porter un marqueur. Ces attributs identifient la structure ; ils ne servent pas au styling.

### La balise de région appartient à celui qui place

`<section>`, `<aside>` et `<main>` découpent le document. La page ou le composant composeur qui place une région possède sa balise, son nom accessible et les classes qui décrivent sa place.

**`<main>` fait exception : il est réservé au layout.** Une page n'en écrit pas. Les `<section>` et `<aside>` restent à la page.

### Périmètre d'espace structurel

Une page vit dans la layer de l'espace qui doit la porter ; son existence est son périmètre. Pas de `definePageMeta({ spaces })`.

Les règles transverses de la couche `app/` s'appliquent (voir [../README.md](../README.md)).
