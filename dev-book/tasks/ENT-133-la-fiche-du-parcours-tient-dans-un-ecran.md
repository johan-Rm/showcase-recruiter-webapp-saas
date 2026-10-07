# TASK ENT-133 — la fiche du parcours tient dans un écran

- **Créée le** : 2026-08-17.
- **Session** : `c6e9274a-26a7-4733-86f6-42a6f9f95f36`.
- **Statut** : ✅ fait le 2026-08-18 — la fiche occupe exactement un écran sur les cinq formats
  mesurés, le corps défilant encaisse la variation, et l'ouverture du profil comme les pastilles
  d'état sont visibles sans rien faire défiler.
- **Type** : fix — une règle posée par `ENT-023`, tenue par `ENT-076`, puis échangée sciemment par
  `ENT-088` contre un repli qui n'avait pas d'autre solution à l'époque. C'est cet arbitrage qu'on
  rouvre, pas un oubli qu'on rattrape.
- **Tests** : `sans objet` — la tâche ne produit **aucune logique** : elle déplace deux blocs et pose
  une contrainte de hauteur. Le dépôt n'a aucun test de rendu sur ses 28 fichiers de test, et ce qui
  se juge ici est une géométrie. **La preuve attendue est la mesure** : la hauteur de la fiche
  comparée à celle de la zone qui la contient, relevée à l'écran sur trois formats, avant et après.
- **Réf** : `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` ·
  `ENT-023` (la règle d'origine) · `ENT-076` (la règle tenue) · `ENT-088` (la règle échangée) ·
  `ENT-129` (la convention de barre de défilement) · `ENT-134` (le même contrat sous 1280 px) ·
  `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (hauteur en `min-height`, jamais en `height`).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, parcourt les candidats d'une recherche
enregistrée — l'écran au compteur « 7 / 93 » en haut à droite. Au milieu, **la fiche du candidat** :
le titre du poste en bandeau, la photo et les moyens de le joindre à gauche, le nom et la substance à
droite. À droite de la fiche, la colonne du verdict, avec la note et le bouton « Retenir ».

**Ce que cet écran promet.** Un candidat = un écran. La molette saute d'un profil au suivant, et le
geste ne change jamais de place, même après cent profils. C'est écrit en tête de la pile : *« une
card = un plein écran. Le défilement de la pile est le SEUL de l'écran, aucune card ne défile en
interne. »*

**Ce qu'on voit.** La promesse casse. Sur un profil au titre long — « Chef de projets travaux neufs
(installation des centrales d'air dans toute industrie) », deux lignes de bandeau — la fiche dépasse
d'environ **130 px** sur une fenêtre de 900 px de haut, mesuré en capture. Deux conséquences :

- la molette ne saute plus au profil suivant, elle **finit d'abord la fiche courante** ;
- ce qui tombe dans ces 130 px n'est pas du décor : les pastilles « Nouveau » / « Vu », puis le bouton
  **« Voir le profil complet »**. L'action la moins chère de l'écran — elle ne coûte aucun crédit et
  ne change aucun état — est la seule qu'on ne voit pas en arrivant sur un profil.

**Pourquoi elle a le droit de dépasser.** `ENT-088` le lui a donné, et l'a écrit : sous 1280 px le
verdict retombe *à l'intérieur* de la fiche, celle-ci passait alors sous la hauteur de son contenu et
son corps **débordait par-dessus le verdict** — constaté en capture à 1150 × 1000. La fiche a donc
reçu `shrink-0` et le droit de grandir, avec sa conclusion assumée : *« une fiche se lit en deux
temps sur un écran court »*. Le compromis a sauvé la bande étroite en cassant le grand écran.

**Le dispositif existe déjà, une colonne à droite.** La liste des critères prend la hauteur qu'on lui
donne et défile à l'intérieur (`MatchList.vue`) — c'est pour cela que « motivation des équipes »
apparaît coupée en deux sur la capture : elle continue. La fiche est **la seule zone de cet écran**
qui n'applique pas cette recette.

## Périmètre

- **La fiche borne sa hauteur** à celle de la pile qui la porte : elle cesse de grandir avec son
  contenu.
- **UNE seule zone souple**, et elle réunit **le projet de retour et les compétences**. Les deux
  ensemble, parce que les compétences ne sont pas la seule part variable : le projet de retour cite
  le candidat dans ses mots, sur un texte libre sans longueur maximale, et peut manger le budget à
  lui seul. Elle prend la hauteur restante et défile.
- **La place de sa barre est réservée en permanence**, et la barre touche le bord de la zone — la
  recette qu'`ENT-129` vient de fixer sur la console. Une seule convention de défilement dans tout
  l'écran.
- **Un plancher, en hauteur minimale et jamais en hauteur imposée** (règle de la charte) : environ
  trois lignes de compétences. **Sous ce plancher, la fiche se remet à défiler** — la règle plie au
  lieu de rogner, et rien ne devient inatteignable sur une fenêtre extrême.
- **« Voir le profil complet » quitte le pied de fiche** et se pose **à droite de la ligne du nom** :
  bouton discret, libellé entier, aplat sourd. Pas d'aplat accent — le vert reste au seul geste de
  décision de l'écran, « Retenir ». Il ne coûte aucune hauteur : la ligne du nom est déjà occupée.
- **Les pastilles d'état remontent sous la ligne de situation**, avec le reste de ce que le candidat
  *est*. Sans filet séparateur : le filet n'avait de sens qu'en pied de fiche, pour refermer une
  lecture.
- **Le pied de fiche se vide.** C'est ce qui rend la hauteur tenable, et non un rangement d'esthète.
- **Les commentaires du fichier sont réécrits** : ils annoncent aujourd'hui l'inverse de ce qui sera
  fait — « elle grandit avec son contenu », « une card se lit simplement en deux temps ».

## Hors périmètre

- **Tout ce qui est sous 1280 px.** À cette largeur le verdict retombe dans la fiche et pèse plus de
  300 px à lui seul : aucune contrainte de hauteur ne tient tant qu'il est là. C'est `ENT-134`, et
  c'est une décision de produit — quoi replier — pas de la géométrie.
- **La colonne du verdict**, dont une ligne apparaît coupée en deux. `ENT-129` l'a déjà écartée avec
  son motif : ses étiquettes débordent volontairement de leur cadre pour respirer, et le défaire
  demande de vérifier leur allure. Elle défile déjà correctement ; il ne lui manque que le signal.
  **Non ouvert.**
- **L'allure de la barre de défilement** — épaisseur, teinte, discrétion au repos. Sujet de charte,
  qui vaudrait pour tout le produit. **Non ouvert.**
- **Le tiroir du profil complet**, sa mise en page et son contenu. Seul l'endroit d'où on l'ouvre
  change.
- **La Table**, l'autre outil de la recherche, qui n'a pas de fiche plein écran.
- **Le contenu de la fiche** : aucun bloc n'est ajouté, retiré ni réécrit. Deux d'entre eux changent
  de place, c'est tout.

## Décisions de cadrage

- **La frontière est à 1280 px, et non à 1024.** Choisir 1024 aurait obligé à traiter le verdict
  replié dans la fiche à l'intérieur de cette tâche — c'est-à-dire à rouvrir l'arbitrage d'`ENT-088`
  en même temps qu'on pose la géométrie. Deux sujets, deux cartes.
- **La règle plie, elle ne rogne pas.** Sur une fenêtre de 700 px de haut, la partie fixe seule —
  titre sur deux lignes, photo, deux coordonnées, deux boutons d'achat, nom, quatre faits — peut déjà
  remplir l'écran. Bloquer la hauteur rendrait alors du contenu inatteignable, **y compris un bouton
  d'achat**. Le plancher préserve la lisibilité et laisse le défilement revenir en dernier recours.
  La charte dit la même chose autrement : *« elle se pose en `min-height`, jamais en `height` — une
  hauteur imposée rogne son contenu »*.
- **Le bouton va sur la ligne du nom, pas ailleurs.** Écarté : la colonne de droite au-dessus de
  « Retenir » — ouvrir un profil n'est pas une décision, et l'y mettre ferait perdre au bouton vert
  son statut de geste unique. Écarté : la colonne de gauche, qui sert à *joindre* quelqu'un et où le
  bouton coûterait une ligne de hauteur. Écarté : la bande du haut à côté du compteur, immobile mais
  détachée du candidat qu'elle ouvre. Écarté : l'icône seule, invisible pour qui ne survole pas et
  sans infobulle au doigt.
- **Le projet de retour n'est pas tronqué.** L'alternative — une citation coupée à trois lignes —
  rendait la hauteur plus prévisible en amputant précisément le différenciant du produit : ce qui
  sépare « il cherche un poste » de « il veut rentrer ».
- **⚠️ ÉCART — une hauteur DÉFINIE, et non un simple plancher.** Le cadrage annonçait un plancher
  « en hauteur minimale et jamais en hauteur imposée », en s'appuyant sur la charte. La mesure l'a
  démenti deux fois : un plancher ne comprime rien. Sous `min-h-full`, la fiche grandit à son
  contenu et le corps défilant n'a jamais de trop-plein à encaisser — mesuré, une fiche à **546 px
  dans une zone de 525**. La fiche porte donc `h-full`, une hauteur imposée, avec `min-h-min`
  derrière pour la faire grandir quand le contenu ne peut plus céder. **La lettre de la règle de
  charte n'est pas tenue, son intention si** : ce qu'elle protège, c'est qu'un bloc ne rogne jamais
  son contenu en silence, et c'est exactement ce que `min-h-min` garantit ici.
- **⚠️ ÉCART — `contain-size` sur le corps défilant, non prévu au cadrage.** `h-full min-h-min` seul
  ne suffisait pas : la hauteur minimale de contenu de la fiche comptait le corps défilant **entier**
  (246 px mesurés) au lieu de son plancher (72 px), ce qui interdisait toute compression. Le
  confinement de taille ramène ce bloc à son plancher dans ce calcul, sans rien changer à son propre
  défilement. C'est la pièce sans laquelle le dispositif ne fonctionne pas.
- **⚠️ ÉCART — le seuil où la règle plie n'est pas 700 px.** La *Définition de terminé* l'annonçait à
  cette hauteur ; la mesure le situe entre 560 et 460 px de fenêtre. À 700 px la fiche tient encore
  dans l'écran. Le critère a été corrigé pour dire ce qui est vrai.
- **Pas de test de rendu introduit pour l'occasion.** Le dépôt n'en a aucun ; en créer un premier ici
  reviendrait à verrouiller du gabarit qui bougera encore, et l'outillage n'existe pas. Le précédent
  d'`ENT-129` — exemption motivée, preuve par la mesure — s'applique tel quel.

## Règles et cas limites

- **Le titre reste borné à deux lignes** (comportement actuel) : c'est lui qui fixe le haut du budget
  de hauteur, et le poste le plus long du vivier y entre en entier.
- **Un candidat sans compétence ni projet de retour** : la zone souple ne laisse ni cadre, ni barre,
  ni vide réservé. Rien à faire défiler ne doit rien afficher.
- **Un seul des deux blocs présent** — compétences seules, ou projet de retour seul : même zone, même
  comportement.
- **La zone qui défile est atteignable au clavier.** Une zone défilante qui ne peut pas recevoir le
  focus rend son contenu inaccessible sans souris.
- **Recherche d'un collègue (`editable: false`)** : le bouton d'ouverture reste présent. Lire n'est
  pas décider — il ne coûte rien et ne change aucun état.
- **Un nom très long ne doit pas écraser le bouton** : c'est le nom qui se tronque, le bouton garde sa
  largeur.
- **Nom masqué réduit à une initiale** : rien ne change à la place du bouton.
- **Aucun texte nouveau.** Le libellé du bouton et ceux des pastilles existent déjà en clés i18n.

## Documentation impactée

- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` — les commentaires du fichier, qui
  annoncent l'inverse de ce qui sera fait. Ils partent dans le même commit.
- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** Cet écran n'y est pas décrit à ce niveau de détail, et la
  charte ne gagne pas de règle nouvelle : celle qu'on applique — hauteur en `min-height` — y est déjà
  écrite.

## Définition de terminé

- [x] À 1280 × 800, 1512 × 900 et 1920 × 1080 : la hauteur de la fiche est inférieure ou égale à
      celle de la zone qui la contient, mesurée à l'écran.
- [x] Sur le profil au titre le plus long du jeu, les pastilles d'état et « Voir le profil complet »
      sont visibles **sans rien faire défiler**.
- [x] Un cran de molette passe d'un profil au profil suivant, sans position intermédiaire.
- [x] Le projet de retour et les compétences défilent ensemble, dans une zone dont la barre est
      réservée en permanence et touche le bord.
- [~] Un candidat sans compétence ni projet de retour n'affiche aucune zone vide — **le garde-fou
      est écrit (`v-if`), il n'est pas prouvé par la donnée** : aucune des 93 fiches du jeu n'est
      dans ce cas.
- [x] Sur une fenêtre trop basse pour la part fixe, la fiche redéfile plutôt que de rogner son
      contenu — seuil mesuré entre 560 et 460 px de fenêtre, et non 700 px comme annoncé.
- [x] La zone qui défile prend le focus au clavier.
- [x] Les commentaires de `ProfileCard.vue` disent ce qui est vrai.

## Vérification

Banc automatisé sur les **93 fiches** de la recherche `019ff56d-6eee-7002-89ac-46a78d713152`,
navigateur réel (Chromium piloté), session authentifiée en `entreprise@showcase-recruiter-webapp-saas.example`, thème sombre.
« Dépassement » = hauteur de la fiche moins hauteur de la zone qui la contient, pire cas sur les 93.

### La fiche tient dans un écran

```
                     avant                          après
1920 × 1080          0 px                           0 px
1512 × 900           0 px                           0 px
1280 × 800           0 px                           0 px
1280 × 700         +97 px                           0 px   corps défilant 225/246, il DÉFILE
1440 × 620         +93 px, ouverture hors écran     0 px   corps défilant 140/154, il DÉFILE
```

**Le défaut ne se reproduit pas sur les trois formats hauts avec les données de ce jeu** : la version
d'origine y tenait déjà. Il n'apparaît qu'en fenêtre basse. Les captures qui ont ouvert le sujet le
montraient à ~900 px de haut sur un profil au titre de deux lignes, absent de ce jeu.

### La règle plie plutôt que de rogner

```
fenêtre 460 | zone 285 | fiche 438 (elle grandit) | contenu échappé hors fiche : aucun
fenêtre 380 | zone 205 | fiche 438 (elle grandit) | contenu échappé hors fiche : aucun
```

Le corps défilant est à son plancher de 72 px dans les deux cas, et rien ne sort de la fiche.

### L'accrochage du défilement

Trois crans de molette à 1512 × 900, hauteur de fiche 710 px :

```
positions de la pile : 0 → 710 → 1420 → 2130     écarts : 710, 710, 710
```

Un cran, un profil, aucune position intermédiaire.

### Le reste

```
barre du corps défilant  scrollbar-gutter: stable          (même recette qu'ENT-129)
focus clavier            document.activeElement === zone → true
pastilles d'état         3 sur la première fiche, sous la ligne de situation
ouverture du profil      à 70 px du haut de la fiche sur le profil le plus chargé
pied de fiche            dernier enfant = le verdict de repli, hauteur nulle au-dessus de 1280 px
```

## Livré

- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` (M) — la fiche prend une hauteur
  définie avec `min-h-min` derrière ; les deux colonnes s'étirent ; le projet de retour et les
  compétences entrent dans un corps défilant confiné, à plancher, avec sa barre réservée et
  atteignable au clavier ; l'ouverture du profil monte sur la ligne du nom en neutre, les pastilles
  d'état sous la ligne de situation ; le pied de fiche est vidé ; les commentaires sont réécrits.

## Reste à faire (hors périmètre)

- **Le vide en bas de fiche sur grand écran.** À 1920 × 1080 la fiche fait 859 px pour un contenu qui
  en occupe environ 370 : un large blanc reste sous les compétences. Ce vide n'est pas né ici — il
  vivait auparavant entre les compétences et le pied —, mais le bouton qui refermait visuellement la
  fiche est parti et plus rien ne la referme. À 1280 × 700 le sujet disparaît. **Non ouvert.**
- **Le cas « ni compétence ni projet de retour »** reste non prouvé faute de donnée.
- **Sous 1280 px**, la fiche déborde toujours : c'est `ENT-134`, et rien ici ne le traite.
