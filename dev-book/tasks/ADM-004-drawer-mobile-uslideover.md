# TASK ADM-004 — webapp admin : tiroir de navigation mobile → `USlideover`

- **Créée le** : 2026-07-28.
- **Statut** : ✅ implémenté le 2026-07-28 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (design system, a11y, `spaces/admin`).
- **Réf** : chantier Nuxt UI admin (suite de `ADM-003`) · `USlideover` (Nuxt UI v4) ·
  **rend caduque `ADM-002`** (l'`aria-controls` corrigé y est désormais supprimé).

## Contexte

`AdminMobileDrawer` était un tiroir **entièrement fait-main** : `Teleport` + `Transition` (backdrop
fade + panneau slide), piège de focus (`Tab` en boucle), restauration du focus au déclencheur,
verrouillage du scroll de page, `Escape` — ~127 lignes dont un bloc `<style>` complet. Nuxt UI offre
exactement ce composant, **responsive et accessible par conception**.

## Livré

- **`AdminMobileDrawer` → `USlideover`** (`side="left"`, `v-model:open` branché sur
  `useAdminDrawer`). Le composant passe de ~127 à ~30 lignes : plus de `Teleport`, backdrop,
  piège de focus, scroll-lock, `Escape` ni `<style>` — `USlideover` les gère nativement.
- Conservés : fermeture à la navigation et fermeture au retour desktop (les deux `watch`).
- **Hamburger (`AdminTopbar`)** nettoyé : `aria-controls="admin-drawer"` et
  `data-admin-menu-trigger` supprimés — `USlideover` porte l'aria du dialog et restaure le focus au
  déclencheur ; `aria-expanded`/`aria-label` conservés.
- `useAdminDrawer` inchangé (l'état `isOpen` alimente `v-model:open`).

## Décisions

- **Composant Nuxt UI plutôt que primitives réassemblées** : le tiroir est un cas d'école
  `USlideover`. En prime, on hérite du responsive et du tactile bien conçus (raison d'être du
  chantier), pas seulement de la cohérence visuelle.
- **`ADM-002` rendu caduc** : le correctif `v-if → v-show` ne sert plus — il n'y a plus d'`id`
  statique ni d'`aria-controls` (le dialog `USlideover` gère son accessibilité seul).

## Critères d'acceptation

- [x] Le tiroir s'ouvre/ferme au hamburger ; se ferme à la navigation et au retour desktop.
- [x] Focus piégé dans le tiroir, restauré au hamburger à la fermeture, `Escape` ferme (natif
      `USlideover`) — à confirmer visuellement sur le serveur.
- [x] Conventions (marqueur racine), lint, format, type-check des 4 espaces verts.
- [ ] Parité/comportement à confirmer sur `admin.wt-admin.localhost:8480` (mobile / < lg).
- [ ] `webapp-quality` complet — au passage PR.

## Suite (chantier)

- Structurels restants : `UTable` (tables candidats/orga/référentiels), `UNavigationMenu` (nav).
- Wrappers gardés (`UiAvatar`, `UiInfoHint`) : ré-implémenter sur Nuxt UI *en interne* si utile.
- `UiCard` : trivial, à trancher (UCard vs inline).
