# TASK 112 — API : niveau d'études dérivé à l'import

- **Créée le** : 2026-07-22.
- **Statut** : ✅ livré et vérifié en réel (couverture mesurée **77 %**).
- **Type** : API — ingestion Jarvi / qualification dérivée.
- **Lié à** : `095` (axes d'expérience — même patron), `020`/`034` (ingestion), `062` (filtres
  conditionnés à la couverture).
- **Réf** :
  - `dev-book/cadrage/entreprise/profil_dimensions_vivier.md` (§ Qualifications portées implicitement)
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`

## Contexte

Le cadrage identifie **trois qualifications portées implicitement** — non des champs du profil, mais
des informations lisibles dans les données existantes. Avec cette carte, les trois sont traitées :

| Qualification | Se lit dans | Couverture à la source | État |
| --- | --- | ---: | --- |
| Séniorité | les intitulés de poste | 71 % | ✅ `095` |
| Secteur d'activité | les sociétés | 59 % | ✅ `105` + `107` |
| **Niveau d'études** | **les titres de formation** | **79 %** | ✅ **cette carte** |

## Livré

- [x] **`EducationLevel`** : nomenclature fermée de 6 valeurs (bac, bac+2, licence, master,
      ingénieur, doctorat) + `rank()` pour retenir le plus élevé.
- [x] **`EducationLevelCalculator`** : reconnaissance par motifs sur texte normalisé (minuscules,
      accents dépliés), niveau **le plus élevé** du parcours.
- [x] **Colonne `educationLevel`** sur la réplica, **nullable** — à côté d'`experienceYears`,
      `seniority`, `currentRoleMonths`.
- [x] Tests sur des intitulés **réels** du vivier, dont les pièges (« Bachelor » et « bac+2 »
      contiennent « bac »).

## Invariants

- Réplica en lecture seule ; le niveau est **dérivé et recalculable**, Jarvi reste maître.
- **Aucun niveau par défaut** : un titre illisible ou absent ne produit rien, jamais « baccalauréat ».
  Un faux niveau serait pire que pas de niveau (même règle que le secteur non classé).
- Le niveau retenu est le **plus élevé** du parcours, pas le plus récent : reprendre un bac+2 après
  un master ne fait pas redescendre.

## Mesuré après réingestion (1 699 profils)

**1 309 profils qualifiés, soit 77 %** — à comparer aux 79 % annoncés par le cadrage : l'écart est
marginal, la reconnaissance par motifs tient ses promesses.

| Niveau | Profils |
| --- | ---: |
| Master | 809 |
| Licence | 198 |
| Ingénieur | 154 |
| Bac+2 | 62 |
| Baccalauréat | 55 |
| Doctorat | 31 |

L'ordre observé reproduit celui décrit par le cadrage (master en tête, puis licence et ingénieur),
ce qui confirme que les motifs attrapent bien les mêmes intitulés.

## Reste à faire

- [ ] Exposition produit (affichage, filtre) — hors périmètre de cette carte.

## Points de vigilance

- **Arbitrage produit** : ingénieur est classé juste au-dessus du master (tous deux bac+5, le
  diplôme d'ingénieur étant le plus spécifique). Ajustable dans `EducationLevel::rank()` sans
  toucher au calcul.
- **M1 compte comme master** : simplification assumée (bac+4 sur un cursus master).
- **Couverture partielle assumée** : à articuler avec `062` pour ne pas proposer un filtre qui
  renvoie 0.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`
