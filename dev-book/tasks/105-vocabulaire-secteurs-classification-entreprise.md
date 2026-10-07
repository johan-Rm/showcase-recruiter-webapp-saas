# TASK 105 — Vocabulaire : sac « secteurs » (classification entreprise → secteur)

- **Créée le** : 2026-07-19.
- **Statut** : 🟢 classification livrée + baseline de 1 207 entreprises versionnée et chargée en base
  (`107`) — reste la **passe LLM sur la traîne** (5 466 entreprises vues une fois).
- **Type** : API — vocabulaire (3ᵉ axe) / enrichissement.
- **Lié à** : `104` (identité d'entreprise — prérequis), `103` (pipeline vocabulaire), `096`
  (référentiel & couche sémantique), `095` (expérience par secteur — consommateur).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
  - `dev-book/cadrage/entreprise/profil_dimensions_vivier.md` (§ Qualifications portées implicitement)

## Troisième passe — 2026-07-24 : cibler plutôt que tout classer

5 466 entreprises restaient non classées, toutes vues **une seule fois**. Les classer toutes aurait
été un effort linéaire pour un bénéfice diffus. En cherchant celles qui **débloquent un candidat
n'ayant AUCUN secteur**, le périmètre tombe à **1 095** — pour le même gain.

| | Avant | Après |
| --- | ---: | ---: |
| Entreprises classées | 1 207 | **2 302** |
| Candidats avec expérience secteur | 1 311 (77 %) | **1 596 (94 %)** |
| Lignes d'expérience secteur | 2 396 | 3 022 |
| Part d'`AUTRE` | 11 % | **15 %** |

**Le piège évité** : `AUTRE` étant une valeur valide, classer les 1 095 entreprises *en bloc* comme
`AUTRE` aurait fait passer la couverture à 94 % tout en portant `AUTRE` à ~35 % — un ✅ obtenu en
vidant la mesure de son sens. La classification a donc été faite en deux temps : règles par mots-clés
(36 %), puis **268 corrections à la main** sur les noms réellement reconnaissables (Eurofins →
santé, Jan De Nul → BTP, FORAFRIC → agroalimentaire…). `AUTRE` est ainsi contenu à 15 %.

**Ce qui reste non classé n'apporte plus rien** : les entreprises restantes appartiennent toutes à
des candidats qui ont déjà un secteur par ailleurs. La commande LLM reste utile pour la finesse, pas
pour la couverture.

## Contexte

Le secteur d'activité n'est **nulle part** dans la donnée (ni Jarvi, ni LinkedIn tel qu'exposé) —
il se **déduit du nom de l'employeur**. C'est un **3ᵉ axe de vocabulaire**, de même nature que
compétences et postes :

| Sac | Se récolte dans | Méthode d'enrichissement |
| --- | --- | --- |
| compétences | `skills[]` | synonymie (LLM) |
| postes | `workExperiences[].title` | synonymie (LLM) |
| **secteurs** | **entreprise canonique** (`linkedinCompanyId` / `companyName`) | **classification** entreprise → secteur (LLM) |

Différence clé avec `enrich` : ce n'est pas de la synonymie, c'est de la **classification** — ranger
chaque entreprise dans une nomenclature de secteurs (≈18 : conseil/audit, informatique, transport &
logistique, industrie, banque…).

## Objectif

Produire un référentiel `entreprise canonique → secteur` (statut `candidat`), utilisable comme
facette de recherche et comme base de l'**expérience par secteur** (`095`).

## Spike (mesuré après ré-ingestion 104)

Le `linkedinCompanyId` dédoublonne **peu** : le vivier a de vraies entreprises très variées.

- Postes avec ID stable : **72 %**.
- Noms bruts : 6 654 → entreprises canoniques (ID) : **4 261** + noms sans ID : **2 412** =
  **~6 673 entités à classer**.
- **Dispersion extrême** : **82 %** des entreprises vues une seule fois ; les 196 fréquentes (5+)
  ne couvrent que **20,6 %** des postes ; la n°1 (OCP) = 0,65 %.
- → **Pas de « head » utile** : on classe TOUT (option A), en batch. La clé stable sert de cache.

## Baseline versionnée (sans clé API)

`api/data/vocabulary/sectors.json` est **committé** : **1 207 entreprises** (celles vues ≥ 2 fois),
**100 % classées**, soit **45,1 % des postes** du vivier. Produit par classification manuelle assistée
— **aucun appel API**, donc pas de dépendance à `ANTHROPIC_API_KEY` pour démarrer.

Conséquence : le jour de la mise en prod, la commande ne traitera **que les entreprises absentes de
ce fichier** (le classifier saute toute clé déjà connue). Le référentiel s'enrichit par delta, on ne
reclasse jamais un secteur déjà connu.

Répartition : autre 216, informatique 128, industrie 101, agroalimentaire 73, éducation 71,
banque/finance 70, transport/logistique 60, énergie 59, public 59, conseil/audit 57, automobile 55,
distribution 55, aéronautique 50, BTP 46, santé/pharma 43, télécom 34, assurance 23, indépendant 6.

Reste la traîne pure : **5 466 entreprises vues une seule fois** (≈ 55 % des postes), à traiter par
la passe LLM en prod ou par de nouveaux lots manuels.

## Livré (classification)

- [x] `CompanySectorHarvester` : entreprises distinctes + clé stable (`linkedinCompanyId` / nom
  normalisé) + fréquence + nom canonique.
- [x] Port `CompanySectorClient` + adaptateur Anthropic (Messages API, secteur contraint par `enum`).
- [x] `CompanySectorClassifier` : incrémental (cache par clé), écrit `sectors.json` (statut `candidat`).
- [x] `app:company:sectors:classify` (`--file`, `--dry-run`) + nomenclature `Sector` (~18, ajustable).
- [x] Tests : classifier (port doublé) + adaptateur (MockHttpClient).

## Périmètre pressenti

- Définir la **nomenclature de secteurs** (fixe, ≈18 — à arrêter avec le produit).
- Récolter les entreprises canoniques (nouveau harvest ou extension).
- **Classifier** chaque entreprise → secteur via LLM en batch (même garde-fous qu'`enrich` : clé
  côté `api/`, statut `candidat`, un batch, relecture `git diff`).
- Stockage : sac `secteurs` dans `taxonomy.json` + tables `vocabulary_*` (ou table dédiée si le
  modèle concept/label ne colle pas à « entreprise → secteur »).

## Débloque, une fois fait

- **Facette secteur** (recherche vivier).
- **Expérience par secteur** (`095`) : chaque poste passé → secteur via son entreprise.

## Points de vigilance

- **Couverture** : ~59 % des profils avaient un secteur lisible sur un échantillon de 100 (doc
  cadrage) — à re-mesurer sur entreprises canoniques.
- **Classification ≠ vérité** : « Deloitte → conseil/audit » est du jugement LLM, statut `candidat`,
  relecture humaine avant tout usage comme filtre dur.
- **Entreprises sans `linkedinCompanyId`** : retomber sur `companyName` normalisé.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
- `dev-book/cadrage/entreprise/profil_dimensions_vivier.md`
