# TASK 099 — admin : verticale /candidats (vivier cross-tenant — API + front)

- **Créée le** : 2026-07-20.
- **Statut** : 🟡 socle livré (PR #93) — vivier cross-tenant en base et à l'écran — recalé le 2026-07-25.
- **Type** : Full-stack — API (Symfony/API Platform) + Front (webapp admin).
- **Dépend de** : `025a` (rôles) · `064` (consentement/exposition vivier) · `086` (carte candidat
  table + inspecteur — à réutiliser côté front) · `080` (normalisation/qualification des champs à
  l'import) · `082` (vocabulaire métiers/compétences + taxonomie ESCO) · réplica `candidate_jarvi`.
- **Réf cadrage** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/candidats`, sous-vue `star`) ·
  `entreprise/profil_dimensions_vivier.md` (ce que contient la donnée profil) ·
  `entreprise/criteres_de_filtrage_profils.md` (quoi afficher / filtrer, sur quelle donnée).
- **Réf code** : `spaces/admin/app/pages/candidats.vue` (coquille à remplacer).

> **Recalage 2026-07-25** : contrairement à ce qu'indiquait le statut, la verticale est **livrée** —
> `AdminCandidateProvider` / `AdminCandidateItemProvider` côté API, `candidats.vue` côté front. Le
> vivier cross-tenant non masqué s'affiche. Reste éventuellement à enrichir (filtres, colonnes), pas
> à démarrer.

## Contexte

`candidats.vue` est une coquille. L'admin est l'**opérateur** de la plateforme : il voit le vivier
**cross-tenant** et les profils **non masqués** (contrairement à l'entreprise). Aucune route
`/v1/admin/candidates` n'existe encore.

## À livrer

**API** — `GET /v1/admin/candidates` :

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/admin/candidates` | `ROLE_ADMIN_*` | **lecture seule** cross-tenant, **non masqué**, filtrable, paginé |
| GET | `/v1/admin/candidates/{id}` | `ROLE_ADMIN_*` | fiche : visibilité, consentement, activité |

- `Output` + `State/Provider` dédiés, garde admin explicite (voter), pagination maison
  (convention 026a).
- Régénérer le contrat : `make dev-openapi` → `openapi.json` → types TS (gate anti-dérive).

**Front** — `candidats.vue` réel : composable `useAdminCandidates` + liste (réutiliser la carte
candidat de `086`) + filtres + fiche.

## Règles

- **Coordonnées non masquées** pour l'admin, mais l'historique tracé (`ProfileView`) **inchangé**.
- Namespace `/v1/admin/*` ; lecture seule (invariant P3).

## Points ouverts (à trancher au cadrage)

- **Fiche candidat admin** : réutilise-t-elle la Visionneuse entreprise (en mode non masqué) ou une
  vue dédiée ? (point ouvert P3 de l'arborescence).
- **Sous-vue `star`** : statut Star (`starInterviewedAt`) + compte rendu (`starInterviewReport`) +
  mise en avant homepage — dans cette tâche ou différée ?

## Définition de terminé

- [ ] Route(s) `/v1/admin/candidates` livrée(s), gardée(s) `ROLE_ADMIN`, non masquées, paginées.
- [ ] Un `COMPANY_*` → 403.
- [ ] `candidats.vue` affiche de vrais candidats (preuve : curl réel + écran).
- [ ] `openapi.json` régénéré et committé ; `quality-check` + `api-quality` verts.
