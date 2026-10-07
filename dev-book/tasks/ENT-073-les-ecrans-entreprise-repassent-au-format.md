# TASK ENT-073 — les écrans entreprise repassent au format

- **Créée le** : 2026-08-09.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042`.
- **Statut** : ✅ fait le 2026-08-09 — les onze fichiers sont conformes et **la vérification de format
  passe sur tout le webapp**. Avec `OPS-032`, déjà mergée, c'est la dernière gate rouge de la branche
  d'intégration qui s'éteint. Le diff est mécanique et je l'ai démontré caractère par caractère
  plutôt qu'affirmé — cf. *Vérification* —, et les cinq écrans rendent **exactement** la même chose
  avant et après.
- **Type** : fix — onze fichiers de l'espace ne respectent plus la mise en forme du dépôt, et la
  vérification qui le constate bloque toutes les PR du webapp.
- **Tests** : `sans objet` — un reformatage ne produit **aucun code exécutable** : il déplace des
  retours à la ligne et regroupe des attributs, sans toucher une expression. La preuve est le
  passage au vert de `bun run quality-format-check`, doublé de la suite de tests existante rejouée
  **inchangée** — 308 tests, qui démontrent qu'aucun comportement n'a bougé.
- **Réf** : `OPS-032` — **parallèle et complémentaire** : la branche d'intégration ne redevient verte
  qu'une fois les **deux** mergées, cf. *Règles et cas limites* · le merge `89dc82e` (PR #3 de
  l'organisation), qui a introduit l'écart · les onze fichiers sont listés dans *Périmètre*.

## Contexte

Le webapp est gardé par un job d'intégration continue qui enchaîne sept étapes : lint, format,
type-check, conventions d'architecture, tests, contrat d'API, compilation. Une étape qui échoue
**arrête le job** — tout ce qui suit est sauté, pas exécuté. Le format est la **deuxième**.

Depuis la reprise mergée depuis l'organisation (`89dc82e`, PR #3), onze fichiers de l'espace
entreprise ne respectent plus la mise en forme du dépôt. La branche d'intégration échoue donc
elle-même à cette étape, et **toute PR qui touche le webapp hérite de ce rouge, quelle que soit sa
lane**.

Ce n'est pas qu'une question d'affichage. Mesuré sur les trois dernières PR : elles sortent rouges au
bout d'une minute, et **rien n'est vérifié** — ni les tests, ni les conventions, ni la compilation.
Une PR saine et une PR qui casse tout produisent le même verdict. La CI a cessé d'informer, et les
deux dernières tâches n'ont pu être livrées qu'en relançant les gates à la main, une par une.

L'écart est purement mécanique : les fichiers n'ont jamais été passés à l'outil de mise en forme.
Aucun n'a de défaut de contenu.

## Périmètre

Reformater, avec l'outil du dépôt et sans aucune retouche manuelle, les onze fichiers suivants :

- `app/layouts/cockpit.vue` et `app/layouts/README.md` ;
- `app/pages/credits.vue`, `index.vue`, `l-entreprise.vue`, `l-equipe.vue`, `mon-compte.vue`,
  `mon-profil.vue` ;
- `app/pages/recherches/[uuid]/index.vue`, `app/pages/recherches/[uuid]/table.vue`,
  `app/pages/recherches/nouvelle.vue`.

Tous sous `webapp/spaces/entreprise/`.

## Hors périmètre

- **Les deux scripts de CI** également non formatés (`structure-checkers.ts`,
  `verify-conventions.ts`) et **l'erreur de typage** que l'un d'eux porte — c'est `OPS-032`, sur la
  lane outillage, avec son commit.
- **Tout changement de contenu, de rendu ou de comportement.** Si le reformatage donne envie de
  corriger autre chose dans un de ces fichiers, ça ne se fait pas ici : le diff doit rester
  entièrement mécanique pour être relisible.
- **L'élargissement de la vérification de format à `docs/` et `dev-book/`**, aujourd'hui hors de
  toute gate — nommé, non traité.
- **Les avertissements de lint** (0 erreur, 4 avertissements de style), qui ne bloquent rien.

## Décisions de cadrage

### Deux cartes plutôt qu'une

Un seul événement a cassé treize fichiers, et l'on aurait pu tout réparer d'un geste. Refusé : la
lane qui l'aurait porté aurait écrit dans le périmètre de l'autre, ce que le registre des agents
interdit. Ici la règle ne coûte rien — la ligne de partage tombe exactement entre les deux lots,
aucun fichier ne chevauche, et chaque PR se relit en trente secondes.

### L'outil fait le travail, pas la main

Une correction manuelle produirait un fichier que l'outil voudrait encore changer, et le rouge
reviendrait au premier passage suivant. Le reformatage s'obtient par la commande, et rien d'autre
n'entre dans le diff.

### Exemption de test, et la raison n'est pas « c'est petit »

Un reformatage ne crée, ne modifie ni ne supprime aucune expression : il n'y a **rien** de nouveau à
couvrir. Ce qu'il faut prouver, c'est l'**absence** de changement — et c'est la suite existante,
rejouée sans y toucher, qui le démontre. Écrire un test pour un retour à la ligne testerait l'outil
de mise en forme, pas le produit.

## Règles et cas limites

- **Le diff doit être entièrement mécanique.** Toute ligne dont le contenu — et pas seulement la
  disposition — a changé est une erreur à retirer avant de committer.
- **Cette carte seule ne rend pas la branche d'intégration verte.** La vérification échoue tant
  qu'**un seul** fichier reste non conforme : il faut `OPS-032` aussi. Ne pas conclure au premier
  merge que la réparation a échoué.
- **Trois de ces fichiers viennent d'être touchés par `ENT-071` et `ENT-072`** — les deux écrans de
  recherche et la page de création. Ils étaient déjà non conformes avant ces tâches, qui n'ont rien
  aggravé ; le reformatage ne doit pas non plus défaire ce qu'elles ont livré.
- **Vérifier le rendu après coup**, au moins sur le cockpit et un écran de recherche : un outil de
  mise en forme ne change pas le sens du HTML, mais l'affirmer sans regarder serait une supposition.
- **`git blame` devient plus bruyant** sur ces onze fichiers. Coût assumé, payé une fois.

## Documentation impactée

Aucune. Aucune convention ne change et aucun `README.md` ne voit sa loi modifiée — celui des layouts
entreprise est **reformaté**, pas réécrit.

## Définition de terminé

- [x] `bun run quality-format-check` ne signale plus aucun fichier — « All matched files use
      Prettier code style! » sur **tout** le webapp.
- [x] Le diff est **entièrement mécanique**, démontré et non affirmé : neuf fichiers sur onze sont
      identiques au caractère près une fois les espaces, virgules finales et points-virgules
      neutralisés ; les deux derniers ne divergeaient que par une paire de parenthèses redondantes,
      isolée et lue.
- [x] La suite de tests existante passe **inchangée** — 308 tests, 22 fichiers.
- [x] `bun run quality-conventions-check` rend toujours 18 règles sans violation.
- [x] Le rendu est constaté sur **cinq** écrans, avant et après, en remisant le diff : mêmes nombres
      d'éléments, mêmes zones de coque, même barre haute.
- [x] La carte dit que `OPS-032` reste nécessaire — elle l'était, et elle est mergée.
- [x] Les gates de la lane sont passées à l'ouverture de sa PR, en une passe — **et toutes les sept
      sont vertes**, ce qui n'était plus arrivé depuis la reprise de l'organisation : lint sans
      erreur, **format vert sur tout le webapp**, type-check vert sur les quatre espaces, 18 règles
      d'architecture sans violation, 308 tests, contrat sans dérive, **build SSR complet**.

## Vérification

### Le diff est mécanique, et c'est mesuré

`--ignore-all-space` ne prouve rien ici : l'outil **redécoupe les lignes**, donc les frontières
changent même sans un caractère de contenu modifié. La comparaison a donc été faite sur le contenu
débarrassé de tout espace, puis des virgules finales et points-virgules — les deux conventions que
l'outil applique.

| Étape de la comparaison | Résultat |
| --- | --- |
| Espaces neutralisés | 7 fichiers identiques, 4 divergents |
| \+ virgules finales et points-virgules neutralisés | **9 identiques**, 2 divergents |
| Les 2 restants, isolés au caractère | **une paire de parenthèses** chacun, redondante autour d'un ternaire |

Le seul écart qui ne soit pas de la disposition pure :

```
- const name = computed(() => (search.value ? displayName(…) || t('…') : ''))
+ const name = computed(() =>
+   search.value ? displayName(…) || t('…') : '',
+ )
```

**Aucune expression, aucune valeur, aucune classe, aucun texte, aucun attribut n'est modifié.**

### Le rendu ne bouge pas — avant et après, cinq écrans

Mesuré dans un navigateur connecté sur la stack du worktree, en remisant le diff pour obtenir la
base de référence sur **la même** stack :

| Écran | Éléments | Zones de coque | Barre haute |
| --- | --- | --- | --- |
| Cockpit / accueil | 271 | topbar · content · navigation | — |
| Liste des recherches | 505 | topbar · content | — |
| Création de recherche | 133 | topbar · content · gouttière | 55 px |
| Mon compte | 100 | topbar · content · navigation | — |
| L'équipe | 104 | topbar · content · navigation | — |

**Ces chiffres sont ceux d'avant ET ceux d'après**, à l'unité près. Deux avertissements JavaScript
apparaissent sur le parcours — des appels à un point de terminaison des outils de développement de
Nuxt — et ils sont **antérieurs** : présents à l'identique sans le diff.

### Les gates jouées ici

| | |
| --- | --- |
| Format, tout le webapp | **vert** |
| Tests | **308 sur 308**, 22 fichiers — inchangés |
| Conventions d'architecture | **18 règles, aucune violation** |
| Périmètre du diff | **exactement les 11 fichiers**, aucun de plus |

## Livré

Onze fichiers de `webapp/spaces/entreprise/` (M), reformatés par l'outil du dépôt et par lui seul :
le layout du cockpit et son README, six pages de compte et d'accueil, et les trois écrans de
recherche. Aucune retouche manuelle, aucun changement de contenu.

## Reste à faire (hors périmètre)

- **`docs/` et `dev-book/` ne sont couverts par aucune gate de format** — le job d'intégration
  tourne avec `webapp` pour dossier de travail. Trou réel, nommé au cadrage des deux tâches, non
  traité : le combler reformaterait des dizaines de cartes et de pages d'un coup.
- **La cause première** — une reprise mergée sans que ses gates soient vertes. Question de protection
  de branche côté organisation, pas de code.
- **`git blame` est plus bruyant** sur ces onze fichiers. Coût assumé au cadrage, payé une fois.
