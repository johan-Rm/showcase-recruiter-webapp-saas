# TASK 123 — Cadrage : recherche à valeur ajoutée (expansion vocabulaire + niveau d'études filtrable)

- **Créée le** : 2026-07-24.
- **Statut** : 📐 cadrage — **volet B livré** (2026-07-24) ; volet A à découper.
- **Type** : transverse — API recherche + qualification. Cadre les cartes filles, n'implémente rien.
- **Lié à** : `035` (moteur de recherche : provider / criteria / repository), `111` (similarité de
  concepts), `112` (niveau d'études dérivé), `113` + `120` (promotion candidat→validé des
  référentiels), `122` (expérience qualifiée exposée sur la fiche).
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` (liste canonique des filtres)
  - `dev-book/cadrage/entreprise/recherche_assistee_ia.md` (la piste IA reste V2)

## Le constat

La recherche filtre aujourd'hui sur des **facettes exactes** (secteur enum, langue, disponibilité,
horizon, présence…). Or le pipeline a produit une matière que la recherche **n'exploite pas encore** :

1. **Le vocabulaire qualifié n'est pas consommé.** Le domaine `Search` ne référence ni
   `VocabularyConcept`, ni `VocabularyConceptSimilarity`, ni `VocabularyStatus`. L'expansion par
   synonymes (« dev » → « développeur / software engineer ») et la similarité de concepts ne
   nourrissent pas le moteur. Pourtant l'accroche physique **existe déjà** :
   `candidate_job_experience.concept_id → vocabulary_concept`.
2. **Le niveau d'études est dérivé mais bloqué.** `112` a livré `EducationLevel` (6 niveaux) et son
   calculateur (couverture **77 %**), mais la sortie ne vit que dans `candidate_jarvi.education_level`
   (le réplica). Elle n'est **ni projetée sur le candidat, ni filtrable** — la « dernière marche »
   explicitement reportée par `112` (« Exposition produit : hors périmètre »).

## Objectif

Passer de « filtre exact » à « recherche qui affirme » : exploiter le vocabulaire qualifié sur l'axe
métier/compétence, et finir la projection du niveau d'études pour le rendre filtrable. La recherche
lit **les tables**, jamais le `taxonomy.json` (perf, statut et similarité vivent en base).

## Volet A — Expansion par le vocabulaire qualifié

Le cœur de la valeur ajoutée, sur l'axe **métier/poste** (et en option les compétences) :

- Un intitulé cherché → résoudre le(s) **concept(s)** → remonter tous leurs `vocabulary_label`
  (synonymes) → filtrer les `candidate_job_experience` dont `concept_id` appartient à l'ensemble.
- **Statut = curseur affirmer / élargir.** Un concept `VALIDE` autorise un matching serré (on
  *affirme*) ; tant qu'il est `candidat`, la recherche ne peut qu'*élargir*. Le moteur lit le statut
  en base — d'où la dépendance à la validation (`120` / volet B métier).
- **Option** : bloc « concepts proches » via `vocabulary_concept_similarity` (`111`).
- **État actuel bloquant** : **860 / 860 concepts en `candidat`, 0 validé**. Sans socle validé,
  l'expansion élargit sans jamais affirmer — la validation du haut de la courbe (via `/referentiels`,
  écran `120`) est le préalable métier.

## Volet B — Projeter le niveau d'études pour le rendre filtrable

Finir la marche reportée par `112`. Tout le travail dur (extraction, normalisation en 6 niveaux) est
fait ; il reste du **câblage**, calqué sur le patron du secteur (`107`) :

1. colonne `education_level` sur `candidate` (typée `EducationLevel`, **nullable**) ;
2. remplissage à l'ingestion — router vers le candidat la sortie du calculateur **déjà appelé** dans
   `ProfileIngestor` ;
3. `CandidateSearchCriteria` + `WHERE` SQL + agrégat de facette ;
4. *(option)* afficher le **niveau normalisé** sur la fiche, à côté des éducations brutes (`122`).

Promeut « Formations » du cadrage filtres (§2, aujourd'hui `🔵 V2` sur JSON brut) vers un vrai
**« Niveau d'études »** dérivé et filtrable. Couverture partielle assumée → articuler avec `062`
pour ne pas proposer un filtre qui renvoie 0.

## Décisions déjà tranchées

- **Poste ↔ secteur = intersection au niveau candidat.** « Métier ∩ secteur » (ce profil a CE métier
  **et par ailleurs** de l'expérience DANS ce secteur) est faisable et suffisant. Le **croisement fin**
  « poste exercé DANS tel secteur » n'est **pas persisté** : `candidate_job_experience` (par concept)
  et `candidate_sector_experience` (par secteur) sont deux agrégations découplées, le lien par position
  est perdu après l'ingestion. → **hors périmètre (YAGNI)** : à ne construire (une ligne par position
  portant `concept_id` + `sector`) que si un besoin recruteur réel le justifie.
- **Le secteur est porté par l'entreprise, pas par le poste** : un poste dont l'employeur n'est pas
  classé n'a pas de secteur (cf. `SectorExperienceCalculator`).

## Invariants

- Masquage / visibilité / candidats actifs garantis **côté serveur** (`035`).
- Jarvi reste maître ; le niveau d'études est **dérivé et recalculable**, **aucun niveau par défaut**
  (`112`).
- Le statut `VALIDE` se lit **en base** ; un réimport de `taxonomy.json` ne rétrograde jamais une
  validation humaine (`mergedStatus`, `120`).

## Découpage (cartes à écrire)

- [x] **Volet B** — projection du niveau d'études, **livré** : colonne `candidate.educationLevel`
      (enum, nullable), remplie à l'ingestion depuis le calculateur déjà appelé + backfill des profils
      existants depuis la réplica ; filtre `educationLevel` « au moins » (`EducationLevel::atLeast`)
      dans `CandidateSearchCriteria` / `CandidateRepository`, câblé aux deux constructeurs de critères
      (provider live + `SearchCriteriaTranslator` des recherches sauvegardées) ; niveau normalisé
      exposé sur la fiche à côté des `educations` brutes ; 2 tests fonctionnels.
      **Reste** : la facette côté webapp (à brancher dans l'UI de recherche, cf. `043`).
- [x] **Volet A** — expansion vocabulaire sur l'axe métier, **livré** (task 125) : le filtre `job`
      résout le terme vers le(s) concept(s) via `prefLabel` OU un libellé vivant, puis matche le
      concept exercé du candidat ; les libellés dépréciés (124) ne résolvent plus. Élargit sans
      encore affirmer — le raffinement par statut de **concept** reste ouvert.
- [ ] *(option)* similarité « concepts proches » (`111`) en surcouche du volet A.

## Docs à tenir à jour

- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` — §2 Métier : promouvoir « Formations »
  vers un « Niveau d'études » dérivé filtrable.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` — colonne `candidate.education_level`.
