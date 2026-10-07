# TASK 042 — Validation Docker

- **Créée le** : 2026-07-06.
- **Statut** : ⬜ à valider.
- **Type** : Phase 1 — Finalisation du socle technique (passe de validation).
- **Valide** : tâche `007` (documentations / dockerisation).
- **Réf ClickUp** : https://app.clickup.com/t/869e0jfzk
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`

## Contexte

La stack Docker est en place. Cette tâche valide la cohérence des images et composes. La
**mise en ligne** (proxy mutualisé, domaines, TLS) est portée par la tâche `036`.

## À valider

- [ ] Images : API (FrankenPHP + PHP 8.4, extensions intl/opcache/pdo_pgsql/zip/pcov),
      webapp (Dockerfiles dev+prod), PostgreSQL.
- [ ] Composes dev / preprod / prod cohérents ; Mailpit en preprod.
- [ ] Refactor **Caddy frontal mutualisé** (`docker-compose.proxy.yml`) — conteneurs
      API/webapp/docs distincts.
- [ ] `memory_limit 512M` dev (Web Profiler) isolé du défaut prod.

## Frontière avec la tâche 036

- Mise en ligne VPS + domaines + TLS = `036`. Ici = cohérence et validation des
  images/composes.

## Rappel

- Docker géré par l'utilisateur — ne pas rebuild/restart proactivement.
