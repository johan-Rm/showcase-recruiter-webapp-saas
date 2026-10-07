# TASK API-054 — les types générés suivent le contrat

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — les types portent la limite de chargement et les trois listes
  d'état, et la gate anti-dérive ne trouve plus rien à régénérer.
- **Type** : Backend API — **conséquence mécanique** d'un contrat modifié : les types TypeScript sont
  régénérés, pas écrits.
- **Tests** : `sans objet` — aucun code n'est produit, seulement des types dérivés. La preuve est la
  gate anti-dérive elle-même (`quality-contracts-check`), qui régénère et échoue à la moindre
  différence.
- **Réf** : `API-047` et `API-048`, qui ont changé le contrat · précédents de même nature :
  `API-037`, `API-028` · aval `ENT-125`, qui lira ces types ·
  `.github/workflows/webapp-quality.yml`.

## Contexte

**Le contrat a bougé, son reflet côté webapp non.** `API-048` a ajouté `searchLoadLimit` à la
configuration, `API-047` a ajouté trois listes au jeu de résultats. Les types TypeScript générés
depuis `api/openapi.json` n'en portent aucune trace — zéro occurrence de `searchLoadLimit` comme de
`favoriteIds` dans `webapp/shared/types/api.ts`.

**Ce n'est pas cosmétique.** Le workflow `webapp-quality` se déclenche **sur toute modification
d'`api/openapi.json`** et lance `quality-contracts-check`, qui régénère les types et **échoue s'ils
diffèrent** de ce qui est commité. Sans cette tâche, la PR de la lane API part avec une gate
bloquante rouge, sur un workflow qui ne parle même pas d'API.

## Périmètre

- **`webapp/shared/types/api.ts` régénéré** depuis `api/openapi.json`, par le script du projet.
- **`webapp/shared/types/organization-constraints.ts` régénéré** dans le même mouvement : le script
  produit les deux, et la gate vérifie les deux.

## Hors périmètre

- **Tout code webapp qui consomme ces types** : la lecture des nouvelles listes appartient à
  `ENT-125`, celle de la limite de chargement à `ENT-126`.
- **Toute retouche manuelle des fichiers générés** : ils portent l'en-tête qui l'interdit, et une
  correction à la main serait effacée à la génération suivante.

## Décisions de cadrage

### La régénération suit le contrat, donc la lane API

C'est le geste qui rend un contrat utilisable, pas une adaptation d'écran : rien n'est décidé ici,
tout est dérivé. Le dépôt a les deux pratiques — `API-037` et `API-028` l'ont fait depuis l'API,
`ENT-017` depuis l'entreprise — et c'est la CI qui tranche : le workflow webapp se déclenche sur
`api/openapi.json`, donc la dérive naît avec le contrat et doit mourir avec lui.

### Une carte pour un fichier généré

Le diff n'est pas écrit à la main, mais il est **du contenu de commit** au même titre
qu'`openapi.json` : sans carte, il apparaîtrait dans une PR sans que rien n'explique pourquoi la
lane API touche à `webapp/`.

## Règles et cas limites

- **Le diff doit se limiter à ce que le contrat a changé** — la configuration et le jeu de résultats.
  Toute autre différence signalerait que les types étaient déjà en retard, et se dirait plutôt que
  se glisserait dans ce commit.
- **La génération se fait sur l'hôte**, pas dans un conteneur : c'est la seule exécution qui
  reproduise la CI.

## Documentation impactée

Aucune : `data-contracts.md` décrit déjà le principe — la source est l'API, les types en sont
dérivés, une gate garde l'écart à zéro. Cette tâche l'applique, elle ne le change pas.

## Vérification

Génération sur l'hôte, puis lecture du diff :

```
bun run quality-generate-schemas
   ../api/openapi.json → shared/types/api.ts [999 ms]
   organization-constraints.ts — 1 schéma(s)

webapp/shared/types/api.ts | 11 +++++++++--   (9 insertions, 2 suppressions)
```

Le diff, en entier — rien d'autre n'a bougé, et `organization-constraints.ts` est resté identique :

```diff
-  resultIds?: (string | null)[];
+  resultIds?: (string | null)[] | null;
+  favoriteIds?: (string | null)[] | null;
+  dismissedIds?: (string | null)[] | null;
+  seenIds?: (string | null)[] | null;
+  searchLoadLimit?: number;
```

(les quatre premières lignes apparaissent deux fois : le contrat porte deux schémas de jeu de
résultats, celui de la recherche et celui des candidats.)

## Livré

- `webapp/shared/types/api.ts` — régénéré depuis le contrat.

## Définition de terminé

- [x] Les fichiers générés sont à jour et commités — `organization-constraints.ts` n'avait aucune
      raison de bouger et n'a pas bougé.
- [x] `searchLoadLimit` et les trois listes du jeu de résultats figurent dans les types.
- [x] La gate anti-dérive passe : régénérer ne produit plus aucune différence.
- [x] Le diff ne contient que ce que le contrat a changé.
