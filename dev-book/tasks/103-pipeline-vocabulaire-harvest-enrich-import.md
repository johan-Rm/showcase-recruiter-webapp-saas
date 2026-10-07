# TASK 103 — Pipeline vocabulaire : harvest → enrich → import

- **Créée le** : 2026-07-19.
- **Statut** : 🟡 socle livré, reste à câbler (exposition, promotion, couverture de la traîne).
- **Type** : API — vocabulaire métiers/compétences (récolte, enrichissement, chargement).
- **Lié à** : `096` (référentiel & couche sémantique — parent décisionnel), `094` (normalisation à
  l'import), `062` (filtres conditionnés à la couverture), `061`/`075` (recherche & facettes).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
  - `dev-book/resources/taxonomie/README.md`

## Contexte

Le vocabulaire du vivier (compétences, postes) arrive en texte libre depuis les profils. Trois
commandes le transforment en un dictionnaire de concepts dédoublonnés, chargé en base :

```
app:vocabulary:harvest  →  app:vocabulary:enrich  →  app:vocabulary:import
(compter, déterministe)    (ranger, LLM)              (charger, déterministe)
```

`harvest` et `import` existaient ; l'étape du milieu (`enrich`) était **faite à la main**. Elle est
désormais outillée (commande dédiée + adaptateur LLM injectable + tests). Le pipeline est décrit dans
la doc d'architecture ci-dessus.

## Invariants (déjà tenus)

- Le LLM ne tourne **que** dans `enrich` (rare, manuel) ; `harvest` + `import` sont sans LLM.
- Tout ce qui est produit entre en statut **`candidat`** — validation humaine séparée.
- `import` idempotent ; réplica `candidate_jarvi` en lecture seule.
- `enrich` n'écrit que `taxonomy.json` (relu en `git diff` avant chargement).

## Livré

- [x] `harvest` (récolte déterministe → `vocabulary.json`).
- [x] `enrich` (regroupement LLM en concepts, structured outputs, clé côté `api/` seulement, tests).
- [x] `import` (upsert idempotent → `vocabulary_concept` / `vocabulary_label`).
- [x] Confort dev `make dev-vocabulary-refresh`.
- [x] Doc d'architecture + mise à jour `dev-book/resources/taxonomie/README.md`.

## Reste à faire / à statuer

- [ ] **Exposition** : aucune route API ne lit le vocabulaire ; le brancher sur la recherche/les
      facettes (dépend de `096`).
- [ ] **Promotion `candidat → validé`** : workflow (relecture, bascule de statut, dépréciation).
- [x] **Couverture de la traîne — deux passes manuelles livrées** (2026-07-22 et 2026-07-24) :
      51,3 % → **70,8 %** des occurrences de compétences, 24,3 % → 32,9 % des postes.
      860 concepts, 3 358 libellés. Reste la traîne vue **2 fois** (~1 675 termes, +7 pts) — ratio
      faible, à laisser à la commande LLM en production.
- [ ] ~~**Couverture de la traîne**~~ : descendre sous 10 occurrences (passes d'enrichissement cadencées)
      et articuler avec le filet **FTS/texte brut** pour ce qui n'est pas rattaché. Mesure au
      2026-07-22, après l'enrichissement manuel de la tête : **493 concepts, 1 072 libellés** →
      **51,3 %** des occurrences de compétences et **24,3 %** des postes. Reste la traîne :
      3 662 compétences vues 2 à 9 fois (+27,9 pts atteignables) et 394 postes (+9,1 pts) ; en
      dessous, 9 367 compétences et 5 848 postes vus **une seule fois** — hors de portée utile.
- [ ] **Concepts proches** : la relation concept↔concept n'est **pas** calculée (constat vérifié
      2026-07-22 : aucune clé `similaires`, 0 relation, aucune table — seule la méthode est décrite
      dans le `_meta`). Chantier ouvert en `111`.
- [ ] **Fraîcheur** : `taxonomy.json` est un instantané figé à l'export ; définir la cadence de
      rafraîchissement (réharvest + enrich + rebuild image en prod).

## Points de vigilance

- **Fichier baké dans l'image** : en prod/preprod, `import` charge le `taxonomy.json` de l'image, pas
  un fichier local → rafraîchir = committer + rebuild + redéployer, puis `import`.
- **Coût LLM** : `enrich` par défaut sur `claude-opus-4-8` ; modèle et seuil de fréquence
  configurables (`ANTHROPIC_VOCABULARY_MODEL`, `VOCABULARY_ENRICH_MIN_FREQUENCY`).
- **Postes bruités** : le concept n°1 récolté est « Stage / Internship » (un statut, pas un métier) —
  à nettoyer avant tout usage des postes comme filtre.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/vocabulary-pipeline.md`
- `dev-book/resources/taxonomie/README.md`
