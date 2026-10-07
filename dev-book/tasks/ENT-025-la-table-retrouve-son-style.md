# TASK ENT-025 — entreprise : la table retrouve son style

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Frontend espace entreprise — correction de design.
- **Tests** : sans objet — la suite front est limitée au code PUR (`webapp/vitest.config.ts`), et
  cette carte ne touche qu'un sélecteur CSS et une contrainte de largeur. Ce qui aurait attrapé le
  défaut n'est pas un test unitaire mais une **capture d'écran**, désormais faite.
- **Réf** : `ENT-024` (dont le style n'a jamais été appliqué) · `ENT-022` (les paliers consommés)

## Contexte

`ENT-024` a été mergée en annonçant un en-tête en bande accent, des libellés en micro-capitales, une
densité resserrée et un filet sur la ligne ouverte. **Rien de tout cela n'était visible.** La table
sortait avec l'en-tête par défaut de la bibliothèque, au corps courant, et une seule colonne à
l'écran.

Deux causes, et la première est une erreur de méthode autant que de code.

### Le sélecteur ne correspondait à rien

Le style vit dans un `<style scoped>` qui cible `.search-table :deep(th)`, et la classe
`search-table` était posée **sur le composant de table**. Vue n'applique l'attribut de portée à un
composant enfant que si celui-ci a une **racine unique** qui hérite des attributs ; la bibliothèque,
elle, répartit `class` à la main sur un `div` interne. La classe arrivait donc bien à destination,
l'attribut de portée non — et `.search-table :deep(th)`, qui compile en `.search-table[data-v-…] th`,
ne trouvait aucun élément.

Tout le CSS d'`ENT-024` était du **code mort** : en-tête, densité, filet.

La carte `ENT-024` affirmait pourtant « l'attribut de portée des styles de composant est présent sur
les cellules, donc les règles s'appliquent ». C'était une **déduction tirée du HTML servi**, pas une
observation. Une capture d'écran l'a démentie en une seconde.

### La colonne du poste mangeait la table

`headline` porte parfois un résumé entier — plusieurs centaines de caractères. Une cellule de tableau
s'élargit pour contenir son texte, donc `truncate`, posé sur un `span` sans borne, ne mordait pas :
la colonne s'étendait et les **neuf autres sortaient de l'écran**. Le défaut préexistait à `ENT-024`,
qui avait tenté une troncature sans lui donner de quoi s'appliquer.

## Périmètre

`recherches/[uuid]/table.vue` : la classe de portée et la borne de la cellule du poste.

## Hors périmètre

- La visionneuse, qui n'est pas concernée — son style vit dans les composants eux-mêmes.
- Les défauts **produit** que les captures ont révélés par ailleurs, listés en aval : ils
  préexistent et méritent leurs propres cartes.

## Décisions de cadrage

### La classe descend sur une balise écrite ici

`search-table` passe du composant de table à la `<section>` qui l'enveloppe — une balise du fichier,
donc porteuse de l'attribut de portée. Le sélecteur `:deep()` fonctionne alors sans rien changer aux
règles.

C'est préférable à un `<style>` non scopé : le style resterait global et fuirait sur toute table de
l'espace.

### La cellule du poste est bornée par un gabarit existant

`max-w-readable` plutôt qu'une largeur improvisée : le besoin est exactement celui du gabarit — une
ligne de texte qui reste lisible — et le design system interdit d'inventer une valeur dans un écran.
La troncature mord enfin, et les dix colonnes tiennent.

## Règles et cas limites

- Un `headline` vide affiche un tiret, inchangé.
- La borne ne s'applique qu'à cette cellule : les autres colonnes gardent leur largeur naturelle.
- Le filet de la ligne ouverte reste en bordure **logique** — il suit le sens de lecture.

## Documentation impactée

Aucune : les tokens et leur documentation appartiennent à `ENT-022`, inchangés.

## Vérifications

Captures d'écran réelles, connecté, sur la recherche de 1 076 profils, en 1600 × 1000 :

- **avant** — en-tête au corps courant sur fond de surface, une seule colonne visible, `headline`
  débordant de l'écran ;
- **après** — bande accent douce, libellés `POSTE / COMPÉTENCES · EXP. · NOM & PRÉNOM · E-MAIL ·
  TÉL. · FENÊTRE MAROC · VILLE ACTUELLE · MOBILITÉ` en micro-capitales accent, **dix colonnes
  visibles**, une vingtaine de lignes à l'écran, `headline` tronqué avec points de suspension.

Constaté dans les **deux thèmes** : en sombre, la bande passe à l'accent lime sur fond olive.

## Aval — ce que les captures ont aussi montré, hors périmètre

- **L'étoile « retenir » est barrée** (`i-lucide-star-off`) tant que le profil n'est pas favori :
  une colonne entière d'étoiles barrées dans la table, et un bouton « Retenir » à l'icône négative
  sur la carte. Un badge nomme un attribut acquis, jamais son absence — la même règle vaut pour une
  icône.
- **Le pays s'affiche en code brut** quand la ville manque : « MA », « CH », « FR », dans la colonne
  « Ville actuelle » comme dans la grille de faits de la carte.
- **La carte de profil est aux deux tiers vide** : les faits occupent le haut, les compétences sont
  collées en bas, et un large vide les sépare quand le profil n'a ni critères satisfaits ni projet
  de retour.
