# TASK API-034 — l'état de la base dit qui manque, et pourquoi

- **Créée le** : 2026-08-10.
- **Session** : `814dae9a-6227-4ac9-86f3-45b857b1124f`.
- **Statut** : ✅ fait le 2026-08-10 — la route rend la répartition par motif et la liste paginée
  pour les quatre métriques, et la somme des motifs égale au profil près le manque annoncé par le
  compteur. Le chiffre qui justifiait la tâche est confirmé sur données réelles : **202 des 340
  profils « sans ancienneté » ont simplement moins d'un an de carrière** — il n'y a que 138 profils
  réellement abîmés, pas 340.
- **Type** : feat — une opération de lecture nouvelle sur une surface admin existante. Aucune
  migration, aucune colonne, aucun chiffre existant modifié.
- **Tests** : **unitaires** sur le classement des motifs — les quatre motifs de l'ancienneté dont le
  piège des moins de douze mois, les trois de la durée sur le poste actuel, les deux du niveau
  d'études, et l'invariant qui les tient tous : *un profil compté comme en manque reçoit un motif et
  un seul*. **Fonctionnels** sur la route — refus sans `ROLE_ADMIN`, métrique inconnue en 404,
  pagination, et cohérence entre la somme de la répartition et le compteur déjà rendu par
  `GET /v1/admin/vivier`.
- **Réf** : **`ADM-020` — aval, bloquée par celle-ci** (les cartes cliquables et l'écran de détail,
  lane `espace/admin`) · [QualificationReporter.php](../../api/src/Domain/Reporting/QualificationReporter.php),
  qui produit les compteurs que cette tâche vient expliquer ·
  [ExperienceYearsCalculator.php](../../api/src/Domain/Jarvi/ExperienceYearsCalculator.php) ·
  [CurrentPositionExtractor.php](../../api/src/Domain/Jarvi/CurrentPositionExtractor.php) ·
  [EducationLevelCalculator.php](../../api/src/Domain/Jarvi/EducationLevelCalculator.php) — les trois
  calculs dont les motifs sont l'exact miroir ·
  [AdminCandidateProvider.php](../../api/src/State/Provider/AdminCandidateProvider.php), le précédent
  de pagination faite main et de garde `ROLE_ADMIN` explicite ·
  [api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md).

## Contexte

L'écran **« État de la base »** de l'espace admin (`/vivier`) affiche vingt-sept chiffres qui disent
ce que la base contient. Quatre d'entre eux sont des taux de qualification : sur 2 561 profils
actifs, 2 208 ont une ancienneté calculée, 1 777 une durée sur le poste actuel, 2 006 un niveau
d'études dérivé, 2 556 une date de modification côté source.

Un administrateur qui lit « 2 208 /2 561 » apprend qu'il manque 353 profils. Il n'apprend **ni
lesquels, ni pourquoi**. Il ne peut donc rien décider : ni corriger un import, ni ajuster une règle
de dérivation, ni conclure que tout va bien.

### Et « tout va bien » est une conclusion possible

[ExperienceYearsCalculator](../../api/src/Domain/Jarvi/ExperienceYearsCalculator.php) rend `0` dans
cinq situations, et **la cinquième n'est pas un défaut** :

- aucun poste dans le parcours ;
- aucun poste portant une date de début lisible et passée ;
- des dates présentes mais incohérentes (fin avant début) ;
- **un cumul mesuré inférieur à douze mois** — `intdiv($months, 12)` rend `0` pour onze mois
  d'expérience réelle.

Ce dernier cas décrit un débutant correctement traité par la chaîne. Le compteur le range pourtant
avec les profils cassés. Tant que l'écran ne sait pas les séparer, **le chiffre du manque est
surévalué d'une quantité inconnue**, et c'est précisément ce qui empêche d'en tirer une décision.

Le même mécanisme joue sur la durée sur le poste actuel — un poste commencé il y a trois semaines
mesure zéro mois — et, sans faux positif cette fois, sur le niveau d'études : un parcours peut
n'avoir aucune formation, ou n'avoir que des intitulés que le référentiel ne reconnaît pas. Ces deux
causes appellent des corrections opposées, et rien aujourd'hui ne les distingue.

### Ce qui bloque techniquement

`QualificationReporter` ne sait que **compter** : chacune de ses méthodes est un `count(*)` sur une
colonne déjà dérivée. Aucune ne rend d'identifiant, et **la cause n'existe nulle part en base** — les
colonnes gardent le résultat (`0`, `null`), jamais la raison. Le manque n'est donc pas seulement
d'affichage : il n'y a aujourd'hui aucune porte par laquelle demander « qui » et « pourquoi ».

## Périmètre

Une opération de lecture, `GET /v1/admin/vivier/gaps/{metric}`, gardée `ROLE_ADMIN`, qui pour une
métrique donnée rend trois choses : le **détail par motif** du manque, la **liste paginée** des
profils concernés avec le motif de chacun, et le **filtre** par motif.

**Les quatre métriques adressables**, et la condition du manque, reprise à l'identique de
`QualificationReporter::profileQualifications()` :

| Code de métrique | Carte à l'écran | Manque = |
| --- | --- | --- |
| `experienceYears` | Ancienneté renseignée | `experience_years = 0` |
| `currentRoleMonths` | Durée sur le poste actuel | `current_role_months = 0` |
| `educationLevel` | Niveau d'études dérivé | `education_level IS NULL` |
| `jarviUpdatedAt` | Date de modification source | `jarvi_updated_at IS NULL` |

Comme partout ailleurs sur ces compteurs, `deleted_at IS NULL` est porté par chaque requête : le
soft delete Doctrine ne couvre pas le SQL natif (cf.
[data-integrity.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md)).

**Le classificateur de motifs** — une unité de domaine nouvelle, à côté du rapporteur qu'elle
explique, qui relit le parcours brut d'un profil et rend le motif unique de son manque.

**La forme de la réponse** :

```json
{
  "metric": "experienceYears",
  "breakdown": [
    { "reason": "UNDER_ONE_YEAR", "count": 187 },
    { "reason": "NO_POSITION", "count": 102 },
    { "reason": "NO_USABLE_START_DATE", "count": 51 },
    { "reason": "INCOHERENT_DATES", "count": 13 }
  ],
  "reason": null,
  "items": [
    { "uuid": "…", "givenName": "…", "familyName": "…", "city": "…", "country": "…", "reason": "NO_POSITION" }
  ],
  "total": 353,
  "page": 1,
  "perPage": 20
}
```

`breakdown` est **toujours complet**, jamais restreint par le filtre : les compteurs de l'écran
doivent garder leur valeur quand on en sélectionne un. `reason` rend explicite le filtre appliqué —
`null` si aucun. `total`, `page` et `perPage` gardent le sens qu'ils ont dans `CollectionOutput` :
ils décrivent la liste **effectivement parcourue**, donc filtrée. Le manque total se lit comme la
somme du `breakdown`, et il n'y a ainsi jamais deux champs nommés « total » qui comptent des choses
différentes.

## Hors périmètre

- **Les chiffres de `GET /v1/admin/vivier` ne bougent pas.** « 2 208 /2 561 » reste tel quel, et
  `app:qualification:report` continue d'afficher exactement la même chose. Cette tâche **explique**
  la mesure, elle ne la corrige pas. Voir *Décisions de cadrage*.
- **Les libellés français émis par le serveur** — sections, noms de métriques — restent où ils sont
  et sous la forme qu'ils ont. La correction de ce défaut de fond n'est pas cardée.
- **Les autres cartes du rapport** : les trois Séniorité, `CERTAIN` / `AMBIGUOUS` / `ABSENT`, Métier
  reconnu, les tables dérivées, les référentiels. Aucune n'est adressable par cette route.
- **Toute écriture** : ni correction de profil, ni relance de traitement, ni marquage « vu ». La
  route est en lecture seule.
- **Le stockage du motif en base** et la migration qu'il demanderait. Voir *Décisions de cadrage*.

## Décisions de cadrage

**Le motif est recalculé à la demande, jamais stocké.** L'alternative — une colonne remplie à
l'ingestion — donnait une lecture instantanée et un filtre en SQL natif, au prix d'une migration et
d'un défaut sournois : toute évolution d'un calcul rendrait les motifs déjà en base faux jusqu'au
prochain import complet, sans que rien ne le signale. Un écran de diagnostic qui ment sur la cause
est pire qu'un écran absent. Recalculer garantit que le motif suit toujours le code en vigueur.

**Une seule route paramétrée par la métrique**, plutôt que quatre routes ou un filtre sur
`/v1/admin/candidates`. Quatre routes auraient quadruplé la même mécanique de garde et de
pagination ; un filtre sur la liste des candidats aurait chargé un endpoint généraliste d'une
logique de diagnostic, et le motif n'a pas sa place dans la fiche candidat qu'elle rend. Une
métrique non reconnue répond **404**, jamais une liste vide : « cette métrique n'existe pas » et
« cette métrique n'a aucun manque » sont deux réponses différentes.

**Le motif voyage en code technique**, pas en phrase française — `NO_POSITION`, pas « Aucun poste ».
C'est de la donnée neuve : rien n'oblige à répéter ici le choix hérité sur les libellés de
métriques. Le code garde `api/` libre de texte d'interface, permet à l'écran de nommer le motif dans
les trois langues, et donne au filtre une valeur d'URL propre — une phrase accentuée voyageant dans
une adresse aurait été le prix immédiat du choix inverse.

**Le chiffre affiché n'est pas corrigé.** Retirer les moins d'un an du manque aurait donné une
mesure plus juste, mais aurait fait diverger l'écran du rapport en ligne de commande — dont l'usage
est justement de comparer un avant et un après d'exécution de pipeline. La justesse se gagne dans le
détail, pas en déplaçant le compteur.

**La pagination et le filtre s'appliquent après le classement, en PHP.** Conséquence directe du
motif calculé à la volée : il n'existe dans aucune colonne, donc ni `WHERE` ni `LIMIT` ne peuvent le
trancher. Le lot des profils en manque est donc chargé **en entier**, classé, puis filtré et
découpé. Voir la borne en *Règles et cas limites*.

**Les jointures vers `candidate` et `person` sont ouvertes, décidé à l'implémentation.** Le compteur
d'origine ne filtre que `candidate_jarvi`. Une jointure fermée aurait pu retirer une ligne que le
compteur, lui, a comptée — et l'invariant « la somme des motifs égale le manque » serait tombé sur
un cas rare, donc découvert tard. Les jointures nomment quand elles peuvent et n'excluent jamais ;
un profil sans identité rendrait un nom vide plutôt que de disparaître du décompte.

## Règles et cas limites

### Les motifs, par métrique

Chaque motif est l'exact miroir d'une branche du calcul qu'il explique. Aucun n'est inventé : ils se
lisent dans le code référencé en tête de carte, et **le classificateur doit être écrit en le
relisant**, pas de mémoire.

**`experienceYears`** — miroir de `ExperienceYearsCalculator::fromPositions()` :

| Motif | Ce que le calcul a rencontré |
| --- | --- |
| `NO_POSITION` | aucun poste dans le parcours |
| `NO_USABLE_START_DATE` | des postes, mais aucun avec une date de début lisible et non future |
| `INCOHERENT_DATES` | des postes datés, mais toute période retenue a une fin antérieure à son début |
| `UNDER_ONE_YEAR` | au moins une période mesurable, cumul strictement inférieur à douze mois |

**`currentRoleMonths`** — miroir de `CurrentPositionExtractor::fromPositions()` :

| Motif | Ce que le calcul a rencontré |
| --- | --- |
| `NO_ONGOING_POSITION` | aucun poste sans date de fin — la certitude vaut `ABSENT` |
| `ONGOING_WITHOUT_START_DATE` | un poste en cours, mais sans date de début lisible |
| `UNDER_ONE_MONTH` | un poste en cours daté, commencé il y a moins d'un mois |

**`educationLevel`** — miroir de `EducationLevelCalculator::fromEducations()` :

| Motif | Ce que le calcul a rencontré |
| --- | --- |
| `NO_EDUCATION` | aucune formation dans le parcours |
| `UNRECOGNIZED_TITLE` | des formations, mais aucun intitulé reconnu par les motifs du référentiel |

**`jarviUpdatedAt`** — un seul motif, `SOURCE_WITHOUT_DATE` : la source n'a pas fourni la date. La
répartition y est dégénérée (une entrée), et c'est normal — l'écran l'affiche quand même, une
répartition à une seule ligne dit exactement ce qu'elle a à dire.

### Invariants

- **Un profil en manque reçoit un motif, et un seul.** L'ordre d'évaluation est décisif : du plus
  spécifique au plus général, comme dans les calculs d'origine. Un profil qui n'entre dans aucun
  motif est un **défaut de conception du classificateur**, pas un cas à absorber par un fourre-tout
  — les tests doivent le faire échouer, pas le ranger.
- **La somme du `breakdown` égale le compteur du manque**, lui-même égal à `Profils actifs` moins la
  valeur affichée par `GET /v1/admin/vivier`. Cette égalité est testée : elle est la seule garantie
  que le classement n'a perdu personne en route.
- **Le classement et le calcul ne peuvent pas diverger sans qu'un test tombe.** Chaque cas de test
  du classificateur vérifie les deux moitiés : le calcul d'origine rend bien `0` (ou `null`) **et**
  le classificateur rend bien le motif attendu.
- **Ordre stable.** `breakdown` trié par effectif décroissant puis par code, `items` triés par nom
  puis prénom puis identifiant. Sans ordre total, deux pages successives peuvent montrer deux fois
  le même profil et en oublier un autre.

### États et volumes

- **Métrique inconnue → 404.** Manque nul → 200, `breakdown` et `items` vides, `total` à zéro.
- **Filtre sur un motif inconnu ou absent de cette métrique → 404** également : une valeur de motif
  n'a de sens que rapportée à sa métrique.
- **`perPage`** : défaut 20, plafond 100 — mêmes bornes que `AdminCandidateProvider`, pour que les
  deux listes admin se comportent pareil.
- **La borne du lot chargé.** Le plus gros manque actuel est celui de la durée sur le poste actuel :
  784 profils. Relire 784 parcours JSON à l'ouverture d'un écran d'administration est acceptable ;
  la requête ne sélectionne que les colonnes nécessaires au classement, jamais le profil entier. **Si
  un manque venait à dépasser quelques milliers de profils, la décision est à rouvrir** — soit en
  pré-classant en SQL, soit en stockant le motif. La carte le dit pour que ce ne soit pas découvert
  comme une surprise.
- **Accès** : `ROLE_ADMIN` vérifié explicitement dans le provider, comme `AdminVivierProvider` et
  `AdminCandidateProvider` — la garde d'un accès cross-tenant reste lisible à côté du code qu'elle
  protège, pas dans `security.yaml`.
- **Aucune donnée masquée** : la surface admin rend le profil non masqué, comme
  `GET /v1/admin/candidates`. Cette route n'assouplit ni n'étend cette règle.

## Documentation impactée

- [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) — la nouvelle opération.
  **À noter au passage** : ni `/v1/admin/vivier` ni `/v1/admin/candidates` n'y figurent aujourd'hui.
  La tâche ajoute la sienne ; combler le reste est un autre sujet et ne s'improvise pas ici.
- [profile-processing.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md) — les
  motifs, écrits à côté des dérivations qu'ils expliquent, et le fait qu'un cumul de moins de douze
  mois rend zéro.
- `api/openapi.json` — **à régénérer** (`make dev-openapi`) et à embarquer dans le commit. La CI
  `api-quality` porte une étape `git diff --exit-code` sur ce fichier que la commande locale ne
  couvre pas : un contrat non régénéré passe vert en local et rouge en CI.

## Définition de terminé

- [x] `GET /v1/admin/vivier/gaps/experienceYears` répond 200 à un admin authentifié, et la somme de
      son `breakdown` vaut exactement `Profils actifs` moins `Ancienneté renseignée` tel que
      `GET /v1/admin/vivier` les rend au même instant — **preuve : les deux réponses HTTP réelles,
      côte à côte**.
- [x] Les quatre métriques répondent ; une cinquième valeur quelconque répond 404.
- [x] Un appel sans `ROLE_ADMIN` est refusé.
- [x] `?reason=UNDER_ONE_YEAR` restreint `items` sans altérer `breakdown`, et la pagination reste
      cohérente d'une page à l'autre sur un lot de plus de 100 profils.
- [x] Les tests unitaires du classificateur passent, y compris l'invariant « un motif et un seul »
      et l'appariement avec le calcul d'origine.
- [x] Les tests fonctionnels de la route passent.
- [x] `api/openapi.json` régénéré, et présent dans le commit.
- [x] `api-routes.md` et `profile-processing.md` à jour.
- [x] La carte est clôturée : statut, ce qui a été prouvé, et les écarts éventuels au cadrage.

## Vérification

Sur la base du worktree `espace/api`, qui porte **3 053 profils actifs** — pas les 2 561 de l'écran
d'où vient la demande, chaque dossier de travail ayant sa propre base.

**L'invariant, sur les quatre métriques.** Compteurs lus sur `GET /v1/admin/vivier`, puis somme des
motifs lue sur la nouvelle route, au même instant :

| Métrique | Compteur | Manque attendu | Somme des motifs |
| --- | ---: | ---: | ---: |
| Ancienneté renseignée | 2 713 | 340 | **340** |
| Durée sur le poste actuel | 2 166 | 887 | **887** |
| Niveau d'études dérivé | 2 440 | 613 | **613** |
| Date de modification source | 3 048 | 5 | **5** |

**La répartition qui justifiait la tâche** — `experienceYears` :

```
UNDER_ONE_YEAR         202     <- moins d'un an de carrière : aucun défaut
NO_POSITION             95
NO_USABLE_START_DATE    42
INCOHERENT_DATES         1
```

**Les motifs recoupés avec la donnée brute en base**, et pas seulement comptés — pour chaque groupe,
le nombre de postes et la présence d'une date de début lus directement en SQL :

| Motif | Échantillon | Postes min | Postes max | Dont ≥ 1 date de début |
| --- | ---: | ---: | ---: | ---: |
| `NO_POSITION` | 95 | 0 | 0 | 0 |
| `NO_USABLE_START_DATE` | 42 | 1 | 5 | 0 |
| `INCOHERENT_DATES` | 1 | 2 | 2 | 1 |
| `UNDER_ONE_YEAR` | 100 | 1 | 7 | 100 |

Le profil unique classé `INCOHERENT_DATES` porte « Assistante comptable, du 2026-04-01 au
2026-01-31 » : une fin trois mois avant le début, exactement le cas que le calcul écarte en silence.

**Les gardes** : 401 sans jeton, 403 pour un recruteur, 404 sur métrique inconnue, 404 sur un motif
inconnu **et** sur un motif réel emprunté à une autre métrique (`experienceYears?reason=NO_EDUCATION`).

**Le filtre et la pagination** : `?reason=NO_POSITION` ramène 95 profils en laissant `breakdown` à
340 au total. Quatre pages de 100 sur les 887 du poste actuel → 400 identifiants, 400 distincts,
aucun doublon. `perPage=5000` plafonne à 100, `page=0` est ramené à 1.

**Tests joués** (impactés seulement, aucune gate) :

```
tests/Unit/QualificationGapClassifierTest.php       OK (17 tests, 50 assertions)
tests/Functional/AdminVivierGapTest.php             OK (6 tests, 81 assertions)
tests/Functional/AdminVivierTest.php                OK (2 tests, 64 assertions)   <- non régression du voisin
```

**Contrat** : `make dev-openapi` → 143 lignes ajoutées, aucune retirée, et le diff ne contient que
`/v1/admin/vivier/gaps/{metric}` et le schéma `AdminVivierGap`. Aucun autre changement de surface ne
s'y est glissé, alors même que l'arbre porte le chantier d'une autre session.

## Livré

- `api/src/Enum/QualificationMetric.php` *(+)* — les quatre métriques adressables, et les motifs que
  chacune admet.
- `api/src/Enum/QualificationGapReason.php` *(+)* — les dix motifs, en codes.
- `api/src/Domain/Reporting/QualificationGapClassifier.php` *(+)* — le classement, miroir des trois
  calculateurs, pur et sans base.
- `api/src/Domain/Reporting/QualificationGapReporter.php` *(+)* — la requête des profils en manque et
  l'agrégation par motif.
- `api/src/ApiResource/Output/AdminVivierGapOutput.php` *(+)* et
  `api/src/State/Provider/AdminVivierGapProvider.php` *(+)* — la route, sa garde et ses 404.
- `api/tests/Unit/QualificationGapClassifierTest.php` *(+)*,
  `api/tests/Functional/AdminVivierGapTest.php` *(+)*.
- `api/openapi.json` *(M)*, `docs/…/api-routes.md` *(M)*, `docs/…/profile-processing.md` *(M)*.

## Reste à faire (hors périmètre)

- **`ADM-020`** — les cartes cliquables et l'écran de détail, sur la lane `espace/admin`. La route
  existe désormais ; rien ne la consomme.
- **`api-routes.md` reste incomplet sur `/v1/admin/*`** : les routes vivier, candidats, utilisateurs,
  référentiels et Jarvi n'y figurent toujours pas. La section ajoutée le dit explicitement plutôt que
  de laisser croire à un inventaire.
- **Les six défauts d'affichage de l'écran « État de la base »**, relevés au cadrage et non cartés :
  ratios sans pourcentage, codes techniques nus, palmarès sans échelle, séniorité éclatée en trois
  cartes, deux entrées de menu nommées « vivier », sous-titre qui parle de la ligne de commande.
