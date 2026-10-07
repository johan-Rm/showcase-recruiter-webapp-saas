# TASK ENT-090 — entreprise : la lane repasse ses portes avant la PR

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — les cinq portes qualité passent au vert sur les neuf tâches de
  la branche.
- **Type** : correctif de qualité. Aucun comportement ne change, à l'écran comme au contrat.
- **⚠️ Cadrée après coup**, au point de contrôle : la passe qualité de la branche a sorti trois
  défauts, chacun venant d'une tâche déjà commitée ici.
- **Tests** : `sans objet` pour les trois correctifs eux-mêmes — un ancrage de type, une variable
  morte, deux reformatages. Mais **la suite de tests est la preuve** : elle ne compilait pas avant.
- **Réf** : `ENT-081` — qui a introduit la variable morte · `ENT-084` — qui lui a retiré son emploi ·
  `ENT-088` — dont le fichier de tests portait l'erreur de type · `ENT-080` — même geste, sur la
  livraison précédente.

## Contexte

Les portes qualité ne tournent qu'une fois par branche, à l'ouverture de la PR. Neuf tâches se sont
empilées depuis la dernière passe, et elle en a sorti trois défauts. Chacun aurait bloqué la CI.

- **Une erreur de type dans un fichier de tests.** `ENT-088` écrivait
  `criterion('q', …, 'motsCles')[0]!` en supposant que l'aide de test rend un tableau : elle rend un
  objet. Le `[0]` indexait un objet, et le type-check des quatre espaces refusait le fichier.
- **Une variable morte.** `ENT-081` avait posé `const slots = useSlots()` pour un emplacement
  d'action facultatif ; `ENT-084` a déplacé ce bouton en pied de carte et l'emplacement a disparu.
  La variable est restée, et le lint la refuse.
- **Trois fichiers hors format.** Une ligne trop longue, une balise à réenrouler, une ligne vide de
  trop laissée par la suppression d'un bloc.

## Périmètre

- **L'aide de test rend un critère**, plus un tableau à indexer.
- **La variable morte disparaît**, son commentaire avec elle.
- **Le format est repassé** sur les trois fichiers concernés.

## Hors périmètre

- **Les vingt avertissements de lint** qui subsistent — valeurs de props sans défaut, ordre
  d'attributs, balise à refermer. Ils vivent sur `develop` depuis longtemps, ne bloquent pas la CI,
  et les corriger ici mélangerait un nettoyage de fond à une livraison de fonctionnalités.

## Décisions de cadrage

### Un compteur, et pas un tirage au hasard

Le fichier de tests avait besoin d'une valeur différente à chaque critère fabriqué — ils servent de
clé dans le gabarit, et deux identiques dans une même liste rendraient le cas ambigu. Un tirage au
hasard aurait suffi à l'exécution ; un compteur dit **pourquoi** les valeurs diffèrent, et il rejoue
à l'identique.

### La variable morte se supprime, elle ne se préfixe pas

Le lint autorise à taire l'avertissement en préfixant d'un `_`. Ce serait garder un appel à
`useSlots()` que plus rien ne lit, et un commentaire qui décrit un emplacement qui n'existe plus.

## Règles et cas limites

- **Le type-check du conteneur ne voit pas ces erreurs** — il donne un faux vert. La passe doit
  tourner **sur l'hôte** (`cd webapp && bun run quality-check`), seule exécution qui reproduise la
  CI. C'est elle qui a sorti l'erreur de type.
- **`.nuxt` doit rester nommé `.nuxt`** : le fichier d'ignore d'ESLint le vise par ce nom exact, et
  un dossier de build renommé fait entrer soixante-dix-neuf erreurs générées dans la passe.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`, aucun README : rien ne change de convention. Les règles enfreintes
étaient déjà écrites et déjà outillées.

## Définition de terminé

- [x] Le lint ne rend plus aucune erreur.
- [x] Le format passe sur l'ensemble du dépôt.
- [x] Le type-check passe sur les quatre espaces.
- [x] Les conventions passent.
- [x] La suite de tests passe.

## Vérification

Passe complète, sur l'hôte, après correctifs :

```
$ cd webapp && bun run quality-check

prettier --check .              All matched files use Prettier code style!
type-check                      espace public · candidat · entreprise · admin — aucune sortie
conventions                     19 règles, aucune violation
vitest run                      24 fichiers, 370 tests passés
eslint .                        0 erreur, 20 avertissements (préexistants sur develop)
```

Contrat front↔API, à titre de contrôle — aucune dérive :

```
$ bun run quality-contracts-check
✔ shared/types/organization-constraints.ts — 1 schéma(s) depuis ../api/openapi.json
(git diff --exit-code : aucune sortie)
```

Ce que la passe rendait **avant** les correctifs :

```
eslint       1 erreur   Sentence.vue:77  'slots' is assigned a value but never used
prettier     3 fichiers hors format
type-check   TS7053 sur criteria.vitest.ts:645, sur les QUATRE espaces
```

## Livré

| Fichier | Geste |
|---|---|
| `services/search/criteria.vitest.ts` | (M) l'aide de test rend un critère ; une ligne réenroulée |
| `spaces/entreprise/app/components/search/Sentence.vue` | (M) la variable morte et son commentaire partent |
| `spaces/entreprise/app/components/search/Progress.vue` | (M) format |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) format |

## Reste à faire (hors périmètre)

- **Vingt avertissements de lint** de fond, antérieurs à cette branche, à traiter pour eux-mêmes.
