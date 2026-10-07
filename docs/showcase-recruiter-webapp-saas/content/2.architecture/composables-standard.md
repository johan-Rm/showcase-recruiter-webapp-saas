# showcase-recruiter-webapp-saas — Standard des composables

> Le rôle des composables dans l'architecture de données du `webapp/` et leur standard d'écriture,
> au sein du triptyque **composables / stores / services**. Complète
> [responsibility-boundaries.md](./responsibility-boundaries.md),
> [stores-standard.md](./stores-standard.md), [services-standard.md](./services-standard.md) et le
> README co-localisé [`composables/README.md`](../../../../webapp/base/app/composables/README.md).

## Rôle

Un composable (`useXxx()`) est la **passerelle réactive** entre l'affichage (pages, composants) et
la logique applicative. Il orchestre la réactivité liée à Vue/Nuxt :

- gestion de l'état réactif (local `ref` vs global via store) ;
- orchestration des données (déclenchement du BFF, dérivation `computed`) ;
- synchronisation UI / URL / cycle de vie ;
- encapsulation d'un comportement réutilisable.

**Le métier pur ne lui appartient pas** : il est délégué à `services/`. Un composable ne devient ni
un « God Object », ni une couche métier globale implicite. Test d'admission : *a-t-il besoin de la
réactivité Vue ou du runtime Nuxt ?* Sinon, c'est un `service` ou un `util`.

## Le triptyque : composables, stores, services

Trois couches aux rôles non superposables.

### Composables — passerelle UI ↔ logique applicative

Exposent une API réactive prête pour les composants, en orchestrant stores, services et endpoints
BFF. Ils préparent des données déjà prêtes à rendre pour que les composants restent passifs.

### Stores — état global brut, lecture enrichie

Les stores Pinia (`useXxxStore()`) centralisent l'état global. Le state stocke le DTO brut
sérialisable ; les getters exposent la lecture et l'état dérivé ; les mutations passent par des
setters explicites (`setX`, `reset`). Détail : [stores-standard.md](./stores-standard.md).

### Services — logique métier pure

Transformation de données (DTO → contrat prêt à rendre), résolution du flou des champs optionnels,
gestion d'erreurs. **Indépendants de Vue, Nuxt et Pinia** (règle CI `services-no-framework-imports`),
donc testables hors runtime. Détail : [services-standard.md](./services-standard.md).

### Le BFF (`server/`)

Le client ne parle jamais à l'API Symfony en direct : les routes Nitro `/api/*` servent de BFF.
Elles attachent le JWT de session, relaient vers l'API et normalisent les réponses. En SSR, un
`useFetch` vers un endpoint interne exécute le handler directement, sans requête HTTP réelle.

## Flux général des données

1. L'utilisateur interagit avec l'UI (pages, composants).
2. Les composants invoquent un composable (`useXxx`) — jamais un appel réseau.
3. Le composable s'appuie sur un store, un service, ou appelle un endpoint BFF (`useFetch('/api/*')`).
4. Le BFF relaie vers l'API Symfony (JWT de session attaché).
5. Le composable hydrate le store → les getters recalculent → l'UI se re-rend.

## Qui charge quoi, et quand — l'espace entreprise

L'espace entreprise **déclare** son chargement au lieu de le laisser à chaque écran. Le registre vit
dans `spaces/entreprise/app/bootstrap/`, les chargeurs à côté de lui, et le plugin les pose au
démarrage. Quatre routes y figurent : l'accueil, la collection, la Visionneuse et la Table.

| Route | Bloquant, avant le rendu | En arrière-plan, après |
| --- | --- | --- |
| accueil | *rien* — l'écran charge sa collection lui-même | l'état, les fiches affichées, **puis le remplissage** |
| collection | *rien* — même raison | l'état, les fiches affichées, **puis le remplissage** |
| Visionneuse, Table | le jeu de CETTE recherche | l'état, **puis le remplissage** |

> **Une route absente de ce tableau ne charge RIEN** — l'orchestrateur lui rend un plan vide. L'écran
> de collection y a manqué : on pouvait y rester sans qu'une seule recherche se charge, et y viser une
> carte ne réordonnait rien puisqu'aucun jeu n'était connu.

### L'état de travail et les fiches sont deux chargements, et l'ordre compte

**L'état de travail** (`/api/searches/workspace`) rapporte les cinq listes d'identifiants de toutes
les recherches ouvertes — le jeu, les retenus, les écartés, les vus, les nouveaux —, et **rien
d'autre** : ni fiche, ni explication. Cinquante-cinq recherches tiennent en quelques centaines de
kilo-octets, ce qui permet de tout prendre.

**Les fiches** (`/api/searches/profiles`) arrivent ensuite, et pour les seules recherches que
l'accueil affiche : leurs trente premiers profils chacune, dédoublonnés entre elles. L'écran nomme
ces recherches — un serveur qui prendrait « les plus récentes » finirait par précharger une recherche
que l'accueil n'affiche pas.

Les deux vivent dans **un seul chargeur** parce qu'un palier exécute ses chargeurs en parallèle :
déclarés côte à côte, les fiches pourraient arriver avant qu'on sache à quelle recherche elles
appartiennent. Aucun des deux n'est bloquant.

> **La séparation vaut un facteur huit** : le préchargement qui mélangeait état et fiches pesait
> 4,95 Mo à l'ouverture d'un compte réel ; les deux appels séparés en pèsent 594 Ko.

### Le remplissage de fond : les recherches, une par une

Une fois l'état connu, un chargement de fond **remplit les recherches entièrement**, et il tourne
quel que soit l'écran. Pour chacune : la pile de travail par pages de cent, puis les profils écartés.
Chaque appel rapporte les fiches **et** leurs explications — les séparer doublerait le nombre
d'allers-retours sans rien permettre de doser, puisqu'on charge tout de toute façon.

**Un seul appel de préchargement est en vol, du début à la fin.** Ce n'est pas de la prudence : c'est
ce qui garantit qu'un geste du recruteur — dévoiler un profil, écarter, ouvrir une recherche — ne
passe jamais derrière une file de remplissage.

**L'ordre se recalcule à chaque pas**, et non une fois pour toutes : la recherche ouverte mène, les
recherches **désignées** suivent, puis les recherches affichées, puis les plus récemment touchées.
Ouvrir une recherche pendant que le fond travaille la fait donc passer devant au pas suivant, sans
qu'on ait à défaire une file déjà construite.

Une recherche se **désigne** par trois gestes qui disent la même chose une demi-seconde avant le
clic : la survoler ~120 ms, y poser le focus au clavier, ou — sur un écran sans survol — la faire
entrer dans la vue. Ces signaux ne lancent **aucun appel qui leur soit propre** : le palier en cours
finit, la désignée passe ensuite. Le délai écarte le balayage — sans lui, traverser une grille de six
cartes les désignerait toutes, et c'est la dernière traversée qui gagnerait.

**Une intention ne rouvre pas un budget épuisé** : une recherche que le plafond exclut ne se charge
qu'en l'ouvrant. Et une recherche déjà complète ne se désigne pas — elle remonterait dans la file une
entrée que le fond saute de toute façon.

**Deux plafonds, et il en faut deux** : vingt recherches, ou 1 500 profils. Un budget en recherches
seul ne borne rien — vingt recherches pèsent de trois cents kilo-octets à huit mégaoctets selon leur
taille ; un budget en profils seul serait avalé par une seule recherche énorme. Le plafond borne un
**poids et non un rang** : une recherche qui ne tient pas dans ce qui reste est sautée, et l'examen
continue. **La recherche ouverte échappe aux deux**, sans quoi elle resterait à moitié chargée — soit
l'état exact que ce mécanisme existe pour supprimer.

**Rien ne se voit, et rien ne bloque.** Les compteurs viennent de l'état et sont déjà justes ; une
recherche pas encore remplie reste cliquable et son écran affiche l'attente qu'il affiche déjà. Un
appel qui échoue arrête le fond **sans message** : le recruteur n'a pas demandé ce chargement.

> Mesuré sur un compte de huit recherches et 4 172 appartenances : **2,39 Mo en 30 secondes**, et
> ouvrir une recherche remplie ne coûte **aucun appel**. Le plafond y écarte une recherche de 3 239
> profils, qui se charge entièrement dès qu'on l'ouvre.

**Une page tentée ne se retente jamais.** Une page peut revenir sans tout ce qu'on en attendait — un
profil supprimé entre l'état et l'appel, ou un profil qu'aucun critère ne sait énoncer. Elle reste
alors incomplète, donc éligible, et le fond la redemanderait sans fin.

### Ce qui se déduit, et ce qui ne se déduit jamais

**Aucune liste d'état ne se calcule à partir des fiches en mémoire.** Un compte tiré du chargé vaut
« parmi ce que j'ai », pas « dans cette recherche » — et il devient faux dès que le chargement est
partiel, ce qui est désormais le cas normal. Les cinq listes viennent du serveur, telles quelles.

L'explication des correspondances fait exception dans l'autre sens : elle n'existe que pour les
profils chargés, elle est donc partielle par nature. **Rien ne doit la compter** — ni total, ni
position, ni badge dérivé de sa taille —, et relire l'état préserve ce qui en est déjà connu au lieu
de l'écraser.

### Deux règles de gardes

- **L'accueil ne déclare rien de bloquant.** Sa collection porte des compteurs — nouveautés, vus,
  retenus — qui bougent dès qu'on ouvre une recherche : déclarée au registre, la garde du chargeur la
  tairait au retour et l'accueil afficherait des compteurs d'avant.
- **Une garde demande si les FICHES sont là, pas si la liste l'est.** L'état apporte les identifiants
  de toutes les recherches : « connais-je la liste ? » répondrait oui sans qu'aucune fiche ne soit
  chargée. Observé au navigateur avec une seule fiche manquante — l'écran affichait 99 cartes en
  annonçant 100, sans rien redemander.
- **Un écran qui charge aussi passe par le même chargeur**, avec sa garde. Deux chemins mènent à la
  donnée — l'orchestrateur et l'écran —, et sans garde partagée chaque appel partait deux fois.

**Ouvrir une recherche remplie ne coûte plus rien.** Les deux appels qui partaient à chaque ouverture
— les retenus et les écartés — ont disparu : un retenu est forcément dans la pile, et les écartés
arrivent par le fond. Ils subsistent pour un geste qui change la donnée côté serveur, comme remettre
un profil écarté, où le jeu en mémoire ne décrit plus la vérité.

## Frontières réseau

- **Un composant ne récupère jamais ses données** (règle CI `app-no-fetch-in-components`) : il les
  reçoit par props ou via un composable.
- **Un composable ou une page appelle le BFF** directement via `useFetch('/api/*')` — la couche
  serveur de la même app.
- **L'API Symfony externe** n'est appelée que par le BFF ; jamais en direct depuis un composable.
  Il n'existe **pas** de couche `services/api/` cliente en showcase-recruiter-webapp-saas.
- **Un composable appelle un service directement** pour une transformation pure ; si l'état produit
  a vocation à être partagé, il passe par le store.

## Conventions d'écriture

Portées par le README co-localisé ([`composables/README.md`](../../../../webapp/base/app/composables/README.md)) :

- **responsabilité unique** — `useSearch`, `useUserProfile`, pas de fourre-tout ;
- **le nom dit la responsabilité, jamais l'espace** — `useVivier` et non `useAdminVivier`, la layer
  portant déjà le domaine ; c'est le nom de l'**export** que Nuxt lit, pas celui du fichier
  (règle CI `app-no-space-prefix`) ;
- **API explicite** — le `return {}` reflète le métier (`users`, `isLoading`, `refreshUsers`), pas
  la mécanique (`data`, `loading`, `execute`) ;
- **side-effects explicites** — aucun appel réseau implicite à l'invocation ; l'appelant déclenche ;
- **état local vs partagé** exprimé clairement (`ref` local vs store) ;
- **≤ 8 valeurs** dans le `return {}` (seuil non verrouillé en CI).

## Philosophie

Un composable doit se comprendre en lisant **son nom, ses paramètres et son `return`**. Objectif :
lisible, prévisible, testable, réutilisable, maintenable.
