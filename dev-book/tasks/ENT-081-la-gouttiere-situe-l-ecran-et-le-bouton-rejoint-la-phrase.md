# TASK ENT-081 — entreprise : la gouttière situe l'écran, et le bouton rejoint la phrase

- **Créée le** : 2026-08-10.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`, **reprise et élargie le 2026-08-11** par la
  session `9d0eb3f2-2064-4ffd-94a9-dbc606ce1450` — décidé avec l'utilisateur : les six retouches
  demandées tombaient dans les deux fichiers que cette carte tenait déjà ouverts, et deux tâches de
  front dans `Sentence.vue` auraient produit un commit impossible à découper.
  Reprise une troisième fois le 2026-08-11 par la session d'origine, sur retour d'écran de
  l'utilisateur : cinq retouches de plus, dans les mêmes fichiers. **Les trois passages tiennent dans
  un seul commit**, et ce n'est pas un choix de confort : ils modifient les mêmes lignes des mêmes
  fichiers, et le découpage partiel d'un fichier n'existe pas dans cet environnement.
- **Statut** : ✅ fait le 2026-08-11 — la gouttière situe l'écran par un fil d'ariane, la phrase et
  les mots-clés sont deux cartes séparées par un vide, et le bouton — devenu « Sélectionner les
  profils » — ferme la seconde en pied de carte.
- **Type** : Frontend entreprise — navigation et placement de l'action principale sur un écran
  existant. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui change est une position et un
  chemin de navigation : un fil d'ariane, un bouton qui descend, une bande qui change de contenu.
  Le webapp n'a aucun harnais de test de composant (`webapp/vitest.config.ts` ne couvre que le code
  pur, sans DOM). **La preuve attendue est une capture de l'écran connecté**, thème clair et sombre,
  grand écran et largeur téléphone, plus la vérification que le fil d'ariane mène bien où il
  annonce. La clé de langue ajoutée est couverte : le test de parité posé par `ENT-059` échoue si
  une langue en manque une.
- **Réf** : **`ENT-067` — amont** (elle a posé la barre haute et y a mis le bouton) ·
  **`ENT-075` — amont** (elle a posé le dessin de la carte, et créé le vide où le bouton descend) ·
  `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` ·
  `webapp/spaces/entreprise/app/components/Gutter.vue` ·
  `webapp/spaces/admin/app/components/Topbar.vue` (le fil d'ariane dont celui-ci s'inspire) ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page « Décrivez le profil que
vous cherchez » — celle qui s'ouvre depuis l'accueil quand on lance une recherche par critères.
Sous la barre du haut court une bande fine, la **gouttière** : à gauche la phrase « Décrivez le
profil que vous cherchez », à droite un bouton « Retour aux recherches ».

**Trois choses ne vont pas.**

1. **Le bouton de sortie ment.** Il dit « Retour aux recherches » et ramène en réalité à l'accueil,
   pas à la liste des recherches. Personne ne s'en plaint parce que les deux écrans se ressemblent,
   mais l'étiquette annonce une destination qui n'est pas la sienne.

2. **Rien ne dit où l'on est.** L'écran n'a ni fil d'ariane ni indication de niveau : on sait qu'on
   décrit un profil, on ne sait pas de quelle page on est descendu ni comment remonter autrement
   qu'en cliquant un bouton qui annonce mal sa destination.

3. **Le bouton qui valide est placé avant ce qu'il valide.** « Créer ma recherche » est en haut à
   droite, à **environ 460 pixels au-dessus** de la dernière ligne qu'on vient de remplir. On lit la
   phrase de haut en bas, on finit sur « niveau », et l'action qui conclut se trouve derrière soi.
   Pire, elle partage son coin avec la sortie : le geste le plus engageant de l'écran et celui qui
   l'abandonne sont voisins, alignés, de taille comparable. Pendant ce temps, **deux cent cinquante
   pixels de vide** s'étendent sous la phrase — exactement là où le regard se pose en finissant.

## Périmètre

**La gouttière**

- **Le bouton « Retour aux recherches » disparaît.** Sa clé de traduction reste : l'accueil s'en
  sert encore.
- **Un fil d'ariane le remplace, à gauche** : « Tableau de bord › Nouvelle recherche ». Le premier
  repère est un lien vers l'accueil de l'espace, le second est la page courante, en clair et non
  cliquable.
- **La phrase d'aide passe à droite, en pastille d'information** : une icône « i » suivie du texte
  actuel, en gris discret.
- **Sur téléphone, la gouttière ne garde que le fil d'ariane.** La pastille disparaît.

**Le bouton principal**

- **« Créer ma recherche » quitte la barre haute et descend sous la phrase**, aligné sur son axe,
  dans le vide qu'`ENT-075` a créé en centrant la phrase.
- **Sur téléphone, il se colle au bas de la carte**, dans la zone du pouce.
- **Le compteur « 293 profils » ne bouge pas** : il reste dans la barre haute, avec le nom.
- **La barre haute reste**, avec le nom de la recherche à gauche et le compteur à droite.

**Second périmètre — le dessin de l'écran** *(ajouté le 2026-08-11, sur retour d'écran)*

Six retouches, toutes sur le même écran et dans les mêmes deux fichiers. Elles ne changent aucune
donnée, aucun contrat, aucune règle : elles corrigent ce que l'écran **montre** une fois la
gouttière et le bouton en place.

- **Le bouton passe en bas à DROITE de la carte**, et non plus aligné sur l'axe de la phrase. Vu à
  l'écran, un bouton posé sous le premier mot d'une phrase de trois lignes flotte au milieu du vide ;
  à droite, il ferme le bloc et se trouve là où l'œil finit sa dernière ligne. Sur téléphone, il
  reste collé au bas de la carte, pleine largeur — rien ne change.
- **Les deux choix affichent « Peu importe » par défaut, en gris.** Aujourd'hui « avec … » et
  « niveau … » laissent un blanc : le recruteur ne sait pas s'il a oublié de répondre ou si le
  critère est facultatif. Le mot dit que ne rien choisir est une réponse valable, et le gris qu'elle
  n'est pas encore la sienne.
- **Les champs de la phrase perdent leur soulignement.** Le filet servait à dire « il y a un champ
  ici » ; il n'a plus lieu d'être quand aucun champ n'est jamais vide, et il hachait une phrase qui
  se lit d'un trait.
- **Le curseur devient une main sur les deux choix.** Ils s'ouvrent au clic et se comportent comme
  des boutons ; la flèche de texte annonçait un champ à saisir.
- **La barre haute n'affiche plus rien à gauche tant que la recherche n'a pas de nom** — ni
  « Se nomme tout seul… », ni le crayon. Une invitation à renommer ce qui n'existe pas encore occupe
  la place la plus visible de l'écran pour ne rien dire.
- **Le nombre de profils devient plus gros que son unité**, à droite de la barre. C'est le chiffre
  qu'on suit du coin de l'œil en remplissant la phrase ; « 293 » et « profils » à la même taille se
  lisent comme une phrase, pas comme un compteur.

**Troisième périmètre — la séparation et le pied** *(ajouté le 2026-08-11, sur retour d'écran)*

- **La phrase et les mots-clés deviennent DEUX CARTES séparées par un vide**, au lieu d'une seule
  carte coupée par un filet vertical. Le filet disparaît. C'est la séparation qu'emploie déjà la
  Visionneuse entre sa colonne d'avancement et sa pile de profils.
- **Le bouton quitte la carte de la phrase pour le PIED de la carte des mots-clés**, en bas à droite,
  collé aux trois bords de sa carte et détaché de la liste par un filet horizontal.
- **Son libellé devient « Sélectionner les profils »**, suivi d'une flèche qui se retourne en arabe.
- **Le nom de la recherche ne s'écrit plus pendant qu'on complète le poste** : « arc » n'est pas un
  critère, c'est le début d'« Architecte ».
- **Le crayon et la mention « se nomme tout seul » disparaissent** : le nom s'édite au simple clic.

## Hors périmètre

- **Les quatorze autres écrans qui utilisent la gouttière** — accueil, liste des recherches,
  visionneuse, table, compte, crédits, équipe, entreprise, profil. Le fil d'ariane est un essai sur
  un seul écran ; sa généralisation est une décision qui viendra après l'avoir vu.
- **Le composant `Gutter` lui-même** : il porte déjà un emplacement à gauche et un à droite, ce qui
  suffit. Il n'a pas à connaître le fil d'ariane.
- **Le contenu de la phrase de recherche** et le panneau des mots-clés, qui ne bougent pas.
- **Le libellé « Retour aux recherches » sur l'accueil**, qui garde son texte et sa destination —
  même si le même reproche pourrait lui être fait.
- **Le déclenchement par la touche Entrée**, qui reste ce qu'il est : le bouton caché du formulaire
  ne change pas de place, seul le bouton visible descend.
- **Le soulignement des champs AILLEURS que dans la phrase** — inscription, compte, équipe, mot de
  passe. Ces formulaires utilisent les mêmes composants, mais leurs champs **peuvent** être vides :
  sans filet, un champ vide y deviendrait invisible. Le retrait est donc porté par la phrase, pas
  par le kit.
- **Le renommage lui-même** : le crayon revient dès que la recherche porte un nom, et l'édition ne
  change pas. Seule l'invite sur une recherche sans nom disparaît.

## Décisions de cadrage

**Deux repères dans le fil, pas trois.** « Tableau de bord › Nouvelle recherche » décrit le chemin
réellement parcouru : on ouvre cet écran depuis l'accueil. Intercaler « Mes recherches » décrirait
une hiérarchie de rubriques et non un chemin — et ce repère du milieu mènerait ailleurs que là d'où
l'on vient.

**« Tableau de bord », pas « Dashboard ».** Le produit n'affiche aucun mot anglais non traduit, et
c'est déjà la traduction retenue ailleurs : le back-office d'administration nomme ainsi son écran
d'accueil. Deux vocabulaires pour la même chose coûteraient plus que la reprise du mot existant.
L'espace entreprise n'avait aucun libellé pour sa page d'accueil — sa liste de navigation commence à
« Mes recherches » —, donc une clé s'ajoute.

**Le fil d'ariane copie le modèle de l'espace admin, il n'en invente pas un autre.** Même balise de
navigation nommée, même clé d'accessibilité (`nav.breadcrumb`, « Fil d'Ariane »), même chevron qui
se retourne en arabe, même page courante marquée comme telle pour les lecteurs d'écran. Ce qui
change est l'endroit : la gouttière, et non la barre du haut.

**Un composant, pas douze lignes dans la page.** La règle de conventions n'interdirait pas d'écrire
le fil directement dans la page — elle ne refuse que les champs, la liaison bidirectionnelle et la
soumission. Mais une page assemble et se lit d'un coup d'œil : un bloc de navigation y serait le
seul morceau de balisage brut. Il vit donc à la racine des composants de l'espace, où vit déjà tout
ce qui relève de la coque et non d'un domaine.

**La pastille d'information plutôt que le texte nu.** À droite d'une bande, on attend un bouton :
c'est là que se trouvait celui qu'on retire. Un texte gris posé au même endroit se lirait comme une
action désactivée. L'icône dit que c'est une aide.

**Le bouton descend, le compteur reste — et les deux ne sont plus côte à côte.** Le compteur suit la
frappe en continu : il se lit comme un état de la recherche en train de se construire, au même titre
que son nom, et il reste visible pendant qu'on remplit la phrase. Arbitré avec l'utilisateur le
2026-08-10, contre la proposition initiale qui les faisait descendre ensemble.

**Deux placements pour le bouton, un par taille d'écran.** Sur grand écran il suit le dernier mot de
la phrase, là où le regard se pose. Sur téléphone, ce même point tombe au milieu de l'écran, hors de
portée du pouce : il se colle alors au bas de la carte. C'est un comportement de plus à maintenir,
assumé — chacun est le bon pour sa taille d'écran, et le compromis unique serait mauvais partout.

**Sur téléphone, la pastille cède la place au fil.** Une bande fine ne tient pas les deux à 390 px,
et l'élargir mangerait une hauteur déjà rare. Savoir où l'on est et pouvoir remonter prime sur une
phrase d'aide que le titre de la page redit en partie.

**Deux cartes plutôt qu'un filet, parce qu'un filet découpe un objet.** La phrase et les mots-clés
sont deux questions, pas deux moitiés d'une même. Un trait à l'intérieur d'un aplat commun disait
« un objet coupé en deux » ; deux surfaces séparées par un vide disent « deux choses », sans rien
tracer. Le précédent est dans la Visionneuse, et son code porte l'argument en creux : à l'intérieur
de sa carte de profil, le portrait et le verdict ne sont **volontairement pas** séparés, « une
couture entre eux en ferait deux objets posés côte à côte ». Ce qui vaut là-bas pour ne pas séparer
vaut ici pour séparer.

**Le bouton passe au pied de la carte des mots-clés, et devient un vrai pied.** Trois positions
auront été essayées à l'écran avant celle-ci : sur l'axe de la phrase (il flottait au milieu du
vide), à droite de la phrase (il venait toucher le filet et le faisait lire comme son propre cadre),
puis ici. Les mots-clés sont la dernière chose qu'on remplit ; le geste qui suit part donc de là.
Collé aux trois bords par des marges négatives qui annulent le rembourrage de la carte, et détaché
de la liste par un filet horizontal — un pied de bloc, pas le dernier élément d'une pile.

**« Sélectionner les profils » remplace « Créer ma recherche ».** Le mot change de nature : le
premier nomme ce qu'on va faire ensuite, le second ce que le clic fait. Les deux se défendent, et
c'est l'écran d'arrivée qui a tranché — on clique pour aller choisir des profils. La clé ne sert que
sur cet écran ; la console de la Visionneuse garde la sienne.

**Le nom attend que le poste soit posé.** La carte de critères prévenait la page à chaque touche, et
la page en tirait deux choses d'un coup : le décompte et le nom. Or ils n'ont pas le même rythme. Le
décompte doit suivre la frappe ; le nom, non — « arc » n'est pas un critère. La carte annonce donc
deux moments distincts : « ça a changé » à chaque touche, « c'est nommable » quand le poste n'est
plus un mot en cours. Le mot cesse d'être en cours dès qu'une suggestion est choisie, ou dès que le
champ est vidé.

**Le crayon disparaît, le clic reste.** Viser un crayon de seize pixels est un geste de précision,
viser un titre ne l'est pas — et le titre était déjà cliquable. Ce que l'icône annonçait, le
pointeur en main et la couleur au survol le disent aussi, sans poser un objet à côté d'un nom qui
peut être long. Pour les lecteurs d'écran rien n'est perdu : le bouton garde sa mention invisible.
La mention « se nomme tout seul » part avec, et sa clé de traduction avec elle — orpheline, elle
aurait survécu à l'écran qui l'appelait.

## Règles et cas limites

- **Le fil d'ariane est une navigation à part**, distincte de la navigation principale : il porte
  son propre nom accessible, sinon deux repères homonymes se confondent au lecteur d'écran.
- **La page courante n'est pas un lien** et s'annonce comme la page en cours ; un lien vers soi-même
  fait cliquer dans le vide.
- **Le chevron se retourne en arabe.** Il marque la descente dans la hiérarchie, donc il suit le sens
  de lecture — sinon il pointe à contresens.
- **Le nom de l'espace ne se répète pas** : « Espace entreprise » est déjà écrit en haut à gauche, à
  côté du logo. Le fil commence à « Tableau de bord ».
- **Le bouton reste désactivé tant qu'aucun critère n'est posé**, comme aujourd'hui : descendre ne
  change pas sa condition.
- **Le bouton descendu reste le même élément** — pas un second bouton qui apparaîtrait selon la
  largeur. Deux boutons pour un geste, c'est deux fois le risque d'en désactiver un seul.
- **Rien ne se code en dur** : couleurs, écarts et paliers de texte viennent des jetons du kit, et se
  tiennent en thème clair comme en thème sombre.
- **Aucun texte utilisateur en dur** : la clé neuve est écrite dans les trois langues du même geste,
  sans quoi le test de parité échoue.

## Documentation impactée

- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**
  — la carte de navigation décrit cet écran par « barre haute : le nom qui s'écrit tout seul, et
  “Créer ma recherche” ». La phrase devient fausse dès que le bouton descend. À corriger, en disant
  aussi ce que porte la gouttière.

Aucune autre page : ni route, ni contrat, ni règle métier ne change. Aucun `README.md` de dossier ne
change de convention — un composant de coque à la racine des composants de l'espace est exactement
ce que le README prévoit déjà.

## Définition de terminé

- [x] Le bouton « Retour aux recherches » a disparu de cet écran ; l'accueil garde le sien.
- [x] La gouttière porte à gauche « Tableau de bord › Nouvelle recherche », le premier repère mène à
      l'accueil de l'espace, le second n'est pas cliquable.
- [x] Le fil d'ariane s'annonce comme tel aux lecteurs d'écran, et sa page courante est marquée
      comme la page en cours.
- [x] En arabe, le fil se lit de droite à gauche et son chevron pointe dans le sens de la lecture.
- [x] La gouttière porte à droite une pastille « i » suivie de « Décrivez le profil que vous
      cherchez ».
- [x] Sous 1024 px, la gouttière ne montre que le fil d'ariane, sur une seule ligne.
- [x] « Créer ma recherche » ne figure plus dans la barre haute ; celle-ci garde le nom de la
      recherche et le compteur de profils.
- [x] Au-delà de 1024 px, le bouton se trouve sous la phrase, aligné sur son axe.
- [x] Sous 1024 px, le bouton est collé au bas de la carte.
- [x] Le bouton reste désactivé tant qu'aucun critère n'est posé, et lancer la recherche fonctionne
      depuis sa nouvelle place comme depuis l'ancienne.
- [x] Les trois langues portent le libellé « Tableau de bord » ; le test de parité passe.
- [x] La carte de navigation ne décrit plus le bouton dans la barre haute.
- [x] Captures fournies : thème clair et sombre, grand écran et largeur téléphone.

*Second périmètre :*

- [x] Au-delà de 1024 px, le bouton est en bas à droite de la carte ; sous 1024 px il reste collé au
      bas, pleine largeur.
- [x] « Peu importe » s'affiche en gris sur l'expérience et sur le niveau tant que rien n'est choisi,
      dans les trois langues, et le test de parité passe.
- [x] Choisir puis revenir à « Peu importe » retire bien le critère : le compteur de profils remonte.
- [x] Aucun champ de la phrase ne porte de soulignement ; les champs des formulaires d'inscription et
      de compte gardent le leur.
- [x] Le curseur est une main au survol des deux choix.
- [x] Sur une recherche sans nom, la barre haute est vide à gauche — ni texte d'invite, ni crayon ; le
      nom et le crayon reviennent dès qu'elle en porte un.
- [x] Le nombre de profils est visiblement plus gros que le mot qui le suit.
- [x] La phrase et les mots-clés sont deux cartes distinctes séparées par un vide ; plus aucun filet
      vertical ne les coupe.
- [x] Le bouton ferme la carte des mots-clés en pied, en bas à droite, collé à ses bords.
- [x] Il dit « Sélectionner les profils » et porte une flèche qui se retourne en arabe.
- [x] Taper un début d'intitulé ne nomme plus la recherche ; le nom n'apparaît qu'au choix.
- [x] Le nom s'édite au simple clic, sans crayon ni mention « se nomme tout seul ».

## Vérification

Preuves prises les 2026-08-10 et 2026-08-11 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, écran
« Décrivez le profil que vous cherchez ». Captures dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

**La gouttière**

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| Fil d'ariane, ses maillons et son nom accessible | « Fil d'Ariane » · « Tableau de bord » › « Nouvelle recherche » |
| Le premier maillon mène à l'accueil | clic réel → l'adresse devient `/` |
| Le second n'est pas un lien | marqué comme page en cours, sans destination |
| Le bouton de sortie a disparu | recherché dans toute la page : absent |
| Sous 1024 px, la pastille d'aide s'efface | plus aucune surface à l'écran |
| Arabe | `dir=rtl`, fil à droite (`x=1263`), « لوحة القيادة › بحث جديد », lien `/ar` |

**Les deux cartes et le pied**

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| Deux cartes, plus une | `2` éléments de carte sous le formulaire |
| Empilement sur téléphone | cartes à `y=167` (h. 338) et `y=514` (h. 322) |
| Bouton en pied, pleine largeur sur téléphone | largeur `326 px`, bas à `820`, carte à `836` |
| Plus de bouton dans la barre haute | la barre n'en contient aucun |

**Le nom**

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| « arc » tapé lettre à lettre | nom **vide**, compteur à jour (« Aucun profil ne correspond ») |
| Suggestion choisie | nom = « Architecte (IT/logiciel) » |
| Plus de crayon | aucune icône dans le bloc du nom |
| Édition au clic | curseur `pointer`, et après clic le champ est ouvert avec le focus dedans |

**Captures** : `preuve-dark-1440.png`, `preuve-light-1440.png`, `preuve-dark-1024.png`,
`preuve-dark-390.png`, `preuve-ar-1440.png`, `preuve-dark-1440-rempli.png`, `preuve-nommage.png`,
`preuve-separation-dark-1440.png`, `preuve-separation-dark-1024.png`,
`preuve-separation-light-1440.png`, `preuve-deux-cartes-390.png`.

**Tests joués** : `base/i18n/locales/parity.vitest.ts` — 9 verts. Type-check du webapp : aucune
erreur dans les fichiers de cette tâche. Les gates complètes appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/Breadcrumb.vue` **(neuf)** — le fil d'ariane de la coque,
  décalqué de celui de l'espace admin : même balise nommée, même clé d'accessibilité, même chevron
  qui se retourne en arabe.
- `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` — gouttière refaite (fil à gauche,
  pastille d'aide à droite, plus de bouton de sortie), barre haute réduite au nom et au compteur,
  bouton descendu dans la carte des mots-clés avec sa flèche, et le nom composé sur le nouvel
  événement plutôt qu'à chaque touche.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — deux cartes au lieu d'une, le
  formulaire les entourant toutes deux ; emplacement d'action en pied de la seconde ; événement
  `settle` distinct de `change` ; suivi de la frappe du poste.
- `webapp/spaces/entreprise/app/components/search/ShellName.vue` — crayon et mention retirés, le nom
  s'édite au clic, la couleur remplace le soulignement au survol.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `nav.entreprise.dashboard` ajoutée,
  `search.sentence.submit` réécrite, `search.sentence.name.autoHint` retirée.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — la carte de navigation décrit
  la gouttière, la barre et la vraie place du bouton.

## Reste à faire (hors périmètre)

- **Le fil d'ariane ne vit que sur cet écran.** Les quatorze autres pages qui utilisent la gouttière
  gardent la leur. Sa généralisation demande que chaque page fournisse son libellé, ce que l'espace
  entreprise ne tient nulle part aujourd'hui.
- **`search.back` reste employée par l'accueil**, avec le même reproche : elle annonce un retour vers
  la liste des recherches et ramène à l'accueil.
- **Aucune animation ne respecte « réduire les animations »** sur cet écran — sujet global au socle.
