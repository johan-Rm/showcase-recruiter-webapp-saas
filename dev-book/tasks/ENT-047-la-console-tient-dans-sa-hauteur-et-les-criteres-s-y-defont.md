# TASK ENT-047 — entreprise : la console tient dans sa hauteur, et les critères s'y défont

- **Créée le** : 2026-08-05.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — la liste des critères défile dans la hauteur qu'on lui donne
  et porte le retrait ; la barre de puces et la liste des favoris sont supprimées ; l'entête tient
  en trois encadrés sur une rangée et passe de 245 à 166 px.
- **Type** : Frontend entreprise — refonte d'un onglet, deux composants supprimés, une règle métier
  ajoutée.
- **Tests** : unitaires dans `webapp/services/search/criteria.vitest.ts`, sur `confrontCriteria` —
  le cas neuf « **aucun profil à confronter** », qui cesse d'être un tableau vide pour devenir des
  critères sans verdict. Le rendu — grille, défilement, teintes — n'est pas couvrable : le webapp n'a
  aucun harnais de test de composant. La preuve attendue est une **capture** de l'onglet à dix
  critères.
- **Réf** : `ENT-041` (la console et ses onglets — c'est elle qui a posé la barre de puces) ·
  `ENT-038` (le panneau dit ce qui manque) · `ENT-039` (le nuancier d'états, et l'interdit sur
  `--alert`) · `ENT-046` (la mention temporelle sur la ligne du métier) ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) ·
  `ControlPanel.vue` · `MatchList.vue` · `ProgressHeader.vue`

## Contexte

L'onglet « En cours » de la console empile quatre blocs, et **un seul plie**.

Les puces de critères et la liste « Ce profil face à vos critères » n'ont ni hauteur maximale ni
défilement : elles prennent ce qu'il leur faut. La section des favoris, elle, est en
`flex-1 min-h-0` — « prends ce qui reste, et accepte zéro ». Chaque critère ajouté lui retire donc
deux lignes : une puce en haut, une ligne de critère au milieu. À six critères les favoris sont
réduits à leur titre ; à dix, ils sortent de la colonne.

Le commentaire de la console annonce pourtant l'inverse — « le seul défilement de la console […]
douze filtres ne tiennent pas dans une hauteur d'écran ». Ce défilement existe, mais **seulement dans
les onglets « Classiques » et « Secondaires »**. L'onglet « En cours » n'en a pas.

Par-dessus, **les puces répètent ce que la liste énonce déjà**, sans dire ce que chaque critère
donne. Le doublon n'est pas visuel, il est fonctionnel à moitié : la barre porte le retrait sans le
verdict, la liste porte le verdict sans le retrait.

## Périmètre

- **La barre de puces disparaît** — `CriteriaBar.vue` **supprimé**, il n'avait aucun autre appelant —
  et son geste descend dans la liste : chaque ligne porte un **✕** qui retire le critère.
- **La liste des favoris disparaît**, fichier compris (`FavoriteList.vue` supprimé) — la Table les
  sert déjà par son propre périmètre.
- **L'entête est refondue** : le nom sur toute la ligne, **quatre encadrés en 2×2** dont deux
  cliquables, une légende de périmètre, la barre de progression avec la **position** à sa droite.
- **La liste des critères prend tout le reste** (`flex-1 min-h-0`) et **défile**
  (`overflow-y-auto`) — ce qui suppose de réparer la chaîne de hauteur dans la **page**, cf.
  *Écart au cadrage*.
- **Le signe et le verdict se colorent** — vert satisfait, rouge non satisfait — par **deux jetons
  dédiés** à inscrire dans la charte.
- **`confrontCriteria` rend des critères sans verdict** quand aucun profil n'est affiché, au lieu
  d'un tableau vide.

## Hors périmètre

- **La couleur sur le libellé du critère ou sur la valeur du profil.** La charte réserve la couleur
  aux **états** et interdit qu'elle code une **donnée** — « vingt lignes colorées ne hiérarchisent
  rien, elles saturent ». S'y tenir est ce qui nous évite de l'amender ; voir *Décisions*.
- **Les onglets « Classiques » et « Secondaires »**, inchangés, défilement compris.
- **La corbeille et la reprise du tri** : le mécanisme ne change pas, seul son **déclencheur** se
  déplace vers un encadré.
- **Le regroupement des mots-clés** en un bloc « un seul suffit ». Examiné, écarté — voir *Risques*.
- **L'étoile de la fiche profil**, qui reste le geste par lequel on retient.
- **La mention temporelle d'`ENT-046`** sur la ligne du métier : elle reste neutre, ce n'est pas un
  verdict.

## Décisions de cadrage

### Le ✕ descend dans la ligne, et c'est ce qui résout vraiment le doublon

`ENT-041` avait posé les puces en écrivant : « c'est le seul endroit du produit où l'on retire un
critère **d'un clic**, sans rouvrir un formulaire ni chercher dans quel onglet il se trouve ».
Supprimer la barre sans déplacer ce geste l'aurait perdu.

La ligne devient donc ce que la barre était, en mieux : elle dit ce que le critère demande, ce que
le profil y oppose, **et** permet de le retirer. La règle d'`ENT-041` n'est pas abandonnée, elle
déménage à un endroit plus utile.

### La couleur porte sur le signe et le verdict, jamais sur la ligne

La charte est explicite : « la couleur appartient à l'accent et aux **états** — ce qu'un objet *est*,
pas ce qu'il *contient* », et « vingt lignes colorées ne hiérarchisent rien, elles saturent ».

Teinter la ligne entière est exactement le cas interdit, et il aurait fallu préciser la charte comme
`ENT-039` a dû le faire. Teinter le **signe** (✓ / ✗) et le **verdict** (« Trouvé » / « Non trouvé »)
ne le fait pas : ce sont deux éléments ponctuels, proches d'un badge, pas une colonne de teintes. La
lecture verte/rouge est obtenue, la règle tient.

**Conséquence, à respecter :** quand le profil porte une valeur comparable, c'est elle qui s'affiche
à la place du verdict — et elle reste **neutre**. Seul le signe se colore alors. Une valeur de profil
est une donnée.

### Deux jetons dédiés, pas l'accent ni l'alerte

`--accent` est réservé au **favori** — « le seul état que le recruteur produit » — et `--alert` au
feedback bloquant, la charte interdisant qu'elle code une donnée. Les emprunter diluerait l'un et
ferait dire « erreur » à l'autre là où rien n'a échoué.

On suit donc le précédent d'`ENT-039`, qui a créé `--state-morocco` plutôt que de réutiliser
`--alert` : **deux jetons de verdict**, hors nuancier, sur des hues libres, identiques dans les deux
thèmes — ce dernier point s'est révélé **faux à la mesure**, cf. *Écart au cadrage*. Points de départ
à **caler en capture**, comme la charte le prescrit — un vert distinct de
`--state-available` (`#4fb87e`, qui signe « Disponible ») et un rouge distinct de `--alert` et de
`--state-morocco` (`#d94a4d`).

### Quatre encadrés, dont deux qui agissent

Le nom peut prendre toute la ligne **parce que** le compteur `1 / 4` en descend — les deux vont
ensemble. Il rejoint la barre de progression, dont il est la lecture chiffrée : la barre montre où
l'on en est, le nombre le dit.

Restaient deux **gestes** que l'entête portait et qu'aucun chiffre ne remplace : reprendre un tri
interrompu, et rouvrir la corbeille. Les encadrés les absorbent :

| Encadré | Chiffre | Geste |
| --- | --- | --- |
| **Vus** | `seenCount` | reprend le tri au premier non vu |
| **Favoris** | nombre de retenus | — |
| **Écartés** | `dismissedCount` | ouvre la corbeille |

**TROIS et non quatre** — « À voir » a été retirée à la relecture, cf. *Écart au cadrage*. Le
raisonnement ci-dessous portait sur quatre : le gabarit existe déjà — la fiche profil pose exactement quatre encadrés en
grille —, et sa règle vaut ici aussi : **toujours le même nombre de cases**, tiret compris, sinon la
grille change de largeur d'un profil à l'autre.

La légende « sur N profils chargés » reste, sous les encadrés. Sans elle, « à voir 29 » se lit
« il reste 29 profils » alors qu'il en reste 155.

### Sans profil affiché, les critères restent — et restent retirables

Aujourd'hui `confrontCriteria` rend un tableau vide dès qu'il n'y a pas de carte, et la liste ne
s'affiche pas. Les puces prenaient le relais ; elles disparaissent.

Sur une recherche qui ne ramène **personne** — précisément le moment où l'on veut défaire un
critère —, l'onglet serait devenu muet et le geste impossible. La liste montre donc les critères
**sans signe ni verdict**, avec leur ✕.

### Les favoris quittent la console sans rien perdre

La Table sert déjà les retenus par son propre périmètre (`?scope=favorites`), sur un onglet qui se
désactive quand rien n'a été retenu. L'écho du geste reste dans la console par l'encadré
« Favoris ».

## Règles et cas limites

- **Un seul bloc plie.** L'entête est `flex-none`, la liste `flex-1 min-h-0` avec
  `overflow-y-auto`, le pied `flex-none`. C'est l'inverse exact d'aujourd'hui, et c'est tout le
  correctif de hauteur.
- **Le verdict reste porté par le texte.** Le panneau pose déjà la règle — « une couleur et une
  icône ne disent rien à qui ne les distingue pas » — et la teinte ne fait que la renforcer. Aucune
  information ne doit dépendre de la couleur seule.
- **Le ✕ n'apparaît pas sur la recherche d'un collègue** (`editable` faux) : elle se lit, elle ne se
  retouche pas. Même règle que les puces qu'il remplace.
- **Le ✕ est atteignable au clavier** et porte un libellé accessible nommant le critère retiré — la
  barre le faisait, on ne régresse pas.
- **Un ajustement en cours ferme la porte aux suivants** (`busy`), comme sur les puces.
- **La ligne du métier peut porter la mention d'`ENT-046`** (« poste actuel » / « poste passé ») :
  elle reste neutre et ne prend jamais la teinte du verdict.
- **La valeur du profil continue de tronquer**, le ✕ et le verdict restent `flex-none` : c'est la
  valeur qui raccourcit, jamais eux.
- **Les encadrés cliquables sont des boutons**, pas des blocs avec un `@click` : ils doivent être
  atteignables au clavier et annoncer ce qu'ils font.
- **Un encadré dont le geste n'a pas lieu d'être se désactive** plutôt que de disparaître — la règle
  du nombre de cases constant.
- **Mobile d'abord** : la grille 2×2 tient dans une colonne étroite ; la liste défile dans la
  hauteur qu'on lui donne, quelle que soit celle de l'écran.

## Documentation impactée

- **`brand-guidelines.md`** — les deux jetons de verdict rejoignent le tableau des couleurs. La
  **règle ne change pas** : c'est le choix de teinter le signe et le verdict, et non la ligne, qui
  l'évite.
- **`design-system.md`** — les deux utilitaires `text-verdict-*` rejoignent la table « Consommer un
  token », qui les énumère un par un.
- **Les commentaires des composants touchés** — `ControlPanel.vue` (il n'assemble plus les mêmes
  blocs), `MatchList.vue`, `ProgressHeader.vue`, et `Console.vue` dont le commentaire affirme un
  défilement que l'onglet « En cours » n'avait pas.
- **Le doc-block de `describeDecision`** (`services/search/candidate.ts`), qui annonce « deux listes
  s'en servent » : la corbeille restera seule.
- **Vérifié, non impacté** : `arborescence-entreprise.md` — elle décrit la Visionneuse comme
  « affiner, et **retenir** ses favoris », ce qui reste vrai : l'étoile ne bouge pas, seule la liste
  s'en va.

## Risques et reports assumés

**Dix mots-clés donneront dix ✗ rouges sur un profil que la recherche a retenu à juste titre.** Les
mots-clés sont reliés en **OU** — le formulaire le promet (« chaque mot ajouté ÉLARGIT le résultat,
un profil suffit à en satisfaire un seul ») et le moteur le fait. Le panneau affichera donc un mur de
verdicts négatifs là où la recherche est satisfaite.

Le regroupement (« Mots-clés · un seul suffit », ce qui a marché nommé, le reste compté) a été
proposé et **écarté** : une ligne par mot-clé est plus simple à lire comme règle. Le choix est
assumé, il a été signalé deux fois avant d'être pris. La couleur limitée au signe et au verdict en
atténue la violence, sans la supprimer.

À rouvrir si l'usage montre que des profils valides sont écartés sur cette lecture.

## Définition de terminé

- [x] À dix critères, la liste **défile** au lieu de pousser, et l'entête reste entièrement visible.
- [x] La barre de puces a disparu, et un **✕ sur la ligne** retire un critère sans quitter l'onglet.
- [x] La liste des favoris a disparu, `FavoriteList.vue` supprimé, aucun import orphelin.
- [x] **Trois** encadrés sur une rangée : « Vus » reprend le tri, « Écartés » ouvre la corbeille —
      « À voir » retirée, cf. *Écart au cadrage*.
- [x] Le nom occupe toute la ligne ; `1 / 4` est à droite de la barre de progression ; la légende
      « sur N profils chargés » est sous les encadrés.
- [x] Signe et verdict colorés par les deux jetons dédiés ; **libellé et valeur du profil restent
      neutres**.
- [x] Sur une recherche qui ne ramène personne, les critères restent affichés **et retirables**,
      sans signe ni verdict.
- [x] Sur la recherche d'un collègue, aucun ✕.
- [x] `criteria.vitest.ts` couvre le cas « aucun profil à confronter ».
- [x] Les deux jetons sont dans `brand-guidelines.md`, calés en capture dans les deux thèmes — et
      **par thème**, la valeur unique ne pouvant pas passer AA des deux côtés.
- [x] Captures à l'appui : onglet à dix critères, et onglet sur une recherche vide.

## Vérification

### La hauteur, mesurée au navigateur sur une recherche à vingt critères

Le défaut et sa correction se lisent dans les mêmes trois nombres, relevés sur la page réelle :

| Élément | Avant | Après |
| --- | --- | --- |
| `<aside>` de la page | **1014 px** pour une case de 791 | 791 px, borné à sa case |
| Liste des critères | 608 px, elle poussait | **386 px visibles** pour 608 px de contenu |
| Entête | 245 px | **166 px**, à toutes les largeurs ≥ 1024 |

Le `overflow-y-auto` de la liste ne se déclenchait pas tant que le `<aside>` débordait : tout ce qui
est en dessous calculait sa hauteur sur une valeur déjà fausse.

### Les trois encadrés tiennent sur une rangée dès 1024 px

Largeurs relevées : **56, 77 et 79 px** — 212 px de cases plus les écarts, dans une colonne qui en
offre 245 à la largeur où la console apparaît. Le passage à la ligne ne se déclenche jamais ; il
reste comme filet.

### Le contraste, mesuré dans les deux thèmes

Les quatre valeurs passent AA (seuil 4,5:1) :

```
fond clair  rgb(226,233,227)   #2e7048 → 4,82:1 ✓   #aa454d → 4,63:1 ✓
fond sombre rgb(33,33,33)      #409c64 → 4,72:1 ✓   #c57278 → 4,64:1 ✓
libellé des encadrés en --muted → 5,71:1 ✓
```

C'est la mesure en thème clair qui a fait basculer le cadrage : les valeurs uniques d'origine y
sortaient à 2,83:1 et 3,82:1.

### La colonne du profil ne parle plus que quand elle apprend quelque chose

Relevé sur un profil réel, après le passage de `sameValue` en insensible à la casse : « angular »,
« typescript », « docker », « kubernetes », « sql », « git » affichent « Trouvé » ; « React Native »,
« Vue.js », « Java Development », « Agile Methodologies », « Cascading Style Sheets (CSS) » restent
affichés parce qu'ils apportent une graphie différente de ce qui a été tapé.

### Captures

Onglet « En cours » à dix critères, et sur une recherche sans résultat — dans les deux thèmes. C'est
la capture qui a fait voir l'échec de contraste, invisible à toutes les gates.

### Sorties fraîches

```
vitest        16 fichiers, 197 tests passés (3,63 s)
conventions   13 règles, aucune violation
type-check    public, candidat, entreprise, admin — code de sortie 0
i18n          fr == en == ar, aucun écart de clé
```

Le type-check a été rejoué **après** le retrait des quatre clés mortes : c'est lui qui aurait vu une
clé encore lue quelque part. Lint, format et la suite complète appartiennent à l'ouverture de la PR.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `MatchList.vue` | Prend la hauteur restante et défile ; porte le ✕ permanent et la teinte du verdict |
| `ProgressHeader.vue` | Trois encadrés sur une rangée, icône teintée, chiffre sous le libellé |
| `ControlPanel.vue` | N'assemble plus que deux blocs ; un seul plie |
| `Console.vue` | Ne relaie plus `release` ; son commentaire ne promet plus un défilement unique |
| `pages/recherches/[uuid]/index.vue` | `min-h-0` sur le `<aside>` — **le correctif de hauteur** |
| `CriteriaBar.vue`, `FavoriteList.vue` | **Supprimés**, sans appelant restant |
| `services/search/criteria.ts` | `matched` devient `boolean \| null` ; `sameValue` insensible à la casse |
| `services/search/candidate.ts` | Doc-block : la corbeille est le dernier appelant |
| `criteria.vitest.ts` | Le cas « aucun profil à confronter », assertion retournée |
| `themes.css`, `main.css` | Les deux jetons de verdict, **par thème**, et leurs utilitaires |
| `brand-guidelines.md`, `design-system.md` | La famille des verdicts et ses deux utilitaires |
| `fr/en/ar.yaml` | Les trois libellés d'encadré ; quatre clés devenues mortes retirées |

## Reste à faire (hors périmètre)

- **La Visionneuse ne charge que trente profils sur deux cent vingt-cinq**, sans pagination, alors
  que la barre de progression compte sur le jeu entier. C'est ce qui a fait retirer la case « À
  voir » plutôt que la corriger : deux dénominateurs cohabitent encore sur le même bandeau, la
  légende « sur N profils chargés » n'étant qu'un pansement. À rouvrir avec le chargement.
- **`search.viewer.panel.title` est une clé sans lecteur**, et elle l'était **avant** cette tâche.
  Non touchée : la retirer ici serait du hors-périmètre.

## Écart au cadrage

### Le vrai correctif de hauteur était dans la PAGE, pas dans la console

La carte attribuait l'écrasement au `flex-none` de la liste. C'était exact et **insuffisant** :
mesuré au navigateur, la liste prenait toujours sa hauteur naturelle une fois passée en
`flex-1 min-h-0 overflow-y-auto`.

La chaîne cassait un cran plus haut. Le `<aside>` de la page est un **élément de grille**, donc
`min-height: auto` par défaut : il grandissait à son contenu — **1014 px pour une case de 791** — et
tout ce qui est en dessous calculait sa hauteur sur une valeur déjà débordée. Aucun `overflow`
interne ne pouvait se déclencher.

Un `min-h-0` sur le `<aside>` suffit, et la mesure bascule : la liste affiche 386 px pour 608 px de
contenu, à vingt critères.

**C'est un défaut qui précède cette tâche** : le commentaire de `Console.vue` annonçait « le seul
défilement de la console » depuis `ENT-041`, et il ne fonctionnait pas non plus pour les onglets de
filtres. Le commentaire est corrigé dans le même mouvement.

### `CriteriaBar.vue` est supprimé, pas seulement délaissé

La carte disait « retiré de la console ». Vérification faite, il n'avait **aucun autre appelant**
dans tout le webapp. Le laisser aurait posé un composant mort dans un dossier auto-importé — donc
découvrable, donc invitant à reproduire un motif qu'on vient de retirer.

### Le ✕ est PERMANENT, pas révélé au survol

Non tranché par le cadrage. J'ai d'abord copié le motif de la liste des favoris — `opacity-0`,
révélé au survol. C'est faux ici : le ✕ remplace les puces, où il était permanent, et **au doigt il
n'y a pas de survol**. Le critère serait devenu irretirable sur tablette et sur mobile.

Il est donc toujours visible, discret par la teinte (`text-muted`) et non par l'absence.

### Une assertion existante encodait l'ancienne règle

`ne confronte rien sans profil ni hors d'une recherche` affirmait que `confrontCriteria` rend un
tableau vide sans profil. C'était vrai tant que les puces portaient les critères ailleurs.

Elle est **retournée**, pas supprimée : c'est exactement la propriété qui bascule, et le test la
tient désormais dans l'autre sens.

### Deux ajouts décidés à la relecture de l'écran

Ni l'un ni l'autre n'était cadré ; les deux viennent de ce que la capture a rendu visible.

**La comparaison de valeurs devient insensible à la casse.** La règle « ne pas répéter une valeur
identique à ce qui a été tapé » comparait au caractère près. Résultat mesuré sur un profil réel :
« docker » s'effaçait derrière « Trouvé » quand « angular » affichait « Angular » — la distinction
que le lecteur voyait était **typographique**, pas sémantique, les compétences venant du texte libre
des profils.

Insensible à la casse, la colonne ne parle plus que quand elle apprend quelque chose : « angular »,
« typescript », « docker », « kubernetes », « sql », « git » passent en « Trouvé » ; « React
Native », « Vue.js », « Java Development », « Agile Methodologies », « Cascading Style Sheets (CSS) »
restent affichés. Une ligne dans `sameValue`.

**Les quatre encadrés passent sur une rangée.** En 2×2 ils mangeaient cent quarante pixels d'une
colonne qui en fait deux cent quatre-vingts. Libellé et chiffre côte à côte, l'entête tombe de
**245 px à 149 px**.

Deux mesures ont dicté la forme finale :

| Forme essayée | Ce que la mesure a dit |
| --- | --- |
| Grille de 4 colonnes égales | « Vus 19 » reçoit la largeur qu'il faut à « Écartés 0 » → **trois libellés tronqués** sous 1500 px |
| Flex sans passage à la ligne | les quatre demandent **282 px** quand la colonne en offre **245** à 1024 → **débordement** |
| Flex avec passage à la ligne | une rangée dès 1500 px, deux en dessous — rien de tronqué, rien hors cadre |

La console n'apparaissant qu'à partir de 1024 px, c'est cette largeur qui fait la contrainte.

### Les jetons de verdict changent de valeur selon le thème — et c'est arithmétique

Le cadrage suivait la charte : les jetons hors nuancier sont « communs aux deux thèmes, donc jamais
redéclarés dans `.dark` ». Mesuré en thème CLAIR, mes deux valeurs échouaient :

```
fond clair rgb(226,233,227)   vert #3f9a63 → 2,83:1 ✗   rouge #b9535b → 3,82:1 ⚠
fond sombre rgb(33,33,33)     vert #3f9a63 → 4,61:1 ✓   rouge #b9535b → 3,41:1 ⚠
```

Et le défaut n'est pas rattrapable en déplaçant la teinte : atteindre 4,5:1 demande une luminance
**≤ 0,137** sur le fond clair et **≥ 0,240** sur le fond sombre. Aucune valeur unique ne satisfait les
deux.

La raison est structurelle : les jetons d'ÉTAT servent de fond de badge, à travers une **recette** qui
les mélange à `--text` — c'est elle qui s'adapte au thème, la teinte n'a pas à le faire. Les verdicts,
eux, sont une **couleur de texte** posée directement. Ils doivent donc varier.

Seule la **luminosité** varie ; teinte et saturation sont identiques. Mesuré après correction :
4,82:1 et 4,63:1 en clair, 4,72:1 et 4,64:1 en sombre — les quatre passent AA.

### Le chiffre passe sous le libellé, et les cases portent une icône teintée

Demandé à la relecture de l'écran. Le libellé et son icône sur une ligne, le chiffre en dessous :
c'est le chiffre qu'on balaie, il gagne à être seul sur sa ligne.

**L'icône porte la teinte, pas le libellé** : une couleur ne doit pas payer sa présence en contraste,
et le libellé reste en `--muted`, mesuré à 5,71:1.

Coût en hauteur : l'entête passe de 149 à **169 px** sur une rangée (245 px avant la refonte), et de
182 à 222 px sur deux rangées en dessous de 1500.

### « À voir » est retirée, et son geste passe sur « Vus »

Décidé à la relecture de l'écran. La case comptait ce qu'il reste dans la **pile chargée** — trente
profils — quand la barre de progression compte sur le **jeu entier**. Deux dénominateurs sur le même
bandeau : « 11 à voir » se lisait « 11 avant d'avoir fini » alors qu'il en restait deux cents.

Son geste, lui, ne pouvait pas disparaître avec elle : sans « Reprendre », rouvrir une recherche
entamée repart du premier profil et impose de refaire défiler ceux qu'on a déjà tranchés. Il passe
donc sur **« Vus »**, qui compte ce qu'on a derrière soi et ramène là où l'on s'est arrêté. Elle se
désactive quand tout a été vu.

**Gain de place inattendu** : à trois cases, la rangée tient **dès 1024 px**. Le passage à deux
rangées disparaît, et l'entête se stabilise à 166 px à toutes les largeurs — contre 245 avant la
refonte, et 222 à quatre cases sur les écrans étroits.
