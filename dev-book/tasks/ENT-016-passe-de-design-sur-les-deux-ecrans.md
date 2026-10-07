# TASK ENT-016 — entreprise : passe de design sur les deux écrans

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise — design.
- **Dépend de** : `ENT-013`, `ENT-014`, `ENT-015` — on n'habille pas un écran auquel il manque un
  geste.
- **Réf** : `dev-book/cadrage/requirements/brand_guidelines.md` · `ENT-004` (les deux systèmes
  visuels qui y cohabitent).

## Contexte

Les deux écrans d'une recherche ont été construits en huit tâches, chacune jugée sur ce qu'elle
faisait — jamais sur ce à quoi elle ressemblait. Aucun n'a été confronté au guide de marque.

Le symptôme le plus net est dans le formulaire de recherche : des champs Nuxt UI **encadrés**
voisinent avec des champs maison **soulignés**, parce qu'`ENT-004` a pris les composants de la
bibliothèque pour ce que le système maison ne couvrait pas. C'est visible, et ce n'est qu'un exemple.

## Décisions

### Tranché — le trait MAISON est porté sur Nuxt UI

Les COULEURS étaient déjà un seul système : `nuxt-ui.css` remappe les échelles et les jetons
sémantiques de Nuxt UI sur ceux du kit. Ce qui divergeait n'était donc pas la palette mais la
**forme** : un champ Nuxt UI arrive encadré, un champ maison est un filet souligné.

C'est le trait maison qui l'emporte. Il est plus sobre, il porte la « séparation par le fond, jamais
par une bordure » du guide, et l'inverse aurait demandé de réécrire tous les formulaires au lieu de
restyler une famille de composants.

Portée volontairement étroite — une classe `.field-inline` posée par l'appelant. Un restyle global
des `U*` toucherait des écrans que personne n'a relus, et cette carte ne couvre que deux d'entre eux.

### Ce que le guide impose et qui n'a pas été vérifié

- la couleur ne code jamais une donnée : elle est réservée aux vues et à l'accent ;
- séparation par le fond, jamais par une bordure ni une ombre — sauf surcouche ;
- titres en Sora, interface en Geist ;
- « moins mais mieux » : des blancs généreux, la divulgation progressive.

### Les états qui n'ont jamais été dessinés

Le vide, le chargement, l'erreur et le refus existent en texte brut sur les deux écrans. Ce sont les
moments où l'utilisateur a le plus besoin d'être tenu, et ceux qu'on habille en dernier.

### Ce que cette passe ne fait PAS

Elle ne change aucun comportement. Un écran qui se met à faire autre chose sous couvert de design
rend la revue impossible : on ne saurait plus dire si une différence est voulue ou subie.

## Critères d'acceptation

- [x] Un seul système de champs sur les deux écrans, et le choix est écrit.
- [x] La couleur ne porte plus qu'un sens.
- [x] Vide, chargement et erreur sont dessinés sur les deux écrans.
- [x] Aucun comportement modifié : le diff ne touche que la présentation — à deux exceptions
      constatées et corrigées, ci-dessous.
- [x] Les deux écrans se relisent côte à côte comme un seul produit.

## Un seul accent

Cinq badges portaient l'accent sur la card : Rencontré, Nouveau, Au Maroc, Disponible, Favori. Cinq
accents ne hiérarchisent rien, et l'accent cesse de vouloir dire quelque chose.

**Seul « Rencontré » le garde** — c'est la promesse du produit, constatée et pas supposée. Les autres
informent, en neutre.

## Deux défauts trouvés en vérifiant, et corrigés

Ils dépassent la présentation, mais les laisser aurait consisté à habiller un écran cassé.

**Aucun des deux écrans n'avait de `h1`.** La première en-tête rencontrée sur le parcours était le
`h2` d'une card de profil : on entrait dans un document sans titre, par un chapitre. Le titre est
posé dans la barre haute — l'ordre du DOM compte, une en-tête placée dans la colonne arrive après la
pile — et reste `sr-only` : à l'œil, la colonne porte déjà le nom de la recherche.

**`useAsyncData` avertissait** que son gestionnaire ne rendait rien, et aurait rejoué l'appel côté
client faute de savoir qu'il avait abouti.
