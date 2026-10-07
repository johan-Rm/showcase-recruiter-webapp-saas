# TASK ENT-074 — entreprise : les commentaires cessent de raconter l'histoire du code

- **Créée le** : 2026-08-09.
- **Session** : `3d83b5c5-c632-417d-967f-1345935b8392`.
- **Statut** : ✅ fait le 2026-08-09 — plus un seul commentaire de l'espace entreprise ne cite un
  numéro de tâche ni ne décrit un état révolu. **17 fichiers, 138 lignes retirées pour 119 posées**,
  et **le code est strictement identique** : les 17 fichiers, débarrassés de leurs commentaires,
  rendent octet pour octet le même contenu qu'avant.
- **Type** : refactor de documentation dans le code. Aucune ligne exécutable n'est touchée.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable et n'en modifie aucun. La
  preuve est une **comparaison mécanique** : chaque fichier est comparé à sa version `HEAD` une fois
  les commentaires retirés, et les deux doivent être identiques. Un écart, si petit soit-il,
  signifierait qu'une ligne de code est partie avec un commentaire.
- **Réf** : `ADM-019` et `WEB-017` — **mêmes lanes sœurs, même geste**, sur l'espace admin et sur le
  socle · `WEB-018` — **aval** : le verrou de conventions qui empêche le récit de revenir, et qui ne
  peut passer au vert qu'une fois ces trois cartes mergées ·
  [CLAUDE.md](../../CLAUDE.md) global, § *Style de commentaires*.

## Contexte

Les commentaires de cet espace sont bons : ils disent **pourquoi**, presque jamais **quoi**, et
plusieurs empêchent une régression réelle — la raison d'être d'un `<div>` d'apparence inutile, le
seuil arbitraire qui définit « vu », la classe qu'on ne doit pas remettre.

Mais une partie d'entre eux a cessé de décrire le code pour **raconter son histoire** :

```
// ELLE NE RETOUCHE RIEN (`ENT-063`). Le ✕ qui retirait un critère d'un clic a vécu ici de `ENT-047`
// à `ENT-063` : […] Le ✕ vient donc d'`ENT-041`, où il était « le seul endroit du produit où l'on
// retire un critère d'un clic » — il n'est pas perdu, il a déménagé là où on lit ce qu'on défait.
```

Trois défauts, dans cet ordre de gravité :

1. **C'est invérifiable.** Aucune gate ne dit qu'une de ces phrases est devenue fausse, et personne
   ne les corrigera : on corrige le code, pas le récit du code. Une carte de tâche, elle, est datée
   et close — elle ne peut pas mentir sur le présent, parce qu'elle ne parle pas du présent.
2. **C'est déjà écrit ailleurs, deux fois.** `git log` porte la chronologie, `dev-book/tasks/` porte
   la décision et son motif. Le fichier est le seul des trois qui ne soit pas fait pour ça.
3. **Ça ne redescend jamais.** Chaque tâche ajoute un paragraphe, aucune n'en retire :
   `search/Progress.vue` en était à **16 lignes de commentaire pour 20 lignes de code**, sur une
   barre de deux pixels de haut.

Le socle global tranche déjà : *« commentaires sobres, pourquoi non-évident seulement, jamais narrer
un changement ni reformuler le code (git trace l'historique) »*.

## Périmètre

- **Les 17 fichiers `.vue` de `spaces/entreprise/`** qui portent un renvoi de tâche (`ENT-0xx`) ou
  une phrase au passé décrivant un état révolu.
- **Réécriture au présent, jamais suppression sèche** — cf. *Décisions de cadrage*.
- **Aucune autre ligne**, de commentaire comme de code.

## Hors périmètre

- **Les fichiers `.ts`** — composables, stores, services : **69 lignes dans 29 fichiers** portent la
  même maladie. Écartés par décision explicite au cadrage, pour tenir un diff lisible. Conséquence
  assumée : le verrou aval (`WEB-018`) ne pourra porter que sur `**/*.vue`, et les `.ts` resteront
  non couverts. **Nommé ici, non traité.**
- **Les espaces admin et socle** — `ADM-019` et `WEB-017`, sur leurs lanes, avec leurs commits.
- **Le verrou de conventions** — `WEB-018`, lane socle.
- **Le volume des commentaires en général.** Cette carte retire le récit ; elle ne raccourcit pas ce
  qui décrit correctement le présent, même long.

## Décisions de cadrage

### Réécrire au présent, et surtout pas couper

C'est la seule décision qui compte, et couper aurait été le piège. Une partie de ces phrases au passé
cache une **contrainte réelle** dont le passé n'était que la formulation maladroite :

```
<!-- ELLE PREND LE RESTE ET ELLE DÉFILE (`ENT-047`). Avant, elle poussait : `flex-none` sans
     hauteur maximale, elle chassait les favoris hors de la colonne dès six critères. -->
```

Supprimée, cette phrase emporte avec elle « ne remets pas `flex-none` ici ». C'est une barrière dans
un champ : on ne la retire pas sans savoir pourquoi elle a été posée. Réécrite, elle garde la
contrainte et jette le récit :

```
<!-- ELLE PREND LE RESTE ET ELLE DÉFILE. Trois blocs se disputent la hauteur de la colonne et
     c'est celui-ci qui absorbe : `min-h-0` avec `flex-1`, jamais `flex-none` sans hauteur
     maximale — sous cette forme elle chasse les autres blocs hors de la colonne dès six
     critères. -->
```

À l'inverse, la généalogie d'une croix de suppression entre deux numéros de tâche ne laisse **aucune**
contrainte derrière elle : celle-là se coupe net.

**Conséquence pratique : une passe de jugement, ligne à ligne, jamais un `sed`.** C'est ce qui évite
de rouvrir un défaut déjà payé une fois.

### Le critère de tri, en une phrase

**Un commentaire décrit un état présent.** Si une phrase n'a de sens que pour qui connaît le passé,
elle appartient au message de commit ou à la carte. « La console est un outil de consultation, on n'y
retouche pas la recherche » est du présent et se garde ; « le ✕ y a vécu d'`ENT-047` à `ENT-063` » ne
se garde pas.

Corollaire sur les numéros de tâche : ils partent **tous**, y compris ceux qui semblaient de simples
renvois. Un `(task ENT-064)` en fin de phrase n'apporte rien à qui lit le fichier — il coûte un
aller-retour vers `dev-book/` pour retrouver, au mieux, la phrase qu'on vient de lire.

### Le détecteur de départ était trop étroit — constat d'implémentation, deux fois

Le cadrage listait 17 fichiers sur les motifs francs (« désormais », « n'est plus », un numéro de
tâche préfixé). **Deux balayages successifs l'ont pris en défaut, et chacun a changé la forme du
verrou aval.**

**Les temps du passé.** Un second balayage sur l'imparfait et le passé composé a sorti **quatre
fichiers de plus**, dont un que le premier n'avait aucune chance de voir :

```
<!-- OÙ L'ON EN EST […]. Tous deux vivaient dans la colonne de gauche, masquée sous 1024 px :
     sur téléphone, l'avancement n'existait nulle part. -->
```

Aucun mot-clé, aucun numéro : juste trois verbes à l'imparfait. Un détecteur de vocabulaire seul
serait passé à côté ; il lui faut aussi les temps.

**L'ancienne numérotation, sans préfixe.** Découverte en balayant la lane admin (`ADM-019`), elle
touche aussi cet espace — `FiltersPrimary.vue` portait un `(6 valeurs, task 112)`. Les cartes 001 à
164 sont antérieures au découpage en espaces : elles s'écrivent en numéros nus, et chercher
`ENT-\d{3}` ne les voit pas.

**Le milieu des commentaires multi-lignes.** La plus grosse fuite, et la seule qui condamnait la
méthode : **tous mes balayages étaient ancrés en début de ligne** (`^\s*(//|\*|<!--)`), donc aveugles
à la troisième ligne d'un commentaire HTML de six. Sept renvois y dormaient — dont un
`` TROIS COLONNES ÉGALES (`ENT-058`), là où `ENT-047` avait mesuré l'inverse `` qui portait à lui
seul deux numéros et un imparfait.

Ils ne sont apparus qu'en écrivant le **détecteur du verrou** (`WEB-018`), qui suit l'ouverture et la
fermeture des blocs au lieu de juger ligne par ligne. **C'est l'outil qui a trouvé ce que la
relecture avait manqué**, et c'est la justification la plus solide de la carte aval : trois lanes
avaient été déclarées propres par un `grep`, aucune ne l'était.

Les neuf fichiers concernés ont été repris et **les deux commits corrigés avant toute PR**, plutôt
que de laisser les cartes affirmer un « zéro renvoi » qui aurait été faux.

## Règles et cas limites

- **Le passé n'est pas toujours du récit.** « Ce qui a été demandé » (les critères de la recherche),
  « laisserait croire qu'une valeur a été retenue » : ce sont des passifs au présent de l'action.
  Quatre occurrences ont été **relues et conservées** à ce titre.
- **« n'est plus » n'est pas toujours du récit** non plus : « une grille dont les cases apparaissent
  et disparaissent change de largeur — ce n'est plus une grille » est une conséquence logique, pas
  une chronologie. Conservée.
- **Aucun commentaire n'est supprimé pour cause de longueur.** Ce qui décrit correctement le présent
  reste, même sur quinze lignes.
- **Le rendu ne change pas** — c'est vérifié mécaniquement, pas supposé.

## Documentation impactée

Aucune. La carte ne change ni un comportement, ni un contrat, ni une convention écrite : elle
**applique** une règle qui existe déjà dans le socle global. La règle elle-même sera outillée par
`WEB-018`, sur la lane socle.

## Définition de terminé

- [x] Aucun commentaire `.vue` de l'espace ne cite un numéro de tâche — **ni la numérotation
      préfixée (`ENT-0xx`), ni l'ancienne, en numéros nus (`task 112`)**.
- [x] Aucun ne décrit un état révolu — vérifié sur les marqueurs francs **et** sur les temps du
      passé, les seconds ayant seuls attrapé quatre cas.
- [x] Aucun `TODO`, `FIXME`, `HACK` ni `XXX` — il n'y en avait aucun, et il n'y en a toujours aucun.
- [x] **Les contraintes portées par les phrases au passé sont conservées**, reformulées au présent.
- [x] **Le code est identique** : 17/17 fichiers rendent le même contenu une fois les commentaires
      retirés.
- [x] Gates, en **une passe** à l'ouverture de la PR : **vitest 308/308**, type-check vert sur les
      quatre espaces, prettier vert, **conventions — 18 règles, aucune violation**, eslint **0
      erreur**. Les 20 avertissements d'eslint sont **pré-existants et inchangés** : `develop` en
      rend exactement 20, et le diff de cette carte ne touche aucune ligne de code.

## Vérification

### Le code n'a pas bougé — la preuve, pas la promesse

Un script retire de chaque fichier les commentaires JS (`//`, `/* */`) et HTML (`<!-- -->`),
normalise les blancs, et compare la version `HEAD` à celle du worktree :

```
✔ 17/17 fichiers : code strictement identique une fois les commentaires retirés
```

C'est le point où cette carte pouvait casser en silence : une ligne de code emportée au milieu d'un
bloc de commentaire ne se voit pas à la relecture d'un diff de 220 lignes.

### Les motifs ne rendent plus rien

```
=== A. refs de tâche + marqueurs francs ===
(aucun)
=== C. TODO ===
(aucun)
```

Vérifié par le **détecteur du verrou** (`WEB-018`) et non par un `grep` : il suit l'ouverture et la
fermeture des blocs de commentaire, là où un motif ancré en début de ligne est aveugle au milieu d'un
commentaire de six lignes. C'est lui qui a sorti les sept derniers renvois.

Le balayage sur les temps du passé rend **quatre lignes**, toutes relues une à une et **conservées à
dessein** : trois passifs au présent de l'action (`MatchList.vue:2`, `account/CompanyForm.vue:60`,
`Card.vue:380`) et un faux positif du motif sur le mot « portée » (`ProgressHeader.vue:27`).

### Le volume

138 lignes retirées, 119 posées, sur 17 fichiers. Le solde est faible et c'est normal : **on a
réécrit, pas coupé**. Deux fichiers concentrent la baisse réelle — `ProgressHeader.vue` (28 → 19
lignes d'en-tête) et `Progress.vue` (16 → 13).

## Livré

- `webapp/spaces/entreprise/app/components/search/MatchList.vue` (M) — la généalogie de la croix de
  retrait part ; la règle « la console consulte, elle ne retouche pas » et la contrainte `min-h-0`
  restent, au présent.
- `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` (M) — l'en-tête passe de six
  paragraphes à quatre : ce qui reste dit ce qui **n'est pas** dans ce bloc et pourquoi, sans
  raconter par quelle tâche ç'a été sorti.
- `webapp/spaces/entreprise/app/components/search/Progress.vue` (M),
  `Position.vue` (M), `OrderPicker.vue` (M), `Console.vue` (M), `ControlPanel.vue` (M) — même geste,
  chacun gardant sa contrainte de placement énoncée au présent.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` (M), `Card.vue` (M),
  `Sentence.vue` (M), `FiltersPrimary.vue` (M) — renvois de tâche retirés, conditionnels rétablis
  (« il révélerait » et non « il révélait »). `FiltersPrimary.vue` en portait **deux** : un préfixé
  et un à l'ancienne numérotation, le second trouvé après coup.
- `webapp/spaces/entreprise/app/components/ActionBar.vue` (M), `AccountMenu.vue` (M),
  `CreditBalance.vue` (M) — idem.
- `webapp/spaces/entreprise/app/components/registration/CompanyForm.vue` (M) — « n'est plus
  désactivé » devient « n'est JAMAIS désactivé », qui est la règle et non son historique.
- `webapp/spaces/entreprise/app/pages/recherches/index.vue` (M),
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` (M) — la seconde portait le cas
  que le détecteur de départ avait manqué.

## Reste à faire (hors périmètre)

- **Les 29 fichiers `.ts` de l'espace et du socle**, 69 lignes au total : même défaut, écarté au
  cadrage. Tant qu'ils ne sont pas traités, le verrou aval reste borné aux `.vue`.
- **`WEB-018`**, le verrou de conventions — il ne peut passer au vert qu'une fois cette carte,
  `ADM-019` et `WEB-017` mergées : le script scanne l'arbre entier, pas le diff de la branche.
- **Relevé au passage, aucun rapport avec cette carte** : l'analyseur de l'éditeur signale sur
  `search/Progress.vue` et `registration/CompanyForm.vue` qu'un `role="progressbar"` gagnerait à
  être un `<progress>`. Défaut d'accessibilité pré-existant, non traité ici.
