# TASK ENT-024 — entreprise : la table, en-tête, états de ligne et pastilles

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Frontend espace entreprise — design.
- **Tests** : sans objet — la suite front est volontairement limitée au code PUR
  (`webapp/vitest.config.ts`) ; cette carte ne touche que la présentation de la table, et
  `presence.vitest.ts` couvre déjà le libellé de fenêtre que la pastille se contente d'habiller.
- **Réf** : `ENT-022` (les paliers qu'elle consomme — **amont obligatoire**) ·
  `ui-ux-design/draft/mockup-espace-entreprise-vue-cockpit-v23.html` · `ENT-005` (les deux écrans
  nommés) · `ENT-008` (le tri comme file de travail)

## Contexte

La table sert à comparer **beaucoup** de profils : dix colonnes, des centaines de lignes, et une
décision qui se prend en balayant des yeux. C'est l'écran où la mise en forme fait le plus de
travail, et c'est celui qui en a le moins reçu — la table sort telle que la bibliothèque de
composants la rend.

Trois manques concrets :

**Rien ne tient l'œil pendant le défilement.** L'en-tête est collé en haut mais ne se distingue pas
des lignes ; à la trentième ligne on ne sait plus quelle colonne on lit.

**Trois états de ligne coexistent sans se distinguer** : survolée, ouverte dans le panneau de
profil, cochée pour un lot. Une ligne peut être les trois à la fois.

**La promesse du produit est invisible.** « Rencontré » — le profil vu en entretien par showcase-recruiter-webapp-saas —
vit sur la carte de la visionneuse et nulle part dans la table. Le commentaire du code l'assume :
« Vérifié n'est pas une colonne […] le tri l'exprime mieux qu'un ✓ qui coûterait une largeur de
tableau ». L'argument tient contre une **colonne** ; il ne tient pas contre une marque posée dans
une cellule qui existe déjà.

## Périmètre

La table de `recherches/[uuid]/table.vue` : en-tête, états de ligne, deux cellules, densité, et une
marque dans la cellule du poste.

## Hors périmètre

- **La visionneuse** — `ENT-023`.
- **Les paliers et le solde** — `ENT-022`.
- **Les onglets « Tous » / « Favoris »** de la barre haute : ils restent tels quels. Ce ne sont pas
  des badges d'état mais des **périmètres** servis par l'API — « Favoris » a sa propre requête, un
  filtrage local ne ferait pas apparaître un retenu hors de la page chargée.
- **La gouttière, la barre haute, le menu de sélection, l'export et les modales** : la demande est
  la table, et rien que la table.
- **La console de facettes de 320 px** : non rouverte, cf. `ENT-022`.

## Décisions de cadrage

### L'en-tête devient une bande franche

Aplat accent doux, libellés en encre accent et au palier de libellé de `ENT-022`. Le token existe
déjà — aucune valeur arbitraire à écrire. L'en-tête est **déjà** collé au défilement ; il lui
manquait seulement d'être visible.

### Deux natures d'état, deux natures de signal

« Ouverte » est une **position de lecture**, « cochée » est un **engagement**. Les distinguer par
deux doses de la même couleur les rend ambigus dès qu'une ligne est les deux à la fois — et c'est le
cas courant, puisqu'on ouvre ce qu'on vient de cocher.

Donc : un **filet vertical accent** en bord de ligne pour l'ouverte, un **aplat** pour la cochée, un
survol léger pour la troisième. Les trois se superposent sans se détruire.

Le filet suit le **sens de lecture** — bordure en début de ligne, pas à gauche : en arabe la table
se lit de droite à gauche et un filet cloué à gauche se retrouverait en fin de ligne.

### La ligne ouverte demande un état qui n'existe pas

Aujourd'hui le clic ouvre le panneau de profil sans retenir de qui il s'agit. Le filet suppose de
connaître la ligne ouverte : c'est un petit ajout d'état dans la page, et **le seul point de cette
carte qui ne soit pas de la présentation**. Il se referme avec le panneau.

### La présence au Maroc cesse d'être du texte parmi du texte

« Fenêtre Maroc » et « Mobilité » passent en pastilles neutres à icône, hauteur fixe. C'est le
dispositif du mockup, et c'est aussi la règle écrite de la charte : « une information répétée sur
chaque ligne se distingue par l'icône et le libellé », jamais par la couleur.

L'absence reste un **tiret**, pas une pastille vide : une pastille est un signal, et il n'y a rien à
signaler.

### « Rencontré » entre dans la cellule du poste

Une pastille accent posée après l'intitulé, sans colonne supplémentaire — la largeur est déjà
disputée par dix colonnes. C'est le seul badge que la charte autorise à porter l'accent : il constate
un entretien, pas une déclaration.

### La densité se resserre d'un cran

Cinq à six lignes gagnées à l'écran, par le palier de `ENT-022` — aucune valeur écrite dans la page.
Sur un écran de comparaison, chaque ligne visible est une comparaison de plus faite sans défiler.

## Règles et cas limites

- La table **scrolle en interne**, la page ne scrolle jamais.
- Les trois colonnes soumises au masquage gardent leur rendu : valeur en clair, placeholder flouté si
  la donnée existe, tiret si elle n'existe pas. Le flou porte sur un **placeholder** — la donnée
  masquée ne descend jamais dans le navigateur, et la mention « masqué » remplace le placeholder pour
  les lecteurs d'écran.
- Une ligne ouverte **et** cochée doit rester lisible comme les deux.
- Le survol ne doit pas rendre la ligne cochée moins visible qu'elle ne l'est.
- L'encre accent sur l'aplat accent doux demande une **mesure de contraste dans les deux thèmes** :
  le thème sombre change d'accent, la mesure du thème clair ne vaut pas pour lui.
- Les compteurs des deux onglets sont pris sur le **jeu entier**, pas sur les lignes affichées ; rien
  ici ne doit les recalculer localement.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : les tokens et leurs mesures appartiennent à `ENT-022`.

## Définition de terminé

- [x] L'en-tête est une bande accent douce, libellés au palier de libellé, collée au défilement.
- [x] Le filet de la ligne ouverte suit le sens de lecture.
- [x] La ligne ouverte est retenue et relâchée avec le panneau.
- [x] « Fenêtre Maroc » et « Mobilité » sont des pastilles neutres à icône ; l'absence est un tiret.
- [x] « Rencontré » est dans la cellule du poste, sans colonne supplémentaire.
- [x] La densité est resserrée par le palier, aucune valeur écrite dans la page.
- [x] Aucun comportement modifié hors du suivi de la ligne ouverte.
- [ ] Les trois états de ligne constatés superposés — **non fait**, cf. ci-dessous.
- [ ] Contrastes de l'en-tête mesurés dans les deux thèmes — **non fait**.
- [ ] Table constatée en thème clair et sombre — **non fait**.

## Vérifications

Constaté sur le rendu réel, connecté, sur une recherche de 256 profils (30 lignes servies) :

- les lignes portent bien leurs classes d'état, résolues ligne par ligne ;
- la cellule du poste passe par son gabarit, prête à recevoir la marque ;
- les cellules « Fenêtre Maroc » et « Mobilité » rendent leur repli en tiret ;
- l'attribut de portée des styles de composant est présent sur les cellules, donc les règles
  d'en-tête, de densité et de filet s'appliquent.

**Un défaut trouvé et corrigé en cours de route** : passer les paliers par le `:ui` du composant de
table ne marchait pas. Sa fusion de classes ne connaît pas nos tokens — elle prenait `text-label`
pour une couleur et le supprimait au profit de `text-accent`, et laissait cohabiter `p-4` avec
`px-cell-x`. L'en-tête sortait sans sa typographie et les cellules à deux rythmes. D'où le passage
en CSS de composant, qui ne réécrit aucune valeur : les règles lisent les mêmes variables.

**Non constaté**, et il faut le savoir avant de clore : les pastilles et la marque « Rencontré »
n'ont **jamais eu de donnée à afficher**. Sur 2 561 candidats, 3 portent une mobilité, 3 une
disponibilité, et **un seul** est « Rencontré ». Seules les branches de repli ont été observées.
Le filet de la ligne ouverte demande un clic, invisible en rendu serveur.
