# TASK OPS-016 — Le README du dossier fait loi

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — `/demarrer-tache` impose désormais la lecture du `README.md`
  du dossier avant d'y écrire.
- **Type** : Outillage — commandes projet.
- **Tests** : sans objet — aucun code exécutable. La vérification est la relecture de la commande et
  le contrôle des trois chemins qu'elle cite.
- **Réf** : `OPS-009` (la commande et le contrat du cycle) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — section « README
  de dossier : un contrat contrôlé »

## Contexte

Le dépôt porte **vingt-quatre `README.md` de dossier**, co-localisés avec le code qu'ils
gouvernent. Ce ne sont pas des documents d'accompagnement : `ai-workflow.md` les décrit comme un
**contrat contrôlé**, en deux chapitres — Rôle et responsabilités, puis Conventions techniques — et
les conventions courtes y sont adossées à une règle CI qui bloque le merge.

Cette qualification existait donc déjà, mais **à un endroit qu'un agent ne lit pas forcément avant
de coder**. Le cycle de tâche, lui, ne la mentionnait nulle part : ni `/demarrer-tache`, qui pose le
contrat avant la première ligne de code, ni `/commit-tache`, qui ne parle des README que dans sa
table de documentation impactée — c'est-à-dire au moment où il est trop tard pour en tenir compte.

Un README découvert au commit ne fait pas gagner de temps : il désigne un refactor.

## Périmètre

- Ajouter à `.claude/commands/demarrer-tache.md` une section qui impose la lecture stricte du
  `README.md` du dossier visé, avant d'y écrire.

## Hors périmètre

- **Le contenu des vingt-quatre README**, inchangé.
- **La règle CI** `quality-conventions-check` et `webapp/scripts/ci/rules.yaml`, cités mais pas
  touchés.
- **`/commit-tache`**, dont la table de documentation impactée mentionne déjà le README du dossier
  modifié. Le manque était en amont, pas à la clôture.
- **Le doublon `create-pr` (skill globale) / `/creer-pr` (commande projet)**, identifié pendant la
  même session et laissé à l'arbitrage de l'utilisateur.

## Décisions de cadrage

### La règle vit dans `/demarrer-tache`, pas ailleurs

C'est la commande qui « pose le contrat du cycle avant la première ligne de code ». Une règle de
lecture n'a de valeur qu'avant l'écriture ; placée dans `/commit-tache`, elle aurait rejoint la
liste des choses qu'on constate trop tard — le défaut exact que `OPS-009` avait diagnostiqué pour
l'invariant « une tâche à la fois ».

### La section renvoie, elle ne recopie pas

Elle nomme les deux chapitres attendus et la règle CI, puis pointe vers `ai-workflow.md` qui reste
propriétaire de la description complète. `OPS-009` avait retenu la leçon inverse : l'ancienne
version de cette commande recopiait le tableau des espaces, et avait fini par manquer l'ajout d'un
espace.

### Trois réflexes plutôt qu'une injonction

« Lire le README » se comprend sans se pratiquer. Les trois réflexes tranchent les cas réels : quel
README gagne quand il y en a deux, à quel moment le lire, et quoi faire quand la tâche change la
convention elle-même.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code. Les trois chemins cités par la nouvelle
section ont été vérifiés dans l'arbre avant d'être écrits :

- `git ls-files | grep -E "rules\.ya?ml$"` → `webapp/scripts/ci/rules.yaml` existe.
- `webapp/package.json:17` → `"quality-conventions-check": "bun run scripts/ci/verify-conventions.ts"`.
- `ai-workflow.md:58` → le titre exact de la section est « README de dossier : un contrat contrôlé ».
- `git ls-files | grep -cE "README\.md$"` → **24**, le chiffre annoncé par la section.

## Livré

- `.claude/commands/demarrer-tache.md` **(M)** — nouvelle section « Le README du dossier fait loi »,
  insérée entre l'invariant du cycle et la procédure, soit avant toute écriture.

## Définition de terminé

- [x] `/demarrer-tache` impose la lecture du README du dossier avant d'y écrire.
- [x] La section renvoie vers `ai-workflow.md` au lieu de recopier sa description.
- [x] Les chemins et le décompte cités sont vérifiés dans l'arbre.
- [x] Le cas de deux README concurrents est tranché (le plus proche gagne).
