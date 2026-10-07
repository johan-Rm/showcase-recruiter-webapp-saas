# TASK ENT-049 — entreprise : le parcours reprend sur ce qui reste à voir

- **Créée le** : 2026-08-05.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — le parcours démarre sur les non vus, un sélecteur nomme les
  deux ordres, et le geste « Reprendre » est retiré avec la chaîne qui le portait.
- **Type** : Frontend entreprise — un ordre de parcours, son sélecteur, et le retrait d'un geste
  devenu superflu.
- **Tests** : unitaires dans `webapp/services/search/`, sur la fonction d'ordre — tout vu, rien vu,
  mélangé, et surtout la **stabilité de l'ordre à l'intérieur de chaque groupe** : remonter les non
  vus ne doit pas perdre la pertinence entre eux. Le sélecteur et le rendu ne sont pas couvrables —
  le webapp n'a aucun harnais de composant —, la preuve est une capture.
- **Réf** : **`ENT-047` — amont** (c'est elle qui a posé le geste « Reprendre » sur la case « Vus »,
  et cette carte le retire) · `ENT-038` (la progression mesure le travail) ·
  `SearchResultMembershipRepository::ordered` (l'ordre servi) · `useSearch.ts` · `ProgressHeader.vue`

## Contexte

**Rouvrir une recherche entamée repart du premier profil.** Le recruteur qui en a tranché dix-neuf
sur trente doit refaire défiler les dix-neuf pour retrouver le vingtième. Rien ne mémorise sa
position, et le seul chemin vers ce qui reste est un geste qu'il faut connaître — celui qu'`ENT-047`
vient de poser sur la case « Vus ».

**Et l'ordre servi n'est nommé nulle part.** La pile arrive triée par **nombre de critères
satisfaits** (`m.matchedCount DESC`), puis les profils rencontrés en départage à correspondance
égale, puis les entrées récentes, puis l'identifiant pour que deux appels rendent le même ordre.
C'est une hiérarchie utile et invisible : le recruteur ignore que sa pile est déjà classée par
pertinence, donc il ignore ce qu'il perd en la réordonnant.

## Périmètre

- **Deux ordres nommés** : « Non vus d'abord », qui devient le **défaut**, et « Pertinence », qui
  nomme l'ordre servi par le serveur.
- **Un sélecteur** à deux liens dans la console, **sous l'entête**, au-dessus de la liste des
  critères.
- **Le retrait du geste « Reprendre »** de la case « Vus », que le défaut rend superflu — la case
  redevient un simple constat.
- **Une fonction d'ordre pure** dans `services/search/`, testable hors runtime.

## Hors périmètre

- **La mémorisation du tri.** Il repart au défaut à chaque ouverture : rien à stocker, et aucun état
  qui survive à l'utilisateur pour le surprendre trois jours plus tard.
- **Le tri côté serveur.** L'API n'a aucun paramètre d'ordre ; en ajouter un est une tâche de la lane
  API, et elle n'est pas nécessaire ici — voir *Risques*.
- **Masquer les profils vus.** Ils passent derrière, ils ne disparaissent pas : se raviser sur un
  profil qu'on vient de voir est fréquent, et le masquer obligerait à changer de tri pour y revenir.
- **Le plafond de trente profils chargés**, antérieur et plus large que cette carte.
- **Un troisième ordre** — par expérience, par ville, par date d'entrée. Deux suffisent à répondre au
  besoin ; le reste est le travail de la Table.

## Décisions de cadrage

### Le défaut règle le retour, et rend le geste inutile

Plutôt que de mémoriser une position — qu'il faudrait stocker, et dont il faudrait décider le sort
quand le jeu a changé entre-temps —, l'ordre le fait tout seul : si les non vus sont devant, arriver
sur une recherche entamée signifie arriver sur ce qui reste à faire.

Conséquence directe et assumée : **« Reprendre » n'a plus de raison d'être**, et il est retiré ici.
Il n'est pas retiré d'`ENT-047`, où il reste juste : sans tri, il y est le seul chemin vers les non
vus. Les deux cartes se tiennent dans cet ordre, et rien n'est cassé entre elles.

### La pertinence n'est pas à construire, elle est à nommer

Le serveur classe déjà. L'erreur serait d'écrire un tri « par pertinence » côté front, qui
recalculerait mal ce que le SQL fait bien — le front ne reçoit d'ailleurs même pas `matchedCount`.
« Pertinence » désigne donc **l'ordre reçu**, rendu tel quel.

C'est ce qui rend l'autre ordre sûr : il ne mélange pas, il **regroupe**. Les non vus devant, les vus
derrière, et **dans chaque groupe l'ordre du serveur intact**. Un tri qui perdrait la pertinence à
l'intérieur des groupes serait un tri qui dégrade.

### Côté front, et la réserve est écrite

`isSeen` voyage avec chaque fiche : l'ordre se calcule sans toucher à l'API, sur une seule lane,
livrable tout de suite. Il ne porte donc que sur les **trente profils chargés** — voir *Risques*.

### Le sélecteur va dans la console

La console dit déjà « sur quoi je cherche et où j'en suis » ; l'ordre du parcours en fait partie. La
pile, elle, n'a pas d'en-tête — une card occupe un plein écran — et lui en créer une mangerait la
hauteur de chaque profil.

Deux liens à bascule plutôt qu'une liste déroulante : à deux options, ouvrir un menu pour en choisir
une est un clic de trop.

## Règles et cas limites

- **L'ordre regroupe, il ne mélange pas** : à l'intérieur des non vus comme des vus, l'ordre du
  serveur est conservé tel quel.
- **Changer d'ordre ne perd pas le profil courant** : on reste sur celui qu'on lisait, à sa nouvelle
  place. Sauter ailleurs parce qu'on a changé de tri désoriente.
- **Marquer un profil comme vu ne le déplace pas sous les yeux.** Le regroupement se calcule à
  l'ouverture et sur changement d'ordre, jamais pendant le défilement — sinon la pile se réorganise
  sous le curseur à chaque card franchie.
- **Tout vu, ou rien vu** : l'ordre est alors identique à la pertinence, et le sélecteur reste
  utilisable sans rien changer.
- **Aucun texte en dur** : deux clés i18n dans les trois langues.
- **Le sélecteur est atteignable au clavier** et annonce l'ordre actif, pas seulement par la couleur.
- **Il disparaît sans profils** : ordonner une pile vide n'a pas d'objet.

## Risques et reports assumés

**Le tri porte sur les trente profils chargés, pas sur les 225 du jeu.** Si les trente ont tous été
vus, « non vus d'abord » n'a plus rien à remonter alors que cent quatre-vingt-quinze profils non vus
existent, non chargés.

Le vrai obstacle est donc le **plafond de chargement** de la Visionneuse — elle demande une page de
trente et n'en redemande jamais —, qui précède cette carte et la dépasse. Le tri améliore
franchement le cas courant sans le résoudre au-delà.

À rouvrir avec la question du chargement, pas seule.

## Documentation impactée

- **Le commentaire de `ProgressHeader.vue`**, qui décrit le geste porté par « Vus ».
- **Le doc-block de `useSearch.ts`** autour de `firstUnseenIndex` et `unseenCount`, si le retrait de
  « Reprendre » les laisse sans consommateur.
- **Vérifié à l'écriture, non impacté** : `arborescence-entreprise.md` (aucun écran ni parcours
  nouveau), `services-standard.md` (à relire pour la forme du service).

## Définition de terminé

- [x] Rouvrir une recherche entamée arrive sur le premier profil **non vu** — capture à l'appui.
- [x] Le sélecteur propose « Non vus d'abord » et « Pertinence », et dit lequel est actif.
- [x] En « Pertinence », l'ordre est **exactement** celui reçu du serveur.
- [x] En « Non vus d'abord », l'ordre du serveur est conservé **à l'intérieur de chaque groupe**.
- [x] Changer d'ordre garde le profil courant sous les yeux.
- [x] Marquer un profil comme vu ne réorganise pas la pile en cours de défilement.
- [x] La case « Vus » n'est plus cliquable, et la chaîne `resume` / `canResume` ne laisse aucun code
      mort.
- [x] La fonction d'ordre est couverte sur les quatre cas, dont la stabilité intra-groupe.
- [x] Les deux clés existent dans les trois langues.

## Vérification

### Le sélecteur, et ce qu'il annonce

```
sélecteur présent : ✓
« Non vus d'abord »  aria-pressed=true      ← le DÉFAUT
« Pertinence »       aria-pressed=false
groupe nommé pour le lecteur d'écran : ✓
après clic sur « Pertinence » : aria-pressed bascule sur les deux
```

L'ordre actif ne se lit donc pas que par la graisse et la couleur.

### La case « Vus » n'est plus un bouton

```
encadrés : ["div:Vus 27", "div:Favoris 0", "button:Écartés 0"]
```

Seule « Écartés » reste un bouton — elle ouvre la corbeille. Aucune trace de `resume`, `canResume`,
`firstUnseenIndex` ni `unseenCount` dans tout le webapp.

### Le gel : marquer un profil vu ne réorganise pas la pile

C'est la règle la plus subtile, et **ma première mesure a conclu à tort à un échec** — elle prenait
le relevé de départ pendant que l'écran finissait de se poser. Le relevé au fil du temps a situé le
moment exact :

```
t+ 2s → t+ 8s   pile stable                     vus = 1
t+10s           LA PILE CHANGE                  vus = 1, AUCUN défilement encore
t+12s → t+26s   pile STABLE, vus 2 → 6, cinq POST /reviews
```

Le seul remaniement arrive **pendant l'ouverture**, avant tout geste : le pool de profils se remplit
progressivement, et `cardsInSearch` ne rend que les cartes déjà chargées. C'est ce que la carte
autorise — « le regroupement se calcule à l'ouverture ».

Mesure refaite une fois l'écran posé, en défilant sur six cartes :

```
tête de pile au départ  : ["AI/Cloud Software Engi", "Poste non renseigné", "ServiceNow Consultant", …]  Vus = 10
tête de pile après      : ["AI/Cloud Software Engi", "Poste non renseigné", "ServiceNow Consultant", …]  Vus = 15
la pile N'A PAS bougé : ✓
```

Cinq profils marqués vus, tête de pile identique.

### Changer d'ordre ne perd pas le profil courant

On descend de quelques cartes avant de basculer, sinon le test ne prouve rien — le premier profil
resterait premier par accident :

```
au chargement      DOM= 1  affiché= 1  "Sales Specialist | Molecular"
après défilement   DOM= 6  affiché= 5  "Sales R&D Director Export Ma"
bascule + 1s       DOM= 5  affiché= 5  "Sales R&D Director Export Ma"   ← MÊME profil
bascule + 6s       DOM= 5  affiché= 5  "Sales R&D Director Export Ma"
```

L'écart d'un rang pendant le défilement vient de ma mesure — « la carte la plus proche du haut » —
plus grossière que la règle du composant, qui retient la dernière carte dont le haut a franchi celui
du conteneur. Après la bascule les deux coïncident.

### Tout vu : les deux ordres se confondent

Observé sans le chercher, sur une recherche dont les trente profils chargés avaient tous été vus :
les deux ordres rendent exactement la même pile, et le sélecteur reste utilisable. C'est la règle
« tout vu, ou rien vu ».

### Le sélecteur disparaît sans profils

Recherche `019fd166`, aucun résultat :

```
Vus 0  Favoris 0  Écartés 0
sélecteur présent : 0
critères listés   : 3      ← ils restent, et restent retirables (règle d'`ENT-047`)
```

### Sorties fraîches

```
vitest        17 fichiers, 214 tests passés (214 = 203 + 11 nouveaux)
conventions   13 règles, aucune violation
i18n          fr == en == ar ; trois clés d'ordre dans les trois langues
```

Captures : la console dans les deux thèmes, intitulé sur une ligne et les deux options en dessous.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `services/search/stack.ts` | **Nouveau** — les deux ordres, le regroupement, et le gel par identifiants |
| `stack.vitest.ts` | **Nouveau** — 11 cas, dont la stabilité intra-groupe et les deux réconciliations |
| `components/search/OrderPicker.vue` | **Nouveau** — deux liens à bascule, `aria-pressed` et groupe nommé |
| `pages/recherches/[uuid]/index.vue` | Tient l'ordre, le gel et le retour au profil courant ; `resume` retiré |
| `ControlPanel.vue`, `Console.vue` | Relaient l'ordre en prop + événement ; ne relaient plus `resume` |
| `ProgressHeader.vue` | « Vus » redevient un constat ; deux props de moins |
| `composables/useSearch.ts` | `unseenCount` et `firstUnseenIndex` retirés — plus aucun consommateur |
| `fr/en/ar.yaml` | Trois clés d'ordre ; la clé `resume` retirée avec son geste |

## Reste à faire (hors périmètre)

- **La pile se réorganise une fois, vers la dixième seconde, pendant que l'écran se pose.** Le pool
  de profils se remplit progressivement et `cardsInSearch` ne rend que les cartes déjà chargées : le
  premier regroupement porte donc sur une pile incomplète. C'est la même faiblesse d'hydratation que
  celle qui fait tomber le compte de favoris à zéro (`ENT-048`, *Reste à faire*). Invisible en
  production si l'écran se pose plus vite ; à traiter avec ce défaut-là, pas séparément.
- **Le plafond de trente profils chargés** reste entier : si les trente ont tous été vus, « non vus
  d'abord » n'a plus rien à remonter alors que des non-vus existent, non chargés. Déjà écrit en
  *Risques* ; à rouvrir avec la question du chargement.

## Écart au cadrage

### Le sélecteur remonte une INTENTION, il ne se lie pas

La carte n'en disait rien, et la liaison bidirectionnelle était le réflexe. Elle est **interdite dans
une page** par `pages-no-interactive-template`, une règle qui bloque le merge : « une page orchestre,
elle assemble des sections et se lit d'un coup d'œil ».

La page reçoit donc `@order` et le traite dans une fonction nommée, comme ses autres gestes
(`@remove`, `@refine`). Le bénéfice dépasse la conformité : le regel et le retour au profil courant
vivent dans cette fonction plutôt que dans un observateur, et la séquence se lit d'un bloc.

### L'intitulé passe AU-DESSUS des options

Décidé à la relecture de l'écran. Côte à côte, les deux se partageaient une colonne de deux cent
quatre-vingts pixels : « Ordre du parcours » se cassait en deux lignes pendant que les deux options
s'empilaient l'une sous l'autre.

L'intitulé au-dessus, en petites capitales, est aussi la grammaire des autres blocs de la colonne —
« RECHERCHE EN COURS », « CE PROFIL FACE À VOS CRITÈRES ».

### `unseenCount` et `firstUnseenIndex` sont supprimés, pas seulement délaissés

La carte prévoyait de revoir leur doc-block « s'ils se retrouvaient sans consommateur ». Vérification
faite, ils n'en avaient plus **aucun** dans tout le webapp. Les garder aurait posé deux valeurs
mortes dans un composable que son README borne à huit valeurs exposées.

Ce que la pile fait de l'état « vu » est désormais un **ordre**, et il vit dans `services/`.
