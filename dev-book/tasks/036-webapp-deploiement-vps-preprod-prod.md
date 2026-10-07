# TASK 036 — Webapp : déploiement VPS PREPROD + PROD

- **Créée le** : 2026-07-06.
- **Statut** : 🚧 en cours — **infrastructure multi-espaces livrée le 2026-07-13** (compose preprod/prod
  + Caddyfile : une build par espace) ; reste le **déploiement effectif sur le VPS** et la CD.
- **Type** : Phase 1 — Socle technique / mise en ligne webapp.
- **Prolonge** : tâche `022` (webapp host-routing + auth multi-sous-domaines).
- **Dépend de** : scaffold `022` (branche `spike/task-022-webapp-host-routing-auth-i18n`).
- **Réf ClickUp** : https://app.clickup.com/t/869e0nrt2
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` (§ Organisation de la preprod)

## Contexte

Le scaffold Nuxt (host-routing, i18n, BFF, Dockerfiles dev+prod, CI `webapp-quality`) est
réalisé sous `022` mais **non poussé**. Cette tâche met la webapp en ligne sur le VPS, en
PREPROD et PROD, selon l'organisation preprod déjà cadrée.

## Objectif

Déployer la webapp sur un VPS unique, derrière un Caddy frontal mutualisé, avec
environnements PREPROD et PROD isolés et déploiement automatique depuis `develop`.

## Périmètre

- Pousser / merger le scaffold `022` vers `develop`.
- Caddy frontal **mutualisé** (`docker-compose.proxy.yml`) + retrait du caddy local des
  composes preprod/prod.
- Domaines `*.preprod.showcase-recruiter-webapp-saas.example` (2 wildcards TLS DNS-01) + prod ; `BASE_DOMAIN`
  paramétré, aucun domaine en dur.
- `.env` + keypair JWT **distincts par env** ; cookie BFF + CORS par variable d'env.
- Workflow `deploy-preprod.yml` (auto depuis `develop`) + cible prod.
- Basic-auth + `noindex` sur `*.preprod.*` ; Mailpit en preprod.

## Invariants

- Une seule app Nuxt sert apex + 3 espaces ; conteneurs API/webapp/docs distincts.
- Aucun secret en clair, aucun domaine en dur.

## Livré le 2026-07-13 — une build par espace (preprod, prod, dev)

L'infrastructure supposait **une seule app Nuxt** routée par host — hypothèse d'avant la migration
en Nuxt Layers. Depuis, **une build ne contient qu'un espace** : les 4 sous-domaines pointant sur le
même service auraient servi le même espace partout, et exposé ses pages sur les autres hosts.

- `docker-compose.prod.yml` / `.preprod.yml` : `webapp` unique → **4 services**
  (`webapp-public|candidat|entreprise|admin`), chacun buildé avec `--build-arg SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE`,
  alias `edge` dédiés (`webapp-<espace>-prod` / `-preprod`).
- `docker/caddy/Caddyfile` : chaque host route vers **son** service, en prod comme en preprod.
- `docker-compose.dev.yml` : le dev tourne désormais **comme la prod** (4 espaces derrière un Caddy
  local, `docker/caddy/Caddyfile.dev`, `*.localhost:${DEV_PROXY_PORT}`) — le conteneur mono-espace,
  qui servait la même build sur tous les hosts, est supprimé.
- **Builds séquentiels** (`prod-build`, `preprod-build`) : 4 images Nuxt en parallèle saturent la RAM
  du VPS. Même précaution que pour les docs.
- Le **même `NUXT_SESSION_PASSWORD`** dans les 4 builds : sans lui, chaque espace scellerait la
  session avec sa propre clé et le SSO inter-sous-domaines tomberait.

Vérifié : image prod `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE=admin` construite et démarrée → sert `space: "admin"` ; les 4
hosts de dev servent chacun leur espace, et une page d'un autre espace y renvoie un 404 natif.

## Critères d'acceptation

- [x] Une build par espace, routée par host (dev, preprod, prod).
- [ ] `entreprise.preprod.showcase-recruiter-webapp-saas.example` répond en HTTPS via le proxy mutualisé.
- [ ] Déploiement automatique déclenché depuis `develop`.
- [ ] PREPROD protégée (basic-auth + noindex).

## Hors périmètre

- Auth fonctionnelle bout-en-bout → tâche `037`.
- Contenu des espaces (coquilles + Vivier) → tâche `038`.
