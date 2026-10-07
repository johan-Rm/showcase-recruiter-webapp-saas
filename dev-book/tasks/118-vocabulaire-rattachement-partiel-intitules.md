# TASK 118 — Vocabulaire : rattacher les intitulés par correspondance partielle

- **Créée le** : 2026-07-22.
- **Statut** : ✅ fait le 2026-07-22 — `TitleConceptResolver`.
- **Lié à** : `095` (expérience par métier, premier bénéficiaire), `103` (pipeline vocabulaire),
  `111` (concepts proches), `096` (couche sémantique — arbitrage de la stratégie de matching).

## Contexte

Un intitulé de poste n'est rattaché à un concept que par **égalité exacte** de sa forme canonique.
C'est ce qui plafonne toute la chaîne des postes.

Mesuré le 2026-07-22 sur les 9 955 positions du vivier :

| Stratégie de rattachement | Positions rattachées |
| --- | ---: |
| Égalité exacte (actuel) | 2 652 — **26,6 %** |
| Un terme connu apparaît dans l'intitulé | 6 735 — **67,7 %** |

**Sur 7 303 intitulés non rattachés, 4 083 (56 %) contiennent déjà un terme du dictionnaire.**
Le problème n'est donc pas la taille du dictionnaire — élargir la traîne des postes ne rapporterait
que +9 points — mais la **façon de comparer**.

Conséquence directe : l'expérience par métier (`095`) ne voit que 33 % du parcours médian d'un
candidat. Ce chantier est ce qui la rendrait fiable.

## Le piège à traiter — la raison pour laquelle ce n'est pas déjà fait

Une correspondance par sous-chaîne naïve, plus longue d'abord, produit de vraies erreurs :

| Intitulé | Rattaché à | Verdict |
| --- | --- | --- |
| `juriste stagiaire` | Stage / Internship | ❌ le métier (juriste) est perdu au profit du statut |
| `software test manager` | Manager | ❌ un terme générique avale un métier précis |
| `it operating delivery manager` | Manager | ❌ idem |
| `chef de projet r&d` | Chef de projet | ✅ |
| `strategic account executive` | Account Manager | ✅ |

Les termes **génériques** (`manager`, `ingénieur`, `consultant`) et les **statuts** (`stagiaire`)
sont courts, fréquents, et raflent les intitulés précis.

## Ce qui a été livré

`TitleConceptResolver` applique trois règles, dans cet ordre :

1. **Un mot entier, jamais une sous-chaîne** — « manager » ne se déclenche pas sur « management ».
2. **Le métier bat le statut** — « stage auditeur interne » devient Auditeur, pas Stage.
3. **Le plus spécifique gagne** — le libellé le plus long en mots d'abord, puis le plus RARE (les
   termes génériques sont aussi les plus fréquents).

Effet mesuré sur l'expérience par métier (`095`), après réingestion `--full` :

| | Avant (égalité exacte) | Après |
| --- | ---: | ---: |
| Candidats avec au moins un métier | 966 | **1 405** |
| Lignes (candidat, métier) | 1 500 | **3 344** |
| Postes rattachés | 2 043 | **5 500** |
| Parcours médian couvert | 33 % | **62 %** |

`candidate_job_experience.exact_positions` conserve la part reconnue par égalité (2 043 sur 5 500,
soit 37 %) : les deux qualités de rattachement ne se confondent pas dans un même chiffre.

## Piste retenue

- Préférer le concept le plus **spécifique**, pas le terme le plus long : pondérer par la fréquence
  du terme dans le vivier (un terme rare est plus discriminant).
- **Déclasser les statuts** : ne rattacher au statut que si aucun métier ne correspond.
- Exiger une correspondance sur un **mot entier**, pas sur une sous-chaîne quelconque.
- Conserver la trace du mode de rattachement (exact / partiel) pour pouvoir mesurer, et ne pas
  mélanger les deux qualités dans un même filtre.

## Critères d'acceptation

- [x] Taux de rattachement mesuré avant/après, sur les mêmes 9 955 positions : 26,6 % → 67,7 %.
- [x] **Taux d'erreur mesuré sur un échantillon de 30 rattachements partiels relus à la main** :
      ~67 % justes, ~27 % appauvrissants (`qa manager` → Manager), **~7 % faux**
      (`architecte conseiller` → Architecte IT, `technicien d'essais` → Technicien de maintenance).
      Jugé acceptable pour ÉLARGIR une recherche, à condition de tracer le mode — d'où
      `ResolvedJob::$exact` et la colonne `exact_positions`.
- [x] Les cas du tableau ci-dessus sont couverts par des tests.
- [x] `095` recalculé, écarts inscrits dans la roadmap.
- [ ] **`111` volontairement laissé sur l'égalité exacte** : la co-occurrence est sensible aux faux
      positifs, qui déplaceraient les proximités sans que rien ne le signale. À rouvrir seulement
      avec une mesure dédiée de l'effet sur les paires.

## Points de vigilance

- **Ne pas dégrader `111`** : les concepts proches reposent sur la co-occurrence. Un rattachement
  plus large mais plus faux déplacerait les proximités sans qu'on s'en aperçoive.
- **Le sens de l'erreur change** : aujourd'hui l'erreur est en moins (faux négatifs), inoffensive
  pour une recherche. Un matching partiel introduit des faux **positifs**, qui, eux, se voient.
