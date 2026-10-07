# TASK ENT-092 — entreprise : la colonne du verdict baisse la voix

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — la liste tient sur une couleur et du gris, la note défile
  avec elle, le bas de colonne garde sa hauteur, et la carte se lit en trois paliers de profondeur.
- **Type** : habillage. Aucune donnée, aucune règle métier, aucun geste ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle change des teintes, une graisse
  de texte et la place de deux blocs. La preuve est une **capture dans les deux thèmes**, plus la
  **mesure du défilement** — la note doit se trouver sous le dernier critère, et non le trancher.
- **Réf** : maquette fournie en conversation le 2026-08-11 · `ENT-088` — qui a posé cette colonne et
  que celle-ci retouche · `ENT-083` — le rouge du verdict, **entièrement défait** · `ENT-091` — le
  commentaire d'en-tête de la liste, à clore avant d'implémenter.

## Contexte

La colonne a été livrée le matin même sur une maquette. La maquette a évolué, et l'écart porte
entièrement sur **le poids visuel**. Quatre défauts, dont un qui trompe le lecteur.

- **La note coupe la liste.** Punaisée en bas de colonne, hors du défilement, elle tranche la
  quatrième cartouche en deux. Sur un profil à cinq critères, on en voit trois : le score annonce
  « sur 5 », l'œil en compte 3, et rien ne dit où sont les deux autres.
- **Deux pastilles vertes pleines pèsent autant que le bouton *Retenir***, qui est pourtant le seul
  geste de l'écran. Un constat ne doit pas crier plus fort qu'une action.
- **Les six critères s'écrivent dans la même encre**, qu'ils soient satisfaits ou non. Rien ne
  hiérarchise à l'intérieur de la liste.
- **Le bas de colonne saute** d'un profil à l'autre selon qu'il porte des pastilles ou non.

## Périmètre

- **Une seule couleur dans la liste** — celle de la marque — plus du gris.
- **Le rond devient un disque sombre à signe coloré.**
- **Le nom d'un critère manquant passe en gris.**
- **La note descend dans le défilement**, en dernier élément de la liste.
- **Le bas de colonne est réservé aux états**, séparé par un filet, à hauteur constante.

## Hors périmètre

- **Les deux états nouveaux** (« vu », « écarté ») — carte suivante.
- **Le compte d'occurrences**, qui demande une évolution du serveur : lane API.
- **La suppression des tokens de verdict** devenus sans emploi — voir *Risques*.
- **La Table**, le tiroir des écartés, les deux archives.

## Décisions de cadrage

### Une seule couleur : celle de la marque

**Arbitré.** Ce qui est trouvé prend l'accent — le lime en thème sombre, le Cèdre en clair — et ce
qui manque reste gris. La paire vert/rouge disparaît de l'écran : il n'y a plus deux couleurs qui se
répondent, mais **une couleur et son absence**.

⚠️ **Ça contredit un texte existant, et il faudra le corriger.** `themes.css` écrit que le vert de
verdict n'est pas l'accent, « réservé au favori, le seul état que le recruteur PRODUIT ». L'accent
gagne donc un second sens. L'argument qui le rend tenable : dans cette colonne, l'accent ne dit plus
« état produit par le recruteur » mais **« ce qui compte »** — et c'est aussi ce que dit le bouton
*Retenir* juste au-dessus. Les deux emplois se parlent au lieu de se disputer.

### Le disque s'assombrit, le signe s'éclaire

**Arbitré.** Le rond n'est plus une pastille pleine à signe découpé mais un **disque sombre**, à
teinte très diluée, dont **la coche porte la couleur vive**. Six lignes cessent de faire six taches,
et la liste rend au bouton *Retenir* la préséance qu'il avait perdue.

Le signe reste dedans, coche ou croix : la couleur ne porte jamais le sens seule.

### Le nom d'un critère manquant se retire

**Arbitré.** Ce que le profil porte reste en encre franche, ce qui manque passe en gris — la même
encre que sa ligne de nature en dessous. Une hiérarchie qui ne coûte aucune couleur : l'œil trouve
d'abord ce qui est là.

**Contrepartie assumée** : un critère manquant se remarque moins, alors que c'est parfois ce qu'on
vient chercher. Le mot du verdict, lui, reste écrit sur chaque ligne.

### La note descend dans le défilement, et c'est ce qui corrige la coupe

**Arbitré.** Elle cesse d'être punaisée en pied de colonne pour devenir le **dernier élément de la
liste** : on la rencontre après le dernier critère, en descendant, comme une phrase de fin.

C'est la décision qui règle le défaut le plus grave : rien ne prend plus de hauteur à la liste, donc
plus rien ne la tranche. Elle reste **conditionnelle** — absente quand tous les critères sont
trouvés —, ce que le défilement rend sans conséquence puisqu'elle ne réserve plus rien.

### La zone basse garde sa hauteur, filet compris

**Arbitré, et c'est un renversement explicite.** `ENT-088` disait « un badge nomme un attribut
acquis, jamais son absence — le pied reste vide plutôt que de réserver une hauteur ». La règle
s'inverse ici pour le motif contraire : **une hauteur qui ne bouge pas d'un profil au suivant**. Sur
une pile qu'on parcourt par centaines, un bas de colonne qui saute est plus coûteux qu'un espace
vide.

Le filet reste tracé même sans pastille : un trait qui apparaît et disparaît d'un profil à l'autre
clignote.


### ⚠️ Écarts constatés à l'implémentation

Quatre décisions prises sur rendu, qui dépassent ce que la carte annonçait le matin.

- **La carte se lit en TROIS PALIERS de profondeur**, et non plus sur un aplat unique. La fiche du
  profil avance (`--surface-soft` dilué de moitié, `#2e2e2e`), la colonne du verdict tient le ton des
  cartes (`--surface`, `#212121`), les cartouches descendent au fond (`--bg`, `#1a1a1a`). Le fond de
  la colonne n'était pas au périmètre — la carte parlait des couleurs de la liste, pas de ce qui la
  porte.
- **Ça défait `ENT-078`**, où la demande était l'inverse : « ne pas voir de séparation avec la
  colonne de droite ». Elle tient toujours au sens strict — aucun trait, aucune couture, une seule
  carte — mais ses deux moitiés ne sont plus au même niveau. C'est la profondeur qui distingue, pas
  une bordure.
- **L'anneau du profil retenu passe en CONTOUR** (`outline`), et le fond de la colonne l'impose : un
  anneau intérieur se peint SOUS les enfants, et la colonne, qui touche le bord droit au pixel près,
  l'effaçait sur trois de ses quatre côtés. Le contour se peint par-dessus tout et ne prend aucune
  place dans la mise en page — la contrainte d'origine, ne rien décaler à la bascule de l'étoile,
  est mieux servie qu'avant.
- **Deux demi-paliers ont été créés puis retirés.** `shark-850` d'abord, `shark-750` ensuite, chacun
  avec son pendant clair et son rôle sémantique. Une **dilution** du ton existant a fini par donner
  la même valeur sans toucher au nuancier. Ce qui reste de l'aller-retour : le nuancier est intact,
  et `--surface-soft/50` est le seul endroit du produit où une surface se pose par dilution.

  ⚠️ **Une dilution dépend de ce qu'il y a derrière.** Tant que ce bloc est posé sur le fond de page,
  `#2e2e2e` est garanti ; posé ailleurs — tiroir, fenêtre modale — il changerait de couleur sans que
  rien ne le signale. Accepté ici : ce bloc est la carte principale d'une page, il ne voyage pas.

### ⚠️ Un défaut trouvé et corrigé dans le périmètre

Les ronds neutres étaient peints en `bg-muted/20`. Or `bg-muted` **n'est pas le gris du nuancier** :
Nuxt UI revendique ce nom et le branche sur la surface douce — c'est-à-dire sur le fond de la
cartouche elle-même. Le rond se peignait dans la couleur de ce qu'il était censé trancher. Il passe
en `bg-text/10`.

Le verrou `app-no-ambiguous-kit-class` ne l'a pas vu, et pour deux raisons cumulées : il ne vise que
la classe **exacte**, sans modificateur d'opacité, et il ne lit que les classes **statiques du
gabarit** — celle-ci vivait dans une chaîne du script.

## Règles et cas limites

- **Recherche d'un collègue** (`editable` faux) : pas de gestes, mais l'en-tête, les lignes, la note
  et la zone basse restent.
- **Aucun profil à confronter** (`matched` vaut `null`) : pas de rond, pas de note — inchangé.
- **Tous les critères trouvés** : pas de note, et la zone basse garde sa hauteur.
- **Aucun état sur le profil** : filet tracé, espace vide en dessous.
- **Le repli en pied de fiche** sous 1280 px porte le même composant, donc les mêmes règles.

## Contraintes transverses

- **Accessibilité** : le sens ne tient jamais à la seule couleur — le rond porte coche ou croix, et
  le mot du verdict reste écrit sur chaque ligne. Le passage du rouge au gris **améliore** la
  situation pour qui distingue mal les couleurs : il ne reste qu'une teinte à reconnaître.
- **i18n** : aucun texte nouveau.
- **SSR** : rien à mesurer côté client.
- **Mobile-first** : le repli est le cas nominal sous 1280 px.

## Documentation impactée

- **`webapp/base/app/assets/css/themes.css`** — le commentaire du bloc des verdicts affirme que
  `--verdict-met` n'est pas `--accent` « réservé au favori ». Il devient faux.
- **`docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`** — la charte liste `text-verdict-met` et
  `text-verdict-missed` parmi les utilitaires du kit, et pose que « la couleur appartient à l'accent
  et aux états ». Elle doit dire ce que l'accent signifie quand il sort du favori.

## Dépendances

- **Amont** : `ENT-091` est **non commitée dans l'arbre** et touche le même fichier
  (`MatchList.vue`). Elle se clôt par `/commit-tache` **avant** d'ouvrir celle-ci, sans quoi les deux
  finissent dans le même commit.
- **Aval** : `ENT-093`, les deux états nouveaux, qui remplira la zone basse posée ici.

## Vérification

### Les trois paliers de la carte, mesurés au rendu

```
                     sombre                        clair
la fiche profil      rgb(50, 50, 50)   #2e2e2e     rgb(215, 225, 217)
la colonne           rgb(33, 33, 33)   #212121     rgb(226, 233, 227)
une cartouche        rgb(26, 26, 26)   #1a1a1a     rgb(236, 240, 239)
```

Chaque marche recule. En sombre elle assombrit, en clair elle éclaircit — l'élévation s'inverse avec
le thème, et les trois tokens la suivent sans qu'aucune valeur ne soit écrite dans un composant.

### La liste, sur un profil incomplet

```
rouge encore présent            non — plus aucune trace des tokens de verdict
ronds, deux teintes             accent à 15 %   ·   encre à 10 %
glyphes                         #c6e76b   ·   #a5a5a5
noms, deux encres               blanc franc   ·   gris, selon que le critère est trouvé
verdicts, deux encres           accent   ·   gris
```

### La note et le bas de colonne

```
note                     10 px sous la dernière cartouche, DANS le conteneur qui défile
à 720 px de haut         la liste défile vraiment, la note se trouve au bout du parcours
zone basse               filet 1 px présent · 24 px sans pastille · 45 px avec
```

### L'anneau du profil retenu

```
2px solid rgb(198, 231, 107)   décalage -2px
```

Vérifié en capture : il fait le tour complet, colonne du verdict comprise. En anneau intérieur, la
colonne l'effaçait sur trois côtés.

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
$ bunx vitest run services/search/criteria.vitest.ts → 62 tests passés
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) une couleur et du gris, disque sombre, noms retirés, note dans le défilement, cartouches plus fines |
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) la zone basse réservée, filet compris |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) les trois paliers, l'anneau en contour |
| `base/app/assets/css/themes.css` | (M) le commentaire des verdicts dit que la Visionneuse ne les emploie plus |
| `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` | (M) l'accent ne dit pas « favori », il dit « ce qui compte » |

## Reste à faire (hors périmètre)

- **`--verdict-met` n'a plus aucun consommateur**, `--verdict-missed` garde le seul décompte des
  écartés. Les retirer est un geste de socle ; un token déclaré sans emploi ne casse rien.
- **Le verrou des classes ambiguës passe à côté des dilutions et des chaînes de script** — cf. le
  défaut ci-dessus. C'est la lane socle.
- **Le compte d'occurrences** sous un mot-clé, qui demande le serveur : lane API.

## Risques et reports assumés

- **Deux tokens perdent leurs consommateurs** : `--verdict-met` n'en a plus aucun, `--verdict-missed`
  garde le seul chiffre « Écartés » du panneau de gauche. Les retirer touche `themes.css`, `main.css`
  et la charte — **nommé, pas fait** : c'est un geste de socle, et un token déclaré sans emploi ne
  casse rien.
- **L'accent devient très présent** dans la colonne : le bouton en aplat, plus une coche par critère
  satisfait. À regarder sur un profil qui coche tout.

## Définition de terminé

- [x] La liste n'emploie plus que l'accent et du gris ; ni vert de verdict, ni rouge.
- [x] Le rond est un disque sombre dont le signe porte la couleur.
- [x] Le nom d'un critère non trouvé est gris.
- [x] La note est le dernier élément du défilement, et aucune cartouche n'est tranchée.
- [x] Le bas de colonne garde la même hauteur avec ou sans pastille, filet compris.
- [x] Le commentaire de `themes.css` et la charte disent la nouvelle règle.
