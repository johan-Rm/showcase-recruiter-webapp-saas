# showcase-recruiter-webapp-saas

**Shortlist** est le nom de démonstration du produit. Projet de démonstration d’une plateforme SaaS de recrutement, avec des espaces candidat, entreprise et administration.

## Vue d'ensemble

```text
showcase-recruiter-webapp-saas SaaS Platform                                                                    showcase-recruiter-webapp-saas.example
│
├── API                 Symfony + API Platform — métier, sécurité et intégration Jarvi     api.showcase-recruiter-webapp-saas.example
│
├── Public Website      Site public (landing, SEO et contenu accessible sans compte)       showcase-recruiter-webapp-saas.example
│
├── SaaS Platform       Espaces authentifiés (candidat, entreprise et administration)
│   ├── Candidat        Espace candidat                                                    candidat.showcase-recruiter-webapp-saas.example
│   ├── Entreprise      Espace entreprise / recruteur                                      entreprise.showcase-recruiter-webapp-saas.example
│   └── Administration  Espace d'administration                                            admin.showcase-recruiter-webapp-saas.example
│
└── Documentation       Docs & outillage (sous-domaines dédiés)
    ├── Docs            Documentation produit & technique                                  docs.showcase-recruiter-webapp-saas.example
    ├── Swagger         API reference (OpenAPI)                                            swagger.showcase-recruiter-webapp-saas.example
    └── Dev-book        Cadrage produit & fonctionnel                                      dev-book.showcase-recruiter-webapp-saas.example
```

> Domaine unique `BASE_DOMAIN` (`showcase-recruiter-webapp-saas.example`), TLS wildcard sur `*.showcase-recruiter-webapp-saas.example`.

## Stack technique

* Frontend : Nuxt (Vue 3, TypeScript)
* Backend : Symfony + API Platform
* Base de données : PostgreSQL
* Authentification : JWT
* Recherche : Jarvi
* Infrastructure : Docker + Caddy

## Installation rapide

```bash
cp .env.example .env     # configurer les secrets
make dev-setup           # build + install + JWT + migrations
make dev-up              # démarrer (api:18080 · webapp:3000 · db:5432)
make help                # toutes les commandes
```

## Documentation

* 📚 Documentation : `docs/showcase-recruiter-webapp-saas/content/`
* 🏗️ Architecture : `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md`
* 🚀 Guide de démarrage : `docs/showcase-recruiter-webapp-saas/content/1.getting-started/index.md`

## Roadmap

La roadmap du projet est disponible dans :

```text
dev-book/cadrage/ROADMAP.md
```

## Contribution

Les règles de contribution et le workflow Git sont documentés dans :

```text
CONTRIBUTING.md
```

## Licence

Dépôt de démonstration destiné à être public. Tous droits réservés ; aucune licence open source n’est accordée à ce stade.

## Configuration de démonstration

Les domaines `*.example`, les comptes et les identifiants d’intégration sont des exemples. Configurez vos propres services et secrets dans les fichiers `.env` locaux, exclus de Git. Le dépôt cible est [johan-Rm/showcase-recruiter-webapp-saas](https://github.com/johan-Rm/showcase-recruiter-webapp-saas).

Ce dépôt public démarre avec un historique neuf : les commits du projet source ne sont pas publiés. Les fichiers `.env` locaux et les dépendances installées restent exclus du dépôt.
