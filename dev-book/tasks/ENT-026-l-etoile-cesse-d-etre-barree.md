# TASK ENT-026 — entreprise : l'étoile cesse d'être barrée

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Frontend espace entreprise — correction de design.
- **Tests** : sans objet — la suite front est limitée au code PUR (`webapp/vitest.config.ts`), et
  cette carte ne change qu'un nom d'icône. La preuve est une capture d'écran.
- **Réf** : `ENT-025` (les captures qui l'ont révélé) · `ENT-006` (la carte comme résumé de
  décision) · [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)

## Contexte

Sur la table, la colonne « retenir » affiche **une étoile barrée sur chaque ligne** tant que le
profil n'est pas favori. Vingt lignes à l'écran, vingt étoiles barrées : une colonne de négations.
Sur la carte de parcours, le même dessin met une icône barrée dans le bouton « Retenir » — l'action
la plus courante de l'écran s'annonce par un symbole d'interdiction.

C'est la règle de la charte appliquée aux badges, et elle vaut pour les icônes : **un signe nomme un
attribut acquis, jamais son absence**. « Non rencontré » n'existe pas ; « non retenu » non plus. Le
vide est déjà le signal.

Le défaut préexistait aux passes de design ; les captures d'écran de `ENT-025` l'ont rendu évident.

## Périmètre

Le geste « retenir », partout où il se dessine : la carte de parcours et la colonne de la table.

## Hors périmètre

- Le badge « Favori » de la rangée d'état sur la carte, qui utilise déjà l'étoile pleine et n'a pas
  de variante négative.
- La liste des favoris dans la colonne de droite, dont la pastille est déjà correcte.
- Le comportement : rien ne change à ce que fait le clic.

## Décisions de cadrage

### Une seule étoile, deux couleurs

Le contraire d'une étoile n'est pas une étoile barrée : c'est **une étoile éteinte**. Les deux états
partagent donc le même dessin, et ne se distinguent que par la couleur et le remplissage du fond —
neutre en attente, accent une fois retenu. C'est ce que fait le mockup, et c'est ce que fait
n'importe quelle liste de favoris.

L'icône reste monochrome : la bibliothèque rend les icônes en masque, un remplissage n'aurait aucun
effet. La distinction passe donc par l'encre et par la variante du bouton, pas par le tracé.

### Le libellé continue de porter le sens

« Retenir » / « Retenu » restent : c'est le texte qui dit l'état, l'icône ne fait que l'accompagner.
Un lecteur d'écran n'a jamais vu la barre de toute façon.

## Règles et cas limites

- Sur une recherche partagée (lecture seule), le bouton n'est pas rendu — inchangé.
- Dans la table, le bouton reste désactivé si la recherche n'est pas éditable, et l'étoile éteinte
  ne doit pas être confondue avec un état désactivé : le `disabled` porte déjà son opacité.

## Documentation impactée

Aucune page : la règle « un signe nomme un attribut acquis » est déjà écrite dans la charte, cette
carte l'applique.

## Définition de terminé

- [x] Aucune icône `star-off` dans l'espace.
- [x] Étoile neutre en attente, accent une fois retenu, sur les deux écrans.
- [x] Constaté en capture, dans les deux thèmes.
- [x] Aucun comportement modifié.

## Vérifications

Capture réelle de la table, connecté, sur la recherche de 1 076 profils : la colonne « retenir »
sort **vingt étoiles en contour neutre** au lieu de vingt étoiles barrées. Aucun profil n'étant
retenu sur ce jeu, l'état accent n'a pas de ligne à montrer — il n'est pas nouveau et son rendu
n'a pas changé.

`grep -rn "star-off"` sur l'espace ne rend plus rien.
