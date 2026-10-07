# TASK ADM-019 — admin : les commentaires cessent de raconter l'histoire du code

- **Créée le** : 2026-08-09.
- **Session** : `3d83b5c5-c632-417d-967f-1345935b8392`.
- **Statut** : ✅ fait le 2026-08-09 — plus un seul commentaire `.vue` de l'espace admin ne cite un
  numéro de tâche, ne décrit un état révolu, ni ne promet une tranche future. **11 fichiers**, et le
  code est **strictement identique** une fois les commentaires retirés.
- **Type** : refactor de documentation dans le code. Aucune ligne exécutable n'est touchée.
- **Tests** : `sans objet` — aucun code exécutable produit ni modifié. La preuve est une comparaison
  mécanique : chaque fichier est comparé à sa version `HEAD` une fois les commentaires retirés.
- **Réf** : `ENT-074` — **même geste sur l'espace entreprise**, carte sœur qui porte le cadrage
  complet et le critère de tri · `WEB-017` — même geste sur le socle · `WEB-018` — **aval** : le
  verrou de conventions, qui ne passera au vert qu'une fois ces trois cartes mergées.

## Contexte

Le cadrage vit dans `ENT-074` et n'est pas recopié ici : un commentaire décrit un **état présent** ;
ce qui n'a de sens que pour qui connaît le passé appartient au message de commit ou à la carte.

Cet espace en portait **trois familles**, dont deux qu'`ENT-074` n'avait pas rencontrées :

1. **Des états révolus à l'imparfait** — « une table tenue ici en parallèle **avait fini par**
   oublier deux écrans ».
2. **L'ancienne numérotation de tâches, sans préfixe** — `(tâche 099)`, `(carte 120)`, `(tâche 124)`.
   Sept écrans en portaient. **Le détecteur d'`ENT-074` ne cherchait que les préfixes `ADM-`/`ENT-`
   et passait complètement à côté** ; c'est en balayant cette lane qu'elle est apparue, dans les
   trois worktrees à la fois.
3. **De la feuille de route**, qui est un `TODO` sous un autre nom : « Fiche dédiée + filtres =
   tranches suivantes », « l'édition des droits est la suite », « la relance on-demand est la tranche
   suivante ».

La troisième famille porte le défaut le plus net du lot : `pages/candidats.vue` annonçait une fiche
dédiée « à venir » alors que `pages/candidats/[id].vue` **existe et la rend**. Le commentaire était
donc devenu faux, et personne ne pouvait le savoir sans ouvrir le dossier voisin.

## Périmètre

- **Les 11 fichiers `.vue` de `spaces/admin/`** portant un renvoi de tâche (ancienne ou nouvelle
  numérotation), une phrase au passé, ou une promesse de tranche future.
- **Réécriture au présent, jamais suppression sèche** — la contrainte portée par la phrase reste,
  seul le récit part.
- **Aucune autre ligne**, de commentaire comme de code.

## Hors périmètre

- **Les fichiers `.ts`** de l'espace : même défaut, écarté au cadrage commun.
- **Les espaces entreprise et socle** — `ENT-074` et `WEB-017`.
- **Le verrou de conventions** — `WEB-018`, lane socle.
- **La justesse fonctionnelle des écrans décrits.** La carte corrige ce que les commentaires
  **disent**, pas ce que les écrans **font**.

## Décisions de cadrage

### La feuille de route sort du code, au même titre que le journal

Une promesse de tranche future est un `TODO` déguisé : elle ne se vérifie pas, elle n'a pas de date,
et rien ne la retire quand elle est tenue — `candidats.vue` en est la démonstration. Elle est donc
traitée comme le reste, mais **le fait qu'elle porte survit**, énoncé au présent :

**Aujourd'hui** — « Lecture seule ; l'édition des droits (ADMIN_OWNER) est la suite. »
**Demain** — « LECTURE SEULE : l'édition des droits demande `ADMIN_OWNER` et ne se fait pas ici. »

La seconde dit à qui lit l'écran ce qu'il peut en attendre, sans engager un calendrier que le fichier
n'a aucun moyen de tenir.

### Deux renvois dormaient au MILIEU d'un commentaire, et le `grep` ne pouvait pas les voir

Le balayage de cadrage était ancré en début de ligne (`^\s*(//|\*|<!--)`) : il lit la première ligne
d'un commentaire, jamais la troisième. `referentiels.vue` et `vivier.vue` en cachaient un chacun,
`(tâche 124)` et un « défile désormais », au milieu de commentaires de trois lignes.

Ils ne sont apparus qu'en écrivant le **détecteur de `WEB-018`**, qui suit l'ouverture et la
fermeture des blocs. **L'outil a trouvé ce que la relecture avait manqué** — et c'est la meilleure
justification de la carte aval : cette lane avait été déclarée propre par un `grep`, elle ne l'était
pas.

### Ce qui reste, et qui ressemble à du passé

`pages/vivier.vue` garde « ce que le pipeline **a fait** » : c'est la question à laquelle répond
l'écran `/jarvi`, pas une chronologie de notre code. Relu, conservé.

## Règles et cas limites

- **Aucun commentaire n'est supprimé pour cause de longueur.**
- **Le rendu ne change pas**, et c'est vérifié mécaniquement.
- **Les renvois à l'ancienne numérotation partent tous**, y compris les `(cf. carte 120)` qui
  ressemblaient à des références utiles : les cartes 099 à 124 sont antérieures au découpage en
  espaces, et rien dans le nom d'un fichier ne permet de les retrouver.

## Documentation impactée

Aucune : la carte applique une règle qui existe déjà dans le socle global, sans en créer de nouvelle.

## Définition de terminé

- [x] Aucun commentaire `.vue` de l'espace ne cite un numéro de tâche — **ni ancienne, ni nouvelle
      numérotation**.
- [x] Aucun ne décrit un état révolu.
- [x] Aucune promesse de tranche future.
- [x] Aucun `TODO`, `FIXME`, `HACK` ni `XXX` — il n'y en avait aucun.
- [x] **Le code est identique** : 11/11 fichiers rendent le même contenu une fois les commentaires
      retirés.
- [x] Gates, en **une passe** à l'ouverture de la PR : **vitest 308/308**, type-check vert sur les
      quatre espaces, prettier vert, **conventions — 18 règles, aucune violation**, eslint **0
      erreur** (20 avertissements pré-existants, identiques à `develop`).

## Vérification

### Le code n'a pas bougé

```
✔ 11/11 fichiers : code identique commentaires retirés
```

### Les motifs ne rendent plus rien

```
=== renvois de tâche (deux numérotations) + marqueurs révolus ===
(aucun)
=== feuille de route dans le code ===
(aucun)
```

Le balayage sur les temps du passé rend **une ligne**, relue et conservée à dessein :
`pages/vivier.vue:5`, « ce que le pipeline a fait » — la question posée par un autre écran, pas notre
historique.

### Le volume

21 lignes posées pour 22 retirées, sur 11 fichiers. Le solde est nul et c'est attendu : **on a
réécrit, pas coupé**.

## Livré

- `webapp/spaces/admin/app/pages/candidats.vue` (M) — la promesse d'une « fiche dédiée à venir »
  part : elle était devenue fausse, `candidats/[id].vue` la rend.
- `webapp/spaces/admin/app/pages/candidats/[id].vue` (M), `index.vue` (M), `vivier.vue` (M),
  `referentiels.vue` (M) — renvois à l'ancienne numérotation retirés.
- `webapp/spaces/admin/app/pages/jarvi.vue` (M), `utilisateurs.vue` (M) — la tranche promise devient
  un état présent : « LECTURE SEULE », et ce qu'elle interdit.
- `webapp/spaces/admin/app/components/Topbar.vue` (M) — l'oubli de deux écrans par une table tenue en
  parallèle devient la règle qui l'empêche, au présent.
- `webapp/spaces/admin/app/components/DashboardBody.vue` (M) — « la racine async **cassait** la
  transition » devient « **casse** » : c'est un piège actif, pas un souvenir.
- `webapp/spaces/admin/app/components/MobileDrawer.vue` (M), `SidebarNav.vue` (M) — conditionnels
  rétablis.

## Reste à faire (hors périmètre)

- **Les fichiers `.ts`** de l'espace, porteurs du même défaut : écartés au cadrage commun.
- **`WEB-018`**, le verrou — sa forme doit couvrir **les deux numérotations** de tâches et les
  **temps du passé**, faute de quoi il laisserait passer exactement ce que cette carte vient de
  retirer.
