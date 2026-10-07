---
description: Mener UNE tâche — positionner la branche de l'espace (crée / reprend / rebase, refuse develop), implémenter la carte, prouver le résultat, puis écrire tests et doc
argument-hint: [référence de la tâche ou espace visé — ex. ENT-025, ou « entreprise »]
allowed-tools: Bash(git:*), Bash(make:*), Bash(bun:*), Bash(docker:*), Read, Write, Edit, Glob, Grep
---

Mener la tâche : **$ARGUMENTS**

## Ce que cette commande fait, et ce qu'elle ne fait pas

Elle **mène une tâche** : elle place le dépôt sur la bonne branche, **implémente la carte**, arrête
le travail sur une preuve fraîche, et — une fois le résultat validé — écrit les tests et la
documentation. Elle s'arrête avant le commit.

Elle ne cadre pas le besoin, n'écrit pas de carte, ne commite pas, ne pousse pas. Chacun de ces
moments a sa commande, et **aucune n'empiète sur une autre** :

| Moment | Commande | Ce qu'elle possède |
| --- | --- | --- |
| Cadrer et spécifier | `/creer-tache` | Le questionnement, la carte `dev-book/tasks/` |
| Ouvrir **et mener** une tâche | **`/demarrer-tache`** | La branche, le code, la preuve, puis les tests et la doc |
| Clore une tâche | `/commit-tache` | La clôture de la carte et **un** commit |
| Livrer la lane | `/creer-pr` | Les gates qualité, le titre et le corps de PR |
| Après le merge | `/apres-pr-mergee` | La vérification du merge et le réalignement |

**Deux portes d'entrée, une seule source pour la branche.** `/creer-tache` **appelle la seule phase
1** de cette commande pour positionner la branche, et s'arrête là : elle cadre, elle ne code pas.
Lancer `/creer-tache` sur un besoin neuf suffit donc, puis revenir ici. L'appel direct de
`/demarrer-tache` enchaîne les deux phases — c'est le cas courant : on reprend une tâche, on
enchaîne la suivante, ou on revient dessus après une PR.

> **Rien ne se code avant que la carte existe.** Sans carte `dev-book/tasks/`, la phase 2 ne démarre
> pas : renvoyer vers `/creer-tache`. La carte est la spec, pas un compte rendu.

## L'invariant du cycle — UNE tâche à la fois

**Une tâche s'ouvre, se code, se commite, et seulement ensuite la suivante s'ouvre.**

Ce n'est pas une préférence de rangement. Deux tâches menées de front finissent dans les mêmes
fichiers — `webapp/base/`, les fichiers de langue, une page partagée par deux écrans —, et le
découpage a posteriori devient une chirurgie d'index : `git add -p` n'existe pas dans cet
environnement.

> **Incident du 2026-07-30.** Trois cartes (`ENT-022`, `ENT-023`, `ENT-024`) rédigées puis
> implémentées d'une traite, commit repoussé à la fin. Quatre fichiers portaient des morceaux de
> deux tâches. Le découpage a demandé de reconstruire à la main la version intermédiaire de six
> fichiers pour l'écrire dans l'index — récupérable, mais au prix d'une manœuvre qui n'aurait jamais
> dû être nécessaire.

Deux conséquences pratiques :

- **Un cadrage peut produire plusieurs cartes** — c'est légitime, et souvent souhaitable. Les
  **implémenter** à la suite ne l'est pas : revenir ici entre chaque.
- **Une demande au pluriel** (« fais tomber les tâches », « enchaîne ») demande N tâches, donc
  **N commits**. Elle n'autorise jamais à différer les commits.

> **C'est le retour ici qui coûte, pas la règle** — et `/demarrer-taches` l'outille. Elle prend une
> plage de cartes déjà cadrées du même espace et enchaîne les tours complets, commit compris, sans
> reprise de main. Elle **ne change rien à l'invariant** : cinq tâches, cinq commits, arbre vérifié
> propre entre chaque. Elle s'arrête net dès qu'une preuve n'est pas nette. Une carte qui laisse une
> décision ouverte, ou dont le résultat se juge à l'œil, reste ici — une carte à la fois.

## La tâche est nommée par l'humain, jamais choisie par l'agent

Cette commande s'ouvre sur **une référence donnée**. Elle ne va pas la chercher : un agent ne
parcourt pas `dev-book/tasks/` pour trouver quoi faire, ne propose pas la file d'attente et ne
présente pas une carte en attente comme la suite logique du travail. Le § *Ce qu'aucun agent ne fait*
du [CLAUDE.md racine](../../CLAUDE.md) porte la règle ; elle est stricte.

## Le README du dossier fait loi

**Avant d'écrire dans un dossier, lire son `README.md` s'il existe — et s'y tenir strictement.**
Le dépôt en compte vingt-quatre, co-localisés avec le code qu'ils gouvernent.

Ce n'est pas de la documentation d'accompagnement. Chacun tient deux chapitres — **Rôle et
responsabilités**, puis **Conventions techniques** — et il est **propriétaire** de ces règles pour
son périmètre : elles ne se déduisent ni du code voisin, ni du bon sens. Les plus courtes sont
formalisées dans `webapp/scripts/ci/rules.yaml`, vérifiées par `bun run quality-conventions-check`,
et **bloquent le merge**. Cf.
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md), section « README de
dossier : un contrat contrôlé ».

Trois réflexes :

- **Le plus proche gagne.** Le README du dossier modifié précise celui du dossier parent, il ne le
  contredit pas. Un doute sur une responsabilité → remonter d'un cran.
- **Lire avant, pas après.** Un README découvert au moment du commit, c'est un refactor à faire,
  pas une case à cocher.
- **Une convention qui change s'écrit là.** Si la tâche modifie une règle du dossier, son README
  part dans le **même commit** — c'est de la documentation impactée au sens de `/commit-tache`.

## Phase 1 — Positionner la branche

> C'est la seule phase que `/creer-tache` exécute. Le positionnement n'est écrit qu'**ici**.

Le tableau `espace → périmètre → préfixe` **n'est pas recopié ici** ; il se lit dans
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#les-espaces),
seule source de vérité — la précédente version de cette commande en gardait une copie, et avait
manqué l'ajout d'un espace.

Transverse : rattacher à l'espace **consommateur** — `webapp/base/` touché *pour* l'entreprise part
sur `espace/entreprise`, une page de `docs/` qui documente le contrat part sur `espace/api`.
`espace/socle-webapp` et `espace/outillage` ne prennent que ce qu'**aucun** espace ne consomme.

1. **Inventorier avant de bouger.** `git status --porcelain` et `git branch --show-current`.
   Travail non commité présent ? L'inventorier et **ne rien écraser** : s'il appartient à une autre
   tâche ou à une autre branche, le signaler et s'arrêter. Une session peut aussi avoir démarré sur
   la mauvaise branche — la photo `gitStatus` d'ouverture est périmée, c'est ici qu'on vérifie.
2. **Refuser les branches d'intégration.** Branche courante `develop` ou `main` → aucun commit
   dessus, basculer.
3. **`git fetch origin`**, puis selon l'état de la branche d'espace :
   - **inexistante** → `git switch -c espace/<espace> origin/develop`
   - **alignée sur `develop`** (aucun commit d'avance) → `git switch espace/<espace>` puis
     `git reset --hard origin/develop`
   - **avec des commits d'avance** → afficher `git log --oneline origin/develop..espace/<espace>`
     (les tâches pas encore mergées) et rebaser sur `origin/develop` avant d'empiler la suivante
4. **Vérifier qu'aucune tâche n'est en cours.** Si l'arbre porte des changements non commités qui
   appartiennent à la tâche **précédente**, elle n'est pas finie : la clore par `/commit-tache`
   avant d'ouvrir celle-ci. C'est le point de contrôle que l'invariant ci-dessus protège.
5. **Vérifier à qui appartient la carte.** Comparer le champ `**Session**` de la carte visée à l'UUID
   de la session courante — celui du chemin `/tmp/claude-<uid>/<projet>/<UUID>/scratchpad`.
   - **Identiques**, ou **champ absent** (carte antérieure à la règle) → continuer sans rien dire.
   - **Différents** → **s'arrêter et demander**. Dire quelle session l'a cadrée, et que ses décisions
     de cadrage ont été prises ailleurs. Reprendre la carte d'un autre est légitime — une session
     s'interrompt, une autre finit le travail —, mais ça se décide, ça ne se découvre pas au commit.

   > Le 2026-08-02, une session ouverte sur une question d'architecture a trouvé sept cartes non
   > commitées dans un worktree et en a implémenté trois. Deux n'avaient rien à voir avec la demande
   > initiale, et tout a dû être défait. Rien, à aucun moment, n'avait signalé qu'elles changeaient
   > de main.

> ⚠️ Ne **jamais** créer une branche nommée `espace` tout court, ni un segment sous une branche
> existante (`espace/api/fix`) : Git interdit qu'une réf soit à la fois un fichier et un dossier
> (`cannot lock ref … exists`).

**Sortie de phase 1** — trois lignes : la branche et son état (créée / reprise / réinitialisée /
rebasée), les tâches déjà empilées sur la lane et pas encore mergées, et la carte de tâche visée, ou
le renvoi vers `/creer-tache` si elle n'existe pas encore. **Un appel venu de `/creer-tache`
s'arrête ici.**

## Phase 2 — Implémenter

1. **Relire la carte, puis les README du périmètre.** La carte est la spec : *Périmètre*, *Hors
   périmètre*, *Décisions de cadrage*, *Règles et cas limites*, *Définition de terminé*. Les README
   des dossiers touchés font loi — cf. la section ci-dessus, et les lire **avant**, pas au moment du
   commit.

2. **Coder, et rien d'autre.** Diff minimal, aucune dépendance ajoutée sans justification, aucun
   refactor hors périmètre. Ce qui sort de la lane se **nomme** et s'arrête : ni le code, ni la carte
   d'une autre lane.
   - **Chemins absolus** vers le dossier de travail, à chaque commande : le shell repart de la racine
     du dépôt entre deux appels, et un chemin relatif édite l'arbre principal.
   - **Aucune gate ici.** La *vérification comportementale* — requête réelle, logs, rendu, capture,
     config résolue — est libre et attendue à chaque changement. Les *quality gates* appartiennent au
     point de contrôle.

3. **S'arrêter sur une preuve fraîche, et attendre le verdict.** L'implémentation ne file pas d'une
   traite jusqu'au commit.
   - Une preuve est un **fait observé** : une capture, une réponse HTTP, une sortie lue. Pas « testé
     manuellement », et jamais une suite qualité présentée comme preuve de justesse.
   - **Les gates ne voient pas tout** : le CSS mort d'`ENT-024` sortait vert partout et n'est apparu
     qu'en capture. C'est pour ça que le verdict est humain.
   - **Verdict rouge → corriger et re-présenter.** Ni tests ni documentation ne s'écrivent avant le
     vert : couvrir et documenter un code que la relecture va faire bouger, c'est l'écrire deux fois.

4. **Écrire les tests — après la validation.**
   - Nature attendue : celle qu'annonce la ligne `**Tests** :` de la carte.
   - **Exemption** seulement si la tâche ne produit aucun code exécutable, et motivée dans la carte.
     Jamais d'auto-exemption sur du code applicatif : si le test est impossible en l'état, le dire —
     la décision appartient à l'utilisateur, et elle se trace dans la carte.
   - **Exception TDD** : une carte qui annonce un cycle RED-GREEN écrit ses tests **avant** le code,
     à l'étape 2 — le test y est le moteur, pas la couverture.
   - Jouer les tests **impactés** (quelques secondes) ; jamais la suite complète en avant-plan.

5. **Mettre la documentation à jour — après la validation.** Les pages que le diff **déclenche**,
   celles que la carte liste en *Documentation impactée*, et le `README.md` d'un dossier dont la
   convention change. Le tableau des déclencheurs vit dans `/commit-tache` et n'est pas recopié ici.
   - Surface d'API modifiée → `make dev-openapi` : c'est du **contenu de commit**, pas une gate.
   - Aucun déclencheur ne s'applique → le dire en une ligne, ne pas se taire.

6. **Écart au cadrage.** Si l'implémentation a tranché autrement que la carte, corriger la carte
   **maintenant** et dire pourquoi dans ses *Décisions de cadrage*. Sinon elle ment, et c'est elle
   qui reste.

## Sortie

Quatre lignes : ce qui a été implémenté, la preuve présentée et son verdict, les tests et les pages
de documentation écrits, puis le renvoi vers `/commit-tache`.

Ne rien committer, ne rien pousser : le commit appartient à `/commit-tache`, qui vérifiera une
dernière fois que la carte, les tests et la doc sont bien là.
