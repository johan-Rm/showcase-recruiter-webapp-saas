# TASK 157 — workflow : lever le télescopage des numéros de tâche

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — renumérotation faite ; système de numérotation inchangé (décision).
- **Type** : Process / documentation — aucun changement de comportement.

## Contexte

Trois numéros ont été attribués **deux fois**, parce que trois lanes avançaient en parallèle et que
chacune a pris « le suivant sur `develop` » sans voir ce que les autres réservaient au même moment :

| Numéro | Lane A | Lane B |
| --- | --- | --- |
| 151 | `admin-error-unexpected-i18n-message` | `api-fin-de-la-shortlist-export-sur-selection` |
| 152 | `api-notifications-flux-curseur-marquage-lu` | `api-devoilement-en-trois-acces` |
| 153 | `api-import-jarvi-memoire-lots` | `entreprise-contrat-des-trois-devoilements` |

Les fichiers cohabitent sans se gêner — leurs slugs diffèrent. Le problème est **ailleurs** : treize
fichiers source citent « task 151 » ou « task 152 » dans leurs docblocks, et ces numéros désignent
désormais deux tâches chacun. Un lecteur qui suit la référence tombe sur la mauvaise.

## Décisions

### Ce sont les tâches arrivées EN SECOND qui bougent

Les trois de la colonne B — les miennes — ont atterri sur `develop` après celles de la colonne A.
Renuméroter les premières arrivées aurait invalidé des références déjà écrites ailleurs.

`151 → 154`, `152 → 155`, `153 → 156`, et **toutes** les références suivent : docblocks de l'API,
renvois entre fichiers de tâche, descriptions du contrat OpenAPI et types TS régénérés avec.

### Les messages de commit mergés ne mentent plus, ils datent

Impossible de réécrire l'historique déjà sur `develop` : les commits citent encore « task 151 ». Chaque
fichier renuméroté porte donc en tête la ligne qui fait le pont — numéro d'origine, numéro actuel,
raison. La trace reste lisible dans les deux sens.

## Livré

- Trois fichiers de tâche renumérotés, chacun avec sa ligne de traçabilité.
- Douze fichiers `api/src` réalignés sur les nouveaux numéros.
- `api/openapi.json` et `webapp/shared/types/api.ts` régénérés (diff **commentaire seul**).

## Décision — le système ne change pas

> **Renversée le jour même** par `OPS-001-numerotation-des-taches-par-lane.md`, qui prend la piste 1
> ci-dessous sous une autre forme : un **préfixe par espace** (`ENT-001`, `API-003`…) plutôt que des
> plages numériques. Ce que la décision ci-dessous n'avait pas vu : les collisions n'étaient pas
> trois mais **six** — `035`, `081` et `146` doublonnent aussi, et n'ont jamais été réparés.
> La section reste telle quelle : c'est l'inventaire incomplet qui a produit le mauvais arbitrage.
> `OPS-002` a corrigé le tirage du numéro dans la foulée.

**On garde la numérotation actuelle** (2026-07-27) : un numéro, une séquence, pris sur `develop`.
La collision est réelle mais rare, et elle se répare — ce fichier montre à quel prix. Changer de
système coûterait plus cher que la réparer une fois de temps en temps.

Le réflexe reste donc, avant de choisir un numéro : regarder ce que `develop` porte, et ce que les
autres worktrees ont déjà commité.

Si le télescopage devient régulier, trois pistes existent, dans l'ordre où je les prendrais :

1. **Plages par lane** — `api` 200-299, `entreprise` 300-399, `admin` 400-499… Aucune coordination
   nécessaire, format des commits inchangé. Abandonne la séquence chronologique unique.
2. **Réservation élargie** — prendre le suivant en regardant `develop` **et** les branches des autres
   worktrees. Simple à énoncer, mais deux commits dans la même minute se télescopent encore.
3. **Plus de numéro** — la tâche s'identifie par son slug, le commit le cite. Supprime le problème à
   la racine, au prix de la convention `— task NNN` et de 150 fichiers existants.
