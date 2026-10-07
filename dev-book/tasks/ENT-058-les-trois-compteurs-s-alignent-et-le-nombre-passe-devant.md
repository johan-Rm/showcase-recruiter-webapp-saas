# TASK ENT-058 — entreprise : les trois compteurs s'alignent, et le nombre passe devant

- **Créée le** : 2026-08-06.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-06 — les trois compteurs occupent la rangée à parts égales, le
  nombre est passé au-dessus de son libellé, l'ensemble centré.
- **Type** : Frontend entreprise — mise en forme d'un bandeau, sans logique nouvelle.
- **Tests** : `sans objet — aucun code exécutable`. Rien ne change que le gabarit des trois
  encadrés : ni les compteurs, ni leur source, ni le geste de « Écartés ». La preuve attendue est la
  **mesure des largeurs à 1024 px** — le seuil où la colonne est la plus étroite — et une **capture**
  dans les deux thèmes.
- **Réf** : **`ENT-047` — amont** (elle a posé la rangée, et écarté les colonnes égales sur une
  mesure faite à quatre cases) · `ENT-049` (elle a retiré le geste de la case « Vus ») ·
  `ProgressHeader.vue`

## Contexte

**Les trois compteurs ne s'alignent pas.** Chacun prend sa largeur naturelle — mesuré 56, 77 et
79 px — et la rangée est calée à gauche, laissant un vide à droite dans une colonne qui en fait deux
cent quatre-vingts. Trois nombres à des abscisses différentes ne se comparent pas d'un coup d'œil :
il faut lire chaque case, alors que le geste attendu est un balayage.

**Et le nombre arrive après son libellé.** `ENT-047` avait mis le libellé sur la première ligne et le
chiffre en dessous. C'est l'inverse de ce qu'on balaie : on cherche « combien », le nom de la case
n'est qu'une confirmation.

## Périmètre

- **Trois colonnes de largeur égale**, occupant toute la largeur disponible.
- **Le nombre au-dessus du libellé**, et non plus l'inverse.
- **Tout centré** dans chaque case.

## Hors périmètre

- **Le libellé « sur N profils chargés »**, qui reste tel quel — son sort dépend d'une tâche amont
  qui n'existe pas encore, voir *Risques*.
- **Les compteurs eux-mêmes** : ni leur source, ni leur portée, ni ce qu'ils comptent.
- **Le geste de « Écartés »**, qui ouvre la corbeille et reste un bouton, désactivé à zéro.
- **Les icônes et leurs teintes**, posées par `ENT-047`.
- **La barre de progression et le rang**, sous les cases.
- **Le sélecteur d'ordre**, qui fait l'objet d'`ENT-057`.

## Décisions de cadrage

### Les colonnes égales redeviennent possibles, et ce n'est pas un revirement de goût

`ENT-047` les avait écartées sur une mesure :

> « Grille de 4 colonnes égales → "Vus 19" reçoit la largeur qu'il faut à "Écartés 0" → **trois
> libellés tronqués** sous 1500 px. »

Cette mesure portait sur **quatre** cases. Il n'en reste que **trois** depuis qu'`ENT-047` a retiré
« À voir ». Et surtout, le chiffre passant sur sa propre ligne, chaque cellule n'a plus à loger
« libellé + chiffre » côte à côte : elle ne loge que son **libellé**. Le plus long est « Écartés »,
sept lettres.

Trois colonnes égales dans les 245 px que la colonne offre à 1024 px font environ 78 px chacune.
L'obstacle d'`ENT-047` a donc disparu deux fois — moins de cases, et des cases moins exigeantes.

**Ce n'est pas acquis pour autant** : c'est une prévision, pas une mesure. La *Définition de terminé*
l'exige mesurée.

### Le nombre devant, parce que c'est lui qu'on cherche

`ENT-047` avait justifié le chiffre en second par « c'est le chiffre qu'on balaie, il gagne à être
seul sur sa ligne ». La première moitié reste vraie, la seconde n'imposait pas l'ordre : seul sur sa
ligne, il l'est dans les deux cas.

Or on ne lit pas ces cases, on les balaie — et un balayage attrape la **première** ligne. Trois
nombres alignés sur une même ligne haute se comparent sans effort ; les libellés en dessous
confirment ce qu'on vient de lire.

### Le nombre remonte à l'œil, pas dans le document

Non tranché par le cadrage, qui disait « le nombre au-dessus » sans dire comment. Les deux moyens ne
se valent pas.

Échanger les deux éléments **dans le document** casserait la paire : une liste de définitions veut
son terme avant sa définition, et une définition qu'aucun terme ne précède n'est plus rattachée à
rien pour qui écoute la page au lieu de la regarder.

L'ordre du document reste donc « libellé, puis nombre », et c'est une propriété de mise en page qui
remonte le nombre. La preuve le vérifie sur les positions réelles à l'écran, jamais sur le code —
c'est la seule façon de s'assurer que les deux ordres divergent bien.

### Centré, parce que la comparaison est horizontale

Des nombres calés à gauche dans des cellules de même largeur restent alignés — le centrage n'apporte
rien de ce côté. Il apporte l'équilibre **dans** la case : un nombre à un chiffre et un nombre à
trois chiffres, centrés, restent visuellement au même endroit, alors qu'alignés à gauche ils tirent
la case vers un bord.

## Règles et cas limites

- **Aucun libellé tronqué à 1024 px**, la largeur où la colonne est la plus étroite. Si « Écartés »
  ne tient pas, c'est la décision de cadrage qui est fausse et il faut le dire, pas rogner le mot.
- **Trois cases, toujours** : la règle du nombre constant tient depuis `ENT-047` — une case dont le
  geste n'a pas lieu d'être se **désactive**, elle ne disparaît pas, sinon la grille change de
  largeur d'un profil à l'autre.
- **« Écartés » reste un bouton** et garde son état désactivé à zéro ; « Vus » et « Favoris »
  restent des constats.
- **L'icône garde sa teinte, le libellé reste lisible** : une couleur ne doit pas payer sa présence
  en contraste (`ENT-047`).
- **Les chiffres restent tabulaires** : sans cela la largeur saute d'une carte à l'autre.
- **Mobile-first** : la console n'apparaît qu'à 1024 px, mais la rangée ne doit pas déborder si la
  colonne rétrécit — le filet de passage à la ligne reste.
- **Un nombre à quatre chiffres** ne casse pas la case : à vérifier, le jeu pouvant dépasser mille.

## Documentation impactée

- **Le commentaire de `ProgressHeader.vue`**, qui décrit aujourd'hui le choix du flex contre la
  grille et cite les largeurs mesurées — il devient faux.
- **Vérifié à l'écriture, non impacté** : `arborescence-entreprise.md` (aucun écran ni parcours
  nouveau), `design-system.md` et `brand-guidelines.md` (aucun jeton nouveau, aucune règle changée),
  `i18n.md` (aucune clé nouvelle).

## Risques et reports assumés

**Le libellé « sur N profils chargés » reste, et il désinforme.** Vérifié dans le code pendant le
cadrage : les trois compteurs **ne comptent pas sur le même ensemble**. « Favoris » et « Écartés »
sont servis à part par le serveur, sans pagination — ils portent sur le **jeu entier**. Seul « Vus »
est limité aux profils chargés, parce que l'état « vu » est déduit des fiches reçues, page par page.

Le libellé est donc posé sous trois nombres alors qu'il n'est vrai que pour **un**.

**Le corriger suppose une tâche amont, dans une autre lane** : que le serveur rende le nombre de
profils vus de la recherche entière. Elle n'existe pas ; elle est **nommée ici, pas écrite** — une
carte de la lane API rédigée depuis celle-ci atterrirait sur `develop` sans le code qu'elle décrit.

Cette carte ne touche donc pas au libellé, délibérément. Le retirer maintenant échangerait une
précision maladroite contre un chiffre franchement trompeur — « Vus 29 » se lirait « 29 sur 225 »
alors qu'il en porte 43.

**À rouvrir dès que la tâche serveur existe**, et pas avant.

## Définition de terminé

- [x] Les trois cases occupent toute la largeur, à parts **égales** — mesuré à 1024 px.
- [x] Aucun libellé n'est tronqué à cette largeur, « Écartés » compris.
- [x] Le nombre est **au-dessus** du libellé, et l'ensemble est centré dans chaque case.
- [x] Un nombre à quatre chiffres ne déforme pas la rangée.
- [x] « Écartés » reste un bouton, désactivé quand la corbeille est vide ; les deux autres restent
      des constats.
- [x] Les icônes gardent leur teinte, les libellés leur lisibilité.
- [x] Le libellé « sur N profils chargés » est **inchangé** — son sort appartient à une autre carte.
- [x] Captures dans les deux thèmes.

## Vérification

### Les largeurs, mesurées aux deux bornes qui comptent

`1024 px` est la largeur où la console apparaît — donc la plus étroite qu'elle connaisse, et la seule
qui puisse tronquer.

```
viewport 1024 px — rangée 245 px
  "Vus 4"       largeur= 79   tronqué=non   nombre au-dessus=✓   <div>
  "Favoris 0"   largeur= 79   tronqué=non   nombre au-dessus=✓   <div>
  "Écartés 0"   largeur= 79   tronqué=non   nombre au-dessus=✓   <button> désactivé
  largeurs ÉGALES ✓ (79/79/79) · occupe la rangée ✓ · aucun libellé tronqué ✓

viewport 1500 px — rangée 330 px
  trois cases de 107 px, mêmes verdicts
```

Le pari du cadrage tient : l'obstacle d'`ENT-047` avait deux causes, une case de trop et un libellé
qui partageait sa ligne avec le nombre. Les deux ont disparu.

### Le nombre est au-dessus À L'ŒIL, et après son libellé dans le document

Mesuré sur les positions réelles à l'écran (`nombre au-dessus = ✓` ci-dessus), pendant que le texte
brut de chaque case sort en `"Vus4"` — le libellé d'abord. Les deux ordres divergent bien, ce qui
est exactement l'intention : la paire terme/définition reste lisible à qui écoute la page.

### Quatre chiffres

Forcés dans les trois compteurs, sans toucher au code :

```
avant        : rangée 245, cases [79, 79, 79], aucun débordement
après (1234) : rangée 245, cases [79, 79, 79], aucun débordement
largeurs toujours ÉGALES ✓ · aucune case ne déborde ✓
```

Les chiffres tabulaires y sont pour beaucoup : sans eux la largeur sauterait à chaque carte franchie.

### Captures

L'entête à 1024 px dans les deux thèmes, plus le cas à quatre chiffres. L'icône garde sa teinte,
« Écartés » reste gris désactivé faute de corbeille à ouvrir.

### Sorties fraîches

```
vitest        19 fichiers, 257 tests passés
conventions   13 règles, aucune violation
```

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `components/search/ProgressHeader.vue` | La rangée passe en trois colonnes égales ; le nombre remonte par la mise en page, pas dans le document ; le commentaire cesse d'affirmer le choix inverse |
| `pages/recherches/[uuid]/table.vue` | **Formatage seul** — voir ci-dessous |

Aucune page de `docs/` n'est déclenchée : ni écran ni emplacement nouveau, aucun jeton, aucune règle
de charte, aucune clé de traduction.

**Une ligne de `table.vue` a été reformatée**, et ce fichier n'appartient pas à cette tâche : il vient
d'`ENT-052`, commitée par une autre session sur la même branche. Sa version enfreignait le
formateur — vérifié : celle de `develop` passe, la sienne non —, et la gate de format bloque la PR
entière. `git rebase -i` n'existe pas dans cet environnement, il n'y avait donc aucun créneau propre
où poser la correction. Trois lignes d'espacement, aucun changement de comportement, signalées ici
plutôt que glissées en silence.

## Reste à faire (hors périmètre)

- **Le libellé « sur N profils chargés » reste**, et il ne concerne qu'une des trois cases : « Vus »
  compte sur les profils chargés quand « Favoris » et « Écartés » comptent sur le jeu entier. Le
  corriger suppose une tâche de la lane API — rendre le nombre de profils vus de la recherche
  entière —, nommée sans être écrite. Détaillé en *Risques*.
