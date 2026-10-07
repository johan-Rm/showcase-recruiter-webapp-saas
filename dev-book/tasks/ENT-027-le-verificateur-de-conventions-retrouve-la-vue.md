# TASK ENT-027 — le vérificateur de conventions retrouve la vue

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — l'extraction lit le fichier entier, sept tests l'exigent, et
  la dette de six violations est inscrite avec son propriétaire.
- **Type** : Outillage qualité de la webapp — correction.
- **Tests** : `verify-conventions.vitest.ts`, colocalisé avec le vérificateur, sur des fichiers
  appâts. C'est **le cœur de la carte** : le défaut corrigé ici n'a pas de symptôme visible, il rend
  vert. Sans test qui exige la détection, rien ne dira qu'il revient.
- **Réf** : `ENT-028` (aval — la mise en conformité des pages, que cette carte doit rendre
  nécessaire) · [base/app/pages/README.md](../../webapp/base/app/pages/README.md) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — « README → docs →
  règle → script CI → gate avant merge »

## Contexte

`webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` porte **310 lignes de template**,
quatre listes, six boutons et deux tiroirs. Le README du dossier dit pourtant qu'une page « ne
contient que la composition de ses sections » et que « toute brique interactive vit dans un
composant, jamais dans la page ».

La règle qui protège cette convention **existe** — `pages-no-interactive-template` —, elle **cite ce
README**, et son périmètre **couvre bien les espaces** : sept des huit règles portent
`spaces/*/app/**`. Rien de tout cela n'était en cause.

Le vérificateur extrait la région `template` avec une expression **non gourmande** :

```
<template\b[^>]*>([\s\S]*?)</template>
```

Elle s'arrête à la **première** `</template>` — celle qui ferme le premier slot nommé. Sur cette
page, le template racine ouvre ligne 201, la première fermeture arrive ligne 259 : **58 lignes lues
sur 310**, soit 19 % du fichier. Les deux `v-model:open` interdits sont aux lignes 263 et 488, juste
après la coupure. La CI affiche « 8 règles, aucune violation ».

Deux règles sont concernées, toutes deux en `region: template` :
`pages-no-interactive-template` et **`app-no-hardcoded-text`**, qui vaut pour toute la webapp — du
texte en dur passe donc sous le radar partout où un slot nommé apparaît tôt dans un fichier.

C'est le même défaut que le CSS d'`ENT-024` : présent, correct dans l'intention, **inopérant**, et
invisible parce que la sortie est verte. Un garde-fou dont personne ne vérifie qu'il agit n'est pas
un garde-fou.

### La parade était déjà écrite, et jamais appliquée

`webapp/README.md`, qui gouverne `scripts/ci/`, porte cette phrase depuis l'origine :

> **Toute nouvelle règle doit être prouvée par une violation volontaire** avant d'être commitée :
> une gate qui ne mord jamais est pire qu'inutile — elle rassure sans protéger.

**Aucune des huit règles ne l'a honorée.** Cette carte n'introduit donc pas une pratique nouvelle :
elle applique une convention que le dépôt s'était donnée puis avait oubliée — et c'est précisément
son absence qui a laissé passer trois ans de lecture à 19 %.

Le même README annonce par ailleurs **quatre** règles actives quand `rules.yaml` en porte **huit** :
sa liste est périmée.

## Périmètre

- L'extraction de région dans `webapp/scripts/ci/verify-conventions.ts`.
- Une fixture de non-régression par règle, et le test qui l'exécute.
- L'absorption de la dette révélée, dans le champ `except:` des règles concernées.

## Hors périmètre

- **La mise en conformité des pages** — carte suivante, et l'ordre est délibéré : la gate doit
  mordre **avant** qu'on refasse la page. Sinon rien ne prouve qu'elle mordra, et on reproduit
  exactement le défaut qu'on corrige. La page rentrera dans le rang en faisant repasser la règle au
  vert : l'ordre **est** la preuve.
- **`table.vue`**, qui viole la même règle et aura sa carte.
- **Un plafond de lignes de template**, et **tout nouveau motif interdit** : la règle dit déjà la
  bonne chose, on lui rend seulement la vue.
- **Des README pointeurs** dans les dossiers des espaces : ils informent, ils ne bloquent rien.

## Décisions de cadrage

### On répare la vue, on ne renforce pas la règle

Une fois qu'elle lit le fichier entier, la règle attrape **cinq pages** avec ses trois motifs
actuels, dont les deux écrans de recherche visés. C'est suffisant pour ce qu'on cherche à empêcher,
et cela garde la carte à un correctif et ses tests.

Un plafond de lignes a été envisagé puis écarté : il aurait fait entrer un nombre arbitraire dans
une règle qui n'en avait pas besoin. Les listes en dur (`<ul>`, `<dl>`, `<table>`) ont été
envisagées aussi — elles porteraient la dette de 5 à 13 fichiers sur les quatre espaces, sans que
le besoin l'exige aujourd'hui.

### La dette est inscrite, datée et attribuée

Introduire la règle réparée rendrait la CI rouge sur des pages que cette carte ne traite pas. Le
champ `except:` existe déjà dans le format : la dette y entre, **chaque ligne nommant l'espace
responsable** et le bloc portant sa date d'ouverture.

Pas de numéro de carte pré-réservé : le compteur d'un espace se prend au moment du commit, et les
lanes avancent en parallèle — une référence réservée d'avance serait fausse avant d'être utile.

Conséquence voulue : toute page **nouvelle ou modifiée** est prise immédiatement, et la dette
diminue à chaque carte au lieu de rester invisible. Une exception sans propriétaire ni date est une
exception qui reste.

### `ux-kit.vue` est une dette, pas une exception permanente

La galerie de composants fait 245 lignes et gagne cinq violations de `app-no-hardcoded-text` une
fois la région réparée. L'argument « une galerie est une énumération » a été écarté : une page reste
une page, et le modèle vaut aussi pour celle qui sert de vitrine aux composants.

### Les appâts sont des littéraux, pas des fichiers — écart au cadrage

Le cadrage prévoyait des fichiers `.vue` sous `scripts/ci/fixtures/`. À l'écriture, sans objet :
`regionOf` prend une **chaîne**, un vrai fichier n'apporte rien de plus — et il se ferait linter et
formater, ce qui suppose de l'exempter dans deux configurations pour un gain nul. Les appâts vivent
donc dans le test, à côté de l'assertion qu'ils servent.

### La fonction sort du script — écart au cadrage

Le cadrage disait « l'extraction de région dans `verify-conventions.ts` ». Impossible en l'état : ce
script **s'exécute au niveau module** (il lit `rules.yaml` et appelle `process.exit`) et importe
`Glob` depuis `bun`. L'importer depuis un test lancerait la vérification entière, et `bun` ne résout
pas sous vitest.

`regionOf` part donc dans `scripts/ci/regions.ts`, pur et sans dépendance runtime — **même partage
que `checkers.ts`**, qui existait déjà pour la même raison. L'extraction a été faite d'abord *à
l'identique*, bug compris, et la gate vérifiée inchangée : c'est ce qui garantit que l'échec du test
qui a suivi venait bien du défaut, et pas du déplacement.

### Un second défaut trouvé en route : les lignes étaient décalées

L'ancien décalage valait `content.slice(0, index).split('\n').length`, soit le numéro de la ligne
d'ouverture. Combiné au `offset + index + 1` de l'appelant, il rapportait **chaque violation une
ligne trop bas**. Le compilateur donne `loc.start.line`, 1-indexée, d'où le `- 1`.

Ce n'était pas au périmètre et ça y entre : la carte exigeait déjà des numéros exacts, et un rapport
qui pointe la mauvaise ligne ne vaut guère mieux qu'un rapport absent.

## Règles et cas limites

- **Un fichier sans slot nommé** doit continuer d'être lu comme avant : la réparation ne change rien
  pour lui, et la fixture doit le vérifier.
- **`match.index === 0`** — le commentaire existant du vérificateur signale qu'un test de véracité
  manquerait le cas où le bloc ouvre le fichier. La réparation ne doit pas réintroduire ce défaut.
- **`region: script`** n'est pas touchée : un bloc `<script>` ne s'imbrique pas.
- **Le décalage de lignes** (`offset`) doit rester exact, sinon les violations sont rapportées à la
  mauvaise ligne — un rapport faux vaut à peine mieux qu'un rapport absent.
- Un template **sans** fermeture (fichier tronqué) ne doit pas faire planter la CI.

## Documentation impactée

**`webapp/README.md`**, section « Conventions vérifiées en CI » — c'est le README propriétaire du
dossier modifié, et il est faux sur un point de fait : il énumère **quatre** règles actives quand il
y en a huit. Il gagne aussi la mention de l'endroit où vivent désormais les preuves, puisque c'est
lui qui les exige.

Aucune page de `docs/showcase-recruiter-webapp-saas/` en revanche : les READMEs disaient déjà la règle, et la règle citait
déjà le bon README. C'est le vérificateur qui mentait, pas la documentation.

## Définition de terminé

- [x] L'extraction lit le template **entier**, de son ouverture à sa dernière fermeture.
- [x] Un fichier sans slot nommé est lu comme avant.
- [x] Les numéros de ligne rapportés sont exacts.
- [x] `regions.vitest.ts` exige qu'une violation posée **après** un slot nommé soit détectée — et ce
      test **échouait** sur le code d'avant.
- [x] Les appâts vivent hors du périmètre de toutes les règles (littéraux dans le test).
- [x] Cinq pages sortent rouges par `pages-no-interactive-template`, une par
      `app-no-hardcoded-text`.
- [x] Toutes sont dans `except:`, avec leur espace responsable et la date d'ouverture du bloc.
- [x] `webapp/README.md` annonce le bon nombre de règles et dit où vivent leurs preuves.
- [x] `bun run quality-check` est vert.

## Vérification

**Le test échoue sur le code d'avant** — c'est la preuve, et elle a été lue avant le correctif :

```
FAIL  regionOf — template > lit au-delà du premier </template> fermé par un slot nommé
FAIL  regionOf — template > rapporte la ligne réelle du fichier
      AssertionError: expected 5 to be 12
Tests  2 failed | 5 passed (7)
```

**Après correctif** : `7 passed (7)`.

**La gate mord** — la vérification est passée de « 8 règles, aucune violation » à :

```
✖ pages-no-interactive-template — 20 violation(s)
✖ app-no-hardcoded-text — 5 violation(s)
25 violation(s) sur 8 règles.
```

sur cinq pages : `ux-kit.vue`, `auth/define-password.vue`, `admin/referentiels.vue`, et les deux
écrans de recherche de l'entreprise. Les lignes rapportées — `index.vue:263` et `:488` — sont
exactement celles relevées à la main avant l'intervention.

**Après inscription de la dette** : `✔ conventions — 8 règles, aucune violation`.

**Chaîne complète** : lint 0 erreur, format vert, type-check vert sur les quatre espaces,
`8 fichiers de test, 62 tests` — contre 7 et 55 avant.

## Livré

- `webapp/scripts/ci/regions.ts` **(+)** — `regionOf`, pure et testable, sur l'AST du SFC.
- `webapp/scripts/ci/regions.vitest.ts` **(+)** — sept cas, dont deux qui échouaient.
- `webapp/scripts/ci/verify-conventions.ts` **(M)** — consomme le module au lieu de porter la
  fonction.
- `webapp/scripts/ci/rules.yaml` **(M)** — la dette, datée et attribuée, sur les deux règles.
- `webapp/README.md` **(M)** — huit règles au lieu de quatre, où vivent les preuves, et la
  convention de dette.

## Reste à faire (hors périmètre)

La dette compte **cinq fichiers**, dont trois n'appartiennent pas à cet espace :

| Fichier | Espace responsable |
| --- | --- |
| `recherches/*/index.vue` | entreprise — `ENT-028`, déjà cartée |
| `recherches/*/table.vue` | entreprise |
| `base/app/pages/ux-kit.vue` | socle-webapp (les deux règles) |
| `base/app/pages/auth/define-password.vue` | socle-webapp |
| `spaces/admin/app/pages/referentiels.vue` | admin |
