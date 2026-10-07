# TASK ENT-075 — entreprise : la carte de nouvelle recherche prend son dessin, et ses mots-clés s'ouvrent sur le côté

- **Créée le** : 2026-08-10.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-10 — la carte tient en deux colonnes séparées par un filet, la
  phrase se lit centrée en hauteur et bornée en largeur, le bouton d'ajout coiffe la colonne des
  mots-clés dont la liste défile sous lui, et le menu s'ouvre en glissant sur le côté, à la hauteur
  exacte du filet, fermé par un contour.
- **Type** : Frontend entreprise — habillage d'un écran existant et sens d'ouverture d'un panneau du
  kit. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui change ici se mesure en pixels et
  en position à l'écran : une répartition de colonnes, un filet, un alignement vertical, le côté par
  lequel un panneau s'ouvre. Le webapp n'a aucun harnais de test de composant
  (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et jsdom ne mesure ni largeur ni
  position. **La preuve attendue est une capture de l'écran connecté**, thème sombre et thème clair,
  grand écran et largeur téléphone, panneau des mots-clés ouvert dans les deux cas — complétée par
  des mesures relevées dans la page, seule façon de prouver un glissement ou l'absence d'un halo qui
  n'apparaît qu'au focus. Aucune clé de langue n'est ajoutée ; le test de parité posé par `ENT-059`
  tourne quand même, puisqu'un libellé change de valeur.
- **Réf** : **`ENT-066` — amont** (elle a posé les deux colonnes que celle-ci habille) ·
  **`ENT-067` — amont** (elle a sorti la carte sur son écran dédié) · **`ENT-059` — amont** (le test
  de parité des trois langues) · `webapp/spaces/entreprise/app/components/search/Sentence.vue` ·
  `webapp/base/app/components/ui/TagPicker.vue` · `webapp/base/i18n/locales/{fr,en,ar}.yaml`

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page « Décrivez le profil que vous
cherchez » — celle qui s'ouvre depuis l'accueil quand on lance une recherche par critères. L'écran
est un grand encadré sombre, avec la phrase à remplir à gauche et les mots-clés à droite.

**Trois choses s'y voient mal.**

1. **L'encadré descend jusqu'en bas de l'écran, mais tout son contenu reste collé en haut.** Les
   trois quarts du bas sont un grand rectangle sombre et vide. La phrase, qui est pourtant le seul
   geste de la page, se retrouve tassée dans un bandeau haut.

2. **Rien ne sépare les deux colonnes.** La phrase et les mots-clés flottent dans le même aplat : on
   ne voit pas qu'il s'agit de deux questions distinctes, l'une sur le poste, l'autre sur les
   compétences.

3. **Le bouton d'ajout de mot-clé est une petite pastille discrète** — un `+` suivi de « mot-clé » —
   posée sous un titre qui, lui, occupe toute la largeur de la colonne. Le geste principal de cette
   colonne est ce qu'on y voit le moins.

**Et le menu qui s'ouvre recouvre ce qu'il faudrait voir.** Aujourd'hui, cliquer sur ce bouton fait
descendre un panneau juste en dessous, large comme la colonne : il masque les mots-clés déjà posés,
c'est-à-dire précisément la liste qu'on est en train de compléter. La colonne est étroite, donc le
panneau l'est aussi, et les intitulés suggérés s'y coupent.

## Périmètre

Le design de référence est la capture fournie par l'utilisateur le 2026-08-10, décrite ici en toutes
lettres pour qu'elle survive à la session :

- **Le contenu de la colonne de gauche se centre en hauteur.** L'encadré garde sa taille — il descend
  jusqu'en bas —, mais la phrase se place à mi-hauteur, le vide se répartissant au-dessus et en
  dessous. **La colonne de droite, elle, reste calée en haut** : son titre part du bord supérieur.
- **Un filet vertical sépare les deux colonnes**, sur toute la hauteur de l'encadré.
- **La colonne des mots-clés se resserre** : la phrase gagne la largeur qu'elle perd.
- **La phrase monte au palier de sous-titre** et ses lignes respirent davantage.
- **La phrase est bornée en largeur puis centrée dans sa colonne** : elle ne va plus jusqu'au filet,
  et laisse autant de vide de chaque côté.
- **Le bouton d'ajout prend toute la largeur de sa colonne** : un rectangle bordé, arrondi, son
  libellé centré, et ce libellé dit « + Ajouter un mot-clé » au lieu de « mot-clé ».
- **Le menu des mots-clés s'ouvre sur le côté, vers la gauche** : il sort latéralement du bouton et
  s'étend par-dessus la colonne de la phrase. Les mots-clés déjà posés restent visibles, et le menu
  devient assez large pour écrire un intitulé en entier.
- **Il arrive en glissant**, depuis le bouton vers la phrase, et non par simple apparition.
- **Il occupe exactement la hauteur du filet dès son ouverture**, du haut au bas de la colonne, avant
  même qu'une suggestion soit arrivée. Il ne grandit plus au fil de la frappe.
- **Un filet de contour le ferme**, faute de quoi rien ne le distingue de la carte en thème sombre.
- **Sa zone de saisie n'a ni cadre ni halo bleu** : du texte posé en haut du panneau.
- **Sur téléphone, ce même menu s'ouvre en plein écran**, avec de quoi le refermer.
- **Le bouton d'ajout coiffe la colonne** et n'en bouge plus ; **la liste des mots-clés posés défile**
  sous lui dès qu'elle dépasse la place offerte.
- **Les étiquettes sont fines** : elles servent à relire une liste, pas à être cliquées une par une.
- **Les tailles suivent l'écran** : la répartition des colonnes et le palier de la phrase changent à
  1024 px et à 1280 px, au lieu d'une valeur unique tenue de force partout.

## Hors périmètre

- **La barre haute** — nom de la recherche, décompte de profils, bouton « Créer ma recherche » : elle
  appartient à `ENT-067` et ne bouge pas.
- **Le contenu de la phrase** : les mêmes trois critères, les mêmes libellés, la même grammaire. Seuls
  sa taille et son placement changent.
- **La recherche assistée** (`SearchFiltersAi`), qui garde son dessin.
- **Ce que le menu propose** : les suggestions, leur seuil de déclenchement, leur tri et le terme
  libre ne changent pas. Seul le côté par lequel le panneau s'ouvre change.
- **`SearchFiltersPrimary`** : ce composant n'est plus monté par aucune page — vérifié sur tout le
  webapp — alors que son propre commentaire et celui de `SearchSentence` affirment qu'il « sert
  toujours la console de la Visionneuse ». Le retirer, ou le remettre en service, est une décision
  qui n'appartient pas à cette tâche. Elle est **nommée ici et laissée ouverte**.

## Décisions de cadrage

**La capture fait foi, et elle est décrite en mots dans la section *Périmètre*.** Une carte de tâche
survit à la conversation qui l'a produite ; une image collée dans un fil de discussion, non. Ce qui
n'est pas écrit ci-dessus n'est pas dans le design.

**Le vide du bas se comble par le centrage, pas en raccourcissant l'encadré.** Raccourcir l'encadré
déplacerait le vide sur le fond de page sans rien régler : l'écran resterait un bandeau haut suivi de
rien. Centrer donne à la seule question de la page la place qu'elle mérite. C'est aussi ce que montre
la capture.

**Les deux colonnes ne s'alignent pas de la même façon, et c'est voulu.** La phrase se centre parce
qu'elle est de hauteur fixe — trois lignes, toujours. La colonne des mots-clés grandit à mesure qu'on
en pose : centrée, elle se déplacerait à chaque ajout. Calée en haut, elle pousse vers le bas et rien
ne bouge au-dessus.

**Le menu s'ouvre à côté plutôt qu'en dessous, et c'est le point de la demande.** En dessous, il
recouvre la liste qu'on complète ; à côté, il recouvre la phrase — qu'on a déjà remplie et qu'on ne
consulte pas pendant qu'on ajoute des mots-clés. Il y gagne aussi la largeur qui lui manquait.

**Sur téléphone, le plein écran plutôt que le repli sous le bouton.** Quand les deux colonnes
s'empilent, il n'y a plus de côté gauche : il faudrait alors deux comportements à maintenir et à
prouver. Le plein écran en donne un troisième, mais c'est le seul qui rende la saisie confortable là
où le clavier virtuel mange déjà la moitié de l'écran.

**Le plein écran ne passe PAS par `USlideover`, contrairement à ce que ce cadrage prévoyait.** La
primitive Nuxt UI aurait apporté le fond, le piège de focus et la touche d'échappement — mais elle
impose sa propre structure de contenu, donc le champ de recherche et la liste auraient été écrits
**deux fois** dans le composant, une version pour le tiroir et une pour le panneau flottant. Deux
copies d'un même balisage divergent au premier correctif. Le panneau garde donc un seul balisage et
change de classes selon sa position — et ce que la primitive apportait était, ici, déjà là : la
touche d'échappement et le retour du focus au bouton existent depuis la première version, et la
tabulation referme le panneau, ce qui rend le piège de focus sans objet. Restait le fond, qui tient
en trois lignes, et une croix de fermeture, qu'il fallait de toute façon écrire puisque le bouton
d'origine passe **sous** le plein écran.

**Le palier typographique retenu est celui des sous-titres, et seulement au-delà de 1280 px.** Le
cadrage laissait la valeur ouverte, à caler sur capture : mesurée sur la maquette, la phrase y est
environ une fois et demie plus grande qu'avant, ce qui correspond à ce palier. Il ne vaut cependant
que sur les grands écrans — **trois paliers, un par largeur** : corps de texte sous 1024 px,
intertitre entre 1024 et 1280, sous-titre au-delà. Les lignes de la phrase sont insécables : au
palier le plus haut, « avec … d'expérience, » sort d'un écran de téléphone, et se serre à 1024.

**La répartition des colonnes suit la largeur elle aussi** — deux tiers / un tiers à partir de 1024
px, trois quarts / un quart à partir de 1280. À 1024 px, un quart ne fait que 230 px : chaque
étiquette y arrivait tronquée, et le menu ouvert à côté n'avait plus d'ancrage lisible.

**Les étiquettes se lisent, elles ne se manipulent pas une par une.** Ramenées de 40 à 28 px de
haut, avec un libellé qui se coupe par des points de suspension plutôt que de forcer la colonne. La
croix de retrait descend à 24 px — sous le confort tactile recommandé, mais c'est un geste de
correction rare, et la vraie sortie de secours reste le menu, où recliquer un terme le retire.

**La liste défile, et le bouton la coiffe.** Dix-huit mots-clés dans une colonne de carte sortaient
par le bas, par-dessus le fond de page. La liste seule défile désormais — et le bouton d'ajout est
passé **au-dessus** d'elle, contrairement à la maquette qui le montre en dessous. Sous la liste, il
se dérobait vers le bas à chaque terme ajouté : le geste le plus répété de cette colonne changeait
de place à chaque fois qu'on le répétait. Au-dessus, il ne bouge plus, et le menu s'ouvre en face
de lui plutôt qu'en face du milieu d'une liste.

**Le bouton est premier dans le document, pas seulement à l'écran.** Un bouton placé en tête par une
simple règle d'affichage laisserait le parcours au clavier traverser dix-huit croix de retrait avant
de l'atteindre. La rangée compacte — la forme historique du composant, sans appelant vivant
aujourd'hui — garde son bouton en fin de ligne par l'ordre d'affichage : elle hérite du décalage,
d'un seul élément, là où personne ne le rencontre.

**Piège rencontré, et il vaut d'être écrit : une colonne flexible qui « enroule » déverse à côté.**
Un conteneur en colonne avec retour à la ligne autorisé ne pousse pas son débordement vers le bas —
il ouvre une **seconde colonne à droite**, hors de la carte. Le bouton d'ajout s'y était échappé,
hors écran. La disposition en bloc interdit donc explicitement l'enroulement.

**Les deux sélecteurs restent vides tant que rien n'est choisi.** La maquette y montre « peu
importe » en gris, mais ce mot a été retiré volontairement quatre jours plus tôt par `ENT-066` — la
maquette est antérieure à ce changement. Arbitré avec l'utilisateur le 2026-08-10 : la décision
d'`ENT-066` tient, la maquette ne la rouvre pas.

**Le panneau latéral prend sa hauteur définitive à l'ouverture, il ne la calcule plus.** Ouvert sur
une liste vide, puis grandissant à mesure que les suggestions arrivent, il faisait bouger sous les
yeux ce qu'on était en train de lire — et le premier résultat n'était jamais au même endroit d'une
frappe à l'autre. Il épouse désormais la hauteur du bloc qui le porte : du haut du bouton au bas de
la carte, aligné sur le filet qui sépare les deux colonnes. La liste défile dedans. Les deux
positions verticales, elles, gardent leur plafond calculé : sous un champ, un panneau haut de six
cents pixels couvrirait tout.

**Le titre de la colonne descend DANS le composant, par un emplacement laissé à l'appelant.** Posé
au-dessus, il faisait commencer le panneau une hauteur de titre plus bas que le filet — impossible à
rattraper sans mesurer, puisque le panneau se cale sur le composant qui le porte. Déplacé dedans, le
composant devient toute la colonne, et l'alignement tombe juste sans un seul calcul. Le titre reste
écrit par l'appelant : le kit ne connaît aucun vocabulaire métier, il ne fait que lui réserver sa
place.

**Un filet de contour ferme le panneau, et c'est un défaut de fond qu'il corrige.** La couleur d'un
menu suit celle des surfaces posées, parce qu'un menu se pose normalement sur le **fond de page**,
dont il se détache. Posé sur une **carte**, il tombe sur la valeur exacte de cette carte : mesuré
`#212121` des deux côtés en thème sombre. Et l'ombre ne sépare pas sur fond sombre — la feuille de
thème le dit déjà, c'est la surface qui sépare. Le panneau n'avait donc plus aucun bord. Le trait le
lui rend, dans les deux thèmes : en clair, où le panneau est bien plus clair que la carte
(`#f8fbfa` contre `#e2e9e3`), il ne fait que le fermer. Écarté au passage : monter le fond d'un cran
— l'échelle d'élévation s'inverse entre les thèmes, il aurait fallu une valeur par thème, et le seul
gris disponible fait une dalle sur six cent soixante pixels de haut.

**Il entre en glissant, et le sens du glissement se lit en propriétés logiques.** Le mouvement dit
d'où il sort ; une apparition sèche laisse chercher ce qui vient de changer sur un écran large. En
arabe, le panneau sort du bord opposé, donc le glissement s'inverse avec lui. Les autres positions
gardent un simple fondu : sous le champ, le panneau est déjà à sa place, un déplacement de plus n'y
apporterait rien.

**La zone de saisie perd son cadre ET son anneau de focus.** Le cadre d'abord : le panneau est déjà
l'objet qui se détache du fond, un second contour à l'intérieur ne délimite plus rien. L'anneau bleu
du navigateur ensuite — et c'en est un vrai retrait d'indicateur de focus, donc il se justifie : le
champ reçoit le focus **à l'ouverture du panneau**, il est le seul élément de saisie qui puisse le
prendre, et la tabulation referme le panneau au lieu d'y circuler. Il n'existe donc aucun état où
quelqu'un aurait à chercher où se trouve le focus dans ce panneau ; le curseur clignotant suffit.

**Le kit reçoit des réglages, il ne change pas de comportement par défaut.** `UiTagPicker` vit dans
`base/app/components/ui/`, sans domaine et réutilisable partout. Deux réglages neufs — la forme du
bouton (pastille compacte ou bloc pleine largeur) et le côté d'ouverture du panneau —, tous deux
avec la valeur d'aujourd'hui par défaut. Un composant du kit ne change pas d'allure pour tous ses
appelants parce qu'un écran a un besoin.

**Le libellé du bouton reste affiché même une fois des mots-clés posés.** Aujourd'hui il disparaît
dès le premier mot-clé, ne laissant qu'un `+` : acceptable pour une pastille, absurde pour un bouton
pleine largeur, qui serait alors une grande boîte vide avec un `+` au milieu.

**Les textes neufs s'écrivent dans les trois langues du même geste.** Le test de parité posé par
`ENT-059` échoue sinon — c'est exactement ce pour quoi il a été écrit. Le libellé du bouton change de
valeur sans changer de clé (`search.sentence.keywordAdd`), et aucune clé n'est ajoutée.

**Aucune phrase d'aide sous le bouton, contrairement à la maquette.** Elle y annonçait « Un seul
mot-clé suffit pour qu'un profil corresponde. » — soit exactement ce que dit déjà le titre de la
colonne, « qui maîtrise **l'un** de ces mots-clés ». Écrite puis retirée en cours d'implémentation,
sur constat à l'écran : deux lignes de gris pour redire le titre qui les surmonte. La colonne reste
nue. `search.primary.keywords.hint`, qui porte la même idée en quatre lignes, n'est pas davantage
reprise : elle appartient au composant pour lequel elle a été écrite.

## Règles et cas limites

- **« À gauche » veut dire « du côté de la phrase », pas « à l'ouest ».** En arabe, l'écran est en
  miroir : la colonne des mots-clés passe à gauche et la phrase à droite. Le panneau doit s'ouvrir du
  côté de la phrase dans les deux cas — donc en propriétés logiques, jamais en `left`/`right`.
- **Le panneau ne doit pas se faire couper.** L'encadré vit dans une zone qui défile, à l'intérieur
  d'une coque à hauteur d'écran fixe. Le calcul qui borne déjà la hauteur du panneau au conteneur qui
  masque son débordement reste en place et doit valoir aussi pour la largeur.
- **Sous 1024 px, il n'y a qu'une colonne** : le filet vertical disparaît, le centrage vertical n'a
  plus d'objet, et le bouton reste pleine largeur.
- **Le clavier doit rester praticable dans les deux formes.** Panneau latéral : flèches, Entrée,
  Échap et Tab se comportent comme aujourd'hui. Plein écran : le focus entre dans le panneau à
  l'ouverture, n'en sort pas tant qu'il est ouvert, et **revient sur le bouton** à la fermeture.
- **Le bouton reste l'ancre du `<label>`** de l'appelant : il est le seul élément toujours présent,
  quelle que soit sa forme.
- **Retirer le dernier mot-clé ne doit pas perdre le focus** — la reprise existante sur le bouton
  vaut aussi pour le bouton pleine largeur.
- **Le libellé « + Ajouter un mot-clé » porte son `+` dans l'icône, pas dans le texte traduit** : une
  langue ne traduit pas un signe.
- **Rien ne se code en dur** : les couleurs du filet, de la bordure du bouton et du texte d'aide
  viennent des tokens du kit, et se tiennent en thème clair comme en thème sombre.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`** : ni route, ni contrat, ni règle métier ne change.
- **Aucun `README.md`** : `base/app/components/README.md` gouverne la création d'un `Ui*` et le
  nommage ; ici aucun composant n'est créé et aucune convention ne bouge. Deux réglages s'ajoutent à
  un composant existant, ce que le README prévoit sans rien en dire de particulier.
- **Les commentaires de `UiTagPicker`** portent la doctrine du composant : celui qui explique
  pourquoi le panneau bascule entre le haut et le bas doit dire, dans le même esprit, pourquoi il
  bascule aussi entre le côté et le plein écran. Même commit.

## Définition de terminé

- [x] Au-delà de 1024 px, la phrase se lit à mi-hauteur de l'encadré et la colonne des mots-clés part
      du bord supérieur.
- [x] Un filet vertical sépare les deux colonnes sur toute la hauteur de l'encadré, et disparaît
      quand elles s'empilent.
- [x] La colonne des mots-clés est plus étroite qu'avant, la phrase plus large.
- [x] La phrase se lit au palier de sous-titre au-delà de 1280 px, à l'intertitre entre 1024 et 1280,
      au corps de texte en dessous — et rien n'est coupé à aucune de ces largeurs.
- [x] La colonne des mots-clés vaut un tiers de la carte à 1024 px et un quart à partir de 1280.
- [x] Avec dix-huit mots-clés posés, la liste défile **dans** la carte : rien ne dépasse son bord
      inférieur, et le bouton d'ajout reste visible.
- [x] Le bouton d'ajout est en tête de colonne et n'y bouge plus, quel que soit le nombre de termes ;
      la tabulation l'atteint avant les croix de retrait.
- [x] La phrase est bornée en largeur et centrée dans sa colonne, avec le même vide de chaque côté.
- [x] Le bouton d'ajout occupe toute la largeur de sa colonne, dit « + Ajouter un mot-clé », et garde
      son libellé une fois des mots-clés posés.
- [x] Aucune phrase d'aide ne double le titre de la colonne.
- [x] Sur grand écran, ouvrir le menu le fait apparaître **à côté du bouton, du côté de la phrase** ;
      les mots-clés déjà posés restent visibles.
- [x] Le menu **glisse** en entrant, du bouton vers la phrase — et dans l'autre sens en arabe.
- [x] Il couvre exactement la hauteur du filet dès l'ouverture, avant toute suggestion, et ne change
      plus de taille ensuite.
- [x] Un filet de contour le sépare de la carte, en thème sombre comme en thème clair.
- [x] Sa zone de saisie n'a ni bordure, ni anneau de focus, ni ombre.
- [x] Sur largeur téléphone, ouvrir le menu occupe l'écran entier, se referme, et rend le focus au
      bouton.
- [x] En arabe, le menu s'ouvre du côté de la phrase, c'est-à-dire à droite.
- [x] Les trois langues portent le nouveau libellé ; le test de parité passe.
- [x] Aucun autre appelant vivant de `UiTagPicker` : `SearchFiltersPrimary`, le seul, n'est monté par
      aucune page. Les deux réglages neufs sont éteints par défaut, la rangée compacte est rendue à
      l'identique.
- [x] Captures fournies : thème clair et thème sombre, grand écran et largeur téléphone, menu ouvert
      dans les deux formats.

## Vérification

Preuves prises le 2026-08-10 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, écran
« Décrivez le profil que vous cherchez ». Captures et scripts de relevé dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

**La disposition, mesurée dans la page**

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| Colonnes, 1440 px | `1016,3 px` · `338,8 px` — trois quarts / un quart |
| Colonnes, 1024 px | `627,4 px` · `313,7 px` — deux tiers / un tiers |
| Phrase bornée et centrée | bloc de 768 px, **124 px de vide de chaque côté** |
| Phrase centrée en hauteur | occupe les 662 px de la colonne, contenu au milieu |
| Paliers de la phrase | 26,56 px au-delà de 1280 · 19,49 px à 1024 · 16,23 px à 390 |
| Filet vertical | 1 px, sur les 662 px de hauteur de carte |
| Étiquette | **28 px** de haut, contre 40 avant |

**Le panneau des mots-clés**

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| Ouvert à côté, du côté de la phrase | `x=676`, large de 384 px ; le bouton commence à `x=1088` |
| Aligné sur le filet | `y=204`, haut de **662 px** — l'étendue exacte du filet |
| Glissement à l'entrée | bord gauche `711 → 701 → 697 → 695 px`, opacité `0 → 0,58 → 0,84 → 1` |
| Glissement inversé en arabe | bord gauche `345 → 355 → 359 → 361 px` |
| Zone de saisie nue | `border-width: 0px`, `outline: aucun`, `box-shadow: none`, focus reçu |
| Plein écran sur téléphone | panneau `fixed` 390 × 844, fond présent, croix de fermeture |
| Retour du focus | après fermeture, `document.activeElement` **est** le bouton « Ajouter un mot-clé » |

**La liste sous charge** — dix-huit mots-clés posés, quatre formats :

| Format | Liste | Débordement de la carte |
| --- | --- | --- |
| 1024 × 900 | 358 px, tient | aucun |
| 1280 × 640 | **défile** : 339 px visibles sur 361 | aucun |
| 1440 × 900 | 325 px, tient | aucun |
| 390 × 844 | 280 px, tient | aucun |

**Le diagnostic qui a motivé le filet de contour** : `--dropdown-bg` et `--surface` valent la même
couleur en thème sombre, `#212121`. Le panneau posé sur une carte n'avait donc aucun bord. En thème
clair les deux diffèrent (`#f8fbfa` contre `#e2e9e3`) et le filet ne fait que fermer le panneau.

**Captures** : `preuve-dark-1440.png`, `preuve-dark-1440-panneau.png`, `preuve-light-1440.png`,
`preuve-light-1440-panneau.png`, `preuve-dark-390.png`, `preuve-dark-390-panneau.png`,
`preuve-ar-1440-panneau.png`, `preuve-charge-{1024,1280x640,1440,390}.png`.

**Tests joués** : `base/i18n/locales/parity.vitest.ts` — 9 verts. Aucune gate qualité n'a été jouée :
elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — grille à trois paliers (empilée,
  deux tiers / un tiers à `lg`, trois quarts / un quart à `xl`), phrase bornée et centrée dans les
  deux axes, palier typographique par largeur, et la colonne des mots-clés devient le composant
  lui-même : le titre lui est passé par son emplacement d'en-tête, le filet et la marge portés par
  sa racine.
- `webapp/base/app/components/ui/TagPicker.vue` — deux réglages neufs, `block` (bouton pleine largeur
  en tête de colonne, libellé conservé) et `beside` (ouverture latérale, plein écran sous 1024 px) ;
  un emplacement d'en-tête ; la liste des termes rendue défilante et rétractable ; étiquettes
  ramenées à 28 px avec libellé tronqué et marges en propriétés logiques ; panneau en quatre
  positions pour un seul balisage, avec transition d'entrée, filet de contour, et champ de recherche
  débarrassé de son cadre et de son anneau de focus.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.sentence.keywordAdd` passe de « mot-clé » à
  « Ajouter un mot-clé », dans les trois langues.

## Reste à faire (hors périmètre)

- **`SearchFiltersPrimary` est du code mort** : aucune page ne le monte, et son commentaire — comme
  celui de `SearchSentence` — affirme le contraire. Le retirer ou le remettre en service appartient à
  une autre tâche.
- **`design-system.md` n'inventorie pas `UiTagPicker`** dans sa liste du kit maison. L'écart est
  antérieur à cette tâche, qui ne crée pas le composant.
- **Le glissement ignore `prefers-reduced-motion`** — comme le reste du produit à ce jour. Le sujet
  est global au socle, pas propre à ce panneau.
