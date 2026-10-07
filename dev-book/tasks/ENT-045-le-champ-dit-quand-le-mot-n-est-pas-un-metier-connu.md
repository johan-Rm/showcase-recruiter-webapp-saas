# TASK ENT-045 — entreprise : le champ dit quand le mot n'est pas un métier connu

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — « plombier » dit maintenant que le mot n'est pas un métier
  connu, au lieu de laisser croire que le vivier est vide. 6 tests unitaires.
- **Type** : Frontend entreprise — message d'interface, appuyé sur une règle extraite en service pur.
- **Tests** : unitaires, sur la règle « ce terme n'est pas un métier connu » extraite dans
  `webapp/services/search/` (métier pur, zéro import framework) et couverte par un `*.vitest.ts`,
  comme ses voisins. Les cinq cas qui comptent : liste vide, liste non vide, appel en échec, appel en
  cours, terme trop court.
- **Réf** : **`API-023` — amont BLOQUANT**, voir *Décisions de cadrage* · `ENT-044` (l'autre
  changement du même champ) · `API-024` (ce que le serveur, lui, fait des mots inconnus) ·
  `CandidateRepository` (l'égalité stricte du filtre) ·
  `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue`

## Contexte

Un recruteur remplit « Intitulé de poste » et regarde la ligne posée juste à gauche du bouton
« Rechercher ». Elle lui annonce ce que sa recherche ramènera.

Aujourd'hui elle sait dire **une seule chose** quand le compte est nul : « Aucun profil ne
correspond ». Or deux situations opposées produisent ce zéro :

| Ce qui se passe vraiment | Ce que la ligne affiche |
| --- | --- |
| Le vivier ne contient personne sur ces critères | « Aucun profil ne correspond » |
| **Le mot tapé ne désigne aucun métier connu** | « Aucun profil ne correspond » |

Dans le premier cas, le recruteur a compris la réponse : il n'y a personne, il élargit. Dans le
second, on ne lui a pas dit qu'on n'avait **pas compris sa question** — et il en conclut que le
vivier est vide alors qu'il ne l'a jamais interrogé.

Le second cas est structurel, pas accidentel. Le filtre par métier exige une correspondance
**exacte** : le terme doit être, mot pour mot, le nom officiel d'un métier ou l'un de ses libellés
vivants (`CandidateRepository`, résolution du critère `job`). Un intitulé hors dictionnaire ramène
donc zéro **quoi qu'il y ait dans le vivier**. C'est le bon comportement — il a été examiné puis
confirmé dans `API-023` — mais il doit se voir.

## Périmètre

- **Une règle extraite en service pur** : à partir de ce que le champ sait déjà (le terme, les
  propositions reçues, l'état de l'appel), dire si le terme est hors dictionnaire.
- **`FiltersPrimary.vue`** : la ligne de volume affiche ce message **à la place** de « Aucun profil
  ne correspond » quand la règle est vraie.
- **Une clé de traduction** dans les trois fichiers de langue (`fr.yaml`, `en.yaml`, `ar.yaml`).

## Hors périmètre

- **Le champ « Mots-clés ».** Ils ne résolvent pas contre le dictionnaire par conception (`API-009`) :
  un mot-clé inconnu n'est pas une incompréhension, c'est le fonctionnement normal du champ.
- **Empêcher de lancer.** Le bouton reste ouvert : on peut vouloir lancer sur un terme que le
  dictionnaire ignore, ne serait-ce que pour le constater — et c'est ce lancement qui produit la
  trace qu'`API-024` exploite.
- **Assouplir le filtre** pour qu'il accepte les termes approchants : écarté dans `API-023`, et ça le
  reste.
- **Proposer autre chose à la place** (métiers voisins, profils dont le titre mentionne le mot) :
  mesuré et écarté dans `API-024` — le titre de profil est de la prose, pas un intitulé de poste.

## Décisions de cadrage

### `API-023` est un amont bloquant, et pas seulement un confort

Livrée avant `API-023`, cette carte **mentirait**. Aujourd'hui, taper `logiciel` ne propose rien —
alors que « Développeur logiciel » existe et pèse 293 profils. Le message annoncerait donc « ce mot
ne désigne aucun métier connu » à propos d'un mot qui en désigne un très bien.

`API-023` fait justement tomber ce mur : après elle, un mot présent dans un métier connu propose
toujours quelque chose. Le silence de la liste redevient alors un signal fiable, et c'est ce signal
que cette carte transforme en phrase.

### Le signal, c'est la liste VIDE — surtout pas « le terme n'est pas dans la liste »

La règle qui vient à l'esprit est fausse, et elle mérite d'être écrite pour que personne ne la
réinvente : *« le terme saisi ne figure pas parmi les propositions, donc il est inconnu »*.

Contre-exemple mesuré : `développeur` est un **libellé variante** du métier « Développeur logiciel ».
Le filtre l'accepte — il ramène bien 293 profils. Mais les propositions n'affichent que les **noms
officiels**, donc `développeur` n'y figure pas. Cette règle-là le déclarerait inconnu à tort.

Ce qui est fiable, c'est l'inverse : **si la liste revient entièrement vide**, alors aucun métier ni
aucune de ses variantes ne porte ce mot, et l'égalité stricte du filtre échouera à coup sûr. C'est
une implication solide dans ce sens-là, et seulement dans ce sens-là.

Conséquence assumée : quand la liste est **non vide** mais que le terme libre n'en fait pas partie,
on ne dit rien. On préfère se taire que se tromper.

### Trois états qui ne sont pas « inconnu », et qu'il faut distinguer

Le champ sait déjà les distinguer, il ne faut pas les perdre en route :

- **l'appel a échoué** — `useSuggestions` renvoie `failed` précisément pour ça, et c'était le motif de
  sa conception : « rendre une liste vide sur erreur rendait la panne et le vocabulaire vide
  indistinguables ». Une panne ne prouve rien sur le mot ;
- **l'appel est en cours** — une liste vide pendant le chargement est une liste pas encore arrivée ;
- **le terme fait moins de deux caractères** — aucune interrogation n'est même tentée.

Dans ces trois cas, la ligne garde son message habituel.

### Le formulaire décide seul, la page n'est pas informée

Le volume descend de la page en `prop`, mais le fait « ce mot n'est pas un métier connu » appartient
au formulaire : c'est lui qui interroge le vocabulaire, lui seul sait ce que la liste a répondu. Le
faire remonter à la page pour redescendre en `prop` ajouterait un aller-retour sans rien clarifier —
et le composant est déjà décrit comme faisant ses propres appels d'auto-complétion pour cette raison.

### La règle sort du composant pour être testable

Elle tient en quelques conditions, mais ce sont **elles** qui portent la justesse du message, et le
webapp n'a aucun harnais de test de composant. Posée dans `webapp/services/search/` — métier pur,
zéro import framework, comme ses voisins `volume.ts`, `criteria.ts`, `states.ts` — elle devient
couvrable par un `*.vitest.ts` ordinaire.

## Règles et cas limites

- **Le message remplace, il ne s'ajoute pas.** Deux phrases au même endroit diraient deux fois la
  même chose et se contrediraient à moitié.
- **Il reste dans la même zone `role="status"`**, donc annoncé aux lecteurs d'écran (les logiciels
  qui lisent la page à voix haute) sans voler le focus au champ en cours de saisie.
- **Il nomme le terme concerné** et dit quoi faire — « choisir une proposition » —, sinon il constate
  sans aider.
- **Aucun texte en dur** : clé i18n dans les trois langues.
- **Un terme choisi dans la liste ne déclenche jamais le message** : interroger le vocabulaire avec
  un nom officiel le retrouve toujours, donc la liste n'est pas vide.
- **Le bouton reste actif** : ce message est une aide, pas une garde.
- **Le message disparaît dès que la liste se remplit à nouveau**, sans attendre un lancement.

## Documentation impactée

- **Le commentaire de `FiltersPrimary.vue`**, qui décrit les états de la ligne de volume.
- **Le `README.md` de `webapp/services/`** si la convention du dossier le demande pour un service
  nouveau — à lire avant d'écrire, il fait loi sur son périmètre.
- **Vérifié, non impacté** : `arborescence-entreprise.md` (aucun écran ni parcours nouveau),
  `i18n.md` (une clé s'ajoute, aucune règle ne change), `services-standard.md` (à relire pour la
  forme du service, pas à modifier).

## Définition de terminé

- [x] Un intitulé hors dictionnaire affiche « ce mot ne désigne aucun métier connu », pas « aucun
      profil ne correspond » — **capture à l'appui**.
- [x] Un intitulé qui ramène zéro profil pour une autre raison affiche toujours « aucun profil ne
      correspond ».
- [x] `développeur`, libellé variante, n'est **jamais** déclaré inconnu.
- [x] Une panne du service de propositions n'affiche pas le message.
- [x] Pendant le chargement, aucun message d'inconnu.
- [x] La règle est un service pur, couvert par un `*.vitest.ts` — six cas au lieu des cinq prévus.
- [x] La clé existe dans les trois langues.

## Vérification

**Constaté dans le navigateur**, sur la stack de ce worktree, avec un compte recruteur — l'API y sert
le code d'`API-023` fraîchement mergé, vérifié avant de commencer. Trois termes tapés à la suite, et
la ligne relue à chaque fois :

```
« plombier »             → « plombier » ne désigne aucun métier connu — choisissez une proposition…
« dev »                  → Aucun profil ne correspond
« développeur logiciel » → 293 profils correspondent
```

Les trois disent exactement ce qu'il faut. Le premier est le cas de la carte. Le troisième prouve
qu'un intitulé reconnu passe normalement. **Le deuxième est le plus intéressant** : « dev » ramène
des propositions, donc la règle se tait — et l'écran retombe sur « Aucun profil ne correspond »,
parce que « dev » n'est pas un libellé exact. C'est le comportement voulu (« se taire plutôt que se
tromper »), et c'est aussi la limite de la carte : voir *Écart au cadrage*.

La capture montre le message posé à côté du bouton, qui **reste actif** — on peut toujours lancer.

**`vocabulary.vitest.ts` : 6 tests, vert.** La liste vide, la liste non vide portant autre chose que
le terme tapé (le cas `développeur`, celui qui piège), l'échec, le chargement, le terme trop court, et
le champ vide ou blanc.

**Aucune page de documentation ne devient fausse** : ni `arborescence-entreprise.md` ni
`services-standard.md` ne décrivent la ligne de volume ou n'énumèrent les fichiers de services —
vérifié. Le `README.md` de `webapp/services/` n'impose rien pour un service nouveau. Seul le
commentaire du composant a été mis à jour.

## Livré

- `webapp/services/search/vocabulary.ts` **(+)** — la règle, en couche pure : elle reçoit ce que le
  champ a observé et rend un verdict. Son doc-block porte le piège de la règle inverse.
- `webapp/services/search/vocabulary.vitest.ts` **(+)** — 6 cas.
- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — le formulaire tranche
  seul, et la ligne de volume affiche le message **à la place** du compte à zéro.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — la clé `volume.unknownJob`.

## Écart au cadrage

### `API-023` a rétréci le cas d'usage, et c'est mesuré

La carte a été écrite en supposant qu'un terme hors dictionnaire ramènerait une liste vide. Depuis
`API-023`, un second passage **tolère les fautes** : un terme inconnu peut donc recevoir des
propositions par ressemblance, et la règle se tait alors.

Mesuré sur le vivier réel après merge :

| Terme tapé | Liste | Message |
| --- | --- | --- |
| `plombier` · `sommelier` · `berger` · `astronaute` · `zzz` · `xyzab` | vide | ✅ s'affiche |
| `prompt engineer` | 10 propositions (« Ingénieur d'études »…) | ❌ silence |

Le rattrapage n'attrape que le second cas : un terme **composé** dont un mot ressemble à un métier
connu — « engineer » contre « ingénieur ». C'est étroit, et la carte reste utile. Mais ce n'est plus
« tout terme hors dictionnaire », et il fallait le dire.

### Le fragment en cours de frappe n'est pas couvert, et ne peut pas l'être ici

Taper `dev` ouvre une liste de cinq propositions **et** affiche « Aucun profil ne correspond » : le
mot est connu du dictionnaire, mais il n'est pas un libellé exact, donc le filtre ramène zéro.

C'est cohérent avec la décision de cadrage — la liste n'est pas vide, on se tait — mais c'est
probablement le cas le plus **fréquent** à l'écran, et il reste sans explication. Le couvrir
demanderait que le serveur dise si le terme résout, ce qu'il ne fait pas : c'est une tâche de la lane
API, pas d'ici. **Nommé, pas ouvert.**
