# TASK OPS-025 — une carte dit de quelle session elle vient

- **Créée le** : 2026-08-02.
- **Session** : `686c7d26-4f0c-4649-a3cc-21504295097e`.
- **Statut** : ✅ fait le 2026-08-02 — une carte dit de quelle session elle vient, `/demarrer-tache`
  s'arrête quand elle vient d'ailleurs, et un agent n'ouvre plus aucune tâche de sa propre
  initiative.
- **Type** : Gouvernance IA — marqueur de provenance sur les cartes de tâche.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable, elle modifie deux commandes
  Markdown et une page de documentation. La preuve est l'application de la règle à elle-même : cette
  carte porte le champ qu'elle introduit.
- **Réf** : [creer-tache.md](../../.claude/commands/creer-tache.md) — le gabarit de carte ·
  [demarrer-tache.md](../../.claude/commands/demarrer-tache.md) — phase 1 ·
  [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md) —
  « `dev-book/tasks/` : un fichier par tâche, un dossier pour tous »

## Contexte

**Rien ne dit qui a écrit une carte.** Le dossier `dev-book/tasks/` est partagé par conception, et
une carte y vit en fichier **non suivi** entre le moment où elle est cadrée et celui où sa tâche est
commitée. Pendant cette fenêtre, elle est visible de tous et attribuée à personne.

L'incident qui l'a montré, le 2026-08-02 : une session ouverte sur une question d'architecture — qui
possède la balise de région d'un composant — a trouvé sept cartes non commitées dans le worktree
entreprise (`ENT-029` à `ENT-035`), écrites par une **autre** session. Elle en a implémenté trois.
Deux d'entre elles n'avaient rien à voir avec la demande initiale, et l'une portait des décisions de
cadrage que la session exécutante n'avait jamais prises — elle a dû les reprendre en cours de route,
puis tout défaire.

Une huitième carte (`ENT-037`) est apparue **pendant** l'implémentation, écrite par une session
concurrente dans le même dossier. Rien ne la distinguait des autres.

Le compteur par espace protège les **noms** : deux agents ne créent pas `ENT-030` en même temps. Il
ne protège pas les **intentions** : rien n'empêche un agent de reprendre la carte d'un autre en
croyant continuer son propre travail.

## Périmètre

- Un champ **`- **Session** :`** dans le gabarit de carte de `/creer-tache`, juste après `Créée le`.
- La **provenance de l'identifiant** documentée dans la même commande : il se lit dans le chemin du
  dossier de travail temporaire de la session.
- Un **contrôle en phase 1 de `/demarrer-tache`** : si la carte visée porte une session différente de
  la session courante, le signaler et demander avant d'implémenter.
- Le **pourquoi** dans `parallel-work.md`, section `dev-book/tasks/`, à côté du compteur par espace
  qu'il complète.
- **L'interdiction d'initiative** dans le `CLAUDE.md` racine, § *Ce qu'aucun agent ne fait*, et son
  rappel en tête de `/demarrer-tache`.

## Hors périmètre

- **Reprendre les cartes existantes.** Une carte déjà écrite n'a pas de session connue, et lui en
  inventer une serait pire que l'absence. Le champ vaut pour les cartes **neuves** ; les anciennes
  restent sans, ce qui se lit très bien comme « antérieure à la règle ».
- **Toute trace ailleurs que dans la carte** — ni dans le commit, ni dans la PR, ni dans un fichier
  d'état. Le message de commit porte la nature du travail, pas sa provenance ; et un fichier d'état
  partagé serait un second dossier à faire diverger.
- **Interdire de reprendre la carte d'une autre session.** Ça arrive légitimement — un agent finit ce
  qu'un autre a cadré, et c'est parfois exactement ce qu'on veut.

## Décisions de cadrage

### Ce qui est strict, c'est l'initiative — pas la reprise

Le cadrage initial s'arrêtait au marqueur et à son contrôle. **Il ratait la cause.** Dans l'incident,
l'agent n'a pas pris les cartes en douce : il les a **proposées**, et l'humain a ensuite lancé les
commandes. Un contrôle au moment d'implémenter arrive donc trop tard — la brèche s'ouvre au moment où
la file d'attente est présentée comme « la suite ».

Deux règles de portée différente, et il faut les deux :

- **stricte, sur l'initiative** — une session ne travaille que sur ce qui lui a été demandé dans
  cette session. Elle ne parcourt pas `dev-book/tasks/` pour trouver quoi faire, ne propose rien, et
  s'arrête en le disant quand le travail demandé est fini ;
- **gardée, sur la reprise** — si l'humain nomme explicitement une carte d'une autre session, c'est
  permis, mais annoncé et confirmé une fois.

Une interdiction **absolue** de toucher à la carte d'un autre a été écartée : elle aurait refusé des
ordres explicites et rendu impossible un relais légitime — une session s'interrompt, une autre finit
le travail. Elle aurait aussi poussé à recréer une carte plutôt qu'à reprendre celle qui existe.

### Une session se clôt par un inventaire, pas par « la suite ? »

Ajouté en cours de route, et c'est la contrepartie de l'interdiction d'initiative : sans elle, la
règle reposerait sur la seule vigilance de l'humain. Le dernier message d'une session dit ce qui a
été **livré**, ce qui reste **ouvert** dans l'arbre, et ce qui a été **nommé au passage** et
appartient à d'autres sessions. Un inventaire, pas une proposition.

L'une empêche de proposer, l'autre oblige à conclure. Séparées, chacune se contourne : un agent qui
ne propose rien mais laisse la session ouverte invite à demander « et ensuite ? », ce qui ramène
exactement au point de départ.

### Un avertissement, pas un verrou

Le contrôle **demande**, il ne refuse pas. Reprendre la carte d'une autre session est légitime : une
session s'interrompt, une autre finit le travail. Ce qui manquait n'était pas l'interdiction, c'était
**le fait de le savoir** — dans l'incident, personne, ni l'agent ni l'humain, n'a vu que trois cartes
changeaient de main.

Un verrou aurait par ailleurs un effet pervers : il pousserait à recréer une carte plutôt qu'à
reprendre celle qui existe, et le dossier gagnerait des doublons.

### L'identifiant se lit dans le chemin du scratchpad

Chaque session reçoit un dossier de travail temporaire dont le chemin contient son UUID :

```
/tmp/claude-<uid>/<projet>/<UUID-de-session>/scratchpad
```

C'est la seule source disponible sans outil supplémentaire, et elle est donnée à l'agent au
démarrage. La commande le dit explicitement plutôt que de laisser deviner — un agent qui ne sait pas
où trouver l'identifiant écrira « inconnue » et le champ mourra en trois cartes.

### Le champ va dans l'en-tête, pas dans une section

L'en-tête d'une carte porte déjà ce qui la situe — date, statut, type, tests, références. La
provenance est de cette nature : on la lit en diagonale, on ne la cherche pas. Une section dédiée
suggérerait qu'il y a quelque chose à y raconter.

## Règles et cas limites

- **Format** : l'UUID tel quel, entre accents graves. Pas de nom d'agent, pas d'horodatage — ils se
  périment ou se dupliquent, l'UUID non.
- **Une carte sans champ `Session`** est une carte antérieure à la règle : le contrôle de
  `/demarrer-tache` ne la signale pas, il ne signale qu'une **divergence** avérée.
- **Le champ ne conditionne aucune gate** : `/commit-tache` et `/creer-pr` ne le lisent pas. Une
  carte qui en manquerait ne bloque pas une livraison — la règle est un garde-fou de cadrage, pas un
  contrôle qualité de plus.
- **`/creer-tache` peut produire plusieurs cartes** dans le même tour : toutes portent la même
  session, c'est le but.

## Documentation impactée

- [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md), section
  « `dev-book/tasks/` — un fichier par tâche, un dossier pour tous » : le compteur par espace protège
  les noms, le marqueur de session protège les intentions. Les deux se lisent ensemble.

Rien d'autre : `git-workflow.md` décrit les branches, les commits et les PR, pas le gabarit de carte.

## Définition de terminé

- [x] Le gabarit de `/creer-tache` porte `- **Session** :`, et la commande dit où lire l'identifiant.
- [x] La phase 1 de `/demarrer-tache` compare la session de la carte à la session courante et
      signale une divergence avant d'implémenter.
- [x] Le `CLAUDE.md` racine interdit **strictement** d'ouvrir une tâche de sa propre initiative, de
      proposer la file d'attente ou de présenter une carte en attente comme « la suite ».
- [x] `/demarrer-tache` rappelle en tête que la tâche est **nommée par l'humain**, jamais choisie.
- [x] `parallel-work.md` explique ce que le marqueur protège, à côté du compteur par espace.
- [x] Cette carte porte elle-même le champ qu'elle introduit.
- [x] Aucune carte existante n'est retouchée.
- [x] `CLAUDE.md` dit ce que contient la **clôture** d'une session — ajout en cours de route, voir
      les décisions de cadrage.

## Vérification

**La règle s'applique à elle-même.** Cette carte porte `- **Session** : 686c7d26-…`, le champ
qu'elle introduit. C'est la seule preuve qu'une tâche d'outillage éditorial puisse produire : le
gabarit est utilisé au moment où il est écrit.

**Les quatre textes disent la même chose sans se recopier**, chacun à sa portée :

| Fichier | Ce qu'il porte |
| --- | --- |
| `CLAUDE.md` | la règle stricte — pas d'initiative, et ce que contient une clôture |
| `.claude/commands/demarrer-tache.md` | le rappel en tête, et le contrôle de provenance en phase 1 |
| `.claude/commands/creer-tache.md` | le champ dans le gabarit, et où lire l'identifiant |
| `parallel-work.md` | le pourquoi, à côté du compteur par espace qu'il complète |

**Aucune carte existante n'a été retouchée** : `git status` ne montre que les quatre fichiers ci-dessus
et cette carte. Les quatre cartes en attente du worktree entreprise (`ENT-032`, `ENT-034`, `ENT-035`,
`ENT-037`) restent sans marqueur, ce qui se lit comme « antérieure à la règle » — le contrôle ne les
signalera pas.

Aucune gate ne s'applique : le diff est exclusivement du Markdown de gouvernance, et il ne touche ni
`webapp/` ni `api/`.

## Livré

- `CLAUDE.md` **(M)** — § *Ce qu'aucun agent ne fait* : l'interdiction d'initiative et la clôture.
- `.claude/commands/creer-tache.md` **(M)** — le champ `Session` dans le gabarit, sa provenance.
- `.claude/commands/demarrer-tache.md` **(M)** — « la tâche est nommée par l'humain », et le contrôle
  de provenance en phase 1.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md` **(M)** — ce que le marqueur protège.

## Reste à faire (hors périmètre)

**`ai-workflow.md` n'a pas été touchée**, alors que le tableau des déclencheurs de `/commit-tache` la
cite pour un changement de commandes. Raison : le diff ne la rend pas **fausse** — il la rend
incomplète —, et cette page est le sujet d'un chantier à part, diagnostiqué le 2026-08-02 : les
concepts d'espace, de carte et de discipline de commit y sont éparpillés entre elle,
`git-workflow.md` et `parallel-work.md`, sans propriétaire. Y ajouter un paragraphe maintenant
reviendrait à écrire quelque chose qu'il faudrait déplacer. C'est nommé, pas repris ici.

## Aval

- **Les trois dettes ouvertes par `WEB-006`** — `account/CreditJournal` (entreprise),
  `admin/FillRateSection` (admin), `auth/Showcase` (socle) : une carte par lane propriétaire.
