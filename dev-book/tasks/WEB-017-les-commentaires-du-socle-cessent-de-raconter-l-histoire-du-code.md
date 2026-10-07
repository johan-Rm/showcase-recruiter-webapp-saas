# TASK WEB-017 — socle : les commentaires cessent de raconter l'histoire du code

- **Créée le** : 2026-08-09.
- **Session** : `3d83b5c5-c632-417d-967f-1345935b8392`.
- **Statut** : ✅ fait le 2026-08-09 — plus un seul commentaire `.vue` du socle ne cite un numéro de
  tâche ni ne décrit un état révolu. **11 fichiers**, et le code est **strictement identique** une
  fois les commentaires retirés.
- **Type** : refactor de documentation dans le code. Aucune ligne exécutable n'est touchée.
- **Tests** : `sans objet` — aucun code exécutable produit ni modifié. La preuve est une comparaison
  mécanique : chaque fichier est comparé à sa version `HEAD` une fois les commentaires retirés.
- **Réf** : `ENT-074` — carte sœur qui porte le cadrage complet et le critère de tri · `ADM-019` —
  carte sœur, espace admin · `WEB-018` — **aval, à ouvrir après celle-ci** : le verrou de conventions
  qui empêche le récit de revenir.

## Contexte

Le cadrage vit dans `ENT-074` et n'est pas recopié : un commentaire décrit un **état présent** ; ce
qui n'a de sens que pour qui connaît le passé appartient au message de commit ou à la carte.

Le socle en portait la version la plus coûteuse, parce qu'il est **lu par les quatre espaces**. Deux
exemples de ce que ces phrases faisaient perdre :

```
// Le PLACEMENT est confié à la bibliothèque, et c'est tout l'objet de WEB-015. Écrit à la main, il
// était ancré au bord gauche de l'icône et poussait vers la droite […] : 19 bulles sur 27 sortaient
// de l'écran sur un téléphone.
```

Lu au passé, ce paragraphe se comprend comme « un vieux problème, réglé ». Il énonce en réalité une
**loi toujours vraie** : écrire un placement à la main fait sortir les bulles de l'écran. C'est
exactement l'information qu'on veut donner à qui serait tenté de retirer `UPopover` — et le passé la
neutralisait.

## Périmètre

- **Les 11 fichiers `.vue` de `base/`** portant un renvoi de tâche (préfixé ou en numéros nus) ou une
  phrase au passé décrivant un état révolu.
- **Réécriture au présent, jamais suppression sèche.**
- **Aucune autre ligne**, de commentaire comme de code.

## Hors périmètre

- **Les fichiers `.ts` du socle** — même défaut, écarté au cadrage commun ; ils restent non couverts
  par le verrou aval.
- **Les espaces entreprise et admin** — `ENT-074` et `ADM-019`.
- **Le verrou de conventions** — `WEB-018`, cette lane, commit suivant.
- **`base/app/components/ui/` en tant que kit** : la carte ne touche à aucun contrat de composant.

## Décisions de cadrage

### Une loi énoncée au passé cesse d'être une loi

C'est la raison pour laquelle cette carte existe séparément de la simple hygiène. Dans le socle, la
plupart des phrases au passé décrivent un **piège de plateforme** qui n'a pas bougé : le
comportement d'`overflow-x` en CSS, le « Create "c" » d'`UInputMenu` sous le seuil, la frise qui
s'affiche vide avant l'hydratation.

Écrites au passé, elles se lisent comme des anecdotes. Réécrites au présent, elles redeviennent ce
qu'elles sont : **la raison pour laquelle il ne faut pas défaire ce code**.

**Aujourd'hui** — « Sans cette classe, la frise s'affichait vide puis se complétait d'un coup. »
**Demain** — « Sans cette classe, la frise s'affiche vide puis se complète d'un coup. »

Un caractère de différence, et la phrase passe de souvenir à avertissement.

### Ce qui reste, et qui ressemble à du passé

Quatre occurrences sont **relues et conservées** :

- deux passifs au présent de l'action — « la recherche **a été faite** et n'a rien ramené », « une
  suggestion **a été choisie** » : ce sont des états, pas des chronologies ;
- une description d'état à l'exécution — « le bouton qui **portait** le focus vient de disparaître » :
  elle parle de l'instant du clic, pas de l'histoire du fichier ;
- une consigne conditionnelle — « s'il **devait** un jour servir seul, lui en donner un » : c'est une
  instruction pour un cas de figure, la seule forme de futur qui ait sa place dans un commentaire,
  parce qu'elle ne promet rien.

## Règles et cas limites

- **Aucun commentaire n'est supprimé pour cause de longueur.**
- **Le rendu ne change pas**, et c'est vérifié mécaniquement.
- **Un renvoi à une carte du socle est un renvoi comme un autre** : `WEB-015` disparaît de
  `InfoHint.vue` au même titre que les autres. Le fichier n'a pas à savoir par quelle tâche il est
  arrivé.

## Documentation impactée

Aucune : la carte applique une règle qui existe déjà dans le socle global. **Son écriture dans
`docs/` est l'objet de `WEB-018`**, avec le verrou qui la fait respecter — une règle et son
outillage vont dans le même commit, séparés ils divergent.

## Définition de terminé

- [x] Aucun commentaire `.vue` du socle ne cite un numéro de tâche, dans l'une ou l'autre
      numérotation.
- [x] Aucun ne décrit un état révolu.
- [x] Aucun `TODO`, `FIXME`, `HACK` ni `XXX` — il n'y en avait aucun.
- [x] **Les lois de plateforme sont énoncées au présent**, donc lisibles comme des avertissements.
- [x] **Le code est identique** : 11/11 fichiers rendent le même contenu une fois les commentaires
      retirés.
- [x] Gates, en **une passe** à l'ouverture de la PR : **vitest 327/327**, type-check vert sur les
      quatre espaces, prettier vert, eslint **0 erreur** (20 avertissements pré-existants,
      identiques à `develop`). Les conventions sortent **rouge**, sur 58 violations qui ne viennent
      **pas** de cette carte : elles portent sur `spaces/entreprise` et `spaces/admin`, absents de
      son diff, et c'est le verrou de `WEB-018` — le commit suivant de cette branche — qui les
      rend. Cf. son § *Reste à faire* pour l'ordre des PR.

## Vérification

### Le code n'a pas bougé

```
✔ 11/11 fichiers : code identique commentaires retirés
```

### Les motifs ne rendent plus rien

```
=== renvois de tâche (deux numérotations) + marqueurs révolus ===
(aucun)
```

Le balayage sur les temps du passé rend **quatre lignes**, toutes relues et conservées à dessein
(cf. *Décisions de cadrage*).

### Le volume

20 lignes posées pour 20 retirées, sur 11 fichiers. Solde nul : **on a réécrit, pas coupé**.

## Livré

- `webapp/base/app/components/ui/InfoHint.vue` (M) — le piège de placement redevient une loi au
  présent, avec ses deux mécanismes (`collisionPadding` et le rendu hors flux).
- `webapp/base/app/components/BootTrail.vue` (M) — pourquoi `motion-reduce:after:w-full` existe :
  sans elle, la frise saute à l'hydratation.
- `webapp/base/app/components/ui/RemoteCombobox.vue` (M) — le « Create "c" » d'`UInputMenu` est un
  comportement actuel de la bibliothèque, pas un souvenir.
- `webapp/base/app/components/ui/TagPicker.vue` (M) — le panneau **se fait** couper à hauteur fixe ;
  mesurer contre la fenêtre seule **serait** faux.
- `webapp/base/app/components/ui/Badge.vue` (M), `Avatar.vue` (M),
  `components/layout/Logomark.vue` (M), `components/auth/Showcase.vue` (M), `pages/login.vue` (M) —
  conditionnels et présents rétablis.
- `webapp/base/app/app.vue` (M), `components/AppBootShell.vue` (M) — renvois à l'ancienne
  numérotation (`carte 055`) retirés.

## Reste à faire (hors périmètre)

- **Les fichiers `.ts` du socle**, porteurs du même défaut : écartés au cadrage commun. Le verrou de
  `WEB-018` ne les couvrira donc pas.
- **`WEB-018`**, commit suivant sur cette lane : le verrou de conventions et la règle écrite. Il ne
  peut passer au vert qu'une fois `ENT-074` et `ADM-019` mergées — le script scanne l'arbre entier,
  pas le diff de la branche.
