# showcase-recruiter-webapp-saas Dev Book Docs

Site Docus dédié au dev-book showcase-recruiter-webapp-saas.

## Source Markdown

Les sous-dossiers `runbooks/` et `tasks/` de `dev-book/` sont liés directement sous `content/`
(un lien symbolique par sous-dossier : `content/runbooks`, `content/tasks` →
`../../../dev-book/<sous-dossier>`), pour servir les pages à la racine du site (`/runbooks/…`,
`/tasks/…`) sans niveau `/dev-book/` intermédiaire. La page d'accueil est `content/index.md`.

## Commandes

```bash
bun run dev
bun run build
```
