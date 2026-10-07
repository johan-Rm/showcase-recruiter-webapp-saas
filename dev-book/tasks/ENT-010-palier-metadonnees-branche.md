# TASK ENT-010 — entreprise : le palier métadonnées trouve enfin son consommateur

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — config vérifiée dans le payload SSR.
- **Type** : Frontend espace entreprise — chargement des données.
- **Réf** : `ENT-001` (le palier, déclaré et sans usage) · `API-001` (la config enrichie) ·
  `ENT-002` (le store `referentials`, créé et jamais rempli).
- **Débloque** : `ENT-006` (le coût d'un dévoilement) · `ENT-004` (les nomenclatures du formulaire).

## Contexte

Deux pièces s'attendaient sans se connaître.

`ENT-001` a ajouté le **palier métadonnées** à l'orchestrateur et l'a livré en écrivant noir sur
blanc qu'il n'avait **aucun consommateur** — « c'est exactement l'état dans lequel
`loadBackgroundData` a pourri huit tâches durant ». `ENT-002` a créé le store `referentials`, jamais
rempli. `API-001` a enrichi `/v1/config` des quatre nomenclatures que le front recopiait en dur.

Trois tâches, et rien qui les relie. La première carte à réclamer une valeur du serveur — le coût
d'un dévoilement, pour `ENT-006` — a rendu le manque bloquant.

## Décisions

### Le palier 1 seulement

Le registre de **routes** reste vide : chaque écran charge encore ses propres données par
`useAsyncData`. Le basculer relève d'une autre tâche, et le palier métadonnées n'a pas à l'attendre —
il ne dépend d'aucun écran, c'est même sa définition.

### Une route BFF publique

`GET /api/config` relaie `/v1/config` **sans session ni Bearer** : rien n'y est propre à un
utilisateur, et exiger une session retarderait le chargement le plus précoce du démarrage.

### La config se fige après le premier chargement

`isLoaded` la verrouille : elle ne change qu'au rythme d'un déploiement. La relire à chaque
navigation serait un appel pour rien, et l'orchestrateur sait déjà ne pas rejouer ce qui est chargé.

## Ce que ça débloque

| Écran | Ce qu'il cessera de recopier |
| --- | --- |
| La carte de décision | le coût d'un accès dévoilé |
| Le formulaire de recherche | niveaux d'études, disponibilités, horizons, niveaux de langue |

Ces quatre nomenclatures sont encore écrites en dur dans `<SearchFiltersPrimary>` et
`shared/types/ai-search.ts`. Elles tombent avec `ENT-004`, maintenant qu'elles ont un canal.

## Vérification

Rendu SSR de l'écran de parcours : le payload porte `revealCost`, `welcomeCredits` et les
nomenclatures (`BAC_PLUS_2`, `NATIVE_OR_BILINGUAL`) — la config est donc chargée **avant** le rendu,
et le store hydraté sans second appel côté client.
