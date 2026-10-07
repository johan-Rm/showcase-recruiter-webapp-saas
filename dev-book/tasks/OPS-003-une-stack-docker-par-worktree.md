# TASK OPS-003 — infra : une stack Docker par worktree, pour voir deux branches à la fois

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — compose et Caddyfile paramétrés, stack de worktree vérifiée.
- **Type** : Infrastructure / outillage — aucun changement de comportement applicatif.
- **Réf** :
  - task `OPS-002-branches-par-espace-et-travail-parallele.md` (les worktrees, et le piège du
    conteneur API que cette tâche supprime)
  - `docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md`,
    `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`

## Contexte

`OPS-002` a documenté les worktrees et, avec eux, un défaut qu'elle ne pouvait que contourner :

> Un agent qui développe l'API depuis `worktree/api/` teste contre le code de l'arbre principal —
> symptôme : « mon changement n'a aucun effet ».

Le contournement proposé — recopier le code du worktree vers l'arbre principal — est exactement le
geste qui a détruit du travail non commité le 2026-07-25. La page l'encadrait de garde-fous ; elle ne
pouvait pas le supprimer.

La cause est pourtant sans mystère : `docker-compose.dev.yml` monte `./api:/app`, un chemin
**relatif**. Une stack lancée **depuis** le worktree en monte le code. Il ne manquait que de pouvoir
faire tourner deux stacks à la fois — cinq ports, un domaine et un nom de projet les séparent.

## Décisions

### Un espace = un décalage de 100 sur tous les ports

Une table plutôt qu'une formule : elle se lit, se vérifie d'un coup d'œil et tient dans
`.env.example`. L'arbre principal garde ses valeurs actuelles (rang 0), `espace/api` prend le rang 1
(proxy 8180, API 18182, base 5532, mails 18125), et ainsi de suite jusqu'à `outillage`.

`scripts/worktree-init.sh` écrit ce bloc dans le `.env` du worktree. Le calcul n'est fait qu'une
fois, à la création : ensuite, la valeur est là, lisible, modifiable à la main.

### Un domaine par stack, et pas seulement un port

C'est le point qui n'est pas négociable : **les cookies ignorent le port**. Deux stacks servant
`entreprise.localhost` sur 8080 et 8180 partagent leur cookie de session — se connecter sur l'une
déconnecte l'autre, et le symptôme ne ressemble en rien à sa cause.

D'où `DEV_BASE_DOMAIN`, `localhost` dans l'arbre principal et `wt-<espace>.localhost` ailleurs. Le
front n'a demandé aucun changement : `useSpaceUrl` dérivait déjà tout de `NUXT_PUBLIC_BASE_DOMAIN` et
conservait le port de la requête.

Le préfixe `wt-` évite une collision réelle : `api.localhost` est déjà le host de l'API dans le
Caddyfile de dev.

### Un seul serveur Nuxt par stack

Mesuré sur les conteneurs en marche : un serveur Nuxt de dev pèse **0,7 à 1,3 Go**, contre **434 Mo**
pour tout le reste réuni (base, API, worker, Mailpit, Caddy). Dupliquer les quatre espaces coûterait
3,5 Go par stack, dupliquer le socle n'en coûte que 0,4.

`dev-up-space` démarre donc le socle et l'unique espace déclaré en `DEV_SPACE`. Les espaces sans
front — `api`, `outillage`, `socle-webapp` — reçoivent `entreprise` par défaut, la plus complète,
modifiable dans le `.env`.

C'est aussi ce qui a écarté un second mode « léger » (un front rebranché sur la base et l'API de
l'arbre principal) : il n'économisait que ces 434 Mo, imposait un fichier compose de plus et une base
partagée, et surtout **ne réglait pas** le piège du conteneur API, puisqu'il ne duplique que le front.

### Caddy perd son `depends_on`

Il listait les quatre espaces : `dev-up-space` en aurait démarré quatre. Caddy résout ses upstreams
**à la requête**, pas au chargement — un espace éteint répond 502 sur son seul host. La dépendance ne
garantissait rien (un conteneur démarré n'est pas un Nuxt prêt) et interdisait le cas utile.

### Supprimer un worktree demande une commande, à cause des droits root

Découvert en démontant la stack d'essai : `git worktree remove` échoue en **« Permission denied »**.
Les conteneurs écrivent en root dans le dossier monté — `api/vendor`, `api/var`, `webapp/.nuxt` — et
l'utilisateur ne peut pas les effacer. (`.env.example` porte bien `DOCKER_UID` / `DOCKER_GID`, mais
`docker-compose.dev.yml` ne les consomme pas ; les aligner déborde du périmètre de cette tâche.)

`worktree-remove` fait donc le ménage depuis un conteneur, **par chemins nommés** et jamais en
effaçant le dossier entier — c'est cette liste explicite qui garantit qu'aucun fichier source ne
passe par le `rm` privilégié.

Une commande qui supprime un dossier de travail doit se refuser plus souvent qu'elle n'accepte :
arbre sale → refus sec, commits absents de `develop` → refus levable par `FORCE=1`. Le premier n'est
jamais levable, parce que c'est exactement ce qui a été perdu le 2026-07-25.

### `.claude/worktrees/` entre au `.gitignore`, et `worktree/` avec lui

`claude --worktree` crée ses arbres dans `.claude/worktrees/` — sous un dossier **versionné**, et
non couvert par l'exclusion existante. Un worktree complet y atterrissait dans `git status`.

`worktree/` passe du `.git/info/exclude` (par machine) au `.gitignore` (partagé) : depuis que
`make worktree-add` crée ce dossier chez tout le monde, l'exclusion doit voyager avec le dépôt.

Un `.worktreeinclude` à la racine fait au passage recopier par `claude --worktree` les fichiers non
versionnés sans lesquels rien ne démarre. Il ne va pas plus loin : ces worktrees portent un nom
variable et ne peuvent donc pas se voir attribuer un bloc de ports. Pour un espace qu'on veut **voir
tourner**, la porte d'entrée reste `make worktree-add`.

## Vérification

Comportementale — aucune suite de tests ne couvre du compose ni un Caddyfile. Deux stacks ont
réellement tourné côte à côte, sur un worktree jetable posé sur ce commit.

**Configuration**

| Vérification | Résultat |
| --- | --- |
| `docker compose config`, arbre principal | projet `showcase-recruiter-webapp-saas-mvp`, ports 8080 / 8082 / 18082 / 5432 / 18025, domaine `localhost` — **identiques à avant** |
| `docker compose config`, `.env` de worktree | projet `showcase-recruiter-webapp-saas-api`, ports 8180 / 8182 / 18182 / 5532 / 18125, domaine `wt-api.localhost` |
| `caddy validate` sans variables | `Valid configuration` (valeurs par défaut) |
| `caddy adapt` avec `DEV_BASE_DOMAIN=wt-api.localhost` | hosts `wt-api.localhost`, `entreprise.` / `candidat.` / `admin.` / `api.wt-api.localhost`, écoute `:80` et `:8182` |
| `scripts/worktree-init.sh api` sur un worktree existant | worktree et fichiers déjà là **laissés intacts**, `.env` généré avec le bloc du rang 1 |
| `make -n dev-setup` | `dev-up-build` dans l'arbre principal, `dev-up-space-build` dans un worktree |

**Les deux stacks en marche**

| Vérification | Résultat |
| --- | --- |
| Arbre principal après le changement | vitrine **200**, candidat / entreprise / admin **302** → `/login`, `api.localhost/v1/config` **200** |
| Liens inter-espaces rendus par la vitrine | `//candidat.localhost:8080/`, `//entreprise.localhost:8080/` — le domaine paramétré arrive bien jusqu'au HTML |
| Conteneurs de la stack de worktree | **6** (socle + `webapp-entreprise`), projet `showcase-recruiter-webapp-saas-api` |
| `entreprise.wt-api.localhost:8180` | **302** → `/login` ; `api.wt-api.localhost:8180/v1/config` **200** |
| `admin.wt-api.localhost:8180` (espace éteint) | **502** — le host mort ne gêne pas les autres |
| Isolation des bases | **2 561** candidats côté principal (import Jarvi), **5** côté worktree (fixtures seules) |
| RAM de la stack de worktree | **1,29 Go**, dont 1,00 pour le serveur Nuxt |
| `worktree-remove` | stack démontée, volumes supprimés, dossier effacé malgré les fichiers root |

Deux surprises, sans conséquence : la **première** requête sur un espace fraîchement démarré dépasse
30 s (compilation Nuxt à froid) et fait passer un `curl` court pour une panne ; et les domaines à
trois niveaux (`entreprise.wt-api.localhost`) se résolvent sans rien toucher à `/etc/hosts` — `curl`
comme les navigateurs traitent tout `*.localhost` en boucle locale.

## Livré

- `docker-compose.dev.yml` : `DEV_BASE_DOMAIN` et `DEV_OAUTH_PORT` paramétrés, `depends_on` de Caddy
  retiré.
- `docker/caddy/Caddyfile.dev` : hosts et port OAuth dérivés de l'environnement.
- `scripts/worktree-init.sh` (+) : worktree, fichiers non versionnés, `.env` de la stack. Idempotent,
  n'écrase jamais un fichier existant.
- `scripts/worktree-remove.sh` (+) : démontage complet, deux garde-fous avant toute suppression.
- `Makefile.worktree` (+) : `worktree-add`, `worktree-remove`, `worktree-list`, `dev-up-space`,
  `dev-up-space-build`.
  `dev-setup` démarre un seul espace dans un worktree, les quatre dans l'arbre principal.
- `.env.example` : table des blocs de ports, trois variables de stack.
- `.gitignore`, `.worktreeinclude` (+), `cspell.json`.
- `parallel-work.md` : section « une stack Docker par worktree » ; le contournement par recopie
  disparaît. `docker-and-make.md` : fichiers et conventions à jour.

## Reste à faire (hors périmètre de cette tâche)

- **Rien ne vérifie qu'un `.env` de worktree n'entre pas en collision** avec un autre. La table des
  rangs suffit tant qu'on la suit ; un `worktree-doctor` qui compare les ports ouverts n'a de sens
  que si la collision se produit vraiment.
- **`DOCKER_UID` / `DOCKER_GID` ne sont consommés nulle part.** `.env.example` les déclare « pour
  aligner les droits fichiers conteneur ↔ hôte », mais aucun compose ne les lit : les conteneurs
  écrivent en root. C'est ce qui impose le détour de `worktree-remove`, et ça se paierait aussi sur
  un `rm` manuel dans l'arbre principal. À câbler le jour où les droits gênent ailleurs.
- **Les docs Docus** (`dev-docs`, ports 3010-3012) tournent sur l'hôte, hors compose : deux worktrees
  qui les lancent en même temps se disputent les ports. Sans objet tant qu'on ne les lance que depuis
  l'arbre principal.
