# TASK API-048 — le jeu servi s'arrête à cent profils, le total dit le vrai nombre

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — une recherche sert cent profils au plus, `total` continue de
  porter le nombre réel de correspondances, et la limite voyage dans `/v1/config`.
- **Type** : Backend API — borne posée sur ce qui est servi, sans toucher au calcul ni au stockage.
- **Tests** : fonctionnels phpunit — une recherche de plus de cent correspondances sert cent
  identifiants et un `total` réel ; une page au-delà des cent revient vide ; une recherche courte est
  inchangée ; la limite est lisible dans `/v1/config`.
- **Réf** : aval `ENT-126` (l'écran qui charge en un appel), `API-050` (le plan de travail, qui hérite
  de cette borne), `API-051` (l'inventaire des recherches qui débordent) ·
  `api/src/State/SearchResultsBuilder.php` · `API-006` (l'ordre du jeu, inchangé).

## Contexte

**Le jeu d'une recherche n'a aucune borne**, et la mécanique de pagination existe pour cette seule
raison. Sur la base de démonstration : 79 recherches, 8 503 appartenances matérialisées, une moyenne
de 109 profils, 149 au 90ᵉ centile — et **une recherche à 3 239 profils**.

Cette valeur aberrante coûte à chaque page servie :

```
/results, page 1 d'une recherche à 3 239 profils
  réponse entière   167,1 Ko brut  →  59,6 Ko compressée
    dont resultIds  126,5 Ko brut  →  51,5 Ko compressée   ← 87 % du poids
    dont 30 fiches   40,5 Ko brut  →   7,8 Ko compressée
```

Elle coûte surtout **au recruteur** : une recherche à un seul critère large ne distingue rien, et
l'écran la présente comme un travail de plusieurs jours. Onze recherches sur 79 dépassent cent
profils — 13,9 %.

La décision produit est de **charger cent profils au plus**, et de **continuer à dire le vrai
nombre**. Cent fiches tiennent alors dans un seul appel : ≈ 135 Ko bruts, 26 Ko compressés.

## Périmètre

- **`GET /v1/searches/{id}/results` borne le jeu exposé aux cent premiers** de l'ordre existant :
  `resultIds` en porte cent au plus, et aucune page ne sert au-delà de ce rang.
- **`total` continue de porter le nombre réel de correspondances** (352, pas 100). C'est lui qui
  permet à l'écran d'annoncer « 100 chargés sur 352 », et à l'administration de repérer les
  recherches qui débordent.
- **La limite est exposée dans `/v1/config`**, à côté de `perPage` : l'écran demande une page de
  cette taille sans coder le nombre en dur.
- **Les périmètres `?scope=favorites` et `?scope=dismissed`** sont bornés de la même façon.

## Hors périmètre

- **La matérialisation.** Le jeu continue d'être calculé et stocké en entier : c'est ce qui rend le
  `total` gratuit, l'inventaire d'`API-051` gratuit, et la décision réversible.
- **Le recalcul à l'import Jarvi**, qui continue de porter sur le jeu entier.
- **L'affichage** de « 100 sur 352 » : c'est `ENT-126`.
- **Refuser l'enregistrement d'une recherche trop large** : écarté, cf. décisions.
- **La clôture automatique** (`API-049`) et **le plan de travail agrégé** (`API-050`).

## Décisions de cadrage

### La borne porte sur ce qui est servi, pas sur ce qui est calculé

Trois bénéfices tiennent à ce choix : le `total` reste gratuit (aucune colonne, aucune migration),
l'inventaire administrateur se lit sur ce même compte, et **rien n'est perdu** — relever la limite ou
ouvrir un « voir au-delà » ne demanderait qu'un changement de borne, la matière étant toujours là.

Ce qu'on n'y gagne pas, et il faut l'assumer : le stockage ne bouge pas, et le recalcul non plus.

### Les cent servis sont les cent premiers, sans qualificatif

Ce sont les cent premiers de l'ordre du jeu, celui qu'`API-006` a posé. **Aucune notion de « cent
meilleurs » n'est introduite** : sur une recherche à un seul critère, tous les profils satisfont le
même critère et l'ordre ne distingue rien. C'est précisément pour ça que le total réel reste dit —
l'écran ne prétend pas avoir choisi, il annonce ce qu'il montre et ce qui existe.

### La borne se pose en un seul point — décidé à l'implémentation

Elle vit dans le constructeur du jeu, juste après la lecture de la page et **avant** toute mise en
carte : les lignes au-delà du centième rang ne chauffent pas l'explication des critères et ne sont
pas mappées pour être jetées. Conséquence directe : elle vaut pour les trois périmètres sans qu'aucun
n'ait à la connaître, et la règle « borner ce qui reste après avoir sorti les écartés » devient vraie
par construction plutôt que par vigilance.

La liste d'identifiants, elle, est bornée **en SQL** : la couper en PHP aurait fait remonter les
milliers d'identifiants d'une recherche large pour n'en garder que cent.

### Aucun champ nouveau

`total` porte le réel, la longueur de `resultIds` porte le chargé. Les deux nombres suffisent à
écrire « 100 sur 352 » ; un troisième champ ne dirait rien de plus et resterait à tenir d'accord.

### Refuser l'enregistrement au-delà de cent : écarté

Le formulaire annonce déjà le volume pendant la saisie (`useSearchVolume` sur `/api/candidates/count`),
donc la garde aurait été jouable. Elle a été écartée pour deux raisons : elle ne garantit rien dans le
temps — le jeu se recalcule à chaque import Jarvi, une recherche créée à 95 profils passe à 120 toute
seule — et elle empêche un usage légitime, regarder large pour découvrir le vivier. Borner
l'affichage informe sans interdire.

## Règles et cas limites

- **Recherche courte** (80 profils) : `total` = 80, `resultIds` = 80. Rien ne change, et l'écran ne
  doit rien annoncer de particulier.
- **Recherche large** (352) : `total` = 352, `resultIds` = 100.
- **Les écartés sortent du jeu avant la borne** : la limite s'applique après exclusion, si bien qu'on
  sert toujours cent profils utiles, jamais cent moins les écartés.
- **`page=2` sur une recherche large** : réponse vide, pas d'erreur — la borne n'est pas un refus.
- **`perPage` demandé au-delà de la limite** : borné, sans erreur (`MAX_PER_PAGE` reste la borne dure
  du paramètre).
- **`resultCount` de la fiche de recherche** continue de porter le nombre réel : c'est déjà le cas, et
  c'est lui qui peint les cartes de la collection.
- **L'ordre ne change pas.** Cette tâche coupe, elle ne trie pas.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md` — la borne du jeu servi et la distinction
  entre `total` (réel) et `resultIds` (chargé).
- `api/openapi.json` — régénéré, contenu de commit.

## Vérification

**Requêtes réelles**, stack du worktree API (`db` + `api`), sur une base portant 3 053 candidats et
une recherche de **515 profils** — `owner@demo.showcase-recruiter-webapp-saas.example`, recherche `019fc316-d28f-…` :

```
GET /v1/searches/019fc316…/results        total=515  items=30   resultIds=100  perPage=30
GET …/results?perPage=100                 total=515  items=100  resultIds=100   ← un seul appel
GET …/results?page=2&perPage=100          total=515  items=0    resultIds=100   ← vide, pas d'erreur
GET …/results?page=4&perPage=30           total=515  items=10                   ← rangs 91 → 100
GET /v1/config                            perPage=30  searchLoadLimit=100
```

Recherche courte, pour vérifier qu'on ne coupe rien là où il n'y a rien à couper :

```
GET /v1/searches/019fd859…/results  (28 profils)   total=28  resultIds=28
```

Les deux périmètres répondent inchangés (`?scope=favorites`, `?scope=dismissed` : `HTTP 200`).
Poids de la page 1 de la recherche à 515 profils : **45 602 octets** — les 415 identifiants qui ne
partent plus valaient ~15,8 Ko bruts, et ils repartaient à chaque page.

**Tests joués**

```
phpunit --filter SearchLoadLimitTest                          OK (5 tests, 31 assertions)
phpunit Search{,FavoritesScope,ProfileReview,Lists,Counts,Export}Test
                                                              OK (43 tests, 406 assertions)
```

## Livré

- `api/src/State/SearchResultsBuilder.php` — la constante `LOADED_LIMIT`, la coupe des lignes posée
  **avant** la mise en carte, et le total laissé au jeu réel.
- `api/src/Repository/SearchResultMembershipRepository.php` — `orderedCandidateUuidsFor` accepte une
  borne, appliquée en SQL.
- `api/src/ApiResource/Output/SearchResultsOutput.php` — le commentaire qui promettait « tout le jeu »
  disait désormais faux : corrigé.
- `api/src/ApiResource/Output/ConfigOutput.php`, `api/src/State/Provider/ConfigProvider.php` —
  `searchLoadLimit` exposé.
- `api/tests/Functional/SearchLoadLimitTest.php` — cinq cas.
- `api/openapi.json` — régénéré.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md` — la règle du jeu borné.

## Reste à faire (hors périmètre)

- **L'écran ne dit pas encore « 100 sur 352 »** : c'est `ENT-126`, et jusqu'à sa livraison le
  recruteur voit cent profils sans savoir qu'il y en a plus. L'information est dans le contrat, pas
  encore à l'écran.
- **Un défaut croisé en chemin, hors de cette tâche** : `CandidateMapper` lit les coordonnées dans la
  réplica Jarvi sans vérifier qu'elle existe (`getJarvi()->contacts`). Un candidat sans réplica —
  celui qui s'inscrit lui-même, par exemple — ferait tomber la mise en carte. Découvert parce que les
  profils semés par le test n'en avaient pas ; contourné dans le test, non corrigé ici.

## Définition de terminé

- [x] Une recherche de plus de cent correspondances sert cent identifiants et un `total` réel.
- [x] Une page demandée au-delà des cent premiers revient vide, sans erreur.
- [x] Une recherche de moins de cent profils est servie exactement comme avant.
- [x] `?scope=favorites` et `?scope=dismissed` répondent inchangés — la borne leur est commune par
      construction (point unique), et prouver leur coupe demanderait cent retenus ou cent écartés
      dans les fixtures, pour une garantie que le code ne peut pas contredire.
- [x] La limite est lisible dans `/v1/config`.
- [x] Tests fonctionnels phpunit sur les cinq points ci-dessus.
- [x] `api/openapi.json` régénéré, `api-conventions.md` à jour.
