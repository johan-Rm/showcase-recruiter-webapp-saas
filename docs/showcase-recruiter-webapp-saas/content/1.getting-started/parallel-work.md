# Travail parallèle — plusieurs agents, un seul dépôt

- Rôle : réduire les conflits quand plusieurs agents écrivent du code en même temps sur le
  monorepo — ce que le découpage en espaces partitionne, et ce qui reste partagé malgré lui.
- Related : [./git-workflow.md](./git-workflow.md), [./ai-workflow.md](./ai-workflow.md)

## Le principe

Deux agents qui travaillent sur deux espaces différents ne se croisent pas — **dans la plupart des
cas**. C'est ce « dans la plupart des cas » qui fait cette page.

Le découpage en espaces **réduit** les collisions ; il ne les supprime pas. Plusieurs ressources
traversent les espaces par conception, et c'est là que le travail parallèle casse. Cette page dit
lesquelles, pourquoi elles cassent, et les gestes qui l'évitent.

## Ce que l'espace partitionne

Trois séparations découlent de la **définition** de l'espace — sa branche, son préfixe de tâche, son
code applicatif —, et [ai-workflow.md](./ai-workflow.md#les-espaces) les porte. Deux autres ne
relèvent pas de git, et c'est cette page qui les possède :

| Ressource | Mécanisme |
|---|---|
| Dossier de travail | un `git worktree` par espace (ci-dessous) |
| Serveurs et ports | une stack Docker par worktree — un décalage de 100 par espace |

## Un dossier de travail par espace — `git worktree`

Un dépôt Git peut avoir plusieurs dossiers de travail, chacun sur sa propre branche. Deux agents
travaillent alors dans deux dossiers, sans jamais se basculer la branche sous les pieds :

```bash
make worktree-add ESPACE=api    # worktree + fichiers non versionnés + bloc de ports
make worktree-list              # qui travaille où
```

Les worktrees vivent sous `worktree/`, **exclu du suivi** par le `.gitignore` — ils n'apparaissent
donc pas dans `git status`. Renommer une branche checked-out dans un worktree est sans danger : le
worktree suit automatiquement le nouveau nom.

> Un `make worktree-list` en début de session dit qui travaille où. C'est le premier réflexe avant
> de toucher à une branche : celle d'un worktree au travail est **intouchable**.

### Une stack Docker par worktree

Un worktree n'est qu'une copie du code : les conteneurs, eux, continuent de lire l'arbre depuis
lequel ils ont été lancés. Chaque dossier de travail fait donc tourner **sa propre stack**, avec son
bloc de ports et son domaine — deux branches sont alors visibles dans le navigateur en même temps.

`make worktree-add` prépare tout : il crée le worktree, y recopie les fichiers non versionnés sans
lesquels rien ne démarre (`.env`, `api/.env`, `api/.env.test`, les clés JWT), puis écrit dans son
`.env` le `COMPOSE_PROJECT_NAME`, le domaine et les cinq ports de l'espace. **Un espace = un
décalage de 100 sur tous les ports** ; la table complète vit dans `.env.example`.

```bash
cd worktree/api
make dev-setup        # une fois : composer install, JWT, schéma, fixtures, comptes
make dev-up-space     # socle + UN espace Nuxt

# → http://entreprise.wt-api.localhost:8180/
```

Et pour démonter, `make worktree-remove ESPACE=api` — stack, volumes et dossier. Il refuse tant que
l'arbre est sale ou porte des commits absents de `develop` (`FORCE=1` lève le second refus, jamais le
premier). Le détour par une commande tient à ce que les conteneurs écrivent en **root** dans le
worktree (`api/vendor`, `api/var`, `webapp/.nuxt`) : un `git worktree remove` seul échoue en
« Permission denied ».

Trois points qui font tenir l'ensemble :

- **Un seul serveur Nuxt par stack.** Chacun pèse 0,7 à 1,3 Go, contre 434 Mo pour tout le reste
  réuni (base, API, worker, Mailpit, Caddy). `dev-up-space` n'allume que l'espace déclaré en
  `DEV_SPACE` — `make dev-up`, lui, monte toujours les quatre.
- **Un domaine par stack, pas seulement un port.** Les cookies de session ignorent le port : deux
  stacks servant `entreprise.localhost` se déconnecteraient mutuellement. D'où `wt-<espace>.localhost`.
- **Une base par stack.** Le volume Postgres est nommé d'après le projet compose : une tâche qui
  change le schéma ne touche que sa propre base.

> `claude --worktree` crée ses worktrees ailleurs (`.claude/worktrees/`, ignoré) et sous un nom
> variable : il ne peut pas porter de bloc de ports. Le `.worktreeinclude` de la racine lui fait au
> moins recopier les fichiers non versionnés. Pour un espace qu'on veut **voir tourner**, passer par
> `make worktree-add`.

## Ce qui ne se partitionne pas

### `webapp/base/` et `webapp/shared/` — partagés par conception

La règle de l'« espace consommateur » veut qu'un changement de socle fait *pour* un espace parte
sur la branche de cet espace. Deux espaces peuvent donc modifier `webapp/base/` en même temps. C'est
assumé : l'alternative — un espace propriétaire du socle — sérialiserait tout le travail front.

Conséquence pratique : sur un fichier du socle, se réaligner sur `develop` **souvent**, plutôt que
d'accumuler dix tâches avant de découvrir le conflit.

### `dev-book/tasks/` — un fichier par tâche, un dossier pour tous

Le fichier de tâche voyage avec son commit, quel que soit l'espace. Deux agents créent donc des
fichiers dans le même dossier au même moment. Ils ne se marchent pas dessus tant que les **noms**
diffèrent — d'où le compteur par espace, et la commande qui le lit sur **toutes** les branches et
tous les remotes :

```bash
git log --all --diff-filter=A --name-only --format= -- 'dev-book/tasks/ENT-*' \
  | grep -oE 'ENT-[0-9]{3}' | sort -u | tail -1
```

Un `ls dev-book/tasks/` ne voit que l'arbre courant — ni le fichier qu'un autre agent vient de créer
sur sa branche, ni celui qui est arrivé sur `develop` depuis le dernier réalignement. C'est ce
second cas qui a fait numéroter `OPS-002` en `OPS-001` au premier essai.

**Le compteur protège les noms ; il ne protège pas les intentions.** Une carte vit en fichier **non
suivi** entre son cadrage et le commit de sa tâche : pendant cette fenêtre, elle est visible de tous
et attribuée à personne. C'est ce que règle le champ `**Session**` de son en-tête —
[ai-workflow.md](./ai-workflow.md#à-qui-appartient-une-carte).

### `api/openapi.json` — régénéré, donc en conflit garanti

Toute modification de la surface d'API impose `make dev-openapi`, et le fichier régénéré fait partie
du commit (la CI `api-quality` le vérifie par `git diff --exit-code`). Deux espaces qui touchent la
surface en parallèle produisent **toujours** un conflit sur ce fichier. Il se résout en régénérant
après rebase, jamais en fusionnant les deux versions à la main.

### Le conteneur API — il lit l'arbre depuis lequel il a été lancé

`docker-compose.dev.yml` monte `./api:/app` — un chemin **relatif**. Une stack lancée depuis l'arbre
principal sert donc l'`api/` de l'arbre principal, y compris pour un agent qui code dans
`worktree/api/` : symptôme, « mon changement n'a aucun effet ».

C'est ce que règle la stack par worktree ci-dessus : lancée **depuis** le worktree, elle en monte le
code. Il n'y a plus lieu de recopier `worktree/api/api/` vers l'arbre principal — un tel overlay a
déjà détruit du travail non commité, et le geste n'a plus de raison d'être.

### La base de test — isolable par `TEST_TOKEN`

Une stack par worktree isole déjà la base de test : le conteneur Postgres et son volume appartiennent
au projet compose. Le jeton reste nécessaire **à l'intérieur** d'une même stack — deux suites lancées
côte à côte dans le même conteneur API.

`api/config/packages/doctrine.yaml` définit `dbname_suffix: '_test%env(default::TEST_TOKEN)%'`. Sans
`TEST_TOKEN`, toutes les suites tapent la **même** base `showcase-recruiter-webapp-saas_test` et se la recréent
mutuellement : index absent, colonne « contient des nulls » sur une table vide, table disparue —
autant de symptômes qui ressemblent à des bugs et n'en sont pas.

Chaque agent qui lance des tests en parallèle pose donc son propre jeton. Attention : `TEST_TOKEN`
change le **nom** de la base, il ne la **crée** pas — elle doit exister et porter le schéma, une
fois pour toutes :

```bash
export TEST_TOKEN=2                                              # → showcase-recruiter-webapp-saas_test2
APP_ENV=test php bin/console doctrine:database:create --if-not-exists
APP_ENV=test php bin/console doctrine:schema:create
make api-test
```

Deux corollaires, tout aussi coûteux que le premier :

- le harness **ne recrée pas le schéma** entre deux runs (`ApiTestCase::loadFixtures` purge et
  recharge, rien de plus) : après un changement d'entité, `doctrine:schema:update --force` est à
  faire à la main ;
- une tâche qui **change le schéma** ne migre **jamais** une base partagée — elle casserait la
  suite des autres agents sous leurs pieds.

En cas de doute sur un échec bizarre, la première question est « quelqu'un d'autre teste-t-il en ce
moment ? » (`git worktree list` montre les arbres qu'on n'a pas créés), avant toute investigation
sur le code.

## Ce que la concurrence change au moment de commiter

Les règles de commit — staging par chemins explicites, relecture de l'index avant de figer — sont
dans [ai-workflow.md](./ai-workflow.md#cadrer-coder-figer-à-chaque-carte), à l'étape qui les
applique. Ce qui suit est le **risque propre au travail parallèle**, et lui seul.

- **Le fichier d'un autre agent peut apparaître entre le `git status` et le `git add`.** C'est ce
  qui rend `git add -A` et `git add <dossier>` dangereux ici et pas ailleurs : sur un dépôt à un
  seul auteur, l'arbre ne bouge pas pendant qu'on le regarde. Ici, si.
- **Ne jamais écraser du travail non commité**, le sien ou celui d'un autre. Aucun `checkout --`,
  `reset --hard` ni copie de dossier sans avoir lu `git status` **juste avant** — et une carte non
  commitée trouvée dans un worktree appartient à la session qui l'a écrite.
