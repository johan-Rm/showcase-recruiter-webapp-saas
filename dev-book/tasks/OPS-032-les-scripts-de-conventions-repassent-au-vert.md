# TASK OPS-032 — les scripts de conventions repassent au vert

- **Créée le** : 2026-08-09.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042`.
- **Statut** : ✅ fait le 2026-08-09 — les deux scripts d'outillage sont formatés et le **type-check
  passe entièrement**, sur les quatre espaces, là où il échouait partout. Le verdict du vérificateur
  n'a pas bougé — **18 règles, aucune violation** —, et ses 74 tests restent verts : c'est ce qu'il
  fallait démontrer, la correction touchant un typage et non un comportement. Le format ne signale
  **plus aucun** fichier d'outillage ; les onze restants sont ceux d'`ENT-073`.
- **Type** : fix — deux gates de la branche d'intégration sont rouges, et elles empêchent toutes les
  autres de s'exécuter. On répare les deux fichiers d'outillage responsables.
- **Tests** : les suites existantes **`scripts/ci/checkers.vitest.ts` et `scripts/ci/regions.vitest.ts`
  rejouées**. C'est la nature exacte du besoin : la correction de typage ne change aucun
  comportement, et c'est précisément ce qu'il faut démontrer plutôt qu'affirmer. Aucun test neuf —
  le reformatage, lui, ne produit aucun code exécutable.
- **Réf** : `ENT-073` — **parallèle et complémentaire** : la branche d'intégration ne redevient verte
  qu'une fois les **deux** mergées, cf. *Règles et cas limites* · les commits amont `47e20ae`
  (`feat(ci): load structural convention rules`) et le merge `89dc82e` (PR #3 de l'organisation),
  qui ont introduit les deux défauts · fichiers touchés :
  [scripts/ci/structure-checkers.ts](../../webapp/scripts/ci/structure-checkers.ts),
  [scripts/ci/verify-conventions.ts](../../webapp/scripts/ci/verify-conventions.ts).

## Contexte

Le webapp est gardé par un job d'intégration continue qui enchaîne sept étapes : lint, format,
type-check, conventions d'architecture, tests, contrat d'API, compilation. Une étape qui échoue
**arrête le job** — tout ce qui suit est sauté, pas exécuté.

**Deux de ces étapes échouent aujourd'hui sur la branche d'intégration elle-même**, et le format est
la deuxième. Conséquence mesurée sur les trois dernières PR : elles sortent rouges au bout d'une
minute, et **rien n'est vérifié** — ni les tests, ni les conventions, ni la compilation. Une PR
parfaitement saine et une PR qui casse tout produisent exactement le même verdict. La CI a cessé
d'informer.

Les deux défauts sont arrivés ensemble, par la reprise mergée depuis l'organisation
(`89dc82e`, PR #3), et deux fichiers d'outillage en portent leur part.

### Le format — deux scripts jamais passés à l'outil

`scripts/ci/structure-checkers.ts` et `scripts/ci/verify-conventions.ts` ne respectent pas la mise en
forme du dépôt. Rien de fonctionnel : des retours à la ligne et des regroupements d'arguments.

### Le typage — un faux positif que le compilateur ne sait pas lever

`structure-checkers.ts:35` promet de rendre `TemplateElement | null` et rend `roots[0]`, dont le type
est `TemplateElement | undefined` :

```ts
return roots.length === 1 && roots[0]?.tag !== 'template' ? roots[0] : null
```

**Le code est juste** : quand `roots.length === 1`, `roots[0]` existe forcément. C'est le typage
strict des accès indexés — activé sur ce dépôt — qui refuse de le déduire, la longueur d'un tableau
et l'existence de son premier élément n'étant pas liées pour le compilateur. Le vérificateur
fonctionne d'ailleurs parfaitement : les conventions d'architecture, elles, sortent vertes.

Il faut donc **lier la valeur avant de la tester**, pour que le compilateur suive le raisonnement que
le code tient déjà.

## Périmètre

- **Reformater les deux scripts** avec l'outil de mise en forme du dépôt. Aucune retouche manuelle,
  aucun changement de logique.
- **Corriger le typage de `singleRootOf`** en liant le premier élément avant de le tester. Le
  comportement rendu doit être **identique** : une seule racine qui n'est pas un `<template>` →
  cette racine ; tout autre cas → `null`.
- **Rejouer les deux suites de tests** qui couvrent ces vérificateurs.

## Hors périmètre

- **Les onze fichiers de l'espace entreprise** également non formatés — c'est `ENT-073`, sur sa lane,
  avec son commit. Les deux lots n'ont en commun que leur cause.
- **L'élargissement de la vérification de format à `docs/` et `dev-book/`.** Le job d'intégration
  tourne avec `webapp` pour dossier de travail : la documentation et les cartes de tâche ne sont
  couvertes par **aucune** gate de format. C'est un trou réel, mais le combler reformaterait des
  dizaines de fichiers d'un coup — un sujet à cadrer pour lui-même.
- **La logique des vérificateurs** : on répare un typage, on ne révise aucune règle.
- **La cause en amont** — une reprise mergée sans que ses gates soient vertes. C'est une question de
  protection de branche côté organisation, pas de code.

## Décisions de cadrage

### Deux cartes plutôt qu'une, et la règle tombe juste ici

Un seul événement a cassé treize fichiers, et la tentation était d'en faire une seule tâche
mécanique. Refusé : la lane outillage écrirait alors onze fichiers de l'espace entreprise, ce que le
registre des agents interdit en toutes lettres. Et ici la règle ne coûte rien — la ligne de partage
tombe exactement entre les deux lots, aucun fichier ne chevauche, et chaque PR se relit en trente
secondes au lieu d'une seule illisible.

### Lier la valeur plutôt qu'assouplir le typage

Deux autres voies existaient : une assertion de non-nullité, ou un `?? null`. Toutes deux masquent
le raisonnement au lieu de l'exprimer. Lier le premier élément dans une constante et le tester dit
au compilateur ce que le code sait déjà — et le prochain lecteur comprend pourquoi la garde est là.

### Le sens des comparaisons suit le fichier, pas mes habitudes

Écart relevé et corrigé pendant l'implémentation : la condition avait d'abord été écrite
littéral-à-gauche (`1 === roots.length`). Aucune règle de lint ne l'impose dans ce dépôt, et ce
fichier écrit ses dix autres comparaisons dans l'autre sens. Un correctif qui introduit un second
style dans le fichier qu'il répare coûte plus qu'il ne rapporte.

### Rejouer les tests plutôt qu'en écrire

Ces vérificateurs sont déjà couverts. Un test neuf sur une correction de typage documenterait le
compilateur, pas le produit. Ce qui compte est la **non-régression**, et les suites existantes la
prouvent — à condition de les lancer et d'en lire la sortie.

## Règles et cas limites

- **Aucun comportement ne change.** Toute différence de sortie du vérificateur après cette tâche est
  un défaut, pas une amélioration : les conventions rendent déjà 18 règles sans violation.
- **Cette carte seule ne rend pas la branche d'intégration verte.** La vérification de format échoue
  tant qu'**un seul** fichier reste non conforme : il faut `ENT-073` aussi. Ne pas conclure au
  premier merge que la réparation a échoué.
- **Le reformatage passe par l'outil, jamais à la main** : une correction manuelle produirait un
  fichier que l'outil voudrait encore changer.
- **Vérifier les deux gates ensemble** après coup — format **et** type-check —, sur les quatre
  espaces pour le second : il s'exécute une fois par espace.
- **`git blame` devient plus bruyant** sur ces deux fichiers. Coût assumé, payé une fois.

## Documentation impactée

Aucune. Aucune convention ne change, aucune règle n'est ajoutée ni retirée, aucun `README.md` de
dossier ne voit sa loi modifiée. La carte répare l'application des règles, pas les règles.

## Définition de terminé

- [x] `bun run quality-format-check` ne signale plus aucun des deux scripts d'outillage.
- [x] `bun run quality-type-check` passe **sans aucune erreur**, sur les quatre espaces.
- [x] `bun run quality-conventions-check` rend toujours **18 règles, aucune violation** — le
      comportement du vérificateur n'a pas bougé.
- [x] `checkers.vitest.ts` et `regions.vitest.ts` rejoués et verts, sortie lue.
- [x] Le diff des deux scripts ne contient **aucun changement de logique** hors la correction de
      typage — relu ligne à ligne.
- [x] La carte dit que `ENT-073` reste nécessaire pour que la branche d'intégration soit verte.
- [x] Les gates de la lane sont passées à l'ouverture de sa PR, en une passe : **type-check vert sur
      les quatre espaces**, conventions à 18 règles sans violation, **308 tests**, contrat OpenAPI
      sans dérive, **build SSR complet**, lint sans erreur. Le **format sort rouge sur 11 fichiers**,
      et **aucun n'est dans ce diff** — vérifié par intersection : ce sont exactement les onze de
      l'espace entreprise que porte `ENT-073`. C'est le résultat attendu, écrit au cadrage.

## Vérification

Sorties lues sur la branche, l'une après l'autre. Aucune n'est une intention.

| Ce qui a été lancé | Résultat |
| --- | --- |
| `checkers.vitest.ts` + `regions.vitest.ts` | **74 tests, 2 fichiers, tous verts** |
| `bun run quality-conventions-check` | **✔ 18 règles, aucune violation** |
| `bun run quality-type-check` | **vert** — les quatre espaces, plus aucune erreur |
| `bunx prettier --check` sur les deux scripts | **« All matched files use Prettier code style! »** |

**Le verdict du vérificateur est la preuve qui compte.** Il rendait déjà 18 règles sans violation
avant cette tâche, il les rend toujours : le typage a changé, pas le comportement. Un test neuf
n'aurait rien ajouté à cette démonstration — il aurait couvert le compilateur.

**Le balayage du format, avant et après :**

| | Scripts d'outillage | Espace entreprise |
| --- | --- | --- |
| Avant | 2 non formatés | 11 non formatés |
| Après | **0** | 11 — périmètre d'`ENT-073` |

**Le diff, relu ligne à ligne.** Hors la correction de typage — la constante liée, son commentaire
et la condition réécrite —, tout le reste est de la mise en forme : des arguments regroupés sur une
ligne, une parenthèse ajoutée autour d'un ternaire. Aucune expression, aucune condition, aucun
littéral modifié.

## Livré

- `webapp/scripts/ci/structure-checkers.ts` (M) — `singleRootOf` lie sa racine avant de la tester.
  Le compilateur ne reliait pas `roots.length === 1` à l'existence de `roots[0]`, que le typage
  strict des accès indexés rend `… | undefined` ; la constante porte le raisonnement que la
  condition tenait déjà. Un commentaire dit pourquoi la replier ramènerait l'erreur — sans lui, la
  prochaine « simplification » rouvre le défaut. Reformaté pour le reste.
- `webapp/scripts/ci/verify-conventions.ts` (M) — reformatage seul, aucune ligne de logique touchée.

## Reste à faire (hors périmètre)

- **`ENT-073`** — les onze fichiers de l'espace entreprise, toujours non formatés. **La branche
  d'intégration reste rouge au format tant qu'elle n'est pas passée** : cette carte ne suffit pas
  seule, c'était prévu et c'est mesuré ci-dessus.
- **`docs/` et `dev-book/` hors de toute gate de format** — le job d'intégration tourne avec
  `webapp` pour dossier de travail. Trou réel, nommé au cadrage, non traité : le combler
  reformaterait des dizaines de cartes et de pages d'un coup.
- **La cause en amont** — une reprise mergée sans que ses gates soient vertes. Question de
  protection de branche côté organisation, pas de code.
