# TASK 047 — webapp : alignement du nommage des scripts qualité

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (outillage, cohérence).
- **Revient sur** : tâche `035` (qui imposait le préfixe `quality-*`).
- **Réf** : `webapp/package.json` · `webapp/README.md` · `Makefile.common` ·
  `.github/workflows/` (gate `webapp-quality`) · `~/.claude/CLAUDE.md` (profil `github`).

## Contexte

La webapp nomme ses scripts `quality-lint-check`, `quality-format-check`, `quality-type-check`,
`quality-check`. La convention du socle de gouvernance (`~/.claude/CLAUDE.md`, profil `github`)
et celle du projet de référence `blue-bay-mogador` utilisent `lint:check`, `format:check`,
`type-check`, `quality:check`. Divergence purement cosmétique, mais elle casse les réflexes —
humains comme agents.

## Objectif

Un seul vocabulaire de scripts qualité, aligné sur le socle.

## Périmètre

| Avant | Après |
| --- | --- |
| `quality-check` | `quality:check` |
| `quality-lint-check` | `lint:check` |
| `quality-lint-fix` | `lint:fix` |
| `quality-format-check` | `format:check` |
| `quality-format-fix` | `format:fix` |
| `quality-type-check` | `type-check` |

- Ajouter `quality:fix` (`format:fix && lint:fix`), comme sur le projet de référence.
- `quality:check` enchaîne `lint:check && format:check && type-check` — puis `test` une fois la
  tâche `046` livrée.
- Répercuter dans : `Makefile.common`, workflow CI `webapp-quality`, `webapp/README.md`,
  et le bloc « commandes disponibles » de la tâche `035`.

## Invariants

- Aucun appelant orphelin : rechercher les anciens noms dans tout le repo (Makefiles,
  workflows, docs, hooks) avant de clore.

## Critères d'acceptation

- [ ] `bun run quality:check` vert.
- [ ] `grep -rn "quality-lint-check\|quality-format-check\|quality-type-check\|quality-check"`
      ne renvoie plus rien hors historique et hors note d'écart de la tâche `035`.
- [ ] La CI `webapp-quality` passe sur une PR.
- [ ] `make` (cible qualité webapp) fonctionne.

## Hors périmètre

- Renommage des scripts côté `api/` (Symfony) — hors sujet.
- Commit séparé des changements de modules (tâche `044`) pour garder un diff relisible.
