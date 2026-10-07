# TASK 117 — API : poste actuel dérivé à l'import (titre, entreprise, ambiguïté)

- **Créée le** : 2026-07-22.
- **Statut** : ✅ fait le 2026-07-22 — `CurrentPositionExtractor`.
- **Type** : API — ingestion Jarvi / qualification à l'import.
- **Lié à** : `095` (expérience & séniorité — la distinction *poste actuel / poste occupé* y est
  ouverte), `103`/`111` (vocabulaire des postes), `062` (filtres), `061` (recherche).

## Contexte

Le poste actuel est **défini** ({@see CurrentRoleDurationCalculator}) — la position sans `endDate`
dont la date de début est la plus récente — mais cette définition ne sert qu'à calculer une
**durée**. Le poste lui-même, titre et entreprise, **n'est stocké nulle part** : on sait qu'un
candidat est en poste depuis 18 mois, pas lequel.

Et la définition ne tranche que la moitié du vivier (mesuré le 2026-07-22) :

| Situation | Profils | % |
| --- | ---: | ---: |
| 1 poste en cours — non ambigu | 865 | 52,7 % |
| Aucun poste en cours | 484 | 29,5 % |
| 2 postes en cours | 207 | 12,6 % |
| 3 et plus | 85 | 5,2 % |

Sur les **292 profils ambigus**, 53 portent la **même entreprise** (une promotion, une ligne non
clôturée : le départage par date la plus récente y est juste) ; les **239 autres relèvent
d'entreprises différentes**, et le départage peut alors désigner une activité secondaire plutôt que
l'emploi principal. 23 profils ambigus n'ont même pas de date de début exploitable.

Les 29,5 % sans poste en cours ne sont pas des candidats sans emploi : c'est le plus souvent un
profil LinkedIn dont la dernière expérience n'a jamais été rouverte.

## Objectif

Matérialiser le poste actuel à l'ingestion — titre, entreprise, concept métier rattaché — **avec son
degré de certitude**, pour que chaque consommateur cesse de refaire le calcul et sache quand ne pas
s'y fier.

## Ce qui a été livré

`CurrentPositionExtractor` porte désormais la **définition unique** du poste actuel. Elle vivait
auparavant dans un calculateur qui n'en tirait qu'une durée sans jamais dire de quel poste il
s'agissait ; ce calculateur est supprimé — deux définitions concurrentes du même concept sont
exactement ce que `094a` a corrigé ailleurs.

Résultat après réingestion `--full` :

| Certitude | Profils | % | dont métier reconnu |
| --- | ---: | ---: | ---: |
| CERTAIN | 918 | 54,0 % | 558 |
| ABSENT | 545 | 32,1 % | — |
| AMBIGUOUS | 236 | 13,9 % | 133 |

Les promotions internes (plusieurs postes ouverts chez le MÊME employeur) sont classées CERTAIN :
c'est ce qui fait passer l'ambiguïté de 17,8 % à 13,9 %.

**Exposition et masquage** — l'intitulé et la certitude *décrivent sans nommer*, ils sortent même
sur un profil masqué ; **l'employeur ne sort qu'au dévoilement**, conformément à la ligne de partage
du masquage. Le concept métier, agrégat, est toujours visible.

## Périmètre

- Poste actuel dérivé et stocké : titre brut, entreprise, concept métier rattaché
  (via le vocabulaire), date de début.
- **Marqueur explicite** : certain / ambigu (plusieurs postes en cours) / absent. C'est lui qui
  distingue « pas de poste actuel » de « on ne sait pas trancher ».
- Départage documenté et testé, y compris le cas « même entreprise » (promotion).
- Réingestion `--full` pour peupler l'existant.

## Hors périmètre

- Choisir l'emploi *principal* parmi plusieurs postes concurrents : sans signal supplémentaire
  (temps de travail, salaire), le classement serait une invention. On expose l'ambiguïté.

## Invariants

- Champ **dérivé**, recalculé à chaque ingestion, jamais saisi ; la réplica reste en lecture seule.
- **Absent ≠ zéro** : aucun poste en cours ne se traduit pas par un poste vide, mais par un état
  « absent » lisible.

## Critères d'acceptation

- [x] Poste actuel stocké avec titre, entreprise et concept métier quand il est déterminable.
- [x] Les trois états (certain / ambigu / absent) sont distingués en base et exposés
      (`currentPositionCertainty` sur le détail candidat).
- [x] Taux mesurés et inscrits dans la roadmap.
- [x] Tests : cas non ambigu, deux postes même entreprise, deux postes entreprises différentes,
      aucun poste en cours, date de début manquante, intitulé purement statutaire.

## Points de vigilance

- **Couverture du vocabulaire des postes** : le concept métier n'est reconnu que sur **691 postes
  actuels sur 1 154** (60 % de ceux qui existent), et une part vient de la reconnaissance partielle
  (`118`), fausse dans ~7 % des cas. Ne pas en faire un filtre dur.
- **« Stage / Internship » est le concept de poste n°1** : un statut, pas un métier. À écarter avant
  d'exploiter le poste actuel comme critère.
- **Ne pas dériver la séniorité du seul poste actuel** : elle vient de l'ancienneté cumulée (`095`).
