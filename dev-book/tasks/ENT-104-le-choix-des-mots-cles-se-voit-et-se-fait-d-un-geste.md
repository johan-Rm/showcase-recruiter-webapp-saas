# TASK ENT-104 — entreprise : le choix des mots-clés se voit et se fait d'un geste

- **Créée le** : 2026-08-12.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-12 — le panneau des mots-clés couvre exactement la carte de la
  phrase, chaque suggestion porte un carré qui dit ce qu'elle attend, celles déjà retenues montrent
  une coche pleine et la mention « ajouté », le champ de recherche est encadré et le bouton garde
  son libellé pendant toute l'ouverture.
- **Type** : Frontend entreprise — refonte visuelle d'un composant du socle partagé, retouché *pour*
  l'espace entreprise. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable`. Rien ne se calcule : ce sont des classes, un
  gabarit et trois libellés. Le webapp n'a aucun banc capable de rendre un composant
  (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM). **La preuve attendue est une
  capture** du panneau ouvert, en thème clair et sombre, montrant une ligne retenue et une ligne
  libre côte à côte.
- **Réf** : `webapp/base/app/components/ui/TagPicker.vue` ·
  `webapp/spaces/entreprise/app/components/search/Sentence.vue` ·
  `webapp/base/i18n/locales/{fr,en,ar}.yaml` · maquette fournie par l'utilisateur le 2026-08-12 ·
  amont livré et volontairement non consommé : `API-037`.

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page **« Nouvelle recherche »** —
celle qui s'ouvre depuis l'accueil, et qui sert aussi à retoucher une recherche enregistrée. L'écran
tient en deux cartes : la phrase de recherche à gauche, la colonne **« Qui maîtrise l'un de ces
mots-clés »** à droite. Quand il clique sur **« + mot-clé »**, un panneau glisse par la gauche et
vient couvrir la phrase : c'est ce panneau, et lui seul.

**Trois défauts, tous de lisibilité.**

**Rien ne dit qu'une ligne est cliquable.** Les suggestions s'alignent comme un texte ; il faut en
survoler une pour découvrir qu'elle réagit. Un recruteur qui découvre l'écran n'a aucune raison
d'essayer.

**Ce qui est déjà retenu se signale par un rond de huit pixels.** Il se remplit quand le terme est
posé. À cette taille, la différence entre vide et plein se voit mal, et rien n'écrit ce qu'elle
signifie.

**Le panneau est trop étroit.** Il plafonne à 384 pixels, alors qu'il s'ouvre par-dessus une carte
trois fois plus large qui, à cet instant, n'a rien à montrer. Les intitulés longs passent à la ligne
sur trois niveaux, et la liste devient une colonne de pavés.

**Une maquette a été fournie** le 2026-08-12 et sert de référence : panneau élargi, bouton d'ajout
carré sur chaque ligne, case cochée verte et mention « ajouté » sur les lignes retenues, champ de
recherche encadré avec une loupe.

## Périmètre

- **Le panneau s'élargit à la carte de la phrase** qu'il recouvre, au lieu d'un plafond en pixels.
- **Chaque ligne porte un bouton d'ajout carré**, à son bord d'attaque, qui dit que la ligne se
  clique.
- **Une ligne déjà retenue montre une case cochée** en couleur d'accent, et la mention **« ajouté »**
  à l'autre bord.
- **Le champ de recherche du panneau retrouve un cadre** et une loupe.
- **Le bouton reste « + mot-clé »** pendant l'ouverture, passé en couleur d'accent, au lieu de se
  changer en croix.
- **Les lignes s'aèrent** — hauteur et espacement montent d'un cran.
- **La mention « ajouté » entre dans les trois langues** comme clé de traduction.

## Hors périmètre

- **Tout regroupement de la liste par intertitres.** La maquette en montre deux — « TECHNOLOGIES » et
  « COMPÉTENCES » — et cette information n'existe nulle part. Le détail est en *Décisions de
  cadrage* ; la tâche serveur qui la créerait appartient à la lane `espace/api` et **n'est pas
  ouverte ici**.
- **Le panneau d'auto-complétion du métier**, juste à côté sur le même écran. Il s'ouvre sous le
  champ où l'on tape déjà, n'a pas de champ de recherche à lui, donc rien à encadrer. Le composant
  qui le porte sert aussi à l'inscription des entreprises : le retoucher déborderait sur un parcours
  sans rapport.
- **Le formulaire de critères `FiltersPrimary.vue`**, qui monte le même composant mais **n'est rendu
  nulle part** — c'est du code mort depuis que la phrase l'a remplacé. Il n'est ni retouché, ni
  supprimé : le supprimer est une décision qui se prend pour elle-même.
- **La position du panneau et son mode plein écran sur mobile.** Le plein écran hérite des lignes
  redessinées, pas de la nouvelle largeur — il occupe déjà l'écran entier.
- **Le classement des suggestions**, qui reste celui du serveur : du terme le plus porté par les
  profils au moins porté.

## Décisions de cadrage

**Pas de regroupement, et ce n'est pas un renoncement — c'est une impossibilité mesurée.** Trois
constats, dans l'ordre où ils ont été établis :

1. **Les mots-clés proposés viennent d'une source unique et plate** : les compétences écrites sur les
   profils, lues dans la copie locale de Jarvi, mises en minuscules et dédoublonnées. Rien n'y
   distingue une technologie d'un savoir-faire.
2. **La nature de suggestion livrée par `API-037` ne s'applique pas ici.** Elle sépare un métier du
   dictionnaire — qui élargit aux synonymes — d'un terme trouvé à la lettre, mais seulement pour le
   champ *intitulé de poste*. Pour les mots-clés, le serveur inscrit toujours la même valeur, et son
   code le dit : *« La liste servie est HOMOGÈNE »*, chaque passage du moteur ne jouant que si le
   précédent n'a rien ramené. Un regroupement là-dessus n'afficherait jamais qu'un seul intertitre.
3. **Emprunter le vocabulaire des métiers pour fabriquer un second groupe a été écarté.** Un mot-clé
   cherche bien dans deux endroits du profil — la ligne de titre et les compétences —, mais les
   métiers proposés sortent d'un dictionnaire normalisé, pas du texte écrit sur les profils. On
   servirait des propositions qui ne ramènent rien, ce que le serveur s'interdit ailleurs : *« une
   suggestion qui ramène zéro profil est le pire échec d'une auto-complétion »*.

**Le panneau prend la largeur de la carte qu'il couvre, et non une valeur en pixels.** Un nombre figé
tiendrait à une taille d'écran et fausserait les autres : à 1024 pixels la carte de la phrase vaut
deux tiers de l'écran, au-delà elle en vaut trois quarts. Le panneau suit cette proportion, donc il
couvre toujours exactement ce qu'il doit couvrir.

**La case cochée ET le mot « ajouté », arbitré avec l'utilisateur le 2026-08-12.** La redondance est
voulue : la case se balaie du regard sur une liste entière, le mot se lit sur la ligne qu'on
regarde. L'une répond à « où en suis-je », l'autre à « et celle-ci ? ». La case seule avait été
proposée, et écartée.

**Le mot « ajouté » ne doit pas être lu deux fois.** La ligne est déjà une option de liste dont l'état
retenu est porté par l'attribut prévu pour ça — un lecteur d'écran annonce donc déjà « sélectionné ».
Ajouter un mot visible sans le masquer à la lecture ferait entendre l'information en double.

**Le champ de recherche retrouve un cadre, ce qui renverse une décision écrite.** Le code justifiait
son absence : *« le panneau est déjà l'objet qui se détache, un second contour dedans ne délimite
rien de plus »*. Cet argument a été écrit pour un panneau de 384 pixels, où le champ occupait toute
la largeur. Élargi à la carte entière, le champ flotte dans un grand vide et son bord d'arrivée ne se
devine plus. Le motif d'origine reste écrit dans le commentaire, corrigé et non effacé — sinon
quelqu'un retirera le cadre de bonne foi dans six mois.

**Le bouton garde son libellé, et son nom lu suit son libellé.** Aujourd'hui il devient une croix à
l'ouverture, et son nom accessible bascule sur « Fermer ». Le garder en « + mot-clé » tout en le
faisant annoncer « Fermer » créerait un écart entre ce qui est écrit et ce qui est lu — un défaut
d'accessibilité caractérisé. Le nom lu suit donc le libellé visible. **Les trois sorties du panneau
sont conservées** : recliquer le bouton, la touche d'échappement, et le clic à l'extérieur. Le plein
écran mobile garde en plus sa croix explicite, seule sortie possible quand le bouton passe dessous.

## Règles et cas limites

- **Les trois notes muettes gardent leur place et leur exclusivité** : chargement, service
  indisponible, aucun résultat. Aucune ne s'affiche sous le seuil de saisie.
- **La ligne « ajouter le terme tel quel » survit à la panne**, comme aujourd'hui : ce terme part
  dans la recherche sans passer par le vocabulaire.
- **Le bouton d'ajout de chaque ligne n'est pas un arrêt de tabulation.** Parcourir vingt suggestions
  coûterait vingt tabulations de plus, pour un geste que la ligne entière offre déjà. Il est
  décoratif ; c'est la ligne qui reste l'élément actionnable.
- **Le parcours clavier existant ne bouge pas** : flèches pour se déplacer, entrée pour poser,
  échappement pour fermer, tabulation pour sortir.
- **Recliquer une ligne retenue la retire**, comportement actuel conservé — la case cochée doit donc
  se décocher sous le doigt.
- **Aucun texte en dur** : « ajouté » est une clé de traduction, présente dans les trois langues.
- **Le sens de lecture arabe est respecté** : bouton d'ajout, case et mention suivent les bords
  logiques, pas la gauche et la droite.
- **Les deux thèmes sont vérifiés.** Le filet qui borde le panneau existe parce qu'en thème sombre le
  fond du menu et celui de la carte tombent sur la même valeur — il ne disparaît pas.

## Documentation impactée

**Aucune, et c'est vérifié.** Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit ce composant — ni le système
de design, ni l'arborescence de l'espace. Le dossier `webapp/base/app/components/ui/` ne porte pas de
`README.md`, donc aucune convention de dossier ne change. Ni route, ni contrat, ni règle métier n'est
touché.

## Définition de terminé

- [x] Le panneau ouvert couvre la carte de la phrase, à 1024 pixels comme au-delà.
- [x] Chaque suggestion montre un bouton d'ajout carré à son bord d'attaque.
- [x] Une suggestion retenue montre une case cochée en couleur d'accent et la mention « ajouté ».
- [x] Recliquer une suggestion retenue la retire, et la case se décoche.
- [x] Le champ de recherche du panneau est encadré et porte une loupe.
- [x] Le bouton reste « + mot-clé » en couleur d'accent pendant l'ouverture, et son nom lu dit
      « ajouter » et non « fermer ».
- [x] Les trois sorties du panneau fonctionnent : reclic, échappement, clic extérieur.
- [x] La tabulation ne s'arrête pas sur les boutons d'ajout des lignes.
- [x] Un lecteur d'écran annonce l'état retenu **une seule fois**.
- [x] La clé « ajouté » existe dans les trois langues ; le test de parité passe.
- [x] Les trois notes muettes et la ligne d'ajout libre sont inchangées.
- [x] Le mode plein écran mobile reçoit les nouvelles lignes sans changer de taille ni de position.
- [x] Captures fournies en thème clair et sombre, avec une ligne retenue et une ligne libre visibles
      ensemble.

## Vérification

Preuves prises le 2026-08-12 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, sur
« Nouvelle recherche », panneau ouvert avec « synthèse » retenu et les autres suggestions libres.

| Ce qui est prouvé | 1440 px | 1100 px |
| --- | --- | --- |
| Bord gauche du panneau vs bord gauche de la carte de la phrase | **écart 0 px** | **écart 0 px** |
| Largeur du panneau · de la carte | 1084 · 1060 | 740 · 716 |
| Cadre du champ, et sa couleur | 1 px, accent | 1 px, accent |
| Loupe présente | oui | oui |
| Texte du bouton · son nom lu | « Ajouter un mot-clé » · **identique** | identique |
| Icône du bouton, panneau ouvert | `plus` | `plus` |
| Carré d'une ligne retenue | 20×20, `check`, fond d'accent | idem |
| Carré d'une ligne libre | 20×20, `plus`, fond transparent | idem |
| Mention « ajouté », et son silence | présente, `aria-hidden` | idem |
| Hauteur d'une ligne | 46 px | 46 px |
| Arrêts de tabulation ajoutés dans le panneau | **0** | **0** |

**La largeur est vérifiée par le calcul autant que par l'œil** : 1084 = 1060 + 24, soit la carte plus
un rembourrage — exactement ce qu'il faut pour que le bord droit s'aligne sur la colonne pendant que
le bord gauche rejoint la phrase. Les deux paliers de la grille tombent juste sans retouche.

**Les deux thèmes sont vérifiés** : l'accent mesuré vaut `rgb(15, 75, 55)` en clair et
`rgb(198, 231, 107)` en sombre, sur le cadre du champ, la bordure du bouton et le fond de la coche.

**Le plein écran mobile** (390 × 780) occupe exactement la fenêtre, à l'origine, garde sa croix de
sortie nommée « Fermer la liste des mots-clés », et reçoit bien les lignes de 46 px avec leur
mention.

**La règle CI des conventions passe** — 19 règles, aucune violation. Elle a été jouée ici et non au
point de contrôle parce que les commentaires ajoutés risquaient précisément la règle
`app-no-comment-journal` ; les trois qui racontaient un chemin ont été réécrits au présent, ce que la
règle n'aurait pas attrapé. Aucune autre gate n'a tourné : elles appartiennent à l'ouverture de la
PR.

**Captures** : `ent104-light-1440.png`, `ent104-dark-1100.png`, `ent104-mobile-390.png`, dans le
dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

## Livré

- `webapp/base/app/components/ui/TagPicker.vue` — la largeur du panneau latéral devient réglable par
  l'appelant ; la ligne de suggestion troque son point de huit pixels contre un carré qui porte un
  `+` ou une coche ; la mention « ajouté » apparaît sur les lignes retenues, muette aux lecteurs
  d'écran ; le champ de recherche gagne un cadre et une loupe ; le bouton garde son libellé et son
  nom lu pendant l'ouverture au lieu de devenir une croix.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — pose la largeur à couvrir, calculée
  depuis la grille, et passe le libellé « ajouté ».
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — clé `search.primary.keywords.added`.

## Reste à faire (hors périmètre)

- **Le vocabulaire des compétences n'est classé par rien.** Tant qu'il ne l'est pas, la liste reste à
  plat et les intertitres de la maquette sont impossibles. C'est une tâche de la lane `espace/api` ;
  elle n'est pas ouverte.
- **Une même compétence apparaît deux fois dans les suggestions** — « système d'information » sort
  deux fois alors que le serveur groupe sur la valeur mise en minuscules, ce qui désigne deux
  écritures distinctes du même mot, très probablement deux apostrophes différentes. Défaut de
  données côté serveur, lane `espace/api`.
- **`FiltersPrimary.vue` monte encore ce composant et n'est rendu nulle part.** Sa suppression est
  une décision qui se prend pour elle-même.
