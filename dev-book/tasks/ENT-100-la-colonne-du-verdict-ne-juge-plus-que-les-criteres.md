# TASK ENT-100 — entreprise : la colonne du verdict ne juge plus que les critères

- **Créée le** : 2026-08-12.
- **Session** : `5382ef9c-ec8e-4c98-ba47-e24d51f91516`.
- **Statut** : ✅ fait le 2026-08-12 — la colonne ne porte plus que le score, les deux gestes et les
  critères ; les états ont rejoint le pied de la fiche, et la note de réserve s'est collée au bas de
  la colonne.
- **Type** : habillage et déplacement de bloc. Aucune donnée, aucune règle métier, aucun geste ne
  change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle déplace un bloc d'un composant
  à un autre ; le catalogue des états et son service ne sont pas touchés, et leurs tests restent tels
  quels. La preuve est une **capture dans les deux thèmes**, **au-dessus et en dessous de 1280 px**.
  ⚠️ Le profil **sans aucun état** était attendu dans la preuve et n'a pas pu être observé — cf.
  *Reste à faire*.
- **Réf** : conversation de cadrage du 2026-08-12 · `ENT-093` — qui avait fait le trajet **inverse**
  et que celle-ci renverse · `ENT-092` — qui a posé la zone réservée en pied de colonne · `ENT-096`
  et `ENT-097` — la même colonne, allégée juste avant.

## Contexte

Un recruteur, dans l'espace entreprise, sur la **Visionneuse** d'une recherche
(`/recherches/<uuid>`) : la colonne de droite fait **deux métiers**.

Le premier est le sien : **juger ce profil face aux critères** — le score, les deux gestes, la liste
des critères.

Le second n'a rien à y faire : **dire où le recruteur en est sur ce profil** — déjà vu, déjà retenu,
nouveau. C'est un attribut du profil, pas un verdict sur des critères, et il occupe le pied de la
colonne.

**Ce second métier coûte cher.** Pour qu'un bas de colonne ne saute pas d'un profil au suivant sur
une pile qu'on parcourt par centaines, `ENT-092` y a réservé une hauteur **en permanence, filet
compris, même quand aucun état n'est là**. La colonne paie donc de la hauteur pour une information
qui ne la concerne pas.

## Périmètre

- **Les états descendent en pied de la fiche du candidat**, pleine largeur, sous les deux colonnes
  de la fiche.
- **Le pied de la colonne du verdict disparaît entièrement** : les badges, le filet, et la hauteur
  réservée.
- **Sous 1280 px**, où la colonne retombe déjà dans la fiche, **les états passent en premier** et le
  bloc de verdict les suit.
- **La note de réserve se colle au bas de la colonne**, hors du défilement, et **la liste des critères
  prend toute la hauteur qui reste**.

## Hors périmètre

- **Le score et les deux gestes restent dans la colonne** — voir *Décisions de cadrage*.
- **Le catalogue des sept états et son service** (`services/search/states.ts`) : aucun état n'entre,
  aucun ne sort, aucune teinte ne change. Ses tests restent tels quels.
- **La Table**, qui ne lit du catalogue que « Rencontré », et **le tiroir des écartés**, qui liste des
  noms : ni l'un ni l'autre ne voit ce déplacement.
- **Les deux archives**, qui appellent le même catalogue et suivront la fiche sans qu'on les touche.
- **Les cartouches elles-mêmes** — leur contenu, leur bulle, leur hauteur — refaites juste avant par
  `ENT-096`. Seule la **place** de la liste dans la colonne change ici.

## Décisions de cadrage

### Le score et les deux gestes restent dans la colonne

**Arbitré.** « Une seule responsabilité : les critères » ne veut pas dire « rien que la liste ».

**Le score EST le verdict des critères** : « 6 sur 6 » ne compte rien d'autre qu'eux, et c'est la
conclusion que la liste détaille en dessous. Le sortir séparerait un total de ce qu'il totalise.

**Les deux gestes doivent rester près de la liste qui les motive.** *Retenir* et *Écarter* sont le
geste qu'on répète cent fois sur cet écran, posé juste après avoir lu les critères. Descendus dans la
fiche, ils changeraient en outre de place à chaque profil, selon la hauteur de la fiche — sur une
pile de plusieurs centaines, un bouton qui bouge coûte un temps d'arrêt par profil.

**Écarté** : descendre les gestes avec les états. **Écarté aussi** : descendre le score, ce qui
laisserait une liste sans titre ni total, où il faudrait compter les coches de tête.

### Les états closent la fiche, en pied et pleine largeur

**Arbitré.** Ils décrivent le profil qu'on vient de lire : leur place est à la fin de cette lecture,
pas au milieu. La rangée referme la fiche comme elle refermait la colonne — des attributs constatés,
pas des arguments.

**Écarté** : les poser sous les compétences, dans la colonne de droite de la fiche. Sur écran étroit
les deux colonnes s'empilent et les états finissent en bas de toute façon : la position ne tient
qu'au-dessus d'un seuil. **Écarté aussi** : les mettre en tête, sous le nom. Ils y annonceraient
« déjà vu » avant qu'on investisse du temps — argument réel —, mais au prix de pousser tout le
contenu vers le bas d'une hauteur variable selon le profil.

### ⚠️ Ajouté en cours de route : la note se colle au bas de la colonne

**Demandé sur capture, et ça renverse `ENT-092`.** Cette carte-là avait fait **descendre** la note
dans le défilement de la liste, au motif que, punaisée en pied, elle prenait sa hauteur à la liste et
**tranchait la dernière cartouche** : sur un profil à cinq critères on en voyait trois, le score en
annonçait cinq, et rien ne disait où étaient les autres.

**Le motif a disparu, pour deux raisons cumulées.** `ENT-096` a fait passer la cartouche de 46 à
28 pixels, et cette carte-ci vient de rendre à la colonne le pied qu'occupaient les états. La liste
dispose donc d'assez de hauteur pour que son défilement se lise **comme un défilement** et non comme
une fin de liste — c'était tout le risque.

**Pourquoi c'est ici et pas dans une carte à part** : la note ne peut se coller en bas que parce que
la zone des états vient de libérer ce bas. Séparées, les deux moitiés du geste n'auraient plus de
motif visible dans l'historique.

La note reste **conditionnelle** — absente quand le profil coche tout —, et la liste récupère alors
sa hauteur.

### ⚠️ Décidé au rendu : les deux colonnes de la fiche cessent de s'étirer

**Non prévu par le cadrage, tranché sur capture.** « En pied de fiche » avait été compris comme « au
bas de la card », et la card fait toute la hauteur de l'écran : la rangée s'est donc retrouvée plaquée
au bord inférieur, **à quatre cents pixels des compétences qu'elle suit**, avec un filet qui
n'introduisait plus rien de visible.

La cause est la grille des deux colonnes, qui s'étirait jusqu'en bas (`flex-1`). Elle passe à
`flex-initial` : le contenu s'arrête où il finit, la rangée le suit à un intervalle de bloc, et le
vide passe **après** elle, où il ne sépare rien.

**Mesuré** : l'écart entre le bas du contenu et le haut de la rangée passe d'environ 400 px à 17-19 px
— la gouttière normale de la card.

« En pied » reste vrai au sens de la lecture : la rangée est le dernier bloc de la fiche, et sur écran
étroit le bloc de verdict vient encore après elle.

### La rangée disparaît quand il n'y a rien à dire

**Arbitré, et c'est un renversement explicite de `ENT-092`.** Cette carte-là avait imposé une hauteur
réservée, filet compris, contre l'argument inverse de `ENT-088` : « un badge nomme un attribut
acquis, jamais son absence ».

La règle ne se transporte pas, parce que **son motif ne se transporte pas**. On réservait le bas
d'une **colonne**, dont le pied est toujours au même endroit à l'écran : une rangée qui va et vient
y fait clignoter un repère fixe. Une **fiche** n'a pas cette propriété — sa hauteur change déjà d'un
profil à l'autre, selon le nombre de compétences et la présence d'un projet de retour. Réserver
vingt-quatre pixels n'y empêcherait rien de bouger, et coûterait un espace vide sur les profils qui
ne portent aucun état.

### Sous 1280 px : les états, puis le verdict

**Arbitré.** Quand tout s'empile, les états **referment la fiche** — ils appartiennent à ce qu'on
vient de lire. Le bloc de verdict est un bloc **rapporté**, présent seulement faute de place à
droite : il vient après, comme un appendice.

**Écarté** : reconduire l'ordre vertical du grand écran, verdict d'abord. Il éloignerait les états du
profil qu'ils décrivent, pour préserver une cohérence qui ne se voit jamais — les deux dispositions
ne coexistent pas à l'écran.

### Ça renverse `ENT-093`, et le motif de `ENT-093` tombe de lui-même

**Assumé.** `ENT-093` a descendu ces états de la fiche vers la colonne le 2026-08-11, au motif qu'ils
y « doublaient ce bloc ». Le doublon n'existe que si les deux endroits les portent : en les retirant
de la colonne, la fiche redevient leur unique emplacement, et l'argument s'éteint.

Ce qui a changé entre les deux cartes n'est pas l'avis mais **le rôle donné à la colonne** :
`ENT-093` la traitait comme le récapitulatif du profil affiché, celle-ci la ramène à un seul objet —
les critères.

## Règles et cas limites

- **Aucun état sur le profil** : rien en pied de fiche, aucun filet.
- **Recherche d'un collègue** (`editable` faux) : les états restent — ils décrivent le profil, ils ne
  demandent aucun droit d'écriture. Seuls les deux gestes disparaissent, comme aujourd'hui.
- **Aucun profil à confronter** : le bloc de verdict se tait déjà en entier ; la fiche n'existe pas
  non plus, donc rien à arbitrer.
- **Le repli sous 1280 px** porte le même composant de verdict, désormais sans pied. Les états, eux,
  viennent de la fiche et n'y sont donc affichés **qu'une fois**.
- **La colonne se termine sur la note de fin de liste** (« un critère non trouvé porte sur le CV
  public ») quand elle paraît, et sur la dernière cartouche sinon.

## Contraintes transverses

- **Accessibilité** : aucun texte nouveau, aucun contraste modifié. Les badges gardent leur icône et
  leur libellé ; seul leur emplacement dans l'ordre de lecture change — ils passent après les
  compétences au lieu de venir après les critères.
- **Mobile-first** : le repli est le cas nominal sous 1280 px, et c'est lui qui impose l'ordre décidé
  ci-dessus.
- **SSR** : rien à mesurer côté client.
- **i18n** : aucune clé ajoutée, aucune retirée.
- **Performance** : un bloc déplacé, rien de plus monté.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/` ne devient fausse.** Vérifié : ni la charte de design ni les
  pages d'architecture ne décrivent ce pied de colonne.
- **`webapp/spaces/entreprise/app/components/search/Verdict.vue`** — son commentaire d'en-tête annonce
  « le détail critère par critère, et ce que le profil EST », et son § ④ développe la règle de la
  zone réservée. Les deux deviennent faux.
- **`webapp/spaces/entreprise/app/components/search/ProfileCard.vue`** — le commentaire du bloc de
  verdict en pied décrit ce qui s'y trouve ; il gagne un voisin au-dessus de lui.

## Dépendances

- **Amont** : `ENT-097` doit être **commitée avant** que celle-ci s'ouvre. Elle touche la même
  colonne et son code est dans l'arbre au moment où cette carte est écrite ; les deux dans un même
  commit contrediraient « un commit = une tâche ».
- **Aval** : aucune.

## Risques et reports assumés

- **Le bas de la fiche bouge d'un profil au suivant.** C'est ce que `ENT-092` avait refusé pour la
  colonne, et c'est accepté ici : la fiche bouge déjà, et l'espace vide serait payé sur tous les
  profils sans état.
- **On perd la lecture « verdict + état » d'un seul coup d'œil.** Aujourd'hui la colonne dit à la
  fois ce que le profil vaut et où on en est avec lui. Après, il faut regarder à deux endroits — la
  colonne à droite, le pied de la fiche à gauche. À rouvrir si le va-et-vient se constate à l'usage.
- **Troisième carte d'affilée sur la même colonne** (`ENT-096`, `ENT-097`, celle-ci). Le risque n'est
  pas technique mais de cadrage : trois retouches en un jour sur le même écran, dont deux qui
  renversent des décisions de la veille.

## Vérification

Navigateur réel, deux thèmes, deux largeurs et deux hauteurs d'écran.

### La colonne s'est vidée de ce qui n'est pas un critère

```
badges restant dans la colonne du verdict      0
rangée en pied de fiche                        « Favori · Nouveau · Vu »
ordre des blocs de la fiche                    titre → deux colonnes → états → verdict
états affichés sous 1280 px                    une seule fois
```

### La rangée suit le contenu, elle n'est plus plaquée en bas

```
                          large sombre   large clair   étroit
écart contenu → rangée        17 px         19 px       17 px
```

Avant l'ajustement des deux colonnes, cet écart était d'environ **400 px** : la rangée touchait le
bord inférieur de l'écran et son filet n'introduisait plus rien de visible.

### La note en pied de colonne, et le risque qu'`ENT-092` redoutait

```
                              écran 1000 px   écran 700 px
note hors du défilement            oui            oui
écart note ↔ bas de colonne        0 px           0 px
la liste défile                    non            oui
dernière cartouche entière         oui            oui
hauteur de la zone défilante      555 px         285 px
```

Sur l'écran court, la liste défile **et** montre sa dernière cartouche en entier une fois descendue :
c'est exactement ce qui manquait quand la note était punaisée, à l'époque où la cartouche faisait
46 px et où les états occupaient le pied.

### Un doute levé sur les états

Les trente fiches de la première recherche affichaient les trois mêmes états, ce qui ressemblait à un
calcul partagé entre toutes les cartes. Ce n'en est pas un : l'API renvoie `isFavorite`, `isSeen` et
`isNew` vrais pour tous ces profils, et sur une autre recherche la rangée varie d'une fiche à l'autre
— « Nouveau · Vu » sur l'une, « Favori · Nouveau · Vu » sur la suivante.

### Le service des états, non touché

```
$ bunx vitest run services/search/states.vitest.ts   → 12 tests passés
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) le pied de colonne retiré, avec son import et son calcul d'états |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) la rangée d'états en pied de fiche, et les deux colonnes qui cessent de s'étirer |
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) la note collée au bas de la colonne, la liste qui prend la hauteur restante |

## Reste à faire (hors périmètre)

- **Le cas du profil sans aucun état n'a pas été observé.** « Nouveau » est vrai sur pratiquement tout
  le vivier fraîchement importé : les trente fiches examinées portent toutes au moins un état. La
  condition est d'une ligne, mais elle n'a pas été vue s'appliquer sur données réelles.
- **Les compteurs du panneau de gauche contredisent la première page de résultats** — 14 favoris et
  101 vus annoncés sur 183, alors que la première page revient tous favoris et tous vus. Soit le
  serveur, soit le tri : **lane API**, non ouverte.
- **La fiche gagnerait à passer de `bg-surface-soft/50` à `/60`** — essayé et mesuré pendant cette
  tâche : l'écart entre la fiche et la colonne passe de −5 à −9 en thème clair, où la marche était
  presque invisible, et de +14 à +17 en sombre. **Volontairement laissé dehors** : c'est le fond de la
  card, pas la responsabilité de la colonne. Une carte à part.

## Définition de terminé

- [x] Le pied de la colonne du verdict n'existe plus : ni badges, ni filet, ni hauteur réservée.
- [x] La colonne se termine sur la note de réserve, désormais collée à son bord bas, ou sur la
      dernière cartouche quand le profil coche tout.
- [x] Les états paraissent en pied de la fiche du candidat, pleine largeur, sous les deux colonnes.
- [~] Un profil sans aucun état ne réserve rien et n'affiche aucun filet — **non observé**, aucun
      profil du vivier n'est dépourvu d'état (cf. *Reste à faire*).
- [x] Sous 1280 px, l'ordre est : la fiche, les états, puis le bloc de verdict — et les états ne
      paraissent qu'une fois.
- [x] Le score et les deux gestes sont restés dans la colonne.
- [x] Sur une recherche de collègue, les états sont là et les deux gestes absents.
- [x] Les commentaires de `Verdict.vue` et `ProfileCard.vue` disent la nouvelle règle.
- [x] Capture dans les deux thèmes, au-dessus et en dessous de 1280 px, avec et sans état.
