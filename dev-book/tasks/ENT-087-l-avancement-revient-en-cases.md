# TASK ENT-087 — entreprise : l'avancement revient, en cases

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — une jauge de vingt cases précède le rang dans la barre haute.
- **Type** : ajout d'un repère visuel. Aucune donnée nouvelle : elle relit le rang qui existe déjà.
- **⚠️ Cadrée après coup.** Demandée en conversation à partir d'une capture d'un autre produit. Deux
  arbitrages posés à l'humain avant écriture — le comportement sur les grandes recherches et la
  couleur.
- **Tests** : `sans objet` — la carte ne produit aucune logique métier. Une règle de trois et un
  arrondi, dans un composant d'affichage. La preuve est le **relevé de la jauge à chaque profil**
  d'une recherche parcourue de bout en bout, plus une seconde recherche de 293 profils.
- **Réf** : `ENT-071` — qui avait posé une barre de progression dans cette même bande · `ENT-076` —
  qui l'avait supprimée · `ENT-079` — l'infini du défilement, qui rend le total fiable.

## Contexte

Une barre de progression a déjà vécu ici, posée par `ENT-071` et supprimée par `ENT-076`. Elle était
**continue** — un trait qui se remplit — et elle mesurait alors la pile chargée, pas la recherche :
elle atteignait sa fin au trentième profil d'une recherche qui en comptait trois cents.

Deux choses ont changé depuis. `ENT-079` a rendu le total **vrai** — le défilement couvre désormais
la recherche entière. Et la demande porte cette fois sur une jauge **en cases**, qui ne se lit pas
comme un trait : on compte des cases du coin de l'œil, on estime la longueur d'un trait.

## Périmètre

- **Une jauge de vingt cases**, à gauche du rang, dans la barre haute.
- **Un remplissage au prorata** de la recherche entière.
- **Une case entamée** en tête du remplissage, à teinte diluée.
- **Le vert de la marque** pour les cases allumées.

## Hors périmètre

- **Le rang « 8 / 234 »**, qui ne bouge pas — la jauge se pose à côté de lui.
- **Les trois chiffres du tri** dans la colonne de gauche (`ProgressHeader`), qui comptent des gestes
  et non un avancement.
- **Les deux archives**, qui gardent leur barre haute.

## Décisions de cadrage

### Vingt cases fixes, jamais une case par profil

**Arbitré.** Une case par profil est le dessin de la capture, et il ne survit pas à nos volumes : les
recherches vont de trois profils à trois mille, et trois mille cases n'existent pas. Les deux autres
issues examinées étaient un dessin qui bascule au-delà d'une trentaine de profils — l'écran change
alors d'allure d'une recherche à l'autre — et une fenêtre glissante autour du profil courant, qui
aurait cessé de dire l'avancement global.

Vingt est un compromis de largeur. En dessous, la jauge cesse de distinguer les avancements proches :
à dix cases, le tiers et le quart de la recherche donnent le même dessin. Au-dessus, elle mange une
bande qu'elle partage avec le rang et les deux entrées vers les filtres.

**Contrepartie assumée** : sur une recherche de trois cents profils, la jauge avance d'une case tous
les quinze profils. Elle dit une vérité décourageante, et c'en est une.

### La case entamée est ce qui sauve les grandes recherches

Au sixième profil d'une recherche qui en compte 293, le prorata ne remplit pas un vingtième de la
barre. Sans elle, la jauge resterait **éteinte pendant les quatorze premiers profils** et se lirait
comme une panne. Elle dit « c'est commencé » là où l'arrondi dirait « rien » — mesuré, c'est
exactement ce qui se produit.

### Le vert de la marque, et il tombe juste sur les deux thèmes

**Arbitré**, et le résultat vaut d'être noté : en thème sombre, `--accent` est déjà le lime
`#c6e76b` — **la couleur exacte de la capture**, à l'œil près. La demande de style et la règle de
marque désignaient le même vert sans le savoir.

Les deux autres pistes étaient le `--state-met` olive, qui dit déjà « profil déjà croisé » et aurait
porté deux sens, et un gris neutre, qui aurait fait disparaître la jauge.

### Elle ne s'annonce pas

Le rang qu'elle double, à huit pixels d'elle, porte déjà sa phrase pour qui écoute la page. Deux fois
la même information à l'oreille est une gêne, pas un service — d'où `aria-hidden`.

### Elle cède sur téléphone, le rang reste

Vingt cases et deux entrées de filtres ne tiennent pas ensemble dans la bande. C'est la jauge qui
disparaît sous 640 px : elle est le doublon visuel du rang, et lui seul porte le chiffre.

## Règles et cas limites

- **Recherche sans résultat** : la page ne monte ni le rang ni la jauge — une jauge éteinte sur un
  écran vide se lit comme une panne, tout comme « 0 / 0 ».
- **Total nul** : le composant retombe sur zéro plutôt que de diviser par zéro, même si la page ne le
  monte pas dans ce cas.
- **Dernier profil** : les vingt cases sont pleines, aucune case entamée.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni convention de dossier.

## Définition de terminé

- [x] La jauge précède le rang dans la barre haute.
- [x] Elle compte vingt cases quelle que soit la taille de la recherche.
- [x] Elle avance avec le profil regardé.
- [x] Elle montre quelque chose dès le premier profil d'une grande recherche.
- [x] Elle n'est pas annoncée deux fois aux lecteurs d'écran.

## Vérification

### La recherche de 17 profils, parcourue de bout en bout

Relevé à chaque profil — `#` case pleine, `~` case entamée, `.` case éteinte :

```
#~..................    1 / 17
##~.................    2 / 17
####~...............    4 / 17
#####~..............    5 / 17
#######~............    6 / 17
#########~..........    8 / 17
##########~.........    9 / 17
##############~.....   12 / 17
###############~....   13 / 17
##################~.   16 / 17
####################   17 / 17
```

### La recherche de 293 profils, aux six premiers

```
~...................    1 / 293
~...................    6 / 293
```

Une case entamée, et elle le reste jusqu'au quinzième profil. C'est le cas que la décision visait.

### Les teintes calculées, dans les deux thèmes

```
sombre   pleine  rgb(198, 231, 107)   ← #c6e76b, le lime de la capture
         entamée oklab(… / 0.4)
         éteinte rgb(67, 67, 67)

clair    pleine  rgb(15, 75, 55)      ← #0f4b37, le vert Cèdre
         entamée oklab(… / 0.4)
         éteinte rgb(205, 218, 207)
```

### La mesure

```
cases                    20
largeur de la jauge     158 px
hauteur d'une case       16 px
écart jauge / rang        8 px
aria-hidden            true
```

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Progress.vue` | (+) la jauge en cases |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) elle précède le rang dans la barre haute |

## Reste à faire (hors périmètre)

- **Rien n'est ouvert par cette carte.**
