# TASK 125 — Recherche : expansion par le vocabulaire qualifié (task 123, volet A)

- **Créée le** : 2026-07-25.
- **Statut** : 🚧 en cours.
- **Type** : API recherche — le moteur enfin branché sur le vocabulaire.
- **Lié à** : `123` (cadrage — c'est son volet A), `120`/`124` (validation/dépréciation qui pilotent
  ce que l'expansion inclut), `096`/`095` (concepts, rattachement des intitulés), `035` (moteur).
- **Réf** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`.

## Le besoin, et pourquoi maintenant

L'UI de recherche entreprise (#108) affiche sous le champ « Intitulé de poste » :
**« Les synonymes et rôles proches sont inclus automatiquement. »** Or le moteur fait aujourd'hui un
**match EXACT** sur le libellé canonique :

```
JOIN cje.concept jc WHERE jc.prefLabel = :job
```

Donc chercher « software engineer » ne trouve **rien** si le concept s'appelle « Développeur
logiciel ». **L'écran promet une expansion que le back ne fait pas.** Ce lot tient la promesse.

## Ce que ça fait

Le terme cherché **résout vers le(s) concept(s)** — par le `prefLabel` OU un **libellé vivant**
(non déprécié), en égalité insensible à la casse — puis on matche les candidats via
`candidate_job_experience.concept_id`. Un candidat dont l'intitulé « software engineer » a été
rattaché au concept « Développeur logiciel » à l'ingestion ressort donc quand on cherche n'importe
lequel de ses synonymes.

**La dépréciation de `124` paie ici** : un libellé passé en `DEPRECIE` ne résout plus — chercher un
synonyme parasite exclu par un admin ne ramène plus son concept.

## Décisions de design (V1, KISS)

- **Résolution par égalité** (insensible à la casse) sur `prefLabel` + libellés vivants. Pas de
  `pg_trgm`/fuzzy en V1 : le champ est une **combobox à autocomplétion** (le recruteur choisit un
  terme réel), l'égalité suffit. Le fuzzy est un raffinement ultérieur.
- **Le statut du CONCEPT ne filtre pas encore** (un concept `candidat` résout comme un `validé`).
  L'expansion **élargit** ; le raffinement *affirmer vs élargir* par statut de concept est la marche
  suivante (cf. `123`). On ne bloque pas la valeur immédiate en l'attendant.
- **Synonymes seulement, pas encore « rôles proches »** : la similarité (`vocabulary_concept_similarity`,
  `111`) est un incrément séparé. L'UI sur-promet légèrement « et rôles proches » — on livre d'abord
  le 80 % solide (les synonymes du même concept).

## Limite connue à documenter

Le `concept_id` d'un candidat est figé **à l'ingestion** (via `TitleConceptResolver`). Déprécier un
libellé (`124`) affecte la **résolution de la requête** (chercher ce terme n'élargit plus), **pas
l'appartenance des candidats** déjà rattachés à ce concept. Pour que la dépréciation retire aussi les
candidats, il faudrait que `TitleConceptResolver` lise `label.status` et **réingérer** — hors périmètre.

## Périmètre

- [ ] `CandidateRepository::applyFilters` — le filtre `job` résout terme → concept(s) via
      `prefLabel` OU libellé non déprécié, puis matche `candidate_job_experience.concept_id`.
- [ ] Tests fonctionnels : un synonyme trouve le concept ; le `prefLabel` trouve ; un libellé
      **déprécié** n'élargit plus ; un terme inconnu ne trouve rien.
- [ ] Carte `123` : cocher le volet A.

## Invariants

- Masquage / visibilité / actifs restent garantis serveur (`035`).
- L'expansion **honore `label.status`** : un libellé déprécié ne participe jamais à la résolution.

## Hors périmètre

- Similarité « rôles proches » (`111`) — incrément suivant.
- Raffinement *affirmer vs élargir* par statut de **concept**.
- Recherche floue (`pg_trgm`) sur le terme.
- Réévaluation du `concept_id` des candidats sur dépréciation (réingestion).
