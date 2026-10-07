# base/app/middleware/

Garde-fous de navigation exécutés avant l'entrée dans une route.

## Rôle et responsabilités

**Rôle Nuxt :** un middleware de route s'exécute avant le rendu de la cible — serveur au premier
chargement, client ensuite — et laisse passer, redirige (`navigateTo`) ou interrompt
(`abortNavigation`). Les middlewares globaux (`.global.ts`) s'exécutent à chaque navigation ; les
nommés s'appliquent via `definePageMeta`. Réf. : [doc Nuxt — `middleware/`](https://nuxt.com/docs/4.x/directory-structure/app/middleware).

**Rôle attendu :** cette couche fait respecter les invariants de navigation **avant tout rendu** —
contrôle d'accès, normalisation d'URL, garde-fous transverses. Elle décide de l'admission dans une
route ; elle reste légère, sans I/O bloquante, et délègue toute logique métier aux couches dédiées.
Elle est une garde UX, pas la sécurité : l'autorisation réelle est côté API et BFF.

## Conventions techniques

Aucune règle propre à ce dossier. Les règles transverses de la couche `app/` s'appliquent (voir
[../README.md](../README.md)).
