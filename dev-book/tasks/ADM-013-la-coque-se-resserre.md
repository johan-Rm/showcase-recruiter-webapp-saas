# TASK ADM-013 — webapp admin : la coque se resserre

- **Créée le** : 2026-08-02.
- **Statut** : ✅ fait le 2026-08-02 — coque à 4 px de padding et d'écart, rayon des panneaux à 6 px
  par une variable posée sur elle. Mesuré aux styles calculés dans les deux thèmes et sous le seuil
  `lg` ; gates au passage PR.
- **Type** : fix — réglage de la coque.
- **Tests** : sans objet — le diff est un jeu de classes utilitaires sur la balise de coque : ni
  calcul, ni branche, ni service. La suite vitest du webapp couvre le code pur, pas le rendu. Preuve
  par mesure des boîtes et par capture.
- **Réf** : `ADM-012` (la passe de design sur la barre latérale, qui ne touche pas ces
  fichiers) · [sizing.css](../../webapp/base/app/assets/css/sizing.css) (l'échelle d'espacement et
  ses trois paliers) · `cockpit.vue` de l'espace Entreprise, qui porte la même coque.

## Contexte

La coque du dashboard sépare les panneaux de la fenêtre par **9 px** (`p-element`, le plus petit
palier de l'échelle) et les sépare entre eux d'autant. À l'usage, la séparation se lit comme une
marge — un cadre autour de l'application — là où l'intention est une **couture** : des panneaux
posés côte à côte, à peine détachés.

## Périmètre

- **Le padding et les écarts de la coque passent à 4 px**, fixes : la balise de coque et l'écart de
  sa colonne principale.
- **Le rayon des panneaux passe de 8 à 6 px**, par une variable posée sur la coque.

## Hors périmètre

- **La barre latérale elle-même** : c'est `ADM-012`, dans un autre fichier et un autre commit.
- **L'espace Entreprise**, dont le cockpit porte la même coque : le resserrer est une tâche `ENT`.
  La divergence entre les deux dashboards est **assumée le temps que l'autre lane suive**.
- **Le padding intérieur des panneaux** (`p-block`, 18 → 22 px) : c'est lui qui fait respirer le
  contenu, et la remarque ne portait pas dessus.
- **`--spacing-element` et l'échelle du socle** : on ne descend pas un palier partagé par les quatre
  espaces pour un besoin de coque.
- **La primitive `.shell` du socle** — `mx-auto w-full max-w-shell` + gouttière — que les deux
  dashboards réimplémentent à la main. Constat signalé, non traité : le réconcilier touche
  l'entreprise autant que l'admin, donc le socle.

## Décisions de cadrage

- **4 px et non 6.** Les quatre valeurs (9, 6, 4, 2) ont été rendues et comparées en capture. 2 px
  fait disparaître la séparation ; 6 px reste une petite marge ; 4 px donne la couture voulue.
- **Le rayon suit, il n'a pas le choix.** À 4 px d'écart, un rayon de 8 px donne un coin arrondi
  *coupé* par le bord de la fenêtre : l'œil lit une erreur, pas un resserrement. Descendre le rayon
  à 6 px n'est donc pas un embellissement mais la condition du réglage.
- **Une variable plutôt que 32 remplacements**, et **`--ui-radius`, pas `--radius-lg`.** Le cadrage
  visait la seconde : compilée seule, la classe `rounded-lg` sort bien en
  `border-radius: var(--radius-lg)`. Mais dans l'application, la surcharger n'a **rien changé** —
  mesuré à 8 px alors que la variable valait 0,375 rem sur l'élément. La cause est écrite dans
  [nuxt-ui.css](../../webapp/base/app/assets/css/nuxt-ui.css) : Nuxt UI **recalcule toute l'échelle
  de rayons** à partir de `--ui-radius` (`--radius-lg: calc(var(--ui-radius) * 2)`), et c'est cette
  expression que la règle servie porte. Posé sur la coque, `--ui-radius: 0.1875rem` donne les 6 px
  attendus — et reste **borné à cette coque**, là où le changer globalement toucherait les quatre
  espaces, ce que le commentaire du socle met précisément en garde de faire.
- **Une valeur fixe assumée, hors de l'échelle fluide.** Les paliers du socle sont des `clamp()` qui
  grandissent avec l'écran ; 4 px est un `p-1` fixe. C'est voulu : une couture n'a pas de raison de
  s'épaissir sur un grand écran, contrairement à une gouttière. Écrit ici pour qu'une relecture ne
  le prenne pas pour un oubli de token.

## Règles et cas limites

- **Les surfaces portées hors de la coque** n'héritent pas de la variable. Vérifié : le tiroir
  mobile est bien hors coque, mais il n'a **aucun rayon** (feuille pleine hauteur) — rien à
  arbitrer de ce côté.
- **⚠️ Le CSS écrit à la main de la topbar ne suit pas**, et c'est le vrai angle mort : `.icon-btn`
  et `.menu` déclarent `border-radius: 0.5rem` dans un `<style scoped>`, donc ils restent à 8 px
  **à l'intérieur** d'une coque passée à 6. Ce n'est pas un défaut de ce réglage mais la même cause
  que le hamburger visible sur desktop (reste-à-faire d'`ADM-011`) : un bloc de style scopé qui
  réécrit ce que les tokens portent. Les deux se traitent ensemble, dans une carte sur `AdminTopbar`.
- **La hauteur est la ressource rare** : la coque est en `h-[100dvh] overflow-hidden`, donc chaque
  pixel de padding vertical est pris au contenu. Le resserrement en rend 10.
- **Le seuil `lg`** : sous 1024 px la barre disparaît et la coque n'a plus qu'une colonne — le
  réglage doit rester correct là aussi.

## Documentation impactée

Aucune. La charte ne fixe ni le padding de coque ni le rayon des panneaux.

## Livré

**`dashboard.vue`**, sur la seule balise de coque :

- `p-element` et `gap-element` deviennent `p-1` et `gap-1` — 4 px fixes au lieu d'un palier fluide —
  et l'écart de la colonne principale suit.
- `[--ui-radius:0.1875rem]` est posé sur la coque : Nuxt UI recalcule toute son échelle de rayons à
  partir de cette variable, si bien que les 32 `rounded-lg` de l'espace passent à 6 px sans qu'aucun
  des dix fichiers qui les portent ne soit touché.

## Vérification

Styles **calculés** dans Chromium, sur la stack `:8480`.

| Point mesuré | Sombre | Clair | 900 px (sous `lg`) |
|---|---|---|---|
| Padding de la coque | **4 px** | 4 px | 4 px |
| Écart entre panneaux | **4 px** | 4 px | 4 px |
| Rayon barre / topbar | **6 px** | 6 px | 6 px |

- **Les quatre valeurs de padding avant changement** étaient fluides — 8,96 px à 1280, 9,12 à 1440,
  9,60 à 1920, 10 à 2560 — contre 4 px fixes désormais, quelle que soit la largeur.
- **La surface rendue au contenu** : 10 px de hauteur, non négligeables sur une coque en
  `h-[100dvh] overflow-hidden` où rien ne défile au niveau de la page.
- **Le tiroir mobile** n'a aucun rayon (feuille pleine hauteur) : la variable ne le concerne pas,
  vérifié.

## Reste à faire (hors périmètre)

- **Le CSS écrit à la main de la topbar ne suit pas la variable** : `.icon-btn` et `.menu` gardent
  leur `border-radius: 0.5rem` en dur, donc 8 px au milieu d'une coque à 6. Même cause que le
  hamburger visible sur desktop — une carte sur `AdminTopbar` traitera les deux.
- **Le cockpit entreprise porte la même coque à 9 px** : le resserrer est une tâche `ENT`.
- **La primitive `.shell` du socle**, que les deux dashboards réimplémentent à la main.

## Définition de terminé

- [x] Padding et écarts de la coque mesurés à 4 px.
- [x] Rayon des panneaux mesuré à 6 px, sans avoir touché aux 32 `rounded-lg`.
- [x] Aucun coin ne paraît rogné par le bord de la fenêtre, capture à l'appui, deux thèmes.
- [x] Le comportement des menus portés est constaté et tranché.
- [x] Vérifié sous le seuil `lg`, où la coque n'a plus qu'une colonne.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**.
