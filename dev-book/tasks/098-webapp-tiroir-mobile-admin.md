# TASK 098 — webapp : tiroir mobile de la coque admin (front)

- **Créée le** : 2026-07-20.
- **Statut** : ⏳ Spécifiée le 2026-07-20 — non démarrée.
- **Type** : Front — Webapp Nuxt (espace admin), autonome (aucune API).
- **Dépend de** : tâche `097` (coque + sidebar).
- **Réf cadrage** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`.
- **Réf code** : `spaces/admin/app/layouts/dashboard.vue` (sidebar `hidden … lg:flex`),
  `components/AdminTopbar.vue`.

## Contexte

La sidebar admin est `hidden … lg:flex` : sous 1024 px elle **disparaît** et l'espace n'a **plus
aucun accès de navigation**. Il faut un tiroir (drawer) ouvert par un bouton hamburger dans la
topbar.

## À livrer

- **Bouton hamburger** dans `AdminTopbar`, visible `< lg` uniquement, à gauche du breadcrumb.
- **Drawer** : la navigation de la sidebar réutilisée à l'identique, en overlay ancré à gauche,
  avec **backdrop**.
- **Fermeture** : clic backdrop, touche Échap, et navigation vers un lien.
- Extraire la nav de `dashboard.vue` en **composant partagé** (ex. `AdminSidebarNav`) pour éviter
  la duplication sidebar desktop ↔ drawer.

## Règles

- Accessibilité : `role="dialog"` + `aria-modal`, **piège de focus**, focus rendu au hamburger à la
  fermeture ; hamburger `aria-expanded`/`aria-controls`.
- Transitions respectant `prefers-reduced-motion` ; verrouiller le scroll du body à l'ouverture.
- Respecter les **safe-areas** mobiles ; aucune dépendance ajoutée (VueUse déjà présent).
- Aucun texte en dur (i18n), la sidebar desktop `≥ lg` reste inchangée.

## Critères de validation

- Sur 360 px : le hamburger ouvre le drawer, un lien navigue **et** ferme le drawer, Échap ferme,
  le focus est piégé puis rendu.
- `≥ lg` : le hamburger et le drawer sont absents, la sidebar fixe est intacte.
- `quality-check` vert, 3 langues.
