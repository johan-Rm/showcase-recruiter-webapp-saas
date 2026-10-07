# TASK 094a — API : normalisation partagée du vocabulaire

- **Créée le** : 2026-07-22.
- **Statut** : ✅ fait — tranche *refactor seul* de `094`, sans changement de données.
- **Type** : API — pipeline vocabulaire (récolte, enrichissement).
- **Lié à** : `094` (parent : normalisation & qualification à l'import), `103` (pipeline
  vocabulaire), `105`/`107` (clé entreprise, hors périmètre — voir plus bas).

## Contexte

Le vocabulaire était normalisé à **trois endroits** avec trois règles différentes : les intitulés de
poste via `TitleCanonicalizer`, les compétences en SQL (`lower(btrim())`), et la couverture de
l'enrichissement via un `mb_strtolower(trim())` local.

Cette divergence a coûté cher le 2026-07-22 :

- `travail d'équipe` et `travail d’équipe` comptaient pour **deux termes distincts** ; la variante
  typographique (308 occurrences) échappait au dictionnaire, ~860 occurrences au total.
- Le rognage de ponctuation travaillait **par octet** (`trim()` avec liste de caractères) et
  produisait de l'UTF-8 invalide sur les termes en écriture arabe.

## Ce qui a été fait

- `TitleCanonicalizer` devient **`TermNormalizer`**, point de normalisation unique du vocabulaire :
  - `normalize()` — forme commune (entités HTML, apostrophes, casse, espaces). **Ne rogne pas** la
    ponctuation de bord, porteuse de sens sur les compétences (« .net framework »).
  - `canonicalizeTitle()` — coupe aux séparateurs de bruit, puis normalise et rogne la ponctuation.
- Les trois consommateurs partagent cette classe : récolte des compétences, récolte des intitulés,
  et test de couverture de `VocabularyEnricher`.
- Récolte vérifiée **identique** avant/après le renommage (13 746 compétences, 6 597 postes).

## Écarté sciemment : la clé entreprise

`CompanyKey` (et son jumeau SQL dans `CompanySectorHarvester`) normalise moins fort — `trim` +
`lower`, sans apostrophes ni espaces. **Ne pas l'aligner**, mesure à l'appui :

- sur 2 412 noms d'entreprise sans `linkedinCompanyId`, une normalisation plus forte change 65 clés
  mais ne fusionne **aucun** doublon → gain nul ;
- elle casserait 2 des 186 clés `name:` gelées dans `sectors.json`, dont le secteur ne se
  rattacherait plus.

Le jour où l'alignement deviendra utile, il faudra migrer les clés de `sectors.json` dans le même
commit.

## Points de vigilance

- **Deux implémentations de la clé entreprise** cohabitent toujours (PHP et SQL) et doivent rester
  synchronisées — l'avertissement est porté par le docblock de `CompanyKey`.
- Les autres usages de `mb_strtolower` (nom de fichier CV, slug d'export CSV, paramètres DQL) ne
  sont **pas** de la normalisation de terme : les laisser où ils sont.

## Reste à faire (dans `094`)

La tranche *données* du parent reste entière : clés normalisées stockées en base, flags de
couverture par champ, qualification de source.
