# TASK 007 — Documentations Docus : sites, Dockerisation et commandes Make

- **Créée le** : 2026-06-19.
- **Statut** : ✅ **réalisée le 2026-06-19**.
- **Périmètre** : exposer les documentations showcase-recruiter-webapp-saas avec le template Docus/Nuxt, brancher les
  trois sites dans la dockerisation preprod/prod, et fournir des commandes Make pour le dev et le
  build Docker.
- **Hors périmètre** : correction exhaustive des liens Markdown historiques, stratégie
  d'authentification privée pour le dev-book, CI/CD de déploiement automatisé.

## Sites livrés

| Site | Contenu source | Domaine prod |
| --- | --- | --- |
| Documentation projet | `docs/showcase-recruiter-webapp-saas/content/` (éditée en place) | `docs.showcase-recruiter-webapp-saas.example` |
| Documentation API | `docs/api/content/` (généré en place) | `swagger.showcase-recruiter-webapp-saas.example` |
| Dev book | `dev-book/` | `dev-book.showcase-recruiter-webapp-saas.example` |

## Règles d'implémentation

- Un workspace Bun `docs/` porte les trois sites Docus.
- Le dev-book reste dans son dossier propriétaire `dev-book/`, consommé par le site `dev-book` via
  liens symboliques (un par sous-dossier servi) ; les sites `showcase-recruiter-webapp-saas` et `api` embarquent leur
  contenu en place (`docs/showcase-recruiter-webapp-saas/content/`, `docs/api/content/`).
- La documentation API est générée en Markdown depuis l'OpenAPI JSON exposée par l'API, directement
  dans le contenu du site `api` (`docs/api/content/`, versionné).
- Les images Docker utilisent un Dockerfile générique paramétré par `DOCS_SITE`.
- Caddy route chaque domaine de documentation vers son service Nuxt SSR dédié.

## Commandes Make attendues

### Dev local

- `make dev-docs-showcase-recruiter-webapp-saas`
- `make dev-docs-api`
- `make dev-docs-dev-book`
- `make dev-docs-generate-api`
- `make dev-docs-build`

### Preprod / prod

- `make preprod-docs-build`
- `make preprod-docs-up`
- `make prod-docs-build`
- `make prod-docs-up`

## Critères d'acceptation

- [x] Les trois sites Docus sont configurés avec leurs domaines cibles.
- [x] Les trois services sont déclarés dans `docker-compose.preprod.yml` et
  `docker-compose.prod.yml`.
- [x] Caddy expose `docs.showcase-recruiter-webapp-saas.example`, `swagger.showcase-recruiter-webapp-saas.example` et `dev-book.showcase-recruiter-webapp-saas.example`.
- [x] Les commandes Make dev/preprod/prod sont disponibles.
- [x] `docker compose -f docker-compose.prod.yml build docs swagger devbook` passe.
- [x] `docker compose -f docker-compose.preprod.yml config` et
  `docker compose -f docker-compose.prod.yml config` passent.

## Points de vigilance

- Le prerender de la documentation projet signale des liens Markdown cassés existants. Ils ne
  bloquent pas la génération Docker, mais doivent être corrigés dans une passe documentaire dédiée.
- Le site dev-book ignore les fichiers `.html` et `.gitkeep`, car Nuxt Content ne consomme que les
  contenus Markdown pertinents pour cette V1.
