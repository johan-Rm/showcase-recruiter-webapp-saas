# TASK 045 — webapp : sitemap SEO + collection d'icônes locale

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (SEO, performance).
- **Dépend de** : tâche `044` (nettoyage des modules).
- **Réf** : `webapp/nuxt.config.ts` · `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` §5.

## Contexte

Deux manques identifiés en comparant avec un projet Nuxt 4 de référence plus mature
(`blue-bay-mogador`, hors repo).

**SEO** : « SEO by design » est une contrainte transverse du projet et la vitrine publique vit
sur l'apex, mais aucun sitemap n'est généré.

**Icônes** : `@nuxt/icon` (2.3.1) tourne déjà en transitif via Nuxt UI, mais **aucune collection
Iconify n'est installée localement**. Les icônes sont donc résolues par requête réseau au
runtime — coût de latence et dépendance externe.

## Objectif

Générer un sitemap conforme au routage par host, et servir les icônes sans requête externe.

## Périmètre

- Installer et configurer `@nuxtjs/sitemap`.
  - Le sitemap ne couvre **que l'apex** (Public Website). Les espaces `candidat.` /
    `entreprise.` / `admin.` sont authentifiés : ils doivent être exclus, pas indexés.
  - `siteUrl` piloté par variable d'environnement — aucun domaine en dur
    (cf. `NUXT_PUBLIC_BASE_DOMAIN`).
  - Prendre en compte i18n (FR/EN/AR, stratégie `prefix_except_default`) : alternates
    `hreflang`.
- Installer `@iconify-json/lucide` en `devDependency` et déclarer la collection en local
  (`icon: { serverBundle: { collections: ['lucide'] } }` ou équivalent).
  - Vérifier au préalable quelles collections les composants Nuxt UI utilisent réellement
    avant d'en ajouter d'autres.

## Invariants

- Aucun domaine en dur dans la config.
- Les espaces authentifiés ne sont ni listés dans le sitemap, ni indexables.
- Aucune requête vers un CDN Iconify au runtime.

## Critères d'acceptation

- [ ] `/sitemap.xml` servi sur l'apex, contenant les pages publiques et leurs alternates
      `hreflang` FR/EN/AR.
- [ ] Aucune URL de sous-domaine authentifié dans le sitemap.
- [ ] Les icônes s'affichent avec le réseau coupé après le build (bundle serveur local).
- [ ] `bun run quality-check` vert.

## Hors périmètre

- `robots.txt` et balises `meta robots` par espace — à cadrer avec la vitrine.
- Données structurées Schema.org.
