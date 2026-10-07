# TASK ENT-064 — entreprise : les recherches d'un collègue se rangent à part

- **Créée le** : 2026-08-06.
- **Session** : `6132d437-ec3b-4d4f-ae53-e96165f4b0a9`.
- **Statut** : ✅ fait le 2026-08-07 — la collection se lit en trois groupes, l'accueil ne montre
  plus que mes recherches et son lien annonce ce que la collection contient, et un membre sans
  recherche à lui arrive directement sur l'écran de création. 15 tests.
- **Type** : Frontend entreprise — un regroupement d'écran et un filtre. Aucun contrat, aucune
  migration, aucune règle serveur touchée.
- **Tests** : unitaires (vitest) sur la logique pure — la partition à **trois** groupes, et le filtre
  de l'accueil qui écarte ce qui n'est pas à moi **avant** de trier par date. Le rendu des deux
  écrans se prouve en **capture authentifiée** : le README de `components/` interdit à un composant
  de porter de la donnée dérivée, donc tout ce qui se teste vit hors du rendu.
- **Réf** : **`ENT-051` — amont, mergée** (elle pose la partition pure et le pli des closes, dont
  cette tâche reprend le patron) · **`ENT-054` — amont, mergée** (les deux rangées de l'accueil) ·
  **`ENT-050` — amont, mergée** (la carte, son rail et la ligne « partagée par X ») ·
  **`ENT-065` — aval, écrite avec celle-ci** (l'export proposé à qui le serveur le refuse) ·
  `webapp/services/search/collection.ts` ·
  `webapp/spaces/entreprise/app/pages/recherches/index.vue` ·
  `webapp/spaces/entreprise/app/pages/index.vue` ·
  `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

Sur « Vos recherches enregistrées », les recherches qu'un collègue m'a partagées sont **mêlées aux
miennes**, dans la même grille et dans le même ordre. Rien ne les regroupe : chaque carte porte bien
une ligne « partagée par Leila Mansouri », mais il faut la lire carte par carte pour savoir ce qui
m'appartient. Or les deux ne s'utilisent pas pareil — sur la mienne je travaille, sur la sienne je
regarde. Le rail de la carte le dit déjà, d'ailleurs : il montre mon avancement sur les miennes, et
seulement « N retenus par l'équipe » sur les autres. L'écran connaît la distinction ; il ne l'a
simplement jamais rendue visible.

L'accueil aggrave la chose. Son métier est de **faire reprendre un travail en cours** — son propre
code le pose, et c'est pour ça que `ENT-051` en a exclu les recherches closes. Il propose pourtant,
au même rang que les miennes, des recherches que je ne peux ni modifier, ni renommer, ni favoriser.
Une recherche d'un collègue peut occuper une des six places de l'écran d'arrivée, à la place d'une
recherche vivante qui est la mienne.

**La lecture seule, elle, n'est pas à construire** — ce point a été vérifié pendant le cadrage et il
délimite la tâche. `SavedSearchVoter` réserve depuis toujours l'écriture à l'auteur (critères, nom,
suppression, favoris, export), et les deux écrans de recherche calculent déjà `editable = isOwn`.
Marquer « vu » et « écarter » restent **volontairement** permis au destinataire : c'est un avancement
personnel, pas une modification de la recherche, et le lui refuser rendrait le partage inutilisable.

## Périmètre

### L'écran de collection — trois groupes au lieu de deux

- **Mes recherches ouvrent la page**, sans intertitre.
- **Une section « Partagées avec moi · N »**, toujours ouverte, introduite par un **vrai titre de
  section** ; les noms de recherche y descendent d'un niveau. La carte reçoit donc une **option de
  niveau de titre**, dont la valeur par défaut ne change rien partout ailleurs.
- **Le pli des closes est inchangé** et reste en dernier : il prend les recherches closes **de tout
  le monde**, les miennes comme celles d'un collègue.
- **La section n'apparaît pas du tout** quand aucune recherche ne m'a été partagée — même règle que
  le pli, qui disparaît faute de closes.

### L'accueil

- **Les deux rangées ne montrent plus que MES recherches actives.**
- **Le lien du bas compte les actives de tout le monde** — les miennes plus celles qui me sont
  partagées, closes exclues — et **cesse de dire « mes »**, puisqu'il mène à un écran qui montre
  aussi celles des collègues.
- **Sans aucune recherche à moi, le moteur classique s'affiche déjà déplié** à la place du message
  d'écran vide, **sans bouton « Retour »**. Les deux boutons « Créer une recherche » et « Recherche
  assistée » restent au-dessus.

### La couche pure et le reste

- **`collection.ts`** : la partition passe de deux groupes à trois, et le sélecteur de l'accueil
  écarte ce qui n'est pas à moi.
- **Les trois fichiers de langue** : l'intertitre, son compte accordé en nombre, le libellé du lien.
- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**,
  pour deux raisons distinctes — cf. *Documentation impactée*.

## Hors périmètre

- **Le bouton « Exporter » proposé à un collègue alors que le serveur le refuse** — défaut réel,
  sujet distinct, carte `ENT-065`.
- **Le libellé « Ma sélection »** sur la carte d'un collègue, qui parle de « ma » sélection là où ce
  sont les retenus de l'équipe. Écart nommé, non traité : c'est un mot sur un bouton, pas une règle
  de groupement, et le corriger ici mélangerait deux sujets dans un commit.
- **Le pli des closes lui-même** : ni son libellé, ni son comportement, ni la mémoire de son état.
- **Toute co-édition**, toute notification « une recherche vous a été partagée », tout réglage fin
  du partage (par personne plutôt que par équipe).
- **L'écran de collection entièrement vide** — aucune recherche, ni à moi ni partagée : il garde son
  message actuel et son bouton vers l'accueil.
- **Tout contrat, toute migration, toute règle serveur.** Cette tâche consomme `isOwn`, déjà servi.

## Décisions de cadrage

**La section est DÉPLIÉE, là où les closes sont repliées.** Ce n'est pas une inconséquence, c'est la
différence de nature entre les deux groupes. Le pli des closes **range ce qui est fini** — c'était
tout le bénéfice de la clôture. Une recherche qu'un collègue m'a partagée n'est pas finie : c'est du
travail vivant qu'on m'a confié, et le replier reviendrait à le cacher. Le regroupement doit
**ordonner** la page, pas en soustraire une partie.

**La clôture l'emporte sur l'appartenance.** Une recherche partagée **et** close descend dans le pli
des closes, pas dans la section partagée. Le pli veut dire « plus rien à y faire », et c'est vrai
quel qu'en soit l'auteur ; ranger une recherche terminée dans un groupe de travail en cours
reviendrait à annuler le bénéfice de la clôture pour la moitié des cartes.

**Un seul intertitre, et il arrive quand le groupe change.** Mes recherches ouvrent la page sans
étiquette : un titre « Mes recherches » posé sous un titre de page « Vos recherches enregistrées »
dirait deux fois la même chose. C'est exactement ce que fait déjà le pli des closes, qui n'introduit
rien au-dessus de lui.

**L'intertitre est un vrai titre de section, et les cartes descendent d'un niveau.** La section étant
toujours ouverte, elle se saute à la lecture comme à l'œil — encore faut-il qu'un lecteur d'écran
(le logiciel qui lit la page à voix haute aux personnes malvoyantes) puisse l'annoncer et permettre
d'y sauter au clavier. Une ligne de texte ordinaire ne le permet pas. **Coût assumé** : la carte
gagne une option pour savoir à quel niveau se nommer, réglée une fois par écran.

**L'accueil se réserve à mes recherches.** Il sert à reprendre **mon** travail ; une recherche que je
ne peux pas modifier n'a pas à occuper une des six places de l'écran d'arrivée. La carte de
collection reste, elle, le seul endroit où l'on voit tout.

**Le lien du bas annonce ce que l'écran d'arrivée montre.** Il compte donc les actives de tout le
monde — c'est déjà son comportement, seul le libellé change — et non mes seules recherches, qui
promettraient trois cartes là où l'écran suivant en montre cinq. Le total absolu, closes comprises, a
été écarté pour la raison inverse : il annoncerait plus que ce qu'on voit en arrivant, puisque les
closes attendent derrière leur pli.

**Sans recherche à moi, le moteur remplace le vide.** Filtrer l'accueil crée un cas nouveau : un
membre à qui l'on a partagé deux recherches, mais qui n'en a créé aucune, tombait sur un écran vide.
Plutôt qu'un message qui explique l'absence, l'écran **propose directement de chercher** — le moteur
classique s'affiche déplié, prêt à recevoir un premier critère. **Pas de bouton « Retour »** : il
ramènerait précisément à l'écran vide qu'on vient d'éviter. La recherche assistée reste accessible
par son bouton.

### Écart : l'écran vide de l'accueil a changé de forme entre le cadrage et le code

Le cadrage décidait que « le moteur classique s'affiche déjà déplié à la place du message d'écran
vide ». Entre-temps, **`ENT-067` a sorti ce moteur de l'accueil** pour lui donner son écran —
`/recherches/nouvelle`, avec sa barre haute et son nom qui s'écrit tout seul. La décision ne pouvait
plus s'appliquer telle quelle : le seul moteur encore hébergé par l'accueil est celui en langage
naturel.

**Arbitré avec l'utilisateur, en cours d'implémentation** : sans aucune recherche à soi, on **arrive
directement sur l'écran de création**. C'est la traduction fidèle de l'intention — montrer le
moteur, pas expliquer le vide — dans la nouvelle organisation des écrans. Le compte retrouve
l'accueil dès sa première recherche créée. **Réserve assumée** : tant qu'il n'en a aucune, la
recherche assistée n'est pas atteignable depuis l'accueil, et la collection reste accessible par le
menu du compte.

### Écart : le composable des recherches gagne une entrée que le cadrage ne prévoyait pas

La redirection posée d'abord en `watch` **ne partait pas au rendu serveur** : `pending` vaut encore
`true` pendant le setup, et un `watch` n'est jamais rejoué côté serveur faute d'ordonnanceur.
Mesuré — l'accueil répondait `200` et corrigeait dans le navigateur, l'écran vide passant sur le fil.

`useSearches()` expose donc `loaded`, la promesse de sa collection, et la page l'attend avant de
décider. La règle du dossier est respectée — le nom dit le métier et non la mécanique — mais le
`return` passe de 11 à 12 valeurs, au-dessus du seuil de 8 recommandé (non verrouillé en CI). Il le
dépassait déjà ; c'est nommé en *Reste à faire* plutôt que corrigé ici, un découpage du composable
sortant du périmètre.

**Écart de doc constaté pendant le cadrage, et tranché ici.** Le § *Partage d'une recherche* de
l'arborescence affirme que le destinataire « peut **dévoiler** (commun) et dupliquer ». Le serveur
l'autorise en effet — acheter un contact s'autorise au niveau de l'entreprise et non de la recherche
—, mais **l'écran le refuse** : les quatre boutons d'achat sont inactifs dès qu'on n'est pas
l'auteur, dans la Table comme dans la Visionneuse. Arbitré : **c'est l'écran qui a raison**, un
collègue ne dépense pas les crédits communs depuis la recherche d'un autre. La phrase du cadrage
perd donc sa mention du dévoilement.

## Règles et cas limites

- **Aucune recherche partagée → aucune section**, pas même un intertitre suivi du vide.
- **Le compte de l'intertitre s'accorde en nombre** par une clé i18n — « 1 recherche partagée » et
  non « 1 recherche(s) », comme le pli des closes le fait déjà.
- **Une recherche partagée close va dans le pli**, et le compte de la section ne la voit pas.
- **Sur l'accueil, le filtre s'applique AVANT le tri** par date, pour la même raison que les closes :
  dans l'autre ordre, une recherche d'un collègue fraîchement touchée évincerait une des miennes.
- **Le niveau de titre de la carte a une valeur par défaut** : l'accueil et le groupe de mes
  recherches ne la précisent pas, seule la section partagée la surcharge.
- **Le moteur affiché à vide ne l'est qu'en mode liste** — ouvrir la recherche assistée puis revenir
  ne doit pas empiler deux moteurs.
- **Aucune lecture de `window`** : le découpage en deux rangées reste porté par le gabarit, sans quoi
  le rendu serveur et le rendu client divergeraient à l'hydratation.
- **Mobile d'abord** : la section et son intertitre tiennent sur une colonne, comme la grille
  existante.
- **Toute chaîne passe par une clé i18n**, dans les **trois** fichiers de langue.
- **Collision connue** : la tâche `ENT-059`, cadrée par une autre session, touche elle aussi les
  trois fichiers de langue. Se réaligner sur `develop` avant d'y écrire, et ne stager que ses propres
  chemins.

## Documentation impactée

- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**,
  à deux endroits :
  - l'encadré de `/recherches` ne connaît que deux groupes — « les closes descendent sous un pli
    replié » — et ignore la section des partagées ;
  - le § *Partage d'une recherche* annonce que le destinataire « peut dévoiler (commun) », ce que
    l'écran refuse et ce que le cadrage vient de trancher dans l'autre sens.

Aucun `README.md` ne change de convention. Aucun autre document n'est rendu faux par ce diff.

## Définition de terminé

- [x] Sur « Vos recherches enregistrées », les recherches d'un collègue sont regroupées sous un
      intertitre « Partagées avec moi » qui annonce leur nombre, et cette section est ouverte.
- [x] Mes recherches ouvrent la page sans intertitre au-dessus d'elles.
- [x] La section disparaît entièrement quand aucune recherche ne m'a été partagée.
- [x] Une recherche close d'un collègue se trouve dans le pli des closes, pas dans la section.
- [x] Un lecteur d'écran annonce la section comme un titre, et les noms de recherche un niveau en
      dessous.
- [x] L'accueil ne montre plus aucune recherche dont je ne suis pas l'auteur.
- [x] Le lien du bas de l'accueil ne dit plus « mes » et compte les actives, partagées comprises.
- [x] Un compte qui n'a créé aucune recherche arrive sur le moteur de recherche — sur SON écran
      désormais, cf. *Écart*, et sans bouton « Retour » vers un accueil vide.
- [x] La partition à trois groupes et le filtre de l'accueil sont couverts par des tests unitaires.
- [x] Aucun texte en dur : toutes les chaînes passent par une clé i18n, dans les trois langues.
- [x] L'arborescence de l'espace entreprise décrit les trois groupes et ne promet plus le
      dévoilement au destinataire d'un partage.

## Vérification

**Le plan du document**, lu dans le DOM servi — c'est ce qui prouve que la section est un titre et
non une ligne décorative, et que les cartes lui appartiennent :

```
H1 — Mes recherches
H2 — Développeur logiciel                          ← les miennes
H2 — Commercial · 1 an et plus · Bac
   …
H2 — Partagées avec moi · 4                        ← la section
   H3 — Acheteur · achats                          ← ses cartes, un cran en dessous
   H3 — Contrôleur de gestion · contrôle des coûts
   H3 — Chef d'équipe · management
   H3 — Manager · management · 5 ans et plus
```

**La redirection, décidée au rendu serveur** — un compte témoin a été créé pour ce cas, membre
d'Atlas sans aucune recherche à lui et destinataire de huit partages :

| Compte | Accueil |
| --- | --- |
| membre sans recherche à lui | **302** → `/recherches/nouvelle` |
| propriétaire avec ses recherches | **200** |

Posée d'abord en `watch`, la même redirection répondait `200` : elle ne partait que du navigateur.

**Le cas limite « close ET partagée »**, fabriqué en clôturant une recherche de Leila depuis son
propre compte, puis rouverte :

| Observé | Avant | Après clôture |
| --- | --- | --- |
| section « Partagées avec moi » | 4 | **3** |
| pli des closes | 6 | **7 recherches closes**, « Acheteur · achats » à l'intérieur |

**Captures authentifiées**, thème sombre :

| Écran | Ce qui a été observé |
| --- | --- |
| Collection, `atlas.owner` | mes cartes, puis l'intertitre à filets, puis les quatre partagées — « partagée par Leila Mansouri · lecture seule », rail à « N retenus par l'équipe » |
| Collection, membre témoin | la page s'ouvre directement sur « Partagées avec moi · 8 », sans groupe au-dessus |
| Accueil, `atlas.owner` | quatre cartes, **aucune** « partagée par » ; le lien dit « **Voir les 57 recherches** » |
| Accueil, membre témoin | l'écran de création, nom « Se nomme tout seul… », sans « Retour » |
| Collection à 390 px | une colonne, l'intertitre et ses filets tiennent, les cartes suivent |

**`collection.vitest.ts` : 15 tests, vert**, dont celui qui fige la priorité de la clôture sur
l'appartenance et celui qui interdit de trier avant de filtrer. Les **190 tests** de
`services/search/` passent.

## Livré

- `webapp/services/search/collection.ts` **(M)** — `groupSearches` (trois groupes) remplace
  `partitionByClosure`, `recentToResume` remplace `recentActive` en écartant aussi les recherches
  d'un collègue, et `activeCount` sort le compte du lien de la page qui le calculait.
- `webapp/services/search/collection.vitest.ts` **(M)** — 15 tests.
- `webapp/spaces/entreprise/app/pages/recherches/index.vue` **(M)** — la section, son titre de
  niveau 2 et ses filets ; le pli des closes inchangé, en dernier.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — l'option de niveau de titre,
  par défaut 2 : la taille du texte ne bouge pas, seule la place dans le plan du document change.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** — mes recherches seules, le compte du lien,
  et la redirection **attendue** avant de rendre.
- `webapp/spaces/entreprise/app/composables/useSearches.ts` **(M)** — `loaded`, la collection une
  fois chargée, sans quoi la décision se prend trop tard (cf. *Écart*).
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — l'intertitre et son compte ; en français, le
  lien de l'accueil cesse de dire « mes » (l'anglais et l'arabe étaient déjà neutres).
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` **(M)** — les trois groupes,
  l'accueil réservé à ses propres recherches, et la lecture seule qui ne promet plus le
  dévoilement.

## Reste à faire (hors périmètre)

- **Le titre de la page dit « Mes recherches »** au-dessus d'un écran qui, pour un nouveau membre,
  ne contient que celles des autres. Le décalage préexiste — les partagées y figuraient déjà — mais
  la section le rend visible.
- **Le bouton « Ma sélection »** sur la carte d'un collègue parle de « ma » sélection là où ce sont
  les retenus de l'équipe.
- **`useSearches()` renvoie 12 valeurs** pour un seuil recommandé de 8. Il en renvoyait déjà 11 ;
  le découper est un chantier à soi.
- **Sur téléphone, « pas de nouveaux » déborde du rail** de la carte et se coupe au bord gauche.
  Préexistant, sans rapport avec ce diff.
- **`ENT-065`** — l'export encore proposé à qui le serveur le refuse. Carte écrite, non implémentée.
