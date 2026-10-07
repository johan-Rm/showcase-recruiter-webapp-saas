# TASK API-003 — API : ce que les gates ont trouvé à l'ouverture de la PR

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28.
- **Type** : API — correctif de style et d'annotation, aucun changement de comportement.
- **Réf** : task 163 (le docblock égaré) · task API-001 et API-002 (les deux imports).

## Contexte

Les gates tournent une fois par lane, à l'ouverture de la PR — pas à chaque commit. C'est ce passage
qui a trouvé trois écarts, aucun visible à l'exécution :

- **`RevealTest.php`** portait `@return array{0: string, 1: string}` au-dessus d'un test `void`. Un
  docblock recopié depuis le helper voisin `organizationAndCandidateIds()` et laissé là. phpstan le
  refuse — à raison : une annotation qui contredit la signature égare le lecteur avant l'analyseur.
- **`ConfigProvider.php`** et **`SearchResultMembershipRepository.php`** référençaient `\BackedEnum`
  et `\Stringable` en pleine qualification plutôt que par un `use`. La règle de style du projet veut
  l'import ; php-cs-fixer l'applique.

## Pourquoi un commit à part

Les trois écarts appartiennent à trois tâches déjà commitées (163, API-001, API-002), et le squash
est désactivé : réécrire ces commits demanderait un rebase interactif, indisponible ici. Un commit de
correction est plus honnête qu'une réécriture d'historique — il dit **quand** le défaut a été vu, et
c'est une information.

## Vérification

`composer quality` complet : `lint:container`, phpstan, php-cs-fixer, deptrac et la suite phpunit.

## Ce que ça dit du processus

Le premier passage de gates de la lane a trouvé un défaut que la task 163 déclarait vert. La leçon
n'est pas de gater plus souvent — c'est de ne pas lire un code de sortie à travers un `tail` :
`composer quality | tail -60` rend le statut de `tail`, toujours 0. La sortie disait « ERROR », le
code disait « OK ».
