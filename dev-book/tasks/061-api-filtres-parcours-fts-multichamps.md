# TASK 061 — API : filtres « parcours » (données Jarvi) + FTS multi-champs

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Phase 2 — Espace entreprise / recherche vivier (back).
- **Prolonge** : tâche `035` (provider/criteria/repository recherche).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` (critères canoniques)
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` (couvertures mesurées 2026-07-09)
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` (§ Moteur — décision FTS 2026-07-06)

## Contexte

Constat de l'inventaire 3 couches (2026-07-09) : **les filtres implémentés portent sur des
données à ~0 % de couverture** (différenciants remplis par le candidat), tandis que les données
massivement présentes après import Jarvi n'ont **aucun filtre** — compétences 92 %,
expériences 97 %, langues 88 %, ville 71 %, nouveaux 100 %. Par ailleurs `q` est un simple
`LIKE` sur `headline` seul, alors que le signal réel est dans skills + intitulés de postes
(summary : 2 % de couverture seulement).

## Objectif

Rendre la recherche utile **sur le vivier réel** (importé de Jarvi) : brancher les filtres de
la famille « parcours » et le vrai moteur FTS décidé le 2026-07-06.

## Périmètre

- **Index FTS Postgres** (`tsvector` GIN + `pg_trgm`) sur : headline, skills, intitulés et
  entreprises des expériences (+ summary, marginal). Configuration de langue à trancher
  (contenu FR/EN mixte). `q` bascule du LIKE vers cet index.
- **Nouveaux critères** dans `CandidateSearchCriteria` / provider :
  - `skills` (multi-valeurs, jsonb) ;
  - `experienceYearsMin` — nécessite une **colonne normalisée calculée à l'ingestion**
    (somme des périodes de `positions`, chevauchements gérés simplement) ;
  - `language` (+ `proficiencyMin`) — règle : **`proficiency` null = langue maternelle** ;
  - `city` (ville actuelle, `person.city`, couverture 71 % assumée) ;
  - `createdAfter` (ou fenêtre « nouveaux ») — support du compteur console.
- **Facettes des dimensions qualifiées** (données livrées depuis, filtre absent — identifiées en
  rendant les métriques de `119` cliquables) :
  - `educationLevel` — `112` livré, couverture 77 % ;
  - `currentPosition` — `117` livré ; distinguer CERTAIN d'AMBIGUOUS, ne pas les confondre en un
    seul filtre ;
  - **absence** de niveau de langue — 34 % des langues n'en portent pas (57 % pour le français) :
    `languageLevel` les exclut silencieusement. Soit le filtre les assume, soit l'UI le dit.
- Tests fonctionnels par critère (avec masquage inchangé).

## Décisions ouvertes (à trancher en réunion filtres)

- Intitulé de poste / entreprise : facettes dédiées ou couverts par le FTS seul ?
- Fenêtre « nouveaux » : 7 j / 30 j / paramétrable ?
- Config FTS : `french` + `unaccent`, `simple`, ou double index ?

## Invariants

- Masquage/visibilité **côté serveur** inchangés ; profils actifs du périmètre uniquement.
- Pagination serveur (≈ 20/page) ; pas de récupération massive (cf. task 043).
- Le contrat de query params devient la base de sérialisation des `SavedSearch` (task 030) —
  le stabiliser ici.

## Critères d'acceptation

- [ ] `q` interroge l'index FTS (headline + skills + expériences), tolère préfixe/fautes (`pg_trgm`).
- [ ] Filtres `skills`, `experienceYearsMin`, `language`, `city`, `createdAfter` opérationnels et testés.
- [ ] Facettes `educationLevel` et `currentPosition` opérationnelles — les chiffres correspondants
      de `119` deviennent cliquables.
- [ ] Années d'expérience normalisées à l'ingestion (backfill par ré-import inclus).
- [ ] `make api-quality` vert ; plan de requête vérifié sur le vivier réel (~1 700, cible 20×).

## Hors périmètre

- Filtres différenciants (déjà en place) et leur activation UI → task `062`.
- Filtre salaire (décision multi-devises pendante) ; établissements de formation (🔵 V2 — le
  *niveau* d'études, lui, passe en périmètre ci-dessus depuis `112`).
- Coquille coordonnées au dévoilement → task `060`.
