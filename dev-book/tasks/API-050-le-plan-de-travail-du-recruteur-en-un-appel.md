# TASK API-050 — le plan de travail du recruteur, en un appel

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — la route sert le plan de travail en un appel, à nombre de
  requêtes constant ; deux N+1 découverts en mesurant ont été corrigés au passage.
- **Type** : Backend API — nouvelle route de lecture, agrégée, sans changement de modèle.
- **Tests** : fonctionnels phpunit — seules les recherches ouvertes visibles du recruteur sont
  servies ; les fiches sont dédoublonnées ; la borne de cent par recherche est respectée ; le nombre
  de requêtes SQL ne croît pas avec le nombre de recherches.
- **Réf** : **amont `API-047`** (les listes d'état, dont cette route reprend la forme) et **`API-048`**
  (la borne de cent) · **`API-049`**, qui borne ce que cette route transporte · aval `ENT-127` (le
  palier d'arrière-plan qui la consomme) · `api/src/State/Provider/SavedSearchesProvider.php` (le
  patron de comptage groupé à tenir).

## Contexte

Ouvrir une recherche coûte aujourd'hui un appel, même quand le recruteur en ouvre cinq d'affilée dans
la même session. Or **ses recherches ouvertes forment un plan de travail** dont la taille est modeste,
à condition de le mesurer correctement — c'est-à-dire **en fiches uniques** et non en appartenances :

```
55 recherches ouvertes d'un même compte, bornées à cent
   2 162 appartenances
   1 373 fiches UNIQUES        ← un candidat sert 1,6 recherche en moyenne
   ≈ 1,6 Mo bruts  /  ≈ 350 Ko compressés
```

Avec la clôture automatique d'`API-049`, ce nombre tombe à une poignée de recherches, soit de l'ordre
de cent à deux cents kilo-octets. À ce prix-là, **l'espace peut tenir tout son plan de travail en
mémoire** et n'appeler le serveur pour aucune ouverture de recherche.

Le client sait déjà ranger cette matière : ses stores gardent un **pool de fiches** d'un côté et des
**listes d'identifiants par recherche** de l'autre. Aucune route ne sert ce découpage — le front le
reconstruit à chaque réponse.

## Périmètre

- **Une route de lecture agrégée** servant, pour le recruteur courant, **ses recherches ouvertes** —
  les siennes et celles que l'équipe a partagées, même périmètre de visibilité que la collection.
- **Sa réponse porte deux choses** :
  - `searches` — un objet par recherche : son identifiant, `resultIds`, `favoriteIds`,
    `dismissedIds`, `seenIds`, exactement la forme d'`API-047` ;
  - `candidates` — **les fiches, dédoublonnées**, masquage appliqué comme partout ailleurs.
- **L'explication des critères voyage avec la recherche**, indexée par profil (`matches`) : décidée à
  l'implémentation, cf. décisions.
- **La borne de cent par recherche est héritée** d'`API-048`.
- **`api/openapi.json` régénéré.**

## Hors périmètre

- **`GET /v1/searches` ne change pas** : la collection porte les noms, les critères et les compteurs,
  et elle reste sur le chemin critique de l'accueil. Cette route porte le **contenu**, en arrière-plan.
- **Les fiches des profils écartés** : ils sont hors du jeu, `dismissedIds` en donne les identifiants,
  la corbeille garde son appel dédié.
- **Toute pagination et toute borne sur le nombre de recherches** : la clôture automatique est la
  borne, décision de cadrage.
- **Le déclenchement côté client** : c'est `ENT-127`.
- **Les recherches closes**, qui ne font pas partie du plan de travail.

## Décisions de cadrage

### La forme épouse les stores, pas les écrans

Un pool de fiches et des listes d'identifiants : c'est le découpage que le client reconstruit déjà à
chaque réponse. Servir ce découpage évite un troisième format à traduire, et rend la route lisible
sans connaître l'interface.

### Aucune borne dans la route

Toutes les recherches ouvertes partent, quel qu'en soit le nombre. Un plafond posé ici serait un
nombre magique invisible pour l'utilisateur, à ajuster au doigt mouillé ; la règle de produit — une
recherche qu'on ne touche plus se ferme — borne la même chose et s'explique. Le jour où un compte en
garde cinquante vivantes, l'inventaire administrateur le montre.

### C'est une photo, et ça se dit

Un collègue qui retient un profil pendant la session ne se verra pas dans un plan de travail déjà
chargé. C'est exactement la fraîcheur d'aujourd'hui pour une recherche ouverte, ni plus ni moins : la
route ne dégrade rien, elle ne promet rien non plus.

### L'explication des critères voyage avec la recherche — décidé à l'implémentation

Mesure faite avant de trancher : **l'explication pèse 42 % d'une carte** — 890 octets sur 2 095 — et
elle est **propre à chaque recherche**. Le même profil qui répond à deux recherches en a deux, ce qui
la rend seule inéligible au dédoublonnage.

Trois chemins se présentaient : la servir à côté du pool (le plan double, ~2,3 Mo), ne pas la servir
(le plan tombe à 1,3 Mo mais la Visionneuse perd son verdict tant qu'elle n'a pas demandé son jeu),
ou renoncer au dédoublonnage (4,3 Mo, le pire des trois). **Le premier a été retenu** : ouvrir une
recherche préchargée ne coûte alors plus rien du tout, verdict compris.

La fiche du pool, elle, ne porte **aucune** explication — une explication posée sur une fiche
dédoublonnée n'en garderait qu'une et mentirait sur les autres.

### Une route littérale contre une route paramétrée — décidé à l'implémentation

`/searches/workspace` était avalée par `/searches/{id}` : le fournisseur de recherche recevait
« workspace » comme identifiant et rendait 404. La priorité d'opération n'y change rien.

Réglé en disant au routeur ce qu'un identifiant **est** — `requirements: ['id' => '[0-9a-fA-F-]{36}']`
sur la route item. Plus robuste qu'un ordre de déclaration, qui se serait défait au prochain ajout.

### Deux requêtes, jamais cinquante-cinq

Le danger de cette route est le N+1 — une requête par recherche. Le fournisseur de la collection sait
déjà compter en une passe groupée pour toutes les recherches d'un coup ; c'est ce patron qu'il faut
tenir, et un test doit l'empêcher de se dégrader.

## Règles et cas limites

- **Aucune recherche ouverte** : réponse valide, `searches` et `candidates` vides.
- **Recherche partagée** : les retenus sont ceux de l'auteur, l'avancement — vus, écartés — est celui
  du lecteur courant, comme sur `/results`.
- **Un candidat présent dans huit recherches** apparaît **une seule fois** dans `candidates`, et huit
  fois dans les `resultIds` correspondants.
- **Masquage** : identique à celui de `/results`, aucun champ de contact ne fuit par cette route.
- **Organisation rejetée** : accès refusé, comme la collection.
- **Volume** : la réponse n'est pas paginée ; sa taille suit le nombre de recherches ouvertes, ce qui
  est assumé et borné par `API-049`.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle route, et **la remise à jour du
  périmètre recherche** : la page listait encore `/organizations/{uuid}/saved-searches`, un chemin
  qui n'existe plus.
- `api/openapi.json` — régénéré, la route et ses deux schémas y entrent.

## Vérification

**Requêtes réelles**, stack du worktree API, sur le compte le plus chargé de la base :

```
GET /v1/searches/workspace   HTTP 200 · 4,3 s · 2,4 Mo

56 recherches ouvertes · 1 794 appartenances · 1 099 fiches uniques   (dédoublonnement ×1,63)
2,28 Mo bruts · 308 Ko compressés
   pool de fiches   1,25 Mo
   explications     1,08 Mo
```

Forme servie, vérifiée sur la réponse : chaque recherche porte `resultIds`, `favoriteIds`,
`dismissedIds`, `seenIds` **et** ses `matches` indexées par profil ; la fiche du pool n'en porte
aucune (champ absent).

**Deux N+1 trouvés en mesurant, et corrigés** :

```
première version    2 386 requêtes · 9,2 s
après correction        68 requêtes · 4,3 s
```

- la **mise en carte** lisait trois collections par profil — présences, langues, secteurs — soit
  ~3 300 requêtes sur un millier de fiches. Le pool les précharge en trois requêtes ;
- l'**explication d'un critère de poste** parcourait les métiers exercés, que le préchauffage du
  `CriterionMatcher` ne couvrait pas.

Les deux corrections profitent au jeu de résultats, qui emprunte les mêmes chemins.

**Tests joués**

```
phpunit SearchWorkspaceTest                                     OK (6 tests, 55 assertions)
phpunit Search{LoadLimit,WorkState,,InactivityClosure}Test + CandidateTest
                                                                OK (65 tests, 547 assertions)
```

## Livré

- `api/src/ApiResource/Output/SearchWorkspaceOutput.php` et `SearchWorkspaceEntryOutput.php` — la
  route et sa forme.
- `api/src/State/Provider/SearchWorkspaceProvider.php` — le périmètre du lecteur.
- `api/src/Domain/Search/SearchWorkspaceBuilder.php` — l'assemblage, à requêtes groupées.
- `api/src/Repository/` — trois lectures groupées : identifiants de plusieurs jeux, retenus de
  plusieurs recherches, vus et écartés de plusieurs recherches ; plus `findByUuids` et son
  préchargement des collections de carte.
- `api/src/Domain/Search/CriterionMatcher.php` — le préchauffage couvre aussi les métiers exercés.
- `api/src/ApiResource/Output/SavedSearchOutput.php` — l'identifiant de la route item est un uuid.
- `api/tests/Functional/SearchWorkspaceTest.php` — six cas, dont **le compte de requêtes qui ne suit
  pas le nombre de recherches**.

## Reste à faire (hors périmètre)

- **4,3 secondes sur ce compte**, et c'est assumé : le coût est désormais du calcul — 1 794
  explications à produire — et non des allers-retours. Ce compte est une valeur aberrante, celle-là
  même que la clôture automatique (`API-049`) ramène à une poignée de recherches ; à dix recherches
  ouvertes, le même calcul passe sous la seconde. L'appel étant d'arrière-plan, il ne bloque aucun
  rendu — mais si beaucoup de comptes ressemblaient à celui-ci, il faudrait y revenir.
- **Le déclenchement côté client** : c'est `ENT-127`.
- **Les fiches des écartés** ne sont pas dans le pool — ils sont hors du jeu, la corbeille garde son
  appel.

## Définition de terminé

- [x] La route sert les recherches ouvertes visibles du recruteur, et elles seules.
- [x] Les fiches sont dédoublonnées ; un candidat partagé entre plusieurs recherches n'apparaît
      qu'une fois, et autant de fois qu'il le faut dans les appartenances.
- [x] Chaque recherche porte ses quatre listes d'identifiants, à la forme d'`API-047`, **plus ses
      explications de critères** — ajout décidé à l'implémentation.
- [x] La borne de cent par recherche est respectée.
- [x] Un compte sans recherche ouverte reçoit une réponse vide et valide.
- [x] Le nombre de requêtes SQL ne croît pas avec le nombre de recherches, et un test le retient.
- [x] `api/openapi.json` régénéré, `api-routes.md` à jour sur le périmètre recherche.
