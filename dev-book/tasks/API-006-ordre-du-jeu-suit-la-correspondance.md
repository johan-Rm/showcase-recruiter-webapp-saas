# TASK API-006 — API : l'ordre du jeu suit la correspondance aux critères

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 25 tests, 189 assertions.
- **Type** : API — moteur de recherche.
- **Dépend de** : `API-005` (l'explication de correspondance fournit le classement).
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28 · `API-002` (le tri unifié qu'elle
  remplace).

## Contexte

Le jeu est aujourd'hui trié **profils rencontrés en tête**, puis par date d'entrée :

```
CASE WHEN c.starInterviewedAt IS NULL THEN 1 ELSE 0 END ASC
m.firstMatchedAt DESC
m.id ASC
```

Avec le passage des mots-clés en **OU**, ce tri ne suffit plus : le jeu s'élargit sans hiérarchie, et
un profil qui satisfait un seul critère se retrouve mêlé à ceux qui les satisfont tous. Sur 256
profils parcourus un par un, l'ordre **est** la fonctionnalité.

## Décisions

### Les profils les mieux appariés d'abord

L'ordre suit le nombre de critères satisfaits. C'est ce que le recruteur a demandé qui gouverne.

### Ce que cela coûte, et qui doit être su

Ce tri **retire aux profils rencontrés leur place en tête**. C'était la seule hiérarchie du jeu, et
le cadrage écrit qu'elle « incarne la promesse » — le profil passé en entretien showcase-recruiter-webapp-saas remontait
avant tous les autres. Le **badge demeure**, la priorité d'affichage disparaît.

L'arbitrage se joue entre *ce que showcase-recruiter-webapp-saas garantit* et *ce que le recruteur a demandé*, et il tranche
pour le second — mais pas au prix d'un effacement, cf. « Ce que Rencontré perd, et ce qu'il garde ».

### Un seul `ORDER BY`, toujours

`API-002` a réuni le tri de la page et celui de la liste complète d'identifiants dans un `ordered()`
privé, précisément pour qu'ils ne divergent pas. Le nouveau tri le remplace **au même endroit** : la
page et `resultIds` doivent continuer à ranger pareil, sans quoi la page N du client cesse d'être la
page N du serveur.

### Elle n'était pas livrable avant `API-011`

Premier passage : le critère « un profil satisfaisant trois critères précède un profil qui n'en
satisfait qu'un » était **intestable**. Le jeu est matérialisé depuis `matchingIds`, qui chaîne un
`andWhere` par critère — un ET strict. Tout membre les satisfaisait donc tous, le rang aurait été
identique pour chacun, et le tri n'aurait rien changé d'observable.

Le OU sur les mots-clés crée les correspondances partielles. C'est lui qui rend cette tâche à la fois
**nécessaire** et **vérifiable**, et c'est pourquoi elle a été livrée après.

### Le rang est matérialisé, pas calculé à la lecture

`SearchResultMembership.matchedCount`, écrit au recalcul du jeu — donc à chaque écriture de la
recherche et à chaque ingestion, le même moment que l'appartenance.

Il le fallait : le jeu se trie **en SQL sur des lignes**, alors que la correspondance se lit **en PHP
sur des entités chargées**. Trier par un score que seul PHP sait produire aurait obligé à charger
tout le jeu pour ordonner une page — et la liste complète d'identifiants n'aurait plus eu le même
ordre que les cartes.

### Le score vient du FILTRE, pas d'une seconde implémentation

Le rang se calcule en rejouant le filtre **un critère à la fois** : pour chaque critère pris seul,
l'ensemble des profils qui le satisfont ; le score d'un profil est le nombre d'ensembles qui le
contiennent.

C'est ce qui garantit qu'il ne peut pas contredire la recherche. Un moteur et un score écrits
séparément se seraient contredits au premier ajustement, et le profil le mieux classé n'aurait plus
été celui que la recherche retient le mieux — le défaut déjà constaté sur « Au Maroc », en plus
sournois.

Le coût est celui d'**une recherche par critère** — trois ou quatre en pratique — et non d'un calcul
par profil : une recherche ramenant deux mille personnes en aurait coûté deux mille.

### Ce que « Rencontré » perd, et ce qu'il garde

Les profils rencontrés passent **en départage** au lieu d'être en tête. Ils cèdent à ce que le
recruteur a demandé, mais gardent le dernier mot **à correspondance égale** — là où ils ne
contredisent rien. La promesse survit exactement dans la zone où elle n'entre pas en conflit.

## Vérification

- Un profil satisfaisant **deux** mots-clés précède celui qui n'en satisfait **qu'un**, et
  `matchedCount` vaut bien 2 et 1.
- La **page** et la **liste complète d'identifiants** rangent pareil — sans quoi la pagination du
  client se décalerait de celle du serveur.
- `SearchTest`, `SearchListsTest`, `CandidateKeywordSearchTest` : **25 tests, 189 assertions**.

## Hors périmètre

L'affichage de la correspondance → `API-005` et l'espace entreprise.
