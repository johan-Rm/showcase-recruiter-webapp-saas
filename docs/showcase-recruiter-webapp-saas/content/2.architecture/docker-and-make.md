# showcase-recruiter-webapp-saas — Docker & Makefile

> Socle d'infrastructure du monorepo : compose, Makefiles, Caddy, environnements.
> Complète la section CD de [testing-and-ci.md](../3.quality/testing-and-ci.md).

## Services & serveur applicatif

| Service | Image / build | Rôle |
| --- | --- | --- |
| `db` | `postgres:17-alpine` | base de données |
| `api` | **FrankenPHP** (build `api/`) | Symfony + API Platform — **Caddy + PHP embarqué** (défaut API Platform 4) ; mode worker, HTTP/2-3 |
| `worker` | même image que `api` | **worker Messenger** : consomme la file des e-mails (`messenger:consume async`). Ne sert aucune requête |
| `webapp-public` · `webapp-candidat` · `webapp-entreprise` · `webapp-admin` | Node/Bun (build `webapp/`, `--build-arg SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=…`) | Nuxt — **une build par espace** (architecture Layers) : dev server (dev) / SSR (preprod/prod) |
| `caddy` | `caddy:2-alpine` | **reverse proxy frontal** (dev, preprod, prod) : TLS auto (hors dev), route `api.` → api, et **chaque host vers la build de son espace** |

> **FrankenPHP** remplace le couple `nginx + php-fpm` : un seul process sert l'API **et** parle
> HTTP au proxy. En dev, l'API est exposée directement (port `DEV_API_PORT`), **sans** Caddy.

> **Les e-mails partent hors requête.** Magic link, invitation et réinitialisation sont poussés dans
> une file Postgres (transport `async`) et envoyés par le `worker` : un SMTP lent ne ralentit plus
> l'utilisateur, un SMTP indisponible ne fait plus échouer sa demande. Contrepartie : **si le worker
> est arrêté, plus aucun e-mail ne part, sans erreur visible** — les messages s'empilent en base.
> Après 3 tentatives, l'e-mail rejoint la file `failed` (`make prod-messenger-failed` pour l'inspecter,
> `messenger:failed:retry` pour le rejouer). Les tables de file sont hors mapping Doctrine :
> `messenger:setup-transports` les crée, appelé par `make dev-schema` et par chaque `*-deploy`.

> **Une build ne contient qu'un espace.** Le host n'est pas un simple libellé : c'est lui qui
> sélectionne l'image à servir. Router les 4 sous-domaines vers un seul service afficherait le même
> espace partout — et exposerait ses pages sur les autres sous-domaines. Le même
> `NUXT_SESSION_PASSWORD` est partagé par les 4 builds, sinon chacune scellerait la session avec sa
> propre clé et le SSO inter-espaces tomberait.

## Fichiers (racine du monorepo)

```
docker-compose.dev.yml        # db + api + worker + les 4 espaces + caddy + mailpit
docker-compose.preprod.yml    # api + worker + les 4 espaces + db + mailpit + docs (APP_ENV=prod, sans caddy)
docker-compose.prod.yml       # api + worker + les 4 espaces + db + docs (APP_ENV=prod, sans caddy)
docker-compose.proxy.yml      # Caddy frontal UNIQUE partagé preprod/prod
docker/caddy/Caddyfile        # reverse proxy frontal (TLS auto)
docker/caddy/Caddyfile.dev    # reverse proxy dev (*.localhost, HTTP)
Makefile                      # vars + -include + help auto-documenté
Makefile.common               # env-agnostique : quality, test, deptrac, type-check, contracts-drift
Makefile.dev / .preprod / .prod / .proxy
Makefile.worktree             # une stack par worktree d'espace (ports décalés, un seul Nuxt)
scripts/worktree-init.sh      # worktree + fichiers non versionnés + .env de la stack
scripts/worktree-remove.sh    # démontage : stack, volumes, dossier (fichiers root compris)
.env.example · .dockerignore · .gitignore · .worktreeinclude

api/Dockerfile · api/Dockerfile.prod        # FrankenPHP
webapp/Dockerfile.dev · webapp/Dockerfile   # Nuxt
```

## Trois environnements

| Env | Compose | Proxy | Déploiement |
| --- | --- | --- | --- |
| **dev** | `docker-compose.dev.yml` | Caddy local, HTTP (`*.localhost:${DEV_PROXY_PORT}`) ; API en port direct | `make dev-up` / `dev-setup` |
| **preprod** | `docker-compose.preprod.yml` | Caddy **frontal partagé** | `make preprod-deploy` |
| **prod** | `docker-compose.prod.yml` | Caddy **frontal partagé** | `make prod-deploy` (sur tag `vX.Y.Z`) |

> **Environnement Symfony : preprod tourne en `APP_ENV=prod`, PAS en env custom `preprod`.**
> Le Kernel n'autorise que `dev`/`test`/`prod` (`App\Kernel::getAllowedEnvs()`) — un `APP_ENV=preprod`
> ferait échouer toutes les commandes console. La preprod ne se distingue de la prod **que par ses
> domaines, ses données et son infra** (Caddy noindex/basic-auth, Mailpit, données synthétiques),
> jamais par l'environnement applicatif. Conséquence : côté API, les surcharges sont dans
> **`api/.env.prod`** (contenant les valeurs preprod : domaines `*.preprod.showcase-recruiter-webapp-saas.example`, CORS, secrets),
> et `make preprod-seed-team` s'exécute avec `--force` (l'env vu par Symfony est « prod »).

## Organisation de la preprod

La preprod est un **miroir jetable de la prod** : mêmes images, **même `APP_ENV=prod`**, même proxy,
données synthétiques, redéployée à chaque intégration sur `develop`.

### Hébergement — un seul VPS, un Caddy frontal mutualisé

Preprod et prod cohabitent sur **le même VPS**. Le proxy vit dans un **compose dédié** — deux Caddy
sur `80/443` entreraient en conflit de ports :

```
docker-compose.proxy.yml      # caddy frontal UNIQUE (80/443), réseau externe partagé
docker-compose.preprod.yml    # api + les 4 espaces + db + mailpit (sans caddy)
docker-compose.prod.yml       # api + les 4 espaces + db (sans caddy)
```

- Le Caddy frontal route **par domaine** : `*.showcase-recruiter-webapp-saas.example` → stack prod, `*.preprod.showcase-recruiter-webapp-saas.example`
  → stack preprod. Il vit dans son propre projet compose : **les deploys applicatifs ne touchent
  jamais le proxy** (zéro coupure croisée preprod/prod).
- **`make proxy-*` isole le proxy** même dans un dossier partagé avec une stack : projet compose dédié
  (`-p showcase-recruiter-webapp-saas-proxy`, donc un `preprod-down/clean` ne l'éteint pas) + **domaine apex forcé**
  (`PROXY_BASE_DOMAIN=showcase-recruiter-webapp-saas.example`, indépendant du `BASE_DOMAIN` de la stack qui vaut
  `preprod.showcase-recruiter-webapp-saas.example`). Le réseau `showcase-recruiter-webapp-saas_edge` reste partagé (déclaré `external` par les stacks).
### Domaines & TLS

- `BASE_DOMAIN=preprod.showcase-recruiter-webapp-saas.example` → `candidat.preprod.showcase-recruiter-webapp-saas.example`,
  `api.preprod.showcase-recruiter-webapp-saas.example`, etc. (le Caddyfile est entièrement paramétré par `BASE_DOMAIN`).
- **Deux wildcards TLS** (un niveau chacun) : `*.showcase-recruiter-webapp-saas.example` **et** `*.preprod.showcase-recruiter-webapp-saas.example`
  (Caddy DNS-01).
- **Aucun domaine en dur dans le code** : cookie de session BFF scopé par variable d'env
  (`NUXT_SESSION_COOKIE_DOMAIN` : `.showcase-recruiter-webapp-saas.example` en prod, `.preprod.showcase-recruiter-webapp-saas.example` en preprod) ;
  origines CORS de l'API pilotées par `CORS_ALLOW_ORIGIN` dans `api/.env.prod`.

### Données — jamais une copie de prod

- **Entreprises/recruteurs** : fixtures démo (`owner@demo.showcase-recruiter-webapp-saas.example`…).
- **Candidats** : pas de fixtures → import Jarvi **ciblé et manuel**
  (`app:jarvi:profiles:import --profile-id` du profil témoin, ou `--limit` réduit).
  **Pas de polling planifié** en preprod (RGPD + quota Jarvi).
- **Pas d'import complet du périmètre Jarvi en preprod** : l'import reste ciblé, comme en prod
  (base légale et rétention RGPD).
- Environnement **jetable** : `make preprod-clean` (reset volumes) puis re-seed sans cérémonie.

### Emails & accès

- **Mailpit** dans le compose preprod : capte magic links, invitations et transactionnels pour la
  recette — aucun email réel ne part de preprod. UI sur `mailpit.preprod.showcase-recruiter-webapp-saas.example`.
- **Preprod entièrement verrouillée** au niveau du Caddy frontal : **basic auth** sur tous les
  hosts `*.preprod.*` + header `X-Robots-Tag: noindex` (pas d'indexation SEO du site vitrine
  dupliqué, pas d'accès public aux données de test).
- **Docs d'équipe protégées partout** : `docs.` / `swagger.` / `dev-book.` sont sous **basic auth +
  noindex y compris en prod** (identifiants dédiés `DOCS_BASIC_AUTH_*`, distincts de ceux de la
  preprod). Seule l'API échappe au basic auth (JWT + healthcheck de deploy).

### Déploiement & secrets

- **Déploiement preprod** : sur le VPS, `git pull` + `make preprod-deploy`
  (build → up → JWT skip-if-exists → healthcheck → notification).
- **Notification de fin de déploiement** : `preprod-notify` (`scripts/notify-deploy.sh`) envoie un
  e-mail disant **ce que la mise en ligne a apporté** — les intitulés des travaux livrés, débarrassés
  de leur préfixe de type et de leur référence de tâche —, et joignant les accès de recette. Il est
  la **dernière** ligne de `preprod-deploy` : `make` interrompt la chaîne à la première étape en
  échec, donc l'e-mail ne part que sur un déploiement entièrement passé — aucune condition à écrire.
- **Trois rôles, et ils ne se mélangent pas.** L'**application possède l'apparence** : une commande
  (`app:deploy:compose-notification`) compose le message avec le gabarit des quatre e-mails du
  produit, logos attachés compris, et le rend **sans l'envoyer**. Le **script possède ce qu'il y a à
  raconter** — l'image de l'API ne contient que le code, jamais l'historique du dépôt — et **le
  transport**. Recopier l'apparence dans le script aurait dupliqué cent cinquante lignes qui
  divergent au premier changement.
- **L'étendue de la liste vient d'une mémoire, pas de la dernière récupération de code.** Le script
  note dans `.deploy-notified-<environnement>` (ignoré par le dépôt) l'état du code au moment où un
  mail est **effectivement parti**, et ne met cette trace à jour qu'en cas de succès : un envoi raté
  n'emporte pas ce qu'il devait annoncer, le suivant couvre les deux mises en ligne. Un
  redéploiement sans nouveau code l'annonce franchement. La liste est plafonnée à quinze entrées,
  le reste étant compté — une coupe silencieuse laisserait croire à l'exhaustivité.
- **Les accès de recette sont écrits dans le script**, pas dans les réglages du serveur : ils sont
  identiques sur toute machine où il tourne, et la ficelle qu'il fallait composer dans le `.env` a
  fait partir trois mails sans le bloc avant que quiconque comprenne pourquoi. Une valeur identique
  partout n'est pas un réglage. ⚠️ **Ils portent des mots de passe en clair — dérogation assumée**,
  tenable à trois conditions écrites dans le script : préproduction jetable, mot de passe reposé à
  chaque `preprod-reset`, jamais réutilisé ailleurs.
- **Un seul verrou protège désormais ces mots de passe** — `BASE_DOMAIN` commençant par `preprod.` —
  là où il y en avait deux : le réglage disparu, la condition « renseigné » disparaît avec lui.
  C'est une liste d'**autorisation** : tout autre environnement est traité comme une production, le
  bloc est écarté et **le refus s'affiche** pendant le déploiement — un garde-fou silencieux qui se
  déclenche à tort passe pour une panne.
  **Il n'emprunte pas le mailer de l'application** : `MAILER_DSN` pointe sur Mailpit en preprod et
  y capte tout, un signal d'exploitation passé par là ne sortirait jamais du serveur. Il sort par le
  **compte mail du domaine** — celui-là même dont la prod aura besoin en `MAILER_DSN` —, sans aucun
  service tiers : les boîtes de `showcase-recruiter-webapp-saas.example` sont chez OVH et le SPF du domaine y autorise déjà
  l'envoi, donc le message part authentifié. Port 465 (soumission chiffrée), pas le 25 que les
  hébergeurs ferment. Sans `DEPLOY_NOTIFY_TO` / `DEPLOY_NOTIFY_SMTP_USER` dans le `.env`, il ne fait
  rien et le déploiement se termine normalement ; un envoi qui échoue **avertit sans rendre le
  déploiement rouge**.
  ⚠️ **Le mot de passe de la boîte s'écrit entre apostrophes** dans le `.env` : ce fichier est lu par
  un shell, et un `$` ou un guillemet nu y détruit silencieusement la valeur.
  `make preprod-notify` envoie le mail **seul**, sans rien reconstruire — c'est par là qu'on vérifie
  la configuration, pas par un déploiement complet.
- **Fichiers `.env` à créer sur le VPS** (jamais commités, générés depuis les `*.example`) :
  - `.env` racine → `BASE_DOMAIN=preprod.showcase-recruiter-webapp-saas.example`, `POSTGRES_*`, `NUXT_SESSION_PASSWORD`,
    `DEPLOY_NOTIFY_*` si l'on veut l'e-mail de fin de déploiement ;
  - `api/.env` → base Symfony (défauts non-secrets) — **doit exister avant le build**, sinon la runtime
    Symfony refuse de démarrer (« Unable to read the .env file ») ;
  - `api/.env.prod` → surcharges de l'API pour ce VPS (domaines `*.preprod.showcase-recruiter-webapp-saas.example`, CORS preprod,
    `APP_SECRET`, `JWT_PASSPHRASE`). Chargé car **`APP_ENV=prod`** (cf. note § Trois environnements).
  - Secrets propres au VPS : mot de passe BDD, **keypair JWT propre** (généré par `--skip-if-exists`),
    clé API Jarvi distincte si Jarvi le permet.
- **Build OOM-safe** : `preprod-build`/`preprod-deploy` buildent `api` puis les **4 images d'espace** en séquentiel
  (`COMPOSE_BAKE=false`), les 3 docs à part (`preprod-docs-build`), et `webapp/.dockerignore` réduit le
  contexte (~570 Mo → 0,4 Mo). Builder les 6 images en parallèle sature la RAM du VPS.
- **Schéma BDD** : le schéma preprod est créé depuis le mapping Doctrine. **`preprod-deploy` ne touche
  pas à la base** — un déploiement ne détruit jamais les données de recette. La remise à zéro est
  **explicite** :
  - `preprod-schema` = drop + create depuis le mapping (miroir de `dev-schema`), destructif ;
  - `preprod-reset` = `preprod-schema` + `preprod-seed-team` (repartir de zéro avec les comptes) ;
  - `preprod-clean` = reset complet des volumes (miroir de `dev-clean`).

  Les deux cibles destructives affichent un avertissement et laissent 5 s pour annuler. En prod, le
  déploiement joue les **migrations Doctrine** (`prod-migrate`), jamais `schema`.

## Traitements périodiques

| Commande | Rythme | Ce qu'elle fait |
| --- | --- | --- |
| `app:searches:close-inactive` | quotidien | clôt les recherches sans **contact humain** depuis `SEARCH_INACTIVITY_DAYS` jours (15 par défaut). Réversible — une recherche close se rouvre d'un geste et repart pour un tour plein. `--dry-run` liste sans fermer, à passer avant la première mise en service : sur une base qui n'a jamais rangé, la première passe peut en fermer beaucoup d'un coup. |

Elle est **idempotente** : deux passes le même jour ne ferment rien la seconde fois. Ce qui compte
comme contact, et pourquoi le recalcul du jeu n'en est pas un, se lit dans
[data-model.md](./data-model.md#lastactivityat-nest-pas-updatedat-et-cest-tout-lenjeu).

## Conventions Makefile

- **Makefile racine** : variables (`COMPOSE_DEV/PRE/PROD`, `EXEC_API/WEBAPP`) + `-include` des
  modules + cible `help` **auto-documentée** (annotations `## …`).
- **`Makefile.common`** : cibles **env-agnostiques** (qualité, tests, deptrac, type-check,
  contracts-drift) — s'exécutent dans les conteneurs **dev**. Cibles **app-scopées** : `api-*` /
  `webapp-*` (+ agrégats `quality`, `test`).
- **`Makefile.{dev,preprod,prod}`** : cycle de vie **préfixé par environnement** (`dev-up`,
  `preprod-deploy`, `prod-deploy`…) ; commandes app-scopées en dev (`dev-api CMD=`, `dev-webapp CMD=`).
- **`Makefile.proxy`** : cycle de vie du Caddy frontal (`proxy-up`, `proxy-reload`, `proxy-logs`…),
  dans son **propre projet compose** (`-p showcase-recruiter-webapp-saas-proxy`).
- **`Makefile.worktree`** : travail parallèle (`worktree-add`, `worktree-list`, `dev-up-space`). Une
  stack par dossier de travail, isolée par `COMPOSE_PROJECT_NAME` et un décalage de 100 sur tous les
  ports — cf. [parallel-work.md](../1.getting-started/parallel-work.md).
- Chaque `*-deploy` = **build → up → JWT (skip-if-exists) → healthcheck** (cf. garde-fous de déploiement).
  Le schéma est **hors du deploy** : explicite en preprod (`preprod-schema` / `preprod-reset`), via
  `prod-migrate` en prod.
