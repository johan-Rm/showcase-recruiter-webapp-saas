# TASK API-053 — les tests de dévoilement comptent en écart, pas en absolu

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — les cinq tests comptent l'écart qu'ils provoquent, et
  `RevealTest` repasse au vert sans qu'aucune fixture ni ligne applicative n'ait bougé.
- **Type** : Backend API — **correction d'un défaut relevé au point de contrôle**, jamais parti de la
  lane. Aucun code applicatif touché : cinq tests apprennent à compter.
- **Tests** : c'est la tâche elle-même — `RevealTest` doit repasser au vert **sans que les fixtures
  ni le code de dévoilement changent**, et rester verte si la démo gagne un profil dévoilé de plus.
- **Réf** : `API-042`, dont les fixtures ont révélé le défaut · `API-052`, qui a libéré la chaîne
  qualité et rendu ce rouge visible · `api/tests/Functional/RevealTest.php`.

## Contexte

**Cinq tests de dévoilement échouent, tous du même écart : un profil dévoilé de trop.**

```
RevealTest::testRevealDebitsOneCreditAndRecordsProfile        2 ≠ 1
RevealTest::testEachAccessIsBilledSeparately                  3 ≠ 2
RevealTest::testRevealIsIdempotent                            2 ≠ 1
RevealTest::testRevealWithInsufficientCreditsIsRejected       1 ≠ 0
RevealTest::testBatchRevealRefusesTheWholeLotWhenCreditsAreShort  1 ≠ 0
```

Ce n'est pas un flake : la classe rejouée seule échoue à l'identique, cinq fois sur cinq.

**La cause.** `API-042` a rendu les profils de démo crédibles, et pour cela sème sur l'organisation de
démo **des profils déjà dévoilés**, avec leurs transactions de crédit. C'était le but : un espace
entreprise qu'on montre ne peut pas avoir un historique vide.

**Le défaut est dans les tests, pas dans les fixtures.** `RevealTest` compte en **absolu** — « après
un dévoilement, la liste en contient 1 » — ce qui suppose une organisation vierge. Cette hypothèse
n'a jamais été écrite nulle part ; elle était vraie par accident, et elle a cessé de l'être le jour où
la démo a gagné un historique. Le solde, lui, ne dérive pas : les fixtures le remettent à sa valeur
d'accueil.

**Conséquence si c'était parti :** `api-quality` rouge en CI, PR bloquée — et surtout un test qui
casse à chaque enrichissement de la démo, donc une pression permanente pour appauvrir les fixtures
afin de garder la suite verte.

## Périmètre

- **Les cinq tests comptent l'écart** qu'ils provoquent : ils lisent l'état avant d'agir, et
  vérifient ce que leur geste a changé.
- **Les refus vérifient que rien n'a bougé**, plutôt que de vérifier un total nul.

## Hors périmètre

- **Les fixtures d'`API-042`** : elles font ce qu'on leur demande, et les rétrécir pour arranger un
  test serait prendre le problème à l'envers.
- **Le code de dévoilement** : aucun comportement n'est en cause, aucune ligne applicative ne bouge.
- **Les autres classes de la suite** : elles passent, y compris celles qui touchent aux crédits.
- **Le solde du portefeuille** : les assertions de solde sont justes et le restent.

## Décisions de cadrage

### Corriger les tests, pas les données

Un test qui dépend du nombre exact de lignes qu'un jeu de démonstration contient mesure les fixtures
autant que le code. Le rendre relatif le rend **vrai pour toujours** : il continuera de dire « un
dévoilement en ajoute exactement un » quel que soit l'historique de départ.

### Les refus se vérifient par l'immobilité

« Le total reste ce qu'il était » dit exactement ce qu'un refus doit garantir — rien n'a été débité,
rien n'a été dévoilé. « Le total vaut zéro » disait la même chose par un raccourci qui ne tenait que
sur une base vierge.

## Règles et cas limites

- **Aucune assertion n'est affaiblie** : un écart attendu de +1 reste aussi strict qu'un absolu de 1,
  et il vaut sur n'importe quel état de départ.
- **L'ordre des dévoilements servis** (du plus récent au plus ancien) reste vérifié sur les lignes que
  le test vient de créer.
- **La classe est jouée seule** avant la suite complète : les tests de recherche flakent par
  isolation, et un rouge isolé n'accuserait pas ce changement.

## Documentation impactée

Aucune : ni surface d'API, ni convention de dossier, ni contrat. Le diff ne touche qu'une classe de
test.

## Vérification

```
phpunit tests/Functional/RevealTest.php    OK (17 tests, 175 assertions)
```

Avant correction, la même classe rendait `Tests: 17, Failures: 5` — et les cinq mêmes échecs
apparaissaient dans la suite complète (`Tests: 531, Assertions: 3341, Failures: 5`).

Le diff ne touche **que** `RevealTest.php` : ni les fixtures, ni le dévoilement, ni les crédits.

## Livré

- `api/tests/Functional/RevealTest.php` — un helper qui lit le nombre de profils déjà dévoilés, cinq
  assertions passées en écart ou en immobilité, et l'ordre des lignes vérifié sur les deux que le
  test vient de créer.

## Définition de terminé

- [x] `RevealTest` est verte, sans qu'aucune fixture ni ligne applicative n'ait changé.
- [x] Les cinq tests comptent un écart ou une immobilité, jamais un absolu hérité des fixtures.
- [x] `composer quality` va au bout : phpstan, cs, deptrac et la suite complète.
