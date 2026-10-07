# TASK ADM-002 — webapp : drawer mobile — `aria-controls` sans référence pendante

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — type-check vert, conventions vertes. `webapp-quality`
  complet : au passage PR.
- **Type** : Front — Webapp Nuxt (accessibilité, `spaces/admin`).
- **Réf** : `AdminMobileDrawer.vue` · `AdminTopbar.vue` (le hamburger).

## Contexte

`AdminTopbar` porte `aria-controls="admin-drawer"` sur le bouton hamburger, mais le panneau
(`id="admin-drawer"`, dans `AdminMobileDrawer`) était rendu en **`v-if="isOpen"`** : fermé, l'`id`
n'existe pas dans le DOM et la référence **pend**. C'est le `no-missing-references` remonté par
html-validate au rendu (drawer fermé par défaut).

Un `aria-controls` doit pointer vers un élément **présent**, même masqué.

## Livré

- Le panneau passe en **`v-show="isOpen"`** : il reste dans le DOM, `display:none` quand fermé (donc
  hors flux et hors focus), et l'`aria-controls` du hamburger résout toujours. Le `<Transition
  name="drawer-slide">` fonctionne à l'identique avec `v-show`.
- Le backdrop reste en `v-if` (rien ne le référence, inutile de le garder monté).

## Critères d'acceptation

- [x] Plus de `no-missing-references` sur `admin-drawer` (l'id existe drawer fermé comme ouvert).
- [x] Le panneau est bien hors focus quand fermé (`display:none`).
- [x] Animation d'ouverture/fermeture inchangée.
- [x] Type-check vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.
