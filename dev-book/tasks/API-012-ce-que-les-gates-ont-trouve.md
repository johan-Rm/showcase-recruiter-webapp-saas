# TASK API-012 — API : ce que les gates ont trouvé avant la PR

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 376 tests, 1870 assertions ; webapp 42 tests.
- **Type** : API et socle partagé — correctifs d'analyse statique et de fixtures, aucun changement de
  comportement.
- **Réf** : `API-004` à `API-011` (les huit tâches que ce passage vérifie).

## Contexte

Les gates tournent une fois par branche, à l'ouverture de la PR. Ce passage-là couvrait **huit
tâches** d'un coup — une entité neuve, une colonne, une fonction DQL, deux routes. Il a trouvé deux
familles de défauts, dont une que les tests de l'API ne pouvaient pas voir.

## Ce qui a été corrigé

### Dix-huit erreurs phpstan, toutes mécaniques

Des `\assert($x instanceof Y)` sur des collections déjà typées par leur `@var`, un `array_values` sur
une liste, deux casts inutiles, un `?->` sur une valeur jamais nulle, un `array_filter` sans callback
strict, et un `instanceof` sur un type déjà garanti par le contrat du processeur.

Aucune ne changeait le comportement — toutes disaient la même chose : **du code qui se protège de ce
qui ne peut pas arriver**. Les retirer rend le vrai garde-fou plus lisible.

### Les fixtures du front, cassées par un type de l'API

Le défaut qui compte. Les six champs ajoutés à `CandidateCard` — `educationLevel`, `returnProject`,
`isInMorocco`, `isSeen`, `isDismissed`, `matches` — sont **obligatoires**. Or deux fixtures Vitest
construisent une carte complète en littéral :

```
webapp/services/search/views.vitest.ts
webapp/services/search/normalize.vitest.ts
```

Sans les nouveaux champs, le type-check et les tests du front partaient **rouges en CI**, alors que
les 376 tests de l'API étaient verts. Un changement de contrat côté serveur casse le front sans
qu'aucune gate serveur ne s'en aperçoive.

## Ce que ça dit du garde-fou existant

`candidate-card.ts` porte depuis `API-004` un avertissement : ce type est écrit à la main, la gate de
contrat ne le voit pas, toute évolution de la carte doit y être reportée. Il a servi — le type a bien
été tenu à jour à chaque tâche.

**Mais il ne couvrait pas les CONSOMMATEURS du type.** Tenir le miroir ne suffit pas : ajouter un
champ obligatoire casse tout ce qui construit l'objet. L'avertissement dit désormais de vérifier les
deux.

## Vérification

- API : `lint:container`, phpstan **0 erreur**, php-cs-fixer, deptrac, **376 tests / 1870
  assertions**.
- Webapp : lint, format, type-check des **quatre** espaces, conventions (8 règles), **42 tests**,
  contrat OpenAPI ↔ types, build SSR — tous verts.

## Note d'outillage

`bun install` échouait dans le worktree : les conteneurs y avaient créé `node_modules` et `.nuxt` en
**root**. Les deux dossiers étaient vides — les retirer suffit. Le worktree fait désormais tourner
ses propres gates front sans dépendre de l'arbre principal.
