# TASK WEB-009 — le bloc de marque suit l'axe de son conteneur

- **Créée le** : 2026-08-02.
- **Session** : `60245012-7ab9-4ed0-9148-8737388c8a2d`.
- **Statut** : ✅ fait le 2026-08-02 — le bloc de marque prend son agencement de son conteneur :
  aligné dans la topbar du cockpit, empilé partout ailleurs. Mesuré au navigateur dans cinq
  contextes, en RTL et dans les trois langues ; gates au passage PR.
- **Type** : feat — une prop d'orientation sur le bloc de marque partagé.
- **Tests** : `sans objet` — le diff est une prop qui commute deux jeux d'utilitaires : ni calcul, ni
  service, ni donnée. La suite vitest du webapp couvre le code pur (`shared/`, `services/`, `utils/`
  d'un espace), pas le rendu : [vitest.config.ts](../../webapp/vitest.config.ts) ne déclare aucun
  environnement DOM et pose le critère — « tant qu'il tient, `@nuxt/test-utils` reste inutile ».
  Précédent `WEB-007`, même nature de diff. Preuve par mesure des boîtes au navigateur, sur les deux
  variantes et en RTL.
- **Réf** : `WEB-001` **amont** — c'est elle qui a posé le badge sous le logo dans les quatre
  espaces, et c'est sa règle que celle-ci remplace · `WEB-007` — le décalage `1,2732`, qui devient
  conditionnel · `WEB-005` — le fer à gauche du bloc ·
  [Brand.vue](../../webapp/base/app/components/layout/Brand.vue) et ses trois appelants :
  [cockpit.vue](../../webapp/spaces/entreprise/app/layouts/cockpit.vue),
  [AdminSidebarNav.vue](../../webapp/spaces/admin/app/components/AdminSidebarNav.vue),
  [AppHeader.vue](../../webapp/base/app/components/layout/AppHeader.vue).

## Contexte

`LayoutBrand` n'a qu'un agencement — le logo, puis le badge d'espace en dessous — pour trois
conteneurs d'axes différents.

Empilé, il est juste dans le rail de l'admin, vertical et étroit. Dans la topbar du cockpit
entreprise, qui est une bande horizontale, il coûte : 24 px de logo, 6 de gouttière et 20 de badge
font un bloc de 50 px qui fixe à lui seul la hauteur du header, dans un gabarit qui descend à
54 px — deux pixels de respiration en haut et en bas. Et le décalage sous le « C » laisse le picto
seul au-dessus d'un vide, ce qui donne au bloc un profil en escalier que la verticale ne montre pas.

`WEB-001` avait tranché l'inverse — badge dessous, partout, « pour la cohérence inter-espaces ».
Cette carte abandonne cette uniformité : ce n'est pas l'espace qui commande l'agencement, c'est
l'axe du conteneur qui l'accueille.

## Périmètre

- **Une prop `orientation: 'horizontal' | 'vertical'` sur `Brand.vue`, requise et sans valeur par
  défaut.** Chaque appelant déclare son axe.
- **Le décalage `ml-[calc(var(--logo-h)*1.2732)]` devient conditionnel** : il n'existe qu'en
  `vertical`, où il aligne la boîte du badge sous le « C ». En `horizontal`, le badge est un voisin
  de flex ordinaire, séparé par une gouttière et centré sur la hauteur du logo.
- **Les axes** : `horizontal` pour la topbar du cockpit entreprise ; `vertical` pour le rail admin
  et pour `AppHeader`, qui sert le public et le candidat — ces deux espaces n'ont pas de layout
  propre et retombent sur `base/app/layouts/default.vue`.
- **Un commentaire porte la règle** — l'axe du conteneur décide — et **circonscrit celui de
  `WEB-007`** : l'exception `ml` plutôt que `ms` ne vaut plus que pour la variante verticale.

## Hors périmètre

Trois sujets nommés pendant le cadrage, aucun repris ici :

- **La couleur du badge.** `UiBadge` est `bg-accent-soft` + `text-accent`, le langage des chips de
  critères et des actions, pour un libellé qui n'est ni cliquable ni changeant. Le corriger touche
  un composant générique qui sert ailleurs, ou impose une variante : décision propre, carte propre.
- **La hauteur du header cockpit.** Cette carte rend possible un `clamp` plus bas ; elle ne le prend
  pas. Combien de place la Visionneuse récupère est un arbitrage de la coque entreprise — lane
  `ENT`, pas le socle.
- **Le contenu du badge.** Nommer l'organisation courante plutôt que l'espace ferait entrer une
  donnée métier dans un composant que le README du dossier dit passif, et dépend d'une donnée
  d'API. À recadrer côté entreprise si le besoin tient.

Ni le dessin du logo, ni `UiBadge` — rayon, remplissage, typographie —, ni les appelants de
`LayoutLogo` qui n'affichent pas de badge : rideau de boot, vitrine d'authentification, layout
d'inscription entreprise.

## Décisions de cadrage

- **Une prop, pas deux composants.** Ce qui varie est l'agencement de deux enfants ; ce qui ne varie
  pas est le lien lui-même — `NuxtLink` vers `localePath('/')`, `aria-label`, `aria-current`, anneau
  de focus, variable `--logo-h`. C'est la moitié coûteuse, et un dédoublement la condamne à
  diverger : un correctif d'accessibilité sur l'un ne suivrait pas sur l'autre. Un troisième
  conteneur — rail réduit, tiroir mobile — demanderait un troisième fichier.
- **L'axe du conteneur, pas l'espace.** `Brand.vue` lit déjà `useSpace()` : une table
  espace → agencement était à portée de main. Mais l'admin est empilé parce que son rail est
  vertical, pas parce que c'est l'admin — et le même composant sert son tiroir mobile. Indexer sur
  l'espace, c'est cacher une décision de mise en page dans le composant partagé, sur la mauvaise
  clé.
- **`orientation`, valeurs `horizontal` / `vertical`.** Le nom et les valeurs qu'emploie déjà
  `UNavigationMenu` deux lignes plus bas dans le rail admin. `layout` est pris par Nuxt, `variant`
  ne dit pas de quoi.
- **Requise, sans défaut.** Trois appelants, tous connus. Un défaut aurait obligé à désigner lequel
  des deux cas est « normal », et le camp minoritaire aurait changé d'allure sans qu'on l'ait
  décidé. Requise, elle rend surtout la tâche **atomique** : elle ne peut pas se découper en un
  commit socle puis un commit espace, puisque la compilation casserait entre les deux.
- **Public et candidat gardent l'empilement.** Leur conteneur est pourtant horizontal, et la règle
  leur donnerait l'autre variante. Mais leurs lanes n'ont rien demandé : la carte s'en tient au diff
  minimal et leur laisse la décision.

## Règles et cas limites

- **RTL.** En `horizontal`, l'écart est une gouttière de flex, agnostique au sens de lecture : en
  arabe, le badge passe naturellement à gauche du logo. L'exception documentée par `WEB-007` —
  marge physique et non logique, parce qu'un tracé SVG ne se mirroite pas — reste vraie, mais
  **seulement** pour la variante verticale. Le commentaire doit le circonscrire, sinon il devient
  faux.
- **Longueur du libellé.** En `horizontal`, le badge partage sa ligne avec la topbar — solde,
  notifications, compte. « فضاء الشركة » et la traduction anglaise n'ont pas la largeur
  d'« Espace entreprise » : vérifier qu'aucune des trois langues ne pousse la topbar ni ne fait
  passer le badge à la ligne.
- **Alignement vertical.** En `horizontal`, le badge se centre sur la hauteur du logo et non sur la
  ligne de base du mot-symbole : le tracé est verrouillé, sa ligne de base n'est pas exposée au CSS.
- **Aucun autre appelant.** Trois exactement, confirmés par recherche. Un quatrième apparu
  entre-temps ne compilerait pas — c'est l'effet recherché de la prop requise, pas un accident à
  rattraper par un défaut.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : la charte fixe l'usage du logo et du badge, pas la géométrie du
verrouillage, et aucune page ne décrit ce bloc. Le pourquoi vit dans `Brand.vue`, au contact du
code, comme le fait déjà `Logo.vue` pour le verrouillage du tracé. La règle n'est **pas** promue en
convention dans `base/app/components/README.md` tant qu'un seul composant la porte : une convention
de dossier tirée d'un cas unique s'appliquerait à des situations qu'on n'a pas examinées.

## Livré

- **`Brand.vue`** — une prop `orientation` requise, une table `AXIS` qui associe à chaque axe les
  utilitaires de sa racine et ceux de son badge, et un `computed` qui la lit. Le décalage `1,2732`
  quitte le gabarit statique : il n'appartient plus qu'à `vertical`. Deux commentaires portent le
  pourquoi — la règle de l'axe, et la **restriction** de l'exception `ml`/`ms` à la seule verticale,
  sans laquelle le commentaire hérité de `WEB-007` serait devenu faux.
- **`cockpit.vue`** — `orientation="horizontal"`, le seul appelant qui change d'allure.
- **`AdminSidebarNav.vue`** et **`AppHeader.vue`** — `orientation="vertical"`, qui reconduit
  exactement le rendu existant pour l'admin, le public et le candidat.

## Vérification

**La géométrie**, mesurée au navigateur sur la stack du worktree (`showcase-recruiter-webapp-saas-socle-webapp`, `:8680`),
dont la base a été amorcée pour l'occasion :

| Contexte | `flex-direction` | marge du badge | bloc | contrôle |
|---|---|---|---|---|
| Cockpit entreprise | `row` | `0px` | **24 px** | badge à droite, écart 9,11 px ; centres verticaux à **0,00** |
| Cockpit, `/ar` | `row` | `0px` | 24 px | badge passé **à gauche** du logo ; centres à **0,00** |
| Rail admin | `column` | `30,5568px` | 50 px | « C » à 57,28 — badge à 57,27, **écart −0,01** |
| Header public | `column` | `30,5568px` | 50 px | « C » à 126,56 — badge à 126,55, **écart −0,01** |
| Header candidat | `column` | `30,5568px` | 50 px | **écart −0,01** |

Le bloc passe de 50 à 24 px dans le cockpit et reste à 50 partout ailleurs. Les trois variantes
verticales retombent au centième sur le critère de `WEB-007` — c'est ce qui prouve qu'elles n'ont
pas bougé, plutôt qu'une comparaison de captures.

**Les trois langues**, dans le cockpit : « Espace entreprise » 123,73 px · « Company area »
102,48 px · « فضاء الشركة » 84,75 px. Le jour avec la topbar va de 856 à 895 px — aucun contact,
aucun retour à la ligne. Le rendu français s'obtient à la racine et non sur `/fr` : le préfixe de la
langue par défaut est nu, et `/fr` tombe sur la 404, qui rend l'autre variante.

**RTL**, sur `/ar` : le bloc bascule à droite et le badge se place à gauche du mot-symbole, sans
qu'aucune marge n'intervienne — `gap` suit déjà le sens de lecture. C'est le comportement prévu au
cadrage.

**Non vérifié** : les gates (conventions, lint, format, type-check des quatre espaces) — au passage
PR, comme le veut le cycle.

## Reste à faire (hors périmètre)

Les trois renvois du cadrage, aucun ouvert : la couleur du badge, le `clamp` du header cockpit
(lane `ENT`, qui décide seule de la place rendue à la Visionneuse), le badge portant le nom de
l'organisation.

## Définition de terminé

- [x] `Brand.vue` expose `orientation`, requise, deux valeurs, sans défaut.
- [x] Le décalage `1,2732` n'existe qu'en `vertical`.
- [x] Les trois appelants déclarent leur axe, et aucun quatrième n'existe.
- [x] Cockpit entreprise : badge à droite du logo, sur une ligne, centré sur sa hauteur — mesuré au
      navigateur.
- [x] Rail admin, header public, header candidat : rendu identique à avant, mesuré et non supposé.
- [x] RTL vérifié sur la variante horizontale, et le commentaire circonscrit l'exception `ml` à la
      verticale.
- [x] Le libellé « Espace entreprise » tient sur la ligne du cockpit dans les trois langues.
- [ ] Conventions, lint, format, type-check des 4 espaces — **au passage PR**.
