# base/app/stores/

État global partagé de l'application (Pinia).

## Rôle et responsabilités

**Rôle Pinia :** un store centralise un état global réactif partagé entre plusieurs composants ou
composables, exposé via un contrat typé (state, getters, actions), auto-importé via son
`useXxxStore()` et sérialisable pour le SSR. Réf. : [doc Pinia](https://pinia.vuejs.org/core-concepts/).

**Rôle attendu :** cette couche conserve l'état **brut** d'un domaine fonctionnel et en expose une
lecture enrichie — pattern « état brut, getter enrichi » : le state stocke des DTOs sérialisables,
les getters délèguent l'enrichissement aux services de mapping à la demande. Les mutations passent
par des **setters explicites** (`setX`, `reset`). Le store ne porte pas la logique métier et ne
connaît pas l'UI ; il expose un contrat typé que les composables consomment. Un état sans vocation
globale reste local au composable qui le crée (`ref`).

Les conventions détaillées (deux styles de store, responsabilités d'un getter, setters défensifs,
règles SSR, naming) sont fixées dans
[docs/2.architecture/stores-standard.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/stores-standard.md).

## Conventions techniques

Aucune règle propre à ce dossier. Les règles transverses de la couche `app/` s'appliquent (voir
[../README.md](../README.md)).
