# showcase-recruiter-webapp-saas — Docus sites

Workspace Bun pour publier trois documentations Markdown avec le template officiel Docus.

## Sites

| Site | Markdown source | URL cible |
| --- | --- | --- |
| `showcase-recruiter-webapp-saas` | `showcase-recruiter-webapp-saas/content` (doc projet, éditée en place) | `https://docs.showcase-recruiter-webapp-saas.example` |
| `api` | `api/content` (généré en place par `generate:api-docs`) | `https://swagger.showcase-recruiter-webapp-saas.example` |
| `dev-book` | `dev-book/{runbooks,tasks}` (un symlink par sous-dossier) | `https://dev-book.showcase-recruiter-webapp-saas.example` |

## Commandes

```bash
bun install
bun run dev:showcase-recruiter-webapp-saas
bun run dev:api
bun run dev:dev-book
bun run build
```

Chaque site vient du starter officiel `create-docus` et peut être déployé sur une URL différente.

## Docker

Les images Docker sont construites depuis la racine du dépôt afin de résoudre le lien symbolique
du site `dev-book` vers `dev-book/`. Les sites `showcase-recruiter-webapp-saas` et `api` embarquent leur contenu en place
(`showcase-recruiter-webapp-saas/content`, `api/content`).

```bash
docker compose -f docker-compose.prod.yml build docs swagger devbook
```
