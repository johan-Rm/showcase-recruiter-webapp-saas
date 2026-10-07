# TASK API-005 — API : dire pourquoi chaque profil est dans le jeu

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 39 tests, 331 assertions.
- **Type** : API — surface de contrat et moteur de recherche.
- **Débloque** : `API-006` (l'ordre suit la correspondance) · le surlignage et la liste des critères
  non trouvés côté entreprise.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

L'API **filtre**, elle n'**explique** pas. Elle sait dire qu'un profil appartient au jeu, jamais
lesquels des critères il satisfait. Trois fonctionnalités décidées pendant la revue en dépendent, et
c'est la clé de voûte des trois.

Le besoin devient pressant avec le passage des mots-clés en **OU** : un profil peut entrer dans le
jeu en ne satisfaisant qu'un critère sur quatre. Sans explication, le recruteur voit un résultat qui
gonfle sans comprendre pourquoi — c'est exactement la surprise inverse de celle du 2026-07-27, où
`développeur` + `frontend` ramenait un seul profil.

## Décisions

### Chaque carte porte le détail de sa correspondance

Pour chaque critère de la recherche : satisfait ou non, et **par quoi**. Le « par quoi » n'est pas
un ornement — il est indispensable pour l'intitulé de poste.

### L'intitulé de poste ne se surligne pas, il s'énonce

Le critère passe par le dictionnaire de concepts :

```
LOWER(jc.prefLabel) = :jobTerm  OR  un VocabularyLabel du concept = :jobTerm
```

Le critère « développeur » retrouve donc un profil dont le poste s'affiche « Frontend developer ».
Chercher la chaîne dans la fiche ne surlignerait rien. La réponse doit porter **le libellé du profil
qui a matché**, pour que l'écran puisse dire : *votre critère « développeur » correspond à son poste
« Frontend developer »*.

C'est aussi ce qui rend enfin visible le travail du dictionnaire, promis par l'aide du champ
(« les synonymes et rôles proches sont inclus automatiquement ») et jamais prouvé à l'écran.

### Les mots-clés, eux, se surlignent

Ils matchent littéralement. La réponse indique le terme et l'endroit — titre, compétence — pour que
l'écran marque la bonne chip.

### « Non trouvé » n'est pas « ne le sait pas »

Le vocabulaire de la réponse doit rester **factuel**. Le résumé est vide sur 98,5 % des profils, les
compétences sont déclaratives et très inégales : affirmer une absence de compétence sur cette donnée
ferait écarter de bons profils. L'API dit qu'un critère n'a pas été **trouvé dans le profil**, jamais
que la personne ne le maîtrise pas.

### Deux lectures d'une même règle, et le garde-fou qui va avec

`CriterionMatcher` explique en PHP ce que `CandidateRepository::applyFilters` restreint en SQL. Ce
sont deux implémentations de la même règle, et deux implémentations divergent — c'est exactement le
défaut relevé sur « Au Maroc », où le badge du front et le filtre de l'API ne disaient déjà plus la
même chose.

L'avertissement est écrit en tête du fichier, et le garde-fou est dans les tests : un profil retenu
par le filtre doit être rapporté comme satisfaisant ce critère. Toute facette ajoutée au filtre
s'ajoute au matcher.

Le niveau d'études en est l'illustration : le filtre retient « **au moins** ce niveau » et exclut les
profils sans niveau reconnu. Une égalité stricte dans le matcher aurait rapporté « non satisfait » à
des profils que la recherche venait de retenir.

### Un N+1 évité de justesse

Premier jet : parcourir les libellés du concept profil par profil. Le test l'a fait tomber, et la
raison était pire que l'échec — chaque lecture déclenchait un chargement paresseux, soit **une
requête par ligne** sur une page de 200. Le terme est désormais résolu en concepts **une seule
fois**, comme le fait le filtre, et mémorisé pour la durée du jeu.

## Vérification

- `SearchTest`, `CandidateTest`, `CandidateJobExpansionTest` : **39 tests, 331 assertions**, verts.
- Le test décisif : une recherche sur « software engineer » rapporte `matchedOn = « Développeur »` —
  le libellé **du profil**, que le recruteur n'a jamais tapé et qui n'existe nulle part dans sa
  requête.
- Hors d'une recherche (`/search/candidates`), `matches` vaut `null` : sans critères posés, la
  question n'a pas d'objet.

## Ce que cette tâche confirme

`CriterionMatchOutput` **n'apparaît pas dans le contrat**, pour la raison découverte en `API-004` :
il est niché dans une carte qui n'y figure elle-même jamais. Le miroir front a donc été écrit à la
main dans `candidate-card.ts`, comme l'avertissement de ce fichier le prescrit.

## Hors périmètre

L'ordre du jeu → `API-006`. Le rendu → espace entreprise.
