# TASK ENT-080 — entreprise : la branche repasse ses propres conventions

- **Créée le** : 2026-08-10.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-10 — la gate qualité du webapp repasse au vert : 21 marqueurs de
  racine posés dans les archives, deux commentaires réécrits au présent, trois fichiers reformatés,
  **et une erreur de typage que la gate locale ne voyait pas** — cf. *Décisions de cadrage*.
- **Type** : fix de conformité. Aucune ligne de comportement ne change — marqueurs de débogage,
  temps d'un commentaire, espacement.
- **⚠️ Cadrée après coup, et révélée par la gate.** Les quatre tâches précédentes de la branche
  n'ont jamais lancé la gate qualité — c'est le contrat du dépôt, elle tourne une fois à l'ouverture
  de la PR. Elle a trouvé 23 violations. Cette carte les répare, et sert de trace : sans elle,
  personne ne saurait pourquoi les archives portent des marqueurs différents de ceux qu'elles
  archivent.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable. Ce qu'elle change est un nom
  de classe servant de repère dans le DOM inspecté, le temps de deux phrases de commentaire, et de
  l'espacement. La preuve est **la gate elle-même**, dont la sortie est reportée plus bas.
- **Réf** : `ENT-077` — qui a créé les archives sans les passer à la gate, et dont cette carte
  complète aussi la section *Documentation impactée*, absente · `ENT-078`, `ENT-079` — dont deux
  commentaires racontaient la chronologie · `WEB-018` — le verrou `app-no-comment-journal`.

## Contexte

Les gates tournent **une fois, à l'ouverture de la PR** — pas à chaque commit. Cinq tâches se sont
donc empilées sur `espace/entreprise` sans qu'aucune ne les lance, et la première passe en a trouvé
vingt-trois.

Trois familles, toutes mécaniques :

- **21 marqueurs de racine.** `components-root-marker` veut que la racine d'un composant porte en
  première classe le marqueur kebab de son chemin — `search-v1/ProfileCard.vue` doit dire
  `search-v1-profile-card`. Les archives d'`ENT-077` ont été copiées telles quelles et portent
  encore le marqueur de leur original, `search-profile-card`. Vingt et un composants, deux dossiers.
- **2 commentaires** écrits avec « désormais » — `app-no-comment-journal` interdit de raconter la
  chronologie dans le code : `git log` la porte, `dev-book/tasks/` porte la décision.
- **3 fichiers** au format non conforme, produits par des éditions successives sans passage de
  Prettier.

## Périmètre

- **Les 21 marqueurs de racine** des dossiers `search-v1/` et `search-v2/`.
- **Les deux commentaires**, réécrits au présent sans perdre la contrainte qu'ils portent.
- **Le formatage** de `search/ProfileCard.vue`, `search-v2/ProfileCard.vue` et de la page de la
  Visionneuse.
- **La section *Documentation impactée* de la carte `ENT-077`**, qui n'en avait pas.

## Hors périmètre

- **Les 20 avertissements de lint** que la gate signale sans échouer — attributs Vue mal ordonnés,
  propriétés sans valeur par défaut. Préexistants, répartis sur les quatre espaces, et sans rapport
  avec cette branche. Nommés, non traités.
- **Toute correction de comportement.** Une carte de conformité qui en profiterait pour changer un
  rendu serait indébogable : on ne saurait plus ce qui a bougé pour quelle raison.

## Décisions de cadrage

### Les archives changent de marqueur, et c'est voulu

Le marqueur sert à remonter, dans le DOM inspecté, d'un bloc d'écran au fichier qui le rend. Deux
composants qui rendent le même marqueur rendent ce chemin ambigu — et c'était exactement le cas :
trois `search-profile-card` coexistaient, un par version.

Les archives d'`ENT-077` diffèrent donc de ce qu'elles archivent **sur ce seul point**. C'est le prix
de leur autonomie, et il est faible : le marqueur n'habille rien, il nomme.

### ⚠️ La gate lancée DANS LE CONTENEUR donne un faux vert sur le type-check

Découvert par la CI, après un premier passage local annoncé vert.

`make webapp-quality` exécute `bun run quality-check` **dans le conteneur** ; la CI, elle, l'exécute
**sur l'hôte**, après un `bun install --frozen-lockfile` propre. Les deux ne voient pas la même
chose : dans le conteneur, `vue-tsc --build --force` ne prend pas les pages de l'espace dans son
programme et sort `exit=0` quoi qu'elles contiennent.

Prouvé et non supposé — une erreur grossière posée volontairement dans la page de la Visionneuse :

```
const sonde: number = "une erreur de type volontaire"

conteneur (make webapp-quality)  → exit=0        ← ne voit rien
conteneur neuf, sans serveur     → exit=0        ← ce n'est pas le serveur de dev
hôte (bun run quality-type-check) → error TS2322 ← la voit, comme la CI
```

L'erreur réelle qu'il masquait : `isRetained(current)` recevait `CandidateCard | null` alors que la
fonction attend une carte. Elle vient d'`ENT-078`, où le passage de l'anneau sur le bloc a introduit
un profil courant qui peut être absent.

**Conséquence pratique, et elle dépasse cette carte : les gates du webapp se lancent sur l'HÔTE.**
`cd webapp && bun run quality-check` reproduit la CI ; `make webapp-quality` ne la reproduit pas.

### Une carte plutôt qu'un commit sans carte

Arbitré avec l'humain. Le correctif traverse trois tâches déjà figées ; les amender demanderait de
réécrire l'historique, y compris le commit d'une session voisine encore au travail. Une carte de
conformité respecte l'invariant — un commit, une tâche — et laisse la trace qui manquerait sinon.

## Règles et cas limites

- **Le marqueur se pose en PREMIÈRE classe**, avant les utilitaires : c'est ce que la règle vérifie.
- **`DismissedDrawer`** portait sa classe seule, sans utilitaire à la suite — remplacement exact et
  non préfixé comme les vingt autres.
- **`search-v1/ActionBar.vue`** dérive de `ActionBar.vue`, dont le marqueur est `action-bar` : son
  marqueur d'archive suit le chemin, `search-v1-action-bar`, et non l'original.
- **Aucun de ces noms n'est stylé.** Vérifié : les classes de marqueur ne portent aucune règle CSS,
  la mise en page est en utilitaires. Les renommer ne peut rien casser à l'écran.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`, aucun `README.md` : les règles violées existaient déjà et ne
changent pas — c'est le code qui s'y conforme. `base/app/components/README.md`, qui porte la règle du
marqueur, reste exact mot pour mot.

## Définition de terminé

- [x] `bun run quality-check` passe : lint, format, type-check, conventions, tests.
- [x] Zéro violation sur les 19 règles de conventions.
- [x] Les trois versions de la Visionneuse rendent trois marqueurs distincts.
- [x] Aucun changement de comportement — le diff ne touche que des noms de classe, deux phrases de
      commentaire et de l'espacement.
- [x] La carte `ENT-077` déclare sa documentation impactée.

## Vérification

### La gate, avant

```
✖ components-root-marker — 21 violation(s)
✖ app-no-comment-journal — 2 violation(s)
    search/Stack.vue:104                  → « désormais » — chronologie
    pages/recherches/[uuid]/index.vue:226 → « désormais » — chronologie
[warn] spaces/entreprise/app/components/search-v2/ProfileCard.vue
[warn] spaces/entreprise/app/components/search/ProfileCard.vue
[warn] spaces/entreprise/app/pages/recherches/[uuid]/index.vue
23 violation(s) sur 19 règles.
```

### Ce que la CI a trouvé en plus

```
spaces/entreprise/app/pages/recherches/[uuid]/index.vue(238,28): error TS2345:
  Argument of type 'CandidateCard | null' is not assignable to parameter of type 'CandidateCard'.
```

### La gate, après — **sur l'hôte**, seule exécution qui reproduise la CI

```
$ cd webapp && bun run quality-check
  lint        : 20 problems (0 errors, 20 warnings)   ← préexistants, hors périmètre
  format      : All matched files use Prettier code style!
  type-check  : public · candidat · entreprise · admin — aucune erreur
  conventions : ✔ 19 règles, aucune violation
  tests       : Test Files 24 passed (24) · Tests 357 passed (357)
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search-v1/*.vue` (11) | (M) marqueur de racine `search-v1-*` |
| `spaces/entreprise/app/components/search-v2/*.vue` (10) | (M) marqueur de racine `search-v2-*` |
| `spaces/entreprise/app/components/search/Stack.vue` | (M) un commentaire au présent |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) un commentaire au présent, formatage |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) formatage |
| `dev-book/tasks/ENT-077-…md` | (M) sa section *Documentation impactée*, qui manquait |

## Reste à faire (hors périmètre)

- **Les 20 avertissements de lint** du webapp, répartis sur les quatre espaces. Ils ne bloquent pas
  la gate, et aucune carte ne les porte.
- **Lancer la gate plus tôt.** Cinq tâches ont été commitées avant qu'une seule passe ne révèle
  vingt-trois violations. Le contrat du dépôt le permet — les gates sont à l'ouverture de la PR —
  mais l'écart grandit avec la pile. Constat pour `espace/outillage`, pas une action d'ici.
- **`make webapp-quality` ment sur le type-check** — il tourne dans le conteneur, qui ne voit pas les
  pages d'espace. Deux issues possibles, aucune décidée ici : faire pointer la cible du Makefile vers
  l'hôte, ou rapprocher le conteneur de la CI. **Appartient à `espace/outillage`** : c'est le
  Makefile et l'outillage de gates, pas l'espace entreprise.
