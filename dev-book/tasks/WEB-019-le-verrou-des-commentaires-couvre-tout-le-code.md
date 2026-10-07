# TASK WEB-019 — socle : le verrou des commentaires couvre tout le code

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — le verrou inspecte tout le code du produit, et les 96
  violations qu'il fait alors apparaître sont réparées dans le même commit.
- **Type** : outillage de qualité, plus la mise en conformité qu'il exige.
- **⚠️ Cadrée après coup.** Née d'une relecture des commentaires de la Visionneuse : la règle tenait
  les gabarits et laissait les fichiers TypeScript libres.
- **Tests** : `sans objet` pour l'extension elle-même — elle ne change **aucune ligne de code du
  vérificateur**, seulement la liste de chemins qu'il inspecte, et les tests de ce vérificateur
  existent déjà (`scripts/ci/checkers.vitest.ts`). La preuve est la **sortie du vérificateur** avant
  et après la mise en conformité.
- **Réf** : `WEB-018` — qui a posé le verrou sur les gabarits · la règle « Ce qu'un commentaire ne
  raconte pas » (`base/app/README.md`).

## Contexte

`WEB-018` a posé un verrou qui refuse trois familles dans les commentaires : le numéro de tâche, le
vocabulaire de chronologie, le marqueur de dette. Sa portée s'arrête aux `.vue` d'`app/**`.

**Les deux tiers du code n'y sont pas.** Les composables, les stores, le serveur intermédiaire, la
couche métier pure et les contrats de données sont tous en `.ts` — donc hors d'atteinte. Mesuré au
moment d'étendre la portée : **96 violations dans 56 fichiers**, quand les gabarits, eux, en
comptaient **zéro**. Le verrou tenait exactement ce qu'il regardait, et rien d'autre.

Un renvoi de tâche dans un `.ts` coûte la même chose que dans un gabarit : un aller-retour vers
`dev-book/` pour retrouver, au mieux, la phrase qu'on vient de lire. Et une chronologie y vieillit
pareil — `services/search/card.ts` expliquait encore ce que le zéro « était auparavant ».

## Périmètre

- **La portée du verrou s'étend** aux `.ts` d'`app/**`, au serveur intermédiaire, à `services/` et à
  `shared/`.
- **Les 96 violations sont réparées**, dans le même commit.
- **Les trois README concernés** disent la portée réelle.

## Hors périmètre

- **Le vérificateur lui-même**, qui n'a besoin d'aucune modification (voir ci-dessous).
- **`scripts/`**, qui n'est pas du code de produit — le vérificateur cite les tâches qu'il applique,
  et se condamnerait lui-même.
- **Les deux fichiers générés** de `shared/types/` : personne ne les écrit à la main.
- **Le volume et le ton des commentaires**, sujet distinct et non outillable.

## Décisions de cadrage

### Le verrou n'avait pas besoin d'être touché

C'est ce qui rend cette carte petite. Son lecteur de commentaires — `commentLinesOf` — travaille sur
le **texte brut** et suit l'ouverture et la fermeture des blocs `//`, `/* */` et `<!-- -->`. Il n'a
jamais lu d'arbre de gabarit, contrairement aux sept autres vérificateurs. Un `.ts` lui convient
donc tel quel, et le moteur de règles filtre les fichiers par simple motif de chemin.

**L'extension est une liste de chemins dans `rules.yaml`, rien d'autre.**

### La mise en conformité entre dans le MÊME commit

Les deux ne se séparent pas : un verrou posé seul rend `develop` rouge, une conformité posée seule se
défait à la tâche suivante. C'est aussi ce qu'a fait `WEB-018`, à l'ordre près.

**Conséquence assumée** : ce commit touche des fichiers de trois espaces (`entreprise`, `admin`) et
de la couche partagée. Le découpage par lane cède devant l'atomicité — il n'existe pas d'ordre dans
lequel scinder ce travail sans passer par un état rouge.

### Le récit se réécrit, il ne se coupe pas

Quatre-vingt-un renvois étaient de purs renvois entre parenthèses — « (task ENT-050) », « (tâche
099) » — et se suppriment sans rien perdre. Les **quinze autres** étaient tissés dans la phrase, et
portaient chacun une contrainte toujours vraie sous un habillage de récit. Ils ont été réécrits au
présent, jamais coupés :

```
❌  Le zéro était auparavant remplacé par `null`, et le rail n'affichait alors rien.

✅  ZÉRO NE SE REMPLACE PAS PAR `null` : le rail n'afficherait alors rien, et rien du tout
    se lit « la carte n'a pas fini de charger ».
```

### Un faux positif, corrigé dans le texte et non dans la règle

`acquisition-referrer.client.ts` disait « que Nitro ne relaie pas **jusqu'ici** » — un « ici »
d'espace, pas de temps, que le motif de chronologie attrape quand même. La phrase devient « jusqu'au
client » : elle y gagne en précision, et la règle reste sans exception. Une liste d'exceptions coûte
plus cher qu'une phrase reformulée.

### Le nom du verrou garde son préfixe

`app-no-comment-journal` ne couvre plus seulement `app/`. Le renommer toucherait `rules.yaml`, le
registre des vérificateurs, leurs tests et trois README, pour un gain de justesse nominale. Le README
le dit explicitement plutôt que de faire porter cette dette au nom.

## Règles et cas limites

- **Les fichiers de test sont couverts** comme le reste : un renvoi de tâche y vieillit pareil, et
  ils en portaient dix-sept.
- **Un commentaire qui cite un identifiant de tâche pour une raison légitime** n'existe pas dans le
  code de produit : c'est la carte qui porte le lien, jamais le fichier.
- **`scripts/ci/`** est exclu de fait, sa portée ne le nommant pas — et c'est heureux : le
  vérificateur cite les tâches qu'il applique.

## Documentation impactée

- **`base/app/README.md`** — la phrase qui annonçait la portée disait « les `.vue` d'`app/**` » ;
  elle dit maintenant tout le code du produit, avec les deux exclusions et la raison du préfixe.
- **`services/README.md`** et **`shared/README.md`** — deux dossiers désormais gouvernés par la
  règle, et dont les README ne la mentionnaient pas. Chacun reçoit le renvoi, pas une copie.

## Vérification

### Ce que la portée étendue fait apparaître

```
avant l'extension    96 violations · 56 fichiers
                     services 39 · base/server 21 · spaces/admin 13
                     spaces/entreprise 12 · shared 6 · base/app 5

les gabarits .vue     0 violation   ← le verrou les tenait déjà
```

### Le traitement, en deux temps

```
81 renvois entre parenthèses    retirés mécaniquement, sans rien perdre
15 récits tissés dans la phrase réécrits au présent, un par un
 1 faux positif                 la phrase reformulée, pas la règle
```

Le geste sur les quinze, à l'exemple :

```
❌  Le zéro était auparavant remplacé par `null`, et le rail n'affichait alors rien.

✅  ZÉRO NE SE REMPLACE PAS PAR `null` : le rail n'afficherait alors rien, et rien du
    tout se lit « la carte n'a pas fini de charger ».
```

La contrainte reste, le récit part. C'est ce que la règle demande, et c'est là qu'était le travail —
un remplacement automatique aurait emporté l'avertissement avec le récit.

### Le faux positif

`base/app/plugins/acquisition-referrer.client.ts` disait « que Nitro ne relaie pas **jusqu'ici** ».
C'est un « ici » d'espace, pas de temps, et le motif de chronologie l'attrape quand même. La phrase
devient « jusqu'au client » : elle y gagne en précision, et la règle reste sans exception.

### ⚠️ Un fichier GÉNÉRÉ modifié, rattrapé par la seule gate qui pouvait le voir

Le passage mécanique a couru sur `shared/**/*.ts` sans respecter la liste d'exclusions qu'il venait
d'écrire : **il a retiré neuf renvois de `shared/types/api.ts`**, qui est produit par
`openapi-typescript` à partir d'`api/openapi.json`. Les « (tâche 102) » qu'il a effacés ne
s'écrivent pas là — ils viennent des annotations de l'API, dans une autre lane.

Rien ne l'a signalé : ni le lint, ni le format, ni le type-check, ni les 370 tests. **Seule la
vérification de dérive de contrat l'a vu**, en régénérant le fichier et en comparant. Elle aurait
sorti la même erreur en CI, sur une PR déjà ouverte.

La leçon tient en une ligne : **un fichier généré ne se corrige pas, il se régénère** — et un
remplacement automatique doit lire les exclusions de la règle qu'il applique, pas seulement son
périmètre.

### Après

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
$ bun run quality-format-check                       → All matched files use Prettier code style!
$ bun run quality-lint-check                         → 0 erreur, 20 avertissements préexistants
$ bunx vitest run                                    → 24 fichiers, 370 tests passés
$ bun run quality-contracts-check                    → exit 0, aucune dérive
```

⚠️ La dernière est celle qui a rattrapé le fichier généré : elle rendait `exit 1` au premier
passage.

### Le vérificateur n'a pas changé d'une ligne

`commentLinesOf` travaille sur le texte brut et suit l'ouverture et la fermeture des blocs `//`,
`/* */` et `<!-- -->`. Il n'a jamais lu d'arbre de gabarit, contrairement aux sept autres. Le diff de
`scripts/ci/` se réduit à **huit lignes de chemins** dans `rules.yaml`.

## Livré

| Fichier | Geste |
|---|---|
| `webapp/scripts/ci/rules.yaml` | (M) cinq motifs de chemin, deux exclusions de fichiers générés |
| `webapp/base/app/README.md` | (M) la portée réelle, les exclusions, la raison du préfixe `app-` |
| `webapp/services/README.md`, `webapp/shared/README.md` | (M) le renvoi vers la règle qui les gouverne désormais |
| 56 fichiers de `services/`, `shared/`, `base/`, `spaces/` | (M) renvois de tâche retirés, récits réécrits au présent |

## Reste à faire (hors périmètre)

- **`scripts/` reste hors portée**, et c'est heureux : le vérificateur cite les tâches qu'il
  applique, il se condamnerait lui-même.
- **Le volume et le ton des commentaires** — 40 % des lignes sur certains écrans — qu'aucun verrou ne
  traite et qui demande un jugement humain.

## Définition de terminé

- [x] Le verrou inspecte les `.ts` d'`app/**`, du serveur intermédiaire, de `services/` et `shared/`.
- [x] Les deux fichiers générés en sont exclus.
- [x] Le vérificateur ne rend plus aucune violation.
- [x] Les quinze récits tissés sont réécrits au présent, pas coupés.
- [x] Les trois README disent la portée réelle.
- [x] Le format, le lint et les tests passent.
