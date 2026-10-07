# TASK 148 — entreprise : les trois modèles de pages de la recherche

- **Créée le** : 2026-07-26.
- **Statut** : ✅ fait le 2026-07-26 — les trois routes servent des données réelles.
- **Type** : Frontend espace entreprise (dépend de la task 146 côté API).
- **Réf** :
  - `dev-book/cadrage/entreprise/ui/arborescence.md` (source des routes)
  - `vue_la_visionneuse.md` · `vue_la_table.md` (les deux outils)
  - task 146 (`GET /v1/searches/{id}/results`)

## Contexte

Les trois routes existaient en coquilles : elles affichaient « en attente d'API » sur des tableaux
vides. Tout ce que le cadrage décrit — la collection, la pile de cards, les colonnes, les vues d'état
— restait à écrire, et rien n'était branché.

## Objectif

Que les trois pages montrent le vivier réel, et que la structure porte déjà les gestes à venir.

## Décisions

### Deux routes, un seul jeu de résultats

La Visionneuse et la Table lisent le **même état**. Basculer d'un outil à l'autre ne rejoue pas la
recherche : c'est l'invariant du cadrage (« deux postures sur le même jeu »), et il ne tient que si
l'état est partagé.

### Un store par DOMAINE, pas par écran

Deux stores : `searches` (le domaine recherche — la collection **et** chaque recherche ouverte) et
`candidates` (les cartes masquées du jeu de résultats). Un premier découpage donnait un store
`searchWorkspace` par écran : il mélangeait deux domaines et surtout **dupliquait** l'entité
`Search`, présente à la fois dans la collection et dans la fiche ouverte — deux copies qui divergent
au premier renommage. La recherche ouverte est désormais une **lecture** de la collection
(`getSearchById`), pas une seconde copie.

Le store des candidats retient à quelle recherche le jeu appartient : sans ça, passer d'une recherche
à l'autre afficherait un instant les profils de la précédente sous le nom de la nouvelle.

`useSearch` (au singulier, face à `useSearches`) est un **composable** et non un store : un écran qui compose deux domaines et porte
l'appel BFF, ce qu'aucun store n'a le droit de faire.

### `useRequestFetch` et non `$fetch`

Au SSR, `$fetch` ne rejoue pas les en-têtes de la requête entrante — dont le **cookie de session**.
Le BFF ne trouvait donc pas de session et répondait 401 : les trois écrans restaient vides au premier
rendu, panne **invisible côté client** puisque le navigateur, lui, envoie le cookie. Même correctif
que la task 138 côté admin, et même cause — un composable qui appelle le BFF sans passer par
`useFetch`.

### Le jeu vient de la recherche, pas d'un rejeu de ses critères

`/api/searches/{id}/results` sert le jeu que l'API a matérialisé, avec l'état de travail de chaque
carte. Une première version traduisait les critères en query pour interroger le moteur : deux vérités
pour le même jeu, elle a été retirée avec la task 146.

### Deux arbitrages de cadrage tranchés

Deux documents se contredisaient. La **Table montre tout le jeu** (12 colonnes, vues d'état), pas
seulement les favoris ; les **critères vivent dans la barre haute** de la Visionneuse, la console
contextuelle à 9 accordéons est abandonnée. `vue_la_table.md` et `console_contextuelle.md` restent à
réaligner — c'est du texte client, il aura sa tâche.

### Sans critère, aucun résultat

Une recherche sans critère n'interroge même pas le moteur : le vivier entier n'est pas un résultat de
recherche. L'afficher laisserait croire que le travail est fait et pousserait à dévoiler au hasard —
sur un produit où chaque dévoilement coûte un crédit.

### Le masquage distingue « absente » de « présente mais masquée »

`SearchMaskedValue` porte les trois cas : valeur en clair, points flous si la coordonnée existe,
tiret si elle n'existe pas. Les points sont **hors ordre de lecture** et la mention « masqué » les
remplace pour les lecteurs d'écran — le masquage vaut aussi pour l'accessibilité. Le masque lui-même
vit dans le composant : c'est une convention d'affichage posée sur un placeholder, jamais de la donnée.

### `t` rejoint le contrat de `useLocale`

Les libellés d'onglets et les en-têtes de colonnes se composent en JavaScript. Sans `t` dans le
wrapper, chaque écran rouvrait `@nuxtjs/i18n` pour son compte — ce que `useLocale` existe pour éviter
(la règle `encapsulation-i18n` l'interdit, à raison).

## Livré

- `/recherches` : la collection en cartes (critères en chips, partage et lecture seule, nombre de
  profils), avec ses états chargement / erreur / vide.
- `/recherches/:uuid` : la Visionneuse — critères en barre haute, pile de cards `scroll-snap`
  (badges acquis seulement, coordonnées masquées, compétences), panneau de contrôle portant la
  recherche en cours et **la shortlist**.
- `/recherches/:uuid/table` : la Table — `UTable` sticky, 8 colonnes, **7 vues d'état** avec compteurs
  calculés sur le jeu entier, vérifiés en tête, clic ligne → inspecteur.
- Couche de données : relais BFF, stores `searches` et `candidates`, composables, et le métier
  pur testé (`presence`, `views` — 13 tests).

## Reste à faire (hors périmètre de cette tâche)

- **Les gestes** : dévoiler (unitaire et en lot, modale de coût), favoriser, shortlister, exporter.
- **Éditer les critères** depuis la barre haute (aujourd'hui ils s'affichent).
- **Migrer vers l'orchestrateur de bootstrap** (`bootstrap/loaders.ts` + registre), arrivé sur
  `develop` avec les tasks 055b et suivantes — les composables font encore le fetch eux-mêmes.
- **Pagination** : le jeu est servi en une page (30 profils). La sélection et la shortlist devront y
  survivre.
- Colonnes manquantes faute de contrat : sélection, favori (l'étoile), « ouvert aux opportunités ».
