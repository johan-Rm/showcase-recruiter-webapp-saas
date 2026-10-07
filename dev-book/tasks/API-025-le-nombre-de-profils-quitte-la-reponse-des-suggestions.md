# TASK API-025 — api : le nombre de profils quitte la réponse des suggestions

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-05 — le nombre ne sort plus du serveur, et l'ordre prouve qu'il y
  est toujours calculé. 12 tests, 68 assertions.
- **Type** : Backend API — retrait d'un champ du contrat exposé.
- **Tests** : fonctionnels, dans `SuggestionTest` — la réponse ne porte plus le nombre, **et** l'ordre
  des propositions reste celui du plus porté au moins porté, ce qui prouve que le décompte est
  toujours calculé côté serveur. Les assertions actuelles sur `profileCount` sont retournées, pas
  supprimées : c'est leur disparition qui est la propriété recherchée.
- **Réf** : **`ENT-044` — amont BLOQUANT** (le formulaire doit avoir cessé de lire le champ) ·
  `API-009` (qui a introduit ce nombre, et pourquoi) · `API-023` (l'auto-complétion, où le sujet est
  venu) · `SuggestionOutput` · `SuggestionProvider` · `SuggestionFinder`

## Contexte

`GET /v1/suggestions` renvoie, avec chaque proposition, le nombre de profils qu'elle ramènerait :

```json
{"items":[{"value":"Développeur logiciel","profileCount":293},
          {"value":"Business Developer","profileCount":156}]}
```

**Ce nombre décrit le vivier avant qu'on l'ait acheté.** En tapant quelques lettres au hasard, un
recruteur relève la taille de chaque population et la composition du portefeuille sans avoir dévoilé
un seul profil, donc sans avoir dépensé un crédit.

`ENT-044` a retiré son affichage. Ça ne suffit pas : la réponse HTTP se lit en ouvrant les outils de
développement du navigateur, sans compétence ni outil particulier. **Tant que le serveur l'envoie, il
est public.** Cette carte referme.

## Périmètre

- **`SuggestionOutput`** perd son champ.
- **`SuggestionProvider`** cesse de le transmettre.
- **`SuggestionFinder`** cesse de le **remonter** du SQL : il rend une liste de termes. Le décompte
  reste dans la requête, où il classe — c'est ce qui rend « il devient interne » vrai plutôt
  qu'approximatif. Ajout au périmètre décidé à l'implémentation, cf. *Écart au cadrage*.
- **`api/openapi.json`** régénéré — contenu de commit, pas une gate.
- **`webapp/shared/types/api.ts`** régénéré depuis le contrat
  (`bun run quality-generate-schemas` dans `webapp/`), **dans le même commit** : sinon les types du
  front décrivent un champ que le contrat ne porte plus.
- **`webapp/shared/types/search.ts`** — la déclaration écrite à la main de `SearchSuggestion`, et le
  commentaire qui explique pourquoi ce nombre accompagne « TOUJOURS » la valeur.
- **`SuggestionTest`** — les assertions sur le nombre.

## Hors périmètre

- **Le calcul du décompte**, qui reste entier côté serveur : c'est lui qui classe les propositions et
  qui écarte les métiers que personne n'exerce. Il devient **interne**, il ne disparaît pas.
- **La règle « aucune proposition ne ramène zéro »** (`API-009`), qui en dépend et qui reste.
- **L'affichage**, déjà traité par `ENT-044`.
- **Le comptage de volume** (`GET /v1/search/candidates` et sa ligne « 12 profils correspondent »),
  qui répond à une autre question : ce que la recherche entière ramènerait une fois tous les critères
  posés. Il n'est pas un inventaire du vivier, et il reste.

## Décisions de cadrage

### L'ordre des deux cartes est imposé par le typage, pas par le confort

Si le champ quittait le contrat en premier, le formulaire lirait une propriété que les types générés
ne décrivent plus, et la construction du front échouerait. `ENT-044` d'abord, celle-ci ensuite.

**Entre les deux, le problème n'est pas réglé** : l'écran n'affiche plus rien, le réseau dit encore
tout. À dire tel quel si la question se pose dans l'intervalle.

### Le contrat et les types générés voyagent ensemble

`webapp/shared/types/api.ts` est **produit** à partir d'`api/openapi.json`, et la CI vérifie qu'il
n'a pas dérivé. Les séparer en deux commits laisserait le dépôt rouge entre les deux, sur une branche
qui n'a rien fait de mal.

Cette carte touche donc `webapp/shared/`, qui n'est pas le périmètre habituel de la lane. C'est la
règle du transverse appliquée à l'endroit : le fichier **est** le contrat, et le contrat appartient à
l'API. Le point est nommé ici pour qu'il ne se découvre pas au moment de la relecture.

### Le contrat ne le déclarait pas — découvert à l'implémentation

Vérification faite : le schéma `Suggestion.CollectionOutput` de l'OpenAPI n'expose que l'enveloppe
(`items`, `total`, `page`, `perPage`), et ses `items` sont **opaques**. `profileCount` n'y a donc
jamais figuré. C'est le troisième cas de suite — `API-022` et `API-026` l'avaient rencontré sur la
fiche candidat.

Deux conséquences. La régénération d'`api/openapi.json` et des types TS reste due, mais elle ne
déplace que des **descriptions** ; et le champ que le front connaissait venait en réalité de la
déclaration **écrite à la main** dans `webapp/shared/types/search.ts`, qui est donc le vrai endroit
du retrait.

Ce que ça change pour la preuve : le contrat ne peut rien prouver ici. Seule la **réponse HTTP
réelle** le peut.

### On retire le champ, on ne le met pas à zéro

Un `profileCount: 0` uniforme serait pire que le retrait : il occuperait le contrat, mentirait sur ce
qu'il décrit, et laisserait croire à un défaut de calcul. Un champ qui n'a plus de sens se supprime.

### Ce qu'on perd, et où c'était déjà écrit

L'avertissement d'`API-009` — « communication » couvre 28 % du vivier, et sans le nombre en face on
pose ce filtre en croyant restreindre — disparaît pour de bon. La décision et sa contrepartie sont
prises dans `ENT-044`, *Décisions de cadrage* ; elles ne sont pas rejouées ici.

## Règles et cas limites

- **La régénération du contrat se fait sur un cache vidé.** Sur une stack de worktree fraîchement
  démarrée, un cache Symfony périmé fait ressortir le contrat du code d'avant — piège constaté
  pendant `API-022`, qui a failli réintroduire un champ supprimé deux jours plus tôt.
- **Le cache de l'environnement de TEST se vide aussi.** Retirer une propriété d'un objet de sortie
  laisse le sérialiseur la chercher tant que son cache n'a pas été refait : neuf tests sont tombés en
  `500 — Can't get a way to read the property "profileCount"`, alors que la route servait déjà la
  bonne réponse en `dev`. `cache:clear --env=test` règle la chose ; c'est le code qui était bon.
- **La CI a un `git diff --exit-code` sur `api/openapi.json`** que le `make` local ne couvre pas :
  vert en local ne veut pas dire vert en CI si le fichier n'a pas été régénéré.
- **L'ordre des propositions ne doit pas bouger.** C'est la seule preuve observable, une fois le
  champ parti, que le décompte est toujours calculé : les plus portées restent en tête.
- **Aucun autre consommateur.** Vérifié : le champ n'est lu que par `FiltersPrimary.vue`, que
  `ENT-044` aura déjà nettoyé. À revérifier au moment de faire, la lane entreprise ayant pu avancer.

## Documentation impactée

- **Le commentaire de `SuggestionFinder`**, qui présente le décompte comme servi au recruteur
  (« LE COMPTE N'EST PAS UN ORNEMENT ») ; il devient un critère de tri interne.
- **Le commentaire de `SuggestionOutput`** et celui de `webapp/shared/types/search.ts`.
- **`api-routes.md`** — la ligne de `/v1/suggestions`, ajoutée par `API-023`, ne doit pas annoncer un
  champ retiré.
- **Vérifié, non impacté** : `data-model.md` (aucune table, aucune colonne), `data-contracts.md` (la
  règle « la source est l'API, les types sont générés » ne change pas, elle s'applique).

## Définition de terminé

- [x] `GET /v1/suggestions` ne renvoie plus `profileCount` — **sortie de la requête réelle à
      l'appui**.
- [x] L'ordre des propositions est identique à avant : les plus portées en tête.
- [x] Un métier que personne n'exerce n'est toujours jamais proposé.
- [x] `api/openapi.json` régénéré sur un cache vidé, et `webapp/shared/types/api.ts` avec lui, dans
      le **même** commit.
- [x] Plus aucune référence à `profileCount` dans le webapp ni dans l'API, hors calcul interne.
- [x] `SuggestionTest` passe, avec ses assertions retournées.

## Vérification

**La route réelle, sur la stack de ce worktree**, avec un compte recruteur. Deux métiers posés
exprès à des volumes différents — « Développeur mobile » 3 porteurs, « Développeur logiciel » 1 :

```
GET /v1/suggestions?type=JOB&q=dev
{"items":[{"value":"Développeur mobile"},{"value":"Développeur logiciel"}],"total":2,"page":1,"perPage":2}
```

Deux choses s'y lisent, et la seconde est la moins évidente :

- **le champ a disparu** — la proposition ne porte plus que son terme ;
- **le classement tient toujours** : « mobile » passe devant « logiciel » alors que le départage
  alphabétique dirait l'inverse. Seul le nombre de porteurs, resté en base, explique cet ordre. C'est
  la seule trace observable du décompte, et donc la seule preuve qu'il n'a pas été perdu.

Le premier essai, volumes non inversés, ne prouvait rien : l'ordre y était compatible avec
l'alphabet. Il a été refait.

**`SuggestionTest` : 12 tests, 68 assertions, vert.** Les trois assertions sur le nombre sont
**retournées** — elles vérifient désormais son absence — et un test s'ajoute pour l'ordre, construit
de sorte que l'alphabet joue en sens contraire.

**`api/openapi.json` régénéré sur cache vidé**, et `webapp/shared/types/api.ts` avec lui : ils ne
bougent que par leurs **descriptions**, pour la raison expliquée en *Décisions de cadrage*.

**Plus aucune référence à `profileCount`** dans l'API ni dans le webapp, hors la variable locale
homonyme de `ConceptSimilarityCalculator`, sans rapport.

## Livré

- `api/src/ApiResource/Output/SuggestionOutput.php` **(M)** — le champ part ; le doc-block dit
  pourquoi, et que le décompte n'a pas disparu pour autant.
- `api/src/State/Provider/SuggestionProvider.php` **(M)** — construit la proposition depuis un simple
  terme.
- `api/src/Domain/Search/SuggestionFinder.php` **(M)** — rend `list<string>` ; le décompte s'arrête
  au SQL, où il classe. Le doc-block « LE COMPTE N'EST PAS UN ORNEMENT » devient « il ne sort plus
  d'ici, mais il commande toujours ».
- `webapp/shared/types/search.ts` **(M)** — la déclaration écrite à la main, d'où venait réellement le
  champ côté front.
- `api/openapi.json` **(M)** et `webapp/shared/types/api.ts` **(M)** — régénérés, descriptions seules.
- `api/tests/Functional/SuggestionTest.php` **(M)** — 3 assertions retournées, 1 test ajouté.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` **(M)** — la ligne de la route cessait d'être
  vraie : elle annonçait que chaque proposition porte son nombre de profils.

## Écart au cadrage

### Le périmètre s'élargit d'un fichier, pour que la carte dise vrai

La carte plaçait `SuggestionFinder` hors périmètre en écrivant que le décompte « devient interne ».
Laissé tel quel, il aurait continué de remonter du SQL jusqu'en PHP pour n'être lu par personne :
« interne » aurait été approximatif, et la donnée morte se serait vue en relecture.

Il rend donc `list<string>`. Le calcul, lui, n'a pas bougé d'un caractère — il est dans le `ORDER BY`,
et c'est bien lui qu'on garde.

### Le cache de test a fait tomber neuf tests sur du code juste

Après le retrait de la propriété, neuf tests sont sortis en `500 — Can't get a way to read the
property "profileCount"`, alors que la route servait déjà la bonne réponse en `dev`. Le sérialiseur
d'API Platform garde en cache la façon de lire chaque objet de sortie, et j'avais vidé le cache de
`dev` sans penser à celui de `test`.

C'est un cousin du piège d'`API-022` (contrat régénéré depuis un cache périmé), et il mérite d'être
retenu séparément : **retirer une propriété d'un objet de sortie demande de vider les DEUX caches**.
La règle est ajoutée aux *Règles et cas limites*.
