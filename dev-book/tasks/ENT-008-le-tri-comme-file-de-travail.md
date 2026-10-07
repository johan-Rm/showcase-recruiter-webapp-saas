# TASK ENT-008 — entreprise : le tri devient une file de travail

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Dépend de** : `API-007` (les états vu et écarté, persistés et écrits en lot).
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

Un jeu de résultats compte couramment plusieurs centaines de profils — 256 sur la recherche d'essai.
Parcourus un par un, ils ne tiennent pas en une séance, et l'écran n'offre aujourd'hui **aucune
trace** de ce qui a déjà été examiné : le recruteur qui revient le lendemain recommence à l'aveugle.

## Décisions

### Trois états, et une pile qui se vide

**À voir · Retenu · Écarté.** Écarter **retire le profil de la pile**. C'est ce qui rend le tri
**fini** plutôt qu'infini : chaque décision réduit ce qui reste, au lieu de laisser une liste
immuable qu'on repasse indéfiniment.

Le geste est **réversible** : les écartés restent consultables et se rétablissent.

### « Vu » se mérite

Un profil est vu quand la carte est **restée à l'écran un instant** — de l'ordre d'une à deux
secondes — pas quand elle a été traversée.

La nuance décide de tout : dans une pile qui défile, tout ce qu'on traverse est techniquement vu. Un
défilement rapide du haut vers le bas marquerait les 256 profils en quelques secondes, et
l'information ne vaudrait plus rien — pire, elle mentirait, puisqu'elle sert précisément à savoir ce
qu'il reste à examiner.

Le seuil est arbitraire, et c'est assumé : il correspond à ce que le mot veut dire pour l'utilisateur.

### Reprendre au premier non vu

Un bouton qui saute là où on s'est arrêté. C'est la seule chose qui donne un sens à l'état « vu », et
sans elle il ne sert à rien de le conserver.

Il porte AUSSI le compte de ce qui reste — « Reprendre · 214 à voir ». Deux éléments côte à côte
diraient la même chose deux fois, et le reste-à-faire est justement ce qu'on veut lire avant d'y
retourner. Quand tout a été vu, il cède la place à une phrase : il n'y a plus rien à reprendre.

### Vu et écarté sont personnels

Les deux états appartiennent au recruteur, pas à la recherche (cf. `API-007`) : le destinataire d'une
recherche partagée peut la trier alors qu'il ne peut ni la modifier ni la favoriser.

**Non couvert ici — l'écart d'un collègue.** Faire dire à la carte *« Karim a écarté ce profil »*
suppose que l'API rende les revues des AUTRES membres, ce que `API-007` n'expose pas : la ressource
est filtrée sur le recruteur courant, et l'ouvrir demande une décision de confidentialité qui n'a pas
été prise. À rouvrir avec une carte API dédiée.

### Les écartés se retrouvent depuis la colonne

Un lien discret sous les favoris — « 12 écartés » — qui ouvre la corbeille. Présent sans peser : on
ne le cherche que si l'on croit s'être trompé, et la colonne est déjà l'endroit où l'on suit son
avancement. Un troisième onglet dans « Ma sélection » aurait regonflé un écran qu'on venait de
réduire, et l'écart se pose pendant le parcours, pas pendant la revue.

### Écarter enchaîne, retenir non

Écarter est une décision **finie** : le profil quitte la pile, rester dessus n'a plus de sens — on
avance au suivant. Retenir n'est pas la fin de la lecture : on peut vouloir dévoiler dans la foulée,
alors on reste.

Chaque geste enchaîne selon ce qu'il termine, plutôt que d'appliquer la même règle aux deux.

### L'écriture part en lot

Marquer chaque carte vue déclencherait un appel par profil — 256 pour un passage complet. Le client
accumule et pousse par paquets, y compris quand on quitte la page.

## Critères d'acceptation

- [x] Un défilement rapide ne marque aucun profil comme vu.
- [x] Un arrêt sur une carte le marque, et l'état survit au rechargement.
- [x] Écarter retire le profil de la pile ; les écartés restent consultables et se rétablissent.
- [x] « Reprendre » ouvre le premier profil non vu.
- [x] Le marquage n'émet pas un appel par profil.
- [x] Un profil ne peut pas être retenu et écarté en même temps — non tenu par cette tâche, réparé
      par `ENT-011` : écarter un profil retenu efface le favori.

## Mise en œuvre

Le seuil de « vu » vit dans la **card** (`ProfileCard.vue`) : un `useIntersectionObserver` à 50 % de
visibilité arme un délai de 1,2 s, que quitter l'écran annule. La card ÉMET, elle ne décide pas — la
page relaie au composable, qui accumule et pousse 1,5 s après le dernier profil franchi, ainsi qu'à la
sortie de l'écran.

Écarter **n'ordonne aucun défilement** : la card disparue, la suivante prend sa place sous le point
d'ancrage du `scroll-snap`. Le geste enchaîne parce que la pile s'est resserrée, là où retenir laisse
la card en place — la règle « écarter enchaîne, retenir non » tombe donc de la structure, sans code
de navigation.

Le filtrage des écartés a lieu dans `cardsInSearch` (service pur) et non dans la vue : la pile n'est
pas une liste filtrée à l'affichage, c'est ce qu'il RESTE à trier.

**La corbeille se DEMANDE.** L'API sort les écartés du jeu de résultats — `resultIds` ne les porte
pas — ils ne peuvent donc pas s'en déduire : `?scope=dismissed` les sert à part. Chargée avec l'écran
et non à l'ouverture du tiroir, sans quoi le compte des écartés resterait inconnu tant qu'on n'aurait
pas cliqué, et le lien qui le porte, invisible.

Conséquence sur le geste inverse : **remettre relit le jeu**. Le profil rendu ne figure dans aucun
`resultIds` local, et aucun état côté client ne peut l'y réinsérer à la bonne place — c'est le serveur
qui range. Un aller-retour sur un geste rare et correctif, plutôt qu'un ordre inventé.
