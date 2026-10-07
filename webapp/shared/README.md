# shared/

Code partagé entre la couche Vue (`app/`) et la couche serveur (`server/`).

## Rôle et responsabilités

**Rôle Nuxt :** `shared/` héberge le code utilisable à la fois par l'app Vue et le serveur Nitro.
Nuxt auto-importe le premier niveau de `shared/utils/` et `shared/types/` des deux côtés ; le reste
s'importe via l'alias `#shared`. Réf. : [doc Nuxt — `shared/`](https://nuxt.com/docs/4.x/directory-structure/shared).

**Rôle attendu :** briques neutres communes aux deux runtimes — helpers purs, constantes et
contrats de types transverses (type `Space`, `SPACES`, `SPACE_ROLE_PREFIX`). Son code **ne dépend
d'aucun runtime** (ni Vue, ni Nitro, ni DOM, ni route, ni store) : c'est la condition pour qu'il
s'exécute indifféremment côté client et serveur. Ce qui est propre à un seul runtime appartient à
sa couche dédiée (`app/utils`, `services`, `server`).

## Conventions techniques

Aucune règle propre à ce dossier à ce jour.

### Ce qu'un commentaire ne raconte pas

Un commentaire décrit un **état présent** : pourquoi le code est ainsi, jamais par quel chemin il y
est arrivé. Ni numéro de tâche, ni chronologie, ni `TODO` — la règle et son geste de réécriture
vivent dans [base/app/README.md](../base/app/README.md), et le verrou CI `app-no-comment-journal`
couvre ce dossier.
