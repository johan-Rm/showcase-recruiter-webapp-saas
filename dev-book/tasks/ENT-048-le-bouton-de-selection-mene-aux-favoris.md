# TASK ENT-048 — entreprise : « Ma sélection » mène aux favoris, pas à la liste entière

- **Créée le** : 2026-08-05.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — « Ma sélection » porte l'étoile et ouvre la Table sur son
  onglet « Favoris » ; la vue vit désormais dans l'adresse, qui devient partageable et rechargeable.
- **Type** : Frontend entreprise — navigation, et un état d'onglet qui remonte dans l'adresse.
- **Tests** : unitaires dans `webapp/services/search/views.vitest.ts`, sur la lecture du paramètre
  d'URL — **l'exemption prévue au cadrage est levée**, cf. *Écart au cadrage*. Le rendu et la
  navigation restent non couvrables : le webapp n'a aucun harnais de test de composant, et la preuve
  y est une **capture** du bouton et de la Table qu'il ouvre.
- **Réf** : `ENT-047` (la console dont ce bouton est le pied) · `ENT-036` (les deux outils, deux
  routes) · `recherches/[uuid]/table.vue` · `ControlPanel.vue` ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

Un recruteur parcourt sa recherche, retient cinq profils, puis clique « Ma sélection » en pied de
console. Il arrive sur la Table ouverte sur **« Tous »** — deux cent vingt-cinq lignes — où il doit
repérer puis cliquer un second onglet pour voir les cinq qu'il vient de garder.

Le geste est pourtant sans ambiguïté : on clique « Ma sélection » **parce qu'on veut voir sa
sélection**. Le bouton est le seul aplat accent de l'écran, la sortie vers l'autre outil ; il mène à
côté de sa promesse.

**Et l'onglet n'est qu'un état local.** `view` est un `ref('all')` dans la page : recharger la Table
la ramène sur « Tous », et l'adresse d'une vue filtrée ne peut être ni partagée à un collègue, ni
mise en favori du navigateur, ni retrouvée dans l'historique. C'est un défaut qui précède ce bouton
et que le corriger règle.

## Périmètre

- **L'onglet de la Table se lit et s'écrit dans l'adresse** — `?vue=favoris` —, pour **toutes** ses
  vues et pas seulement les favoris.
- **Le bouton du pied prend l'étoile** et pointe sur la Table déjà filtrée.

## Hors périmètre

- **Le libellé du bouton**, qui ne change pas : « sélection » est le mot du produit pour ce qu'on
  emporte vers la Table, et le remplacer ici le désynchroniserait du reste.
- **Le compte des retenus dans le bouton** : la case « Favoris » de l'entête le porte déjà, à trois
  centimètres.
- **Le contenu de la Table** — colonnes, tri, export, sélection multiple.
- **La route**, qui reste unique : un onglet n'est pas une page.

## Décisions de cadrage

### L'onglet monte dans l'adresse, et c'est ce qui rend le lien possible

Un lien ne peut pas pointer sur un état local. Faire pointer le bouton « sur les favoris » suppose
donc que la Table sache s'ouvrir sur un onglet donné — et une fois qu'elle le sait, l'adresse
devient partageable et rechargeable, ce qui corrige au passage un défaut qui n'a rien à voir avec ce
bouton.

Un **paramètre** et non une seconde route : les deux outils sont deux routes, mais les vues d'un
outil sont des vues, pas des pages. Créer `/table/favoris` ferait de la navigation avec ce qui est
un filtre.

### L'étoile plutôt qu'un mot de plus

C'est **la même étoile** que le geste « Retenir » de la fiche profil. Le lien se lit alors sans
légende : on reconnaît l'icône du geste qu'on vient de poser cinq fois. Un libellé « Mes favoris »
aurait dit la même chose en désaccordant le vocabulaire du produit.

## Règles et cas limites

- **Un paramètre absent ou inconnu ouvre « Tous »** : une adresse tronquée ou une vue supprimée ne
  doit pas produire un écran vide.
- **Changer d'onglet met l'adresse à jour**, sans empiler une entrée d'historique par clic — sinon
  le bouton « précédent » du navigateur devient inutilisable après trois bascules.
- **L'onglet « Favoris » se désactive déjà quand rien n'a été retenu.** Arriver dessus avec zéro
  retenu doit donc afficher l'état vide de la Table, pas une bascule silencieuse vers « Tous » : le
  recruteur a demandé ses favoris, on lui dit qu'il n'en a pas.
- **Aucun texte en dur** : le libellé du bouton passe déjà par le catalogue, l'icône n'a pas de texte.
- **Le bouton reste le seul aplat accent de l'écran** — l'étoile s'y pose, elle n'ajoute pas une
  seconde couleur.

## Documentation impactée

- **Le commentaire de `ControlPanel.vue`** sur le pied, qui décrit la sortie vers l'autre outil.
- **`arborescence-entreprise.md`** — à vérifier : elle décrit la Table comme « les favoris retenus,
  en lignes » et pourrait mentionner l'adresse de ses vues.
- **Vérifié à l'écriture, non impacté** : `i18n.md` (aucune clé nouvelle), `composables-standard.md`,
  `services-standard.md`.

## Définition de terminé

- [x] « Ma sélection » porte l'étoile et ouvre la Table **sur l'onglet Favoris** — capture à l'appui.
- [x] L'adresse porte la vue ; la recharger rouvre le même onglet.
- [x] Un paramètre absent ou inconnu ouvre « Tous ».
- [x] Trois bascules d'onglet ne rendent pas le bouton « précédent » du navigateur inutilisable.
- [x] Arriver sur « Favoris » sans aucun retenu affiche l'état vide, pas un retour sur « Tous ».

## Vérification

### Le parcours complet, dans un navigateur réel

Recherche `019fd0eb-4022`, 212 résultats. Chaque ligne est une observation, pas une intention :

```
1. bouton du pied  : "Ma sélection"
   destination     : /recherches/019fd0eb-…/table?vue=favoris
   porte une étoile : ✓
2. adresse après clic : /recherches/019fd0eb-…/table?vue=favoris
   onglet actif       : "Favoris 0"   → état vide affiché, PAS de retour sur « Tous »
3. après rechargement : …/table?vue=favoris | onglet "Favoris 0"
4. "?vue=nimportequoi" → onglet "Tous 212"
   "(aucun paramètre)" → onglet "Tous 212"
```

### L'historique ne se remplit pas d'onglets

```
écran de départ        : /recherches
entrée sur la Table    : …/table?vue=favoris   profondeur historique = 3
après bascule « Tous » : …/table               profondeur historique = 3
l'historique n'a PAS grandi : ✓
« précédent »          : /recherches           ramène HORS de la Table : ✓
```

C'est la preuve du `replace` : sans lui, « précédent » aurait défait un onglet au lieu de quitter
l'écran.

### Le rendu serveur ouvre déjà le bon onglet

Lu directement dans le HTML servi, sans navigateur — donc avant toute hydratation :

```
"?vue=nimportequoi" → Tous 225 ←ACTIF | Favoris 0
"(rien)"            → Tous 225 ←ACTIF | Favoris 0
"?vue=favoris"      → Tous 225 | Favoris 0 ←ACTIF
```

### Captures

Le pied de console avec son étoile, et la Table ouverte sur « Favoris » — état vide affiché,
onglet « Favoris » actif et souligné.

### Sorties fraîches

```
vitest        16 fichiers, 203 tests passés (203 = 197 + 6 nouveaux)
conventions   13 règles, aucune violation
```

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `services/search/views.ts` | `viewSlug` et `toSearchView` — le nom public d'une vue, et sa lecture tolérante |
| `views.vitest.ts` | Les six cas de lecture du paramètre, dont le paramètre répété |
| `pages/recherches/[uuid]/table.vue` | La vue passe d'un `ref` local à un `computed` lu/écrit dans l'adresse |
| `pages/recherches/[uuid]/index.vue` | Le pied de console pointe sur la Table filtrée |
| `ControlPanel.vue` | L'étoile sur le bouton de sortie |
| `arborescence-entreprise.md` | Les vues de la Table sont dans son adresse, sans être des routes |

## Reste à faire (hors périmètre)

- **Le compte de favoris tombe à zéro après hydratation** — défaut **antérieur et indépendant**,
  prouvé tel : la Visionneuse, dont le code est déjà commité, perd le même favori à la même seconde
  (`Favoris 1` au rendu serveur, `Favoris 0` six secondes plus tard), **sans aucun appel réseau entre
  les deux**. L'API rend pourtant bien un favori. Conséquence directe sur cette carte : le lien
  qu'elle pose mène à un écran vide tant que ce défaut vit, et l'onglet « Favoris » reste désactivé
  côté client. À ouvrir en propre, sur cette lane.
- **L'entête de la Table annonce « Tous les profils de la recherche courante »** quel que soit
  l'onglet. Antérieur — la bascule d'onglet existait déjà —, mais plus visible maintenant qu'on
  atterrit directement sur « Favoris ».
- **L'état vide dit « Aucun profil dans cette recherche »** sous l'onglet « Favoris », alors que la
  recherche en compte deux cent douze. Le message ne connaît pas la vue.

## Écart au cadrage

### L'exemption de tests est levée : une fonction pure est apparue

La carte exemptait, et le raisonnement tenait : « le vrai risque — que le lien pointe au mauvais
endroit — n'est pas couvrable en unitaire ». Il reste vrai, et cette partie-là est prouvée en
capture.

Mais faire monter la vue dans l'adresse a produit une **fonction pure** que le cadrage n'avait pas
prévue : lire un paramètre d'URL et le ramener à une vue connue. Elle porte exactement les cas
limites que la carte énumère — absent, inconnu — plus un que l'écriture a fait apparaître : le
paramètre **répété** (`?vue=favoris&vue=tous`), qui arrive en tableau. Six tests, quelques
millisecondes.

S'auto-exempter sur ce code-là aurait été un abus de l'exemption, pas son application.

### La vue par défaut ne s'écrit pas dans l'adresse

Non tranché par le cadrage, qui parlait de `?vue=favoris` sans dire ce que devient « Tous ».
`?vue=tous` aurait été le choix symétrique ; il laisse un paramètre inutile sur l'adresse la plus
courante, et fait que revenir sur « Tous » ne rend pas l'adresse d'où l'on venait.

`viewSlug('all')` rend donc `undefined`, ce qui **retire** le paramètre. L'absence et « Tous » sont
la même chose — ce que la règle « un paramètre absent ouvre Tous » disait déjà dans l'autre sens.

### Le nom public n'est pas le nom interne

`favorites` à l'intérieur, `favoris` dans l'adresse. Une adresse se lit par-dessus l'épaule de qui
la partage, et mélanger les deux langues sous ses yeux n'a pas de justification. Le test verrouille
les deux sens : `?vue=favorites` n'ouvre **pas** l'onglet, sinon deux adresses désigneraient la même
vue et l'une ne serait documentée nulle part.
