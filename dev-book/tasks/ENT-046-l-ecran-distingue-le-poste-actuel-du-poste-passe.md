# TASK ENT-046 — entreprise : l'écran distingue le poste actuel du poste passé

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — le panneau de la Visionneuse et la Table disent « poste
  actuel » ou « poste passé », et se taisent quand la donnée se tait. 9 tests unitaires ajoutés.
- **Type** : Frontend entreprise — mention ajoutée sur deux surfaces, appuyée sur un service pur.
- **Tests** : unitaires — `criteria.vitest.ts` sur la confrontation qui alimente le panneau (actuel,
  passé, mention absente, critère non satisfait) et `candidate.vitest.ts` sur la lecture de ligne qui
  alimente la Table (cinq cas). Le rendu lui-même n'est pas couvert : le webapp n'a aucun harnais de
  test de composant, et la preuve attendue est une **capture** des deux écrans.
- **Réf** : **`API-026` — amont BLOQUANT** (c'est elle qui produit la mention) ·
  `webapp/services/search/criteria.ts` (`CriterionOutcome`) ·
  `webapp/spaces/entreprise/app/components/search/MatchList.vue` ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue`

## Contexte

Un recruteur cherche « Développeur logiciel » et parcourt ses résultats. Le tableau lui donne des
lignes, la Visionneuse lui donne un profil à la fois avec, à droite, le panneau « Ce profil face aux
critères ». Ce panneau écrit aujourd'hui :

```
✓  Métier : Développeur logiciel        Développeur logiciel
```

**Il ne dit pas quand.** Le critère repose sur un cumul de carrière : la personne a exercé ce métier,
peut-être aujourd'hui, peut-être il y a huit ans. Sur les 293 profils qui ont exercé ce métier dans
le vivier de développement, **90 le font encore et 92 font autre chose** — deux populations que le
recruteur trie différemment, et que l'écran présente à l'identique.

Restent **111 profils dont on ne sait rien** : c'est pour eux que cette carte affiche une mention
plutôt que d'ouvrir un filtre, et qu'elle se tait plutôt que de deviner.

## Périmètre

- **La confrontation `CriterionOutcome`** (`webapp/services/search/criteria.ts`) transporte la
  mention servie par l'API jusqu'aux composants.
- **`MatchList.vue`** — la ligne du métier, dans le panneau de la Visionneuse.
- **La Table** (`recherches/[uuid]/table.vue`), cellule « Poste », pour que le tri se fasse à la
  volée sans ouvrir chaque profil — **et non `ProfileCard.vue`**, cf. *Écart au cadrage*.
- **`jobTenureOf`** dans `services/search/candidate.ts` : la Table n'a pas de critère sous la main,
  elle a une ligne et cherche dans ce qu'elle porte.
- **Deux clés de traduction** dans les trois fichiers de langue (`fr.yaml`, `en.yaml`, `ar.yaml`).

## Hors périmètre

- **Toute possibilité de filtrer là-dessus** : mesuré et écarté dans `API-026`, la donnée est trop
  lacunaire pour un filtre dur — même verdict que la ville, à 71 % de couverture, dans
  `inventaire-criteres-3-couches.md`.
- **Trier les résultats par « actuel d'abord »** : ce serait remettre un filtre mou à la place d'un
  filtre dur, avec les mêmes 111 profils relégués sans qu'on l'ait dit.
- **Afficher l'employeur actuel ou l'intitulé brut du poste du moment.** Ils existent dans le contrat
  (`currentTitle`, `currentCompanyName`), mais ils répondent à « quel est son métier ? » quand cette
  carte répond à « ce métier-là, il le fait encore ? ».
- **Les autres critères** — une langue ou un pays n'ont pas de « quand ».

## Décisions de cadrage

### Deux mentions, et le silence est la troisième

`API-026` sert « actuel » ou « passé », et **omet** le champ quand le poste du moment est inconnu ou
ambigu. L'écran suit à la lettre : pas de mention, pas de tiret, pas de « non renseigné ».

C'est délibéré et ça se défend ligne par ligne : une mention « inconnu » apparaîtrait sur 38 % des
lignes pour dire qu'on ne sait pas, ce qui charge l'écran d'une information sans usage. Le recruteur
qui ne voit rien sait qu'il n'a rien à en tirer, et regarde le profil.

### Une mention, pas un badge de plus

La ligne du panneau porte déjà quatre éléments — le signe, le libellé du critère, ce qui a été
demandé, ce que le profil porte en face. Un cinquième élément visuel la casserait ; la mention se
range **contre** ce que le profil porte, dont elle est la précision temporelle, pas un attribut
indépendant.

Dans la Table, même règle : la mention entre **dans** la cellule « Poste », en texte discret, comme
le badge « Rencontré » y est entré plutôt qu'en onzième colonne. Elle ne devient pas une pastille —
la charte ne laisse l'accent qu'à « Rencontré », qui constate un entretien.

### Le service porte la règle, le composant affiche

`criteria.ts` fabrique déjà `CriterionOutcome` à partir de ce que le serveur envoie, et il est couvert
par un `*.vitest.ts`. La mention y transite comme le reste — sans quoi deux composants liraient la
réponse brute chacun de son côté et finiraient par diverger sur le cas absent.

## Règles et cas limites

- **La mention n'apparaît jamais sur un critère non satisfait** : un métier qu'on n'exerce pas n'a ni
  présent ni passé.
- **Elle est lisible par les lecteurs d'écran** (les logiciels qui lisent la page à voix haute) : le
  panneau distingue déjà ce qui est décoratif de ce qui porte le sens, et une mention temporelle
  porte le sens.
- **La couleur ne dit rien toute seule.** « Actuel » ne peut pas être un simple point vert : le
  panneau pose déjà la règle — « une couleur et une icône ne disent rien à qui ne les distingue pas ».
- **Aucun texte en dur** : clés i18n dans les trois langues.
- **La ligne du panneau tronque déjà** ce que le profil porte, sur une colonne étroite. La mention ne
  doit pas repousser ce libellé hors de vue : c'est lui l'information principale.
- **La cellule de la Table est bornée** pour que la troncature morde : la mention y est `flex-none`,
  c'est l'accroche du profil qui raccourcit, jamais elle.
- **Un profil sans réplique Jarvi** n'a pas de mention, et ce n'est pas une erreur à signaler.

## Documentation impactée

- **Le commentaire de `MatchList.vue`**, qui énumère ce que porte une ligne.
- **Vérifié, non impacté** : `arborescence-entreprise.md` (elle ne décrit ni le panneau ni les
  colonnes de la Table), `i18n.md` (deux clés s'ajoutent, aucune règle ne change),
  `services-standard.md`, `composables-standard.md`.

## Définition de terminé

- [x] Un profil qui exerce **encore** le métier cherché porte la mention, dans la Table **et** dans
      le panneau de la Visionneuse — **captures à l'appui**.
- [x] Un profil passé à un autre métier porte l'autre mention.
- [x] Un profil au poste actuel inconnu ou ambigu n'affiche **rien** de plus qu'aujourd'hui.
- [x] Un critère métier non satisfait n'affiche aucune mention.
- [x] Le libellé du métier reconnu reste lisible : la mention ne le pousse pas hors du cadre.
- [x] La confrontation est couverte sur les quatre cas dans `criteria.vitest.ts`, et la lecture de
      ligne sur cinq cas dans `candidate.vitest.ts`.
- [x] Les deux clés existent dans les trois langues.

## Vérification

**Constaté dans le navigateur**, sur la stack de ce worktree, avec un compte recruteur et le vivier
réel — l'API y sert `API-026`, fraîchement mergée.

**Le panneau de la Visionneuse**, trois profils, trois comportements :

| Ce que le serveur dit | Ce que le panneau affiche |
| --- | --- |
| `CURRENT` | ✓ Poste **Business Developer** poste actuel |
| `PAST` | ✓ Poste **Technicien de mainten…** · poste passé |
| rien | ✓ Poste **Chef de projet** Trouvé |

Le troisième est le plus important : quand le serveur se tait, l'écran retombe sur le verdict
ordinaire et n'affirme rien. Le second se lit juste — l'accroche du profil dit « Responsable de Lots
Travaux chez SNCF Réseau » : il a bien été technicien de maintenance, il ne l'est plus.

**La Table**, douze premières lignes d'une recherche « Business Developer » :

```
ligne 1  poste actuel      ligne 8  poste passé
ligne 2  poste actuel      ligne 9  poste passé
lignes 3-7, 10-12 : rien
```

Quatre lignes sur douze parlent, huit se taisent — la proportion attendue, le poste du moment
n'étant résolu que sur la moitié du vivier. La mention se pose en fin de cellule « Poste », en texte
discret, sans perturber les neuf autres colonnes.

**111 tests vitest verts** sur `services/search`, dont les 9 ajoutés.

**Aucune page de documentation ne devient fausse** — vérifié : `arborescence-entreprise.md` ne décrit
ni le panneau ni les colonnes de la Table.

## Livré

- `webapp/services/search/criteria.ts` **(M)** — `CriterionOutcome` porte `tenure` ; un critère non
  satisfait n'en a jamais.
- `webapp/services/search/candidate.ts` **(M)** — `jobTenureOf`, pour la Table qui lit une ligne et
  non des critères.
- `webapp/services/search/criteria.vitest.ts` **(M)** et `candidate.vitest.ts` **(M)** — 4 + 5 cas.
- `webapp/spaces/entreprise/app/components/search/MatchList.vue` **(M)** — la mention, et le verdict
  qui s'efface devant elle.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — la mention en fin de
  cellule « Poste ».
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — `viewer.panel.tenure.CURRENT` et `.PAST`.

## Écart au cadrage

### « Les deux surfaces » n'étaient pas celles que la carte nommait

La carte demandait le panneau **et `ProfileCard.vue`**. En allant l'écrire, deux choses ne collaient
pas, et la décision est remontée à l'utilisateur plutôt que d'être prise ici :

- **`ProfileCard` n'est pas la ligne de résultat** : c'est le grand volet de la Visionneuse, à
  quelques centimètres du panneau. Y remettre la mention aurait redit la même chose sur le même
  écran.
- **La surface où l'on trie vraiment est la Table**, que la carte n'avait jamais nommée. Sa colonne
  « Poste » affiche l'accroche du profil, pas le métier reconnu, et son propre commentaire dit que
  dix colonnes s'y disputent la largeur — c'est pourquoi le badge « Rencontré » y a été glissé
  **dans** la cellule plutôt qu'en onzième colonne. La mention suit le même chemin, en texte discret
  et non en pastille : la charte ne laisse l'accent qu'à « Rencontré ».

Arbitrage tranché par l'utilisateur : **panneau + Table**.

### Le verdict s'efface devant le « quand »

Première version : « Trouvé · poste actuel ». Constaté à l'écran, ça mangeait le libellé du métier
dans une colonne de deux cent quatre-vingts pixels — « Business Dev… » — pour redire ce que la
mention disait déjà : un poste qu'on qualifie d'actuel est nécessairement trouvé.

Le verdict ne s'affiche donc plus quand une mention le remplace, et le libellé retrouve sa place.
C'est la règle de la carte (« le message remplace, il ne s'ajoute pas ») appliquée un cran plus loin
que là où elle était écrite.
