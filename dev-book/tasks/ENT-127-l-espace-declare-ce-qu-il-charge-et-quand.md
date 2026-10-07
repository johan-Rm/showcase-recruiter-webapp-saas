# TASK ENT-127 — l'espace déclare ce qu'il charge, et quand

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — le plan de travail est chargé en arrière-plan et ouvrir une
  recherche préchargée ne redemande plus son jeu. Trois écarts au cadrage, consignés plus bas.
- **Type** : Frontend espace entreprise — branchement de l'orchestrateur de chargement du socle,
  préchargement en arrière-plan.
- **Tests** : vitest — le résolveur de chaque route déclare le bon palier ; un chargeur dont la donnée
  est déjà en store est muet ; un plan de travail en échec ne bloque ni ne casse l'écran.
- **Réf** : **amont bloquant `API-050`** (la route agrégée) · `webapp/base/app/composables/useAppBootstrap.ts`
  et `webapp/base/app/utils/bootstrapRegistry.ts` (l'orchestrateur, déjà écrit) ·
  `webapp/spaces/admin/app/plugins/bootstrap-registry.ts` (l'espace qui s'en sert, à prendre pour
  modèle) · `webapp/spaces/entreprise/app/plugins/bootstrap-registry.ts` (celui à remplir).

## Contexte

**Le socle sait séquencer un chargement en trois paliers** — métadonnées, critique bloquant,
arrière-plan différé — et l'espace admin s'en sert. **L'espace entreprise ne déclare que le premier**
et laisse son registre de routes vide : chaque écran charge ses données avec son propre
`useAsyncData`, sans que rien ne sache ce qui est critique, ce qui peut attendre, ni ce qui servira à
l'écran suivant. Le fichier le dit lui-même : *« le registre de routes reste vide : chaque écran
charge encore ses propres données »*.

`API-050` sert désormais le plan de travail complet — toutes les recherches ouvertes, fiches
dédoublonnées — en un appel. Reste à décider **quand** il part, et cette décision est prise :
**en arrière-plan, une fois l'accueil affiché**.

Un point demande de la précision : **le lien direct vers une recherche**. Arriver sur
`/recherches/{id}` sans passer par l'accueil ne doit rien attendre d'un préchargement d'arrière-plan
— cette recherche-là est critique, et elle doit être là avant le premier rendu, comme aujourd'hui.
C'est exactement la distinction que le registre modélise.

## Périmètre

- **Déclarer le registre de routes de l'espace entreprise** :

  ```
  accueil               critique     : la collection des recherches
                        arrière-plan : le plan de travail
  visionneuse / table   critique     : le jeu de CETTE recherche
                        arrière-plan : le plan de travail
  ```

- **Écrire les chargeurs correspondants** dans `app/bootstrap/loaders.ts`, à côté du chargeur de
  configuration existant, chacun avec son `key` et son `isLoaded()`.
- **Le plan de travail hydrate les stores** — pool de candidats, listes par recherche — sans passer
  par les écrans.

## Hors périmètre

- **`preloadAll` et le préchargement prédictif N+1** : deux autres leviers du même orchestrateur,
  utiles mais distincts ; les brancher demande de décider ce qui est « lourd » et ce qui ne l'est pas.
- **Les autres écrans de l'espace** — crédits, équipe, entreprise, profil — qui gardent leur
  `useAsyncData`.
- **Le chargement du jeu lui-même** (`ENT-126`) et **les listes d'état** (`ENT-125`).
- **Une quelconque borne** sur ce que le plan de travail transporte : elle est portée par la clôture
  automatique, côté serveur.

## Décisions de cadrage

### Arrière-plan, jamais bloquant

L'accueil s'affiche exactement comme aujourd'hui ; le plan de travail arrive derrière. Une recherche
ouverte dans la seconde qui suit l'arrivée chargera encore la sienne — **aucune régression, juste pas
encore le gain**. Bloquer l'affichage sur trois cent cinquante kilo-octets pour un bénéfice qui ne se
voit qu'au deuxième clic serait un mauvais échange.

### Le dédoublonnage est déjà écrit

Chaque chargeur porte un `isLoaded()`. Une recherche déjà apportée par le plan de travail rend son
chargeur critique **muet** : aucun appel en double, et aucune condition à écrire à la main dans les
écrans. C'est ce mécanisme qui rend la cohabitation du critique et de l'arrière-plan sûre.

### Le plan de travail ne remplace pas la collection

Les noms, les critères et les compteurs restent servis par la collection, sur le chemin critique de
l'accueil — c'est ce qui décide de ce qui s'affiche. Fusionner les deux mettrait tout le contenu sur
le chemin critique, soit l'inverse du but.

### ÉCART — l'explication des critères devient une relation de la recherche

**Découvert à l'implémentation, arbitré par l'utilisateur.** Le plan de travail sert les fiches sans
leur explication de correspondance : le serveur la sert à part, par recherche, parce qu'un même
profil répond à trois recherches pour trois motifs. Le front, lui, la gardait collée à la fiche —
et le pool étant commun, l'explication de la dernière recherche ouverte écrasait celle des autres.

Préchargé sans traiter ce point, l'écran d'une recherche préchargée aurait perdu ses étiquettes
« pourquoi ce profil ». Elle rejoint donc les autres listes portées par la recherche, et l'affichage
la recolle au dernier moment. **Ça corrige aussi un défaut qui existait avant cette tâche.**

Les deux autres options — ne précharger que les listes, ou assumer la perte des étiquettes — ont été
écartées : la première vide le préchargement de son intérêt, la seconde est une régression visible.

### ÉCART — l'accueil ne déclare finalement rien de bloquant

Le cadrage annonçait la collection des recherches en critique sur l'accueil. À l'implémentation,
c'est une mauvaise idée : cette collection porte des compteurs — nouveautés, vus, retenus — qui
bougent dès qu'on ouvre une recherche, et un chargeur du registre porte une garde qui la tairait au
retour. L'accueil afficherait alors des compteurs d'avant. L'écran continue donc de la charger
lui-même, à chaque visite ; seul le plan de travail est déclaré, en arrière-plan.

### ÉCART — deux chemins mènent à la donnée, il fallait une garde partagée

L'orchestrateur ET l'écran chargent la même chose. Sans garde commune, **chaque appel partait deux
fois** — constaté au navigateur, deux `?scope=favorites` et deux `?scope=dismissed` par ouverture.
Un drapeau par recherche, traversant le rendu serveur, tranche : le second chemin trouve le travail
fait. Le rechargement après un dévoilement passe, lui, par une fonction qui ignore délibérément
toutes les gardes — la garde tairait précisément l'appel dont on a besoin.

## Règles et cas limites

- **SSR** : le palier d'arrière-plan est déclenché après le rendu, donc client seulement. Le plan de
  travail ne doit jamais entrer dans le payload serveur, sous peine d'alourdir le HTML de l'accueil.
- **Échec silencieux** : un plan de travail qui ne répond pas ne montre aucune erreur et ne bloque
  rien — les écrans continuent de charger ce qu'il leur faut.
- **Lien direct** vers une recherche : son jeu est critique, attendu avant le rendu.
- **Session ouverte après le premier rendu** : le palier ne part que pour un utilisateur connecté,
  garde déjà portée par l'orchestrateur.
- **Concurrence** : plan de travail et chargeur critique peuvent partir en même temps sur la même
  recherche ; le rangement dans les stores doit rester idempotent.
- **Mémoire** : le pool grandit avec le plan de travail ; rien ne le purge aujourd'hui, et ce n'est
  pas cette tâche qui l'introduira — à surveiller si le volume monte.

## Ce qui n'a pas pu être couvert par des tests

**Les résolveurs de route et le mutisme d'un chargeur ne sont PAS testés en unitaire**, contrairement
à ce qu'annonçait la ligne `Tests` de cette carte. La raison est structurelle : `vitest.config.ts`
limite son périmètre au **code pur**, sans runtime Nuxt, et un chargeur appelle des stores et le
client HTTP dès sa construction. Les couvrir demanderait `@nuxt/test-utils`, que le dépôt n'a pas
encore introduit — c'est une décision d'outillage, pas un à-côté de cette tâche.

Ce qui est couvert en unitaire : la **traduction** du plan de travail et le déplacement de
l'explication, qui sont le vrai changement de modèle. Le reste est prouvé **au navigateur**, sur la
pile réelle — ce qui est plus convaincant qu'un test à double, mais ne rejoue pas en continu.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md` — ce que l'espace entreprise déclare
  au bootstrap, et ce que les écrans ne chargent plus eux-mêmes.
- `webapp/spaces/entreprise/app/bootstrap/` — le commentaire de tête des chargeurs, qui porte la
  doctrine des paliers pour cet espace.

## Vérification

Tout ce qui suit a été observé **au navigateur**, sur la pile de ce worktree, session ouverte.

**L'accueil n'attend pas le plan de travail** — il s'affiche, l'appel part derrière :

```
── ACCUEIL — appels vus par le navigateur
   GET /api/searches/workspace
```

**Ouvrir une recherche préchargée ne redemande plus son jeu.** L'appel des cent fiches a disparu ;
restent les deux listes que rien ne précharge :

```
── OUVERTURE par clic (jeu déjà préchargé)
   GET /…/results?scope=favorites
   GET /…/results?scope=dismissed
   → 100 cartes à l'écran, 304 étiquettes de critère
```

**Le lien direct charge toujours avant le rendu** : cent cartes sont dans le HTML **servi**, et le
navigateur ne demande rien avant d'afficher.

**Le plan de travail en panne ne casse rien** — réponse forcée en 500, deux fois :

```
   cartes de recherche affichées : 2
   message d'erreur à l'écran    : aucun
   erreurs JavaScript            : aucune
```

**Le « pourquoi ce profil » survit au déménagement** : sur une recherche préchargée, la Visionneuse
affiche « 3/3 Aligné sur vos critères — java · sql · agile ».

```
vitest services/search/   →  316 tests, 16 fichiers, tous verts (11 tests neufs)
quality-type-check        →  vert sur les quatre espaces
```

## Livré

- `webapp/spaces/entreprise/app/bootstrap/registry.ts` **(+)** — la carte route → données.
- `webapp/spaces/entreprise/app/bootstrap/loaders.ts` — six chargeurs : la collection, la fiche, le
  jeu, les retenus, les écartés, le plan de travail ; plus l'ouverture groupée et sa relecture
  forcée.
- `webapp/base/server/api/searches/workspace.get.ts` **(+)** — le relais vers la route agrégée.
- `webapp/shared/types/search.ts` — le plan de travail au contrat, et l'explication portée par la
  recherche.
- `webapp/services/search/normalize.ts` — traduction du plan, détachement puis recollage de
  l'explication.
- `webapp/spaces/entreprise/app/stores/searches.ts` — l'explication survit à un jeu repeint.
- Les deux composables passent par les mêmes chargeurs.

### Le formateur est passé sur la branche entière

Ouvrir la PR a révélé sept fichiers non conformes au formateur, dont **quatre appartiennent à
`ENT-126`** : les gates ne tournent qu'à l'ouverture de la PR, et rien ne les avait vus. Le commit
de tête les embarque — celui d'`ENT-126` n'est plus modifiable sans réécrire l'historique de la
branche. Aucun changement de comportement, uniquement de la mise en forme.

Une erreur de lint a été corrigée dans la foulée, celle-là bien dans le code de cette tâche : le
type de retour explicite ajouté à la suppression d'une recherche n'était pas accepté sous la forme
`void`, refusée comme argument générique par la configuration du dépôt.

## Reste à faire (hors périmètre)

- **Les résolveurs de route ne sont pas testés en unitaire** — la raison est plus haut. La décision
  d'introduire `@nuxt/test-utils` appartient à une tâche d'outillage.
- **Le poids du plan de travail sur un gros compte** : 2,4 Mo pour 56 recherches ouvertes, mesuré
  côté API. Ce worktree n'en a que deux, la question ne s'y pose pas — elle se posera.

## Définition de terminé

- [x] Le registre de routes de l'espace entreprise est déclaré, accueil et écrans de recherche.
- [x] Arriver sur l'accueil affiche l'écran sans attendre le plan de travail, puis le charge.
- [x] Ouvrir une recherche déjà préchargée **ne redemande plus son jeu** — critère RÉÉCRIT. Il
  disait « ne déclenche aucun appel », ce que le plan de travail ne peut pas offrir : il ne
  transporte ni les fiches des écartés, ni celles des retenus posés au-delà de la borne. Deux appels
  de listes courtes subsistent donc, et le gros appel disparaît.
- [x] Un lien direct vers une recherche charge son jeu avant le rendu, comme aujourd'hui.
- [x] Un plan de travail en échec ne produit ni erreur visible ni écran vide.
- [ ] **Tests vitest sur les résolveurs de route et le mutisme d'un chargeur : NON FAITS.** Motif
  structurel décrit plus haut ; commit décidé par l'utilisateur en connaissance de cause, la
  couverture unitaire portant sur la traduction et le comportement étant prouvé au navigateur.
- [x] Documentation à jour.
