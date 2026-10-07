# TASK 012 — Socle CI : GitHub Actions (gate de pré-merge)

- **Créée le** : 2026-06-19.
- **Statut** : ✅ implémenté — 3 workflows en place (`api-quality`, `webapp-quality`, `commit-check`).
- **Périmètre** : poser la **CI GitHub Actions** du socle technique. Gate de **pré-merge bloquant** sur
  les PR vers `develop`/`main`, en **déléguant à l'outillage existant** (`composer quality`,
  `bun run quality-*`) pour garantir la cohérence local ↔ CI.
- **Hors périmètre** : le **CD** (`deploy-preprod.yml` / `deploy-prod.yml`) → tâche `036` ; SonarQube /
  CodeQL.

## Contexte

Sans check CI, la branch protection « required status checks » ne peut pas être activée : le gate de
pré-merge reste théorique.

## Workflows livrés

| Fichier | Déclencheur | Rôle |
| --- | --- | --- |
| `.github/workflows/api-quality.yml` | `pull_request` (develop/main) sur `api/**`, Makefile*, compose, le workflow ; + `workflow_dispatch` | Monte `db`+`api` (compose dev), prépare (install, JWT, base de test), lance `composer quality` : `lint:container`, `phpstan`, `cs:check`, `deptrac`, `phpunit`. **Bloquant.** |
| `.github/workflows/webapp-quality.yml` | `pull_request` (develop/main) sur `webapp/**` ; + `workflow_dispatch` | `bun install --frozen-lockfile`, puis lint (ESLint), format (Prettier), type-check (vue-tsc), conventions d'architecture, tests (Vitest), **contrat OpenAPI ↔ types TS**, build SSR de sanity. **Bloquant.** |
| `.github/workflows/commit-check.yml` | `pull_request` (develop/main) ; + `workflow_dispatch` | Valide les commits de la PR (Conventional Commits, commitlint). **Bloquant.** |

> **La dérive de contrat est une étape, pas un workflow.** `quality-contracts-check` régénère
> `webapp/shared/types/api.ts` depuis `api/openapi.json` et échoue si le fichier bouge : les types du
> front ne peuvent pas diverger du contrat de l'API.

## Choix techniques

- **Parité local ↔ CI** : la CI ne réimplémente pas la qualité, elle exécute les **mêmes** commandes
  que le dev. Côté API, `composer quality` dans le **même conteneur** (`docker-compose.dev.yml`) via
  les cibles `ci-up` (build+up `db`+`api`) et `ci-setup` (install + keypair JWT + base de test).
- **DB de test** : `showcase-recruiter-webapp-saas_test` est créée (`doctrine:database:create --env=test`) ; le schéma est
  (re)construit par `ResetDatabase` (Foundry) dans les tests fonctionnels.
- **Pré-merge uniquement** : déclenchement sur `pull_request`, pas sur push de branche.
  `workflow_dispatch` permet de lancer la CI à la main (UI GitHub ou `gh workflow run`).

## Lancement manuel

- **La CI à l'identique en local** : `make api-quality` (le gate exact). Bring-up complet :
  `make ci-up && make ci-setup && make api-quality`.
- **Déclencher sur GitHub** : `gh workflow run api-quality.yml --ref <branche>`.
- **Suivre une PR** : `gh pr create --base develop`, puis `gh pr checks --watch`.

## Critères d'acceptation

- [x] `api-quality.yml` vert sur une PR touchant `api/**`.
- [x] `webapp-quality.yml` vert sur une PR touchant `webapp/**`, gate de contrat incluse.
- [x] `commit-check.yml` rejette un commit hors Conventional Commits.
- [x] Les workflows sont déclenchables manuellement (`workflow_dispatch`).
- [x] Aucune divergence local ↔ CI : la CI exécute les commandes de qualité du projet, pas une
      réimplémentation.
- [ ] Branch protection GitHub : activer les *required status checks* sur `develop` et `main`.

## Mise à jour

Deux affirmations de la version initiale ne correspondaient pas au dépôt et ont contaminé
`docs/` :

- **`lefthook`** était cité comme l'outil de feedback local (hook pre-commit). **Il n'a jamais été
  installé** — ni `lefthook.yml`, ni `.husky`. Le feedback local passe par les commandes de qualité
  (`make api-quality`, `bun run quality-check`), lancées au point de contrôle.
- **`webapp-quality.yml` était donné comme différé** « tant que `webapp/` n'est pas scaffoldé ». La
  webapp existe (architecture Nuxt Layers) et son workflow tourne.

## Hors périmètre — sans tâche à ce jour

Ces outils étaient mentionnés dans la documentation sans jamais avoir été décidés ni installés. Ils ne
sont **pas au backlog** : les remettre suppose une décision explicite.

- `release-please` (release automatique, CHANGELOG généré).
- **k6** (tests de charge, budgets p95).
- Scan de secrets (gitleaks), détection de code mort (knip / ts-prune).
- Couverture de code (PCOV).
