# TASK 093 — Chore sécurité : sortir la clé privée Jarvi des fichiers Insomnia

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire — **rapide, à traiter avant tout commit de ces fichiers**.
- **Type** : Chore — sécurité / gestion des secrets.
- **Réf** : `~/.claude/CLAUDE.md` (« pas de secret en clair, pas de `.env` committé »).

## Contexte

Les fichiers de collection Insomnia ajoutés le 2026-07-18 portent la **clé API privée Jarvi en
clair** :

- `dev-book/resources/insomnia/jarvi.last.environment.json` (`x_api_key`)
- `dev-book/resources/insomnia/jarvi.last.insomnia` (même clé dans l'auth des requêtes + le bloc
  `environments.data`)

Ils sont actuellement **untracked** (jamais committés). La clé privée = accès complet à l'API Jarvi
(lecture de tout le vivier, écriture) et ne doit **jamais** être versionnée.

## Objectif

Empêcher toute fuite de la clé privée Jarvi via le dépôt, sans perdre l'utilité des collections
Insomnia pour l'équipe.

## Périmètre (une option à retenir)

- **Option A — ignorer** : ajouter `dev-book/resources/insomnia/jarvi.last.*` (ou les fichiers
  d'environnement) au `.gitignore`. Les collections restent locales.
- **Option B — placeholder** : ne committer que des gabarits où `x_api_key` = placeholder
  (ex. `REMPLACER_PAR_CLE_PRIVEE`), la vraie valeur restant hors dépôt (env local Insomnia).
  Aligner sur `x_api_key_public` déjà en placeholder.
- Vérifier que la clé n'a **jamais** été committée dans l'historique (sinon, rotation de clé côté
  Jarvi à demander).

## Invariants

- Aucune clé privée en clair dans un fichier suivi par git.
- La clé privée reste côté `api/` (env) et outils locaux uniquement.

## Critères d'acceptation

- [ ] La clé privée n'apparaît dans aucun fichier destiné à être committé.
- [ ] `.gitignore` (option A) ou gabarits à placeholder (option B) en place.
- [ ] `git log`/historique vérifié : la clé n'a pas fuité ; rotation demandée si besoin.

## Points de vigilance

- **Rotation** : si la clé a déjà transité par un canal partagé, demander une nouvelle clé à Jarvi.
- Ne pas casser le workflow de l'équipe (documenter où mettre la vraie clé en local).

## Docs à tenir à jour

- `dev-book/resources/insomnia/README.md` (si présent) — où placer la clé en local.
