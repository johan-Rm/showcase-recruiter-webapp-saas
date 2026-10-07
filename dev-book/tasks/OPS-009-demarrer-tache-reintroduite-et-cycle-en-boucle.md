# TASK OPS-009 — `/demarrer-tache` réintroduite, et le cycle devient une boucle

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Outillage — commandes projet et documentation du workflow.
- **Tests** : sans objet — aucun code exécutable. La vérification est la relecture des cinq
  commandes, et l'absence de recouvrement entre elles.
- **Réf** : `OPS-007` (le cycle outillé, qui avait supprimé cette commande) · `ENT-022` / `ENT-023` /
  `ENT-024` (l'incident qui a motivé la carte) ·
  [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md)

## Contexte

`OPS-007` a outillé le cycle de tâche en quatre commandes et supprimé `/demarrer-tache`, dont le
rôle — positionner la branche — avait été absorbé par `/creer-tache`.

Le jour même, l'agent a enchaîné **trois cartes** (`ENT-022`, `ENT-023`, `ENT-024`) : cadrage
commun, trois cartes écrites d'un coup, trois implémentations à la suite, et un seul commit possible
à la fin. Quatre fichiers partagés — deux pages, un composant, les trois fichiers de langue —
portaient des morceaux de deux tâches. Le découpage a demandé de **reconstruire à la main la version
intermédiaire de six fichiers** pour l'écrire directement dans l'index : `git add -p` n'existe pas
dans cet environnement.

La règle, elle, était **déjà écrite** au bon endroit — `/commit-tache` dit noir sur blanc « si le
working tree couvre deux tâches, il faut deux commits ». Elle n'a rien empêché, et c'est le vrai
enseignement : **une règle écrite dans la commande de fin ne protège pas d'une dérive qui commence
au début**. Rien ne s'interposait entre deux tâches, parce qu'aucune commande n'était appelée là.

## Périmètre

- Réintroduire `.claude/commands/demarrer-tache.md` comme **porte d'entrée d'une tâche** : branche,
  et contrat du cycle.
- Y **déplacer** la procédure de positionnement de branche, que `/creer-tache` portait.
- Poser dans les cinq commandes un partage des rôles explicite, sans recouvrement.
- Refermer la boucle : `/commit-tache` renvoie vers `/demarrer-tache` pour la tâche suivante.
- Aligner les deux pages de doc qui énumèrent les commandes.

## Hors périmètre

- **Un hook** qui garantirait mécaniquement la règle plutôt que par écrit. Envisagé, écarté pour
  l'instant : on commence par rendre l'instruction impossible à manquer, et on outille si l'erreur
  se reproduit.
- Les autres commandes du cycle (`/creer-pr`, `/apres-pr-mergee`), qui ne changent pas.

## Décisions de cadrage

### La commande revient, mais pas à son ancienne place

Elle n'est pas restaurée telle quelle. L'ancienne version **recopiait le tableau des espaces** et
avait fini par manquer l'ajout d'un espace — c'est ce défaut qui avait justifié sa suppression. La
nouvelle **renvoie** vers `git-workflow.md`, seule source de vérité du tableau.

Ce qu'elle gagne en échange, et qui n'existait nulle part : le **contrat du cycle**. C'est la
commande qu'on lance au début de chaque tâche, donc le seul endroit d'où l'on peut interrompre un
enchaînement.

### Le positionnement de branche n'est écrit qu'une fois

Il quitte `/creer-tache`, qui l'**appelle** désormais. Deux commandes qui décrivent la même
manœuvre finissent par la décrire différemment — c'est exactement ce qui s'était produit avec le
tableau des espaces.

### Chaque commande déclare ce qu'elle possède

`/demarrer-tache` porte le tableau des cinq moments et de leurs responsabilités. Les autres n'en
gardent qu'un renvoi : le recouvrement se voit alors d'un coup d'œil, au lieu de se découvrir à
l'usage.

### Écrire plusieurs cartes reste permis

Un besoin se découpe souvent en trois, et le cadrage a raison de le voir. Ce qui est interdit, c'est
d'**implémenter** à la suite. La garde est posée dans la sortie de `/creer-tache`, là où les trois
cartes viennent d'être écrites.

## Règles et cas limites

- Une demande au pluriel — « fais tomber les tâches », « enchaîne » — demande N tâches, donc
  **N commits**. Elle n'autorise jamais à différer les commits.
- `/demarrer-tache` **refuse** de s'exécuter par-dessus une tâche non finie : un arbre sale qui
  appartient à la tâche précédente est un signal d'arrêt, pas un état de départ.
- Elle ne suppose pas que la carte existe : sans carte, elle renvoie vers `/creer-tache`.
- Le travail non commité de l'utilisateur trouvé dans l'arbre est **inventorié et jamais écrasé**.

## Documentation impactée

- [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — l'encart
  d'outillage passe de quatre à cinq commandes, et dit que le cycle boucle.
- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — même liste, plus
  la raison pour laquelle le cycle est une boucle et non une liste.

## Définition de terminé

- [x] `/demarrer-tache` existe, sans copie du tableau des espaces.
- [x] La procédure de branche n'est écrite qu'à un seul endroit ; `/creer-tache` l'appelle.
- [x] Les cinq moments du cycle et leurs responsabilités sont énumérés à un seul endroit.
- [x] `/commit-tache` referme la boucle vers `/demarrer-tache`.
- [x] `/creer-tache` interdit d'implémenter plusieurs cartes à la suite.
- [x] Les deux pages de doc annoncent cinq commandes.
