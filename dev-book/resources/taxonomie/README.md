# Taxonomie métiers & compétences — brouillon exploratoire

**Statut : tout est en `candidat`**, à relire avant tout usage. Produit à partir d'un export réel.

**Où vivent les fichiers** — le fichier *opérationnel* a quitté ce dossier :

- `api/data/vocabulary/taxonomy.json` — **consommé par `app:vocabulary:import`**. Il doit vivre sous `api/` : c'est le seul périmètre monté en dev (`./api:/app`) et copié dans l'image de prod ; un chemin à la racine du repo est inatteignable depuis le conteneur.
- `dev-book/resources/taxonomie/` (ici) — les artefacts d'analyse, non consommés par le code : `taxonomy.md` (lisible) et `data/vocabulary.json` (vocabulaire brut complet).

La taxonomie **est chargée en base** (`vocabulary_concept` / `vocabulary_label`) via `app:vocabulary:import`. Elle n'est en revanche exposée par **aucune route API** : rien ne la lit encore côté applicatif.

## D'où ça vient

- **Source** : `Search_Candidates-*.json` — export showcase-recruiter-webapp-saas post-import (1697 profils, identité masquée).
- Deux champs seulement sont exploités :
  - **Compétences** ← `skills[]`
  - **Postes / métiers** ← `workExperiences[].title` (tout le parcours, canonicalisé : on coupe au premier séparateur `| / ( ,` pour retirer le bruit d'entreprise).

## Comment c'est fabriqué

Deux couches, deux natures :

1. **Récolte déterministe** (rejouable, vérifiable) : normalisation + fréquences → `vocabulary.json`.
   Outillée par `app:vocabulary:harvest`.
2. **Concepts** = regroupement des libellés en un même sens. Outillé par `app:vocabulary:enrich`
   (jugement LLM en batch, rare et manuel) sur la **tête** de la distribution — voir le pipeline
   complet dans `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md`.
   - **Synonyme** (même concept, autre mot, dont FR/EN : `anglais`=`english`, `PO`=`Product Owner`) → jugement LLM, car ça demande de la connaissance de la langue.
   - **Similaire** (concepts distincts mais proches, score 0–1) → **co-occurrence dans les profils** (Jaccard + NPMI). Mesuré, pas inventé. Le NPMI éteint les termes trop fréquents (« communication » n'est pas « similaire à tout »).

## Comment ça se corrige

Le fichier versionné fait autorité, et pas seulement pour ajouter (task API-035).

- **Retirer un libellé d'un concept** : le supprimer de ses `synonymes` suffit. L'import le retire de
  la base en suppression douce, et le rend s'il revient plus tard dans le fichier — sur sa ligne
  d'origine, sans doublon. Un concept **absent** du fichier, en revanche, garde ses libellés : le
  silence ne vaut pas retrait.
- **Déplacer un libellé d'un concept vers un autre** : le retirer de l'un et l'ajouter à l'autre,
  dans le même fichier. Sans le retrait, il désignerait deux sens à la fois et la reconnaissance
  d'intitulé en choisirait un arbitrairement.
- **Rien n'est acquis avant `app:vocabulary:reproject`.** L'import charge le dictionnaire ; c'est la
  reprojection qui rejoue le rattachement des profils déjà chargés, sans repasser par Jarvi. Un
  concept créé sans elle reste sans personne, donc invisible à l'auto-complétion.

`app:vocabulary:import --dry-run` annonce les compteurs — dont les retraits — sans rien écrire :
c'est le filet avant une correction de grande ampleur.

## Ce que ça vaut, honnêtement

- **Compétences** : riche et fiable. Les similarités retrouvent seules les stacks (`Node.js`~`React`), les outils (`CATIA`~`SolidWorks`), etc.
- **Postes** : plus pauvre. La longue traîne est énorme (86 % des intitulés vus une seule fois) et le concept n°1 est « Stage / Internship » — un **statut**, pas un métier. À nettoyer avant tout usage comme filtre.

## Limites

- **Instantané** : figé à la date de l'export ; se périme quand le vivier bouge.
- **Couverture** : les concepts couvrent la tête (les filtres réellement cliqués), pas la traîne — c'est voulu.
- **Pas encore exposé** : les tables `vocabulary_concept` / `vocabulary_label` existent et `app:vocabulary:import` les charge, mais **aucune route API** ne les lit et la recherche ne s'en sert pas encore. La promotion `candidat → validé` reste à faire.
