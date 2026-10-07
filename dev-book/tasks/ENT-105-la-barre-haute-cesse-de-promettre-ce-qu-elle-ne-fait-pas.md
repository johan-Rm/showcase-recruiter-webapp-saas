# TASK ENT-105 — entreprise : la barre haute cesse de promettre ce qu'elle ne fait pas

- **Créée le** : 2026-08-12.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-12 — les deux boutons ont disparu, et la droite de la bande est
  une colonne à l'aplomb du verdict.
- **Type** : mise en page. Aucune donnée, aucune règle, aucun geste ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle retire un bloc et pose une
  largeur. La preuve est la **mesure des deux bords** — celui de la colonne de droite de la barre et
  celui de la colonne du verdict doivent tomber à la même abscisse.
- **Réf** : `ENT-071` — qui a posé le rang dans cette bande · `ENT-087` — la jauge qui l'y a rejoint ·
  `ENT-092` — la colonne du verdict, dont la largeur sert ici de référence · l'écran de modification
  d'une recherche, qui porte les filtres.

## Contexte

La bande haute de la Visionneuse porte trois choses : le nom de la recherche à gauche, deux boutons
au centre, la jauge et le rang à droite.

**Les deux boutons du centre sont désactivés depuis leur pose.** Rien ne les branche : ils affichent
« Classiques » et « Secondaires », et ne répondent pas au clic. Un bouton d'apparence normale qui ne
répond pas se lit comme une panne — c'est d'ailleurs pour cette raison qu'ils avaient été désactivés
plutôt que masqués, en attendant un branchement qui n'est jamais venu.

**Et la droite de la bande flotte.** La jauge et le rang se posent en bout de ligne, à une abscisse
qui ne doit rien à ce qu'ils surplombent. En dessous, l'écran est pourtant nettement découpé en trois
bandes verticales — la console, la fiche, le verdict.

## Périmètre

- **Les deux boutons de filtres disparaissent**, et la barre reprend sa forme à deux colonnes.
- **La colonne de droite de la barre prend la largeur de la colonne du verdict**, en dessous.

## Hors périmètre

- **Les six autres écrans** qui montent la même barre haute : leur droite reste dimensionnée sur son
  contenu.
- **Les filtres eux-mêmes**, qui continuent de servir sur l'écran de création et sur celui de
  modification.
- **Les deux clés de traduction** `search.console.primary` et `secondary` : les archives `-v1` et
  `-v2` les consomment encore.
- **La Table**, la console, la fiche.

## Décisions de cadrage

### Deux colonnes, c'est déjà le défaut — il n'y a rien à ajouter

La barre haute bascule seule entre deux et trois colonnes selon qu'on lui déclare un centre ou non.
**Six écrans sur sept ne lui en déclarent pas** ; la Visionneuse est la seule exception. Retirer les
deux boutons ne demande donc aucune modification du composant : c'est la suppression d'un bloc, et la
forme à deux colonnes revient d'elle-même.

### Filtrer depuis la Visionneuse est abandonné, pas reporté

**Arbitré.** Cet écran sert à **choisir** parmi un jeu déjà constitué — on parcourt, on retient, on
écarte. Changer les critères, c'est **trouver**, et ça se fait sur l'écran de modification de la
recherche, qui existe et fonctionne.

Les deux boutons ne laissent donc rien derrière eux, et aucune tâche aval n'est ouverte. Contrepartie
acceptée : ajuster un critère demande un aller-retour vers un autre écran.

### L'alignement est propre à la Visionneuse

**Arbitré.** C'est la **page** qui passe la largeur à sa barre ; le composant reste neutre et les six
autres écrans ne bougent pas. Une règle générale — « toute barre haute aligne sa droite sur la
colonne de droite de son contenu » — aurait demandé de vérifier six écrans qui n'ont rien demandé,
pour un gain de cohérence théorique.

La valeur ne se recopie pas : c'est **le même gabarit de colonne** que celui du bloc de contenu qui
doit être employé, sans quoi les deux se désaccordent au premier ajustement.


### ⚠️ Écart constaté à l'implémentation — la colonne ne vaut qu'à partir du seuil

La carte disait déjà que sous 1280 px il n'y a plus rien à aligner. La première version l'ignorait :
elle gardait une colonne de 360 px à toutes les largeurs, et réservait ainsi un tiers de la bande à
une jauge qui en occupe cent. Le drapeau ne s'applique donc **qu'à partir de `xl`** — le seuil exact
où le verdict a sa propre colonne. En dessous, la bande retrouve une fin dimensionnée sur son
contenu : 220 px mesurés à 1150.

### ⚠️ Deux pixels d'écart à 1920, et ils sont structurels

La bande porte une marge intérieure à gauche que la zone de travail n'a pas. La fraction ne porte
donc pas sur exactement la même largeur, et le bord glisse de deux pixels à 1920. L'écart tombe à
zéro dès que la colonne bute sur son plancher de 360 px — mesuré nul à 1440.

L'annuler demanderait de reproduire dans la bande la grille à **deux niveaux** de la zone de travail
— console/bloc, puis fiche/verdict. Beaucoup de mécanique dans un composant partagé par sept écrans,
pour deux pixels : **non fait, et assumé**.

## Règles et cas limites

- **Sous 1280 px, le verdict n'a plus de colonne** : il retombe en pied de fiche. Il n'y a alors plus
  rien à aligner, et la droite de la barre reprend sa largeur de contenu.
- **Recherche sans résultat** : ni jauge ni rang — la colonne de droite existe mais reste vide. Elle
  garde sa largeur, comme le bas de la colonne du verdict garde la sienne.
- **Recherche d'un collègue** (`editable` faux) : le centre était déjà absent, la barre était déjà à
  deux colonnes. Cet écran ne change pas.
- **Le nom de la recherche** occupe la colonne de gauche et cède en premier : c'est lui qui se
  tronque, jamais le rang.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : vérifié, aucune ne mentionne ces deux boutons. La carte de
navigation décrit la bande par le nom de la recherche et le rang, ce qui reste vrai. Aucun `README.md`
ne change de convention.

## Vérification

### L'aplomb, mesuré à trois largeurs

Bord gauche de la colonne de fin de la bande, contre bord gauche de la colonne du verdict :

```
1920 px   bande 1534 · verdict 1532   écart  2 px
1440 px   bande 1071 · verdict 1071   écart  0 px
1150 px   bande dimensionnée sur son contenu (220 px) — le verdict n'a plus de colonne
```

### Les deux boutons

```
cellules de la bande            2
« Classiques » encore présent   false
```

### Les six autres écrans

Le drapeau est **faux par défaut** : la Table, l'écran de création, celui de modification et les
gabarits de maquette montent la même bande sans le passer, et gardent une fin dimensionnée sur leur
contenu.

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/ActionBar.vue` | (M) un drapeau `end-column`, faux par défaut, qui n'agit qu'à partir de `xl` |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) le centre disparaît, la bande demande sa colonne |

## Reste à faire (hors périmètre)

- **Les deux clés de traduction** `search.console.primary` et `secondary` n'ont plus de consommateur
  sur la ligne vivante — mais les deux archives `-v1` et `-v2` les emploient encore. Les retirer
  suppose de trancher d'abord le sort de ces archives.
- **Les deux pixels d'écart à 1920**, si un jour ils dérangent.

## Dépendances

- **Amont** : aucune.
- **Aval** : aucune. Les filtres restent où ils sont.

## Risques et reports assumés

- **La bande perd son seul contenu central**, et donc toute possibilité d'en accueillir un autre sans
  redéclarer un centre. C'est réversible en une ligne.
- **Le dossier de travail est occupé** par une autre session au moment du cadrage — cinq fichiers
  modifiés et trois cartes non commitées. Cette carte n'écrit aucun fichier de code et attend que le
  dossier se libère.

## Définition de terminé

- [x] Les deux boutons de filtres ont disparu de la Visionneuse.
- [x] La barre haute y est à deux colonnes.
- [x] Le bord gauche de sa colonne de droite tombe à la même abscisse que celui de la colonne du
      verdict.
- [x] Sous 1280 px, la droite de la barre reprend sa largeur de contenu.
- [x] Les six autres écrans qui montent la même barre sont inchangés.
