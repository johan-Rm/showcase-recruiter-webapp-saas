# TASK OPS-005 — le titre de PR prend une forme

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Outillage — convention de contribution.

## Contexte

Le workflow disait seulement que « le titre de PR résume l'ensemble ». Faute de forme, six PR
ouvertes de suite en ont pris quatre :

```
Espace entreprise : le menu du compte se resserre                    préfixe d'espace
Recherche : le jeu filtré sur les retenus, et des suggestions…       préfixe de domaine
Outillage de recherche floue et suite de tests 15× plus rapide       aucun préfixe
/v1/me porte l'organisation du recruteur                             un chemin d'API
```

Le dernier ouvre sur un identifiant technique là où il faudrait le sens ; et lu dans une liste, un
titre sans périmètre ne dit pas de quel espace il parle.

## Décision

**`<Périmètre> : <ce que ça change>`**, en français, sans référence de tâche.

Le périmètre est l'espace de la branche, ou `API`. Ce qui suit dit le changement, pas le moyen.

### Pourquoi pas un Conventional Commit

Ce serait cohérent avec les commits en dessous, mais faux sur deux points. Le titre de PR **ne part
pas dans l'historique** — le merge se fait en rebase, seuls les commits atterrissent. Et une PR porte
souvent plusieurs tâches de types différents : lui coller un `feat(...)` reviendrait à en élire une.

Une PR qui porte plusieurs tâches les énumère.

### Ce que la règle ne fait pas

Elle n'est **pas vérifiée par la CI**, et c'est volontaire : `commit-check` analyse la plage de
commits, et un titre qui ne laisse aucune trace ne mérite pas un job de plus. C'est une convention
de lecture, tenue à la main.

## Critères d'acceptation

- [x] La forme est écrite dans `git-workflow.md`, avec ses exemples et la raison de ne pas suivre
      Conventional Commits.
