# TASK OPS-019 — Un sub-agent par lane, conscient du travail parallèle

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — huit agents déclarés dans `.claude/agents/`, `CLAUDE.md`
  devenu routeur, cascade de gouvernance documentée à trois portées.
- **Type** : Outillage — gouvernance IA.
- **Tests** : sans objet — aucun code exécutable. La vérification est la relecture du frontmatter
  des huit fichiers et l'absence de référence pendante vers les cinq rôles supprimés.
- **Réf** : `OPS-011` (la gouvernance en un seul fichier) · `OPS-002` (branches par espace) ·
  [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md)

## Contexte

`CLAUDE.md` portait **cinq rôles experts** — Architecte, Backend, Frontend, Product, MOA — décrits
en prose. Ces rôles n'étaient pas des sub-agents : aucun fichier ne les déclarait, aucun outil ne
les bornait. C'étaient des **postures que l'agent principal adoptait lui-même** en lisant le
fichier, dans un seul contexte.

Trois conséquences, toutes payées :

- **Le périmètre n'était qu'une consigne.** « Backend ne touche jamais `webapp/**` » se lisait dans
  un fichier que l'agent pouvait avoir oublié vingt outils plus tôt.
- **Le découpage ne collait plus au dépôt.** Depuis `OPS-002`, le travail est organisé en **huit
  espaces**, chacun avec sa branche, son préfixe de tâche et son dossier de travail. Cinq rôles
  fonctionnels ne se projettent pas sur huit périmètres : « Frontend » recouvrait à lui seul cinq
  lanes qui n'ont ni les mêmes écrans, ni les mêmes exigences.
- **Rien ne disait à un agent qu'il n'était pas seul.** `parallel-work.md` décrit les ressources
  partagées et les réflexes qui vont avec ; encore faut-il qu'un agent lancé sur une lane sache
  qu'il doit la lire.

## Périmètre

- Déclarer **huit sub-agents**, un par espace, dans `.claude/agents/`.
- Réécrire `CLAUDE.md` en **routeur** : le tableau espace → agent, et le socle commun à tous.
- Documenter la cascade de gouvernance à trois portées dans `ai-workflow.md`.

## Hors périmètre

- **Le verrou technique de périmètre.** Un hook `PreToolUse` refusant toute écriture hors du
  périmètre de l'agent courant a été envisagé et écarté pour cette carte : c'est un script à écrire
  et à maintenir, et il gênerait la règle de l'espace **consommateur** — qui autorise précisément
  une lane à écrire dans le socle. La frontière reste une consigne, appuyée par une liste d'outils
  bornée.
- **Le push et l'ouverture de PR.** Un agent de lane s'arrête au commit. La PR reste un geste humain,
  outillé par `/creer-pr`.
- **Les cinq commandes de `.claude/commands/`**, inchangées : elles outillent le cycle, les agents
  tiennent le périmètre. Les deux se complètent sans se recouvrir.

## Décisions de cadrage

### Les lanes absorbent l'expertise, elles ne s'ajoutent pas aux rôles

Trois montages étaient possibles : huit agents de lane qui absorbent les cinq rôles ; huit lanes
**plus** trois agents transverses sans périmètre de code ; ou les huit lanes cohabitant avec les
cinq rôles laissés en prose.

Le premier a été retenu. La cohabitation reproduisait exactement le mode de panne d'`OPS-011` —
deux registres qui se recouvrent et décrochent en silence. Et les trois transverses n'avaient pas
de périmètre propre : l'architecture, le cadrage et la rédaction client écrivent tous dans `docs/`
ou `dev-book/`, c'est-à-dire dans l'espace `outillage`. Ils y deviennent **trois postures d'un même
agent**, explicitement séparées, plutôt que trois agents sans dossier.

Aucune expertise n'est perdue : le contenu des cinq rôles est réparti dans les huit fichiers, au
plus près du périmètre qui l'applique.

### Un fichier d'agent pointe, il ne recopie pas

La règle de non-duplication du projet vaut ici plus qu'ailleurs : huit fichiers qui recopieraient
les conventions front décrocheraient au premier changement. Chaque agent **pointe** vers la source
qui fait foi — `webapp/README.md` pour le front, `parallel-work.md` pour le parallèle,
`git-workflow.md` pour le cycle — et n'écrit que ce qui est propre à sa lane : son périmètre, son
expertise, son piège, ses interdits.

Le socle partagé par les huit vit à **un** endroit, la section *Socle commun* de `CLAUDE.md`, et
chaque agent l'ouvre en première instruction.

### Le parallélisme est une instruction, pas une note de bas de page

Chaque agent est explicitement prévenu qu'il n'est pas seul, et reçoit six réflexes : lire
`make worktree-list`, travailler en chemins absolus, lire le numéro de tâche sur toutes les
branches, stager par chemins explicites, ne jamais écraser du travail non commité, poser un
`TEST_TOKEN`. Le *pourquoi* reste dans `parallel-work.md` ; l'agent en reçoit le *quoi*, au moment
où il en a besoin.

Les lanes dont le périmètre est le plus exposé le disent en clair : `espace-api` porte le piège
d'`api/openapi.json`, `espace-socle-webapp` celui des quatre espaces qui écrivent dans son dossier.

## Règles et cas limites

- **Un agent de lane ne pousse pas et n'ouvre pas de PR.** Il livre un commit, sur sa branche, dans
  son dossier de travail.
- **`dev-book/tasks/` n'appartient à aucune lane** : chaque carte voyage avec le commit de sa propre
  tâche. `espace-outillage` n'y écrit que ses cartes `OPS-`, ou celles qu'il cadre pour une autre
  lane.
- **Une demande qui traverse plusieurs lanes se découpe en autant de tâches.** Elles peuvent avancer
  en parallèle ; elles ne partagent jamais un commit.

## Documentation impactée

- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — la cascade passe
  de deux à trois portées ; une section décrit le principe d'un sub-agent par espace.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code exécutable. Les preuves sont trois relevés
sur l'arbre de travail :

- les huit fichiers de `.claude/agents/` portent un frontmatter valide (`name`, `description`,
  `tools`), avec huit `name` distincts correspondant aux huit espaces ;
- `git grep` sur les intitulés des cinq rôles supprimés → aucune référence pendante hors cartes
  closes ;
- `CLAUDE.md` relu de bout en bout : tableau de routage complet (huit lignes), socle commun, aucune
  duplication de `parallel-work.md` ni de `git-workflow.md`.

Les agents sont chargés au **démarrage de session** : leur disponibilité se constate par `/agents`
dans une session ouverte après ce commit, pas dans celle qui les a écrits.

## Livré

- `.claude/agents/espace-api.md` **(+)** — expertise Symfony / API Platform / Jarvi serveur, piège
  `openapi.json`, `TEST_TOKEN`.
- `.claude/agents/espace-entreprise.md` **(+)** — cockpit et vivier, deux outils, favoris et
  shortlist portées par la recherche.
- `.claude/agents/espace-candidat.md` **(+)** — consentement, présence Maroc, masquage.
- `.claude/agents/espace-admin.md` **(+)** — back-office, rôles, données sensibles.
- `.claude/agents/espace-public.md` **(+)** — SEO, Core Web Vitals, graphie « showcase-recruiter-webapp-saas ».
- `.claude/agents/espace-socle-webapp.md` **(+)** — layers, BFF, i18n à un seul arbitre, règles CI
  prouvées par une violation volontaire.
- `.claude/agents/espace-design.md` **(+)** — atelier, `draft/` non versionné, promotion vers
  `validate/`.
- `.claude/agents/espace-outillage.md` **(+)** — trois postures : architecture, cadrage produit,
  rédaction pour le client (règle d'or et interdits).
- `CLAUDE.md` **(M)** — les cinq rôles en prose deviennent un tableau de routage à huit lanes et un
  socle commun (lecture, parallélisme, livraison, interdits).
- `ai-workflow.md` **(M)** — cascade à trois portées, section sur le sub-agent par espace.

## Définition de terminé

- [x] Huit agents déclarés, un par espace, avec périmètre, expertise et interdits.
- [x] Chaque agent est prévenu du travail parallèle et sait quoi lire avant d'écrire.
- [x] `CLAUDE.md` route et ne duplique plus aucune documentation.
- [x] Aucune expertise des cinq rôles perdue au passage.
- [x] La cascade de gouvernance est documentée à trois portées.
