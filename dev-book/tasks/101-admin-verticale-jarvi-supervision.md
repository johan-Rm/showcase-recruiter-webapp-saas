# TASK 101 — admin : verticale /jarvi — supervision de l'ingestion (API + front)

- **Créée le** : 2026-07-20.
- **Statut** : 🟡 socle livré (PR #93) — supervision de l'ingestion à l'écran — recalé le 2026-07-25.
- **Type** : Full-stack — API (Symfony/API Platform) + Front (webapp admin).
- **Dépend de** : chaîne d'ingestion Jarvi (`020`/`034`) · `092` (conservation source par bloc) ·
  entités `IngestionReport` / `JarviSyncState`.
- **Réf cadrage** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/jarvi`).
- **Réf code** : `spaces/admin/app/pages/jarvi.vue` (coquille à remplacer).

> **Recalage 2026-07-25** : verticale **livrée** — `AdminJarviProvider` + `jarvi.vue` : état des
> flux (curseur, dernier run) et agrégats du vivier par état. Reste la relance on-demand (POST),
> qui dépend de la chaîne asynchrone `080`.

## Contexte

`jarvi.vue` est une coquille. L'admin doit **superviser l'ingestion** : dernier import, compteurs
(importés / ignorés / erreurs), profils désactivés par la réconciliation, et **relancer** un import
à la demande. Les entités existent, mais rien n'est exposé côté admin.

## À livrer

**API** :

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/admin/jarvi` (état + derniers rapports) | `ROLE_ADMIN_*` | lecture — `JarviSyncState` + `IngestionReport` |
| POST | `/v1/admin/jarvi/sync` (relance on-demand) | `ROLE_ADMIN_OWNER` | déclenche un import |

- `Output` + `Provider`/`Processor`, gardes explicites ; contrat régénéré (`make dev-openapi`).

**Front** — `jarvi.vue` réel : bandeau d'état (dernier import, statut), KPI (importés / ignorés /
erreurs / désactivés), historique des rapports, bouton **Relancer** (OWNER).

## Règles

- **Lecture** = tous les admins ; **relance** = `ROLE_ADMIN_OWNER` uniquement.
- La relance est asynchrone (chaîne Messenger, cf. `080`) : le front reflète l'état, ne bloque pas.
- Namespace `/v1/admin/*`.

## Définition de terminé

- [ ] `GET /v1/admin/jarvi` + `POST …/sync` livrées et gardées (un `ADMIN_MEMBER` relançant → 403).
- [ ] `jarvi.vue` affiche l'état réel et déclenche une relance visible (preuve : curl + écran).
- [ ] `openapi.json` régénéré ; `quality-check` + `api-quality` verts.
