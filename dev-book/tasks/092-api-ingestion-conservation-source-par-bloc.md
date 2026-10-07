# TASK 092 — API : conserver la provenance (`source`) par bloc à l'ingestion Jarvi

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire — **différable** (dépend d'un besoin produit d'affichage de provenance).
- **Type** : API — ingestion Jarvi / réplica `candidate_jarvi`.
- **Lié à** : tâches `034` (import CLI), `020` (ingestion), `086` (carte candidat / inspecteur).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`

## Contexte

La tâche `034` a livré la conservation de la **`source` niveau profil** dans
`candidate_jarvi.raw_payload` (vérifié 2026-07-18 : `LINKEDIN` 1667, `MARKETINGINBOUND` 12,
`COOPTATION`, `APEC`… sur 1698 lignes). En revanche l'ingestion
([ProfileIngestor.php](../../api/src/Domain/Jarvi/ProfileIngestor.php)) **aplatit** :
- `skills` → tableau de chaînes (`["PHP","SQL"]`), **sans `source`** ;
- les positions/educations normalisées **ne portent pas** le `source` par ligne.

Or Jarvi expose bien `positions.source` / `educations.source` (`LINKEDIN` vs `RESUME` vs `MANUAL` —
vérifié : sur 30 profils, positions LINKEDIN 228 / RESUME 31 / MANUAL 3). Impossible aujourd'hui de
dire, en base locale, « cette expérience vient de LinkedIn / du CV ».

## Objectif

Conserver la provenance **par bloc** dans la réplica, si (et seulement si) le produit veut
distinguer/afficher l'origine des données (LinkedIn only, CV only, hybride).

## Périmètre

- Étendre le mapping d'ingestion pour retenir `source` sur chaque `position` / `education`
  (et, si utile, un indicateur d'origine agrégé par profil : LinkedIn present / CV parsé).
- Décider du stockage des skills : garder les chaînes + un bloc parallèle « skills avec source »,
  ou passer à des objets `{ name, source }` (impact sur les consommateurs actuels — recherche,
  sérialisation).
- Réingestion nécessaire (`--full`) pour peupler l'existant (phase dev = base recréable).

## Décision préalable (différable)

Cette carte n'a de valeur que si un écran/produit exploite la provenance par bloc
(ex. badge « issu de LinkedIn » sur la fiche candidat). Sinon, `raw_payload` (source globale) suffit.

## Hors périmètre

- Édition des blocs (réplica reste lecture seule).
- Copie du binaire CV.

## Invariants

- Réplica `candidate_jarvi` = lecture seule alimentée par ingestion, Jarvi reste maître.
- Ingestion idempotente ; réingestion ne duplique pas.
- Aucune donnée maître showcase-recruiter-webapp-saas écrasée.

## Critères d'acceptation

- [ ] `positions`/`educations` de `candidate_jarvi` portent leur `source` d'origine après ingestion.
- [ ] La provenance des skills est disponible (forme à décider) ou explicitement écartée du besoin.
- [ ] Une réingestion `--full` peuple la provenance sur le stock existant.
- [ ] Tests d'ingestion couvrant la conservation du `source` par bloc.

## Points de vigilance

- **Compat consommateurs** : changer la forme des `skills` impacte la recherche et les DTO — mesurer
  avant de migrer la structure.
- **Cohérence 034** : mettre à jour le critère « provenance conservée » de la tâche `034`, qui ne
  couvrait en réalité que la source globale (`raw_payload`).

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
