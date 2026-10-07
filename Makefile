SHELL := /bin/sh

DOCKER_COMPOSE ?= docker compose
COMPOSE_DEV    ?= $(DOCKER_COMPOSE) -f docker-compose.dev.yml
COMPOSE_PRE    ?= $(DOCKER_COMPOSE) -f docker-compose.preprod.yml
COMPOSE_PROD   ?= $(DOCKER_COMPOSE) -f docker-compose.prod.yml
# Proxy = projet compose SÉPARÉ des stacks app (un `preprod-down/clean` ne le touche jamais)
# + domaine APEX forcé, indépendant du BASE_DOMAIN du .env de la stack (preprod.showcase-recruiter-webapp-saas.example
# côté preprod). Permet de tout lancer depuis le même dossier sans conflit.
PROXY_PROJECT     ?= showcase-recruiter-webapp-saas-proxy
PROXY_BASE_DOMAIN ?= showcase-recruiter-webapp-saas.example
COMPOSE_PROXY  ?= BASE_DOMAIN=$(PROXY_BASE_DOMAIN) $(DOCKER_COMPOSE) -p $(PROXY_PROJECT) -f docker-compose.proxy.yml

API_SERVICE   ?= api
# Worker Messenger : même image que l'API, consomme la file des e-mails.
WORKER_SERVICE ?= worker
# Dev = une build par espace (comme en prod). Les commandes webapp (qualité, tests, shell)
# s'exécutent dans un conteneur quelconque : la source est la même partout.
WEBAPP_SERVICES ?= webapp-public webapp-candidat webapp-entreprise webapp-admin
WEBAPP_SERVICE  ?= webapp-entreprise
DB_SERVICE    ?= db
# Port du proxy dev (Caddy). Repris de .env s'il y est défini.
DEV_PROXY_PORT ?= 8080
DOCS_SERVICES ?= docs swagger devbook
DOCS_HOST ?= 127.0.0.1
DOCS_SHOWCASE_RECRUITER_WEBAPP_SAAS_PORT ?= 3010
DOCS_API_PORT ?= 3011
DOCS_DEV_BOOK_PORT ?= 3012
DOCS_RUN_DIR ?= docs/.dev

# Exécution dans les conteneurs dev
EXEC_API    = $(COMPOSE_DEV) exec $(API_SERVICE)
EXEC_API_T  = $(COMPOSE_DEV) exec -T $(API_SERVICE)
EXEC_WEBAPP  = $(COMPOSE_DEV) exec $(WEBAPP_SERVICE)

.DEFAULT_GOAL := help

-include Makefile.common
-include Makefile.dev
-include Makefile.worktree
-include Makefile.preprod
-include Makefile.prod
-include Makefile.proxy

.PHONY: help
help:
	@printf "\n"
	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf " 🚀 showcase-recruiter-webapp-saas — COMMANDES DISPONIBLES\n"
	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"

	@printf "🔧 Qualité & tests (communs)\n\n"
	@printf "  quality                     Qualité complète (api + webapp)\n"
	@printf "  quality-fast                Contrôles rapides (api + webapp)\n"
	@printf "  api-quality                 Qualité api (phpstan, cs, deptrac, phpunit)\n"
	@printf "  webapp-quality              Qualité webapp (lint, format, type-check)\n"
	@printf "  test                        Tous les tests (api + webapp)\n"
	@printf "  api-test / webapp-test      Tests par contexte\n"
	@printf "  analyse / deptrac           PHPStan / frontières d'archi (api)\n"
	@printf "  type-check                  Vue TSC (webapp)\n"
	@printf "  contracts-drift             Types TS générés == OpenAPI\n"
	@printf "\n"

	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"

	@printf "📦 DEV — 4 espaces derrière Caddy (comme en prod), port $(DEV_PROXY_PORT)\n"
	@printf "  localhost · candidat.localhost · entreprise.localhost · admin.localhost\n\n"
	@printf "  dev-up                      Démarrer les services dev\n"
	@printf "  dev-up-build                Démarrer dev avec rebuild\n"
	@printf "  dev-up-space                Socle + UN espace (SPACE=entreprise)\n"
	@printf "  dev-setup                   Bootstrap : up+build → install → JWT → schéma → fixtures\n"
	@printf "  dev-down                    Arrêter et supprimer les containers dev\n"
	@printf "  dev-stop                    Stopper sans supprimer\n"
	@printf "  dev-restart                 Redémarrer les services dev\n"
	@printf "  dev-webapp-restart          Redémarrer un espace webapp (WEBAPP_SERVICE=…)\n"
	@printf "  dev-webapp-recreate         Recréer un espace webapp (.nuxt neuf)\n"
	@printf "  dev-clean                   Reset complet (supprime les volumes)\n"
	@printf "  dev-status                  État des services dev\n"
	@printf "\n"

	@printf "🌿 Travail parallèle — une stack par espace\n\n"
	@printf "  worktree-add ESPACE=api     Dossier de travail + .env + bloc de ports d'un espace\n"
	@printf "  worktree-remove ESPACE=api  Démonter la stack et supprimer le dossier\n"
	@printf "  worktree-list               Qui travaille où (dossiers et branches)\n"
	@printf "\n"

	@printf "📚 Docs Docus (dev)\n\n"
	@printf "  dev-docs                    Lancer les 3 docs en arrière-plan (:$(DOCS_SHOWCASE_RECRUITER_WEBAPP_SAAS_PORT) :$(DOCS_API_PORT) :$(DOCS_DEV_BOOK_PORT))\n"
	@printf "  dev-docs-stop               Stopper les 3 docs en arrière-plan\n"
	@printf "  dev-docs-logs               Suivre les logs des docs en arrière-plan\n"
	@printf "  dev-docs-showcase-recruiter-webapp-saas           Lancer docs.showcase-recruiter-webapp-saas localement (:$(DOCS_SHOWCASE_RECRUITER_WEBAPP_SAAS_PORT))\n"
	@printf "  dev-docs-api                Lancer swagger localement (:$(DOCS_API_PORT))\n"
	@printf "  dev-docs-dev-book           Lancer dev-book localement (:$(DOCS_DEV_BOOK_PORT))\n"
	@printf "  dev-docs-generate-api       Régénérer la doc API Markdown\n"
	@printf "  dev-docs-build              Builder les 3 docs localement\n"
	@printf "\n"

	@printf "📜 Logs & Shell\n\n"
	@printf "  dev-logs                    Suivre les logs dev\n"
	@printf "  dev-shell-api               Shell container api\n"
	@printf "  dev-shell-webapp            Shell container webapp\n"
	@printf "\n"

	@printf "🧱 Symfony & Webapp (dev)\n\n"
	@printf "  dev-api CMD=\"...\"           Console Symfony\n"
	@printf "  dev-composer CMD=\"...\"      Composer (api)\n"
	@printf "  dev-webapp CMD=\"...\"        Commande webapp\n"
	@printf "  dev-schema                  (Re)créer le schéma dev depuis le mapping\n"
	@printf "  dev-fixtures                Charger les fixtures de démo\n"
	@printf "  dev-jwt                     Générer le keypair JWT\n"
	@printf "  dev-messenger-failed        E-mails en échec (file failed)\n"
	@printf "\n"

	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"

	@printf "🚧 PREPROD\n\n"
	@printf "  preprod-up                  Lancer les services preprod\n"
	@printf "  preprod-build               Builder les images preprod\n"
	@printf "  preprod-docs-build          Builder les 3 docs preprod\n"
	@printf "  preprod-docs-up             Lancer les 3 docs preprod\n"
	@printf "  preprod-deploy              build → up → JWT → messenger → healthcheck → e-mail (ne détruit aucune donnée)\n"
	@printf "  preprod-schema              ⚠️  drop + create du schéma (destructif)\n"
	@printf "  preprod-reset               ⚠️  schema + comptes de recette (destructif)\n"
	@printf "  preprod-seed-team           Comptes de recette (PASSWORD=... CREDITS=...)\n"
	@printf "  preprod-api CMD=\"...\"       Console Symfony (preprod)\n"
	@printf "  preprod-healthcheck         Vérifier que l'API preprod répond\n"
	@printf "  preprod-notify              Prévenir par e-mail que la preprod est en ligne\n"
	@printf "  preprod-logs                Suivre les logs preprod\n"
	@printf "  preprod-down / -clean       Arrêt / reset volumes\n"
	@printf "  preprod-status              État des services preprod\n"
	@printf "\n"

	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"

	@printf "🌐 PROXY (Caddy frontal partagé prod + preprod)\n\n"
	@printf "  proxy-up                    Lancer le Caddy frontal (crée showcase-recruiter-webapp-saas_edge)\n"
	@printf "  proxy-reload                Recharger le Caddyfile sans coupure\n"
	@printf "  proxy-logs / proxy-status   Logs / état du proxy\n"
	@printf "  proxy-down                  Arrêter le proxy (coupe prod ET preprod)\n"
	@printf "\n"

	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"

	@printf "🏭 PROD\n\n"
	@printf "  prod-up                     Lancer les services prod\n"
	@printf "  prod-build                  Builder les images prod\n"
	@printf "  prod-docs-build             Builder les 3 docs prod\n"
	@printf "  prod-docs-up                Lancer les 3 docs prod\n"
	@printf "  prod-deploy                 build → up → JWT → migrate → messenger → healthcheck\n"
	@printf "  prod-migrate                Migrations Doctrine (prod)\n"
	@printf "  prod-messenger-failed       E-mails en échec (file failed)\n"
	@printf "  prod-healthcheck            Vérifier que l'API prod répond\n"
	@printf "  prod-logs                   Suivre les logs prod\n"
	@printf "  prod-down / -clean          Arrêt / reset volumes\n"
	@printf "  prod-status                 État des services prod\n"
	@printf "\n"

	@printf "═══════════════════════════════════════════════════════════════\n"
	@printf "\n"
