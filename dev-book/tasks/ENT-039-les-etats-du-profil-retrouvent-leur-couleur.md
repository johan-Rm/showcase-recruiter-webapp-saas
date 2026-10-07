# TASK ENT-039 — entreprise : les états du profil retrouvent leur couleur et une source unique

- **Créée le** : 2026-08-03.
- **Session** : `cb761d33-c5ed-45b5-a3db-3340b185010f`.
- **Statut** : ✅ fait le 2026-08-03 — les cinq états portent leur teinte, la fiche retenue se borde
  d'accent, et une seule fonction les produit pour les trois surfaces.
- **Type** : Frontend espace entreprise — direction artistique appliquée, et la couche pure qu'elle
  rend nécessaire.
- **Tests** : la liste des états d'un profil devient une **fonction pure** de `services/search/`,
  où la suite front la ramasse — chaque condition, l'ordre, et le cas du profil qui n'en porte aucun.
  C'est le genre d'inversion — « Disponible » allumé sur un profil en préavis — qu'un test attrape en
  une ligne et qu'une capture ne montre pas. S'y ajoutent des captures dans les deux thèmes, sur un
  profil qui porte les cinq états et un qui n'en porte aucun.
- **Réf** : `ENT-038` (le panneau, même chorégraphie : couche pure puis surfaces) · `ENT-029` (les
  cinq badges, dont le contenu était alors hors périmètre) · `ENT-023` (« un badge nomme un attribut
  acquis ») · **`API-022`** (la sémantique de `isNew`, sans effet sur cette carte) ·
  `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` — mockup témoin ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md)

## Contexte

Cinq badges disent l'état d'un profil — Rencontré, Nouveau, Au Maroc, Disponible, Favori. Ils
fonctionnent, mais trois choses ne vont pas.

**Ils sont écrits en dur dans la card.** La Table réaffiche « Rencontré » à sa main, dans sa propre
cellule ; le panneau de profil n'en affiche aucun. Trois surfaces montrent le même profil et le
décrivent différemment — le cadrage annonçait une « source unique », elle n'a jamais existé.

**Ils ont perdu leur couleur.** La direction artistique donne à chaque état une teinte propre. Le
code n'en accentue qu'un et neutralise les quatre autres, avec un argument recevable — cinq aplats
accentués ne hiérarchisent rien. Mais le mockup témoin ne propose pas cinq aplats : il propose un
**nuancier d'états** et une recette de rendu qui les rend lisibles **et** discrets. C'est cette
recette qui manquait, pas la retenue.

**Et le nuancier ne les a pas.** La charte compte cinq rampes, dont deux neutres, et une seule
couleur porteuse — `--alert`, explicitement réservée au feedback bloquant. Aucun token d'état
n'existe. `UiBadge` l'avait pourtant prévu : *« Les teintes du nuancier (vues du Vivier)
s'ajouteront ici quand la Table les utilisera. »*

## Périmètre

- **Le nuancier d'états** dans `palette.css` / `themes.css` : cinq tokens, communs aux deux thèmes.
- **`UiBadge`** reçoit sa teinte d'état et la recette du mockup — c'est le kit maison qui porte cette
  brique, pas un composant d'écran.
- **`services/search/states.ts`** : la liste des états d'un profil, en fonction pure, avec ses tests.
- **`ProfileCard`** consomme la fonction et pose la **bordure accent** quand le profil est retenu.
- **La Table** consomme la même fonction pour sa cellule.
- **Les clés i18n** existantes suffisent ; aucune n'est ajoutée.
- **La charte** — `brand-guidelines.md` gagne sa section « Couleurs d'états », et la règle qui
  l'interdisait est précisée.
- **Le cadrage client** — `vue_la_visionneuse.md §4.1` et `inspecteur.md §2bis`.

## Hors périmètre

- **Le panneau de profil** (`ProfilePanel.vue`), coquille de 73 lignes sans contenu de profil : lui
  faire consommer la source unique suppose d'abord qu'il affiche un profil.
- **La place des badges**, qui reste en signature au bas de la colonne — tranché, sans importance
  pour l'instant.
- **La sémantique de `isNew`**, qui change côté serveur (`API-022`) sans que cette carte y touche :
  la card lit un booléen, elle ne sait pas d'où il vient.
- **Les infobulles** du mockup : le libellé est déjà en toutes lettres, et un `title` sur un `span`
  non focusable ne sert ni le clavier ni le lecteur d'écran. On n'ajoute pas une aide que la moitié
  des usages n'atteint pas.
- **Les couleurs des vues** de la Table (`Tous`, `Favoris`) : les sept vues sont devenues deux, ce
  qui reste ne réclame pas de teinte.

## Décisions de cadrage

### Le backend donne les faits, le front décide des badges

`isStar`, `isNew`, `isInMorocco`, `availability` et `isFavorite` descendent tous du serveur : la
source de vérité est déjà unique, et personne ne l'invente côté client.

Ce qui n'est pas partagé, c'est leur **traduction** — libellé, icône, couleur, ordre, et la
dérivation `availability === 'IMMEDIATE'`. Le faire produire par l'API reviendrait à y mettre des
clés i18n et des noms de couleur : l'API imposerait l'apparence, et changer la charte deviendrait
une migration serveur.

La source unique est donc une fonction pure de `services/search/`, comme `describeCriterion` l'est
pour les critères. Testable, et consommée par toutes les surfaces qui montrent un profil.

### Cinq états, cinq teintes, et l'accent revient au favori

| État | Teinte | Pourquoi |
| --- | --- | --- |
| **Favori** | `--accent` | l'accent de marque : c'est la décision du recruteur, le seul état qu'il produit |
| **Rencontré** | olive `#b2b760` | la garantie showcase-recruiter-webapp-saas, constatée en entretien |
| **Disponible** | vert `#4fb87e` | le feu vert : la personne peut y aller |
| **Nouveau** | rose `#e0699f` | seule famille chaude libre, ne se confond ni avec l'ambre ni avec le rouge |
| **Au Maroc** | rouge propre, à caler | le drapeau, le différenciant du produit |

Les quatre teintes fixes viennent du mockup témoin, qui les a choisies sur des **hues libres** pour
qu'elles restent distinguables entre elles. Le favori s'en écarte : il prend l'accent plutôt que
l'ambre du mockup, parce que c'est le seul état qui est un **geste** et non un constat.

### Un nuancier commun aux deux thèmes, rendu lisible par le mélange

Les teintes d'état ne changent **pas** avec le thème, contrairement à l'accent. C'est ce qui les
rend reconnaissables : le rose de « Nouveau » est le même en clair et en sombre.

Ce qui s'adapte, c'est la **recette de rendu** — le texte est un mélange de la teinte et de l'encre
de la page, laquelle change de thème. Le contraste suit donc automatiquement, sans nuancier double.

### La recette du mockup, à l'unité près

C'est elle qui fait la finesse, et elle ne s'improvise pas :

| Propriété | Valeur |
| --- | --- |
| Hauteur | `h-6` — fixe, pour que la rangée s'aligne |
| Corps | `11px`, `font-bold`, `leading-none` |
| Rayon · marge | `rounded-lg` · `px-2`, `gap-1` |
| Fond | la teinte à **20 %** sur transparent |
| Texte | la teinte **mélangée à 62 %** avec `--text` |
| Icône | `12px`, teinte **pure** |
| Bordure | aucune |

Un aplat léger, un texte assombri par mélange, une icône franche : c'est ce qui rend cinq badges
colorés lisibles côte à côte là où cinq aplats saturés crieraient. `UiBadge` en est proche — fond à
14 %, texte en teinte pure, corps 12 px, hauteur libre — et doit être calé dessus.

### Le rouge du Maroc n'est pas le rouge d'alerte

Le mockup pose `--morocco: #f25558`, exactement la valeur d'`--alert`. La charte réserve celle-ci au
**feedback bloquant** et interdit qu'elle code une donnée.

« Au Maroc » reçoit donc **son propre token**, dans la même famille rouge mais distinct à l'œil.
Deux rôles, deux valeurs : une erreur de solde ne doit pas se confondre avec un profil présent au
Maroc, et les deux peuvent apparaître sur le même écran. La valeur exacte se cale en capture, dans
les deux thèmes ; `#d94a4d` sert de point de départ.

### Le favori est dit trois fois, et c'est voulu

Le bouton « Retenu » en accent, le badge « Favori » en accent, et la **bordure accent de la card**.

Trois signaux pour un même fait. C'est une redondance **délibérée** : sur un écran où l'on tranche
cent profils à la suite, ce qu'on a retenu doit se repérer sans lire — la bordure le dit de loin, le
badge dans la rangée, le bouton sous la main. La règle « un badge ne double pas un geste » cède ici
devant l'usage réel.

### La charte doit acter ce qu'elle interdisait

`brand-guidelines.md` écrit que « la couleur appartient à l'accent et aux états, elle ne code jamais
une donnée répétée sur chaque ligne » — et cette carte pose exactement cinq couleurs de donnée.

La règle visait les **colonnes de tableau**, où une teinte par ligne devient du bruit. Elle est
précisée, pas supprimée : la couleur code un **état**, jamais une donnée. La distinction s'écrit dans
la charte, sans quoi la prochaine carte relira l'interdiction et refera le débat.

## Règles et cas limites

- **Un badge nomme un attribut acquis**, jamais son absence : « Non rencontré » n'existe pas, et la
  rangée disparaît quand le profil ne porte aucun état.
- **L'ordre est fixe**, celui du mockup : Rencontré · Favori · Disponible · Nouveau · Au Maroc. Un
  ordre qui varie avec les états présents fait sauter la lecture d'une card à l'autre.
- **La couleur ne porte jamais seule le sens** : chaque badge garde son libellé en toutes lettres et
  son icône. Un daltonien lit la même chose que les autres.
- **La bordure de card ne remplace pas le badge** et ne change pas la hauteur de la card : elle se
  pose sans décaler le contenu.
- **Recherche partagée** (`editable` faux) : les badges sont identiques, seuls les gestes
  disparaissent. La bordure reste — elle dit un état de l'équipe, pas un droit d'écriture.
- **Aucun texte en dur**, et les cinq libellés existent déjà dans les trois langues.
- **La fonction est pure** : elle reçoit une card et rend une liste, sans traduire — le composant
  traduit, comme pour `describeCriterion`.

## Documentation impactée

- **[brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)** — section
  « Couleurs d'états » : les cinq tokens, la recette de rendu, et la précision de la règle sur les
  pilules de données.
- **[design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)** — `UiBadge` cesse
  d'être un badge accent unique.
- **[vue_la_visionneuse.md §4.1](../cadrage/entreprise/ui/vue_la_visionneuse.md)** et
  **[inspecteur.md §2bis](../cadrage/entreprise/ui/inspecteur.md)** — six badges deviennent cinq
  (« Shortlist » n'a jamais existé), « Vérifié » devient « Rencontré », et les couleurs annoncées
  s'alignent sur celles qui sont posées. Pages **écrites pour le client** : aucune note, aucune
  date, aucune mention de ce qui n'existe plus.
- **Vérifié, non impacté** : `services-standard.md` — la carte applique sa règle plutôt que de la
  changer, et la page n'inventorie pas les services.

## Définition de terminé

- [x] Les cinq tokens existent — quatre valeurs fixes plus `--accent` pour le favori —, aucun ne
      vaut `--alert`, et aucun n'est redéclaré par thème.
- [x] `UiBadge` rend la recette du mockup, mesurée dans le DOM : fond à **20 %**, texte mélangé,
      icône pure, hauteur fixe.
- [x] Une seule fonction produit la liste ; ni la carte, ni la Table ne la recompose.
- [x] Ses tests couvrent chaque condition, l'ordre fixe, le profil sans aucun état, et la
      disponibilité dérivée.
- [x] Un profil retenu porte une bordure accent **intérieure** — donc sans décalage du contenu.
- [x] Captures dans les deux thèmes : un profil aux cinq états, un profil sans aucun.
- [x] Les deux rouges comparés à l'écran, **avec une réserve** : nettement distincts en aplat, très
      proches en badge à 20 %. Voir *Vérification*.
- [x] La charte porte « Couleurs d'états » et la recette du badge d'état ; les deux pages de cadrage
      décrivent cinq badges.

## Vérification

**9 tests verts** sur `states.vitest.ts` — sortie lue le 2026-08-03. Le plus utile tient l'inversion
qu'aucune capture ne montrerait sans le bon profil sous la main : un profil en préavis de trois mois
ne porte pas « Disponible ». Deux autres tiennent l'ordre fixe, y compris sur un sous-ensemble.

**Le rendu, mesuré et non jugé à l'œil.** Sur la carte de `Zakaria Jougha`, fabriqué en base pour
porter les cinq états :

| Constat | Valeur lue |
| --- | --- |
| Ordre affiché | Rencontré · Favori · Disponible · Nouveau · Au Maroc |
| Fond de chaque badge | teinte à `0.2` d'alpha |
| Bordure de la carte retenue | `rgb(15, 75, 55)` — accent — `2px inset` |

**Le thème sombre marche sans nuancier double** : la teinte ne bouge pas, mais l'encre d'« Au Maroc »
passe de `srgb(0.54, 0.20, 0.20)` en clair à `srgb(0.91, 0.56, 0.57)` en sombre. C'est le mélange
avec `--text` qui adapte le contraste tout seul — la preuve que la recette porte l'adaptation, pas
la couleur.

**Un profil sans aucun état** ne montre ni badge, ni bordure, ni hauteur réservée. Il a fallu le
vieillir de trente jours en base pour l'obtenir : depuis `API-022`, tous les profils de cette base
sont « nouveaux ».

**La Table suit la source unique** : son badge « Rencontré » sort avec la même teinte olive à 20 %
que celui de la carte. Le badge « Espace entreprise » de l'en-tête garde, lui, son rendu d'origine à
14 % — les deux objets cohabitent dans la même brique sans se contaminer.

**Réserve sur le rouge, présentée et assumée.** `--state-morocco` (`#d94a4d`) et `--alert`
(`#f25558`) se distinguent nettement en aplat plein, mais **très peu en badge à 20 %**. La séparation
des deux rôles est acquise dans le code et dans la charte ; sa lisibilité à l'écran reste faible, et
la teinte pourra être poussée vers une nuance plus sourde sans rien changer d'autre qu'une valeur.

## Livré

- `webapp/services/search/states.ts` **(+)** et son `.vitest.ts` **(+)** — la source unique.
- `webapp/base/app/assets/css/themes.css` **(M)** — quatre tokens d'états, hors nuancier.
- `webapp/base/app/components/ui/Badge.vue` **(M)** — deux objets, une brique : contexte sans
  teinte, état avec.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` **(M)** — consomme la source, et
  se borde quand le profil est retenu.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — même source pour sa
  cellule.
- `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` **(M)** — « Couleurs d'états », la recette du
  badge d'état, et la règle des pilules précisée.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` **(M)** — le badge du kit porte deux objets.
- `dev-book/cadrage/entreprise/ui/{vue_la_visionneuse,inspecteur}.md` **(M)** — cinq badges,
  « Rencontré », couleurs, ordre fixe et bordure.

## Écart au cadrage

### La Table consomme la source unique sans afficher tous les états

Le périmètre disait « la Table consomme la même fonction pour sa cellule ». C'est fait — mais elle
n'en affiche **qu'un**, « Rencontré », comme avant. Ses dix colonnes se disputent déjà la largeur, et
y verser cinq badges par ligne ferait sortir la moitié de l'écran.

Le bénéfice reste entier : le libellé, l'icône et la teinte viennent du même endroit que ceux de la
carte. Changer la teinte d'un état la change partout, ce qui était l'objectif.

### Le favori n'a pas de token, il emprunte l'accent

La carte annonçait « cinq tokens ». Il y en a **quatre** : le favori porte `--accent` directement.

Lui donner son propre token aurait obligé à le redéclarer dans le thème sombre — `themes.css` impose
que tout token dérivé d'un autre soit redéclaré, sans quoi un îlot sombre hériterait de la valeur
calculée en clair. Un alias qui doit être maintenu en double pour ne rien ajouter n'a pas sa place.

## Aval

- **Le panneau de profil**, qui consommera la source unique le jour où il affichera un profil.
- **Le libellé du compteur « Nouveaux »** d'une recherche, qui dira autre chose que le badge une fois
  `API-022` livrée — l'un parle d'activité depuis ma dernière visite, l'autre de l'âge du profil.
