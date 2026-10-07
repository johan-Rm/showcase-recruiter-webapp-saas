# TASK ENT-055 — entreprise : renommer, dupliquer, partager, supprimer une recherche

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — une recherche se renomme, se duplique, se partage et se
  supprime depuis le menu de sa carte ; « Clôturer » y a migré. 6 tests neufs.
- **Type** : Frontend entreprise — quatre gestes s'ajoutent, deux routes de relais se créent.
- **Tests** : unitaires (vitest) sur ce qui se décide hors du rendu — les entrées de menu selon
  l'appartenance de la recherche et son état. Le reste — édition sur place, confirmation, bascule de
  partage — se prouve en capture et en requêtes réelles.
- **Réf** : **`ENT-050` — amont** (le calque de clic du titre, contrainte héritée) · **`ENT-051` —
  amont** (« Clôturer » y migre) · `webapp/base/server/api/searches/[id].patch.ts` ·
  `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  `webapp/spaces/entreprise/app/stores/searches.ts` (`removeSearch`, déjà présent) ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

Deux commentaires du code annoncent depuis des mois que « dupliquer, partager et supprimer viendront
avec leur propre tâche ». Renommer n'existe pas davantage. **L'API sert pourtant les trois** — la
suppression, la duplication et le partage — et le store sait déjà retirer une recherche de sa
collection.

Ce qui manque côté front est précis : **deux routes de relais** — suppression et duplication —, un
endroit d'où lancer ces gestes, et le renommage.

Sans elles, une recherche est un objet qu'on peut créer, parcourir et clôturer, mais jamais corriger
ni copier : un intitulé mal tapé s'y grave, et repartir d'une base demande de tout ressaisir.

## Périmètre

- **Le menu « ⋯ »** naît sur la carte, en haut à droite, avec cinq entrées : renommer, dupliquer,
  partager, **clôturer** et supprimer. **« Clôturer » y migre** depuis la ligne d'actions, où
  `ENT-051` l'avait posée faute de menu.
- **Renommer sur place**, dans le titre de la carte.
- **Dupliquer** : la copie apparaît dans la liste, on ne quitte pas l'écran.
- **Partager** : une bascule, qui devient « Ne plus partager » une fois active.
- **Supprimer** : confirmation qui **répète le nom** de la recherche.
- **Deux routes de relais** : suppression et duplication.
- Sur la carte d'un collègue : **pas de menu**, un lien « Dupliquer » direct.
- L'arborescence de l'espace entreprise, dont la ligne des gestes reste à jour.

## Hors périmètre

- **Toute évolution de l'API** : les trois opérations existent, cette tâche les consomme.
- **Le partage à une personne choisie** : le modèle n'a qu'un booléen d'organisation.
- **Une corbeille** : la suppression est définitive côté serveur, cf. *Décisions de cadrage*.
- **Le nom automatique** — c'est `ENT-053`.

## Décisions de cadrage

**Le menu naît maintenant, parce qu'il a enfin cinq entrées.** `ENT-051` avait posé « Clôturer » en
bouton plutôt que dans un menu, au motif qu'un menu à une entrée est un lien qu'on a caché. Le motif
tombe ici : cinq gestes rares méritent d'être regroupés, et la ligne d'actions retrouve les deux
seules portes qui comptent.

**Renommer se fait sur place, pas dans une fenêtre.** On voit ce qu'on renomme, et vider le champ
rétablit le nom automatique — règle déjà portée par l'API. **Contrainte héritée d'`ENT-050`** : le
titre porte un calque de clic qui couvre toute la carte ; l'édition devra le neutraliser le temps de
la saisie, sinon un clic dans le champ ouvrirait la recherche.

**Partager est une bascule, pas un écran.** Le modèle n'offre qu'un booléen : il n'y a personne à
choisir, et une fenêtre pour un oui/non ajoute une étape sans ajouter une décision. La carte porte
déjà l'état sous son titre.

**Supprimer demande confirmation, et la confirmation répète le nom.** Le vrai risque n'est pas de
supprimer sans réfléchir, c'est de **supprimer la mauvaise ligne** — la boîte doit donc dire
laquelle. Une annulation après coup a été écartée : l'API supprime pour de bon, il faudrait une
corbeille côté serveur, qui n'existe pas.

**Dupliquer laisse sur la liste.** On duplique pour repartir d'une base sans quitter ce qu'on
faisait — et depuis la carte d'un collègue, c'est la seule porte de sortie. Ouvrir la copie
arracherait l'écran sous les pieds.

**Pas de menu sur la carte d'un collègue.** Il s'y réduirait à « Dupliquer » : un menu à une entrée
n'est pas un menu. Le lien y figure directement, comme le montrait la maquette.

### Écart : le menu reprenait le focus au champ de renommage

Ouvrir « Renommer » faisait apparaître le champ, qui **disparaissait de lui-même** un dixième de
seconde plus tard. Mesuré image par image : en se fermant, le menu **rend le focus à son bouton
déclencheur** — comportement juste, et nécessaire à qui navigue au clavier. Mais le champ venait de
le prendre, et il lit ce départ comme « la saisie est finie » : il se validait à vide et se refermait.

```
champ=oui  focus=BUTTON
champ=oui  focus=INPUT      ← le champ prend le curseur
champ=oui  focus=INPUT
champ=non  focus=BUTTON[…]  ← le menu le lui reprend, tout se referme
```

Le menu ne reprend donc plus le focus **dans ce seul cas**, et la carte le lui rend elle-même quand
l'édition se termine — par Entrée comme par Échap. Sans cette contrepartie, le curseur retomberait
sur le document et la navigation au clavier repartirait du haut de la page.

C'est une **deuxième** conséquence du calque de clic d'`ENT-050`, que le cadrage n'avait pas vue : il
avait anticipé le clic, pas le focus.

### Écart : la carte ne disait plus qu'une recherche était partagée

Le cadrage affirme que « la carte porte déjà l'état sous son titre ». **C'est faux depuis `ENT-050`** :
la refonte de la carte a perdu cette ligne, dont la clé i18n existait pourtant toujours. Sans elle,
partager n'aurait eu **aucun effet visible** — le menu aurait changé de libellé, et rien d'autre. La
ligne revient sous le titre, sur la même place que l'appartenance et la clôture.

### Écart : la ligne des gestes de l'arborescence n'était pas à jour

La carte demandait de « vérifier qu'elle le reste, et rien de plus si c'est le cas ». Elle ne l'était
pas : **renommer** n'y figurait pas, précisément le geste que cette tâche ajoute.

## Règles et cas limites

- **Le menu passe au-dessus du calque du titre** et arrête la propagation du clic, sans quoi ouvrir
  le menu ouvrirait la recherche.
- **Le menu s'annonce avec le nom de la recherche** — « Actions sur *Développeur logiciel* » : sur
  une page qui en compte six, six boutons nommés « Actions » ne servent à rien à qui navigue à la
  voix ou au lecteur d'écran.
- **Il reste visible en permanence**, jamais au seul survol : sur un écran tactile il n'y a pas de
  survol, et le menu deviendrait introuvable.
- **Une recherche close n'accepte que « Rouvrir » et « Supprimer »** : le serveur refuse le reste en
  `409`, et l'écran ne propose pas ce qui sera refusé. C'est le geste qui rend enfin **atteignable**
  le traitement du refus écrit dans `ENT-051`.
- **Renommer avec un champ vidé** réactive le nom automatique ; l'écran doit le dire, sinon
  l'utilisateur croit avoir effacé le nom.
- **La copie porte le nom de l'originale** et n'hérite ni de ses profils retenus ni de son partage.
- **Supprimer depuis la liste** retire la carte sans recharger la page — le store sait déjà le faire.
- **Toute chaîne passe par une clé i18n**, libellés de menu et confirmation compris.

## Documentation impactée

- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)** —
  sa ligne des gestes est déjà juste depuis `ENT-051` ; vérifier qu'elle le reste, et rien de plus si
  c'est le cas.

Aucun `README.md` ne change de convention.

## Définition de terminé

- [x] Le menu « ⋯ » porte les cinq gestes, et « Clôturer » a quitté la ligne d'actions.
- [x] Ouvrir le menu n'ouvre pas la recherche.
- [x] Le menu s'annonce avec le nom de la recherche, et reste visible sans survol.
- [x] Renommer se fait dans le titre ; vider le champ rétablit le nom automatique, et l'écran le dit.
- [x] Dupliquer fait apparaître la copie dans la liste, sans quitter l'écran.
- [x] Partager bascule en un clic, et l'état se lit sur la carte.
- [x] Supprimer demande confirmation **en nommant la recherche**, et la carte disparaît sans
      rechargement.
- [x] Sur la carte d'un collègue, un lien « Dupliquer » direct et aucun menu.
- [x] Une recherche close ne propose que « Rouvrir » et « Supprimer ».
- [x] Aucun texte en dur.
- [x] Les entrées de menu sont couvertes par des tests unitaires.

## Vérification

**Mesuré sur l'application**, sur dix recherches à moi, une close, et une recherche partagée par un
collègue de mon organisation :

```
menu ⋯ sur mes 10 recherches, aucun sur celle du collègue
entrées                 : Renommer · Dupliquer · Partager avec l'équipe · Clôturer · Supprimer
sur une recherche close : Rouvrir · Supprimer
carte d'un collègue     : bouton « Dupliquer » direct, aucun menu
annonce du ⋯            : « Actions sur « Métier gamma · gamma » »
ouvrir le menu          : URL inchangée — le calque de clic du titre ne se déclenche pas

renommer                : « Métier gamma · gamma » → « Recherche renommée à la main »
  après rechargement    : « Recherche renommée à la main »
  champ vidé            : « Métier gamma · gamma » — le nom automatique revient
  rang de la carte      : 0 avant, 0 après — elle ne saute pas de place
  focus après Échap     : rendu au bouton ⋯
  focus après Entrée    : rendu au bouton ⋯

dupliquer (carte du collègue) : 10 → 11 cartes, URL inchangée, la copie prend la tête et m'appartient
partager                      : le menu dit « Ne plus partager », la carte affiche « Partagée avec l'équipe »
supprimer                     : « Supprimer « Finance France vers Casablanca » ? » puis 11 → 10 cartes,
                                sans rechargement
```

**Le renommage a été relu en base** et non seulement à l'écran — une première lecture trop précoce
de la carte avait laissé croire qu'il n'avait pas pris :

```
 id |        name         |     updated_at
 36 | Renommée au clavier | 2026-08-05 20:42:30
```

**`card.vitest.ts` : 6 cas neufs** sur `actionsFor`, et **160 tests** verts sur `services/search/`.
Type-check vert sur les quatre espaces, format propre sur les fichiers touchés.

**Un défaut a été trouvé en mesurant, pas en relisant** — le champ de renommage qui se refermait
seul. Le diagnostic image par image, et sa correction, sont en *Décisions de cadrage*.

## Livré

- `webapp/services/search/card.ts` **(M)** — `SearchAction` et `actionsFor` : la règle qui décide
  des entrées, seule chose de cette tâche qui se décide hors du rendu.
- `webapp/services/search/card.vitest.ts` **(M)** — 6 cas.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — le menu et son bouton nommé,
  le champ de renommage sur place, la boîte de confirmation qui nomme la recherche, la ligne
  « Partagée avec l'équipe », le lien « Dupliquer » direct, et la neutralisation du retour de focus
  du menu avec sa contrepartie.
- `webapp/spaces/entreprise/app/composables/useSearches.ts` **(M)** — `update`, `duplicate` et
  `remove`, chacun rendant une clé i18n plutôt que levant ; le `409` d'une recherche close y est
  enfin atteignable.
- `webapp/base/server/api/searches/[id].delete.ts` **(+)** — relais de suppression.
- `webapp/base/server/api/searches/[id]/duplicate.post.ts` **(+)** — relais de duplication, ouvert à
  qui peut lire : c'est la porte de sortie du destinataire d'une recherche partagée.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** et
  `webapp/spaces/entreprise/app/pages/recherches/index.vue` **(M)** — le câblage des cinq gestes.
  Sur l'accueil, `remove` est renommé localement : le nom y était déjà pris par le moteur en langage
  naturel, où il retire un **critère** et non une recherche.
- `webapp/base/i18n/locales/fr.yaml` **(M)** — les onze clés de gestion.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` **(M)** — la ligne des gestes,
  à laquelle « renommer » manquait.

## Reste à faire (hors périmètre)

- **Le bouton « ⋯ » est volontairement très effacé**, et sur une carte au titre court il se voit à
  peine. Comme l'équilibre des aplats accent d'`ENT-054`, cela se juge sur pièce et non sur une
  carte de tâche.
- **L'équilibre des aplats accent sur l'accueil**, toujours ouvert depuis `ENT-054`.
