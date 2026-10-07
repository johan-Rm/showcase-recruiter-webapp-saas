# services/

Couche métier pure, framework-agnostic. Logique applicative réutilisable, indépendante de Vue,
Nuxt et Pinia.

## Rôle et responsabilités

Cette couche porte la logique métier pure, indépendante du framework de rendu : transformation et
mapping de données, appels aux backends (API Symfony / Jarvi via le BFF), génération d'artefacts et
de données dérivées. Elle **reçoit ses entrées en paramètres et retourne des données** — elle ne
connaît ni le cycle de vie des composants, ni l'état réactif, ni le store. Elle est consommée par
les composables, les pages et le serveur, et reste testable hors runtime Nuxt. Racine de la webapp
(comme `shared/`), hors layers : partagée par toutes les builds d'espace.

Le rôle dans le triptyque, l'organisation par domaine et les invariants sont fixés dans
[docs/2.architecture/services-standard.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/services-standard.md).

## Conventions techniques

### Zéro dépendance framework

Aucun fichier de `services/` n'importe depuis `vue`, `nuxt`, `#app`, `#imports`, `pinia` ni un
module d'auto-import Nuxt. La couche reste pure et testable hors runtime. La logique réactive,
l'accès au store et les primitives Nuxt appartiennent aux `composables/` ou aux `pages/`, qui
consomment les services.

### Frontière de la couche

Un service ne lit pas l'état global et ne déclenche pas d'effet de rendu. Toute dépendance à un
contexte d'exécution (requête, store, runtime) est **injectée par l'appelant**.

### Ce qu'un commentaire ne raconte pas

Un commentaire décrit un **état présent** : pourquoi le code est ainsi, jamais par quel chemin il y
est arrivé. Ni numéro de tâche, ni chronologie, ni `TODO` — la règle et son geste de réécriture
vivent dans [base/app/README.md](../base/app/README.md), et le verrou CI `app-no-comment-journal`
couvre ce dossier.

### Placement des types

Un type partagé par plusieurs fichiers est défini dans `shared/types/` ; un type local non exporté
reste dans le fichier qui l'utilise.
