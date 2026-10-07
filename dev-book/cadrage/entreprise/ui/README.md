# UI entreprise

Documents de cadrage UI de l'espace entreprise : shell, console, outils et écrans.

**Source de vérité UI** : `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html`.
Modèle produit : [../les_2_outils.md](../les_2_outils.md).

## Le cadre

- [application_shell.md](./application_shell.md) : le **cockpit** — topbar, barre haute, gouttière de
  guidage, zone de travail (console · vue principale · inspecteur).
- [topbar.md](./topbar.md) : marque, crédits, notifications, menu compte (**qui porte la navigation**).
- [arborescence.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) : routes et portées d'accès.

## Les 2 outils

- [console_contextuelle.md](./console_contextuelle.md) : panneau gauche — Filtres ↔ Mes recherches,
  et la **recherche en cours** (auto-enregistrée, auto-nommée).
- [vue_la_table.md](./vue_la_table.md) : **La Table** — tableau de lignes, tabs d'état, sélection en lot.
- [inspecteur.md](./inspecteur.md) : tiroir droit — aperçu d'un profil, contrat de masquage, 3 actions.
- [vue_la_visionneuse.md](./vue_la_visionneuse.md) : **La Visionneuse** — pile de cards au scroll,
  panneau de contrôle.
- [vue_la_shortlist.md](./vue_la_shortlist.md) : la shortlist d'une recherche et son **export CSV**.

## Hors vivier

- [smart_home.md](./smart_home.md) : hub d'accueil (`/`) — **non couvert par le mockup**, sa
  traduction dans le shell reste à produire.

## Référence transverse

- [brand-guidelines.md](../../../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) : charte de
  marque (palette, typographie, ton) — source de vérité du design. Toute UI de ce dossier s'y
  conforme.

## Périmètre

Une règle API ne figure dans ce dossier que si elle sert de **garde-fou UI** ; le contrat technique
vit dans `../api/` ou `docs/showcase-recruiter-webapp-saas/content/2.architecture/`.
