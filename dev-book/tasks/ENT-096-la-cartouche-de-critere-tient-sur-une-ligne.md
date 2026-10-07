# TASK ENT-096 — entreprise : la cartouche de critère tient sur une ligne

- **Créée le** : 2026-08-12.
- **Session** : `5382ef9c-ec8e-4c98-ba47-e24d51f91516`.
- **Statut** : ✅ fait le 2026-08-12 — la cartouche est passée de 49 à 28 pixels, la catégorie a
  quitté la ligne pour la bulle et l'écoute, et toute cartouche s'ouvre au survol comme à l'appui.
- **Type** : habillage. Aucune donnée, aucune règle métier, aucun geste ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle change un gabarit, des classes et
  le composant qui porte la bulle. La preuve est une **capture dans les deux thèmes**, sur un profil
  qui coche tout et un profil incomplet, plus **l'ouverture de la bulle vérifiée à la souris ET au
  doigt**.
- **Réf** : conversation de cadrage du 2026-08-12 · `ENT-092` — qui a posé la liste actuelle et que
  celle-ci retouche · `ENT-091` — le commentaire d'en-tête de cette même liste · `ENT-097` — le bloc
  de score, même colonne, **indépendante**.

## Contexte

Un recruteur, dans l'espace entreprise, sur la **Visionneuse** d'une recherche
(`/recherches/<uuid>`) : la colonne de droite empile **une cartouche par critère demandé**. Chacune
tient sur **deux étages** — le critère au-dessus, sa catégorie en tout petit en dessous.

À partir de six critères, la liste défile. Et elle défile **sur chacun des centaines de profils de
la pile** : le tri consiste précisément à relire cette liste, profil après profil. Chaque geste de
défilement est donc payé cent fois.

La hauteur perdue ne l'est pas pour de l'information. **La cartouche dit deux fois la même chose** :
le rond vert à coche à gauche, et le mot « trouvé » à droite. Idem pour le rond gris et « non
trouvé ». Seul le cas d'un métier échappe à la redite — « actuel » et « passé » ne se déduisent
d'aucun rond.

## Périmètre

- **La cartouche passe sur UNE ligne** : le rond, le critère en encre franche, et — pour un métier
  seulement — « Poste actuel » ou « Poste passé » en fin de ligne.
- **Les mots « trouvé » et « non trouvé » disparaissent.** Le rond les portait déjà.
- **La catégorie quitte la ligne.** Elle ne se lit plus que dans la bulle, et s'entend dans le texte
  réservé aux lecteurs d'écran.
- **Toute la cartouche ouvre la bulle**, au survol à la souris **et à l'appui au doigt**.
- **La bulle redonne trois choses** : la catégorie, le critère **en entier**, et ce que le profil
  porte en face quand il porte quelque chose.
- **Le soulignement pointillé et le curseur d'aide partent**, remplacés par **un fond qui
  s'éclaire** — le seul signe restant qu'une cartouche réagit.
- **La marge intérieure ne bouge pas**, ni l'écart entre cartouches.

## Hors périmètre

- **L'encadré de fin de liste** (« un critère non trouvé ne dit pas que le candidat ne sait pas
  faire ») reste tel quel, à sa place, dans le défilement.
- **Aucun arrêt clavier n'est ajouté** sur les cartouches — voir *Décisions de cadrage*.
- **Les libellés de catégorie ne sont pas abrégés** : pas de seconde série de traductions courtes.
- **Savoir OÙ dans le profil la valeur a été trouvée** — « dans son poste chez Sanofi, 2019-2023 ».
  Le serveur envoie la valeur, pas l'endroit. Ce serait une tâche lane API en amont ; elle **n'est
  pas ouverte**.
- **Le bloc de score** en tête de colonne : `ENT-097`.
- **La Table**, le tiroir des écartés, le panneau de gauche.

## Décisions de cadrage

### Le mot du verdict part, le rond suffit

**Arbitré.** « trouvé » à côté d'une coche verte et « non trouvé » à côté d'un rond gris ne
transportent rien : ce sont deux écritures du même signe, à trois centimètres d'écart. Leur
suppression est ce qui **rend la ligne unique possible sans rien perdre**.

**« actuel » et « passé » restent**, et eux seuls. Le tiret du rond dit « plus maintenant » de façon
allusive ; le mot le dit. C'est la seule information de la ligne qu'aucun autre élément ne porte.

### La catégorie quitte la ligne — décision RENVERSÉE au rendu

**Cadré d'abord dans l'autre sens, et corrigé sur capture.** Le cadrage retenait la catégorie devant
le critère, au motif qu'elle distingue « Casablanca » de « scrum » — c'est pour ça que `ENT-088`
l'avait mise sous le critère.

**Le rendu a démenti l'argument.** Une recherche pose ses mots-clés par paquets : sur les deux
recherches examinées, huit lignes sur dix commençaient par « Mot-clé ». Un repère qui se répète
huit fois ne repère plus rien — il pousse le critère vers la droite et le fait couper, alors que
c'est lui qu'on lit. « Développeur logiciel » s'écrivait « Archite… » pour laisser la place à un mot
identique à celui du dessus.

**La catégorie n'est pas perdue, elle est déplacée** : la bulle l'annonce en première ligne, sur
toutes les cartouches sans exception. Elle se consulte quand le doute existe, au lieu de s'imposer
quand il n'existe pas.

**Et elle reste À L'ÉCOUTE.** Un texte réservé aux lecteurs d'écran la porte devant le critère, sans
occuper un pixel : sans lui, « scrum » s'énoncerait seul, sans qu'on sache de quoi il s'agit. C'est
la seule partie de la décision d'origine qui survit telle quelle.

**Ce que ça rend** : le critère dispose de toute la largeur, et plus rien ne se coupe sur les
recherches examinées. La règle de troncature devient sans objet — il ne reste qu'un élément
flexible sur la ligne.

**Écarté au passage** : une série de libellés courts réservée à cette colonne (« Secteur »,
« Horizon »). Dix-huit libellés à dupliquer et deux graphies du même critère à l'écran, pour un
repère qu'on vient de juger inutile sur la ligne.

### La bulle redonne la ligne entière avant ce que le profil porte

**Arbitré.** C'est ce qui rend vraie la règle « **chaque cartouche est survolable** ». Aujourd'hui,
le code refuse délibérément la bulle sur une ligne qui n'a rien à dire — et il a raison : une bulle
qui ne s'ouvre pas se lit comme un écran cassé.

En redonnant la catégorie et le critère non coupés, **toute cartouche a toujours quelque chose à
dire**, y compris celles dont le critère manque. Et ça résout du même coup la troncature : ce que la
ligne unique coupe, la bulle le rend.

**Écarté** : n'afficher que ce que le profil porte — les lignes « non trouvé » resteraient muettes,
et une ligne coupée ne se lirait nulle part. Écarté aussi : une phrase de réserve sur les critères
manquants — elle répéterait le rond gris **et** l'encadré déjà présent en pied de liste.

### Le survol sur ordinateur, l'appui sur écran tactile

**Arbitré.** Une infobulle classique ne répond qu'au survol et au clavier : **au doigt, elle ne fait
rien**. Or sous 1280 px cette colonne retombe en pied de fiche, sur des écrans qui n'ont pas de
souris.

La bibliothèque le couvre sans bricolage : `UPopover` en mode survol avec le toucher activé
(`mode="hover"`, `enable-touch`) rend **le même composant** au serveur et dans le navigateur, et
ouvre au survol comme à l'appui. Vérifié dans le code de la bibliothèque installée : Reka expose
bien `enableTouch` sur sa carte de survol, et Nuxt UI la transmet.

⚠️ **Ce composant n'est pas annoncé comme une infobulle** aux lecteurs d'écran. Le texte caché déjà
présent dans la cartouche reste donc **indispensable** : c'est lui qui porte ce que le profil
comporte, exactement comme aujourd'hui.

### Pas d'arrêt clavier sur les cartouches

**Arbitré, et c'est un manque assumé.**

Ce qui est couvert : un **lecteur d'écran** lit le texte complet — une coupure d'affichage masque le
texte, elle ne le supprime pas ; la **souris** ouvre au survol ; le **doigt** ouvre à l'appui.

Ce qui ne l'est pas : une personne **voyante, au clavier seul**, tombant sur une ligne coupée.

Le prix de l'inverse : huit critères font huit tabulations à traverser **avant d'atteindre
« Retenir » et « Écarter »**, sur chaque profil d'une pile qui en compte des centaines. C'est
précisément ce que le code actuel évite déjà, commentaire à l'appui.

### Le fond qui s'éclaire remplace le pointillé

**Arbitré.** Le soulignement pointillé signalait les cinq cartouches sur six qui avaient quelque
chose à dire. Toutes en ayant désormais, il ne distingue plus rien — mais sans lui, plus rien
n'annonce que la cartouche réagit.

Un **changement de fond au survol** le remplace, et il travaille double : il annonce le survol sur
ordinateur, et il montre la cible sur écran tactile.

### La marge ne bouge pas

**Arbitré.** Le gain vient de la ligne supprimée, pas d'un resserrement : environ **46 px par
cartouche ramenés à 28**, soit une liste de huit critères qui passe d'environ 400 px à 250 px.

**Écarté** : resserrer aussi la marge et l'écart. Sous 24 px, la cible devient petite pour un appui
au doigt — alors qu'on vient précisément de la rendre ouvrable à l'appui.

## Règles et cas limites

- **Ligne trop étroite** : le critère se coupe, et le mot du « quand » reste collé à droite sans
  jamais se couper. C'est le seul élément flexible de la ligne.
- **Toutes les facettes ont une catégorie.** `LABELLED_FACETS` les couvre toutes, mot-clé compris —
  aucun cas « sans catégorie » à gérer dans la bulle. ⚠️ Le commentaire de `CriterionDescription` dit
  l'inverse (« `null` sur un mot-clé »), il est **périmé** et à corriger au passage.
- **Critère booléen** (`geoMobility`, `hasPiedATerre`, `star`) : la ligne ne donne que « Oui », et
  c'est la bulle qui dit « Pied-à-terre ». ⚠️ Seul cas où la ligne seule ne se suffit pas — assumé :
  ces trois facettes sont rares dans une recherche, et le rond dit déjà l'essentiel.
- **Le mot du « quand » porte le même interligne que le critère.** À l'interligne d'un paragraphe il
  rendait sa cartouche trois pixels plus haute que ses voisines, et la liste ondulait selon les
  lignes qui portent un métier.
- **Aucun profil à confronter** (`matched` vaut `null`) : pas de rond, pas de mot de fin — la bulle
  existe quand même et redonne la ligne entière.
- **Critère non trouvé** : rond gris, nom en gris, aucun mot de verdict, bulle qui redonne la ligne.
- **Recherche d'un collègue** (`editable` faux) : rien ne change ici, la liste n'a jamais porté de
  geste.
- **Repli sous 1280 px** : le même composant tombe en pied de fiche, et l'appui y prend le relais du
  survol.

## Contraintes transverses

- **Accessibilité** : le sens ne tient jamais à la seule couleur — le rond garde coche, croix ou
  tiret. Le texte réservé aux lecteurs d'écran **gagne la catégorie** que la ligne a perdue, et
  garde ce que le profil porte : à l'écoute, la cartouche dit exactement ce qu'elle disait avant.
  Le manque clavier est nommé ci-dessus, il est assumé.
- **Mobile-first** : le repli en pied de fiche est le cas nominal sous 1280 px, et c'est lui qui
  impose l'ouverture à l'appui.
- **SSR** : un seul composant, même rendu serveur et navigateur — pas de bascule selon le type de
  pointeur, donc aucun risque de rendu divergent à l'hydratation.
- **i18n** : aucun texte nouveau. Deux clés perdent leur dernier consommateur du dépôt —
  `search.viewer.panel.found` et `search.viewer.panel.notFound` — et sont **retirées des trois
  langues**, après vérification qu'aucun autre fichier ne les appelle.
- **Performance** : la bulle reste construite à la demande ; rien n'est monté d'avance pour les dix
  cartouches.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/` ne devient fausse.** Vérifié : la charte de design ne dit rien
  des infobulles ni du soulignement pointillé.
- **`webapp/spaces/entreprise/app/components/search/MatchList.vue`** — le commentaire d'en-tête
  décrit la ligne à deux étages et la règle « l'infobulle n'enveloppe que ce qui a quelque chose à
  dire ». Les deux deviennent faux.
- **`webapp/services/search/criteria.ts`** — le commentaire de `CriterionDescription` affirme qu'un
  mot-clé n'a pas de libellé, ce que `LABELLED_FACETS` dément.

## Dépendances

- **Amont** : aucune. La branche est à jour sur `develop`, `ENT-095` est commitée.
- **Aval** : rien n'attend cette carte. `ENT-097` touche la même colonne mais un autre fichier, et
  les deux sont indépendantes.
- **Nommé, pas ouvert** : dire dans quelle partie du profil la valeur a été trouvée demande que le
  serveur l'envoie — lane API.

## Risques et reports assumés

- **Une personne au clavier seul, sans lecteur d'écran, ne pourra pas lire une ligne coupée.** Pesé
  contre huit tabulations par profil sur une pile de centaines ; le manque est retenu.
- **La bulle n'est plus une infobulle au sens des lecteurs d'écran.** Toute la couverture repose donc
  sur le texte caché — s'il est retiré un jour, l'information disparaît sans que rien ne le signale.
- **La catégorie n'est plus visible sans geste.** Sur une recherche qui mêle des natures très
  différentes — une ville, un secteur, un mot-clé —, deux lignes voisines peuvent se ressembler et
  il faut survoler pour trancher. C'est le prix accepté pour que le critère ne soit plus coupé ;
  à rouvrir si la confusion se constate sur de vraies recherches.
- **Un critère qui ne vaut qu'un oui n'écrit que son libellé.** « Pied-à-terre », sans le « Oui »
  que la bulle porte. Aucune recherche enregistrée n'emploie ces trois facettes aujourd'hui : le
  rendu n'a **pas pu être vérifié sur données réelles**, seulement par le service qui le compose,
  lequel est couvert par ses tests.

## Vérification

Deux navigateurs réels, même recherche, deux arbres : l'« avant » dans l'arbre principal qui tourne
sur `develop`, l'« après » dans le worktree de la lane.

### La hauteur, mesurée

```
                          AVANT (develop)   APRÈS
hauteur d'une cartouche   49 px             28 px
la liste, 9 critères      548 px            —
la liste, 10 critères     —                 396 px
```

Les dix cartouches font **28 px chacune**, y compris celle qui porte « Poste actuel » : à
l'interligne d'un paragraphe elle en faisait 31, et la liste ondulait.

### La bulle, cartouche par cartouche — 10 sur 10

```
0  « Développeur logiciel · Poste actuel » → ["Poste recherché", "Développeur logiciel"]
1  « 5 ans et plus »                       → ["Expérience", "5 ans et plus", "12 ans"]
2  « java »                                → ["Mot-clé", "java", "Senior software and Cloud
                                               engineer (Java/Spring/Angular/ReactJs/AWS)"]
3  « sql »                                 → ["Mot-clé", "sql"]
7  « nestjs »        (critère non trouvé)  → ["Mot-clé", "nestjs"]
```

La troisième ligne n'apparaît que si le profil porte quelque chose ; **les cartouches sans valeur
trouvée ouvrent quand même leur bulle**, ce qui était l'enjeu de la règle « toute cartouche est
survolable ».

### Le doigt, le clavier, la troncature

```
appui tactile sur une cartouche     → bulle ouverte
second appui                        → refermée
éléments focalisables dans la liste → 0
colonne forcée à 200 px             → seul le critère se coupe
largeur réelle minimale mesurée     → 324 px, aucun chevauchement
```

### Les tests du service que la ligne consomme

```
$ bunx vitest run services/search/criteria.vitest.ts   → 62 tests passés (483 ms)
```

`criterionText` y couvre déjà le cas d'un critère qui ne vaut qu'un oui — c'est lui qui écrit
« Pied-à-terre » plutôt que « Oui ».

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) ligne unique, catégorie à l'écoute et dans la bulle, panneau au survol et à l'appui, fond qui s'éclaire, libellé pour les critères booléens |
| `services/search/criteria.ts` | (M) le commentaire qui affirmait qu'un mot-clé n'a pas de libellé |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) « Trouvé » et « Non trouvé » retirés — plus aucun consommateur |

## Reste à faire (hors périmètre)

- **Le critère de pays affiche son code brut** — « FR » et non « France ». `ENUM_CATALOGUES` ne
  couvre pas la facette `country`. Défaut **antérieur**, visible avant comme après.
- **Le mot du « quand » commence par « Poste »**, qui est déjà le premier mot de sa catégorie. La
  redite ne se voyait pas quand les deux étaient sur des lignes différentes ; elle se voit dans la
  bulle. Libellés existants, non touchés ici.
- **Sous 250 px de colonne, la ligne et le mot du « quand » se chevauchent.** La grille ne descend
  jamais là — le minimum réel mesuré est 324 px, sans chevauchement —, donc aucune garde n'a été
  ajoutée pour un cas que le produit ne produit pas.

## Définition de terminé

- [x] La cartouche tient sur une ligne, marge et écart inchangés, **et toutes font la même hauteur**,
      qu'elles portent ou non un mot du « quand ».
- [x] La catégorie ne s'écrit plus sur la ligne ; le critère occupe toute la largeur restante.
- [x] Les mots « trouvé » et « non trouvé » ont disparu ; « Poste actuel » et « Poste passé » sont
      restés.
- [x] Un critère qui ne vaut qu'un oui écrit son libellé, pas « Oui ».
- [x] Toute la cartouche ouvre la bulle au survol **et** à l'appui, y compris sur un critère manquant.
- [x] La bulle donne la catégorie, le critère entier, puis ce que le profil porte s'il porte quelque
      chose.
- [x] Le soulignement pointillé et le curseur d'aide ont disparu ; le fond s'éclaire au survol.
- [x] Le texte à l'écoute porte la catégorie puis ce que le profil comporte, et aucune cartouche
      n'est un arrêt clavier.
- [x] Les commentaires de `MatchList.vue` et de `criteria.ts` disent la nouvelle règle.
- [x] Capture dans les deux thèmes, sur un profil complet et un profil incomplet.
