# TASK 102 — admin : tableau de bord — KPI temps réel (API + front)

- **Créée le** : 2026-07-20.
- **Statut** : 🟡 home câblée (PR #93), tuiles complètes selon leurs sources — recalé le 2026-07-25.
- **Type** : Full-stack — API (agrégats) + Front (webapp admin, home).
- **Dépend de** : `100` (connexions → `lastLoginAt`/journal) · `ProfileView` (dévoilements) ·
  entités d'inscription (candidats/entreprises) · `026a` (file de revue, déjà là).
- **Réf cadrage** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/` centre de contrôle) +
  `initial_brief.md §4`.
- **Réf code** : `spaces/admin/app/pages/index.vue` (coquille à remplacer).

> **Recalage 2026-07-25** : la home `/` est **câblée** sur `AdminDashboardProvider` (`index.vue`),
> pas une coquille. Chaque tuile vit selon sa source : « entreprises à revoir » est réelle (`026a`) ;
> « connexions » attend `lastLoginAt` (`100`), « dévoilements » attend `ProfileView`. Reste à
> brancher au fil des sources, pas à construire l'écran.

## Contexte

La home admin (`/`) est le **centre de contrôle** (brief §4) : `index.vue` est une coquille avec 4
tuiles KPI + un aperçu de la file de revue. Il faut la câbler sur de vrais chiffres.

## Point ouvert (à trancher au cadrage)

Définir **précisément** les KPI et leur source :
- **Connexions** (aujourd'hui) → dépend de `lastLoginAt`/journal (tâche `100`).
- **Dévoilements** (7 j) → `ProfileView`.
- **Inscriptions** (7 j) → comptes candidats + entreprises.
- **Entreprises à revoir** → `026a` (déjà disponible).

Tant que les sources (100, `ProfileView` exposé) ne sont pas là, la home reste partiellement en
coquille : livrer d'abord la tuile « à revoir » (source vivante) puis les autres au fil des sources.

## À livrer

**API** — endpoint(s) d'agrégats admin (ou réutilisation des collections existantes avec `total`).

**Front** — `index.vue` réel : tuiles KPI branchées, aperçu de la file de revue (`EN_REVUE`) réel,
panneau supervision (renvoi vers `/jarvi`).

## Définition de terminé

- [ ] Sources KPI tranchées ; au moins « entreprises à revoir » branchée sur du réel.
- [ ] `index.vue` n'affiche plus de skeleton pour les KPI disponibles (preuve : curl + écran).
- [ ] `openapi.json` régénéré si nouvel endpoint ; `quality-check` + `api-quality` verts.
