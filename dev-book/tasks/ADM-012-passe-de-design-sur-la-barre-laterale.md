# TASK ADM-012 — webapp admin : passe de design sur la barre latérale

- **Créée le** : 2026-08-02.
- **Statut** : ✅ fait le 2026-08-02 — icônes à 16 px, cartouche neutre et libellé neutre, repère
  plein de 4 px au bord de fin. La lueur explorée en cours de route a été retirée. Vérifié aux
  styles calculés dans les deux thèmes et dans le tiroir mobile ; gates au passage PR.
- **Type** : fix — réglages visuels de la navigation.
- **Tests** : sans objet — le diff est un jeu de classes utilitaires passé au thème du composant :
  ni calcul, ni branche, ni service. La suite vitest du webapp couvre `shared/`, `services/` et,
  depuis `ADM-011`, les `utils/` d'un espace : du code pur, précisément ce que ce changement ne
  produit pas. Preuve par styles calculés et par capture, dans les deux thèmes.
- **Réf** : `ADM-010` (la passe de design précédente, qui a posé la pilule et le liseré) ·
  `ADM-011` (la source unique de navigation, et le `navUi` où se posent ces réglages) · `ADM-005`
  (la nav en `UNavigationMenu`, dont le thème porte les valeurs par défaut) ·
  [brand-guidelines.md, § Éléments interactifs](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md).

> ⚠️ **Carte écrite après le code**, les deux réglages ayant été trouvés par essais visuels
> successifs : plusieurs valeurs rendues et comparées en capture avant que l'une soit retenue. Elle
> n'a donc pas eu de cadrage préalable — ce que la nature du changement rendait peu coûteux, mais
> qui reste une entorse au cycle.

## Contexte

Deux remarques de relecture visuelle, dans la même colonne.

**Les icônes pèsent trop.** Elles font 20 px — la valeur par défaut de `UNavigationMenu` — pour un
libellé de 14 px, soit 1,43× la taille du texte. L'icône passe avant le mot dans le poids visuel là
où elle devrait l'accompagner ; sur sept entrées empilées, la colonne se lit comme une suite de
pictogrammes plutôt que comme une liste de destinations.

**Le repère d'item actif ne dit pas ce qu'il pourrait.** Posé par `ADM-010` au ras du bord de début
de ligne, sur 2 px, il se lit comme le bord de la pilule plutôt que comme une marque posée dedans.
Le parti retenu le déplace au bord de **fin** — là où l'œil finit la ligne —, l'épaissit, et le pose
sur une cartouche restée **neutre** au lieu d'un fond teinté d'accent.

## Périmètre

Tout se joue dans le `navUi` d'`AdminSidebarNav`, donc dans le thème du composant.

- **`linkLeadingIcon`** : `size-4` (16 px), soit 1,14× le libellé.
- **Le repère passe du côté `start` au côté `end`** — le côté de fin de lecture — et devient un
  trait **plein de 4 px** : `inset-y-0 end-0`, toute la hauteur, au ras du bord, avec le rayon de la
  pilule (6 px) du seul côté qu'il touche.
- **La cartouche devient neutre** (`--bg`, sans contour) et **le libellé et l'icône aussi**
  (`text-highlighted`) : la ligne active se distingue par sa valeur, pas par une teinte.

## Hors périmètre

- **L'écart icône-libellé** (6 px) et la hauteur de ligne (32 px) : la mesure ne les désigne pas.
- **La couleur et l'épaisseur du trait des icônes** : les `i-lucide-*` sont rendues en masque CSS,
  leur graisse n'est pas pilotable par le style — seule la taille l'est.
- **Les icônes hors barre latérale** — topbar, tables, boutons : voisinage typographique différent,
  rien ne dit qu'elles souffrent du même écart.
- **La charte elle-même** : `brand-guidelines.md` n'est pas touchée, l'écart est assumé côté admin
  seulement (cf. *Décisions*).
- **Les trois autres espaces** : l'Espace Entreprise garde le fond mêlé d'accent prescrit par la
  charte. La divergence est voulue, pas subie.
- **Le hamburger visible sur desktop**, reste-à-faire d'`ADM-011` : fichier voisin, sujet différent.

## Décisions de cadrage

- **16 px plutôt que 18** : les deux ont été rendues et comparées. À 18 px l'icône reste un repère
  distinct pour qui navigue à l'œil ; à 16 px elle se met au niveau du texte et la colonne se lit
  comme des mots. C'est ce second parti qui répond à la remarque.
- **⚠️ ÉCART À LA CHARTE, assumé, et limité à l'admin.**
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) prescrit pour la
  ligne active un fond mêlé d'accent et un liseré — c'est exactement ce qu'`ADM-010` avait livré.
  Le parti retenu ici est l'inverse : **cartouche neutre, repère franc au bord**. La charte **ne bouge
  pas** et l'Espace Entreprise garde le fond mêlé d'accent ; la divergence entre les deux espaces
  est **voulue**, arbitrée en relecture, et écrite ici pour qu'une future relecture ne la prenne pas
  pour un oubli. Si le parti devait s'étendre, c'est la lane design qui reprendrait la charte.
- **Conséquence mesurée, et acceptée** : la cartouche n'est plus une forme visible — `--bg` contre
  la colonne `--surface` donne **1,08:1** dans les deux thèmes. Ce qui distingue la ligne active
  sans passer par la teinte devient le **libellé** : blanc contre `rgb(165,165,165)` en sombre,
  quasi noir contre `rgb(71,94,78)` en clair. Le critère d'`ADM-010` — ne pas reposer sur la seule
  couleur — tient donc toujours, mais par le texte et non plus par le fond.
- **Le repère reste écrit en logique de lecture** (`end`, pas `right`) alors même que rien ne
  bascule aujourd'hui — cf. la règle ci-dessous. Le jour où la locale sera branchée, il suivra sans
  qu'on y revienne ; l'écrire en `right` figerait la dette.
- **⚠️ La lueur a été explorée puis ABANDONNÉE.** Une référence visuelle apportée en relecture
  proposait un bouton lumineux diffusant vers l'intérieur ; quatre versions ont été rendues et
  comparées — dégradé sur toute la bande, puis à 45 %, 28 %, et enfin une décroissance continue
  interpolée en `oklab`. Le rendu final a été jugé bon, **puis écarté** : la notion de luminosité
  est retirée de la cartouche. Ce qui reste d'appris, et qui vaut d'être écrit : c'est le
  **changement de pente** d'un dégradé qui se voit comme un bord, pas son opacité — une butée
  franche suivie d'une pente unique laissait apparaître une coupure à opacité pourtant faible.
- **Le repère est plein, ou il n'est pas.** Un trait de 4 px à pleine intensité dit la position sans
  interprétation ; une lueur demande à l'œil de deviner où elle commence.
- **Les réglages passent par le thème du composant**, jamais par une classe sur le template ni par
  un bloc `<style>` : c'est la voie ouverte par `ADM-005`, qui avait supprimé le CSS maison, et
  celle que `navUi` utilise déjà.
- **L'icône est neutralisée par `group-data-[active]`**, pas par une classe simple : la bibliothèque
  la teinte en accent, et deux utilitaires de couleur au même poids se départagent par l'ordre de la
  feuille de style. Le variant de groupe passe devant par sa spécificité, quel que soit cet ordre.

## Règles et cas limites

- **Un seul composant, deux surfaces** : le tiroir mobile rend la même nav (`ADM-004`), les réglages
  s'y appliquent — à vérifier en 375 px.
- **La cartouche dépend de `--bg`, donc de `WEB-008`** — l'inversion des fonds en thème sombre, sur
  la lane socle. Avant elle, la cartouche était plus **claire** que la barre ; après, plus sombre :
  elle se lit comme une encoche creusée. Le repère de bord garde son rôle dans les deux cas, mais
  l'allure change selon l'ordre de merge des deux lanes.
- **⚠️ Le menu est figé en `ltr`, et ce n'est pas cette lane qui le corrige.** Sondé sur
  `/ar/utilisateurs` : `<html dir="rtl">`, mais le `<nav>` écrit par la bibliothèque porte
  `dir="ltr"` et la direction calculée sur le lien est `ltr` — l'icône reste à 10 px du bord gauche,
  rien ne miroite. La cause : `<UApp>` n'existe nulle part dans `webapp/base/app`, or c'est lui qui
  fournit la locale au `ConfigProvider` de Nuxt UI, donc le `dir` de tous ses composants. **Effet
  rétroactif** : le choix d'`ADM-010` de poser le liseré en `start` « pour suivre le sens de lecture
  en arabe » n'a jamais rien miroité non plus — sa carte le notait comme non vérifié. Le correctif
  appartient à `espace/socle-webapp`.
- **L'état actif ne doit toujours pas reposer sur la seule couleur** : c'est désormais le libellé
  qui le porte, blanc quand les autres sont gris. Critère d'`ADM-010`, préservé par un autre moyen —
  et c'est ce déplacement qu'il faut vérifier, pas seulement le rendu.

## Documentation impactée

Aucune **dans ce commit**. La charte reste en l'état par décision : l'écart est admin-only et écrit
dans cette carte. Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit le thème de ce composant.

## Livré

**`AdminSidebarNav.vue`**, dans le seul objet `navUi` passé au thème du composant :

- `linkLeadingIcon` : `size-4` (16 px), plus `group-data-[active]:text-highlighted` pour neutraliser
  la teinte que la bibliothèque pose sur l'icône active.
- Le repère `after` passe de `start-0` / `w-0.5` à `end-0` / `w-1`, en accent plein, pleine hauteur,
  arrondi `6px` du seul côté qu'il touche.
- La cartouche `before` passe d'`accent-soft` à `bg-bg`, et le lien à `text-highlighted`.

## Vérification

Styles **calculés** dans Chromium, sur la stack `:8480`, session admin réelle.

| Point mesuré | Thème sombre | Thème clair |
|---|---|---|
| Icône de nav | **16 px** | 16 px |
| Repère de l'item actif | **4 px** · `rgb(198,231,107)` | 4 px · `rgb(15,75,55)` |
| Effet lumineux résiduel | `filter: none` | `filter: none` |
| Cartouche | `rgb(26,26,26)` | `rgb(236,240,239)` |

- **Le tiroir mobile hérite de tout**, mesuré à 375 px : sept icônes à 16 px, repère de 4 px en
  accent, `filter: none`, titre toujours en `sr-only`.
- **Le libellé actif reste distinguable sans la couleur** : blanc contre `rgb(165,165,165)` en
  sombre, quasi noir contre `rgb(71,94,78)` en clair. C'est ce qui préserve le critère d'`ADM-010`,
  puisque la cartouche, elle, ne contraste qu'à 1,08:1 avec la colonne.
- **Non vérifié** : le rendu en arabe de cette passe. Le repère est écrit en `end`, mais le menu est
  figé en `ltr` faute de `<UApp>` — cf. *Règles*, le sujet appartient au socle.

## Reste à faire (hors périmètre)

- **Le CSS écrit à la main de la topbar ne suit pas les tokens** : `.icon-btn` et `.menu` déclarent
  leur rayon en dur, et le hamburger reste visible sur desktop parce que `display: grid` bat
  `lg:hidden` en spécificité. Même cause, une seule carte à ouvrir sur `AdminTopbar`.
- **Le menu est figé en `ltr`** faute de `<UApp>` : lane socle-webapp.

## Définition de terminé

- [x] L'icône de la barre mesure 16 px, mesuré aux styles calculés.
- [x] Le repère est au bord de fin : 4 px d'accent **plein**, pleine hauteur, arrondi comme la
      cartouche.
- [x] Aucun effet lumineux ne subsiste — ni dégradé, ni halo : `filter` vaut `none`.
- [x] Le libellé actif reste distinguable **sans la couleur** : blanc contre gris.
- [x] Le tiroir mobile hérite des mêmes réglages.
- [x] Captures à l'appui, thème clair et thème sombre.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**, en une passe
      pour toute la branche.
