---
description: Transformer le travail en cours en UN commit pour UNE tâche — carte clôturée, tests écrits et doc à jour dans le même commit ; les gates qualité, elles, tournent avant la PR
argument-hint: [référence ou slug de la tâche, si connu — ex. ENT-012]
allowed-tools: Bash(git:*), Bash(make:*), Read, Write, Edit, Glob, Grep
---

Commiter le travail en cours comme **une tâche** : $ARGUMENTS

## Invariant

**Un commit = une tâche.** La carte `dev-book/tasks/<PRÉFIXE>-NNN-<slug>.md` a été écrite **avant**
le code par `/creer-tache` : ici on la **clôture**, et elle entre dans le **même** commit que le
code qu'elle spécifiait.

Trois choses conditionnent le commit, et aucune n'est négociable : la carte à jour, **les tests
écrits**, **la documentation impactée à jour**. Un commit qui n'a pas les trois n'est pas prêt.

Les trois ont été **produites en phase 2 de `/demarrer-tache`**, après validation du résultat. Ce
qu'on fait ici est un **dernier filet** : on vérifie qu'elles sont là, on ne les découvre pas.

Le **squash est désactivé sur le dépôt** : ce commit atterrira tel quel sur `develop` en rebase.
Il n'y a pas de session de rattrapage — pas de commit `wip`, pas de `fixup`, message définitif dès
maintenant. Si le working tree couvre **deux tâches**, il faut **deux commits** : ne jamais les
fusionner pour aller plus vite.

## Procédure — point de contrôle

1. **Branche d'abord.** `git branch --show-current`. Si `develop` ou `main` : STOP, aucun commit
   direct (cf. `/creer-tache`). La photo `gitStatus` d'ouverture de session est périmée :
   revérifier ici, systématiquement.
2. **Inventaire du diff réel** : `git status --porcelain` + `git diff --stat` + `git diff --cached --stat`.
   Lire le diff, pas seulement les noms de fichiers.
3. **Délimiter le périmètre.**
   - Regrouper les fichiers par tâche. Annoncer le découpage avant de stager.
   - Tout changement **hors périmètre** — y compris du travail de l'utilisateur trouvé dans
     l'arbre — reste **hors du commit** et doit être signalé nommément.
4. **Clôturer la carte de tâche.** Elle existe déjà (`/creer-tache`) : la relire et la compléter.
   - `**Statut**` → `✅ fait le <AAAA-MM-JJ>` suivi d'une ligne de ce qui a réellement été livré.
   - Section **Vérification** : les **preuves fraîches** — requête réelle, sortie de test, rendu,
     config résolue. Pas « testé manuellement », mais ce qui a été observé.
   - Section **Livré** : les fichiers et ce qu'ils changent.
   - Section **Reste à faire (hors périmètre)** s'il subsiste quelque chose — c'est ce qui évite
     qu'un oubli devienne invisible.
   - Cocher la **Définition de terminé**. Une case qui ne peut pas être cochée signifie que la
     tâche n'est pas finie : le dire, ne pas commiter par-dessus.
   - **Écart au cadrage** : si l'implémentation a tranché autrement que la carte, corriger la carte
     et dire pourquoi dans les *Décisions de cadrage*. La carte ment sinon, et c'est elle qui reste.

   > **Cas dégradé** — code écrit sans passer par `/creer-tache` : rédiger la carte maintenant, en
   > suivant la forme des voisines et la règle de numérotation de `/creer-tache` (compteur par
   > espace, `git log --all`, jamais `ls`, séquence nue `001`-`164` gelée). Le signaler : une tâche
   > cadrée après coup n'a pas eu de cadrage.

5. **Tests écrits — bloquant.** Le changement est couvert par des tests, **écrits dans ce commit**.
   Ils viennent de la phase 2 de `/demarrer-tache` ; s'ils manquent ici, c'est cette phase qui n'est
   pas allée au bout — y retourner plutôt que commiter sans.
   - Nature attendue : celle annoncée par la ligne `**Tests** :` de la carte.
   - **Exemption** — uniquement quand la tâche ne produit **aucun code exécutable** (documentation,
     cadrage, maquette, contenu, outillage éditorial). Elle s'écrit dans la carte :
     `**Tests** : sans objet — <raison>`. La raison est explicite ; « pas nécessaire » n'en est pas
     une.
   - **Ne jamais s'auto-exempter sur du code applicatif.** Si le test est coûteux ou impossible en
     l'état, s'arrêter et le dire — la décision de commiter sans test appartient à l'utilisateur,
     et elle est tracée dans la carte.
   - Les tests **impactés** sont joués (quelques secondes) ; jamais la suite phpunit complète
     (~7 min) en avant-plan.

6. **Documentation à jour — bloquant.** Une convention qui change et une doc qui ne bouge pas, c'est
   une doc qui devient fausse. Elle a été écrite en phase 2 de `/demarrer-tache` ; vérifier ici que
   les seules pages **déclenchées** par le diff y sont, et les embarquer dans le même commit :

   | Ce que touche le commit | À mettre à jour |
   |---|---|
   | Surface d'API : route, DTO, payload, code de statut | `2.architecture/api-routes.md`, `api-conventions.md` **+ `api/openapi.json` régénéré** |
   | Entité, migration, contrainte d'intégrité | `2.architecture/data-model.md`, `data-integrity.md` |
   | Contrat front↔API, types générés | `2.architecture/data-contracts.md` |
   | Composable · store · service webapp | `composables-standard.md` · `stores-standard.md` · `services-standard.md` |
   | Frontière de couche, structure `api/` | `responsibility-boundaries.md`, `api-structure.md` |
   | Tokens, composants, charte | `4.design/design-system.md`, `brand-guidelines.md` |
   | Docker, Make, environnements | `2.architecture/docker-and-make.md` |
   | i18n, vocabulaire métier | `i18n.md`, `profile-processing.md` |
   | Intégration Jarvi | `2.architecture/jarvi-integration.md` |
   | Branches, commits, commandes, worktrees | `1.getting-started/git-workflow.md`, `parallel-work.md`, `ai-workflow.md` |
   | Tests, CI, gates, revue | `3.quality/testing-and-ci.md`, `api-testing.md`, `code-review-checklist.md` |
   | Convention d'un dossier de code | le `README.md` de ce dossier (+ `webapp/scripts/ci/rules.yaml` si la règle est automatisable) |

   Chemins relatifs à `docs/showcase-recruiter-webapp-saas/content/`.

   - **`dev-book/cadrage/` est hors périmètre** : écrit pour les clients, il suit son propre rythme
     et n'a pas à bouger à chaque commit.
   - **Pas de duplication** : la carte de tâche liste la doc impactée, elle ne la remplace pas.
   - Aucun déclencheur ne s'applique → le dire en une ligne, ne pas se taire.

7. **Pas de gates qualité ici.** `make quality` et la suite de tests complète tournent **une seule
   fois, avant d'ouvrir la PR** — pas à chaque commit : une branche empile N tâches pour un seul
   passage en CI.

   > `make dev-openapi` (point 6) n'est pas une gate mais du **contenu de commit** : la CI
   > `api-quality` a un `git diff --exit-code` sur `api/openapi.json` que le `make` local ne couvre
   > pas — vert en local, rouge en CI.

   La *vérification comportementale* (requête réelle, logs, rendu) reste libre et attendue à
   chaque changement : c'est elle qui prouve que la tâche est faite.
8. **Staging par chemins explicites.** `git add <chemin1> <chemin2> …`, jamais `git add -A` ni
   `git add <dossier>` : l'utilisateur travaille en parallèle et un fichier peut apparaître entre
   le `status` et le `add`.
9. **Relire avant de figer** : `git diff --cached --name-only` puis `git diff --cached`. Un seul
   fichier inattendu → le désindexer. Vérifier aussi que **la carte, les tests et la doc** sont bien
   dans l'index — un commit qui n'embarque que du code a raté les points 4 à 6.
10. **Message de commit** — cf. [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) :
   - titre `type(scope): description — task <PRÉFIXE>-NNN` — impératif, **minuscule**, **sans point
     final**, ≤ 100 caractères (commitlint en CI). Avec la ligne `**Type** :` de la carte de tâche,
     ce `type` est le **seul** endroit où la nature du travail est écrite : le nom de branche ne dit
     que l'espace ;
   - corps en puces : ce qui change et **pourquoi** ;
   - section finale `Fichiers:` avec `(+)` ajout · `(M)` modification · `(D)` suppression ;
   - **aucun trailer `Co-Authored-By`**.
11. **Reste à faire.** S'il restait une deuxième tâche dans l'arbre, le rappeler ; ne pas
    enchaîner sans accord.

**La tâche suivante s'ouvre par `/demarrer-tache`**, jamais en enchaînant le code directement : ce
retour est le point où le cycle se referme, et c'est lui qui empêche deux tâches de finir dans les
mêmes fichiers.

> Sur un lot de cartes déjà cadrées, `/demarrer-taches` fait ce retour à ta place — elle repasse
> **par cette commande** à chaque tour, puis vérifie que l'arbre est propre avant d'ouvrir le
> suivant. Rien n'est raccourci ici : un commit par tâche, message définitif, carte clôturée.

Ne pas pousser ni ouvrir la PR ici : c'est le travail de `/creer-pr`. **C'est là, et seulement là,
que `make quality` et la suite de tests complète tournent**, en une passe pour toute la branche.
