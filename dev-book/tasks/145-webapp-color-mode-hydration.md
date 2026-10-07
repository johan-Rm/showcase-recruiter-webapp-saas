# TASK 145 — webapp : corriger le mismatch d'hydratation du toggle de thème (color-mode)

- **Créée le** : 2026-07-26.
- **Statut** : ✅ implémenté le 2026-07-26 — type-check des 4 espaces vert, conventions vertes.
  `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`base/`).
- **Placement** : corrige un bug **pré-existant** révélé par la console pendant les tests du rideau —
  la seule vraie erreur d'hydratation Vue restante.
- **Réf** : `@nuxtjs/color-mode` · `base/app/components/layout/ColorModeToggle.vue`.

## Contexte

`ColorModeToggle` rend l'icône `colorMode.value === 'dark' ? sun : moon`. Or au **SSR** la
préférence de thème est **inconnue** (localStorage / système, côté client) : le serveur rend la
lune, le client résout le thème et attend le soleil → `Hydration class mismatch on <span i-lucide:moon>`
(et « Hydration completed but contains mismatches »).

## Livré

- `base/app/components/layout/ColorModeToggle.vue` — l'icône est rendue dans `<ClientOnly>` (repli =
  la lune, mode clair par défaut). Le glyphe n'est plus hydraté → plus de mismatch. Le bouton et son
  `aria-label` restent rendus au SSR (accessibles).

## Décisions

- **`ClientOnly` sur la seule icône**, pas sur le bouton : on préserve l'accessibilité SSR
  (`aria-label`, focusable) et on n'isole que la partie dépendante du thème résolu.
- **Repli = lune** (défaut clair) : cohérent avec le rendu serveur, évite un saut de layout.

## Critères d'acceptation

- [x] Plus de warning « Hydration class mismatch » sur le toggle de thème à l'hydratation.
- [x] L'icône reflète le thème résolu côté client (lune en clair, soleil en sombre), le toggle marche.
- [x] Type-check des 4 espaces vert ; conventions vertes.
- [ ] `webapp-quality` complet — au passage PR.

## Points de vigilance

- **Léger swap possible** de l'icône au 1er rendu client (repli lune → soleil si sombre) : sans
  gravité, et sans erreur d'hydratation (c'est tout l'objet du `ClientOnly`).
