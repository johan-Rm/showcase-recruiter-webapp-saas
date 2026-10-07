# TASK 107 — API : charger les secteurs d'entreprises en base (`company_sector`)

- **Créée le** : 2026-07-19.
- **Statut** : 🟢 livré. L'expérience par secteur qui en dépendait est livrée (`095`) ; reste
  l'exposition côté produit (secteur sur la fiche, compteurs de facette).
- **Type** : API — vocabulaire / chargement déterministe.
- **Lié à** : `105` (classification → `sectors.json`), `104` (identité d'entreprise), `103` (pipeline
  vocabulaire), `095` (expérience par secteur — consommateur).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
  - `api/data/vocabulary/sectors.json`

## Contexte

`sectors.json` est committé et contient **1 207 entreprises classées** (100 %, ≈ 45 % des postes du
vivier). Mais **aucune table ne le charge** : le travail est fait et **invisible pour l'application**.
C'est la brique qui manque pour que le secteur serve enfin à quelque chose.

## Objectif

Charger `sectors.json` dans une table `company_sector`, de façon **déterministe et idempotente**,
pour rendre le secteur exploitable (facette de recherche + expérience par secteur).

## Livré

- [x] Entité **`CompanySector`** : `companyKey` (unique, clé stable du harvester), `name`, `sector`
      (enum), `frequency`, `status` (`CANDIDAT` par défaut) + index sur `sector`.
- [x] **`CompanySectorImporter`** : upsert idempotent sur `companyKey` — rejouer ne duplique rien et
      rafraîchit nom/secteur/fréquence ; ignore ce qui n'est pas exploitable (clé vide, secteur hors
      nomenclature).
- [x] **`app:company:sectors:import`** (`--file`, `--dry-run`).
- [x] Tests unitaires (EntityManager mocké) : création, mise à jour, rejets, dry-run.

## Invariants

- **Aucun LLM** : l'import est déterministe et rejouable — il tourne en preprod/prod sans clé
  (le fichier est committé et baké dans l'image, comme `taxonomy.json`).
- Tout entre en **`CANDIDAT`** : bon pour élargir une recherche, pas pour un filtre dur tant qu'un
  humain n'a pas relu.
- La **clé stable** (`linkedinCompanyId` sinon `name:<nom normalisé>`) est le seul lien avec les
  postes de `candidate_jarvi` — c'est elle qui évite de reclasser une entreprise déjà connue.

## Reste à faire (hors carte)

- **Expérience par secteur** (`095`) : joindre chaque poste passé à son secteur via la clé.
- **Facette secteur** dans la recherche vivier — aucune route ne lit encore `company_sector`.
- **Traîne** : 5 466 entreprises vues une seule fois (≈ 55 % des postes) restent non classées.
- **Promotion `candidat → validé`** : workflow de relecture, commun au vocabulaire.

## Points de vigilance

- **Schéma** : nouvelle table → `doctrine:schema:update` (ou équivalent) avant le premier import en
  dev/preprod/prod.
- **Couverture partielle assumée** : ~45 % des postes ; un poste dont l'entreprise n'est pas classée
  n'a simplement pas de secteur (pas de valeur par défaut trompeuse).

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
