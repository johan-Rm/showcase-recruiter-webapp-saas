# CLAUDE — Registre des agents IA experts · showcase-recruiter-webapp-saas-mvp

**Un agent par lane.** Le dépôt est découpé en **espaces** — un périmètre de code, une branche, un
préfixe de tâche. À chaque espace correspond un sub-agent qui porte l'expertise de ce périmètre, et
sa définition vit dans `.claude/agents/<nom>.md`.

Ce fichier est le **chef d'orchestre** : il dit quel agent prend quelle demande, et ce qu'ils
partagent tous. Il ne contient ni architecture, ni conventions de code, ni règles métier, ni état
d'avancement — ces contenus vivent dans `docs/`, `dev-book/` et les `README.md`.

Chaque agent **hérite des profils génériques du socle global**
([`~/.claude/CLAUDE.md`](file:///home/johan/.claude/CLAUDE.md)) et y **ajoute la maîtrise de son
espace**.

## Routage — une demande, une lane

| Ce que la demande touche | Agent | Branche | Préfixe |
|---|---|---|---|
| `api/` — entités, endpoints, migrations, sécurité serveur, Jarvi | `espace-api` | `espace/api` | `API` |
| Espace Entreprise — cockpit, vivier, crédits, membres | `espace-entreprise` | `espace/entreprise` | `ENT` |
| Espace Candidat — inscription, consentement, présence Maroc | `espace-candidat` | `espace/candidat` | `CAN` |
| Back-office d'administration | `espace-admin` | `espace/admin` | `ADM` |
| Site public — vitrine, SEO, contenu éditorial | `espace-public` | `espace/public` | `PUB` |
| Socle Nuxt partagé travaillé **pour lui-même** | `espace-socle-webapp` | `espace/socle-webapp` | `WEB` |
| Maquettes et direction artistique (`ui-ux-design/`) | `espace-design` | `espace/design` | `UX` |
| Doc, cadrage, gouvernance, CI, Docker, infra | `espace-outillage` | `espace/outillage` | `OPS` |

Cette table **route** : elle dit où va une demande. Ce qu'**est** un espace — un périmètre de code,
une branche, un préfixe, un agent — et la table qui le définit vivent dans
[ai-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#les-espaces).

Deux règles de routage, et deux seulement :

- **Le transverse se rattache à l'espace consommateur.** `webapp/base/` touché *pour* l'entreprise
  part sur `espace/entreprise` ; une page de `docs/` qui documente le contrat part sur `espace/api`.
  `espace-socle-webapp` et `espace-outillage` ne prennent que ce qu'**aucun** espace ne consomme.
- **Une demande qui traverse plusieurs lanes se découpe en autant de tâches**, une par lane. Elles
  peuvent avancer en parallèle ; elles ne partagent jamais un commit.

Ce qui sort d'une lane **remonte, il ne se traite pas** — la règle est détaillée dans le socle
commun, § *Comment chacun livre*.

## Socle commun à tous les agents

### Ce que chacun lit avant d'écrire

1. Ce fichier.
2. [ai-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — le chemin d'une
   demande jusqu'au code livré : ce qu'est un espace, la carte de tâche, la boucle de delivery.
3. [parallel-work.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md) — ce qui casse quand
   plusieurs agents écrivent en même temps.
4. [git-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — branches, commits,
   merge, PR.
5. Les [invariants non négociables](docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md#invariants).
6. Sa propre définition, puis la doc de son périmètre.

### Tu n'es pas seul dans le dépôt

Plusieurs agents écrivent en même temps. Le découpage en espaces **réduit** les collisions, il ne les
supprime pas — six réflexes les couvrent, et [parallel-work.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md)
en donne le pourquoi :

- **`make worktree-list` en début de session** — la branche d'un worktree au travail est intouchable.
- **Ton dossier de travail s'établit avant la première écriture**, et tout y est absolu — la
  procédure est juste en dessous.
- **Numéro de tâche lu sur toutes les branches**, jamais par un `ls` — la commande est dans
  `git-workflow.md`. Un arbre en retard a déjà fait attribuer deux fois le même numéro.
- **Staging par chemins explicites** — `git add <chemin>`, jamais `git add -A` ni un dossier entier :
  le fichier d'un autre agent peut apparaître entre le `git status` et le `git add`. Relire
  `git diff --cached --name-only` avant de figer.
- **Ne jamais écraser du travail non commité**, le sien ou celui d'un autre : aucun `checkout --`,
  `reset --hard` ni copie de dossier sans avoir lu `git status` juste avant.
- **`TEST_TOKEN` avant de lancer des tests** quand une autre suite peut tourner dans la même stack.

Trois ressources restent partagées par conception : `webapp/base/` et `webapp/shared/`,
`dev-book/tasks/`, et `api/openapi.json`. Sur ces fichiers, se réaligner sur `develop` souvent.

### Ton dossier de travail

**Un agent ne possède pas une branche : c'est le dossier qui la porte.** Un sub-agent démarre dans le
dossier de son appelant, pas dans celui de sa lane — rien ne te met sur ta branche, tu t'y mets. Deux
agents lancés dans le même dossier sont sur la **même** branche, et un `git checkout` de l'un
l'arrache sous les pieds de l'autre.

Ce qui sépare réellement les lanes, ce sont les `git worktree` : un dossier par espace, checkouté sur
sa branche, avec sa stack et sa base. La procédure, avant toute écriture :

1. `git worktree list` → la ligne portant ta branche. **Son chemin absolu est ton dossier.** S'il n'y
   en a pas, `make worktree-add ESPACE=<espace>` depuis la racine, puis relire la liste.
2. `git -C <ton dossier> branch --show-current` doit répondre **exactement** ta branche. Sinon :
   arrête-toi et signale, n'écris rien.
3. Ensuite, tout est absolu, sans exception :
   - fichiers — `<ton dossier>/api/src/…`, jamais `api/src/…` ;
   - git — `git -C <ton dossier> status`, `add`, `commit` ;
   - commandes projet — `cd <ton dossier> && make …` **dans la même invocation**.

**Réécris le chemin en entier à chaque commande.** Les variables de shell ne survivent pas d'un appel
à l'autre, et le shell repart de la racine du dépôt entre deux commandes : un chemin relatif édite
alors l'arbre principal, pas le tien. C'est ce geste qui a déjà détruit du travail non commité.

### Comment chacun livre

Le cycle complet, et les raisons de chacune de ces règles, sont dans
[ai-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#le-chemin-dune-demande).
Ce qui suit est ce qui s'applique **avant** de l'avoir lu.

- **Une tâche à la fois**, et le cycle se referme : une tâche s'ouvre, se code, se commite, et
  seulement ensuite la suivante s'ouvre.
- **Un commit = une tâche**, embarquant le code, ses tests, la documentation qu'il rend fausse et sa
  carte `dev-book/tasks/<PRÉFIXE>-NNN-*.md` clôturée. Pas de commit `wip`, pas de `fixup`.
- **Jamais de commit direct sur `develop` ni `main`.** Vérifier la branche **dès la première
  modification**, pas au moment de committer.
- **Les quality gates tournent à l'ouverture de la PR**, pas à chaque commit. En développement,
  seuls les tests impactés — et la régénération d'`api/openapi.json`, qui est du contenu de commit et
  non une gate.
- **Un agent de lane ne pousse pas et n'ouvre pas de PR.** Il s'arrête au commit ; la PR est un geste
  humain, outillé par `/creer-pr`.
- **Ce qui sort de ta lane remonte, il ne se traite pas.** Tu nommes ce qui manque et tu t'arrêtes :
  ni le code, ni la **carte** de l'autre lane — écrite ici, elle atterrirait sur `develop` sans le
  code qu'elle décrit. Les agents ne s'appellent pas entre eux : le relais passe par l'agent
  principal, où le périmètre reste visible.
- **Diff minimal**, pas de refactor hors périmètre, pas de dépendance ajoutée sans justification.

> Cinq commandes projet outillent le cycle, une par moment : `/demarrer-tache`, `/creer-tache`,
> `/commit-tache`, `/creer-pr`, `/apres-pr-mergee`. Trois s'ajoutent sans y entrer, dont deux
> **précèdent** le cadrage et sont les seules du dépôt à ne rien modifier : `/evoquer-un-sujet`
> discute d'un sujet avant qu'il devienne une tâche — elle conclut, nomme les tâches qui en
> découlent et n'en ouvre aucune ; `/etat-des-lanes` dit où en est chaque lane. La troisième,
> `/demarrer-taches`, enchaîne plusieurs tours du cycle pour un lot de cartes déjà cadrées —
> **un commit par tâche quand même**, elle accélère le rythme des commits, elle n'en réduit pas le
> nombre. Leurs règles vivent dans
> [git-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md).

### Ce qu'aucun agent ne fait

**Une session ne travaille que sur ce qui lui a été demandé — dans cette session.** Elle n'ouvre
aucune tâche de sa propre initiative, ne propose pas la file d'attente, et ne présente jamais une
carte en attente comme « la suite ». Une carte non commitée trouvée dans un worktree est **le travail
de quelqu'un d'autre**, pas un sujet disponible : le dossier `dev-book/tasks/` est partagé, et une
carte y vit sans propriétaire visible jusqu'au commit de sa tâche.

Quand le travail demandé est fini, l'agent **le dit et s'arrête là**. Il n'enchaîne pas, il ne
suggère pas de quoi remplir le temps qui reste. Si l'humain nomme explicitement une carte venue d'une
autre session, l'agent dit à qui elle est et demande **une fois** avant de s'y mettre — reprendre le
cadrage d'un autre est légitime, mais ça se décide, ça ne se découvre pas au commit.

**Une session se clôt, elle ne s'éteint pas.** Le dernier message dit trois choses et rien d'autre :
ce qui a été **livré**, ce qui reste **ouvert** dans l'arbre s'il reste quelque chose, et ce qui a
été **nommé au passage** — un défaut croisé, une dette, un besoin d'amont — qui appartient à
d'autres sessions et que personne ne reprend ici. Ce n'est pas une proposition de suite : c'est un
inventaire, écrit pour que la session suivante démarre sans reconstituer quoi que ce soit.

> L'incident qui a écrit ces trois règles, et la raison pour laquelle c'est la **proposition** et non
> l'exécution qui ouvre la brèche, sont dans
> [ai-workflow.md](docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#la-discipline-de-session).

Sortir de son périmètre pour écrire à la place d'une autre lane, toucher un secret ou un `.env`,
déplacer une contrainte d'intégrité « en code seul », exposer la clé Jarvi hors de `api/`, ou
laisser un texte utilisateur en dur plutôt qu'une clé i18n.
