# TASK ADM-009 — webapp admin : retirer le footer « Compte » (doublon du dropdown avatar)

- **Créée le** : 2026-07-29.
- **Statut** : ✅ implémenté le 2026-07-29 — conventions, lint, format verts ; type-check des 4
  espaces vert. `webapp-quality` complet : au passage PR.
- **Type** : Front — Webapp Nuxt (`spaces/admin`, UX).
- **Réf** : `AdminSidebarNav` (footer) · `AdminTopbar` (dropdown avatar).

## Contexte

Le pied de la sidebar (`AdminSidebarNav`) portait un lien **« Compte »** (`/compte`) — **strictement
redondant** avec le **dropdown de l'avatar** (`AdminTopbar`, haut droite) qui propose déjà **Compte**
(même lien, même icône) **+ langue + déconnexion**. Le dropdown est le point d'entrée canonique du
compte (et le seul à porter la déconnexion).

## Livré

- `AdminSidebarNav` : suppression du second `UNavigationMenu` (le `footerItems` « Compte ») et du
  `computed footerItems`. La sidebar ne garde que la marque + les deux groupes de navigation.
- `/compte` reste accessible via le dropdown avatar — aucune perte d'accès.

## Décisions

- **Un seul point d'entrée compte** : le dropdown avatar, cohérent avec les autres espaces. Le lien
  de pied faisait doublon sans rien apporter (il ne portait même pas la déconnexion).

## Critères d'acceptation

- [x] Plus de lien « Compte » en pied de sidebar ; `/compte` toujours atteignable via le dropdown.
- [x] Conventions, lint, format, type-check des 4 espaces verts.
- [ ] Parité visuelle à confirmer sur `:8480`.
- [ ] `webapp-quality` complet — au passage PR.
