# TASK WEB-001 — socle : marque — badge d'espace sous le logo, logo en couleur de premier plan

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions & format verts. `webapp-quality` complet :
  au passage PR.
- **Type** : Front — Webapp Nuxt (socle, `base/app/components/layout`).
- **Réf** : `LayoutBrand` (consommée par tous les espaces : admin sidebar, entreprise cockpit,
  `AppHeader`).

## Contexte

Deux réglages sur la marque partagée `LayoutBrand`, pour la cohérence inter-espaces :

1. le **badge d'espace** (« Espace admin/entreprise… ») était **à côté** du logo → demandé **en
   dessous** ;
2. le logo était en **accent** (vert) → demandé en **couleur de premier plan** : blanc sur thème
   sombre, quasi-noir sur thème clair.

## Livré

- `LayoutBrand` : `flex items-center gap-2` → **`inline-flex flex-col items-start gap-1.5`** (badge
  sous le logo, aligné à gauche).
- `text-accent` → **`text-text`** : le logo (`currentColor`) suit désormais le token `--text`
  (`#ffffff` en `.dark`, `porcelain-950` en clair). Le badge garde sa couleur accent.

## Décisions

- **Sur `espace/socle-webapp`** et non un espace consommateur : le changement est fait *pour le
  socle lui-même* (cohérence de marque de tous les espaces), aucun espace ne le consomme en
  particulier. Les espaces récupèrent la modif au merge ; les rebases éventuels sont assumés.
- **Portée limitée à `LayoutBrand`** (le chrome). Non touchés (contextes à part, encore en accent) :
  le rideau de boot (`AppBootShell`, logo qui pulse au splash) et les écrans auth/inscription.
- **`UiBadge` conservé** (pas de migration Nuxt UI ici — hors périmètre de ce réglage).

## Critères d'acceptation

- [x] Badge affiché **sous** le logo, aligné à gauche, dans tous les espaces qui rendent
      `LayoutBrand`.
- [x] Logo blanc en thème sombre, quasi-noir en thème clair (vérifié sur l'admin `:8480`).
- [x] Conventions (marqueur racine) & format verts.
- [ ] `webapp-quality` complet — au passage PR.
