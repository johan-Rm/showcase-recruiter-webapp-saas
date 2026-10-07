# TASK API-008 — API : « Au Maroc » devient un fait du serveur

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 3 tests, 33 assertions.
- **Type** : API — surface de contrat, correction d'une règle en double.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

La présence au Maroc est calculée **deux fois, différemment**, et les deux versions ne disent pas la
même chose.

| Où | Règle |
| --- | --- |
| Front — `services/search/presence.ts` | une fenêtre couvre **aujourd'hui** : `startDate ≤ jour ≤ endDate` |
| API — `CandidateRepository` | une fenêtre chevauche **les N prochains jours** : `endDate ≥ aujourd'hui AND startDate ≤ aujourd'hui + N` |

### Correction — ce n'était PAS une règle écrite deux fois

Cette carte annonçait une divergence. L'écriture montre autre chose : ce sont **deux questions
différentes sous un même nom**.

Le badge répond « présent **aujourd'hui** ». Le critère répond « présent **dans les N prochains
jours** ». Un candidat qui arrive dans cinq jours entre donc légitimement dans une recherche « sous
15 jours » **sans** être au Maroc à l'instant — et ne pas lui donner le badge est **correct**.
Aligner les deux ferait mentir l'un des deux.

Le défaut réel est ailleurs, et il subsiste : deux notions portent le même libellé à l'écran. Le
recruteur qui filtre sur une présence et ne voit aucun badge n'a aucun moyen de comprendre pourquoi.
C'est un problème de **nommage**, à traiter dans l'espace entreprise, pas un bug de calcul.

### Ce qui justifiait quand même de remonter le verdict

S'y ajoute que le verdict dépend de **quand** on le calcule : rendu dans le navigateur, il suit
l'horloge et le fuseau du poste, et une page ouverte depuis la veille affiche un badge périmé.

Le commentaire du fichier front assume ce choix — « l'API descend les fenêtres, pas le verdict » —
mais il a été écrit **avant** que l'API porte elle-même un filtre de présence. Depuis, la règle
existe des deux côtés, et c'est cela le défaut : deux implémentations d'une même notion, dans deux
langages, avec deux définitions du présent.

## Décision

Le **verdict** descend avec la carte. Une seule règle, côté serveur, celle qui sert déjà à filtrer.

Le front garde les **fenêtres** pour ce qu'il en fait d'autre : afficher la période en cours ou la
prochaine, qui reste une mise en forme et pas une règle métier.

**« Disponible » reste au front**, et ce n'est pas une incohérence : c'est une égalité sur
`availability`, un champ déjà servi. Un second booléen dupliquerait l'enum, et figerait dans le
contrat un choix d'affichage — que « Disponible » veuille dire `IMMEDIATE` et pas aussi
`NOTICE_1M` est une décision produit qui peut bouger.

## Vérification

- La carte porte `isInMorocco`, calculé côté serveur.
- Le test le sépare explicitement du filtre : deux profils passent une recherche « sous 15 jours »,
  **un seul** porte le badge — celui dont le séjour couvre aujourd'hui.
- `CandidateTest` : **3 tests, 33 assertions**.

## Reste à faire (hors périmètre de cette tâche)

- **Retirer le calcul du front** (`services/search/presence.ts` ne garde que la mise en forme des
  fenêtres) et **lever l'ambiguïté de libellé** entre « au Maroc aujourd'hui » et « séjour à venir ».
  Les deux touchent du code consommé par l'espace entreprise : ils partent avec sa branche, pas avec
  celle de l'API.
