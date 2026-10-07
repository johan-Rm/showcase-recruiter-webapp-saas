---
description: Ouvrir la PR d'une branche d'espace — état des cartes vérifié, gates qualité en une passe, titre et corps prêts à coller (gh n'est pas authentifié ici)
argument-hint: [rien, ou une note sur ce que la PR doit mettre en avant]
allowed-tools: Bash(git:*), Bash(make:*), Bash(bun:*), Read, Glob, Grep
---

Ouvrir la PR de la branche courante. Note éventuelle : **$ARGUMENTS**

## Ce que cette commande est

**Le seul point de contrôle qualité du cycle.** `/commit-tache` n'a lancé que les tests impactés ;
ici, tout tourne, une fois, pour toute la branche.

Et le seul endroit qui **relit les cartes** que la branche embarque : une tâche restée ouverte, une
couverture jamais décidée ou une doc annoncée puis oubliée s'arrêtent ici (étape 3).

Elle **ne commite pas** : le travail est déjà en commits de tâche. Si l'arbre est sale, s'arrêter et
renvoyer vers `/commit-tache`.

Elle remplace la skill générique `create-pr` sur ce dépôt, dont quatre défauts sont **faux ici** :
squash & merge, suppression de la branche après merge, titre en Conventional Commit, et
`gh pr create` — `gh` n'est pas authentifié dans cet environnement.

## Procédure

1. **État de départ.**
   - `git branch --show-current` → doit être une `espace/*` (ou `hotfix/*`). `develop` / `main` :
     STOP.
   - `git status --porcelain` → non vide : STOP, il reste du travail à commiter.
   - `git log --oneline origin/develop..HEAD` → les tâches que la PR va porter. Zéro commit : rien
     à ouvrir.

2. **Relire le diff cumulé** : `git diff origin/develop...HEAD --stat` puis le diff lui-même.
   - Un seul périmètre de travail, aucun fichier embarqué par accident, aucun secret ni `.env`.
   - **Chaque commit** correspond à une tâche `dev-book/tasks/` — `commit-check` valide en CI
     **chacun d'eux**, pas seulement le titre de PR : un commit malformé au milieu bloque la PR
     entière.
   - Aucun `wip` ni `fixup` : le merge se fait en **rebase**, ces commits atterriraient tels quels
     sur `develop`. Nettoyer localement (`git rebase -i`) **avant** d'ouvrir.

3. **L'état des cartes portées — bloquant.** La branche embarque N cartes de tâche : les **lire**,
   toutes. Ces cinq contrôles ne s'exécutent pas, ils se **lisent** dans les fichiers.

   | Contrôle | Ce qu'un échec révèle |
   |---|---|
   | Chaque commit porte une carte `dev-book/tasks/` | Du code livré sans spec |
   | Chaque carte est close — `✅ fait le …`, pas `⏳ à faire` | Une tâche commitée avant d'être finie |
   | Chaque carte a sa ligne `**Tests** :` renseignée | Une couverture jamais décidée |
   | Aucune case vide dans sa *Définition de terminé* | Un critère du cadrage abandonné en silence |
   | Les pages listées en *Documentation impactée* sont dans le diff cumulé | Une doc devenue fausse |

   **Bloquants comme l'arbre sale** : un contrôle consultatif est un contrôle qu'on saute. Un échec
   renvoie à la phase 2 de `/demarrer-tache` ou à `/commit-tache`, il ne se contourne pas.

   Vérifier l'**état**, jamais qu'une commande a tourné : rien n'en garde trace, et c'est le résultat
   qui compte.

4. **Gates qualité — une seule passe.** Lancer ce que le périmètre touche, et lire la **sortie
   fraîche** avant toute affirmation :

   | Périmètre touché | Commande |
   |---|---|
   | `api/` | `make api-quality` (phpstan, cs, deptrac, phpunit) |
   | `webapp/` | `make webapp-quality` (lint, format, type-check) + `make webapp-test` |
   | Les deux | `make quality` |
   | Contrat front↔API | `make contracts-drift` |

   - **Surface d'API modifiée** : vérifier que `api/openapi.json` a bien été régénéré
     (`make dev-openapi`) et commité. La CI `api-quality` a un `git diff --exit-code` sur ce fichier
     que le `make` local **ne couvre pas** — vert en local, rouge en CI.
   - **Ne pas bloquer en avant-plan** sur la suite phpunit complète (~7 min) : la lancer en tâche de
     fond et poursuivre, ou ouvrir la PR sur les autres gates vertes et laisser la CI faire la suite
     entière — en le disant.
   - **Un rouge n'accuse pas toujours le diff** : les tests de recherche **flakent** par
     isolation/ordre, et `develop` flake aussi. Avant d'incriminer le changement, rejouer la classe
     touchée seule. De même, une autre lane qui recrée `showcase-recruiter-webapp-saas_test` en parallèle produit des
     échecs qui ressemblent à des bugs — demander si quelqu'un d'autre teste avant de conclure.
   - Diff exclusivement Markdown / documentation → aucune gate ne s'applique : le dire, ne pas
     inventer une passe verte.

5. **Pousser** sur `origin` — le fork, pas `gd` (l'organisation) :
   `git push -u origin <branche>`.

6. **Titre de PR** — `<Périmètre> : <ce que ça change>`, **en français**, sans référence de tâche.
   Le périmètre est l'espace de la branche (`Espace entreprise`, `Socle webapp`, `Outillage`…) ou
   `API`. Ce qui suit dit le **changement**, pas le moyen : « le dévoilement devient un geste », pas
   « ajout de useReveal ».

   Ce n'est **pas** un Conventional Commit, et c'est délibéré : le merge en rebase ne dépose que les
   commits, le titre ne laisse aucune trace dans l'historique et ne sert qu'à lire une liste de PR
   (cf. [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md#titre-de-pr)).
   Une PR qui porte plusieurs tâches les **énumère** au lieu d'en élire une.

7. **Corps de PR — deux étages, le détail replié.** Le dépôt n'a pas de template ; utiliser
   celui-ci :

   ```md
   ## En bref

   <deux ou trois phrases en langage courant : ce que ça change, et pourquoi ça compte.
   Pas de chemin de fichier, pas de nom de commande, pas de numéro de tâche.>

   **Ce qui compte pour la suite :** <la conséquence qu'un relecteur doit retenir même s'il
   ne déplie rien — un fait qui change une décision, un piège, une limite. Retirer la ligne
   s'il n'y en a pas.>

   <details>
   <summary><b>Le détail</b></summary>

   ## Tâches
   - `<PRÉFIXE>-NNN` — <ce qu'elle livre, et l'arbitrage qui a été fait>

   ## Vérifications
   - <gate> : <résultat lu>
   - <ce qui n'a pas été lancé, et pourquoi>

   ## Points de vigilance
   - <sécurité, performance, accessibilité, rupture de contrat — retirer la section si aucun>

   </details>
   ```

   **Le résumé dit ce que ça CHANGE, pas ce qui a été fait.** « Les coordonnées sont rares à la
   source, une promesse de profil joignable ne peut pas s'appuyer dessus » — pas « déplacé deux
   documents et corrigé trois liens ». Un relecteur qui ne lit que ces trois phrases doit déjà
   savoir s'il a une raison de se méfier.

   Le `<details>` n'est pas une coquetterie : une PR qui s'ouvre sur quarante lignes de
   justification n'est pas lue, elle est approuvée. Trois phrases visibles se lisent vraiment, et
   le pli reste à un clic pour qui veut la preuve.

   Une ligne blanche est **obligatoire** après `<summary>` et avant `</details>`, sinon GitHub rend
   le Markdown replié comme du texte brut.

8. **`gh` n'est pas authentifié ici.** Ne pas tenter `gh pr create`. Fournir à la place :
   - le titre et le corps, **prêts à coller** ;
   - le lien d'ouverture : `https://github.com/<owner>/<repo>/pull/new/<branche>`.

   L'utilisateur ouvre et merge lui-même.

9. **Rappeler le merge.** Le dépôt vise *rebase & merge* seul, mais tant que le réglage GitHub n'est
   pas appliqué, **le défaut du bouton reste *Squash*** — un clic dessus écrase toutes les tâches de
   la branche en un commit, et c'est irréparable une fois construit dessus. Choisir explicitement
   *Rebase and merge*.

10. **Rapport** : branche source et cible, tâches portées, gates lancées avec leur résultat tel quel,
    gates non lancées et pourquoi, points de vigilance restants.

## Garde-fous

- Ne jamais annoncer une gate verte sans en avoir lu la sortie fraîche.
- Ne jamais merger dans la foulée : ouvrir et merger sont deux décisions distinctes.
- Ne pas force-push une branche déjà poussée sans demande explicite.
- Une `espace/*` **n'est pas supprimée** après merge : elle est réalignée et resservira. C'est
  `/apres-pr-mergee` qui s'en charge.
- Pas de review humaine possible en solo : l'équivalent est l'auto-relecture du diff complétée par
  `/code-review`.
