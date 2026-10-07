# TASK ADM-010 — webapp admin : la sidebar dit où l'on est

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-31 — pilule et liseré sur l'item actif, intitulés de groupe
  démotés, filet retiré, sous-titre d'espace retiré du menu de compte. Vérifié au navigateur dans
  les deux thèmes et dans le tiroir mobile ; gates au passage PR.
- **Type** : feat — passe de design sur la navigation.
- **Tests** : sans objet — le diff n'ajoute aucune logique : ni calcul, ni branche, ni service, rien
  qu'une liste de classes et un nœud retiré. La suite du webapp existe bien (7 fichiers `*.vitest.ts`,
  55 tests) mais elle couvre `services/` et `shared/`, du métier sans framework — précisément ce que
  ce changement ne produit pas. Preuve par capture et par styles calculés, cf. « Vérification ».
- **Réf** : `ADM-005` (la nav en `UNavigationMenu`, dont le critère « états actifs à confirmer » est
  resté décoché) · `ADM-004` (le tiroir mobile qui partage cette nav) · `ADM-009` (le pied de
  sidebar retiré) · [brand-guidelines.md, § Éléments interactifs](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)
  · amont : `WEB-005`.

## Contexte

Dans la sidebar admin, la page courante ne se signale que par la couleur de son libellé et de son
icône. Pas de fond, pas de repère de bord. En balayage, on lit une liste homogène avec une ligne
colorée plutôt qu'une position ; et pour qui distingue mal le vert du blanc, la page courante
disparaît — la couleur est le seul véhicule de l'information.

Ce n'est pas une direction artistique à inventer : la charte prescrit déjà, pour la ligne active
d'une liste, un fond mêlé d'accent et un liseré intérieur qui marque le rail. `ADM-005` avait laissé
l'état actif au défaut de la bibliothèque et gardé son critère de vérification visuelle décoché.

Trois bruits se sont ajoutés autour, visibles sur la même capture : les intitulés de groupe pèsent
autant que les liens et se lisent comme des entrées désactivées ; la frontière entre les deux
groupes est marquée deux fois, par un filet *et* par un intitulé ; et « Espace admin » est écrit
trois fois à l'écran.

## Périmètre

- **État actif** de `AdminSidebarNav` : fond mêlé d'accent **et** liseré intérieur côté début de
  ligne.
- **Intitulés de groupe** (« Pilotage », « Supervision ») : démotés en `--muted`, un pas de taille
  en dessous des liens.
- **Filet séparateur** entre les deux groupes : retiré.
- **Sous-titre « Espace admin »** sous le nom de compte dans le menu de l'avatar
  (`AdminTopbar.vue`) : retiré.

## Hors périmètre

- **Le bloc de marque et son alignement** — c'est `WEB-005`, sur la branche du socle.
- **Le badge d'espace sous le logo et le fil d'ariane** : ce sont les deux occurrences d'« Espace
  admin » qu'on garde, la marque et la position.
- **Le tiers vide en bas de colonne** : c'est le résultat voulu de `ADM-009`, qui a retiré le pied
  « Compte » comme doublon. Y remettre quelque chose rouvrirait une décision de deux jours.
- **Le seuil `lg` (1024 px)** qui masque la sidebar sur tablette portrait — élargirait la tâche au
  responsive du layout.
- **La superposition titre + marque dans le tiroir mobile** : vérifiée au passage (cf.
  « Vérification »), la redondance est réelle — mais elle n'est pas traitée ici.
- **Les marqueurs de racine `admin-sidebar-nav` et `admin-mobile-drawer`** : ils ne portent aucun
  style *par construction*, c'est la convention du README de `components/`, et la règle
  `components-root-marker` bloque le merge sans eux. Ils restent.

## Décisions de cadrage

- **Le liseré suit le début de ligne, pas la gauche physique.** La charte parle de « rail de
  gauche », mais elle impose aussi qu'aucune décision visuelle ne suppose un seul sens de lecture :
  en arabe, un liseré fixé à gauche se retrouverait côté contenu. Même coût d'écriture.
- **L'intitulé de groupe survit, le filet part.** L'intitulé nomme le groupe et reste annoncé aux
  lecteurs d'écran ; le filet ne fait que couper, et il s'arrête avant le bord de la colonne, ce qui
  ajoutait un axe d'alignement de plus.
- **C'est le sous-titre de l'avatar qui saute**, pas le badge ni le fil d'ariane : sous le nom du
  compte, répéter l'espace n'apprend rien, et le changement reste local à l'admin — retirer le badge
  toucherait les quatre espaces et reviendrait sur `WEB-001`.
- **Pas de bloc `<style>` réintroduit.** `ADM-005` a supprimé les ~40 lignes de CSS maison en
  passant à `UNavigationMenu` ; la personnalisation passe par les moyens du composant et les
  utilitaires, pas par un retour du CSS à la main.

## Règles et cas limites

- **La nav est un seul composant pour deux surfaces** (sidebar desktop et tiroir mobile, `ADM-004`) :
  tout ce qui change ici change aussi dans le tiroir.
- **Les deux thèmes ne partagent pas leur accent** — vert profond en clair, lime en sombre, et le
  mélange de fond n'a pas le même taux (14 % / 18 %). L'actif se vérifie dans les deux, pas dans un.
- **L'actif ne doit plus reposer sur la seule couleur** : c'est le critère qui justifie la tâche, et
  la capture doit le montrer.
- **Le focus clavier reste intact** : contour accent de deux pixels décollé, imposé par la charte.
  Le fond de l'actif ne doit pas le rendre illisible.
- **La démotion des intitulés rapproche le texte du fond.** À vérifier en thème clair, où `--muted`
  est le plus discret des deux thèmes.

## Documentation impactée

Aucune. La charte porte déjà la règle appliquée ici ; la recopier ailleurs dupliquerait une source
de vérité.

## Livré

- **`AdminSidebarNav`** : un objet `navUi` passé en `:ui` au `UNavigationMenu`, sans bloc `<style>`.
  Il porte les trois réglages — `label: text-muted` (intitulés démotés), `separator: hidden` (filet
  retiré), et sur `link` la pilule `data-[active]:before:bg-accent-soft` plus le liseré
  `data-[active]:after:*` en `start`, largeur `w-0.5`.
- **`AdminTopbar`** : le sous-titre d'espace sous le nom de compte disparaît. L'enveloppe à deux
  lignes n'ayant plus qu'un enfant, elle fusionne avec lui — le nom garde sa troncature et son
  masquage sous `sm`.

## Vérification

Mesures prises dans Chromium sur la stack `:8480`, session admin réelle, aux styles **calculés** —
pas au HTML servi.

| Point mesuré | Thème clair | Thème sombre |
|---|---|---|
| Fond de la pilule active | accent à **14 %** | accent à **18 %** |
| Liseré (`::after`) | `rgb(15,75,55)` · **2 px** · `left: 0` | `rgb(198,231,107)` · **2 px** · `left: 0` |
| Item inactif | fond **transparent** | fond **transparent** |
| Intitulé de groupe | `rgb(71,94,78)` (= `--muted`) | `rgb(165,165,165)` (= `--muted`) |
| Filet séparateur | `display: none` | `display: none` |

- **Le liseré l'emporte sur le défaut de la bibliothèque.** Nuxt UI pose `before:bg-elevated` sur
  l'actif ; les deux règles coexistent dans la feuille servie, mais la nôtre compile en
  `[data-active]::before` et gagne en spécificité. Constaté dans le CSS réellement servi.
- **Le tiroir mobile hérite de tout** en 375 px : pilule, liseré, intitulés démotés, aucun filet.
- **« Espace admin » ne reste que deux fois à l'écran** : le badge de la marque et le fil d'ariane.
  Le document en contient deux autres occurrences qui ne sont pas à l'écran — le titre d'onglet et
  le rideau de boot, qui a son propre badge et disparaît à l'hydratation.
- **La superposition du tiroir mobile est confirmée** : « Navigation » en titre du panneau, puis le
  logo et le badge juste en dessous. Lisible, mais redondant.
- **Non vérifié** : le rendu en arabe. Le liseré est écrit en `start`, donc logique par
  construction, mais aucune capture RTL n'a été prise.

## Reste à faire (hors périmètre)

- **La redondance du tiroir mobile** (titre du panneau + bloc de marque) — constatée, non traitée.
- **Le bloc de marque est encore centré** sur cette branche : `WEB-005` le corrige côté socle et
  n'arrivera ici qu'à son merge.
- **Dette de vérification groupée** : `WEB-001`, `ADM-005`, `ADM-009` et `WEB-005` portent chacune
  une vérification visuelle en attente. Celle-ci, elle, est faite.

## Définition de terminé

- [x] La page courante se reconnaît sans distinguer les couleurs : fond mêlé d'accent + liseré côté
      début de ligne.
- [x] « Pilotage » et « Supervision » se lisent clairement comme des intitulés, pas comme des liens
      désactivés.
- [x] Plus de filet entre les deux groupes.
- [x] « Espace admin » n'apparaît plus que deux fois à l'écran.
- [x] Le tiroir mobile hérite des mêmes états.
- [x] Captures à l'appui, thème clair et thème sombre.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**, en une passe
      pour toute la branche.
