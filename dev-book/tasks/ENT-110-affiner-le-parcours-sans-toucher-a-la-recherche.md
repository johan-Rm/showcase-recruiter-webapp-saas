# TASK ENT-110 — entreprise : affiner le parcours sans toucher à la recherche

- **Créée le** : 2026-08-13.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-13 — la colonne de la Visionneuse porte six affinages en trois
  volets, avec le compte de chaque option, et le parcours se resserre sans que la recherche bouge.
- **Type** : feat — un affinage de parcours dans la Visionneuse, et la couche pure qui le calcule.
- **Tests** : la sélection des profils, le comptage par option et la reprise de position deviennent
  des **fonctions pures** de `services/search/`, où la suite front les ramasse — chaque filtre pris
  seul, leur combinaison en ET, le comptage facetté (tous les filtres actifs **sauf** celui de
  l'option comptée), le jeu vide, et le profil sur lequel on atterrit. Précédent exact :
  `fromSecondaryForm` / `toSecondaryForm` (`ENT-042`), déjà couverts dans `criteria.vitest.ts`.
  Aucun test de rendu : ce qui se voit se prouve à l'écran, ce qui se calcule se prouve ici.
- **Réf** : **`ENT-063`** — la décision que cette carte renverse · `ENT-042` — le formulaire des
  treize filtres, écrit puis laissé orphelin · `ENT-079` — la pagination de la pile · `ENT-047` — la
  console qui tient dans sa hauteur ·
  [criteres_de_filtrage_profils.md](../cadrage/entreprise/criteres_de_filtrage_profils.md) — la liste
  canonique · `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` — les volets
  repliés et le comptage par option ·
  [services/README.md](../../webapp/services/README.md)

## Contexte

La Visionneuse sert à trancher : on parcourt les profils d'une recherche un par un, on retient, on
écarte. Elle ne propose **aucun moyen de resserrer ce qu'on parcourt**.

Le recruteur qui ouvre une recherche de trente-deux profils et qui veut, là, tout de suite, ne voir
que ceux qui seront à Casablanca dans le mois n'a qu'une issue : retourner sur l'écran de
modification, ajouter le critère, et **perdre son parcours** — ce qu'il a déjà vu, écarté, retenu se
recalcule sur un autre jeu. Le coût est tel qu'il ne le fait pas : il déroule les trente-deux.

Ce sont les **différenciants du produit** qui sont les plus mal servis. La présence physique au
Maroc au bon moment est la promesse de showcase-recruiter-webapp-saas ; le moteur sait filtrer dessus, la carte de profil
porte la donnée, et aucun écran de parcours ne l'offre.

Le formulaire, lui, **existe déjà** : `ENT-042` a écrit `FiltersSecondary.vue` et sa traduction en
critères. `ENT-063` l'a ensuite sorti de la colonne pour une raison juste — la console n'a pas à
modifier la recherche qu'on est en train de parcourir — mais la conséquence est qu'il n'est plus
affiché **nulle part** : un composant de deux cents lignes, testé, orphelin.

## Ce que cette carte renverse, et pourquoi

`recherches/[uuid]/index.vue` porte aujourd'hui cette interdiction, en toutes lettres :

> « PAS D'ENTRÉE VERS LES FILTRES ICI, et ce n'est pas un oubli : cet écran sert à CHOISIR parmi un
> jeu déjà constitué. Changer les critères, c'est TROUVER, et ça se fait sur l'écran de
> modification. Chaque écran fait une chose. »

Le raisonnement tient, et cette carte **ne le contredit pas** : elle en tire la conséquence inverse.
Ce qu'on ajoute n'est pas un moyen de **changer la recherche**, c'est un moyen de **regarder ce
qu'elle a déjà ramené sous un angle plus étroit** — au même titre que les onglets Favoris ou
Dévoilés, qui segmentent sans redéfinir.

D'où le mot, qui n'est pas décoratif : ce sont des **affinages**, pas des critères. Ils ne rejoignent
jamais la recherche, ne changent pas son nom, ne survivent pas au rechargement. L'écran continue de
faire une chose : choisir.

Le commentaire d'interdiction est donc **remplacé**, pas supprimé en silence — il devient l'énoncé
de la frontière : les critères se changent ailleurs, l'angle de lecture se change ici.

## Périmètre

**Six filtres, les différenciants**, dans la colonne de gauche de la Visionneuse :

| Filtre | Ce qu'on choisit | Forme | Donnée de la carte |
| --- | --- | --- | --- |
| Présence au Maroc | Maintenant · < 15 j · < 30 j · < 60 j — **un seul** | pastilles | `presencePeriods[]`, `isInMorocco` |
| Ville du séjour | les villes réellement présentes dans le jeu | liste déroulante | `presencePeriods[].city` |
| Pied-à-terre au Maroc | oui / peu importe | interrupteur | `hasPiedATerre` |
| Horizon de retour | < 3 mois · < 6 mois · < 1 an · en réflexion | pastilles | `returnHorizon` |
| Mobilité | prêt à bouger dans tout le Maroc | interrupteur | `geoMobility` |
| Disponibilité | immédiate · préavis 1 mois · préavis 3 mois | pastilles | `availability` |

**Aucun appel serveur nouveau** : ces six champs sont déjà sur `CandidateCard`.

- **Des volets repliés**, sous les compteurs d'avancement et au-dessus du pied « Ma sélection », qui
  ne bougent ni l'un ni l'autre. Ouverts un par un, comme la maquette v23.
- **Un compteur par option**, calculé sur la pile en mémoire — « Casablanca (4) ». Une option à zéro
  se grise et ne se clique pas.
- **Le chargement de la pile entière** au premier filtre posé, avec un état d'attente visible : sans
  lui, l'affinage ne verrait que les trente premiers profils et cacherait des profils qui
  correspondent.
- **Un retrait en un geste** de tous les affinages, présent en permanence dès qu'au moins un est
  posé.

## Hors périmètre

- **Les sept autres filtres secondaires** — secteur visé, secteur d'expérience, fonction, langue,
  niveau de langue, pays actuel, profil vérifié. La forme éprouvée ici les accueillera ensuite.
- **Les quatre filtres du formulaire classique** — poste, mots-clés, expérience, études. Ce sont les
  critères structurants de la recherche : les affiner en parcourant n'a pas de sens, on les change.
- **L'accès sous 1024 px.** La colonne entière est `hidden lg:block` ; les affinages n'existeront pas
  sur téléphone, comme l'avancement et les écartés aujourd'hui. Nommé en aval, pas oublié.
- **Toute persistance** : ni URL, ni session, ni stockage. Voir *Décisions de cadrage*.
- **`FiltersSecondary.vue`**, laissé en place et intact. Il pose des **critères** ; on écrit ici un
  composant qui pose des **affinages**. Les fusionner demanderait de trancher ce que devient un
  formulaire qui sert deux intentions opposées — c'est une carte à part, pas un effet de bord.
- **Le filtrage côté serveur.** Le jeu de résultats est matérialisé (`SearchResultMembership`) et
  l'API ne sait pas le re-filtrer à la volée. Aucune tâche API n'est ouverte ici : le besoin
  n'existera que si le volume rend le chargement complet intenable.

## Décisions de cadrage

**L'affinage est volatil, et pour de bon.** Il ne touche jamais `search.criteria`, ne déclenche
aucune sauvegarde, ne change pas le nom automatique de la recherche, et disparaît au rechargement.
L'écrire dans l'URL a été écarté : une adresse qui porte des critères redevient une recherche, et la
frontière qu'on vient de tracer s'efface au premier lien partagé.

**On charge tout, puis on filtre.** Filtrer seulement les profils déjà en mémoire aurait été gratuit
et instantané, mais un profil qui correspond et dort en page 3 serait resté invisible — un filtre
qui cache ce qu'on cherche est pire que pas de filtre. Le coût est assumé : sur une grande recherche,
l'attente est réelle, et c'est pour ça qu'elle s'affiche.

**Le rang suit l'affinage, l'avancement non.** « 1 / 7 » décrit ce qu'on parcourt maintenant, et
c'est l'information utile. Mais les compteurs Vus / Favoris / Écartés restent ceux de la recherche
entière : ce qu'on a vu, on l'a vu — un filtre ne doit pas donner l'impression d'avoir reculé.

**On repart du premier profil retenu.** Garder sa place quand le profil courant survit au filtre
aurait été plus doux, mais l'affinage rouvre un parcours : revenir au début le dit sans ambiguïté,
et évite d'avoir à expliquer pourquoi on a parfois bougé et parfois pas.

**Chaque filtre prend la forme de ce qu'il demande.** Pastilles quand les valeurs sont peu nombreuses
et se comparent d'un coup d'œil, interrupteur pour un oui/non, liste déroulante pour la ville, dont
les valeurs viennent du jeu et ne sont pas connues d'avance. Les listes déroulantes uniformes de
`FiltersSecondary` obligeraient à ouvrir six menus pour voir ce qui est offert, dans une colonne de
280 px.

**Les compteurs par option se font ici**, alors que le cadrage les décrit comme une dette API. La
raison est circonstancielle et vaut d'être écrite : une fois la pile entière en mémoire, le comptage
est une boucle sur un tableau. Ce qui manque à l'API pour la Table est gratuit dans la Visionneuse.

### Ce que l'implémentation a tranché autrement

**Trois volets et non six.** Un volet par interrupteur coûte un geste pour une ligne. La mobilité et
le pied-à-terre disent tous deux comment le retour se prépare : ils rejoignent l'horizon sous
« Projet de retour ». Restent « Présence au Maroc » et « Disponibilité ».

**La ville est en pastilles, pas en menu déroulant.** Le menu de la bibliothèque d'interface refuse
une option de valeur vide, et l'écran le signalait en console à chaque rendu. Les pastilles règlent
les trois choses d'un coup : plus d'erreur, une forme identique aux autres facettes, et le retrait
par simple reclic — un menu aurait dû loger une entrée « toutes les villes » pour dire *rien*.

**La fenêtre de présence et la ville portent sur le MÊME séjour.** Découvert en lisant le serveur,
qui les résout en un `EXISTS` unique : séparées, « Casablanca sous 30 jours » répondrait oui à un
candidat qui passe à Casablanca en décembre et à Rabat la semaine prochaine. La règle est reproduite
à l'identique, et c'est elle que le test le plus important couvre.

**« Maintenant » se calcule sur les séjours, et non sur le drapeau `isInMorocco` de la carte.** Le
drapeau ne se combine pas à une ville. Le serveur avertit que l'arithmétique de dates ne doit pas
vivre en deux langages ; elle vit ici dans `services/search/presence.ts`, en **un seul** endroit
côté front, à côté du calcul qui y était déjà.

**C'est DÉPLIER un volet qui tire le jeu entier, et non poser un affinage.** La carte disait « au
premier filtre posé » ; ça enfermait. Les comptes se calculent sur ce qui est chargé : une option
dont tous les profils dorment en deuxième page s'affiche à zéro, donc désactivée, donc jamais
cliquée — et le chargement qu'elle aurait déclenché ne part pas. Le moment où l'on **regarde** les
comptes est celui où ils doivent être vrais. Constaté en préparant la preuve, avant d'avoir livré.

### ⚠️ Ce que la preuve a révélé : ces filtres n'ont pas de données

Mesuré en base pendant l'implémentation, sur les **3 899 candidats** du vivier de développement :

| Donnée | Renseignée |
| --- | --- |
| Pays actuel | 3 881 · 99,5 % |
| Compétences | 3 635 · 93 % |
| Langue parlée | 3 434 · 88 % |
| Niveau d'études | 3 143 · 81 % |
| Secteur d'expérience | 3 048 · 78 % |
| **Présence · horizon · disponibilité · pied-à-terre · mobilité** | **3 · 0,08 %** |
| Secteur **visé** · fonction **visée** | 3 · 0,08 % |

**Les six filtres retenus sont exactement les six vides.** Sur une vraie recherche du vivier, les
onze options s'affichent à zéro et grisées ; le panneau est inerte. Les trois seuls profils qui
portent ces données sont les témoins des fixtures, sur lesquels le mécanisme est prouvé.

Ce n'est pas une surprise produit — le tableau de partage annonçait déjà ces champs en 🔴 — mais le
cadrage avait vérifié que les champs **existaient sur la carte de profil**, pas qu'ils étaient
**remplis**. La distinction a coûté une implémentation entière ; elle mérite d'être faite en amont
la prochaine fois.

**Décision, prise au vu de la mesure : livrer quand même.** Le mécanisme est juste et prouvé, il
s'allumera de lui-même à mesure que les candidats compléteront leur espace showcase-recruiter-webapp-saas, et les compteurs
à zéro disent la vérité du vivier plutôt que de la cacher. Ce qui aurait de la donnée aujourd'hui —
pays, langue, compétences — appartient aux sept filtres écartés du périmètre et ne porte pas la
promesse du produit.

## Règles et cas limites

- **La fenêtre de présence est un choix exclusif** — on ne combine pas « < 15 j » et « < 30 j ».
  La ville du séjour, elle, se combine librement avec elle.
- **ET entre filtres.** Chaque filtre posé resserre ; aucun ne se remplace, contrairement aux
  critères de la recherche.
- **Un interrupteur décoché ne pose rien** — il ne filtre pas sur « pas mobile », qui n'intéresse
  personne. Même règle que `flag()` dans `criteria.ts`.
- **Le comptage d'une option ignore son propre filtre** et applique tous les autres : c'est ce qui
  permet de voir ce qu'on gagnerait en changeant d'option sans avoir à l'essayer.
- **Les écartés restent écartés.** L'affinage porte sur la pile telle que le serveur la sert ; il ne
  ramène pas un profil qu'on a mis de côté.
- **Aucun profil ne passe** → un message qui le dit, et le retrait de tous les affinages en un
  geste. Le grisage des options à zéro rend le cas rare, il ne le rend pas impossible : le tout
  premier filtre posé peut vider l'écran.
- **Le chargement échoue en cours de route** → on ne filtre pas sur un jeu incomplet en silence :
  l'écran dit que la pile n'a pas pu être chargée entièrement, et l'affinage attend la reprise.
  `needsNextPage` porte déjà cette règle pour le défilement (`failed` bloque).
- **Un affinage posé et le parcours qui continue** : voir, retenir et écarter fonctionnent
  normalement dans le jeu affiné, et leurs effets valent pour la recherche entière.
- **Accessibilité** : les volets s'ouvrent au clavier et annoncent leur état ; une option grisée est
  réellement désactivée, pas seulement pâle ; le retour au premier profil est annoncé, sans quoi un
  lecteur d'écran ne signale rien alors que tout a changé.
- **Aucun texte en dur** : tous les libellés passent par des clés i18n.

## Documentation impactée

- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` **(M)** — le § « Ce que l'écran pose
  aujourd'hui » gagne le troisième formulaire, la distinction **critère / affinage** qui n'avait pas
  d'autre endroit où vivre, et la mesure de remplissage du vivier.
- `webapp/services/README.md` — inchangé : le calcul ouvre un module de plus dans `services/search/`,
  ce que le README décrit déjà sans les énumérer.
- Les en-têtes de `Console.vue`, `ControlPanel.vue`, `Stack.vue` et `recherches/[uuid]/index.vue`
  portent l'interdiction renversée : ils sont **réécrits**, pas laissés à mentir. C'est exactement le
  défaut qu'`ENT-091` a eu à corriger quatre jours après `ENT-076`.

## Ce qui sort de cette lane

**`docs/showcase-recruiter-webapp-saas/content/2.architecture/filtres-vivier-tableau-partage.md` est à réviser**, et il
appartient à l'outillage (`OPS-018` l'a écrit). Sa colonne « Le filtre est prêt ? » marque ✅ les
différenciants — vrai pour le moteur, trompeur pour le lecteur : le filtre existe, la donnée
n'existe pas. La mesure du 2026-08-13 (3 profils sur 3 899) y a sa place, à côté du taux de
remplissage qu'il porte déjà. Aucune carte n'est ouverte ici.

## Vérification

Stack du worktree entreprise, connecté en `owner@demo.showcase-recruiter-webapp-saas.example`, navigateur piloté.

**Un affinage posé, de bout en bout** — recherche « Profils de démo (fixtures) », 2 profils :

```
avant  → barre haute : profil 1 sur 2
option « Sous 6 mois » cliquable : true
après  → barre haute : profil 1 sur 1
pied de colonne      : 1 profil sur 2
profil affiché       : Développeur backend PHP …
retiré → barre haute : profil 1 sur 2
```

**La recherche n'a pas bougé** — relue par l'API après tous ces gestes :

```
nom      : Profils de démo (fixtures)
critères : [('hasPiedATerre', 'true'), ('geoMobility', 'true')]
résultats: 2
modifiée : 2026-08-13T12:15:23+00:00   ← l'heure de sa CRÉATION
```

**Le jeu entier arrive au dépliage** — recherche de 41 profils, servie par pages de 30 :

```
à l'ouverture      : 30 fiches · 0 appel(s) results
volet déplié       : 41 fiches · 1 appel(s) results
comptes du volet   : Sous 3 mois 0 · Sous 6 mois 1 · Dans l'année 0 · En réflexion 0
```

**Les comptes sur le vivier réel** — recherche « architecte », 32 profils :

```
options : Maintenant 0 [inerte] · Sous 15 j 0 [inerte] · Sous 30 j 0 [inerte] · Sous 60 j 0 [inerte]
          Sous 3 mois 0 [inerte] · … · Préavis 3 mois 0 [inerte]
```

**Tests** — `bun run vitest run services/search/refine.vitest.ts services/search/presence.vitest.ts
base/i18n/locales/parity.vitest.ts` :

```
 Test Files  3 passed (3)
      Tests  43 passed (43)
```

## Livré

- `webapp/services/search/refine.ts` **(+)** — la sélection, le comptage facetté et les villes
  offertes, en fonctions pures.
- `webapp/services/search/presence.ts` **(M)** — `hasStayWithin` (fenêtre **et** ville sur le même
  séjour, règle du serveur) et `stayCities`.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` **(+)** — les trois volets, leurs
  comptes, et la demande du jeu entier au dépliage.
- `webapp/spaces/entreprise/app/components/search/RefineChoice.vue` **(+)** — la rangée d'options
  exclusives ; reposer l'option active la retire.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — accueille le panneau
  entre l'avancement et la sortie, porte le décompte et le retrait.
- `webapp/spaces/entreprise/app/components/search/Console.vue` **(M)** — relaie.
- `webapp/spaces/entreprise/app/components/search/Stack.vue` **(M)** — l'état vide dû à un affinage,
  qui n'accuse plus les critères.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — l'état de l'affinage, le
  chargement complet, le rang qui le suit, le retour au premier profil.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — les libellés, dans les trois langues.
- `webapp/services/search/{refine,presence}.vitest.ts` **(+/M)** — 34 tests sur la couche pure.
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` **(M)** — la distinction critère /
  affinage et la mesure de remplissage.

## Reste à faire (hors périmètre)

- **L'accès sous 1024 px** — la colonne entière est masquée, les affinages n'existent pas sur
  téléphone. Écarté au cadrage, à ouvrir.
- **Les sept autres filtres secondaires**, dont les trois qui ont réellement de la donnée : pays
  actuel (99,5 %), langue (88 %), secteur d'expérience (78 %).
- **Le tableau de partage**, hors lane — cf. § *Ce qui sort de cette lane*.

## Définition de terminé

- [x] Les six affinages sont posables dans la colonne de gauche, en volets repliés, sous les
      compteurs et au-dessus de « Ma sélection ».
- [x] Déplier un volet charge la pile entière, avec une attente visible, puis les affinages
      resserrent le parcours — 30 fiches à l'ouverture, 41 au dépliage.
- [x] Chaque option affiche son compte ; une option à zéro est grisée et ne se clique pas.
- [x] Le rang de la barre haute suit le jeu affiné ; Vus / Favoris / Écartés ne bougent pas.
- [x] Poser ou retirer un affinage ramène au premier profil du jeu obtenu.
- [x] Un jeu vide affiche son message et le retrait de tous les affinages en un geste. **Écrit, non
      éprouvé à l'écran** : le grisage des options à zéro rend cet état difficile à atteindre — c'est
      un filet, et il le reste.
- [x] La recherche enregistrée est inchangée : son `updatedAt` est resté à l'heure de sa création.
- [x] La sélection, le comptage par option et la reprise de position sont des fonctions pures de
      `services/search/`, couvertes par leurs tests.
- [x] Les en-têtes qui portaient l'interdiction disent la frontière nouvelle.
- [x] Aucun texte en dur ; volets et options utilisables au clavier — `UAccordion` et des `<button>`
      natifs, sans piège de focus introduit.
