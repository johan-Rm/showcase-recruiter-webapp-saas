# TASK 129 — workflow : réalignement des lanes après le merge d'une PR

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25 — commande `/apres-pr`.
- **Type** : Process / outillage — aucune ligne de code applicatif.
- **Lié à** : `126` (workflow en lanes, dont cette tâche comble l'angle mort).
- **Réf** : `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md`

## Contexte

La tâche `126` a posé les cinq lanes d'espace et le rebase & merge, et outillé le **début**
(`/demarrer-tache`) et le **milieu** (`/commit-tache`) du cycle. L'**après-merge** n'était couvert
par rien.

Or c'est le moment le plus piégeux du modèle :

- le rebase **réécrit les SHA**, donc une lane mergée n'est jamais réutilisable telle quelle ;
- les **quatre autres lanes** divergent dès qu'un commit arrive sur `develop`, en silence. Le
  conflit ne se manifeste que deux ou trois tâches plus tard, quand il est cher à résoudre ;
- tant que `allow_squash_merge=false` n'est pas appliqué sur le dépôt, un clic sur le bouton par
  défaut écrase les N tâches d'une lane en un commit — sans que rien ne le signale.

## Objectif

Remettre les cinq lanes d'aplomb en une passe après chaque merge, sans jamais détruire de travail
non mergé.

## Décisions

### Vérifier le merge par patch-id, pas par SHA

`git cherry origin/develop <lane>` compare par **patch-id** : il retrouve les commits malgré la
réécriture opérée par le rebase. Une comparaison de SHA conclurait à tort que rien n'est mergé.

### Alerter sur un squash accidentel

Si le contenu de la lane est sur `develop` mais que les N sujets ont fusionné en un seul commit,
la PR a été mergée en squash. C'est réparable tant que rien n'a été construit dessus, et plus
après : la détection doit être immédiate, pas déduite plus tard d'un historique confus.

### Réaligner seulement ce qui est au repos

« Rebaser toutes les branches » détruit du travail si on l'applique sans distinguer les cas. Quatre
états, testés dans cet ordre, le premier qui s'applique gagnant :

| État | Action |
|---|---|
| Checked-out avec un arbre sale | ne rien faire — un humain ou un agent travaille dessus |
| Poussée avec des commits d'avance (PR ouverte) | ne rien faire — un rebase imposerait un force-push |
| Aucun commit d'avance | `git branch -f <lane> origin/develop` — fast-forward pur, sans checkout |
| Commits d'avance, non poussée | `git rebase --onto origin/develop <ancienne-base> <lane>` |

### Ne jamais supprimer une lane

Elle est réalignée et resservira, conformément à `126`.

## Livré

`.claude/commands/apres-pr.md` → `/apres-pr [lane]`.

## Critères d'acceptation

- [x] Refuse de s'exécuter sur un arbre sale.
- [x] S'arrête si la lane n'est pas intégralement retrouvée sur `develop`.
- [x] Signale un merge en squash.
- [x] Met `develop` à jour sans checkout.
- [x] Réaligne les lanes au repos, laisse les autres et explique pourquoi.
- [x] Aucun force-push automatique, aucun `reset --hard` sur des commits non mergés.
