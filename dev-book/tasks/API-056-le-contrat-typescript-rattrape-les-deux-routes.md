# TASK API-056 — le contrat TypeScript rattrape les deux nouvelles routes

- **Créée le** : 2026-08-16.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — types régénérés sur l'hôte, la gate finit sans diff.
- **Type** : Correction de contrat — le fichier de types généré est régénéré, aucun code écrit à la
  main.
- **Tests** : `sans objet` — rien d'exécutable n'est produit. La preuve **est** la gate elle-même :
  `bun run quality-contracts-check` doit finir sans diff.
- **Réf** : `API-050` (le plan de travail) · `API-051` (l'inventaire des débordements) · `API-054`
  (le même oubli, la semaine dernière) · `data-contracts.md`.

## Contexte

**Le front ne décrit pas l'API : il la recopie automatiquement.** `webapp/shared/types/api.ts` est
généré depuis `api/openapi.json` ; la CI le régénère et refuse le merge si le résultat diffère de ce
qui est commité. Les deux routes livrées aujourd'hui ont bien mis `api/openapi.json` à jour, mais pas
le fichier de types qui en découle — la branche serait passée verte en local et **rouge en CI**.

C'est le deuxième tour du même oubli : `API-054` l'a corrigé pour les champs de bornage. Le motif est
toujours le même — la gate ne se déclenche pas sur le périmètre où l'on travaille (`api/`) mais sur
celui d'à côté (`webapp/`), et rien ne la rappelle au moment du commit.

## Périmètre

- **Régénérer** `webapp/shared/types/api.ts` et commiter le résultat tel quel.

## Hors périmètre

- **Consommer ces types.** Le front ne branche pas encore le plan de travail ni l'écran
  d'administration : ces tâches vivent dans leurs lanes, avec leur propre carte.
- **Toucher au générateur** ou au moment où la gate se déclenche. Que l'oubli se répète est un
  problème réel, mais c'est un problème d'outillage — il se cadre ailleurs.

## Décisions de cadrage

### Une carte plutôt qu'un amendement des commits précédents

Les trois commits de la branche sont figés et le squash est désactivé : les réécrire pour y glisser
un fichier ne raconterait pas ce qui s'est passé. La régénération prend sa carte, comme `API-054`.

### Le fichier généré n'est pas relu ligne à ligne

Ce qui se vérifie est **l'absence de diff après régénération**, pas le contenu : le relire
reviendrait à relire le générateur. Ce qui se contrôle, en revanche, c'est que le diff ne contienne
**que** les deux ressources attendues — un ajout inattendu signalerait une surface d'API modifiée
sans qu'on l'ait voulu.

## Règles et cas limites

- **La génération tourne sur l'hôte**, pas dans le conteneur : c'est la seule exécution qui
  reproduise la CI.
- La gate couvre **deux** fichiers, `api.ts` et `organization-constraints.ts` : le second n'a pas
  bougé, et c'est attendu — aucune contrainte d'organisation n'a été touchée.

## Documentation impactée

Aucune. `data-contracts.md` décrit déjà la règle — c'est son application qui avait été manquée, pas
son énoncé.

## Vérification

La dérive a été **constatée avant d'être corrigée** : la gate lancée sur l'hôte, branche à jour,
sortait rouge en réclamant les deux ressources absentes.

```
bun run quality-contracts-check   →  exit 1
  + SearchWorkspace: { … }
  + "AdminOverflowingSearch.CollectionOutput": { … }
  + /searches/workspace  et  /admin/searches  dans paths
```

Après régénération et commit, la même commande finit **sans diff**.

Le diff n'apporte que ces deux ressources, leurs deux chemins et les réponses associées :
139 lignes ajoutées, **aucune supprimée** — rien d'existant n'a bougé.

## Livré

- `webapp/shared/types/api.ts` (+139) — schémas `SearchWorkspace` et
  `AdminOverflowingSearch.CollectionOutput`, plus les deux chemins.
- `webapp/shared/types/organization-constraints.ts` : **inchangé**, comme attendu.

## Définition de terminé

- [x] `bun run quality-contracts-check` finit sans diff.
- [x] Le diff n'apporte que les schémas et chemins des deux routes livrées.
