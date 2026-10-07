# TASK API-052 — l'analyse statique repasse au vert sur les profils de démo

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-15 — PHPStan et le style repassent au vert, et la chaîne qualité va
  désormais jusqu'au bout de ses maillons statiques.
- **Type** : Backend API — **correction d'un défaut relevé au point de contrôle**, jamais parti de la
  lane.
- **Tests** : `sans objet` — le changement porte sur un **helper de test** et ne touche à aucun
  comportement applicatif : il retire un appel qui, par construction, ne faisait rien. La preuve est
  la suite elle-même, qui doit rester verte, et l'analyse statique qui doit repasser.
- **Réf** : `API-042`, dont le commit porte le défaut · précédent de même nature : `API-046` ·
  `api/tests/Functional/DemoProfilesSearchTest.php`.

## Contexte

**La branche était prête, et l'analyse statique l'a refusée** — relevé en ouvrant la PR de la lane,
avant tout push :

```
tests/Functional/DemoProfilesSearchTest.php:69
  Parameter #1 $array (list<string>) of array_values is already a list, call has no effect.
  🪪  arrayValues.list
```

**Le défaut.** Le helper qui extrait les noms de famille enveloppe un `array_map` dans un
`array_values`. Or `array_map` sur une liste rend déjà une liste : l'appel ne réindexe rien. PHPStan
le voit parce que la signature du helper **promet** une `list<string>` — c'est la promesse qui rend
l'appel visiblement inutile.

**Conséquence si c'était parti :** `api-quality` rouge en CI, donc PR bloquée — mais surtout la
chaîne `composer quality` **s'arrête à PHPStan**, si bien que ni le style, ni les frontières
d'architecture, ni les 515 tests n'auraient tourné. Un seul avertissement de style masquait toute la
suite.

**Rien n'est parti.** Le défaut vit dans un commit non mergé, sur une branche jamais poussée.

**Un second refus derrière le premier.** PHPStan une fois vert, la chaîne est allée jusqu'au style et
s'est arrêtée là : le formateur voulait réaligner un bloc de paramètres documentés dans
`AppFixtures.php` — même commit, même origine. C'est la démonstration en direct du point précédent :
tant qu'un maillon casse, les suivants ne s'expriment pas.

## Périmètre

- **L'appel `array_values` inutile est retiré** du helper `familyNamesMatching`.
- **Le bloc de paramètres documentés d'`AppFixtures.php` est réaligné** par le formateur du projet
  (`composer cs:fix`) — une ligne, aucun code touché.

## Hors périmètre

- **Tout le reste du fichier** et des fixtures d'`API-042` : cette tâche ne fait que rétablir ce que
  les gates exigent.
- **Le comportement des tests** : aucun cas n'est ajouté, retiré ni modifié.

## Décisions de cadrage

### Une carte pour une ligne

Le dépôt tient « un commit = une tâche » sans exception, et `API-046` a créé le précédent : un défaut
trouvé au point de contrôle prend sa carte, si petit soit-il. C'est ce qui fait qu'un `git log`
raconte encore quelque chose six mois plus tard.

### Retirer l'appel plutôt que taire l'avertissement

PHPStan propose de désactiver le contrôle (`treatPhpDocTypesAsCertain: false`). L'appel est
réellement inutile : le supprimer coûte une ligne, désactiver le contrôle coûte tous les cas futurs
qu'il aurait attrapés.

## Règles et cas limites

- **Le contrat du helper ne bouge pas** : il rend toujours une `list<string>`, dans le même ordre.
- **La classe est jouée seule** avant la suite complète : les tests de recherche flakent par
  isolation, et un rouge isolé n'accuserait pas ce changement.

## Documentation impactée

Aucune : ni surface d'API, ni convention de dossier, ni contrat. Le diff est un appel de fonction
retiré dans un test.

## Vérification

Sorties fraîches, après correction :

```
composer analyse    [OK] No errors
composer cs:check   Found 0 of 515 files that can be fixed
composer deptrac    Allowed 1626 · Warnings 0 · Errors 0
phpunit DemoProfilesSearchTest   OK (3 tests, 10 assertions)
```

## Livré

- `api/tests/Functional/DemoProfilesSearchTest.php` — l'appel `array_values` retiré.
- `api/src/DataFixtures/AppFixtures.php` — le bloc de paramètres documentés réaligné par
  `composer cs:fix`.

## Reste à faire (hors périmètre)

**La chaîne libérée a découvert autre chose.** Une fois les maillons statiques verts, `composer
quality` est allé jusqu'aux 531 tests et **cinq échouent dans `RevealTest`**, tous à un crédit près,
de façon déterministe. La cause est ailleurs — le portefeuille des fixtures — et se traite dans sa
propre tâche. Cette carte-ci ne prétend pas y toucher : elle a rendu le rouge visible, c'est déjà son
apport.

## Définition de terminé

- [x] PHPStan ne rapporte plus d'erreur.
- [x] Le style ne rapporte plus de fichier à corriger.
- [x] Les frontières d'architecture restent sans violation.
- [x] `DemoProfilesSearchTest` reste verte, sans modification de ses cas.
- [x] La chaîne qualité dépasse les maillons statiques et atteint la suite de tests.
