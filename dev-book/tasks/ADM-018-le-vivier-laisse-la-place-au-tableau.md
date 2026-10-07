# TASK ADM-018 — le vivier laisse la place au tableau

- **Créée le** : 2026-08-07.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042`.
- **Statut** : ✅ fait le 2026-08-09 — l'écran montre **10 candidats sur un 1920 au lieu de 7**, 7 au
  lieu de 3 sur un 1280×800, **5 au lieu d'un seul** sur une fenêtre basse ; et les chiffres de
  remplissage restent à l'écran quand on descend dans la liste, ce qui était tout l'objet. Deux
  défauts que les mesures ne voyaient pas ont été trouvés **à la capture** et corrigés : la valeur
  se rattachait au mauvais libellé, et la jauge partait du mauvais bord en arabe. Le test annoncé a
  changé de forme — le calcul est extrait dans `utils/` et couvert là, cf. *Décisions de cadrage*.
- **Type** : refactor — l'écran ne gagne aucune fonction. Il réorganise ce qu'il affiche déjà, pour
  rendre au tableau la place que sa décoration lui prenait.
- **Tests** : test unitaire Vitest sur `utils/fillRate.ts` — **forme révisée à l'implémentation**,
  cf. *Décisions de cadrage* : le dépôt ne monte pas de composants, le calcul est donc extrait et
  couvert là où il vit. Quatre cas : détenu partout (100), détenu nulle part (0), arrondi à l'entier
  le plus proche sur deux valeurs réelles du vivier, et base vide qui rend 0 et jamais `NaN`. La
  mise en page, elle, ne se teste pas en unitaire : jsdom ne calcule aucune hauteur. Elle se prouve
  par une **mesure dans le navigateur authentifié**, aux trois formats du relevé ci-dessous.
- **Réf** : **`WEB-016` — amont, bloquante** (le palier de hauteur de bande, sur la lane
  `espace/socle-webapp`) · [DashboardBody.vue](../../webapp/spaces/admin/app/components/DashboardBody.vue),
  dont le gabarit A/B/C/D porte tout ce cadrage ·
  [Gutter.vue](../../webapp/spaces/entreprise/app/components/Gutter.vue), la bande de l'espace
  entreprise dont on reprend la mesure · fichiers touchés :
  [admin/app/pages/candidats.vue](../../webapp/spaces/admin/app/pages/candidats.vue),
  [admin/app/components/FillRateSection.vue](../../webapp/spaces/admin/app/components/FillRateSection.vue).

## Contexte

L'écran s'appelle **« Le vivier »** dans le menu de l'espace admin, sous *Comptes*. C'est la liste de
tous les candidats de la base — 2 561 aujourd'hui —, avec au-dessus dix indicateurs qui disent, pour
chaque information (prénom, LinkedIn, photo, e-mail…), la part des profils pour lesquels on la
détient.

La coque de l'espace admin est **plein écran** : elle occupe exactement la fenêtre, sans jamais faire
défiler la page entière, précisément pour qu'un écran de travail montre le maximum d'un coup. Cet
écran-là ne tient pas la promesse.

### Ce qu'on voit, et ce que ça coûte

**Le tableau n'a presque pas de place.** Le titre et la grille des dix indicateurs occupent plus de
40 % de la hauteur, et il ne reste que :

| Fenêtre | Candidats visibles |
| --- | --- |
| 1920 × 1080 | **7,0** |
| 1280 × 800 | **4,0** |
| 1280 × 614 | **1,2** — une seule ligne, et une amorce |

**Et les indicateurs défilent avec le tableau.** Ils ne sont pas dans la section haute fixe de la
coque, mais dans la zone qui défile, mélangés à la liste. Conséquence : dès qu'on descend dans les
candidats, les chiffres partent par le haut. Ils occupent la place sans rester utiles — le pire des
deux mondes.

> **Ces hauteurs sont calculées depuis les jetons du socle, puis recoupées sur une capture réelle en
> 1920 × 1080.** La grille de chiffres y occupe 218 → 493 px ; le calcul annonçait 275 px. Le premier
> candidat y est centré à 596 px ; le calcul annonçait 596. Le modèle est vérifié, donc les
> projections d'après-changement sont du même ordre de fiabilité — **mais elles restent des
> projections, et la définition de terminé exige de les remesurer.**

### Trois défauts, et le troisième est le moins visible

**Les indicateurs sont des cartes.** Fond, coins arrondis, rembourrage : tout ce vocabulaire dit « je
suis un objet, tu peux me survoler, me cliquer, me sélectionner ». Aucun d'eux n'est cliquable. La
carte promet une interaction qu'elle ne tient pas, et fait payer sa mise en scène au tableau.

**Chaque carte empile trois lignes** — le libellé, le pourcentage, la fraction —, et c'est là que
part le plus gros de la hauteur : 64 px sur les 84 d'une tuile. Le rembourrage de la carte, lui, n'en
coûte que 20, et une seule fois pour la rangée entière.

**On lit dix nombres, puis on conclut.** L'information affichée est une **proportion**, et une
proportion se compare mal de tête. Rien, dans l'écran actuel, ne fait voir d'un coup d'œil que cinq
champs sont pleins et cinq quasi vides : il faut lire les dix chiffres et les ordonner soi-même.

## Périmètre

La zone principale de l'écran se découpe en **trois sections**, chacune avec un comportement
distinct — c'est le gabarit A/B/C/D que la coque expose déjà, et que cette page n'utilisait qu'à
moitié.

**A · la bande de titre — fixe.** Le titre et le compteur fusionnent sur une seule ligne, dans une
bande à la hauteur du palier `WEB-016` (43 px sur un 1920, 32 px sur un 1280). Le `<h1>` reste un
`<h1>` ; il change de corps, pas de nature.

**B · les dix mesures de remplissage — fixe.** Elles quittent la zone qui défile pour le slot
`#summary` de la coque, qui existe depuis toujours et n'avait jamais été pris. Les cartes
disparaissent. Chaque mesure devient : le libellé et le pourcentage sur une ligne, une barre pleine
dessous. Le détail chiffré passe en infobulle native.

**C · le tableau — la seule zone qui défile**, et il prend tout ce qui reste.

```
1920 × 1080 · zone principale 1618 × 986
┌─────────────────────────────────────────────────────────────────┬───────┐
│ Le vivier · 2 561 candidats                                     │  43px │ A
├─────────────────────────────────────────────────────────────────┤ gap20 │
│ TAUX DE REMPLISSAGE                                             │       │
│ Prénom  100%   Nom  100%   LinkedIn 99%   Employeur 96%         │  62px │ B
│ ██████████     ██████████  █████████▉     █████████▋            │       │
├─────────────────────────────────────────────────────────────────┤ gap20 │
│ Candidat         Localisation   Joignabilité   Statut           │       │
│ ● Ahmed Chinbou     Lille · FR    ✉ ☎ in      VISIBLE          │ 841px │ C
│ … 10 lignes entières, la 11ᵉ engagée                        ▓ ▲ │       │
└─────────────────────────────────────────────────────────────────┴───────┘
```

Résultat attendu, à remesurer :

| Fenêtre | Avant | Après |
| --- | --- | --- |
| 1920 × 1080 | 7,0 lignes | **~10,9** |
| 1280 × 800 | 4,0 lignes | **~7,6** |
| 1280 × 614 | 1,2 ligne | **~5,0** |

## Hors périmètre

- **La pagination.** Le tableau affiche 20 candidats sur 2 561, sans moyen d'aller plus loin. Le
  découpage rend le défaut plus visible — on atteint le bout de la liste plus vite qu'avant — mais ne
  le traite pas. Déjà relevé à l'audit de l'espace admin ; sa propre carte.
- **La hauteur de ligne du tableau**, 73 px, tenue par la photo ronde de 36 px et les deux lignes de
  texte. La descendre rendrait trois lignes de plus, et c'est un arbitrage de lisibilité distinct :
  deux changements de densité dans le même commit ne se jugent plus séparément. Écarté
  explicitement pour ce tour.
- **La fiche candidat** et tout le reste de l'espace admin.
- **Le comportement sous 1024 px**, qui reste celui de la coque aujourd'hui : tout défile en une
  seule colonne, bandes comprises. Le plein écran n'a pas de sens sur un téléphone, où l'on lit une
  liste au lieu de comparer onze lignes. Aucune règle nouvelle, aucune modification de la coque —
  donc aucun risque pour les autres écrans admin.
- **Les autres écrans de l'espace admin**, qui partagent la même coque : cette carte ne touche que sa
  page et son composant, jamais `DashboardBody.vue` lui-même — à l'exception de son commentaire, cf.
  *Documentation impactée*.

## Décisions de cadrage

### Réserver la hauteur plutôt que la subir

Deux modèles s'opposaient. Ou bien A et B prennent la hauteur de leur contenu et C hérite du reste —
la surface de travail est alors une **conséquence**, que personne ne choisit. Ou bien A et B ont une
hauteur **réservée** et C a une garantie.

Le second est retenu, et il achète trois choses. La surface utile devient un chiffre qu'on peut
écrire dans une spec et tenir. L'écran cesse de sauter au chargement — voir la règle sur l'état
vide, plus bas. Et surtout : les filtres, la recherche et la pagination qui arriveront sur cet écran
ont désormais un **budget**, au lieu de prendre une ligne de candidat chacun, en silence.

Le prix est mesuré : environ un quart de ligne. C'est le bon échange.

**Mais « réservée » ne veut pas dire « imposée ».** Une boîte à qui l'on fixe une hauteur ne
rapetisse pas son texte pour y entrer : elle le rogne. D'où `min-height` partout, jamais `height` —
au zoom du navigateur ou dans une traduction plus longue, la bande grandit et le tableau rétrécit.
C'est une dégradation visible, là où le rognage est silencieux.

### La hauteur se mesure sur la fenêtre, pas en pourcentage

Un pourcentage n'a ni plancher ni plafond. Sur une fenêtre coupée en deux, `4 %` donne 20 px et le
texte est tranché ; sur un écran de 2160 px, 86 px pour porter la même ligne. Or la bande a un
plancher physique — la hauteur d'une ligne de texte et d'un bouton — que le contenu impose et
qu'aucune proportion ne sait exprimer.

La proportion voulue existe bien : c'est le `4vh` au cœur du palier `WEB-016`. Le `clamp()` ne la
remplace pas, il l'encadre. Et elle est bien proportionnelle dans la plage utile : 43 px sur un corps
de 986 px et 32 px sur un corps de 725 px font 4,4 % dans les deux cas.

La zone C, elle, a mieux qu'un pourcentage : « tout ce qui reste ». Trois pourcentages qui doivent
totaliser cent moins les écarts se désaccordent au premier changement.

### Les cartes partent, mais ce n'est pas là qu'est le gain

Il faut être exact, parce que l'arithmétique change la conclusion. Les dix tuiles sont côte à côte :
leur rembourrage haut et bas n'est payé **qu'une fois**, pas dix. Retirer les cartes fait gagner
20 px sur 84 — réel, mais minoritaire. Les 64 autres sont les trois lignes de texte empilées.

On les retire quand même, et pour une raison qui n'est pas la place : une carte annonce un objet
manipulable, et il n'y en a aucun ici. La gouttière de l'espace entreprise n'a d'ailleurs aucune
surface — une bande de coque n'est pas un panneau.

**Le vrai gain vient de la restructuration** : libellé et pourcentage sur une ligne, barre dessous.
84 px deviennent 37. Et la barre fait ce que dix nombres ne font pas — elle rend la coupure entre les
champs pleins et les champs vides lisible sans qu'on lise.

Effet de bord notable : la règle envisagée d'alléger la bande sous 700 px de hauteur de fenêtre
devient **inutile**. Elle existait pour supprimer la troisième ligne ; il n'y en a plus. Un
comportement de moins à écrire, à tester et à oublier.

### Une seule couleur pour les dix barres

Des seuils colorés — vert au-dessus de 80 %, rouge en dessous de 30 — rendraient le problème plus
saillant. Ils sont écartés pour deux raisons. Ils décrètent des seuils métier que personne n'a
validés : 4 % de candidats avec e-mail est peut-être parfaitement normal pour une base alimentée par
import. Et la charte l'interdit en toutes lettres — *« aucune couleur pour coder une donnée »*.

Seule la longueur varie. C'est le signal le plus fiable, et le seul qui reste lisible pour quelqu'un
qui distingue mal les couleurs.

### ÉCART AU CADRAGE — trois points tranchés à l'implémentation

**La jauge vient de la bibliothèque, pas d'une barre écrite à la main.** Le cadrage laissait ouverte
la hauteur de barre, qu'aucun palier ne couvrait, et refusait de l'improviser. Le composant de
progression de la bibliothèque règle la question : c'est elle qui possède la dimension, et le README
des composants prescrit de partir d'elle. **Mais elle ne retourne pas son remplissage en écriture
arabe** — seulement son animation indéterminée. Le miroir est donc posé sur notre propre conteneur,
et non sur sa piste : celle-ci porte sa transformation **en ligne**, qu'aucune feuille de style ne
reprend sans `!important`.

**Le test de composant devient un test d'util.** Le cadrage annonçait un test monté sur la bande. Le
dépôt ne monte pas de composants — `vitest.config.ts` borne les tests au code pur sans runtime Nuxt,
et aucune bibliothèque de montage n'est installée. Le calcul de pourcentage est donc **extrait dans
`utils/fillRate.ts`** et couvert là, suivant le précédent `ADM-011` que l'espace applique déjà deux
fois. L'extraction a une seconde vertu, indépendante du test : le chiffre écrit et la longueur de la
jauge lisent désormais **la même** fonction — un arrondi calculé à deux endroits finit par diverger.

**Le commentaire de la coque n'avait rien à corriger.** Le cadrage annonçait qu'il affirmait à tort
que la page ne prenait pas la section haute. La reprise venue de l'organisation l'a réécrit entre
le cadrage et l'implémentation, et il décrit exactement le bon comportement.

### Le détail chiffré en infobulle native, pas en composant

La fraction (« 2 533 sur 2 561 ») quitte la tuile et passe au survol. L'attribut natif du navigateur
est retenu contre le composant de la bibliothèque : il ne peut pas sortir de l'écran, ne demande
aucun JavaScript, et est lu par les lecteurs d'écran. `WEB-015` vient précisément de corriger une
bulle qui débordait ; dix bulles alignées en bord de bande, dont deux collées au bord de la fenêtre,
sont exactement ce terrain-là. On ne le reprend pas pour un confort d'apparition.

Contrepartie assumée : l'apparition est retardée d'environ une seconde et son style n'est pas le
nôtre.

## Règles et cas limites

- **`min-height`, jamais `height`** — sur A comme sur B.
- **La bande B existe même quand les données ne sont pas là.** Aujourd'hui la grille est conditionnée
  à la présence des données, qui arrivent par le registre de démarrage et non par le rendu de la
  page : il existe donc un instant où elle n'occupe rien, et où le tableau démarre 60 px trop haut
  avant de redescendre. La bande doit occuper sa place **dès le premier affichage**, vide puis
  remplie — c'est le slot qui est toujours rendu, et son contenu qui est conditionnel, pas
  l'inverse.
- **La barre se remplit dans le sens de lecture** : depuis la droite en arabe, l'une des trois
  langues du produit. Une barre qui part toujours de la gauche se lit à l'envers en RTL.
- **La barre est décorative pour un lecteur d'écran.** Le pourcentage est écrit en toutes lettres à
  côté d'elle ; l'information ne dépend jamais de la longueur ni de la couleur seules. La barre
  elle-même est masquée à l'assistance.
- **Un total nul ne casse rien** : le calcul actuel renvoie déjà 0 quand le total est zéro, et la
  barre doit suivre — largeur nulle, pas de `NaN`, pas de barre pleine par accident.
- **Aucune nouvelle clé de traduction.** Le titre fusionné réutilise les deux clés existantes, et
  l'infobulle réutilise la clé de détail. Toute clé ajoutée doit l'être dans les **trois** locales,
  règle CI `app-no-hardcoded-text` — mais l'objectif est de n'en ajouter aucune.
- **Sous 1440 px de large, la rangée des dix mesures défile latéralement** plutôt que de repasser sur
  deux lignes : deux rangées annuleraient tout le gain. Le défilement horizontal reste **dans la
  bande**, jamais sur la page.
- **Aucun palier brut, aucune valeur arbitraire.** L'interdit n°3 de la charte vaut ici comme
  ailleurs : la bande consomme le palier de `WEB-016`, le reste consomme les jetons existants. La
  hauteur de barre, si elle n'est couverte par aucun palier, se discute — elle ne s'improvise pas.
- **La coque n'est pas modifiée.** Elle porte huit autres écrans admin ; le slot `#summary` est déjà
  là et se prend sans rien changer à son code.

## Documentation impactée

- **[DashboardBody.vue](../../webapp/spaces/admin/app/components/DashboardBody.vue)** — son
  commentaire d'en-tête décrit le gabarit A/B/C/D et présente la section haute comme
  « optionnelle ». Elle devient utilisée pour la première fois : le commentaire doit dire ce que la
  section porte réellement et pourquoi elle est fixe, sinon le prochain écran refera le même
  raisonnement à partir de zéro.
- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** `arborescence-admin.md` décrit les routes de l'espace et les
  accès par rôle, pas la mise en page des écrans : la route, son contenu et ses permissions sont
  inchangés.
- **Aucun `README.md` de dossier** : le rôle des composants de l'espace admin ne change pas.

## Définition de terminé

- [x] Les chiffres de remplissage **restent à l'écran** quand on descend dans la liste des candidats
      — le tableau a défilé de 667 px, le titre et la bande n'ont pas bougé d'un pixel.
- [x] Le nombre de candidats visibles est **remesuré** aux trois formats, avant et après, sur la
      même machine : **7 → 10**, **3 → 7**, **1 → 5**. Les projections du cadrage sont confirmées.
- [x] Le tableau est la **seule** zone qui défile au-dessus de 1024 px ; en dessous, la coque
      redescend tout en une colonne, sans modification de sa part.
- [x] La bande de remplissage occupe sa place avant l'arrivée des données : **59 px** de plancher
      au lieu de s'effondrer, et le tableau ne saute plus que de **4 px** au lieu de 64.
- [x] Les jauges se remplissent dans le bon sens dans les trois langues — **défaut trouvé et
      corrigé** : la bibliothèque partait toujours de la gauche, y compris en arabe.
- [x] Le pourcentage est lisible en toutes lettres à côté de chaque jauge, et la jauge est masquée
      aux lecteurs d'écran.
- [x] Le détail chiffré est accessible au survol, dans les trois langues.
- [x] Test unitaire sur le calcul extrait : 100 %, 0 %, arrondi, base vide sans `NaN` — 4 cas verts.
- [x] Aucun palier brut ni valeur arbitraire ; la bande consomme les paliers de `WEB-016`.
- [x] Captures dans les deux thèmes.
- [x] Le commentaire de la coque dit la vérité sur la section haute — il la disait déjà, cf.
      *Décisions de cadrage*.
- [x] Les gates de la lane sont passées à l'ouverture de sa PR, en une passe, **toutes vertes** :
      lint sans erreur, format, type-check sur les quatre espaces, 19 règles d'architecture sans
      violation, **331 tests**, contrat OpenAPI sans dérive, build SSR complet.

## Vérification

Relevés dans un navigateur réel (Chromium), connecté en administrateur sur la stack du worktree,
avec les **2 561 candidats** de la base. La base de référence a été mesurée sur **la même** stack en
remisant le diff, jamais déduite.

### Ce que l'écran montre, avant et après

Comptés : les candidats **entièrement visibles sans défiler** — ce qu'un opérateur voit en arrivant.

| Fenêtre | Avant | Après | |
| --- | --- | --- | --- |
| 1920 × 1080 | **7** (+1 amorcée) | **10** (+1 amorcée) | +43 % |
| 1280 × 800 | **3** (+1) | **7** (+1) | ×2,3 |
| 1280 × 614 | **1** (+1) | **5** (+1) | ×5 |

Les trois zones, à 1920 × 1080 : titre **43 px** (le palier de gouttière), remplissage **64 px**,
tableau **840 px**. Avant : 81 px, 308 px, et le remplissage à l'intérieur du défilement.

### La promesse centrale, prouvée par le geste

Le tableau a été défilé de **667 px**. Le haut du titre est resté à 91 px, le haut de la bande à
147 px — **inchangés au pixel**.

### Deux défauts que les mesures ne voyaient pas, trouvés à la capture

**La valeur se rattachait au mauvais libellé.** Mesuré : **46 px** entre un pourcentage et son
propre libellé, contre **10 px** de la mesure suivante. L'œil liait « 100 % » à « Nom » au lieu de
« Prénom », et le dernier pourcentage restait orphelin. Après correction : **10 px à l'intérieur,
20 px entre les mesures**.

**La jauge partait du mauvais bord en arabe.** Sur une mesure à 86 %, la zone remplie occupait
829→953 sur une piste 829→973 : elle commençait à gauche en écriture de droite à gauche, où elle se
lit alors comme 14 %. Après correction : **849→973**, elle part de la droite, même longueur remplie
(124 px) — c'est bien un miroir et non un décalage.

### Les autres contrôles

| | |
| --- | --- |
| Infobulle au survol | « 2561 sur 2561 » · « 2561 من 2561 » |
| Jauge masquée aux lecteurs d'écran | oui, le pourcentage est écrit à côté |
| Bande sans données | 59 px (plancher) · saut du tableau réduit à 4 px |
| Débordement de la rangée à 1920 | aucun — 1619 px de contenu pour 1619 px visibles |
| Conventions | **19 règles, aucune violation** |
| Tests | **331** (dont 4 neufs), 23 fichiers |

Une règle a repris un de mes commentaires à juste titre : il racontait l'historique au lieu de
décrire la contrainte. Réécrit au présent.

## Livré

- `webapp/spaces/admin/app/pages/candidats.vue` (M) — le titre et le compteur fusionnent sur une
  ligne dans une bande au palier de gouttière ; le remplissage passe au slot `#summary`, **toujours
  rendu** et au contenu conditionnel — l'inverse ferait disparaître la bande pendant le chargement ;
  le tableau reste seul dans la zone défilante.
- `webapp/spaces/admin/app/components/FillRateSection.vue` (M) — les cartes cèdent la place à dix
  mesures : libellé et pourcentage liés sur une ligne, jauge de la bibliothèque dessous, détail
  chiffré en infobulle native. Deux règles locales : le plancher de bande, et le miroir de la jauge
  en écriture de droite à gauche.
- `webapp/spaces/admin/app/utils/fillRate.ts` (+) — le calcul de proportion, extrait pour être
  couvert **et** pour que le chiffre écrit et la longueur de la jauge lisent la même fonction.
- `webapp/spaces/admin/app/utils/fillRate.vitest.ts` (+) — quatre cas.

## Reste à faire (hors périmètre)

- **La pagination** : le tableau affiche 20 candidats sur 2 561, sans moyen d'aller plus loin. Ce
  découpage rend le défaut plus visible — on atteint le bout plus vite — sans le traiter.
- **La hauteur de ligne du tableau**, 73 px, tenue par la photo ronde et les deux lignes de texte.
  Écartée explicitement au cadrage ; elle rendrait trois lignes de plus.
- **Le saut résiduel de 4 px** au premier affichage : le plancher de bande vaut 59 px, le contenu
  64. Fermer l'écart demanderait un palier taillé pour cette bande, donc une décision de socle.
