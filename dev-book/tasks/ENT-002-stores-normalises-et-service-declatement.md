# TASK ENT-002 — entreprise : quatre stores normalisés, et un service qui éclate les réponses

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — 42 tests purs, type-check des 4 espaces, rendu SSR vérifié.
- **Type** : Frontend espace entreprise — stores et couche métier.
- **Réf** : task API-002 (les réponses que ces stores consomment) · task ENT-001 (l'orchestrateur qui
  les remplira) · task 148 (les deux stores refondus ici) · task 162 (la sélection).
- **Dépend de** : **la PR `feature/api` doit être mergée d'abord** — le front lit désormais
  `resultIds` et la forme `{ search, results }`.

## Contexte

Le cockpit rappelait la même donnée plusieurs fois et en gardait plusieurs copies : le nom d'un
auteur sur chaque recherche qu'il a écrite, un jeu de cartes par recherche même quand le même profil
y figure deux fois, et les constantes de nomenclature recopiées en dur dans les composants.

## Décisions

### `isFavorite` et `isNew` ne sont pas des attributs du candidat

C'est le point qui commande tout le reste. Ce sont des **relations (recherche × candidat)** : le même
profil peut être retenu dans une recherche et pas dans l'autre. L'API les pose sur la carte parce
qu'elle sert une recherche à la fois — mais un client qui garde un **pool global** de candidats les
mélangerait dès que deux recherches partagent un profil.

Ils deviennent donc deux listes d'identifiants portées par la recherche, et la carte du pool les
porte à `null` — leur valeur hors recherche.

### Quatre stores, chacun un domaine

| `referentials` | `searches` | `candidates` | `users` |
| --- | --- | --- | --- |
| la config et les nomenclatures, figées après le boot | les recherches, celle qu'on regarde, et l'état de travail de chacune | le pool de profils, indexé par identifiant | les personnes citées |

`searches` porte `sets[searchId]` : `resultIds` · `favoriteIds` · `newIds` · `selectedIds`. Tout ce
qui relie une recherche à des candidats vit là, et nulle part ailleurs.

`candidates` ne sait plus rien des recherches — ni identifiant courant, ni favori. Un pool, pas un
jeu : le jeu est un ordre et une appartenance, c'est `resultIds`.

`users` existe pour une seule raison : `ownerName` voyageait sur chaque recherche. Dix recherches du
même collègue en gardaient dix copies, et le renommer les aurait toutes fait mentir.

### La sélection devient per-recherche, et y gagne

Elle vit dans le `set` de sa recherche. La règle du cadrage tient toujours — on ne voit jamais la
sélection d'une autre recherche — et elle gagne au passage : revenir sur une recherche y retrouve ce
qu'on avait coché, là où un état global l'aurait perdu.

Elle est en revanche **purgée des profils sortis du jeu** à chaque nouveau résultat : cocher un
profil puis le faire disparaître d'un critère ne doit pas le laisser dans un lot à dévoiler.

### La normalisation s'arrête aux stores

`services/search/normalize.ts` éclate à l'entrée ; `cardsInSearch` recompose à la sortie. Les
composants continuent de recevoir une `CandidateCard` complète, avec son favori et sa nouveauté —
`<SearchProfileCard>`, la Table et `services/search/views.ts` n'ont pas bougé d'une ligne.

C'est le rôle du composable dans le triptyque : les stores gardent de l'état brut, la vue veut un
modèle d'écran, la composition a lieu entre les deux.

### Un seul appel à la création

`create()` consomme `{ search, results }` et range les trois morceaux. L'écran ouvert derrière ne
demande plus rien.

L'ajustement de critères aussi : le `PATCH` rend le nouveau jeu, il n'y a plus de relecture derrière —
et plus d'ordre à respecter entre fiche et résultats, qui était un piège (servir les résultats
consomme le repère de nouveautés).

### Ce qui reste un appel, et pourquoi

Ouvrir une recherche lit encore `/results`, et lit la **fiche seulement si le store ne l'a pas** —
le cas du lien direct, où l'on arrive sans avoir chargé la collection. C'est le repli, pas le chemin
normal.

## Vérification

- **42 tests purs** (Vitest), dont **9 neufs** sur l'éclatement : l'auteur détaché et dédoublonné,
  l'état de travail sorti des cartes, le jeu complet pris dans la réponse et non dans la page,
  l'ordre du serveur préservé à la recomposition, une carte non chargée ignorée sans trouer la liste,
  et le pool non contaminé par la recomposition.
- **Type-check vert sur les quatre espaces.**
- **Bout en bout sur le dev**, contrat API-002 servi : `POST /api/searches` → **une** réponse portant
  la recherche, **30 cartes** et **256 `resultIds`** ; Visionneuse et Table rendues au SSR (90 cartes,
  32 lignes) annonçant bien **256 profils** ; un favori posé ressort sur la bonne carte ; aucune
  erreur dans les logs du conteneur.

## Reste à faire (hors périmètre de cette tâche)

- **Les paliers et le write-through** (task suivante) : registre de bootstrap, `/v1/config` branché
  sur `referentials`, chargement de tout le jeu en arrière-plan, et les composables réduits à des
  lecteurs. `useAsyncData` vit encore ici.
- **`favoriteIds` et `newIds` sont partiels** tant que le jeu n'est pas entièrement chargé : ils se
  lisent sur les cartes reçues, pas sur `resultIds`. Le chargement complet en arrière-plan les rendra
  exacts ; l'alternative serait que l'API les serve en entier.
- **Les constantes en dur** (`EDUCATION_LEVELS` et les trois de `ai-search.ts`) tombent avec le
  branchement de `referentials`.
