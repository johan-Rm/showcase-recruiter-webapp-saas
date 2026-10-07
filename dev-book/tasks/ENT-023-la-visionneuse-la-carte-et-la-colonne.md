# TASK ENT-023 — entreprise : la visionneuse, la carte et la colonne

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Frontend espace entreprise — design.
- **Tests** : sans objet — la suite front est volontairement limitée au code PUR
  (`webapp/vitest.config.ts`) ; cette carte ne touche que la présentation de deux composants, et la
  grille de faits n'a demandé aucune fonction de formatage à extraire dans `services/`.
- **Réf** : `ENT-022` (les paliers qu'elle consomme — **amont obligatoire**) ·
  `ui-ux-design/draft/mockup-espace-entreprise-vue-cockpit-v23.html` · `ENT-006` (la carte comme
  résumé de décision) · `ENT-007` (la colonne de droite) · `ENT-013` (le dévoilement comme geste)

## Contexte

Le mockup v23 tient en cinq dispositifs de mise en page. Trois concernent l'écran de parcours, et
aucun n'y est appliqué.

**Les faits vivent dans des encadrés, pas dans une phrase.** Notre carte dit la situation du
candidat en prose — « 11 ans d'expérience · Lyon, France » —, le mockup la donne en grille. La
phrase se lit, la grille se balaie ; sur un écran où l'on tranche cent profils à la suite, ce n'est
pas la même fatigue.

**Le rang est un chiffre, pas une mention.** La colonne affiche `3 / 1076` en petit texte sourd, à
côté du nom de la recherche. Le mockup en fait le repère principal du panneau, avec un filet et une
ligne de compteurs qui racontent l'avancement sans phrase.

**Trois familles d'objets ont le même dessin.** « Rencontré », « Casablanca » et « Symfony » sont
aujourd'hui la même pastille à trois endroits de la carte : un état du profil, un critère satisfait
et une compétence. Rien ne dit qu'ils ne se lisent pas de la même façon.

## Périmètre

`SearchProfileCard` et la colonne de droite de `recherches/[uuid]/index.vue`.

**Rien d'autre que la présentation** — sauf les trois compteurs, qui demandent un décompte, et la
grille de faits, qui change la condition d'affichage du niveau d'études.

## Hors périmètre

- **La table** — `ENT-024`.
- **Les paliers typographiques et le solde** — `ENT-022`.
- **Les onglets de la carte** du mockup (Profil · Situation & langues · Parcours · Maroc & dossier) :
  la carte est un résumé de décision, pas une fiche à ranger (`ENT-006`).
- **La colonne invisible sous `lg`** : elle est en `hidden lg:flex`, donc sur mobile et tablette
  l'écran de parcours n'a ni rang, ni progression, ni favoris. C'est un manquement au mobile-first
  du socle, et il mérite sa carte — le corriger en même temps qu'une passe graphique masquerait
  l'un dans l'autre.
- **La palette et les fontes du mockup** (sable, Exo 2 / Manrope) : la marque est Cèdre, en Sora et
  Geist. Le mockup donne des dispositifs de mise en page, pas une charte.

## Décisions de cadrage

### La grille de faits ne peut pas être celle du mockup

Le mockup pose `POSTE ACTUEL · ENTREPRISE · DEPUIS · SECTEUR`. **Trois de ces quatre faits n'existent
pas dans notre contrat** : `CandidateCard` n'a ni employeur courant, ni date d'entrée en poste, ni
secteur d'activité — seulement `headline`, et `desiredSector` / `desiredFunction` qui sont des
souhaits, pas la situation présente.

On garde donc le **dispositif** et non son contenu. La grille porte
`EXPÉRIENCE · LOCALISATION · DISPONIBILITÉ · ÉTUDES`, et remplace la ligne en prose de l'en-tête.

### Les quatre cases sont toujours là

Une case absente ferait de la grille une rangée de largeur variable, différente d'une carte à
l'autre — donc plus une grille, et plus rien à balayer. Une donnée manquante affiche un tiret.

**Conséquence assumée** : le niveau d'études cesse d'être conditionné à la recherche qui
l'interroge. Il occupait jusqu'ici sa place à cette seule condition ; il l'occupe désormais toujours,
vide s'il le faut.

### Les trois accès gardent le même poids

Trois achats distincts, un crédit chacun — rien n'est groupé. Ils se rangent sur **deux lignes** :
les coordonnées seules, le CV et le compte rendu dessous en corps réduit. La préséance se dit par la
place, pas par la couleur.

Conséquence à connaître : **la carte n'a plus d'aplat accent**. L'unique action pleine de l'écran
devient le pied de la colonne.

### Les compétences deviennent des mots-dièse

`#Symfony`, `#API` — plus léger qu'une pastille pleine, et surtout distinct. Trois familles, trois
dessins : badge d'état, badge de critère satisfait, mot-dièse. Les compétences qu'un mot-clé a
trouvées gardent l'accent.

Chaque mot-dièse est un **îlot de direction** : sans cela, dans une page en arabe, le dièse saute à
l'autre bout du mot. Les noms de technologies ne se traduisent pas et restent latins dans les trois
langues, le cas est donc systématique, pas marginal.

### Les compteurs disent sur quoi ils comptent

`VUS · FAVORIS · DÉVOILÉS` se calculent sur le **jeu chargé** : `unseenCount` filtre `cards`, la
page, pas le jeu entier. Posés tels quels sous un rang qui affiche `/ 1076`, ils se liraient comme
des compteurs sur 1076.

Ils portent donc une mention de portée. Les favoris échappent au problème — ils sont servis par leur
propre périmètre et valent sur le jeu entier.

Le défaut préexiste sur « Reprendre · N à voir » ; le dispositif du mockup le rend seulement plus
visible.

### Les deux gestes de la ligne fine descendent

Le mockup met trois compteurs **inertes** là où nous avons deux **actions** — « Reprendre · N à
voir » et « N écartés ». Elles ne disparaissent pas : elles passent sous les compteurs, en
boutons-liens. Une ligne de plus, mais un chiffre qui agit sans se signaler comme un bouton est un
geste perdu.

### Le pied devient le bord de la colonne

« Ma sélection » cesse d'être un bouton dans une marge : aplat accent, pleine largeur, collé au bord
bas de la carte. C'est le seul aplat accent de l'écran, et il désigne la sortie de séance — la carte
décide d'un profil, la colonne clôt le travail.

## Règles et cas limites

- **La carte tient dans un plein écran et ne scrolle pas** : le défilement de la pile est le seul
  défilement. La grille de quatre est une rangée, jamais deux.
- Un accès **déjà acquis** reste un état et non un bouton — l'API ne refacture pas, un bouton actif
  laisserait croire à une seconde dépense.
- Les badges ne nomment que des attributs **acquis** : « Non rencontré » n'existe pas.
- La liste des favoris ne montre pas **qui**, mais de quoi reconnaître **sa décision** — un favori
  peut être masqué, et douze noms masqués sont douze lignes identiques.
- La croix de retrait révélée au survol doit rester atteignable **au clavier** : la révélation est
  visuelle, pas conditionnelle.
- Les trois compteurs à zéro s'affichent quand même : c'est un début de séance, pas une absence.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : les tokens et leur documentation appartiennent à `ENT-022`, et
cette carte ne fait que les consommer.

## Définition de terminé

- [x] La grille de quatre encadrés remplace la ligne en prose de l'en-tête, tiret compris.
- [x] Les libellés de section consomment le palier posé par `ENT-022`.
- [x] Les compétences sont des mots-dièse, isolés en direction, accent sur celles qu'un mot-clé a
      trouvées.
- [x] Les trois accès sont rangés sur deux lignes, à poids égal, coût affiché sur chacun.
- [x] La colonne porte le grand rang, son filet, les trois compteurs et leur mention de portée.
- [x] « Reprendre » et « Écartés » sont sous les compteurs.
- [x] Les favoris sont en lignes à pastille, retrait atteignable au clavier.
- [x] Le pied « Ma sélection » est un aplat accent collé au bord.
- [x] Aucun comportement modifié hors des deux points annoncés : les compteurs et le niveau
      d'études.
- [ ] L'écran constaté en thème sombre — **non fait**, cf. ci-dessous.

## Vérifications

Constaté sur le rendu réel, connecté avec un compte de recette, sur une recherche de 256 profils :

- la grille sort ses quatre cases — « Expérience 1 an · Localisation — · Disponibilité — · Études
  Master » —, le tiret doublé de « Non renseigné » pour les lecteurs d'écran ;
- le rang affiche `1 / 256` en grand, doublé de « Profil 1 sur 256 » ;
- les compteurs sortent `Vus 0 · Favoris 0 · Dévoilés 0`, suivis de leur mention de portée ;
- les compétences sortent en `#Big Data`, `#Ansi Sql`… dans 232 éléments `bdi` ;
- les accès sortent sur deux lignes, la première au corps normal, les deux autres réduits, chacun
  avec son coût ;
- le pied est rendu en aplat accent, pleine largeur, sans rayon.

**En arabe** (`/ar/recherches/…`) : `dir="rtl"`, libellés de la grille et des compteurs traduits,
mots-dièse toujours dans leurs îlots de direction.

Reste à constater **dans un navigateur** : le thème sombre, et l'aspect général des dispositifs —
le rendu HTML dit la structure, pas l'allure.

Note sur la donnée : `availability` est nul sur la quasi-totalité du vivier (3 candidats sur 2561),
donc la case « Disponibilité » affiche un tiret presque partout. C'est le comportement prévu, mais
la grille perdra une case tant que cette donnée ne sera pas alimentée.
