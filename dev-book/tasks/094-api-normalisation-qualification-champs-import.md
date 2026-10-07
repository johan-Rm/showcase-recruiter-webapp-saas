# TASK 094 — API : normalisation & qualification des champs candidat à l'import

- **Créée le** : 2026-07-18.
- **Statut** : ⚪ **absorbée** le 2026-07-24 — retirée de la roadmap des traitements, ses cibles
  ayant été traitées autrement. Ne reste qu'un volet, décrit plus bas.
- **Type** : API — ingestion Jarvi / enrichissement à l'import.
- **Lié à** : `034` (import CLI), `020` (ingestion), `092` (provenance par bloc),
  `062` (filtres conditionnés à la couverture), `061` (filtres parcours FTS).
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`
  - `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`

## Pourquoi cette carte est retirée de la roadmap

Écrite le 2026-07-18, **avant** toute la chaîne vocabulaire. Ses cibles ont été traitées depuis, et
mieux : le vocabulaire ne normalise pas seulement l'orthographe, il regroupe par le **sens**.

| Ce que 094 voulait normaliser | Traité par |
| --- | --- |
| compétences, postes | chaîne vocabulaire (`103`, `118`) |
| langues | référentiel fermé (`114`) |
| secteur d'expérience | référentiel entreprise (`105`, `107`) |
| métier | concepts métier (`095`, `118`) |
| règle de normalisation partagée | `TermNormalizer` (`094a`) |
| drapeaux de couverture | `app:qualification:report` et l'écran `119` |

**Et ce qui restait est vide.** Les champs *déclarés par le candidat*, mesurés le 2026-07-24 :

| Champ | Rempli |
| --- | ---: |
| Secteur souhaité | **3** / 1 699 |
| Fonction souhaitée | **3** / 1 699 |
| Disponibilité | **3** / 1 699 |

Ces 3 sont les fixtures de démo. Le vivier réel n'en porte aucun : 98 % vient du sourcing LinkedIn,
et personne ne s'est inscrit pour déclarer ses souhaits. Normaliser un champ vide ne rapporte rien —
la valeur reviendra si l'inscription candidat prend, pas avant.

## Le seul volet encore utile

La **qualification de source** (`MANUAL` / `RESUME` / `LINKEDIN`, priorité `MANUAL > RESUME >
LINKEDIN`) : savoir qu'une information vient d'un CV plutôt que d'un scraping change la confiance
qu'on lui accorde. Déjà appliqué aux coordonnées par `110`. À rouvrir sous une carte dédiée si le
besoin s'étend à d'autres champs, plutôt que de garder celle-ci ouverte pour un huitième de son
périmètre.

## Contexte (état d'origine, conservé pour mémoire)

Les champs de recherche `poste`, `métier`, `secteur`, `fonction`, `compétences`, `langues` viennent
de Jarvi / LinkedIn / CV en **texte libre**, non normalisé : casse, accents, pluriels, ponctuation,
espaces et abréviations varient (`Développeur Web` ≠ `developpeur web`). Comparer par égalité de
chaîne échoue, et les compteurs de facettes sont faux dès qu'une variante orthographique existe.

En parallèle, la couverture réelle d'un champ (renseigné / vide) et la **fiabilité de sa source**
(déclaré vs extrait CV vs LinkedIn vs vérifié entretien) ne sont pas qualifiées en base — alors
qu'elles conditionnent l'affichage des filtres (`062`) et leur pondération.

Ces traitements se font **une fois à l'import**, pas à chaque requête.

## Objectif

Produire, à l'ingestion, une **forme normalisée et qualifiée** des champs candidat, à côté de la
valeur brute (jamais à sa place — Jarvi reste maître, réplica en lecture seule).

## Périmètre

- **Normalisation textuelle** des champs comparables : minuscules, dépliage des accents,
  compactage des espaces, ponctuation, singularisation prudente. Stocker une clé normalisée
  parallèle à la valeur d'affichage (qui, elle, reste intacte).
- **Flags de couverture** par champ : renseigné / vide, exploitables par `062` pour masquer un
  filtre sans donnée.
- **Qualification de source** par champ/bloc : origine (`MANUAL` / `RESUME` / `LINKEDIN`) et
  priorité (`MANUAL > RESUME > LINKEDIN`), en cohérence avec `092`.
- Réingestion (`--full`) pour peupler l'existant (phase dev = base recréable).

## Hors périmètre

- Similarité / synonymes / hiérarchie / relation métier↔compétences → **task `096`** (dépend d'une
  décision référentiel).
- Traduction FR/EN/AR des valeurs → `079`.
- Calcul des durées d'expérience et distinction poste actuel/occupé → **task `095`**.

## Invariants

- Réplica `candidate_jarvi` = lecture seule alimentée par ingestion ; la valeur brute n'est jamais
  écrasée par sa forme normalisée.
- Ingestion idempotente ; réingestion ne duplique pas.
- Aucune donnée maître showcase-recruiter-webapp-saas écrasée.

## Critères d'acceptation

- [ ] Chaque champ comparable porte une clé normalisée + sa valeur d'affichage d'origine.
- [ ] Chaque champ expose un flag de couverture (renseigné / vide).
- [ ] La source et sa priorité sont disponibles par champ/bloc (aligné `092`).
- [ ] Une réingestion `--full` peuple normalisation + qualification sur le stock existant.
- [ ] Tests d'ingestion couvrant normalisation, couverture et source.

## Points de vigilance

- **Ne pas sur-normaliser** : la singularisation/stemming agressif crée des collisions (`vente` vs
  `vent`) — rester conservateur, la couche sémantique (`096`) gère l'élargissement.
- **Compat consommateurs** : la recherche et les DTO doivent viser la clé normalisée sans perdre
  l'affichage d'origine.
- **Cohérence `092`** : la qualification de source réutilise le même mapping de provenance.

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
