# TASK WEB-003 — socle : logo du rideau de boot en couleur de premier plan

- **Créée le** : 2026-07-29.
- **Statut** : ✅ implémenté le 2026-07-29 — conventions & format verts. `webapp-quality` complet :
  au passage PR.
- **Type** : Front — Webapp Nuxt (socle, `base/app/components`).
- **Réf** : `AppBootShell` · cohérence avec WEB-001 (marque en couleur de premier plan).

## Contexte

Le rideau de boot (`AppBootShell`) affichait son logo en **accent** (vert de marque, pulsant). Comme
la marque du chrome (WEB-001), le logo ne doit **pas** porter la couleur signature — il suit la
couleur de premier plan (blanc sur sombre, quasi-noir sur clair).

## Livré

- `AppBootShell` : `LayoutLogo` passe de `text-accent` → **`text-text`** (le logo suit `--text` ;
  l'animation `animate-pulse` est conservée). Le badge d'espace garde sa couleur accent.

## Critères d'acceptation

- [x] Logo du rideau blanc sur thème sombre, quasi-noir sur clair (à confirmer au chargement).
- [x] Conventions & format verts.
- [ ] `webapp-quality` complet — au passage PR.
