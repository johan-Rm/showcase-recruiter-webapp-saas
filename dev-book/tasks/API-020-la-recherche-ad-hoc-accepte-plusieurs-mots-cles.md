# TASK API-020 — la recherche ad hoc accepte plusieurs mots-clés

- **Créée le** : 2026-08-02.
- **Session** : `57a6103a-e57e-429e-82e9-aed454da48f3`.
- **Statut** : ✅ fait le 2026-08-02 — l'endpoint cumule les mots-clés en OU, et le formulaire peut
  enfin faire compter la combinaison qu'il détient.
- **Type** : Backend API — alignement d'un paramètre de recherche.
- **Tests** : fonctionnels sur `/v1/search/candidates` — un mot-clé, un autre, puis les deux, avec le
  total combiné **supérieur à chacun pris seul**. C'est ce qui prouve le OU et non le « dernier
  gagne », et aucune autre forme ne le prouverait : le provider lit la requête HTTP, il n'est pas
  atteignable en unitaire sans un refactor que le besoin ne réclame pas.
- **Réf** : **aval `ENT-034`** (le volume lu avant de lancer, que cette carte débloque) ·
  [CandidateSearchProvider.php](../../api/src/State/Provider/CandidateSearchProvider.php) ·
  [SearchCriteriaTranslator.php](../../api/src/Domain/Search/SearchCriteriaTranslator.php) —
  `q` y est déjà la seule facette multivaluée ·
  [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md)

## Contexte

**Le moteur sait relier plusieurs mots-clés ; l'endpoint ad hoc ne sait pas les lui donner.**

`SearchCriteriaTranslator` traite `q` comme la **seule facette multivaluée** : les autres suivent le
« dernier gagne », lui accumule. Une recherche enregistrée exprime donc « php **ou** java » sans
difficulté.

`GET /v1/search/candidates`, qui prend ses filtres en paramètres d'URL, ne lit qu'**une** valeur de
`q`. Mesuré sur le vivier de ce worktree, 3 053 candidats :

| Requête | Total |
| --- | --- |
| `?q=php` | 193 |
| `?q=java` | 435 |
| `?q=php&q=java` | **435** — un seul terme survit |

*(La forme qui cumule est `?q[]=`, pas `?q=` répété — voir l'écart au cadrage.)*

Le commentaire du code assume ce choix : « `?q=` reste un mot-clé unique côté moteur : il n'y a pas
de recherche enregistrée pour en accumuler plusieurs. » L'argument tenait quand cet endpoint ne
servait qu'à une recherche libre. Il ne tient plus depuis que le **formulaire accumule des
mots-clés** et a besoin de faire compter la combinaison qu'il détient.

**Ce que ça bloque.** `ENT-034` veut afficher le volume **avant** de lancer, parce que « lancer, c'est
créer » : une recherche s'auto-enregistre au premier critère, et trois tâtonnements laissent trois
recherches à nettoyer. Le comptage sans persistance existe déjà — cet endpoint rend `total` sans rien
écrire —, mais il ne sait pas compter ce que le formulaire contient.

## Périmètre

`CandidateSearchProvider::criteria()` lit **tous** les `q` de la requête au lieu du dernier, et les
passe au moteur qui sait déjà les relier.

C'est un **alignement de l'ad hoc sur l'enregistré**, pas une fonction neuve : le OU existe, le
plafonnement de page existe, la sécurité existe. Seule la lecture du paramètre est en cause.

## Hors périmètre

- **Les agrégats par facette** — « chaque option affiche son résultat, tous les autres filtres
  appliqués sauf le sien » : un moteur à part, une requête par facette ou une agrégation dédiée, dont
  la performance est à mesurer avant d'être promise. Ils débloquent `ENT-033` et feront leur propre
  tâche.
- **Un endpoint de comptage dédié.** Écarté : l'existant répond déjà, et une route de plus se
  documente, se teste et se sécurise pour économiser **un** profil hydraté (`perPage=1`). À rouvrir
  si le coût d'hydratation se révèle gênant à l'usage, mesures à l'appui.
- **La carte `ENT-034`**, dont la section « Le blocage » affirme qu'aucun comptage n'existe — c'est
  faux, et ce sera réécrit dans le commit de sa propre tâche.
- **Les autres facettes**, qui restent mono-valuées : c'est le contrat du moteur, pas un oubli.

## Décisions de cadrage

### Compléter l'existant plutôt qu'ajouter une route

Le cadrage est parti de « créer un endpoint de comptage », et la lecture du code l'a démenti :
`/v1/search/candidates` accepte déjà **dix-huit filtres** en paramètres et rend `total` sans rien
persister. Vérifié en réel avant d'écrire cette carte — `educationLevel=MASTER` → 1 885,
`job=Chef de projet` → 515, `&experienceMin=10` → 199.

Le manque était donc bien plus étroit qu'annoncé. Ajouter une route pour ce qu'une route existante
fait déjà aurait créé une seconde surface à maintenir, à documenter et à sécuriser, pour un bénéfice
d'un profil hydraté.

### La forme est `?q[]=`, pas `?q=` répété — écart au cadrage

Cette carte annonçait `?q=php&q=java`, sur la foi d'une mesure faite avant de l'écrire. **C'est
impossible en PHP** : les clés répétées d'une chaîne de requête sont écrasées à l'analyse, bien avant
qu'aucun code applicatif ne les voie. Ce que j'avais lu comme « le dernier gagne côté moteur » était
en réalité « le dernier gagne côté parseur » — le symptôme était juste, la cause fausse.

Seule la notation crochets produit un tableau. L'endpoint accepte donc **les deux formes** :
`?q=php` reste valide — il sert aussi une recherche libre à un seul terme —, et `?q[]=php&q[]=java`
cumule.

**Conséquence pour l'aval** : `ENT-034` devra émettre `q[]`, pas `q` répété.

Second piège, attrapé par les tests : `$request->query->all('q')` **lève une erreur sur un scalaire**
et aurait transformé `?q=php` en réponse **400**. Le premier jet le faisait. On lit donc la valeur
brute et on accepte les deux types.

### Aucun plafond sur le nombre de mots-clés — risque assumé

Chaque mot-clé ajoute une condition de recherche plein texte, et cet endpoint est ouvert à tout
porteur de jeton valide. Une URL forgée avec cent `q` fera donc travailler la base.

Le plafond a été **écarté délibérément** : une recherche enregistrée n'est pas bornée non plus, et
plafonner ici créerait une asymétrie de plus entre l'ad hoc et l'enregistré — exactement ce que cette
carte vient supprimer. Le risque est réel, il est nommé ici, et il se rouvrira si l'usage le montre.

Un plafond qui **tronque en silence** avait été écarté d'emblée : un compte tronqué sans le dire
serait faux, et un compte faux vaut moins que pas de compte.

## Règles et cas limites

- **Valeur vide ou blanche** : ignorée, comme aujourd'hui — un critère sur la chaîne vide ne filtre
  rien et fausserait le compte.
- **Doublons** (`q=php&q=php`) : sans effet sur le total, le moteur les relie en OU.
- **Aucun `q`** : comportement inchangé, les autres facettes s'appliquent seules.
- **Un seul `q`** : le total doit rester **identique** à aujourd'hui — c'est la non-régression qui
  compte le plus, l'endpoint sert aussi ailleurs que dans le formulaire.
- **Permissions inchangées** : recruteur authentifié et validé, comme pour toute recherche.
- **Pagination inchangée** : `perPage` reste plafonné à 2 000.

## Documentation impactée

- [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) — la ligne de
  `/v1/search/candidates`, si elle décrit la cardinalité de `?q`.
- `api/openapi.json` régénéré **si** le contrat porte ce paramètre — à vérifier à l'implémentation,
  pas à promettre ici. C'est du contenu de commit, pas une gate.

## Définition de terminé

- [x] `?q[]=` cumulé rend un total **supérieur** à chacun pris seul.
- [x] Un seul `q` rend **exactement** le même résultat qu'avant — non-régression prouvée.
- [x] Le commentaire qui justifiait le mot-clé unique est corrigé, et dit désormais pourquoi
      `?q=` répété ne cumulera jamais.
- [x] Les tests fonctionnels couvrent les trois cas, plus la valeur vide.
- [x] `make api-quality` — vert à l'ouverture de la PR : phpstan **0 erreur**, php-cs-fixer **0 fichier
      à corriger**, deptrac **0 violation**, phpunit **OK (394 tests, 1995 assertions)** en 41 s.

## Vérification

**3 tests, 19 assertions, verts** — sortie lue le 2026-08-02, sur les fixtures où Yassine porte
`PHP` et Imane `Sage` :

| Requête | Total | Ce que ça prouve |
| --- | --- | --- |
| `?q=PHP` | 1 | Yassine seul |
| `?q=Sage` | 1 | Imane seule |
| `?q[]=PHP&q[]=Sage` | **2** | le OU — avec l'ancien code le total serait resté à 1 |

Le troisième cas est le seul qui distingue un OU d'un « un seul terme passe » : deux profils y
répondent là où chaque terme pris isolément n'en rend qu'un. S'y ajoutent la **non-régression** — un
seul terme rend le même profil qu'avant — et une **valeur vide écartée** plutôt que cherchée.

**`api/openapi.json` n'a pas bougé, et n'avait pas à bouger** : le contrat ne décrit pas le paramètre
`q` — vérifié, zéro occurrence —, et le changement vit dans une méthode privée de provider, hors de
toute métadonnée API Platform.

## Livré

- `api/src/State/Provider/CandidateSearchProvider.php` **(M)** — les mots-clés se lisent tous, dans
  les deux notations ; une méthode dédiée porte la règle et son piège.
- `api/tests/Functional/CandidateTest.php` **(M)** — trois tests : le cumul, la non-régression, la
  valeur vide.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` **(M)** — la cardinalité de `?q` est dite.
- `dev-book/tasks/API-020-…md` **(+)** — cette carte.

## Aval

- **`ENT-034`** — le volume lu avant de lancer, débloquée par cette carte. Sa section « Le blocage »
  est à réécrire : elle affirme qu'aucun comptage n'existe.
- **Les agrégats par facette**, pour `ENT-033` — une tâche API à part entière.
- **Le plafond de mots-clés**, à rouvrir si l'usage montre que l'absence de borne coûte.
