# TASK ENT-121 — entreprise : le profil complet se lit en deux colonnes, et cesse de jeter ce qu'il reçoit

- **Créée le** : 2026-08-14.
- **Session** : `4878931a-1a22-468b-bf55-00f534cbb830`.
- **Statut** : ✅ fait le 2026-08-14 — le corps du tiroir passe à deux colonnes sur grand écran, une
  seule en dessous : à gauche l'identité, les états du profil, l'accroche, le projet au Maroc, les
  métiers, les secteurs, les compétences, les langues et le bloc payant ; à droite le parcours daté
  en frise verticale, postes puis formations. Trois informations que le serveur envoyait et que
  l'écran jetait — l'accroche, les métiers, les secteurs — sont affichées, et trois formes fausses du
  miroir de types, découvertes à la capture, sont corrigées.
- **Type** : feat.
- **Tests** : unitaires sur la fusion des deux sources — l'accroche remontée de la vignette, les
  métiers et secteurs réduits à leur nom, l'école lue sous ses deux formes, les états disponibles dès
  l'ouverture. Service pur, sans montage de composant, comme `ENT-112`.
- **Réf** : `ENT-112` (le tiroir, dont cette carte reprend l'ouvrage) · `ENT-006` (la card de la
  Visionneuse est un résumé de décision, pas une fiche) ·
  `webapp/spaces/entreprise/app/components/ProfilePanel.vue` ·
  `webapp/services/search/profile.ts` · `GET /v1/candidates/{id}`.

## Contexte

**Le tiroir du profil complet paraît pauvre et plat — et il l'est pour une raison qui n'a rien à voir
avec le serveur.**

Un recruteur, dans l'espace entreprise, ouvre un profil depuis la Table (clic sur une ligne) ou
depuis la Visionneuse (bouton « Voir le profil complet »). Un tiroir monte par le bas et couvre
presque tout l'écran. Son corps est une colonne unique, tout au même corps de texte, sans
séparateur.

Mesuré sur les **3 899 profils** de la base du worktree entreprise, le tiroir **met en tête des
rubriques presque toujours vides** :

| Rubrique affichée | Profils qui la portent |
| --- | --- |
| Le résumé, en tête de « Son parcours » | 41 — soit 1 % |
| Le CV | 72 — soit 2 % |
| Le bloc « Son projet au Maroc », en entier | 3 |
| Le compte rendu d'entretien showcase-recruiter-webapp-saas | 1 |

…et **jette trois informations que le serveur lui envoie déjà** :

| Information reçue et non affichée | Profils qui la portent |
| --- | --- |
| L'accroche — la phrase que la personne écrit sur son réseau | 3 888 — soit 99,7 % |
| Les métiers exercés | 3 488 — soit 89,5 % |
| Les secteurs d'expérience | 3 048 — soit 78,2 % |

L'accroche est le cas le plus net. `mergeProfile` la calcule et la place dans `identity.headline` ;
le template ne s'en sert que comme **titre de secours**, quand le poste du moment manque. Sur un
profil qui a un poste — 2 931 sur 3 899 —, elle est chargée, transportée jusqu'à l'écran, et jetée.
Elle n'est affichée **nulle part** dans le produit : la card de la Visionneuse la refuse
explicitement comme intitulé, à raison, mais personne ne l'a reprise ailleurs.

Deux autres valeurs subissent le même sort, calculées par le service et absentes du template :
`identity.isStar` — le profil a été **rencontré en entretien par showcase-recruiter-webapp-saas** — et `morocco.isInMorocco`
— il est **au Maroc en ce moment**. Faible volume aujourd'hui (1 et 3 profils), mais ce sont les deux
signaux les plus forts du produit, et ils viennent de la vignette, donc affichables avant même que la
fiche arrive.

Le résumé, lui, ne se rattrapera pas : absent sur 3 858 profils sur 3 899, et ce sont de vrais
profils importés, pas des jeux d'essai. La nouvelle mise en page ne doit rien construire dessus.

## Périmètre

- **Le corps du tiroir passe à deux colonnes sur grand écran**, une seule en dessous. Le seuil est
  celui de la fiche de la Visionneuse, qui bascule déjà à cette largeur : deux surfaces du même écran
  changent de forme au même moment.
- **Colonne de gauche** — l'identité (portrait, nom, lieu, employeur, expérience, niveau d'études,
  lien vers le profil public), les deux marques, l'accroche en entier, le projet au Maroc, puis
  **trois rangées étiquetées** — métiers · secteurs · compétences —, les langues, et enfin le bloc
  « À dévoiler ».
- **Colonne de droite** — une **frise verticale** : les postes, puis les formations, sur un même
  rail, en deux segments successifs sous leur titre.
- **Trois champs entrent à l'affichage** : `headline`, `jobExperiences`, `sectorExperiences`. Les
  deux derniers sont dans le contrat mais absents du service de fusion, qui les ignore aujourd'hui.
- **Les états du profil deviennent visibles**, dont « rencontré par showcase-recruiter-webapp-saas » et « au Maroc en ce
  moment » qui ont motivé la demande.
- **Les compétences changent de section** : elles quittent « Son parcours » — elles n'ont pas de
  date, elles n'ont rien à faire sur une frise — pour rejoindre les rangées d'étiquettes à gauche.
  Les langues suivent, pour la même raison.
- **Sur téléphone, l'ordre de décision d'ENT-112 est conservé** : qui est-ce · va-t-il vraiment venir
  · son parcours · ce qui reste à dévoiler.
- **Les libellés neufs en clés i18n**, trois langues.

## Hors périmètre

- **Le serveur, sous toutes ses formes.** Les trois champs récupérés sont déjà dans la réponse ;
  l'accroche et les deux marques sont même dans la vignette. Aucune propriété ajoutée, aucun endpoint
  touché, aucune sollicitation de la lane API.
- **Le CV dévoilé, qui annonce « Disponible » sans son nom ni sa date.** Le contrat n'expose aucune
  URL de fichier : on ne pourrait de toute façon pas proposer de le lire, et afficher un nom de
  fichier sans moyen de l'ouvrir ne vaut pas une ligne de plus.
- **Le niveau de langue non normalisé** — `professional` s'affiche en minuscules faute d'entrée au
  catalogue. Défaut connu depuis `ENT-112`, qui a posé ce repli exprès.
- **La hiérarchie typographique.** Les colonnes rangent, elles ne hiérarchisent pas : ce qui restera
  plat après cette carte — corps de texte uniforme, absence de filets et de respiration — relève d'un
  passage à part.
- **Les quatre champs Maroc que la fiche détaillée ne porte pas** (`returnProject`, `hasPiedATerre`,
  `piedATerreCity`, `isInMorocco`), écart de contrat déjà écrit dans `ENT-112` et toujours ouvert.
- **Les trois pages de `dev-book/cadrage/`** qu'`ENT-112` a rendues fausses et n'a pas corrigées.
  Même motif qu'alors : ce dossier est écrit pour les clients et suit son propre rythme.
- **Tout geste de décision.** Retenir, écarter, dévoiler restent où ils sont. Le tiroir lit.
- **Les écrans `recherches-v1/` et `recherches-v2/`**, qui portent leurs propres copies et ne sont
  pas retouchés.

## Décisions de cadrage

### Le motif à deux colonnes se reprend, il ne s'invente pas

La card de la Visionneuse emploie déjà une grille à deux colonnes qui s'empile en dessous du seuil,
gauche étroite et droite large. Le tiroir reprend ce motif et son seuil plutôt que d'en poser un
second. Les proportions, elles, diffèrent : la gauche de la card est une carte de visite, celle du
tiroir porte la moitié du contenu — un partage plus équilibré est le point de départ, à ajuster à la
capture.

### La colonne de gauche ne reste pas collée en haut

C'était la première idée, et elle tombe sur une mesure : **l'accroche monte à 752 caractères** sur le
vivier. Une colonne plus haute que l'écran ne peut pas rester collée en haut sans devenir
inatteignable par endroits. Une seule zone qui défile — celle du tiroir —, deux colonnes qui coulent
normalement. C'est aussi ce qui évite trois ascenseurs imbriqués dans une fenêtre qui défile déjà.

### L'accroche s'affiche en entier

Médiane 47 caractères, neuvième décile 125, maximum 752 : sur neuf profils sur dix elle tient en une
à deux lignes. Une coupure à trois lignes aurait demandé un bouton « lire la suite », son libellé en
trois langues et son état, pour une gêne qui concerne 421 profils sur 3 888. Les rares pavés
allongent la colonne, or le tiroir défile déjà.

### L'ordre de décision l'emporte sur la simplicité du code

Sur téléphone, les deux colonnes s'empilent — et l'empilement naïf (toute la gauche, puis la droite)
placerait le parcours **après** le bloc payant. L'ordre posé par `ENT-112` est conservé : identité,
projet au Maroc, parcours, à dévoiler. Le prix est un **placement en grille explicite** sur grand
écran, où le parcours saute dans la colonne de droite sans suivre l'ordre du code — plus délicat à
lire dans le fichier, et c'est le motif de ce commentaire-ci.

### Trois rangées étiquetées, pas un nuage

Un métier exercé, un secteur et une compétence ne se lisent pas de la même façon, et ce sont trois
critères de recherche distincts. Les fondre en une seule rangée aurait gagné quelques pixels et perdu
la distinction entre ce que la personne a fait, où elle l'a fait, et ce qu'elle sait faire.

### Les marques près du nom, pas en bandeau

Un bandeau en tête du tiroir se serait vu davantage, et aurait mangé une hauteur fixe sur les 3 896
profils qui ne portent **aucune** des deux marques. Deux étiquettes sous l'identité se lisent d'un
coup d'œil et ne coûtent rien quand elles sont absentes.

### Le bloc « À dévoiler » termine la colonne de gauche

Il y est déjà, et l'y garder donne deux colonnes franches sans zone en travers. Sur un long parcours,
la gauche s'arrête avant la droite et laisse du vide en bas : **assumé**. Le porter en pleine largeur
sous les deux colonnes l'aurait éloigné de l'identité qu'il complète, et obligé à faire défiler tout
le parcours pour l'atteindre.

### La frise vient du kit

Le kit d'interface fournit une frise verticale — points, rail, date, titre, description. Deux
instances, une par segment, plutôt qu'un rail dessiné à la main. Sa notion d'« étape courante » ne
sert pas ici : un parcours n'a pas d'étape active, et laisser la valeur non renseignée suffit à
n'allumer aucun point.

### Pendant le chargement, la droite reste vide

`ENT-112` a écarté explicitement les blocs gris à l'ouverture — « un tiroir qui s'ouvre sur des blocs
gris gaspille ce que la vignette portait déjà ». La règle tient : la colonne de droite est vide le
temps que la fiche arrive, et l'en-tête annonce déjà « Chargement du profil… ». Ajouter un second
indicateur dirait deux fois la même chose.

### Les marques passent par le catalogue d'états, et non par deux drapeaux lus à la main

Découvert à l'implémentation : `services/search/states.ts` est déjà la **source unique** des états
d'un profil pour les surfaces qui le montrent, et son propre commentaire nomme le tiroir comme « la
seule à n'en afficher aucun ». Poser deux étiquettes à la main aurait donc créé un troisième
vocabulaire pour dire ce que deux écrans disent déjà.

Le tiroir affiche donc **la rangée entière** — rencontré, favori, disponible, nouveau, au Maroc, vu,
écarté — dans l'ordre fixe du catalogue. C'est plus que les deux marques demandées, et c'est moins de
code : la fusion appelle `statesOf`, le tiroir rend ce qu'elle renvoie.

### Le miroir de types était faux sur trois formes

Découvert à la capture, et **antérieur à cette carte** : `jobExperiences` et `sectorExperiences`
étaient déclarés en tableaux de chaînes alors que le serveur envoie des objets, et l'école d'une
formation arrive tantôt en texte (profils de démonstration), tantôt en objet `{ id, name }` (les
93 % venus du réseau professionnel). L'écran affichait le JSON brut.

Les trois sont corrigées ici, parce qu'un écran qui imprime `{ "id": "65d3…", "name": "UMONS" }` n'est
pas livrable — et la leçon, elle, remonte dans `data-contracts.md` : un champ du miroir que personne
n'affiche n'est vérifié par rien, ni par le contrôle des types, ni par un test.

La fusion **absorbe les deux formes** plutôt que de les laisser au template : les métiers et les
secteurs y perdent leurs compteurs — la durée sous-estime, le serveur le documente —, et l'école y
devient un nom.

### Les secteurs arrivent en codes, et il n'existait aucun catalogue

Mesuré : les dix-huit codes de l'énumération sont tous employés dans le vivier, et rien nulle part ne
les traduisait — l'espace admin affiche `CONSEIL_AUDIT` tel quel sur son état de la base. Le
catalogue `search.sectorExperience` est écrit ici, en trois langues, sur le modèle de
`search.education` et `search.horizon`. Les métiers, eux, sont déjà des intitulés lisibles.

### Rien n'est demandé au serveur

Les trois champs récupérés voyagent déjà dans la réponse. Faire ajouter quoi que ce soit côté API
aurait bloqué cette carte sur une autre lane pour des données qu'on a sous la main.

## Règles et cas limites

- **Aucune section vide ne s'affiche**, ni en creux avec un titre suivi de rien. La règle vaut pour
  les trois rangées neuves : 411 profils n'ont aucun métier, 851 aucun secteur, 11 aucune accroche.
- **Aucun poste daté** — la colonne de droite n'a rien à porter : elle ne s'affiche pas, et la gauche
  reprend toute la largeur. Un demi-tiroir vide serait pire que la colonne unique d'aujourd'hui.
- **L'accroche est du texte brut**, saisi par la personne sur son réseau : elle peut contenir des
  retours à la ligne, des emojis et des chevrons. Jamais interprétée comme du balisage.
- **Les métiers et secteurs n'arrivent qu'avec la fiche** : ils sont absents de la vignette. Les
  rangées apparaissent donc pendant le remplissage, sans faire clignoter ce qui précède.
- **La fiche qui ne se charge pas** garde le comportement d'`ENT-112` : le tiroir reste ouvert sur ce
  que porte la vignette, une ligne l'explique, un bouton propose de réessayer.
- **Profil pauvre** : le tiroir doit tenir avec une seule expérience, sans formation et sans langue.
- **Mobile d'abord** — la colonne unique est la forme de référence, les deux colonnes s'y ajoutent.
- **Accessibilité** : le focus entre dans le tiroir et n'en sort pas, `Échap` ferme, le focus revient
  sur le bouton d'origine, le tiroir garde son nom accessible. Les trois rangées d'étiquettes sont
  des listes, pas des suites de mots séparés par des points.
- **Sens de lecture arabe** : la frise a un rail latéral ; il doit passer du bon côté du texte, ce qui
  se constate à la capture et ne se suppose pas.
- **SSR** : le tiroir est un geste, il ne s'ouvre jamais au rendu serveur.
- **i18n** : aucun texte en dur, trois langues.
- **VueUse plutôt que les API du navigateur** pour toute écoute d'évènement.

## Documentation impactée

[`docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md`](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)
— sa section sur les deux DTO hors gardes met en garde contre un **changement** de forme non
rattrapé ; l'implémentation a montré le cas plus insidieux, celui d'une forme **fausse dès
l'écriture** et jamais relue faute d'écran qui la consomme. La conséquence est ajoutée là.

Le cadrage annonçait « aucune page », en supposant que corriger un miroir déjà listé ne déclenchait
rien. C'était vrai du contrat, faux de la mise en garde.

Rien d'autre : ni contrat d'API, ni convention de dossier — le dossier de composants de l'espace ne
porte pas de `README.md`, et cette carte n'introduit pas de convention qui en justifierait un.

Les pages de `dev-book/cadrage/entreprise/ui/` que le tiroir rend fausses depuis `ENT-112` restent
hors du périmètre d'un commit de code.

## Vérification

Capture authentifiée sur la stack du worktree, compte `owner@demo.showcase-recruiter-webapp-saas.example`, thème sombre, deux
recherches — « Preuve ENT-120 · provenances » pour les profils importés, « Profils de démo
(fixtures) » pour un profil portant le projet au Maroc et la marque « rencontré ».

**La grille, mesurée dans le navigateur :**

```
grand écran (1440) : grid-template-columns → 578.859px 810.422px   (5fr / 7fr)
téléphone   (390)  : grid-template-columns → 358px                 (colonne unique)

sections, dans l'ordre du document, profil complet :
  identité · Son projet au Maroc · Son parcours · Métiers · Secteurs ·
  Compétences · Langues · À dévoiler
frises rendues : 2
```

**L'accessibilité, mesurée elle aussi**, et inchangée par la refonte :

```
focus dans le tiroir       : true
après Tab, toujours dedans : true
nom du dialogue            : « Comptable confirmée »
```

Trente profils ouverts puis refermés à la suite par `Échap`, sans incident.

**Cinq défauts trouvés à la capture, corrigés avant clôture** — aucun ne se voyait en lisant le code :

- les **métiers** et les **secteurs** sortaient en JSON brut,
  `#{ "job": "Développeur logiciel", "months": 43, … }` : le miroir de types les déclarait en
  chaînes, le serveur envoie des objets ;
- les **écoles** de même, `{ "id": "65d3…", "name": "UMONS" }` — défaut **antérieur à cette carte**,
  invisible jusqu'ici parce que la capture d'`ENT-112` portait sur une fiche de démonstration, seule
  source qui écrive l'école en texte ;
- les **secteurs** s'affichaient en codes, `CONSEIL_AUDIT` ;
- un **trou de quatre cents pixels** au milieu de la colonne de gauche, la grille répartissant la
  hauteur de la frise entre ses deux rangées ;
- l'**accroche répétait le titre** du tiroir sur les profils où les deux portent le même texte.

**Tests** : `bun run vitest run services/search/ i18n/` → **324 tests, 17 fichiers, verts**.
`profile.vitest.ts` passe de 13 à 18 cas.

**Non lancé** : les gates qualité, qui tournent à l'ouverture de la PR.

## Livré

- `webapp/shared/types/candidate-profile.ts` — les trois formes rétablies : `JobExperience`,
  `SectorExperience`, et `EducationSchool` qui assume les deux écritures de l'école.
- `webapp/services/search/profile.ts` — cinq blocs au lieu de quatre : `expertise` prend les mots
  sans date, `career` ne garde que le daté, l'identité porte l'accroche et le résumé, et `states`
  vient du catalogue commun.
- `webapp/services/search/profile.vitest.ts` — cinq cas neufs sur les formes reçues et la frontière
  entre les deux blocs.
- `webapp/spaces/entreprise/app/components/ProfilePanel.vue` — la grille, les deux frises du kit, les
  rangées de mots, les états près du nom.
- Les trois fichiers de langue — les libellés « Métiers » et « Secteurs », et le catalogue des
  dix-huit secteurs d'expérience.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md` — un champ du miroir que personne
  n'affiche n'est vérifié par rien.

## Reste à faire (hors périmètre)

- **Le repli en colonne unique pour un profil sans aucun poste daté** est écrit et lisible, mais
  **non observé** : les trente profils empilés de la recherche de preuve en ont tous un, et les
  quelques profils sans parcours de la base sont plus loin dans la pagination.
- **La hiérarchie typographique** reste à reprendre : les colonnes rangent, elles ne hiérarchisent
  pas. C'est la moitié de la demande d'origine — « posé de façon brute » — qui n'est pas traitée ici.
- **Le résumé restera vide** sur 3 858 profils sur 3 899, et le bloc « projet au Maroc » sur 3 896 :
  ce ne sont pas des jeux d'essai pauvres, c'est ce que l'import produit.
- **Les trois pages de `dev-book/cadrage/`** fausses depuis `ENT-112` le restent.
- **Un avertissement d'hydratation** au chargement de la Visionneuse, observé et non attribué,
  antérieur à cette carte.

## Définition de terminé

- [x] Au-delà du seuil, le corps du tiroir se lit en deux colonnes ; en dessous, en une seule.
- [x] Sur téléphone, l'ordre est : identité · projet au Maroc · parcours · à dévoiler.
- [x] L'accroche s'affiche en entier, sous l'identité, sur les profils qui en ont une. *Sauf quand
      elle est le titre du tiroir, où elle ferait doublon — décidé à la capture.*
- [x] Les métiers et les secteurs s'affichent en rangées étiquetées distinctes des compétences.
- [x] Les compétences et les langues ont quitté la section du parcours.
- [x] « Rencontré par showcase-recruiter-webapp-saas » et « au Maroc en ce moment » se voient près du nom. *Avec les cinq
      autres états du catalogue, cf. Décisions de cadrage.*
- [x] La colonne de droite porte une frise verticale : les postes, puis les formations.
- [ ] ~~Un profil sans poste daté n'affiche pas de colonne de droite vide.~~ — **écrit, non
      observé** : aucun profil sans parcours dans les trente empilés. Cf. *Reste à faire*.
- [x] Aucune section vide ne s'affiche, pas même en creux.
- [x] Tests unitaires verts sur la fusion : accroche, métiers, secteurs, et les états disponibles dès
      l'ouverture, avant l'arrivée de la fiche.
- [x] Aucun texte en dur ; les trois langues sont renseignées. *Le sens de lecture arabe repose sur
      la frise du kit et sur `bdi` pour les mots latins ; il n'a pas été capturé en arabe.*
- [x] Focus piégé, `Échap`, retour du focus et nom accessible continuent de fonctionner.
