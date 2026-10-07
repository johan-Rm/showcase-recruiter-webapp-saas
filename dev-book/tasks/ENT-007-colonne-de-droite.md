# TASK ENT-007 — entreprise : la colonne de droite, repère et récolte

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-29 — rendu vérifié sur le dev.
- **Type** : Frontend espace entreprise.
- **Dépend de** : `API-005` (les critères non trouvés).
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

La colonne fait déjà l'essentiel de ce qu'elle doit faire : elle nomme la recherche, puis laisse les
favoris occuper tout le reste — retenir est le seul geste de l'écran, la liste est donc l'objet
principal et non un compteur. Quatre ajustements, et un défaut à corriger.

## Le défaut : une récolte qu'on ne peut pas relire

**Les favoris s'affichent masqués.** C'est cohérent — on retient avant de payer — mais douze profils
retenus donnent **douze lignes indistinctes**. Le recruteur voit qu'il a retenu douze personnes sans
pouvoir dire lesquelles, ni en retirer une en connaissance de cause.

Il faut de quoi distinguer **sans dévoiler** : poste, années, ville.
`Développeur Symfony senior · 11 ans · Lyon` identifie une décision sans identifier une personne.

## Décisions

### Le total quitte la colonne, un repère le remplace

Le nombre de profils monte dans la barre haute (`ENT-005`). Ce qui le remplace n'est pas un autre
compte mais une **position** — `3 / 256`. La barre haute dit la taille du jeu, la colonne dit où on
en est dedans.

### Une barre de progression fine

Posée en **bordure basse de l'en-tête**. Idée reprise du mockup, et elle est bonne : une information
permanente qui n'occupe aucune place.

### Les critères non trouvés

Avec le OU sur les mots-clés, un profil peut entrer dans le jeu en ne satisfaisant qu'un critère sur
quatre. Savoir **lesquels manquent** est ce qui permet de trancher.

Ils vont ici et pas sur la carte, en vertu d'une règle simple : **ce qui est trouvé se surligne là où
il est, ce qui manque se liste dans la colonne** — une absence n'a pas d'endroit où être surlignée.
La structure de la colonne reste stable, seul ce bloc suit la carte.

**Le libellé doit rester factuel** : *non trouvé dans le profil*, jamais un jugement de compétence.
Le résumé est vide sur 98,5 % des profils et les compétences sont déclaratives : affirmer une absence
sur cette donnée ferait écarter de bons profils.

### Une sortie vers « Ma sélection »

La colonne montre ce qu'on a retenu et n'offre aucune porte vers l'écran qui l'exploite. Comme les
deux écrans sont les deux moments d'une même recherche, la transition appartient à cette colonne — un
bouton en pied, pleine largeur.

### Retirer un favori depuis la liste

Aujourd'hui elle est en lecture seule : se tromper au profil 40 oblige à le retrouver dans la pile.

### Ce qu'on ne reprend pas du mockup

Le triptyque **Vus · Favoris · Dévoilés**. « Favoris » est déjà dit par la liste elle-même, et
« Dévoilés » relève du budget, qui a sa place dans la topbar avec les crédits.

### Il fallait d'abord savoir quelle carte est sous les yeux

Deux des cinq apports en dépendent — la position, et les manques **du profil courant**. Rien ne le
portait : la page connaissait le jeu, pas l'endroit où l'on en était.

`useVisibleProfile` lit la **position de défilement** plutôt que d'observer chaque carte. La pile est
en `scroll-snap`, donc une carte occupe l'écran à la fois, et comparer les `offsetTop` reste juste
même à hauteurs inégales. Un observateur par carte aurait demandé de suivre des références au fil
d'un `v-for` — plus de mécanique pour le même résultat.

`useScroll` de VueUse et non un `addEventListener` : SSR-safe, retiré tout seul, et c'est l'attendu
du projet (verrou CI `app-prefer-useeventlistener`).

C'est aussi la brique dont `ENT-008` aura besoin pour marquer « vu ».

### Les écartés attendent d'exister

Le lien discret vers la corbeille est tranché, et l'API sait déjà la servir (`?scope=dismissed`).
Mais **aucun état d'écart n'existe côté front** : le lien n'aurait rien à compter ni à ouvrir. Il
part avec `ENT-008`, qui crée le geste.

## Vérification

Rendu sur le dev, recherche à deux mots-clés dont un seul est satisfait :

- la **position** `1 / 1` et la **barre de progression** en bordure d'en-tête ;
- le bloc **« Non trouvé dans ce profil »** listant le critère non satisfait ;
- les favoris décrits par poste · ancienneté · ville, avec le retrait à portée ;
- la **sortie vers « Ma sélection »** en pied, pleine largeur.

Lint, format, type-check des quatre espaces, conventions, **42 tests** — verts.
