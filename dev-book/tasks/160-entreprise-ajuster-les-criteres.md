# TASK 160 — entreprise : ajuster les critères sans quitter ses résultats

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — vérifié en session réelle sur le dev.
- **Type** : Frontend espace entreprise.
- **Réf** : `arborescence.md` (« changer un critère rejoue la recherche là où l'on est »),
  task 159 (la création).

## Contexte

La barre haute affichait les critères sans permettre d'y toucher. Une recherche mal cadrée était donc
à jeter : il fallait retourner à l'accueil et tout ressaisir, alors que le cadrage veut qu'on
**ajuste en lisant** — c'est la raison d'être de cette barre.

## Décisions

### On modifie LA recherche, on n'en crée pas une seconde

`PATCH` sur la même ressource, et l'API recalcule son jeu dans la foulée. Créer une nouvelle
recherche à chaque ajustement aurait rempli la collection de brouillons, et fait perdre les favoris
posés en route.

### Deux gestes, deux poids

Retirer un critère se fait **d'un clic sur sa puce** : c'est l'ajustement le plus courant — une
recherche qui ne ramène rien s'élargit en enlevant. Ajouter ou changer passe par un panneau
d'affinage, qui **se superpose** sans avoir d'adresse : le fermer ramène exactement où l'on en était.

### Le formulaire d'affinage ne réécrit QUE ses facettes

Une recherche née du langage naturel porte des facettes que ce formulaire ignore — langue, pays,
disponibilité. Remplacer la liste entière les effacerait **en silence**, alors que le recruteur croit
n'avoir touché qu'un intitulé de poste. `mergeWithPrimary` ne remplace donc que `job`, `q` et
`educationLevel`, et préserve le reste.

### Une recherche partagée ne s'ajuste pas

Les puces ne portent pas de croix et le bouton d'affinage n'existe pas quand la recherche n'est pas
la sienne : un seul auteur, pas de co-édition. Le destinataire duplique s'il veut la retravailler.

## Vérification

Sur le dev, en session réelle : retirer le critère `MASTER` d'une recherche à deux critères l'a fait
passer de **72 à 97 profils** — le jeu est bien rejoué, pas seulement la liste des puces.

## Livré

- `services/search/criteria.ts` : `toPrimaryForm`, `mergeWithPrimary`, `withoutCriterion` — 3 tests
  de plus.
- `base/server/api/searches/[id].patch.ts` : le relais, qui n'envoie que les clés présentes (un
  `name: ''` non demandé réactiverait l'auto-nommage au passage).
- `useSearch().updateCriteria()` : PATCH puis relecture du jeu.
- La barre haute : puces retirables, panneau d'affinage en surcouche, formulaire principal réutilisé
  et pré-rempli.

## Reste à faire (hors périmètre de cette tâche)

- **Les critères que le formulaire ne porte pas** (langue, pays, séjour au Maroc, disponibilité) ne
  sont ajustables que par retrait. Les rendre modifiables demande les filtres secondaires du cadrage.
- **Renommer une recherche** à la main, et le retour à l'auto en vidant le champ.
