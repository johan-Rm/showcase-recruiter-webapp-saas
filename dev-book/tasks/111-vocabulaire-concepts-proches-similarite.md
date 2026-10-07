# TASK 111 — Vocabulaire : concepts proches (similarité entre compétences / métiers)

- **Créée le** : 2026-07-22.
- **Statut** : ✅ fait le 2026-07-22 — `app:vocabulary:similarity`, 3 826 relations en base.
- **Type** : API — vocabulaire / enrichissement déterministe.
- **Lié à** : `103` (pipeline vocabulaire), `096` (couche sémantique — parent décisionnel), `061`.

## Contexte

Le vocabulaire sait traiter la **synonymie** — même idée, mots différents (`anglais` = `english`).
Il ne sait rien de la **proximité** : deux concepts **distincts mais voisins** (`Node.js` ~ `React`,
`CATIA` ~ `SolidWorks`). C'est pourtant ce qui permet d'élargir une recherche au-delà du mot exact.

**Constat vérifié (2026-07-22)** : contrairement à ce que laissait entendre la documentation, **rien
n'est calculé**. Les concepts de `taxonomy.json` portent les clés `id, label_canonique, type,
frequence_totale, statut, synonymes, esco_uri` — **aucune clé `similaires`**, **0 relation**, aucune
table. Seul le `_meta` décrit une **méthode envisagée** (co-occurrence, Jaccard + NPMI, seuil ≥ 4
profils communs). Une intention documentée avait été prise pour un livrable.

## Objectif

Calculer et persister les relations **concept ↔ concept proches**, avec un score, pour élargir une
recherche sans diluer la précision.

## Ce qui a été livré

`app:vocabulary:similarity` (déterministe, sans LLM), rejouable : chaque exécution remplace
intégralement le jeu de relations. Table `vocabulary_concept_similarity`, paire **non ordonnée**
stockée une seule fois — deux lignes par paire se désynchroniseraient.

**Résultat mesuré** : 3 826 relations depuis 1 597 profils porteurs (94 % du vivier), **426 des 493
concepts** ont au moins un voisin, médiane de 13 voisins par concept.

Le seuil NPMI a été choisi par mesure, pas au jugé :

| Seuil NPMI | Relations | Voisins restants à « Communication » |
| ---: | ---: | ---: |
| 0,0 | 14 712 | 344 |
| 0,2 | 6 719 | 32 |
| **0,3** | **3 826** | **5** |
| 0,4 | 1 856 | 0 |

À 0,3 « Gestion de projet » (618 profils) n'a plus aucun voisin, tandis que les grappes réelles
tiennent : `React` → Node.js · Angular · JavaScript · Laravel ; `Génie électrique` → Électricité ·
Électronique · GMAO · Caneco BT ; `Anglais` → Français · Arabe · Espagnol.

Le nombre de profils communs est stocké à côté du score : c'est l'explication lisible d'un
rapprochement (« 32 profils portent React et Node.js »), utile à l'exigence d'explicabilité.

## Périmètre retenu

- **Calcul déterministe, sans LLM** : co-occurrence dans les profils, mesurée par **Jaccard + NPMI**.
- **Deux seuils, tous deux nécessaires** : ≥ 4 profils communs écarte le hasard des petits nombres,
  NPMI ≥ 0,3 écarte les associations banales.
- **Persistance** : `vocabulary_concept_similarity`, statut `candidat`, champ dérivé recalculé en
  entier — sinon une paire tombée sous le seuil resterait en base pour toujours.
- Étape **hors ligne**, rejouable, à cadencer avec la récolte.

## Invariants

- **Proche ≠ synonyme** : un synonyme rejoint le même concept, un proche reste **un concept distinct**.
- Mesuré, pas inventé : la proximité vient des données du vivier, pas d'un jugement LLM.
- Statut `candidat` : sert à **élargir**, jamais à filtrer strictement.

## Critères d'acceptation

- [x] Relations calculées, stockées avec leur score, rejouables.
- [x] Un concept trop fréquent ne ressort pas proche de tout (NPMI en place).
- [x] Seuils configurables (`VOCABULARY_SIMILARITY_MIN_COOCCURRENCES`, `..._MIN_NPMI`) et documentés.
- [x] Tests : co-occurrence, seuil, extinction des termes fréquents, paire couvrant tout le vivier.

## Points de vigilance

- **Dépend de la couverture** : calculé après l'enrichissement du 2026-07-22 (51 % des compétences).
  Élargir le dictionnaire à la traîne enrichira mécaniquement les proximités — **relancer la commande
  après chaque passe d'enrichissement**.
- **Aucune route API ne lit ces relations** : la façon dont la recherche les exploite relève de `096`.
- **Explicabilité** : si la recherche élargit aux proches, l'entreprise doit comprendre pourquoi un
  profil remonte (cf. `096`).
- **Qualité côté postes** : le concept n°1 récolté est « Stage / Internship », un statut et non un
  métier. À nettoyer avant d'exploiter les proximités de postes.
