# TASK OPS-001 — workflow : un compteur de tâches par lane

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — convention écrite, deux commandes projet alignées.
- **Type** : Process / documentation — aucun changement de comportement.
- **Réf** :
  - task 157 (la renumérotation de 151-153, et la décision inverse qu'elle portait)
  - task 126 (les lanes d'espace, et le point « transverse pur » qu'elle laissait ouvert)
  - `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md`

## Contexte

La task 157 a réparé trois numéros attribués deux fois (151, 152, 153) et conclu, le jour même,
qu'il ne fallait **pas** changer de système : la collision était rare et se réparait. L'inventaire
complet en trouve **trois autres**, jamais traités — `035`, `081`, `146` — soit **six collisions**
sur 173 tâches. Les suffixes `025a`, `026b`, `055a`, `094a` n'en sont pas : ce sont des sous-tâches
volontaires.

Six, ce n'est plus l'accident isolé qu'on répare de temps en temps : c'est le régime permanent
d'une séquence unique partagée par des lanes qui avancent en parallèle, et le taux ne peut que
monter à mesure que les lanes se multiplient.

Le coût ne se voit pas dans `dev-book/tasks/` — les slugs diffèrent, les fichiers cohabitent. Il se
voit dans les **214 fichiers source** qui citent `— task NNN` en docblock : un numéro doublonné y
désigne deux tâches, et le lecteur qui suit la référence tombe sur la mauvaise.

## Décisions

### Le préfixe est celui de la lane, pas celui du dossier

Chaque lane possède son préfixe et son compteur, et personne d'autre n'y écrit :

| Lane | Périmètre | Préfixe |
| --- | --- | --- |
| `feature/api` | `api/` | `API` |
| `feature/espace-entreprise` | `webapp/spaces/entreprise/` | `ENT` |
| `feature/espace-candidat` | `webapp/spaces/candidat/` | `CAN` |
| `feature/espace-admin` | `webapp/spaces/admin/` | `ADM` |
| `feature/espace-public` | `webapp/spaces/public/` | `PUB` |
| `feature/socle-webapp` | `webapp/base/`, `webapp/shared/` | `WEB` |
| `feature/outillage` | `docs/`, `dev-book/cadrage/`, `.claude/`, `.github/`, infra | `OPS` |

Indexer le préfixe sur le **dossier touché** aurait ramené le problème par la porte de derrière :
deux lanes qui modifient `webapp/base/` auraient créé chacune un `WEB-007`. En l'indexant sur la
**lane qui commite**, un préfixe n'a qu'un seul écrivain — la collision devient impossible, pas
simplement improbable.

C'est aussi ce qui supprime l'arbitrage : au moment de nommer la tâche, la lane est déjà choisie.
La règle de rattachement, elle, ne bouge pas — le transverse suit toujours l'**espace
consommateur**, `webapp/base/` touché pour l'entreprise reste sur `feature/espace-entreprise`.

### Deux lanes de plus, pour ce qui n'a pas de consommateur

`feature/socle-webapp` et `feature/outillage` closent le point que la task 126 laissait ouvert : le
socle travaillé pour lui-même et le process pur passaient jusqu'ici par une `feature/*` courte, sans
règle. Elles ne prennent que ce qu'**aucun** espace ne consomme.

### Le numéro suivant se lit sur l'arbre courant

`ls dev-book/tasks/<PRÉFIXE>-*.md | tail -1`, incrémenté sur trois chiffres. Une commande, aucune
branche distante à consulter — c'est le geste que la task 157 recommandait (« regarder ce que
`develop` porte, et ce que les autres worktrees ont déjà commité ») et qui, en pratique, n'était
jamais fait : trop coûteux pour une tâche de plus.

### Les tâches 001 à 164 sont gelées

Elles gardent leur numérotation séquentielle. Les renuméroter aurait touché 214 fichiers source,
`api/openapi.json` et les types TS régénérés — pour un corpus homogène et des messages de commit
déjà mergés qui, eux, citeraient des numéros morts.

Les deux conventions cohabitent donc, et c'est lisible sans documentation : un identifiant
**purement numérique** désigne une tâche d'avant le 2026-07-27, un identifiant **préfixé** une tâche
d'après. Les doublons `035`, `081` et `146` restent en l'état — ils ne gênent que la lecture
rétrospective, et le système qui les produisait n'existe plus.

## Livré

- `git-workflow.md` : table des lanes portée à sept avec sa colonne préfixe, section
  « Numérotation des tâches — un compteur par lane », note de gel du legacy.
- `/demarrer-tache` : sept lanes, et annonce du numéro que prendra la tâche dès le positionnement.
- `/commit-tache` : nom de fichier préfixé, règle du compteur par lane, mention `— task <PRÉFIXE>-NNN`
  dans le titre de commit.
- Cette tâche, première du compteur `OPS`.

## Reste à faire (hors périmètre de cette tâche)

- **Créer les branches** `feature/socle-webapp` et `feature/outillage` au premier travail qui les
  concerne — inutile de les ouvrir à vide.
- **Doublons `035`, `081`, `146`** — jamais réparés, contrairement à 151-153. À traiter comme la
  task 157 l'a fait, mais **seulement** si une référence ambiguë gêne réellement la lecture d'un
  fichier source : la renumérotation coûte plus cher que le doublon tant que personne ne le suit.
