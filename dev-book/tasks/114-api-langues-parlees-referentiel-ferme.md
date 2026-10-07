# TASK 114 — API : langues parlées (référentiel fermé) et niveau

- **Créée le** : 2026-07-22.
- **Statut** : ✅ livré et vérifié en réel (87 % des profils).
- **Type** : API — ingestion Jarvi / qualification, puis filtre de recherche.
- **Lié à** : `095` (mêmes colonnes normalisées), `062` (filtres conditionnés à la couverture).
- **Réf** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` (§ Langues)
  - `dev-book/cadrage/entreprise/profil_dimensions_vivier.md` (§ Composition du vivier)

## Contexte

Le cadrage des critères annonce **« Langue parlée » en filtre V1** et **« Niveau minimum » en V1
secondaire**. **Rien n'est fait** : la langue n'est ni normalisée, ni filtrable.

Or c'est la dimension la plus simple du vivier :

| Dimension | Valeurs distinctes | Occurrences | Couverture | Dispersion |
| --- | ---: | ---: | ---: | ---: |
| **Langues** | **29** | 3 144 | **88 %** | **1 %** |
| Compétences | 13 734 | 45 226 | 92 % | 30 % |
| Écoles | 2 365 | 4 496 | 92 % | 53 % |

**Dispersion de 1 %** : c'est un **vocabulaire fermé**. Les mêmes codes reviennent d'un profil à
l'autre, la liste tient sur un écran. Aucune récolte, aucun regroupement, **aucun LLM** — un
référentiel figé suffit.

Jarvi fournit déjà la donnée structurée : `locales[] = { language, country, proficiency }`
(ex. `{ ar, NATIVE_OR_BILINGUAL }`, `{ en, PROFESSIONAL_WORKING }`).

## Objectif

Rendre la langue parlée et son niveau **filtrables**, sans construire de machinerie.

## Périmètre pressenti

- **Référentiel fermé** des langues attendues au produit : français, anglais, arabe/darija — le
  cadrage précise qu'**arabe et darija ne forment qu'une seule option**. Les autres codes présents
  (espagnol, néerlandais…) restent stockés sans être proposés en facette.
- **Normalisation** des codes à l'ingestion (la source est déjà en ISO court).
- **Niveau** : conserver l'échelle **telle que Jarvi la fournit**.
- **Stockage filtrable** : le modèle actuel range les langues en JSON opaque, ce qui interdit un
  filtre DQL simple — même problème que l'expérience par secteur, même solution (table normalisée,
  cf. `095`).
- **Filtre de recherche** : langue parlée, puis niveau minimum.

## Invariants

- Champ **dérivé et recalculable** ; Jarvi reste maître.
- Une langue absente n'est pas déduite : pas de langue par défaut.

## Mesuré après réingestion (1 699 profils)

- **1 479 profils** portent au moins une langue, soit **87 %** (cadrage : 88 %).
- **3 091 lignes**, dont **2 033 avec un niveau** — soit **34 % sans niveau**, ce qui confirme qu'un
  filtre « niveau minimum » ne peut pas être appliqué par défaut.
- Répartition : `fr` 1 222 · `en` 822 · `ar` 442 · `es` 250 · `nl` 129 · `de` 79.
- Filtre vérifié : **466 candidats** en français de niveau ≥ professionnel complet.

Trois surprises des données réelles, absentes du cadrage :
- le niveau est **absent sur un tiers des entrées** (valeur la plus fréquente) ;
- une variante `PROFESSIONAL` hors standard LinkedIn (3 occurrences), ramenée au palier professionnel ;
- un code `xx` (21 occurrences) qui n'est pas une langue, écarté.

## Critères d'acceptation

- [ ] La langue parlée est filtrable, seule puis combinée à un niveau minimum.
- [ ] Arabe et darija comptent pour **une seule** option de filtre.
- [ ] La couverture réelle est mesurée (attendu ≈ 88 %) et articulée avec `062`.
- [ ] Tests sur des valeurs réelles du vivier, y compris les codes hors référentiel produit.

## Points de vigilance

- ⚠️ **L'échelle de niveau est celle de Jarvi / LinkedIn** (`NATIVE_OR_BILINGUAL`,
  `PROFESSIONAL_WORKING`…), **pas le CECRL**. Ne pas convertir en A1–C2 : la correspondance n'existe
  pas, et l'inventer tromperait l'entreprise.
- **`proficiency` est parfois `null`** (visible dans les données réelles) : la langue est alors connue
  sans son niveau — le filtre « niveau minimum » doit l'exclure, pas le supposer.
- Ne pas confondre avec les langues **du profil** (langue d'interface, i18n) : il s'agit ici des
  langues **parlées par le candidat**.
