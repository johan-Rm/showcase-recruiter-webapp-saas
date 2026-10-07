# TASK ENT-130 — l'accueil charge l'état, puis les fiches qu'il affiche

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-17 — l'accueil charge l'état de travail puis les trente premières
  fiches des recherches qu'il affiche, en deux appels séparés : **594 Ko au lieu de 4,95 Mo**.
- **Type** : Frontend espace entreprise — le préchargement change de forme et de poids.
- **Tests** : vitest sur la couche pure — la composition de l'appel à partir des recherches
  affichées, et le rangement des fiches dans la liste commune. Le reste est prouvé au navigateur :
  l'outillage de test du dépôt ne couvre pas les chargeurs, qui touchent l'état partagé dès leur
  construction.
- **Réf** : **amont bloquant** `API-060` (l'état allégé) et `API-061` (les fiches par lot) ·
  `ENT-127` (le préchargement qu'elle remplace) · `composables-standard.md`.

## Contexte

**Le préchargement livré par `ENT-127` rapporte trois méga-octets à l'ouverture de l'accueil, dont
l'immense majorité ne sera jamais regardée.** Mesuré sur un compte réel : 55 recherches ouvertes,
1 442 profils, alors que l'accueil n'en affiche que quatre cartes et que le recruteur n'en ouvrira
souvent qu'une.

Le serveur sait désormais servir séparément l'état de travail et les fiches. L'accueil doit
s'aligner : **charger l'état de toutes les recherches, et les fiches des seules recherches qu'il
montre.**

## Périmètre

- **Deux chargements distincts à l'ouverture de l'accueil** : l'état de travail de toutes les
  recherches ouvertes, puis les trente premières fiches de chacune des recherches affichées.
- **L'écran nomme les recherches** dont il veut les fiches : ce sont celles de ses cartes, quel que
  soit leur nombre.
- **Les fiches vont dans la liste commune des candidats**, sans rattachement à une recherche —
  c'est déjà ce que fait cette liste, rien à y changer.
- **Retirer l'ancien préchargement**, qui rapportait fiches et explications avec l'état.

## Hors périmètre

- **Le rythme de chargement en descente** — anticipé plutôt que déclenché en fin de liste. Il fera
  sa propre tâche, après discussion. Déjà acté pour elle : elle couvrira la Visionneuse **et** la
  Table, pour qu'une même recherche ne montre pas deux comportements.
- **Les explications**, qui ne sont plus préchargées : leur moment se décide avec la tâche ci-dessus.
- **Les autres écrans que l'accueil.**
- **La libération de la mémoire** : la liste commune ne se vide toujours pas. Dette assumée, à
  mesurer plutôt qu'à corriger ici.

## Décisions de cadrage

### L'écran nomme, le serveur ne choisit pas

L'accueil sait ce qu'il montre. S'il en affichait six demain, l'appel suivrait sans qu'on touche au
serveur. L'inverse — un serveur qui prendrait « les quatre plus récentes » — ferait afficher une
recherche et en précharger une autre.

### Trente par recherche

Quatre recherches affichées donnent environ cent fiches uniques après dédoublonnage, soit à peu près
110 kilo-octets : n'importe laquelle s'ouvre aussitôt avec de quoi commencer à trier. Un budget
global de trente réparti entre elles n'en donnerait que sept ou huit chacune, et l'ouverture
redemanderait immédiatement.

### Six recherches préchargées, et non quatre — arbitré à l'implémentation

L'accueil montre deux rangées pleines, donc **de deux à six cartes selon la largeur de l'écran**, et
cette largeur n'est pas connue au moment du chargement. Précharger pour la grille étroite laisserait
les deux dernières cartes d'un grand écran sans fiches — le cas fréquent. C'est donc la capacité de
la **grille la plus large** qui commande, six.

### La composition de l'appel descend dans la couche pure

Choisir les recherches et trier leurs identifiants est une règle, pas de la plomberie : laissée dans
le chargeur, elle n'était testable qu'au navigateur. Elle vit avec les autres règles d'affichage de
la collection, et ses quatre cas sont couverts — d'où une ligne `**Tests**` tenue plus largement que
ce qu'elle annonçait.

### LA MÉMOIRE D'UNE RECHERCHE NE CONTIENT QUE DES NOMBRES ENTIERS

**Aucune liste d'état ne se déduit de ce qui est chargé.** C'est la règle, et elle ne souffre pas
d'exception : un compte tiré des fiches en mémoire vaut « parmi ce que j'ai », pas « dans cette
recherche » — et il devient franchement faux dès que le chargement est partiel.

Trois écarts à corriger dans cette tâche :

- **Les nouveaux profils** sont aujourd'hui reconstitués à partir des fiches reçues, seule des cinq
  listes dans ce cas. `API-060` les sert désormais ; l'écran les lit au lieu de les recalculer.
- **Les retenus existent en double** : une liste vient du plan de travail, l'autre d'un appel dédié
  qui disparaît. Une seule subsiste, celle que le serveur sert, dans l'ordre du classement.
- **L'explication des correspondances reste partielle par nature** — elle n'existe que pour les
  profils chargés. Elle n'est pas un compte et **rien ne doit la compter** : ni total, ni position,
  ni badge dérivé de sa taille.

### La garde des chargeurs devra changer de question

La garde actuelle demande « la liste des profils est-elle connue ? ». Avec un chargement partiel,
elle répondra « oui » alors que trente fiches sur trois cents seulement sont là. **Elle doit demander
si les fiches nécessaires sont présentes, pas si la liste l'est.** Observé au navigateur : avec une
seule fiche manquante, l'écran affiche 99 cartes en annonçant 100, sans rien redemander.

## Règles et cas limites

- **L'état arrive avant les fiches** : sans lui, l'écran a des profils sans savoir à quelle recherche
  ils appartiennent ni dans quel ordre les présenter.
- **Un échec du chargement des fiches ne bloque ni ne salit l'écran** : l'accueil s'affiche, et
  l'ouverture d'une recherche ira chercher ce qui manque.
- **Rendu serveur** : le préchargement des fiches reste un geste d'après-affichage, jamais dans la
  page servie.
- **Une recherche au jeu vide** ne déclenche aucune demande de fiches.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md` — le chapitre « qui charge quoi, et
  quand » décrit le préchargement d'`ENT-127` ; il devient faux.

## Vérification

Parcours rejoué au navigateur sur la pile du worktree entreprise, compte de recette, 59 recherches
ouvertes. Poids réellement transférés, relevés sur les réponses.

**À l'ouverture de l'accueil** — deux appels, dans cet ordre :

```
+37,5 s  200  360 899 o  /api/searches/workspace
+39,2 s  200  247 396 o  /api/searches/profiles?ids=019ff56d-64b5-…,019ff56d-6eee-…
─────    total 594 Ko
```

Contre **4,95 Mo** mesurés au même endroit avec le préchargement d'`ENT-127` : **÷8,3**.

L'état ne porte plus que des identifiants, ce que confirme sa réponse brute :

```
/v1/searches/workspace  ->  HTTP 200  290512 octets
clés racine : ['searches'] | recherches : 59
clés d une entrée : ['id', 'resultIds', 'favoriteIds', 'dismissedIds', 'seenIds', 'newIds']
```

**À l'ouverture d'une recherche préchargée** — le chargeur du jeu reste **muet**, sa garde voyant les
trente fiches déjà présentes. Seuls partent les deux périmètres, qui ne sont pas préchargés :

```
+42,9 s  200      80 o  /searches/019ff56d-6eee-…/results?scope=dismissed
+44,1 s  200  45 236 o  /searches/019ff56d-6eee-…/results?scope=favorites
─────    total 44 Ko
```

**Chargement partiel, compteurs justes** : 33 cartes à l'écran, compteur « 1 / 93 » — le total vient
de l'état, pas de ce qui est chargé.

Tests joués : `bun run vitest run services/search/normalize.vitest.ts services/search/collection.vitest.ts`
→ **48 tests, 2 fichiers, verts**. `bun run quality-conventions-check` → **19 règles, aucune
violation**.

## Livré

- `webapp/shared/types/search.ts` — l'entrée d'état perd ses fiches et ses explications, gagne
  `newIds` ; `SearchProfiles` décrit le lot de fiches.
- `webapp/services/search/normalize.ts` — `normalizeWorkspace` ne déduit plus rien des fiches et
  préserve les explications acquises ; `normalizeProfiles` range un lot dans la liste commune.
- `webapp/services/search/collection.ts` — `preloadedSearchIds` compose l'appel : les recherches
  affichées, identifiants triés.
- `webapp/base/server/api/searches/profiles.get.ts` **(+)** — le relais du lot de fiches.
- `webapp/spaces/entreprise/app/bootstrap/loaders.ts` — `loadDisplayedProfiles` et
  `loadHomeBackground` ; la garde de `loadSearchSet` demande les fiches, plus la liste.
- `webapp/spaces/entreprise/app/bootstrap/registry.ts` — l'accueil précharge état puis fiches.

## Reste à faire (hors périmètre)

- **L'écran de recherche s'ouvre sur ce qui est préchargé et n'en charge pas plus** — 33 cartes sur
  93 dans la mesure ci-dessus. La descente anticipée est la tâche différée, actée en *Hors
  périmètre* ; livrée seule, celle-ci laisse l'écran partiellement rempli.
- **Les explications n'arrivent plus** — zéro étiquette de critère à l'ouverture. `API-062` les sert
  page par page ; aucun écran ne les demande encore. Même tâche différée.
- **Les deux périmètres `?scope=` partent toujours** alors qu'`API-060` sert désormais l'ordre des
  retenus dans l'état : leur retrait appartient à la même tâche.

## Définition de terminé

- [x] L'accueil charge l'état de toutes les recherches ouvertes, puis les fiches des seules
      recherches qu'il affiche.
- [x] Les fiches sont rangées dans la liste commune, sans rattachement.
- [x] L'ancien préchargement est retiré.
- [x] La garde des chargeurs vérifie la présence des fiches, non celle de la liste.
- [x] Aucune liste d'état n'est déduite des fiches en mémoire : les nouveaux profils viennent du
      serveur, et la liste des retenus n'existe qu'en un exemplaire.
- [x] Un chargement partiel — trente fiches sur trois cents — laisse tous les compteurs justes.
- [x] Un échec du chargement des fiches laisse l'accueil utilisable et sans message d'erreur : le
      palier d'arrière-plan n'est jamais bloquant, et l'accueil tire ses compteurs de sa collection.
- [x] Le poids chargé à l'ouverture est mesuré et rapporté, avant et après.
- [x] Tests vitest sur la composition de l'appel et le rangement des fiches.
- [x] `composables-standard.md` à jour.
