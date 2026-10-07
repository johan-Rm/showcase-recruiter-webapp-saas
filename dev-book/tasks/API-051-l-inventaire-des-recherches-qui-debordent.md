# TASK API-051 — l'inventaire des recherches qui débordent

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — l'inventaire liste les recherches qui débordent, du plus large
  au plus étroit, avec le nombre réel de correspondances que l'écran recruteur ne montre jamais.
- **Type** : Backend API — route d'administration, lecture seule.
- **Tests** : fonctionnels phpunit — seul un administrateur y accède ; seules les recherches
  au-dessus du seuil sont listées ; le tri et la pagination se comportent comme ailleurs.
- **Réf** : **amont `API-048`** (la borne de cent, et le seuil qui en découle) · aval `ADM-025`
  (l'écran) · `api/src/State/Provider/AdminVivierProvider.php` et les autres fournisseurs
  d'administration, pour la forme.

## Contexte

`API-048` borne à cent ce qu'une recherche sert, et continue d'annoncer le nombre réel de
correspondances. Le recruteur voit donc « 100 sur 352 » — mais **personne, côté showcase-recruiter-webapp-saas, ne voit
l'ensemble de ces débordements**.

Or ils disent deux choses utiles, et différentes. Une recherche à 352 profils est le plus souvent
**mal cadrée** : un critère unique et large, que le recruteur gagnerait à resserrer — c'est un signal
d'accompagnement. Mais une multitude de recherches qui débordent sur un même métier dit autre chose :
que le vivier y est abondant, ou que les critères proposés ne savent pas y distinguer.

Sur la base actuelle, **11 recherches sur 79 dépassent cent profils — 13,9 %**, dont une à 3 239.

## Périmètre

- **Une route d'administration** listant les recherches dont le nombre de correspondances dépasse la
  limite de chargement, toutes organisations confondues.
- **Chaque ligne porte** : l'organisation, le propriétaire, le nom de la recherche (ou ses critères
  quand elle n'en a pas), le **nombre réel de correspondances**, l'état ouvert ou clos, et la date de
  dernière activité.
- **Tri par nombre de correspondances décroissant** par défaut, enveloppe de collection habituelle.
- **`api/openapi.json` régénéré.**

## Hors périmètre

- **L'écran** : c'est `ADM-025`.
- **Toute action corrective** depuis l'administration — modifier, fermer ou supprimer la recherche
  d'un client. Cette route regarde, elle ne touche pas.
- **Un indicateur agrégé par métier** (« sur quels métiers les recherches débordent-elles ? ») :
  légitime, mais c'est une analyse, pas un inventaire ; elle se cadrerait avec les mesures du vivier.
- **Une alerte ou une notification** vers le recruteur concerné.

## Décisions de cadrage

### Le seuil est la limite de chargement, pas un nombre à lui

« Déborder » signifie exactement « le recruteur n'a pas tout vu ». Le jour où la limite bouge,
l'inventaire suit sans qu'on y touche : les deux valeurs ne peuvent pas diverger si elles n'en font
qu'une.

### L'inventaire est gratuit, et c'est un effet de `API-048`

Puisque le jeu continue d'être matérialisé en entier, le nombre réel de correspondances est déjà en
base. Aucun compteur à stocker, aucune migration : la route lit ce qui existe.

### La dernière activité figure dans la liste

Une recherche qui déborde et qu'on travaille tous les jours n'appelle pas la même attention qu'une
recherche qui déborde et que personne n'a rouverte depuis un mois. La colonne coûte peu et évite un
aller-retour vers une autre vue.

### Le seuil n'est pas un réglage de cet écran — décidé à l'implémentation

C'est **la borne de chargement elle-même**. Un inventaire avec son propre seuil finirait par lister
des recherches que le recruteur voit entièrement, ou par en taire qui débordent — et l'écart entre
les deux réglages ne se verrait qu'à la lecture d'une liste fausse.

### `servedCount` s'ajoute à la réponse — décidé à l'implémentation

La carte demandait le nombre réel. Le servir seul obligerait l'écran à connaître la borne pour dire
l'écart, donc à l'écrire une seconde fois. La réponse porte les deux nombres, comme le fait déjà le
jeu de résultats.

### Le nom n'est pas recomposé côté serveur — décidé à l'implémentation

Une recherche non renommée n'a pas de nom : son titre se compose de ses critères, et cette
composition vit dans l'écran. La route sert donc `name` **et** `criteria`, et laisse l'affichage
décider — recomposer ici ferait une seconde règle à tenir d'accord avec la première.

## Règles et cas limites

- **Accès réservé au rôle d'administration**, comme les autres routes du périmètre.
- **Les recherches supprimées** sont exclues ; **les recherches closes** sont listées et signalées
  comme telles — une recherche close qui débordait reste une information.
- **Aucune recherche au-dessus du seuil** : collection vide et valide, pas une erreur.
- **Recherche sans nom** : ses critères en tiennent lieu, comme partout ailleurs — la route sert les
  critères, la mise en mots reste à l'écran.
- **Volume** : la liste est paginée ; le comptage ne doit pas faire un `COUNT` par recherche, mais une
  passe groupée, comme le fournisseur de la collection.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle route, dans le tableau
  d'administration.
- `api/openapi.json` — régénéré, contenu de commit.

## Vérification

**Requêtes réelles**, base du worktree API :

```
GET /v1/admin/searches   HTTP 200 · 0,51 s
total 19 recherches débordent · page 1 de 20

   515 correspondances · 100 servies · close   · showcase-recruiter-webapp-saas Demo SARL   Sara Bennani      (Chef de projet)
   515 correspondances · 100 servies · ouverte · showcase-recruiter-webapp-saas — Équipe    Entreprise CAP N. (Chef de projet)
   493 correspondances · 100 servies · ouverte · showcase-recruiter-webapp-saas — Équipe    Entreprise CAP N. (Ingénieur d'études)
   468 correspondances · 100 servies · ouverte · showcase-recruiter-webapp-saas — Équipe    Entreprise CAP N. (Consultant)
```

Ordre décroissant vérifié sur la page entière.

**La garde**

```
administrateur → HTTP 200
recruteur      → HTTP 403
sans jeton     → HTTP 401
```

**Tests joués**

```
phpunit AdminOverflowingSearchTest                                OK (7 tests, 50 assertions)
phpunit AdminOverflowingSearch + SearchWorkspace + SearchLoadLimit  OK (18 tests, 137 assertions)
```

## Livré

- `api/src/Repository/SavedSearchRepository.php` — la requête des débordements (`HAVING` sur un
  agrégat), partagée par la page et son total.
- `api/src/ApiResource/Output/AdminOverflowingSearchOutput.php` — la route et sa forme.
- `api/src/State/Provider/AdminOverflowingSearchProvider.php` — la garde administrateur et la
  pagination.
- `api/tests/Functional/AdminOverflowingSearchTest.php` — sept cas, dont **le seuil est exactement la
  borne de chargement** : cent ne déborde pas, cent un si.
- `api/openapi.json` régénéré · `api-routes.md` complété.

## Reste à faire (hors périmètre)

- **L'écran** qui lira cet inventaire : c'est `ADM-025`, cadrée et non commitée.
- **Aucune action corrective depuis l'administration** : on regarde, on ne resserre pas la recherche
  de quelqu'un d'autre.
- **Un tri ou un filtre par organisation** : la liste est courte tant que le vivier l'est ; le jour où
  elle ne le sera plus, ce sera une tâche à part.

## Définition de terminé

- [x] La route liste les recherches dont le nombre de correspondances dépasse la borne de chargement,
      toutes organisations confondues.
- [x] Chaque ligne porte l'organisation, le propriétaire, le nom **et** les critères, le nombre réel,
      ce qui est servi, l'état et la dernière activité.
- [x] Le tri est décroissant sur le nombre réel, et stable — l'identifiant départage.
- [x] La collection est paginée, enveloppe habituelle, total hors pagination.
- [x] L'accès est réservé au rôle d'administration ; un recruteur reçoit 403, un anonyme 401.
- [x] Une base sans débordement rend une collection vide et valide.
- [x] Le comptage est une passe groupée, pas un `COUNT` par recherche.
- [x] Tests fonctionnels phpunit sur les points ci-dessus.
- [x] `api/openapi.json` régénéré, `api-routes.md` à jour.
