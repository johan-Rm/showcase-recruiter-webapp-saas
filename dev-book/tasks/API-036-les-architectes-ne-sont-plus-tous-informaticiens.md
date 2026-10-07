# TASK API-036 — les architectes ne sont plus tous informaticiens

- **Créée le** : 2026-08-10.
- **Session** : `9d0eb3f2-2064-4ffd-94a9-dbc606ce1450`.
- **Statut** : ✅ fait le 2026-08-12 — les 37 profils d'un seul concept se répartissent en quatre,
  et le concept informatique ne porte plus que des intitulés informatiques.
- **Type** : fix — correction de contenu du dictionnaire. Aucun code applicatif nouveau, aucune
  migration, aucun contrat d'API touché.
- **Tests** : fonctionnel sur la reconnaissance d'intitulé — un intitulé d'architecte d'intérieur ne
  se rattache plus au concept informatique, un intitulé d'architecte logiciel s'y rattache toujours,
  et un « architecte » nu tombe sur le concept générique.
- **Réf** : amont `API-035` (l'import sait détacher, la reprojection rejoue le dictionnaire) —
  **bloquante**, task `118` (reconnaissance partielle des intitulés), `API-023` (auto-complétion) ·
  aval `API-037` (le filet, pour tout ce que le dictionnaire ne connaît pas) ·
  `api/data/vocabulary/taxonomy.json`.

## Contexte

Un recruteur tape « archi » dans le champ « Je cherche un profil de » de l'espace Entreprise. La
liste ne lui propose qu'une entrée : **« Architecte (IT/logiciel) »**. Il la choisit, et reçoit
37 profils — parmi lesquels trois architectes d'intérieur, un architecte paysagiste et un architecte
du bâtiment conseiller PEB. Rien ne le lui signale.

Le mécanisme est connu et fait ce qu'on lui demande. `TitleConceptResolver` (task `118`) cherche les
libellés du dictionnaire **comme mots entiers** dans l'intitulé, et retient le plus spécifique. Le
concept informatique portait la variante nue **« architecte »** ; « Architecte d'intérieur » la
contient, et aucun libellé plus long ne venait la battre. Le rattachement était donc mécaniquement
correct et sémantiquement faux.

Mesuré sur les 3 053 profils du vivier de développement :

| | profils |
|---|---|
| portent un intitulé contenant « architec » | 68 |
| rattachés à « Architecte (IT/logiciel) » | 37 |
| dont architectes d'intérieur, paysagiste, bâtiment | 5 |
| architecte d'intérieur rattaché à **aucun** métier | 1 |

Le dernier cas est le plus parlant : le profil 184 n'a jamais porté que « Interior Architect » et
« Interior Architecture Intern ». Aucun de ces deux intitulés ne contient « architecte », le
dictionnaire ne les connaissait pas, et ce profil n'était rattaché à **rien**. Il était introuvable
par le critère métier, quoi qu'on tape.

## Périmètre

- **`poste-024` reste le concept informatique** et prend les libellés qui le disent vraiment :
  `architecte logiciel`, `software architect`, `solution architect`, `solutions architect`,
  `architecte solutions`, `architecte de solutions`, `enterprise architect`, `system architect`,
  `technical architect`, `architecte technique`, `cloud architect`, `data architect`,
  `application architect`, `architecte cybersécurité`, `architecte réseaux`, `lead architect`,
  `chief architect`, `architecte des systèmes d'information`, `middleware architect`,
  `business architect`, `architecte iam`. Il **perd** la variante nue `architecte`.
- **Trois concepts nouveaux** : `Architecte` (générique — `architecte`, `architect`),
  `Architecte d'intérieur` (`architecte d'intérieur`, `architecte d'interieur`,
  `interior architect`, `interior architecture`), `Architecte paysagiste` (`architecte paysagiste`,
  `landscape architect`).
- **Les termes ainsi rangés quittent le sac `vocabulaire_non_classe`** des postes : un terme ne peut
  pas être à la fois classé et non classé.
- **Les fréquences sont mesurées, pas estimées** — nombre de profils distincts dont l'intitulé
  canonique **égale** le terme, ce que produit `app:vocabulary:harvest`. Un terme à zéro est légitime :
  il désigne le concept sans que personne ne porte exactement ce libellé.
- **Import puis reprojection** : la correction n'est acquise qu'une fois les profils déplacés.

## Hors périmètre

- **Un concept « Architecte (bâtiment) ».** Aucun intitulé du vivier ne dit le bâtiment sans
  ambiguïté : « architecte conseiller », « architecte chef de projet », « project architect »,
  « junior architect » peuvent être l'un ou l'autre. Les créer obligerait à trancher à la place de la
  donnée — exactement le défaut qu'on corrige. Ces intitulés tombent sur le générique.
- **Les autres familles de métiers mal regroupées.** « Architecte » est le cas traité parce qu'il a
  été signalé ; le sac non classé en compte 6 469 autres termes. Ranger le dictionnaire n'est pas
  l'objet de cette carte.
- **Toute modification de code.** L'outillage vient d'`API-035` ; ici, seul le fichier de données
  bouge.
- **Le front.** Aucune clé i18n, aucun composant : les libellés proposés viennent de l'API.

## Décisions de cadrage

- **Le concept informatique garde son identifiant `poste-024`.** Créer un concept neuf pour
  l'informatique et renommer l'ancien en générique aurait produit une collision à l'import : le
  libellé canonique « Architecte (IT/logiciel) » serait cherché en base **avant** que le renommage ne
  soit écrit, et le nouveau concept retomberait sur l'ancien. Garder l'identité historique du concept
  informatique évite le problème par construction, et respecte ce que la task `113` protège — un
  concept s'identifie par son code, pas par son libellé.
- **Un concept générique « Architecte » plutôt que le statu quo.** Le mot nu ne dit pas
  l'informatique ; il dit même plus souvent le bâtiment. Servir dix profils comme architectes
  logiciels sans preuve est un faux positif silencieux, et c'est le pire des deux défauts : le
  recruteur ne peut pas le voir. Avec le concept générique, les deux entrées apparaissent côte à côte
  dans la liste et c'est lui qui choisit.
- **Faux positif résiduel assumé : « Thermal System Architect ».** Le terme `system architect`
  l'attrape, et ce profil est un architecte système **thermique**, pas informatique. Un profil contre
  le rattachement correct de deux architectes système informatiques : le solde est favorable, et le
  retrait de `system architect` renverrait « SYSTEM ARCHITECT » et « System Architect & AI Engineer »
  au générique alors qu'ils sont bien informatiques. Écrit ici pour ne pas être redécouvert comme un
  bug.
- **`interior architecture` est un libellé à part**, en plus d'`interior architect` : la
  reconnaissance exige un mot entier, et « Interior Architecture Intern » ne contient pas
  « interior architect » — le `e` final le brise. Sans ce second libellé, un profil réel du vivier
  reste dehors.
- **`architecte d'interieur` sans accent est conservé** : la normalisation abaisse la casse et
  redresse les apostrophes typographiques, elle ne retire pas les accents. Deux profils du vivier
  écrivent leur intitulé sans accent.
- **Les termes à fréquence nulle sont gardés** (`landscape architect`, `interior architecture`,
  `middleware architect`…). Un dictionnaire décrit un sens, il n'inventorie pas un stock ; et un
  terme rare est plus discriminant qu'un terme courant dans l'arbitrage du résolveur.

## Règles et cas limites

- **« architecte d'intérieur-chargé de projet »** : le trait d'union sans espaces ne coupe pas
  l'intitulé, mais la reconnaissance par mot entier accepte un `-` comme frontière. Rattaché à
  l'intérieur.
- **« stagiaire architecte d'intérieur »** : le métier bat le statut (règle de la task `118`),
  « Stage / Internship » ne rafle pas l'intitulé.
- **Profil portant plusieurs intitulés d'architecte** — le paysagiste du vivier porte aussi
  « Architecte » nu : il se retrouve sur **deux** concepts, générique et paysagiste. C'est le
  comportement attendu, un candidat exerce plusieurs métiers.
- **Un profil déjà rattaché ne perd rien en silence** : la reprojection annonce les rattachements
  posés et retirés.
- **La correction ne vaut que pour le vivier déjà chargé.** Les profils ingérés après coup passent
  par la même règle, donc arrivent au bon endroit sans intervention.

## Documentation impactée

- Aucune page d'architecture ne décrit le contenu du dictionnaire — il vit dans le fichier de
  données, et `dev-book/resources/taxonomie/README.md` en décrit le **cycle**, pas les entrées. Rien
  à mettre à jour ici ; c'est `API-035` qui touche cette page.

## Vérification

**Ce que le recruteur voit** — `GET /v1/suggestions?type=JOB&q=archi`, authentifié en
`entreprise@showcase-recruiter-webapp-saas.example` (`ROLE_COMPANY_OWNER`) :

```
Architecte (IT/logiciel)   CONCEPT
Architecte                 CONCEPT
Architecte d'intérieur     CONCEPT
Architecte paysagiste      CONCEPT
```

Une seule entrée avant la tâche, quatre après. C'est le défaut d'origine, résolu à l'endroit où il
se voyait.

**Ce que chaque entrée ramène** — `GET /v1/search/candidates?job=…`, même session :

| Terme | Profils |
| --- | ---: |
| `Architecte (IT/logiciel)` | 30 |
| `Architecte` | 27 |
| `Architecte d'intérieur` | 3 |
| `Architecte paysagiste` | 1 |
| `software engineer` | 294 |
| `développeur logiciel` | 294 |

Les deux dernières lignes prouvent que **l'élargissement n'a pas été perdu** : un synonyme anglais
retrouve toujours son concept, au même compte qu'avant.

**Chaque profil au bon endroit**, relevé en base sur les profils dont un intitulé écrit mentionne
l'intérieur, le paysage ou la performance énergétique du bâtiment :

| Profil | Avant | Après |
| --- | --- | --- |
| 98 — architecte gérant conseiller PEB | Architecte (IT/logiciel) | **Architecte** |
| 103 — architecte paysagiste | Architecte (IT/logiciel) | **Architecte + Architecte paysagiste** |
| 184 — interior architect | **aucun concept** | **Architecte d'intérieur** |
| 2189, 2753 — architectes d'intérieur | Architecte (IT/logiciel) | **Architecte d'intérieur** |

Quatre autres profils remontent par le même filtre sans être architectes — « aménagement d'intérieur »,
« habillage intérieur », « interior automotive components » : ils ne portent aucun concept
d'architecture, ce qui est correct.

**Le concept informatique ne porte plus que de l'informatique.** Les 51 intitulés écrits par les
profils qu'il rattache ont été listés un à un : logiciel, données, cloud, réseau, entreprise, IAM,
middleware. Aucun architecte d'intérieur, aucun paysagiste, aucun bâtiment. **Une exception, celle
qui était prévue** : « thermal system architect », attrapé par `system architect`.

**Chargement et reprojection** :

```
app:vocabulary:import     3 concepts créés, 860 mis à jour ; 9 libellés créés,
                          3355 mis à jour, 3 retirés, 20 rendus
app:vocabulary:reproject  3053 profils parcourus, 60 lignes de projection posées ou retirées,
                          15 postes actuels rerattachés
```

Les **20 « rendus »** ne sont pas une surprise : ce sont les libellés informatiques posés puis retirés
lors du prototypage de cette tâche, restés en suppression douce et revenus sur leurs lignes d'origine.
C'est le mécanisme d'`API-035` faisant exactement ce pour quoi il a été écrit.

**Tests** — `php bin/phpunit --filter ArchitectTaxonomyTest --testdox` :

```
✔ un architecte d'intérieur n'est pas informaticien
✔ même sans accent, tel qu'il est écrit dans deux profils
✔ un stagiaire garde le métier, pas le statut
✔ l'anglais rejoint le même concept
✔ le paysagiste a le sien
✔ le mot nu ne dit pas l'informatique
✔ un architecte logiciel reste informaticien
✔ et l'anglais aussi
✔ le concept informatique ne revendique plus le mot nu

OK (9 tests, 36 assertions)
```

**Le test échoue bien quand il doit échouer** : en remettant `architecte` sur le concept informatique
dans le fichier, la suite passe à `Tests: 9, Failures: 1`. Le fichier a été restauré à l'identique
juste après, vérifié au `git diff --stat`.

Voisins rejoués (`TitleConceptResolver`, `JobExperienceCalculator`, `CurrentPositionExtractor`) :
`OK (27 tests, 79 assertions)`.

## Livré

- `api/data/vocabulary/taxonomy.json` (M) — `poste-024` perd la variante nue `architecte` et prend
  21 libellés informatiques ; trois concepts naissent (`poste-159` Architecte, `poste-160` Architecte
  d'intérieur, `poste-161` Architecte paysagiste) ; 11 termes quittent le sac non classé.
- `api/tests/Unit/ArchitectTaxonomyTest.php` (+) — neuf cas qui éprouvent le **fichier livré**, pas
  seulement la règle : c'est la donnée qui portait le défaut.

## Reste à faire (hors périmètre)

- **Le faux positif « Thermal System Architect »** reste : un architecte système thermique compté
  comme informaticien. Assumé au cadrage, non cardé.
- **Le sac non classé compte encore 6 469 termes.** « Architecte » a été rangé parce qu'il a été
  signalé ; les autres familles mal regroupées attendent d'être vues. `API-037` fait qu'elles ne sont
  plus introuvables pour autant.
- **La reprojection est à rejouer sur chaque environnement** où le dictionnaire est chargé :
  l'import seul ne déplace personne. En développement, `make dev-vocabulary-refresh` l'enchaîne.

## Définition de terminé

- [x] La liste déroulante propose « Architecte », « Architecte (IT/logiciel) », « Architecte
      d'intérieur » et « Architecte paysagiste » quand on tape « archi ».
- [x] Les trois architectes d'intérieur du vivier sont rattachés au concept d'intérieur, dont le
      profil qui n'était rattaché à rien.
- [x] Le paysagiste est rattaché au concept paysagiste.
- [x] Le concept informatique ne contient plus que des intitulés informatiques — vérifié en listant
      les intitulés bruts de tous les profils qu'il porte, à l'exception documentée du « Thermal
      System Architect ».
- [x] L'architecte du bâtiment conseiller PEB n'est plus servi comme architecte logiciel.
- [x] Test fonctionnel sur la reconnaissance d'intitulé.
