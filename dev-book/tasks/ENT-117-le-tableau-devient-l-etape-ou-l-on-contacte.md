# TASK ENT-117 — entreprise : le tableau devient l'étape où l'on contacte

- **Créée le** : 2026-08-13.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-14 — le tableau s'ouvre sur les retenus, se nomme « Retenus », et
  remonte à la Visionneuse par un fil. Une adresse écrite avant l'inversion reste juste.
- **Type** : feat — le tableau s'ouvre sur les profils retenus, se nomme comme tel, et se raccroche
  à la Visionneuse par un fil plutôt que par un bouton.
- **Tests** : `webapp/services/search/views.vitest.ts`, qui couvre déjà les vues — le défaut
  inversé, la correspondance des noms de vue dans l'adresse, l'adresse tronquée, le paramètre répété,
  le paramètre inconnu. Plus le test de parité des trois langues, qui existe. Aucun test de rendu :
  le fil, la pastille et l'état vide se prouvent à l'écran.
- **Réf** : `ENT-115` — les colonnes du tableau, dont la colonne « Retenir » que l'onglet « Tous »
  de cette carte **oblige** à garder · `ENT-116` — la barre de sélection, qui édite la
  **même** gouttière et doit passer avant ou après, jamais en même temps · `ENT-050` — l'arbitrage
  « retenus » plutôt que « favoris », qui laissait la Table pour plus tard ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) —
  la carte de navigation canonique, qui définit déjà cet écran ·
  [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) ·
  [application_shell.md](../cadrage/entreprise/ui/application_shell.md) §3 et §4 ·
  [services/README.md](../../webapp/services/README.md)

## Contexte

La carte de navigation canonique de l'espace entreprise définit `/recherches/:uuid/table` en une
ligne : « **LA TABLE — les favoris retenus, en lignes · dévoiler les coordonnées · exporter** »,
placée **sous** la Visionneuse, elle-même décrite comme l'écran où l'on « affine, et retient ses
favoris ».

L'écran, lui, s'ouvre sur **tous les profils de la recherche**. Cent quarante-neuf lignes là où la
navigation en annonce douze, avec un onglet « Favoris » relégué en second et un message de guidage
qui dit « Tous les profils de la recherche courante ». Le titre de la page, pourtant, dit déjà « Ma
sélection », et la Visionneuse pointe déjà vers le tableau filtré sur les retenus : la moitié du
chemin est faite, l'écran d'arrivée l'ignore.

Cet écart n'est pas cosmétique. Le parcours du produit s'annonce en trois mots au démarrage —
**Rechercher → Sélectionner → Contacter**. Un écran de contact qui s'ouvre sur le vivier entier de la
recherche invite à dépenser des crédits sur des profils que personne n'a regardés, ce que tout le
reste du cadrage s'emploie à empêcher.

Reste la bascule entre les deux écrans, qui va aujourd'hui à l'envers : le tableau porte un bouton
« Ouvrir la visionneuse », alors que c'est lui qui vient après.

## Périmètre

- **L'onglet des retenus devient le jeu par défaut** du tableau, et « Tous » devient la vue
  secondaire, toujours accessible en un clic.
- **L'adresse s'inverse avec le défaut** : la vue par défaut ne s'écrit pas, donc le nom de la vue
  des retenus disparaît de l'adresse et celui de « Tous » y apparaît. Les adresses déjà partagées
  qui portent l'ancien nom retombent sur une vue lisible, elles ne produisent pas d'écran vide.
- **« Favoris » devient « Retenus »** sur cet écran, dans les trois langues.
- **Un fil d'Ariane prend la gauche de la gouttière** — le nom de la recherche, puis « Ma
  sélection » —, dont le premier maillon ramène à la Visionneuse.
- **Le message de guidage passe en pastille à droite** de la gouttière, comme sur l'écran de
  création de recherche, et son texte cesse d'annoncer « tous les profils ».
- **« Ouvrir la visionneuse » est retiré** : aucun bouton de sortie, c'est le fil qui fait remonter.
- **Un état vide quand rien n'est retenu** : ce qui manque, et le retour vers la Visionneuse.

## Hors périmètre

- **La Visionneuse elle-même**, qui pointe déjà correctement vers cet écran — rien à y changer.
- **Le renommage de la route.** L'adresse reste `/recherches/:uuid/table` : la renommer casserait
  les liens partagés pour un gain de vocabulaire.
- **La pagination du tableau**, restée ouverte dans le cadrage — voir l'hypothèse assumée ci-dessous.
- **Les autres onglets d'état** du cadrage d'origine (Nouveaux, Au Maroc, Disponibles, Non dévoilés,
  Dévoilés), déjà retirés et devenus des badges sur le profil.
- **Les colonnes** et **la barre de sélection** : `ENT-115` et `ENT-116`.

## Décisions de cadrage

- **Les retenus par défaut, mais « Tous » reste.** L'entonnoir strict — le tableau ne montrant que
  ce que la Visionneuse a retenu, sans échappatoire — était tentant : il rendrait impossible de
  dépenser un crédit sur un profil jamais ouvert. Il a été écarté parce qu'un recruteur pressé qui
  repère un nom au vol devrait alors repasser par la pile entière pour le joindre. L'entonnoir guide,
  il n'enferme pas. C'est d'ailleurs déjà la justification écrite de l'onglet « Tous » : retenir
  n'engage à rien, il faut pouvoir se rattraper.

  **Cette décision en commande une autre**, et c'est elle qui a fait garder la colonne « Retenir »
  en `ENT-115` : un onglet de rattrapage sans geste pour retenir est un cul-de-sac, où le recruteur
  voit un bon profil et n'a rien à en faire sinon lui dévoiler ses coordonnées — dépenser un crédit
  sur un profil qu'il n'a même pas pu mettre de côté. L'échappatoire pousserait au geste que le reste
  du cadrage cherche à éviter. Entonnoir souple et geste à sens unique ne vont pas ensemble : soit
  l'entonnoir est strict et « Tous » n'existe pas, soit il est souple et le tableau sait retenir.
- **L'état vide plutôt qu'un défaut mouvant.** L'autre issue consistait à ouvrir sur « Tous » tant
  que rien n'est retenu, puis à basculer. Ça évite le vide — au prix d'un onglet par défaut qui
  change tout seul d'une visite à l'autre, et d'un entonnoir qui cesse de se voir au moment précis où
  il faudrait qu'il s'explique. Un écran vide qui dit quoi faire vaut mieux qu'un écran plein qui ne
  dit rien.
- **Un fil, pas un bouton.** Le bouton de retour aurait pris la place exacte de l'ancien, à droite
  de la gouttière : le geste s'inverse sans changer de place, c'est lisible. Mais on remettrait un
  geste dans une bande dont `ENT-116` vient de retirer les gestes, et l'espace aurait **deux façons
  de remonter** — l'écran de création de recherche utilise déjà le fil, et sa documentation dit
  explicitement « aucun bouton de sortie, c'est le fil qui fait remonter ». Un seul procédé.
- **Le guidage passe à droite parce que le fil prend sa place.** Même écran de création comme
  précédent : le fil à gauche, l'aide en pastille à droite. Ce n'est pas un déclassement du guidage,
  c'est la seule disposition qui garde les deux.
- **« Retenus » et non « Favoris ».** L'arbitrage a été rendu en `ENT-050` — le mot dit le **geste**
  du recruteur là où « favori » dit un goût — et il laissait écrit que « la Table et la Visionneuse
  disent encore *Favoris* jusqu'à ce qu'on les touche ». On les touche.
- **La vue par défaut ne s'écrit pas dans l'adresse**, et cette convention ne bouge pas : c'est elle
  qui garde l'adresse courante propre et qui fait que revenir sur le défaut rend exactement l'adresse
  d'où l'on venait. Inverser le défaut inverse donc mécaniquement les noms de vue.

## Règles et cas limites

- **Aucun profil retenu** → l'état vide, avec le retour vers la Visionneuse. L'onglet « Tous » reste
  visible et actif : le rattrapage ne passe pas par le vide.
- **Aucun critère de recherche** → l'état existant l'emporte, il est plus en amont : sans critère il
  n'y a pas de jeu, donc pas de retenus à afficher.
- **Adresse portant l'ancien nom de vue** — un signet, un lien collé dans un message : elle doit
  rendre une vue lisible et non un écran vide. C'est déjà la règle du service des vues, elle est
  seulement à ré-éprouver après inversion.
- **Paramètre de vue répété ou inconnu** : même règle, repli sur le défaut.
- **Recherche partagée par un collègue** : les retenus sont ceux **de la recherche**, pas du
  recruteur — deux membres voient la même liste. Le fil et l'état vide ne changent pas de texte.
- **Recherche close** : l'écran reste consultable, les gestes d'écriture restent inactifs.
- **Le nom de la recherche est éditable dans la barre haute** et apparaît aussi dans le fil : le fil
  affiche le nom courant, il ne fige pas une copie.
- **Recherche sans nom** : le fil reprend le repli déjà utilisé par la barre haute, il n'invente pas
  son propre libellé.
- **Accessibilité** : le fil est une navigation nommée ; la pastille de guidage reste une zone de
  courtoisie annoncée poliment ; l'état vide est du texte, pas une image.
- **Trois langues** : « Retenus », le fil, la pastille et l'état vide sont posés dans les trois
  fichiers, sous peine d'échec du test de parité.

## Ce que l'implémentation a découvert

**L'écran s'ouvrait vide alors qu'il y avait des retenus**, et ce défaut PRÉEXISTAIT à cette carte.
La liste des retenus vivait dans un `ref` local au composable, quand ses deux voisines — les favoris
et les écartés — vivent dans le store. Or seul le store traverse le rendu serveur : la liste était
calculée au SSR, puis perdue à l'hydratation, sans que le chargement rejoue.

Mesuré avant correction, sur une recherche à 3 retenus sur 32 :

```
HTML rendu par le serveur : « Retenus 3 » · 3 lignes
après hydratation         : « Retenus 0 » · 0 ligne
```

Il était invisible tant que « Retenus » était l'onglet SECONDAIRE — on n'y arrivait qu'en cliquant,
et le clic ne rechargeait rien non plus. En faire le défaut le met en pleine lumière : il rendait
fausse la première ligne de la définition de terminé.

**Corrigé ici plutôt que reporté** : cette carte livre un écran qui s'ouvre sur les retenus ; le
sortir aurait livré exactement le contraire. La liste rejoint le jeu de la recherche, comme ses
voisines, et le store la **préserve** quand un autre écran repeint le jeu — le patron déjà appliqué à
la sélection. La normalisation, elle, ne la fabrique pas : elle lit le jeu paginé, où les retenus
posés au-delà de la première page ne figurent pas.

## Ce que l'implémentation a tranché autrement

**Les liens de la Visionneuse changent, contrairement à ce que le hors-périmètre annonçait.** La
carte disait « rien à y changer » — son intention était bonne, mais son code construisait l'adresse
avec le nom de la vue. Ce nom ayant disparu avec le défaut, le lien produisait `?vue=undefined`. Les
trois pages concernées pointent désormais vers `/table` nu, ce qui est exactement l'adresse du
défaut. C'est un ajustement mécanique de l'inversion, pas un changement de la Visionneuse.

## Hypothèse assumée

Le tableau passe d'environ cent cinquante lignes à une dizaine par défaut. La question de la
pagination — ouverte dans le cadrage, où le mockup charge tout d'un coup pendant que l'API pagine —
devient **beaucoup moins pressante sans être tranchée**. L'onglet « Tous » la ramène intacte : la
carte ne prétend pas la résoudre, elle en réduit la fréquence.

## Documentation impactée

- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — le rôle de l'écran en tête de
  document, ses onglets, son état vide, et les décisions validées qui décrivent encore un écran de
  balayage.
- [application_shell.md](../cadrage/entreprise/ui/application_shell.md) — §3 et §4 : ce que porte la
  gouttière du tableau, et la bascule entre les deux outils qui ne va plus dans ce sens.
- [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) —
  **à vérifier seulement** : elle décrit déjà l'écran correctement ; la ligne du fil et du guidage
  peut y manquer.
- `webapp/services/README.md` — si la convention d'adresse des vues y est énoncée.

## Vérification

Stack du worktree entreprise, navigateur piloté, sur deux recherches — l'une à **3 retenus sur 32**,
l'autre à **0 sur 41**.

**Le défaut par lui-même, et l'adresse :**

```
arrivée              : /recherches/…/table          · Retenus 3 | Tous 32 · 3 lignes
après clic « Tous »  : /recherches/…/table?vue=tous · 30 lignes
```

**Une adresse écrite avant l'inversion reste juste** — `?vue=favoris` n'est plus un nom connu, elle
retombe sur le défaut, qui est la vue qu'elle demandait :

```
/recherches/…/table?vue=favoris · Retenus 3 | Tous 32 · 3 lignes
```

**Sans aucun retenu**, l'écran dit ce qui manque au lieu de basculer :

```
Retenus 0 | Tous 41 · 0 ligne
« Aucun profil retenu pour l’instant · Parcourez les profils de cette recherche et mettez de côté
  ceux qui vous intéressent : ils arriveront ici. »  + bouton « Parcourir les profils »
```

**Le fil et la gouttière**, sur les quatre écrans mesurés :

```
fil : « Preuve architecte › Ma sélection »   ·   guidage à droite, aucun bouton de sortie
```

**Le défaut d'hydratation, avant et après correction** — même page, même serveur :

```
avant : HTML serveur « Retenus 3 » / 3 lignes  →  après hydratation « Retenus 0 » / 0 ligne
après : « Retenus 3 » / 3 lignes, stable de 1 s à 10 s
```

**Tests** — `bun run vitest run services/search/` et le type-check des quatre espaces :

```
 Test Files  16 passed (16)
      Tests  303 passed (303)
 type-check  public · candidat · entreprise · admin — aucune erreur
```

## Livré

- `webapp/services/search/views.ts` **(M)** — le défaut passe aux retenus, « Tous » prend le nom
  dans l'adresse, le repli suit.
- `webapp/services/search/views.vitest.ts` **(M)** — le défaut inversé, et l'adresse écrite avant
  l'inversion.
- `webapp/shared/types/search.ts` **(M)** · `stores/searches.ts` **(M)** ·
  `composables/useSearch.ts` **(M)** · `services/search/normalize.ts` **(M)** — la liste des retenus
  rejoint le jeu de la recherche et survit au rendu serveur.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — onglets, fil, pastille,
  état vide, plus de bouton de sortie.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** · `recherches-v1` **(M)** ·
  `recherches-v2` **(M)** — le lien vers le tableau devient nu.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — « Retenus », le guidage, l'état vide.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` **(M)** · `application_shell.md` **(M)** ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` **(M)**.

## Reste à faire (hors périmètre)

- **La pagination du tableau**, que l'onglet « Tous » ramène intacte — cf. *Hypothèse assumée*.
- **`webapp/services/README.md`** : la convention d'adresse des vues n'y est pas énoncée, rien à y
  corriger. Vérifié.

## Définition de terminé

- [x] Arriver sur le tableau depuis la Visionneuse montre les profils retenus, pas la recherche
      entière.
- [x] L'onglet « Tous » reste accessible en un clic et affiche le compte de la recherche.
- [x] L'onglet des retenus s'appelle « Retenus » dans les trois langues.
- [x] L'adresse de la vue par défaut ne porte pas de paramètre de vue ; celle de « Tous » en porte un.
- [x] Une adresse portant l'ancien nom de vue ouvre une vue lisible, jamais un écran vide.
- [x] La gouttière porte le fil d'Ariane à gauche et la pastille de guidage à droite, et aucun
      bouton.
- [x] Le premier maillon du fil ramène à la Visionneuse de la même recherche.
- [x] Sans aucun profil retenu, l'écran dit ce qui manque et propose de retourner parcourir.
- [x] Le guidage ne dit plus « tous les profils de la recherche courante ».
- [x] `views.vitest.ts` couvre le défaut inversé et les adresses dégradées ; le test de parité des
      langues passe.
- [x] `vue_la_table.md` et `application_shell.md` décrivent l'écran tel qu'il est.
