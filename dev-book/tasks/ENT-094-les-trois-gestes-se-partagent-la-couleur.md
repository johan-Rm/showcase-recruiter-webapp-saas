# TASK ENT-094 — entreprise : les trois gestes se partagent la couleur

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — un seul aplat sur l'écran, et il est sur le geste qu'on répète
  cent fois. Les deux autres se distinguent par leur teinte, pas par leur poids.
- **Type** : habillage. Aucune donnée, aucune règle, aucun geste ne change de comportement.
- **⚠️ Cadrée après coup, et sans cadrage.** Les deux réglages ont été demandés comme **essais hors
  tâche**, arbitrés à l'œil sur planche de comparaison, puis validés. Cette carte les rattrape :
  elle écrit la règle qu'ils dessinent, pour que la prochaine session ne la défasse pas sans savoir
  qu'elle existait.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Trois classes de présentation. La
  preuve est la **mesure des styles calculés** sur le rendu.
- **Réf** : `ENT-082` — qui avait posé ces deux boutons · `ENT-088` — qui réservait l'aplat à la
  sortie vers la Table · `ENT-092` — qui l'a donné à *Retenir* · `ENT-093` — le badge « Écarté », qui
  partage la teinte posée ici.

## Contexte

Trois gestes vivent sur cet écran, et **deux portaient un aplat de couleur pleine** : *Retenir*, dans
la colonne du verdict, et *Ma sélection*, en pied de colonne de gauche. Deux aplats sur un même écran
s'annulent — aucun des deux ne dit plus « c'est ici que ça se passe ».

`ENT-088` avait tranché l'inverse : l'aplat appartenait à la sortie vers la Table, et *Retenir* s'en
tenait au contour. `ENT-092` a déplacé l'aplat sur *Retenir* sans retirer l'autre. Cette carte ferme
la parenthèse.

Le troisième geste, *Écarter*, était en gris neutre : rien ne le nommait, alors que sa notion a une
couleur dans le produit depuis longtemps.

## Périmètre

- **Un seul aplat**, sur *Retenir*.
- **Ma sélection** passe en fond très doux, sans trait.
- **Écarter** prend la teinte des écartés, en trait dilué.
- **La charte** dit ce que `--verdict-missed` signe désormais.

## Hors périmètre

- **La forme, la taille et les libellés** des trois gestes, qui ne bougent pas.
- **Le comportement** : ce qu'ils font, et à qui, est inchangé.
- **`--verdict-met`**, qui n'a plus aucun consommateur — voir *Reste à faire*.

## Décisions de cadrage

### Un aplat, et il va au geste qu'on répète

Sur un écran où l'on tranche des centaines de profils, *Retenir* est **le** geste. La sortie vers la
Table se pose une fois par séance. Elle rend donc son aplat et garde sa place — pleine largeur,
collée au bord — qui suffit à la trouver.

Le fond accent à douze pour cent la maintient visible sans la mettre au même rang. En dessous elle
disparaît : mesuré, à huit pour cent il ne reste qu'un texte posé sur le fond.

### Un fond dilué plutôt qu'un contour

**Arbitré sur planche**, huit variantes comparées sur le fond réel de la colonne. Le contour tenait
mieux la forme, le fond seul est plus calme — et c'est le calme qui a été retenu, l'aplat de
*Retenir* étant désormais seul à réclamer l'œil.

⚠️ **Le trait d'origine était invisible pour une raison mesurable** : la variante `outline` de Nuxt UI
dessine un anneau à **cinquante pour cent d'opacité**, et non une bordure — la propriété `border` du
bouton vaut zéro. Sur un fond déjà teinté, il ne restait presque rien.

### Écarter prend la couleur des écartés, et pas celle de l'alerte

`--alert` est réservé au feedback bloquant — la charte l'écrit, et un geste volontaire n'est pas une
erreur. `--verdict-missed` est la bonne : elle compte déjà les écartés dans le panneau de gauche, et
depuis `ENT-093` elle porte le badge « Écarté » sur le profil. **Le bouton en devient la troisième
échelle** : le geste, la marque, le total.

**Le trait reste dilué à quarante pour cent.** À pleine opacité il dispute le regard au geste vert
d'à côté, et *Écarter* vient après *Retenir* : sa couleur le nomme, elle ne le promeut pas.

### ⚠️ La charte disait deux choses devenues fausses

Découvert en écrivant cette carte, et corrigé ici parce que c'est ce commit qui achève de les rendre
fausses.

- **« Elles ne teintent que le SIGNE et le MOT du verdict »** — `--verdict-missed` teinte désormais
  un badge et un bouton. La phrase valait quand la famille ne servait qu'une liste de critères.
- **« `--verdict-met` signe le critère satisfait »** — c'est l'accent qui le signe depuis `ENT-092`.
  Le token n'a plus de consommateur, et la charte l'annonçait encore comme actif.

## Règles et cas limites

- **Recherche d'un collègue** (`editable` faux) : aucun des deux gestes du verdict n'existe, la
  question ne se pose pas.
- **Profil déjà retenu** : *Retenir* passe en version douce, et reste le seul aplat quand il est
  plein.
- **Thème clair** : les trois teintes suivent leurs tokens, aucune valeur n'est écrite en dur.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`** — la section des couleurs de verdict, sur
  les deux points ci-dessus.

## Définition de terminé

- [x] Un seul aplat de couleur sur l'écran, sur *Retenir*.
- [x] *Ma sélection* porte un fond dilué, sans trait.
- [x] *Écarter* porte la teinte des écartés, trait à quarante pour cent.
- [x] La charte ne décrit plus un usage qui n'existe plus.

## Vérification

### Les trois gestes, styles calculés

```
Retenir        aplat accent plein            (inchangé, posé par ENT-092)
Écarter        encre  rgb(197, 114, 120)     #c57278, la teinte des écartés
               trait  color-mix(#c57278 40 %, transparent)
Ma sélection   fond   accent à 12 %
               trait  aucun — la variable d'anneau est vide
               icône  16 px
```

### Ce que la planche a montré

Huit variantes du pied de colonne, rendues sur le fond réel, dans les deux thèmes. Le fond seul l'a
emporté sur le contour ; en dessous de douze pour cent, le bouton cesse d'exister en thème sombre.

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/ControlPanel.vue` | (M) *Ma sélection* rend son aplat, fond dilué, icône affinée |
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) *Écarter* prend la teinte des écartés |
| `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` | (M) ce que `--verdict-missed` signe, ce que `--verdict-met` ne signe plus |

## Reste à faire (hors périmètre)

- **`--verdict-met` n'a plus aucun consommateur.** Le retirer touche le nuancier, les thèmes, les
  utilitaires et la charte : c'est un geste de socle, et un token déclaré sans emploi ne casse rien.
