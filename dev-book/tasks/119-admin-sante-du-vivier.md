# TASK 119 — admin : « Santé du vivier » (état de la base qualifiée)

- **Créée le** : 2026-07-23.
- **Statut** : ✅ fait le 2026-07-25 — API + écran /vivier, chiffres partagés avec le CLI.
- **Type** : Full-stack — API (agrégats lecture seule) + Front (webapp admin, et lisible recruteur).
- **Placement** : route dédiée `/vivier` côté admin — **ni** le dashboard `/` (qui affiche
  l'activité, `102`) **ni** `/jarvi` (qui supervise les *runs* d'ingestion, `101`). Trois
  questions distinctes : ce qui se passe · ce que le pipeline a fait · ce que la base contient.
- **Lié à** : `102` (KPI d'activité — **complémentaire, pas doublon**), `095`/`111`/`112`/`114`/`116`/`117`
  (dimensions mesurées), `113` (statut `candidat`), `062` (couverture conditionnant les filtres).
- **Réf** :
  - `dev-book/cadrage/traitements-profils.md` (dimensions et taux)
  - `dev-book/cadrage/runbook-traitements-profils.md` (le rapport CLI équivalent)
  - `api/src/Domain/Reporting/QualificationReporter.php` (source des chiffres, déjà écrite)

## Livré le 2026-07-25

`GET /v1/admin/vivier` délègue à `QualificationReporter` — **la même source que
`app:qualification:report`**, écran et CLI ne peuvent pas diverger. Renvoie les 5 sections de
couverture + les distributions (top secteurs / métiers / langues) + un horodatage. Écran `/vivier`.
2 tests (garde admin, structure snapshot + distributions).

## Contexte

Aujourd'hui, personne ne sait **ce que contient le vivier** sans lancer une recherche. Un recruteur
qui cherche un profil « Informatique » ne découvre qu'après coup s'il y en a 5 ou 500 ; un admin ne
voit pas qu'une dimension est trop peu couverte pour servir de filtre.

Or tout est déjà mesuré. `app:qualification:report` (task reporting) produit ces chiffres en CLI :
il **manque seulement l'écran**.

**À distinguer de `102`** : la carte 102 affiche l'**activité** (connexions, dévoilements,
inscriptions — ce qui se passe). Celle-ci affiche l'**état du stock** (ce que la base contient et ce
à quoi on peut se fier). Deux dashboards, deux questions.

## Objectif

Donner à un recruteur et à un admin une lecture immédiate de la base : **volume, couverture par
dimension, distributions**, et les signaux de qualité qui changent la façon de s'en servir.

## À afficher

### Couverture par dimension (le cœur)

Pour chaque dimension : le taux de remplissage **et** la fiabilité, car un taux élevé ne suffit pas.

| Dimension | Couverture (dev, 2026-07-22) | Fiabilité à signaler |
| --- | ---: | --- |
| Langues parlées | 87 % | **34 % sans niveau** (57 % pour le français) — exclus du filtre « niveau min » |
| Niveau d'études | 77 % | fiable |
| Expérience par secteur | 77 % | fiable |
| Expérience par métier | 83 % | **présence** fiable, **durée indicative** (62 % du parcours médian) |
| Poste actuel | 54 % CERTAIN · 14 % AMBIGUOUS · 32 % ABSENT | l'ambigu et l'absent sont distincts |
| Date de modification source | 99,7 % | **PAS une fraîcheur** : 95 % partagent la date d'un même batch |

### Distributions (ce qui aide à décider)

- Top secteurs d'expérience (Informatique 265, Industrie 227, …) — dont **la part de `AUTRE`, 11 %**,
  la traîne d'entreprises non classées.
- Top métiers exercés (Ingénieur d'études 297, Consultant 270, …).
- Top langues, avec la part sans niveau par langue.

### Signaux de qualité à rendre visibles, pas à cacher

- **`Stage / Internship` est le concept de poste le plus fréquent** (411 profils) : un statut, pas un
  métier. Invisible dans une requête, évident sur un écran.
- **2 043 rattachements métier sur 5 500 sont exacts** — le reste vient de la reconnaissance
  partielle (`118`), fausse dans ~7 % des cas.

## Métriques actionnables : cliquer mène aux données

Un chiffre sans destination oblige le lecteur à refaire la recherche à la main. La règle :

> **Un chiffre est cliquable si et seulement s'il se traduit en critère de recherche existant.**
> Le clic ouvre une **recherche pré-filtrée**, jamais une vue de liste dédiée.

Passer par la recherche réutilise pagination, masquage, favoris, shortlist et export (`072`,
`073`, `077`) : zéro endpoint de drill-down, et l'écran devient une **porte d'entrée** du vivier
plutôt qu'un tableau de bord.

| Chiffre | Destination | Facette |
| --- | --- | --- |
| Top secteurs (Informatique 265…) | recherche | `experienceSector` — existe |
| Top métiers (Ingénieur d'études 297…) | recherche | `job` — existe, filtre déjà sur `concept.prefLabel` |
| Top langues | recherche | `language` (+ `languageLevel`) — existent |
| `Stage / Internship` (411) | `/referentiels` (`120`) sur le concept | — |
| `AUTRE` (11 %) | `/referentiels` (`120`), entreprises non classées | — |

**Non cliquables faute de facette** — ces chiffres restent nus, et la facette manquante est
portée par `061`, pas ici :

- Niveau d'études (77 %) : `112` a livré la donnée, aucun filtre ne l'expose.
- Poste actuel (54 % certain) : `117` idem.
- Langues sans niveau (34 %) : `languageLevel` filtre *un niveau*, pas *son absence*.
- Rattachements exacts (2 043 / 5 500) : la population derrière n'est pas des candidats mais des
  lignes d'expérience — autre contrat, hors de cet écran.

**Jamais cliquables, même si une requête le permettait** : les taux de fiabilité sont des mises en
garde, pas des cohortes à explorer. Une file de travail « profils à ré-enrichir » serait une autre
carte, avec une action derrière — une liste qu'on ne peut pas traiter est décorative.

## Hors périmètre — à ne PAS afficher

- **La durée d'expérience par métier comme un chiffre ferme** : elle sous-estime. Afficher la
  présence, la durée en second et étiquetée « indicative ».
- **La date Jarvi comme « profil récent »** : elle date le dernier écrit côté source, dominé par un
  batch de masse. Un filtre ou un tri « fraîcheur » induirait en erreur.
- **La validation des référentiels** : c'est `120`, un écran distinct et actionnable.

## Invariants

- **Lecture seule** : aucun de ces chiffres ne modifie la base.
- Les taux sont **datés** (instantané) : la base bouge, un chiffre affiché sans date ment vite.
- Recruteur et admin voient la même chose ici ; la partie actionnable (`120`) est réservée admin.

## Critères d'acceptation

- [ ] Endpoint d'agrégats admin en lecture seule (réutilise `QualificationReporter`).
- [ ] Écran : couverture par dimension avec fiabilité, top 3 distributions, signaux de qualité.
- [ ] Chaque taux porte sa date d'instantané.
- [ ] Aucune durée d'expérience métier ni date Jarvi présentée comme filtre/tri fiable.
- [ ] Les chiffres adossés à une facette existante ouvrent une recherche pré-filtrée ; les autres
      ne sont pas cliquables (aucun lien mort, aucun endpoint de drill-down créé).
- [ ] `openapi.json` régénéré ; `api-quality` vert.

## Points de vigilance

- **Ne pas recalculer à chaque requête** ce que l'ingestion a déjà agrégé : lire les colonnes et
  tables dérivées, pas re-scanner les `workExperiences` JSON.
- **Cohérence avec le CLI** : l'écran et `app:qualification:report` doivent donner les mêmes nombres,
  sinon l'un des deux ment. Partager `QualificationReporter`.
