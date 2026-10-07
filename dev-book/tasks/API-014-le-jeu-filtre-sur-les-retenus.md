# TASK API-014 — le jeu de résultats filtré sur les retenus

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Backend API.
- **Réf** : `ENT-009` (« Ma sélection » réduite à deux onglets) · `API-007` (les états vu et écarté,
  d'où vient le `?scope=dismissed` dont celle-ci reprend la forme).

## Contexte

L'écran « Ma sélection » a deux onglets, **Tous** et **Favoris**. Leurs compteurs disent la vérité —
`resultIds` porte le jeu entier, et la liste des retenus est servie à part par
`GET /v1/searches/{id}/favorites`.

Les **lignes**, elles, s'arrêtent à la page servie. `GET /v1/searches/{id}/results` rend trente
profils par défaut ; sur un jeu de plusieurs centaines, un profil retenu au-delà de cette page est
**compté sans être affiché**. L'onglet annonce douze favoris et en montre huit.

Le client ne peut pas le combler seul : il connaît les identifiants des retenus, pas leurs cartes, et
les demander un par un ferait un appel par profil.

## Décisions

### `?scope=favorites`, sur le modèle existant

`GET /v1/searches/{searchId}/results?scope=favorites` sert le jeu **réduit aux profils retenus**,
dans l'ordre habituel de la recherche.

`ProfileScope` sait déjà restreindre à une liste d'identifiants — c'est ce que `?scope=dismissed`
utilise pour rouvrir la corbeille. La même mécanique, une source différente : les favoris de la
recherche au lieu des revues du recruteur.

### Trois valeurs, une seule dimension

`scope` reste un paramètre à valeur unique : la pile de travail par défaut, `dismissed`, `favorites`.
Elles nomment **d'où l'on regarde le jeu**, pas des filtres qui se combineraient — un « retenu et
écarté » n'a pas de sens à servir, et `ENT-011` tranche justement qu'il ne doit pas exister.

### Ce que le paramètre ne change pas

Ni le périmètre de la recherche, ni l'ordre, ni le repère de lecture : `markViewed` continue de
tomber à chaque lecture des résultats, quel que soit le `scope`. Un onglet n'est pas une autre
recherche.

## Critères d'acceptation

- [x] `?scope=favorites` rend exactement les profils retenus de la recherche, dans l'ordre du jeu.
- [x] Le `total` et `resultIds` du même appel portent sur ce sous-ensemble, pas sur le jeu entier.
- [x] Une valeur de `scope` inconnue se comporte comme l'absence de paramètre — la pile de travail.
- [x] Le destinataire d'une recherche partagée y accède en lecture, comme sur `/favorites`.
- [x] `api/openapi.json` régénéré : **aucune dérive**, la surface ne change pas.

## Mise en œuvre

**Un enum plutôt qu'un second booléen.** Le builder recevait `bool $dismissedOnly` ; un deuxième
drapeau aurait rendu représentable un « écartés ET retenus » qui n'a pas de sens. `SearchScope`
nomme trois POINTS DE VUE mutuellement exclusifs, et le `match` qui les traduit en périmètre est
exhaustif par construction.

**`ProfileScope` compose désormais deux listes** : une restriction (« seulement ceux-là ») et une
soustraction (« sauf ceux-là »). Les retenus se demandent en effet écartés déduits — sans quoi
« Favoris » cesserait d'être un sous-ensemble de la pile. Une restriction VIDE rend explicitement
zéro ligne : sans cette garde elle se serait évanouie et le jeu entier serait ressorti sous un
périmètre qui devait être vide.

Un profil écarté **reste retenu** : il quitte la récolte mais la corbeille le rend, favori compris.
Écarter n'est pas se dédire — c'est le client qui, lui, révoque le favori en écartant (`ENT-011`).

## Ce que cette tâche ne fait pas

**Le paramètre `scope` n'est pas déclaré dans le contrat.** Aucun paramètre de requête ne l'est dans
cette API — ni `page`, ni `perPage`, ni ceux du moteur : les providers les lisent sur la requête. En
documenter un seul en ferait l'exception plutôt que la règle. Les déclarer tous est une décision de
convention, qui mérite sa propre carte.
