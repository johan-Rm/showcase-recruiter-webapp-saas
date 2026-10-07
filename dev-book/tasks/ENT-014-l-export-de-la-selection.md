# TASK ENT-014 — entreprise : « Ma sélection » s'exporte

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-009` (les deux onglets, qui promettent l'export) · task 154 (l'API qui le sert).

## Contexte

L'export est l'un des trois objectifs cœur du vivier, et `ENT-009` l'annonce comme ce qui reste à cet
écran. **Il n'y est pas** : le menu de la sélection n'offre que « Retenir la sélection » et « Tout
désélectionner ».

L'API le sert depuis la task 154 : `POST /v1/searches/{searchId}/export`, qui rend un **fichier CSV**
et non une ressource — un CSV taillé pour s'ouvrir dans Excel en français.

## Décisions

### On exporte une SÉLECTION, pas un écran

Le corps de la requête porte `candidateIds` : c'est ce qui est coché qui part, pas la vue courante.
Un export qui suivrait l'onglet actif produirait deux fichiers différents selon l'endroit d'où on
clique, pour la même intention.

### Seuls les profils dévoilés sortent

Le writer ne masque rien : par construction, tout ce qu'il écrit est dévoilé. Un profil masqué dans
la sélection n'a donc rien à donner — l'écran le dit **avant** l'appel, en annonçant combien de
profils sortiront et combien seront ignorés, plutôt que de livrer un fichier plus court que prévu
sans explication.

### L'accusé RGPD est une case, pas une formalité

L'API exige `gdprAcknowledged` et refuse sans lui. Ce n'est pas une contrainte technique à satisfaire
en silence : exporter des coordonnées personnelles hors de l'outil engage celui qui le fait. La case
est décochée par défaut, et son libellé dit ce qu'elle engage.

### Le téléchargement passe par le BFF, le NOM par le client

Le relais fait suivre le CSV en texte. Il ne transmet PAS le nom du fichier : `apiFetchForUser` rend
un corps, pas une réponse, et lire l'en-tête `content-disposition` de l'API demanderait de dupliquer
sa logique de session et de rejeu du jeton pour le seul plaisir d'un nom.

La règle de nommage est donc recopiée côté client, en miroir de `SearchCsvWriter::filename` — et
testée. Si les deux divergent un jour, c'est le nom proposé au téléchargement qui change, rien de
plus.

## Critères d'acceptation

- [x] L'export part de la sélection cochée, depuis « Ma sélection ».
- [x] L'écran annonce combien de profils sortiront, et combien seront ignorés faute d'être dévoilés.
- [x] Sans accusé RGPD coché, le bouton ne part pas — et l'API refuse de son côté.
- [x] Le fichier arrive avec son nom lisible (`profils-<recherche>.csv`).
- [x] Une sélection sans aucun profil dévoilé explique pourquoi elle ne peut pas partir.

## Vérifié en réel

```
sans accusé RGPD  → HTTP 422
avec accusé       → HTTP 200 · content-type: text/csv; charset=utf-8

Nom;Poste;E-mail;Téléphone;Ville;Pays;Présence Maroc
Yassine El Amrani;Développeur backend PHP;yassine@example.com;+33600000000;Paris;FR;…
```

Le refus sans accusé vient de l'API, pas de l'écran : la case est une information donnée à
l'utilisateur, la garde est ailleurs.
