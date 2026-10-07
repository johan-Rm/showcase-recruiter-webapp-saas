# TASK 095 — API : extraction des axes d'expérience & de poste à l'import

- **Créée le** : 2026-07-18.
- **Statut** : 🟡 partiel — durée poste actuel, séniorité, expérience par secteur **et par métier**
  livrées ; le **poste actuel lui-même** (titre, entreprise) reste à matérialiser → `117`.
- **Type** : API — ingestion Jarvi / enrichissement à l'import.
- **Lié à** : `094` (normalisation & qualification), `034` (import CLI), `092` (provenance par bloc),
  `061` (filtres parcours), `075` (postes & agrégats facettes).
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`
  - `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`

## Contexte

« Expérience » et « poste » recouvrent chacun **plusieurs axes** qu'un filtre confond aujourd'hui.

Pour l'**expérience**, « 3 ans d'expérience » ne dit pas *de quoi* : carrière totale ? sur un métier
précis ? dans un secteur ? sur le poste actuel ? Ces durées ne sont pas stockées — elles se
**calculent** depuis `candidate_jarvi.workExperiences` (dates `start`/`end`).

Pour le **poste**, trois axes distincts se cachent derrière le mot :
- **poste recherché** — aspirationnel (`Candidate.desiredFunction`) ;
- **poste actuel** — rôle en cours (le plus récent / sans `endDate`) ;
- **poste occupé** — n'importe quel poste de l'historique.

Un filtre « poste » qui ne déclare pas son axe est ambigu.

## Objectif

Calculer et matérialiser à l'import les **axes d'expérience** et **d'ancienneté**, et **qualifier
chaque poste** par son axe, pour que la recherche cible un axe explicite.

## Périmètre

- **Durées d'expérience** dérivées des `workExperiences` :
  - ancienneté générale (carrière totale) ;
  - expérience cumulée par métier / fonction ;
  - expérience cumulée par secteur ;
  - durée sur le poste actuel ;
  - séniorité dérivée (junior / confirmé / senior — seuils à fixer).
- **Qualification du poste** : marquer le poste **actuel** (en cours) vs **occupé** (historique) ;
  garder `desiredFunction` comme axe **recherché** séparé.
- Réingestion (`--full`) pour peupler l'existant.

## Hors périmètre

- Rapprochement sémantique des intitulés (similarité, synonymes, hiérarchie) → `096`.
- Normalisation textuelle brute → `094`.

## Invariants

- Réplica en lecture seule ; les durées sont des champs **dérivés**, recalculables par réingestion.
- Ingestion idempotente.

## État par axe

- [x] **Ancienneté générale** (`experienceYears`) — livrée antérieurement (`ExperienceYearsCalculator`).
- [x] **Durée sur le poste actuel** (`currentRoleMonths`, `CurrentRoleDurationCalculator`) — livrée.
- [x] **Séniorité dérivée** (`seniority`, seuils junior < 2 / confirmé 2–7 / senior > 7) — livrée.
- [x] **Expérience par métier** (`candidate_job_experience`, `JobExperienceCalculator`) — livrée le
      2026-07-22 : **1 405 candidats (86 % de ceux qui ont un parcours), 3 344 lignes**, filtrable
      via le critère de recherche `job`.
      Mêmes règles que le secteur (chevauchements fusionnés par métier, rien d'inventé), plus
      l'exclusion des concepts « statut » — « Stage / Internship » est le concept de poste le plus
      fréquent du vivier, mais un stagiaire n'exerce pas le métier « stagiaire ».

      **La durée sous-estime, et c'est mesuré** : tout intitulé hors dictionnaire est ignoré plutôt
      que deviné. Un candidat couvert l'est sur **62 % de son parcours en médiane** — c'était 33 %
      avant la reconnaissance partielle (`118`). D'où la forme retenue :

      | Signal | Fiabilité |
      | --- | --- |
      | **Présence** du métier | fiable — l'erreur est en moins (faux négatifs), jamais en trop |
      | `months` | **indicatif** — jamais un filtre « au moins N années » |
      | `positions` | dit combien de postes ont alimenté la ligne, pour rendre l'écart visible |
      | `exactPositions` | parmi eux, ceux reconnus par ÉGALITÉ (2 043 sur 5 500) — le reste vient de la reconnaissance partielle, fausse dans ~7 % des cas |

      À titre de comparaison, l'expérience par **secteur** couvre 50 % du parcours médian.
- [x] **Expérience par secteur** (`candidate_sector_experience`, `SectorExperienceCalculator`) — livrée :
      chevauchements fusionnés dans un secteur, comptés séparément entre secteurs ; entreprise non
      classée = aucun secteur inventé. Filtrable via le critère de recherche `experienceSector`.

## Critères d'acceptation

- [ ] Le poste **actuel** est identifié ; les postes **occupés** sont distingués du recherché.
- [ ] Les cas limites sont gérés : poste en cours (pas d'`endDate`), chevauchements, trous, **dates
      manquantes** (ne pas produire de durée fausse — marquer « inconnu »).
- [ ] Une réingestion `--full` recalcule tout le stock.
- [ ] Tests couvrant le calcul des durées et l'identification poste actuel/occupé.

## Points de vigilance

- **Dates manquantes / partielles** : Jarvi/LinkedIn donnent parfois mois-année, ou rien →
  distinguer « 0 » d'« inconnu », sinon les filtres d'ancienneté mentent.
- **Chevauchements** : sommer naïvement les durées de postes parallèles surestime l'ancienneté.
- **Cohérence facettes** (`075`) : les axes de poste doivent alimenter les mêmes agrégats.

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
