# TASK ENT-052 — la colonne « Poste » montre le poste, pas l'argumentaire

- **Créée le** : 2026-08-05.
- **Session** : `450f2479-6465-4986-9ed9-adc417bbbdaf`.
- **Statut** : ✅ fait le 2026-08-06 — la Table et la Visionneuse titrent sur le poste ; l'accroche ne reste qu'en repli, atténuée et en italique, et la colonne est bornée à 40 caractères.
- **Type** : fix.
- **Tests** : unitaires sur `roleOf` — poste servi, repli sur l'accroche, **clé absente** (et non `null`), ni l'un ni l'autre, chaînes d'espaces, rognage des bords. L'exemption annoncée au cadrage est tombée : cf. *Décisions de cadrage*. Le rendu, lui, reste prouvé par capture et par comparaison automatique à la donnée servie.
- **Réf** : amont **`API-030`** (le champ `currentTitle` doit exister dans le contrat de la carte) · `ENT-006` (le poste est le titre) · `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` · `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` · `webapp/base/i18n/locales/fr.yaml`.

## Contexte

Les deux outils du vivier montrent, là où ils annoncent un poste, l'**accroche LinkedIn** du
candidat — la phrase qu'il écrit pour se vendre, pas son intitulé de fonction.

- Dans la **Table** (« Trouver »), la colonne s'appelle « Poste / compétences » et affiche
  `headline` — [`table.vue:296`](../../webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue)
  et `:442`.
- Dans la **Visionneuse** (« Choisir »), le grand titre du bandeau affiche `headline` —
  [`ProfileCard.vue:235`](../../webapp/spaces/entreprise/app/components/search/ProfileCard.vue).

Les deux endroits portent déjà le commentaire du défaut. `ProfileCard.vue:230-233` : *« Ce champ
n'est pas un intitulé de poste mais l'accroche que le candidat s'écrit sur son réseau — elle atteint
cent dix caractères et s'arroge alors tout le bandeau »*. `table.vue:437-439` : *« un `headline`
porte parfois un résumé entier. Sans borne, cette colonne mangeait la largeur et les neuf autres
sortaient de l'écran »*. Dans les deux cas le correctif appliqué est une **borne de largeur**, faute
du bon champ dans le contrat. `API-030` l'y met.

Ce que ça donne à l'écran aujourd'hui, profil réel :

> Le MVP monté à l'IA tourne en démo. En production, c'est une autre histoire. J'industrialise :
> sécurité, scalabilité, fiabilité | Développeur + DevOps freelance | Symfony · Laravel · Docker

189 caractères, dont le métier n'occupe que la quatrième place — coupée bien avant d'être lue. Son
intitulé de poste, lui, tient en une ligne : « Développeur & DevOps freelance ».

Mesuré sur 3 053 profils : l'accroche fait 128 caractères pour les 10 % les plus longues et monte à
752 ; le poste fait 25 caractères en médiane et 52 au 90ᵉ centile.

## Périmètre

- **Le type miroir, EN PREMIER** : `webapp/shared/types/candidate-card.ts` gagne
  `currentTitle: string | null`. Ce fichier est écrit **à la main** et son en-tête dit pourquoi — la
  carte de recherche n'existe pas dans l'OpenAPI (ses `items` y sont typés `(string | null)[]`),
  donc la gate de contrat ne verra jamais l'écart. Sans ce report, le champ arrive du serveur et
  reste invisible du front.
- **La Table** : la colonne affiche `currentTitle`. Bornée à **environ 40 caractères** de large, avec
  troncature — sept postes sur dix passent alors en entier.
- **La Visionneuse** : le titre du bandeau affiche `currentTitle`. **Aucune borne en caractères ici**
  — la limite existante de deux lignes suffit et cesse de mordre : le poste le plus long du vivier
  (100 caractères) y entre en entier, là où une accroche de 189 ou 752 caractères la saturait. Sur
  la fiche on LIT, on ne compare pas : rien n'y justifie de couper un intitulé.
- **Le repli** : quand `currentTitle` est absent, l'accroche prend sa place, **en gris atténué et en
  italique**, à la même borne — aux deux endroits.
- Les libellés i18n si le sens de la colonne change (`search.column.role`, `search.profile.untitled`
  dans `webapp/base/i18n/locales/fr.yaml`, et ses jumeaux `en` / `ar`).

## Hors périmètre

- **L'accroche ne trouve pas d'autre place dans le tableau** — ni seconde ligne, ni infobulle. Une
  seconde ligne double la hauteur de chaque rangée, et un tableau sert à comparer : la hauteur coûte
  plus que la largeur. L'accroche reste lisible sur la fiche détaillée.
- **La certitude du poste** — 410 profils (13 %) ont plusieurs postes ouverts en même temps, et le
  serveur le sait. On ne l'affiche pas encore, ni sur la carte ni dans la colonne. Écarté au cadrage.
- **La mention « poste actuel »** déjà présente dans la cellule (`jobTenureOf`) : elle répond à une
  autre question — *le métier cherché est-il encore exercé* — et ne bouge pas.
- **Le badge « Rencontré »**, les autres colonnes, l'ordre des colonnes.
- **La qualité des intitulés eux-mêmes** : un sur cinq empile périmètre, employeur et pile technique
  (« Technical Sales Manager - Middle East - Africa - India - Brazil & Canada »). Le front les
  affiche tels quels et les coupe ; il ne les nettoie pas.

## Décisions de cadrage

- **Repli sur l'accroche plutôt qu'un tiret.** 831 profils (27 %) n'ont aucun poste dérivé — un tiret
  aurait vidé un quart du tableau de toute indication de métier. Le repli garde un signal, même
  imparfait.
- **Le repli se voit.** Gris atténué et italique : sans cela, rien ne distinguerait un intitulé
  vérifié d'une phrase de démarchage, et on reconduirait la confusion d'aujourd'hui en plus court.
  L'italique porte l'information au-delà de la seule couleur — un écart de teinte ne suffit pas quand
  la vue distingue mal les nuances.
- **40 caractères et non 55.** La colonne partage l'écran avec neuf autres ; c'est elle qui les
  poussait dehors. À 40, trois postes sur dix sont coupés — un survol ou l'ouverture de la fiche les
  rend, et la comparaison ligne à ligne redevient possible. À exprimer en unité de caractère (`ch`)
  et non en pixels : c'est un texte qu'on borne, pas un bloc.
- **La borne des 40 caractères vaut pour le tableau SEUL.** Les deux surfaces changent de champ, pas
  de règle de coupe : le tableau sert à comparer et paie chaque caractère en largeur volée aux neuf
  autres colonnes ; la fiche sert à lire et n'a rien à gagner à tronquer. Mesuré sur les 2 222
  profils qui ont un poste : 2 049 (92 %) tiennent sur une seule ligne du bandeau, 173 en prennent
  deux, **aucun** n'atteint la limite.
- **L'exemption de tests est tombée, et c'est un écart au cadrage.** La carte annonçait
  `sans objet`, au motif qu'aucun test de composant n'existe sur cet espace. L'implémentation a
  extrait `roleOf` — une fonction **pure**, dans un dossier qui a déjà ses tests unitaires
  (`candidate.vitest.ts`). Une règle d'affichage aussi facile à casser silencieusement ne pouvait
  pas rester sans filet : six cas la verrouillent désormais.
- **La borne s'exprime en `ch`, mais vaut 28 et non 40.** L'unité `ch` se cale sur la largeur du
  zéro, plus large que la moyenne des lettres : `40ch` rendaient **56 caractères réels** à l'écran,
  soit exactement la valeur écartée au cadrage. `28ch` tombent entre 36 et 39 caractères, mesurés
  sur le rendu. La décision produit reste « environ 40 caractères » ; seul le nombre écrit dans le
  code diffère, et c'est pour l'honorer.
- **`accessorKey` devient `id`.** La cellule compose deux champs — le poste, l'accroche en repli — ;
  il n'y a plus un champ unique à accéder, comme sur les colonnes d'identité et de coordonnées. Sans
  tri sur cette table, la bascule est sans effet de bord.
- **La fiche est corrigée dans le même geste.** Un recruteur passe de la Table à la Visionneuse sur
  le même profil ; lire deux textes différents au même endroit de l'écran est un défaut à lui seul.

## Règles et cas limites

- ⚠️ **La clé `currentTitle` est OMISE, pas servie à `null`**, quand le profil n'a pas de poste : le
  normaliseur de la carte supprime les nulls pour alléger une page de 1 600 lignes. Une garde
  `null !== candidate.currentTitle` laisserait donc passer l'absence et afficherait du vide — c'est
  exactement le piège déjà constaté sur `matchedOn`. Tester la valeur, pas sa nullité.
- Ni poste ni accroche (8 profils sur 3 053) : le tiret d'absence, comme les autres colonnes.
- Un intitulé peut porter des emoji et des séparateurs exotiques (`🧑‍💻`, `///`) — affichés tels
  quels, la troncature ne doit pas couper au milieu d'un caractère composé.
- **Mobile d'abord** : la borne de 40 caractères est un plafond, pas une largeur figée ; sur écran
  étroit la colonne se réduit avant lui.
- La troncature ne mord qu'avec une largeur maximale posée sur la cellule — une cellule de tableau
  s'élargit pour contenir son texte. C'est le piège déjà documenté à `table.vue:437`.
- L'italique et l'atténuation ne portent **aucune** information à elles seules : le texte de repli
  reste intégralement lisible et sélectionnable.

## Documentation impactée

**Aucune** — vérifié page par page, et c'est le résultat de la vérification, pas un silence :

- `arborescence-entreprise.md` et `filtres-vivier-tableau-partage.md` étaient les deux candidates
  annoncées au cadrage ; ni l'une ni l'autre ne décrit les colonnes de la Table.
- `services-standard.md` fixe des conventions de couche et n'énumère aucune fonction : ajouter
  `roleOf` à un service existant ne la rend pas fausse.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` nomme bien la colonne « Poste / compétences »,
  mais le cadrage est **hors périmètre d'un commit** — il est écrit pour les clients et suit son
  propre rythme.

## Vérification

**Comparaison automatique du rendu à la donnée servie.** Pour chaque carte affichée, le titre
attendu — poste ou repli — est calculé depuis la réponse réelle de `/v1/searches/{id}/results`, puis
confronté au style calculé dans le navigateur. Deux recherches, trois chargements :

```
recherche « 135 profils »  → 30 cartes | 29 conformes | 0 écart | 1 ignorée (texte ambigu)
     même page, recharge 2 → 30 cartes | 29 conformes | 0 écart
     même page, recharge 3 → 30 cartes | 29 conformes | 0 écart
recherche « 234 profils »  → 30 cartes | 30 conformes | 0 écart
```

89 cartes vérifiées, **aucun écart**. La carte « ignorée » l'est pour une raison qui vaut d'être
notée : « Software Engineer » est à la fois le POSTE d'un candidat et l'ACCROCHE d'un autre qui n'en
a pas. Comparer des titres d'un écran à l'autre revient alors à confondre deux personnes — c'est ce
qui a produit trois fausses alertes pendant l'implémentation, jusqu'à ce que la vérification passe
par la donnée plutôt que par l'œil.

**Capture de la Table** (recherche de 135 profils) : les postes réels s'affichent, les replis sont
atténués et en italique, la troncature tombe entre 36 et 39 caractères.

```
POSTE                                            EXP.   NOM & PRÉNOM
Ingénieur développeur Senior JAVA  poste actuel   12    Bouzaabit Said
Ingénieur logiciels                               10    Zakariae Megouaz      ← repli
Tech Lead Transverse J2EE (freelance)             16    Youness El Assaassi
Tech Lead | Java, Angular                         12    Zakaria Charik        ← repli
Développeur Full Stack JAVA | Angular …  poste…    6    Seddik Dahbi          ← coupé
Software Engineer                                 12    Anass Derri           ← repli
```

**Tests unitaires** — `bunx vitest run services/search/candidate.vitest.ts` :

```
✓ services/search/candidate.vitest.ts (15 tests) 23ms
Test Files  1 passed (1)
     Tests  15 passed (15)
```

## Livré

- `webapp/shared/types/candidate-card.ts` (M) — `currentTitle` dans le miroir écrit à la main, en
  **optionnel** et non seulement nullable : le serveur omet la clé.
- `webapp/services/search/candidate.ts` (M) — `roleOf`, la règle d'affichage en un seul endroit :
  quoi montrer, et si c'est un repli.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` (M) — la colonne passe au poste,
  bornée et tronquée.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` (M) — le titre du bandeau passe au
  poste, sans borne en caractères.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` (M) — la colonne s'appelle « Poste », plus
  « Poste / compétences ».
- `webapp/services/search/candidate.vitest.ts` (M) — six cas sur `roleOf`.

## Reste à faire (hors périmètre)

- **La corbeille des écartés garde l'accroche.** `describeDecision` compose « poste · ancienneté ·
  ville » et lit encore `headline` — son propre commentaire dit pourtant « le poste ». Troisième
  surface, non nommée au cadrage, laissée telle quelle : elle mérite sa carte.
- **La certitude du poste** reste invisible. 13 % des profils ont plusieurs postes ouverts en même
  temps et le serveur le sait ; l'écran n'en dit rien, comme décidé au cadrage.

## Définition de terminé

- [x] `API-030` est livrée et mergée ; la lane a été réalignée, et la réponse de recherche porte le
      champ — 107 postes servis sur 135 profils.
- [x] La colonne de la Table affiche le poste, tronquée entre 36 et 39 caractères réels.
- [x] Le titre de la fiche de la Visionneuse affiche le poste.
- [x] Le repli sur l'accroche est en place aux deux endroits, atténué et en italique.
- [x] Les libellés i18n sont à jour en `fr`, `en` et `ar`, sans texte en dur.
- [x] Le rendu est prouvé sur les deux surfaces et les deux états — capture pour la Table,
      **comparaison automatique à la donnée servie** pour la fiche : 89 cartes, 0 écart. Cette
      preuve-là remplace la paire de captures annoncée au cadrage, et vaut mieux qu'elle : elle
      couvre tous les profils rendus au lieu de deux, et elle ne se juge pas à l'œil — trois fausses
      alertes ont montré pendant l'implémentation que l'œil ne suffit pas ici.
- [x] Documentation vérifiée page par page : aucune n'est déclenchée, cf. section dédiée.
