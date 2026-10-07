# TASK API-004 — API : la carte porte ce sur quoi on filtre

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 37 tests, 314 assertions.
- **Type** : API — surface de contrat.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

La carte candidat sert désormais à **décider** — garder ce profil ou passer. Trois données lui
manquent, et chacune pour une raison différente.

**`educationLevel`** est **filtrable sans être visible**. Un recruteur qui cherche « Master » n'a
aucun moyen de vérifier ce qu'il a obtenu : le champ existe sur l'entité et dans les critères de
recherche, pas sur `CandidateCardOutput`.

**`returnProject`** — le projet de retour dans les mots du candidat — n'existe que sur la fiche
détaillée. C'est pourtant l'élément le plus décisif de l'écran de tri : il sépare « il cherche un
poste » de « il veut rentrer », et c'est ce que showcase-recruiter-webapp-saas sait et qu'aucun réseau professionnel ne dit.

**`photoUrl`** est servie, mais **conditionnée à l'accès Coordonnées** :

```php
$access->contact ? $jarvi->photoUrl : null
```

## Décision

Les trois entrent sur la carte, et **la photo sort du cadre du dévoilement** : elle est servie sans
condition. Elle existe sur **2 213 profils sur 2 561 (86 %)**.

**Conséquence assumée, tranchée avec Johan** : une photo identifie plus sûrement qu'un nom. Photo +
poste + ville suffisent généralement à retrouver quelqu'un sur un réseau professionnel. Le masquage
des **coordonnées** demeure — l'anonymat, lui, tombe. Ce n'est pas un effet de bord : c'est le choix.

Cela renverse la note de cadrage du 2026-07-10 (« photo jamais »), à laquelle le code ne s'était de
toute façon jamais aligné.

### La règle vaut aussi pour la fiche détaillée

`toDetail` conditionnait la photo au même accès. La laisser masquée là aurait donné l'incohérence
inverse : le résumé montre le visage, le profil complet le cache. La règle porte sur **la photo**,
pas sur un endpoint.

## Ce que cette tâche a révélé

**La carte n'existe pas dans le contrat.** `CandidateCardOutput` n'apparaît nulle part dans
l'OpenAPI : elle n'y figure que **nichée** dans des collections dont les `items` sont typés
`(string | null)[]` — la limite relevée en livrant `API-002`, et commune aux quinze
`CollectionOutput` du contrat.

Conséquence directe : ajouter trois champs à la carte **ne produit aucun changement de contrat**, et
la gate anti-dérive (`quality-contracts-check`) ne voit rien. Le miroir front,
`webapp/shared/types/candidate-card.ts`, est écrit à la main et doit être tenu à la main — sans quoi
rien ne signalera l'écart. L'avertissement est désormais écrit en tête de ce fichier.

## Vérification

- `CandidateTest` et `SearchTest` : **37 tests, 314 assertions**, verts.
- Le test qui affirmait « un visage désigne : jamais avant dévoilement » est retourné, avec la raison
  du changement.

## Hors périmètre

Le rendu de ces champs à l'écran → `ENT-006`.
