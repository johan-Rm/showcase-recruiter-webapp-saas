# TASK API-062 — les explications d'une recherche, par page

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-18 — `GET /v1/searches/{id}/explanations` sert les explications
  page par page, dans l'ordre du jeu, à coût de requêtes constant.
- **Type** : Nouvelle route — l'explication des correspondances se demande à part, par page.
- **Tests** : fonctionnels phpunit — la page rendue suit l'ordre du jeu ; chaque profil y porte le
  critère satisfait, le libellé du profil qui le satisfait et l'endroit où il a été vu ; un profil
  qu'aucun critère n'explique n'a pas d'entrée ; la route est gardée comme la recherche.
- **Réf** : **amont** `API-060` (l'ordre du jeu) · `API-057` (la traduction vers la forme publiée) ·
  **aval** l'écran, dans une tâche à venir.

## Contexte

L'explication dit **pourquoi ce profil répond à cette recherche** : le critère cherché, le libellé
du profil qui le satisfait — souvent différent, le dictionnaire de métiers ayant fait le
rapprochement — et l'endroit où il a été vu, accroche ou compétence ou poste occupé.

Elle appartient au couple recherche-profil, donc ni au profil seul ni à la recherche seule. Mesurée
à **601 octets par couple** : toutes celles d'un compte à 55 recherches pèsent 1,4 méga-octet, contre
90 kilo-octets pour toutes les listes d'identifiants. **Elles ne peuvent voyager ni avec l'état, ni
avec des fiches qui ignorent les recherches.**

## Périmètre

- **Une route qui rend les explications d'UNE recherche, par page**, dans l'ordre de son jeu.
- **Une page de trente coûte environ 18 kilo-octets** — l'ordre de grandeur qui rend la chose
  praticable à chaque palier de parcours.

## Hors périmètre

- **Les fiches** : `API-061`.
- **L'ordre et l'appartenance** : `API-060`.
- **Le moment où l'écran les demande** — à l'ouverture, en anticipation, ou au profil affiché : cela
  se décide côté écran.

## Décisions de cadrage

### Par page, et non par recherche entière

Dix-huit kilo-octets pour trente profils, contre plus de trois cents pour une recherche de cinq cent
vingt-cinq. Et la Visionneuse n'affiche qu'un profil à la fois : charger l'explication du cinq
centième avant d'avoir vu le premier n'a aucun sens.

### Calculées, jamais stockées

L'explication se recalcule à partir des critères de la recherche et du profil. La stocker créerait
une donnée à invalider chaque fois qu'un critère change ou qu'un profil est réimporté.

### Une entrée porte TOUS les critères, satisfaits ou non — écart au cadrage

La carte disait « un profil qu'aucun critère n'explique n'a pas d'entrée », ce qui se lisait aussi
comme « on ne sert que les critères satisfaits ». L'implémentation ne le fait pas, et ne pouvait pas
le faire : l'écran affiche un score — « 1 / 1 aligné sur vos critères » — qui a besoin des critères
**non** satisfaits pour se calculer. Les retirer aurait cassé la parité avec ce que la fiche portait
déjà, et un même profil aurait affiché deux verdicts différents selon la façon dont l'écran a chargé
son explication.

Une entrée est donc omise dans un seul cas : quand le calcul ne peut **rien** énoncer sur ce profil —
une recherche dont tous les critères sont inexprimables, `languageLevel` seul par exemple. L'absence
se lit alors « rien à dire », ce qui est vrai. Un test couvre exactement ce cas.

### La forme publiée, pas l'objet interne

La traduction vers la forme publiée existe déjà et est partagée — `API-057` l'a nommée précisément
pour qu'un second appelant s'en serve sans la recopier.

## Règles et cas limites

- **La page suit l'ordre du jeu**, celui de la route d'état : deux ordres différents rendraient les
  explications inutilisables.
- **Un profil qu'aucun critère n'explique n'a pas d'entrée** — l'absence se lit comme « aucun critère
  satisfait », ce qui est vrai.
- **Une page au-delà de la fin rend une collection vide.**
- **La route est gardée comme la recherche elle-même** : qui ne peut pas la voir ne peut pas voir ses
  explications.
- **Le coût de calcul est groupé** : les lectures que l'explication demande — les postes écrits d'un
  profil, notamment — se chargent en une fois pour la page, jamais profil par profil.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle route.
- `api/openapi.json` régénéré.

## Vérification

Appels réels sur la pile du worktree api, compte `owner@demo`, une recherche de **515 profils**.

**La forme servie :**

```
GET /v1/searches/{id}/explanations
clés enveloppe : ['items', 'total', 'page', 'perPage']
total=515  page=1  perPage=30  entrées=30
clés d'une entrée : ['id', 'matches']
  {"facet": "job", "value": "Chef de projet", "matched": true, "matchedOn": "Chef de projet"}
```

**L'ordre, confronté au jeu servi par la recherche :**

```
jeu de référence : 515 identifiants
page 1 : 30 expliqués sur 30 · suit l'ordre du jeu ? True
page 2 : 30 expliqués sur 30 · suit l'ordre du jeu ? True
pages disjointes ? True     total annoncé == taille du jeu ? 515 vs 515 → True
page 999 : 0 entrée, total toujours 515
perPage=9999 → borné à 200      perPage=0 → borné à 1
```

**La garde**, identique à celle de la recherche et indistincte de l'inexistence :

```
un recruteur d'une autre organisation : 404   (/results rend 404 aussi)
sans jeton du tout                    : 401
identifiant inexistant                : 404
le propriétaire                       : 200
```

**Le coût groupé** — le nombre de requêtes ne bouge pas quand la page grossit de 1 à 200 profils :

```
perPage=1 → 10 requêtes    perPage=30  → 10 requêtes    perPage=200 → 10 requêtes
perPage=5 → 10 requêtes    perPage=100 → 10 requêtes
```

Tests : `php bin/phpunit --filter SearchExplanationsTest` → **8 tests, 67 assertions, OK**. Les deux
exceptions visibles dans la sortie sont celles que le test de la garde provoque.

## Livré

- `api/src/ApiResource/Output/SearchExplanationsOutput.php` **(+)** — l'enveloppe paginée et la route.
- `api/src/ApiResource/Output/SearchExplanationOutput.php` **(+)** — une entrée : le profil, ses
  correspondances.
- `api/src/State/Provider/SearchExplanationsProvider.php` **(+)** — la garde, la page, le
  préchargement groupé, la traduction vers la forme publiée.
- `api/tests/Functional/SearchExplanationsTest.php` **(+)** — huit cas.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la route.
- `api/openapi.json` — régénéré.

## Reste à faire (hors périmètre)

- **Aucun écran ne demande encore ces explications.** Depuis que l'état de travail ne les porte plus,
  la Visionneuse et la Table ouvrent une recherche sans « pourquoi ce profil ». C'est la tâche de
  chargement anticipé côté entreprise qui décidera du moment de l'appel — elle reste à cadrer.

## Définition de terminé

- [x] La route rend les explications d'une recherche, par page, dans l'ordre du jeu.
- [x] Chaque entrée porte le critère, le libellé du profil qui le satisfait, et l'endroit.
- [x] Un profil sans critère satisfait n'a pas d'entrée ; une page hors limites rend une collection
      vide. **Nuance écrite en *Décisions de cadrage*** : l'entrée est omise quand rien ne peut être
      énoncé, pas quand un critère échoue — le score affiché a besoin des critères non satisfaits.
- [x] La garde d'accès est celle de la recherche.
- [x] Le nombre de requêtes en base ne dépend pas du nombre de profils de la page.
- [x] Tests fonctionnels sur ces quatre points.
- [x] Documentation et contrat régénéré à jour.
