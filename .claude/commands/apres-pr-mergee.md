---
description: Une fois la PR MERGÉE — vérifier le merge, mettre develop à jour et réaligner toutes les branches de travail au repos
argument-hint: [branche mergée, ex. espace/admin]
allowed-tools: Bash(git:*)
---

Après-merge de la branche : **$ARGUMENTS**

> À ne pas confondre avec `/creer-pr`, qui **ouvre** la PR. Ici la PR est déjà **mergée** — le
> premier contrôle ci-dessous refuse de continuer si elle ne l'est pas.

Le rebase réécrit les SHA : une branche mergée n'est **jamais** réutilisable telle quelle, et les
autres divergent dès qu'un commit arrive sur `develop`. Cette commande les remet toutes d'aplomb
en une passe.

## Procédure

1. **Arbre propre exigé.** `git status --porcelain` non vide → s'arrêter et le signaler. Aucune
   des opérations qui suivent ne doit s'exécuter sur un arbre sale.

2. `git fetch origin --prune`.

3. **Vérifier que la PR est réellement mergée** : `git cherry origin/develop <branche>`.
   La comparaison se fait par **patch-id**, donc elle retrouve les commits malgré la réécriture
   de SHA — un `git log` par SHA ne le peut pas.
   - lignes `-` → le commit est sur `develop` ;
   - la moindre ligne `+` → la PR n'est pas mergée, ou pas entièrement : **STOP**, ne rien
     réaligner.

4. **Détecter un squash accidentel.** Récupérer les sujets des commits de la branche et vérifier
   qu'ils apparaissent **individuellement** dans `git log --oneline origin/develop`. Si le contenu
   est présent mais que les N sujets ont fusionné en un seul commit, la PR a été mergée en squash :
   le dire franchement et sans attendre — c'est réparable tant que rien n'a été construit dessus,
   et ça ne l'est plus après.

5. **Mettre `develop` local à jour sans checkout** : `git fetch origin develop:develop`.

6. **Énumérer les branches de travail** — les découvrir plutôt que les écrire en dur, un espace
   pouvant apparaître :

   ```bash
   git for-each-ref --format='%(refname:short)' 'refs/heads/espace/*'
   ```

   `hotfix/*` est **hors périmètre** : son cycle part de `main`, pas de `develop`.

7. **Traiter chaque branche** selon son état — l'ordre des tests compte, le premier qui s'applique
   gagne :

   | État | Action |
   |---|---|
   | Checked-out quelque part (`git worktree list`) avec un arbre sale | **ne rien faire**, signaler — du travail est en cours dessus |
   | Poussée avec des commits d'avance (PR ouverte) | **ne rien faire**, signaler — un rebase imposerait un force-push, c'est une décision explicite |
   | Aucun commit d'avance sur `origin/develop` | `git branch -f <branche> origin/develop` — fast-forward pur, sans checkout |
   | Commits d'avance, non poussée | `git rebase --onto origin/develop <ancienne-base> <branche>` ; en cas de conflit : `git rebase --abort` et signaler |

8. **Rapport** : pour chaque branche, son état avant, ce qui a été fait, et pour celles laissées de
   côté, **pourquoi**. Rappeler ce qui reste en vol (branches avec des tâches non mergées).

## Garde-fous

- Jamais de `reset --hard` sur une branche portant des commits **non retrouvés** sur `develop` : ce
  sont des tâches non mergées, les écraser les détruit.
- Jamais de force-push automatique. Une branche dont la PR est ouverte se rebase sur demande
  explicite, pas par balayage.
- Ne jamais toucher une branche dont l'arbre est sale : quelqu'un — humain ou agent — travaille
  dessus.
- Une `espace/*` mergée n'est **pas supprimée** : elle est réalignée et resservira (cf.
  [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md)).
- **Ne jamais créer de branche nommée `espace`** tout court, ni de segment sous une branche
  existante (`espace/api/fix`) : Git interdit qu'une réf soit à la fois un fichier et un dossier.
