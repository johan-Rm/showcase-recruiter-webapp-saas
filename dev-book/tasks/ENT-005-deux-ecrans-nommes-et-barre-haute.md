# TASK ENT-005 — entreprise : deux écrans nommés, et la barre haute qui les cadre

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 42 tests, rendu vérifié sur le dev.
- **Type** : Frontend espace entreprise — navigation et vocabulaire.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

Une recherche se travaille en **deux moments** : on parcourt ce qu'elle ramène et on retient, puis on
regarde ce qu'on a retenu. Les écrans portaient jusqu'ici des noms d'outils — « La Visionneuse » et
« La Table » — qui nommaient le dispositif plutôt que le moment.

## Décisions

### Les deux écrans prennent le nom de ce qu'on y fait

| Écran | Libellé |
| --- | --- |
| le jeu de résultats, profil par profil | **Parcourir les profils** |
| ce qui a été retenu | **Ma sélection** |

**Le mot « visionneuse » ne doit plus apparaître nulle part dans l'application.** Un seul libellé le
porte — `page.entreprise.viewer.title` — affiché à **deux endroits** : sur la carte d'une recherche,
à côté de « La Table », et dans la Table comme bouton de retour. La clé i18n peut rester `viewer`,
c'est le libellé qui change.

### La gouttière passe en veille

Conservée **visible**, vidée de son contenu, à **hauteur réduite** — un bandeau, pas une bande pleine
hauteur. Sur un portable, la carte compte chaque pixel, et la gouttière n'en prend plus que le
nécessaire pour tenir sa place.

### La barre haute porte trois choses

Les **critères en cours**, le **nombre de profils** du jeu, et le bouton d'**affinage** (critères
secondaires). Le compte migre donc du panneau de droite vers la barre haute.

### Les états quittent la barre haute

Les onglets d'état deviennent des **badges sur le profil** (cf. `ENT-006`). « Ma sélection » ne garde
que **deux onglets** : Tous et Favoris.

### La gouttière déclare sa veille, elle ne la déduit pas

`<Gutter standby />` — un booléen explicite plutôt qu'une hauteur déduite de la présence des slots :
un slot qui ne rend qu'un commentaire ou un fragment vide compte comme rempli, et la bande resterait
haute sans qu'on sache pourquoi. La page sait ce qu'elle a à dire, elle le déclare.

Elle **ne disparaît pas** : l'écran garde son rythme vertical, et ce qui reviendra s'y logera sans
déplacer le reste. La gouttière sert **quatorze pages** — seul l'écran de parcours passe en veille.

### La bascule quitte la gouttière pour la barre haute

Elle y vivait ; la gouttière se vidant, il fallait la reloger sans quoi le commit aurait laissé un
écran sans porte vers l'autre. Elle rejoindra le **pied de la colonne de droite** avec `ENT-007` —
c'est là qu'elle a du sens, à côté de ce qu'on emporte.

### Le compte migre vraiment

Il s'affichait **deux fois** après le premier jet — barre haute et colonne. Vu au rendu, pas à la
lecture du code. La colonne n'en garde rien : elle dira la **position dans le jeu**, qui n'est pas le
même renseignement.

### « Ma sélection » garde sa gouttière pour l'instant

Elle y porte le menu de la sélection en lot, que `ENT-009` reprendra en refondant cet écran. La
vider ici aurait déplacé un mécanisme sans le repenser.

## Vérification

- **Le mot « visionneuse » n'apparaît plus dans aucun texte affiché**, dans les trois langues — y
  compris dans la page squelette `template-recherches-uuid`, qui l'affichait en dur et qui est une
  route réelle. Les clés i18n gardent leur nom `viewer` : elles sont internes.
- Les deux boutons portent les nouveaux libellés dans les deux sens de navigation, vérifié au rendu.
- La barre haute affiche critères, nombre de profils et affinage ; le compte n'apparaît qu'une fois.
- Les commentaires de code ont suivi le vocabulaire : ils nommaient un écran qui n'existe plus.
- Lint, format, type-check des quatre espaces, conventions, **42 tests** — verts.
