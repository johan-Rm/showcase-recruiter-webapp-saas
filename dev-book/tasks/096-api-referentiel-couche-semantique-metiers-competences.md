# TASK 096 — API : référentiel & couche sémantique métiers / postes / compétences

- **Créée le** : 2026-07-18.
- **Statut** : ✅ **clos le 2026-07-24** — référentiel maison retenu par les actes, relation
  métier ↔ compétences exposée, hiérarchie abandonnée après mesure.
- **Type** : API — recherche vivier / enrichissement à l'import (spike + décision).
- **Lié à** : `094` (normalisation, socle), `095` (axes expérience/poste), `061` (filtres parcours
  FTS), `075` (postes & agrégats facettes), `079` (auto-traduction), `035` (endpoint recherche).
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`
  - `dev-book/cadrage/entreprise/recherche_assistee_ia.md`

## Contexte

`094` et `095` couvrent la **normalisation mécanique** (forme, couverture, source, durées, axes de
poste). Restent les subtilités **sémantiques**, qui ne se résolvent pas par nettoyage de chaîne :
rapprocher des termes proches sans égalité stricte. Toutes dépendent d'une même décision : **rester
en texte libre + FTS**, ou **adosser les champs à un référentiel** (type ROME / ESCO / O\*NET) qui
fournit nativement synonymes, hiérarchie et relation métier↔compétences.

Cette carte **cadre la décision** et tient la liste des fonctionnalités à statuer. Elle ne code rien
tant que l'arbitrage référentiel n'est pas tranché.

## Où on en est réellement (2026-07-24)

**L'option A a été retenue de fait** : le référentiel maison existe et fonctionne — 769 concepts,
2 225 libellés, 62 % des compétences et 33 % des postes couverts. Quatre des sept subtilités sont
traitées :

| Subtilité | État |
| --- | --- |
| Synonymes | ✅ 769 concepts, 2 225 libellés (`103`, `118`) |
| Similarité | ✅ 5 681 relations mesurées (co-occurrence Jaccard + NPMI, `111`) |
| Multilinguisme FR/EN/NL | ✅ termes des trois langues sous un même concept |
| Désambiguïsation | ✅ partiellement — règle « le plus spécifique gagne » (`118`) |
| **Relation métier ↔ compétences** | 🟢 **déjà calculée, non exposée** — voir ci-dessous |
| **Hiérarchie** | ⚪ **abandonnée** (non dérivable, besoin non exprimé) |
| Pondération / scoring | ⚪ abandonné (arbitrage du 2026-07-24) |

Le rattachement à ESCO/ROME reste anecdotique (**31 concepts sur 769**, aucun code ROME) et n'est
plus poursuivi : le maison fait le travail.

### Métier ↔ compétences : la donnée existe déjà

La table `vocabulary_concept_similarity` contient **407 paires croisées** métier ↔ compétence, la
co-occurrence traversant naturellement les deux sacs. La qualité est bonne sans retouche :

| Métier | Compétences rapprochées |
| --- | --- |
| Data Scientist | TensorFlow, Deep learning |
| Ingénieur IA / ML | NLP, LLM, Keras, Matplotlib |
| Architecte (IT/logiciel) | Architecture |
| Responsable de magasin | Commerce de détail |

**Exposée le 2026-07-24** par `app:vocabulary:related` (lecture seule) : voisins d'un concept
donné, filtrables par type, ou panorama des paires croisées. Aucun calcul écrit — la donnée était
déjà là, il manquait un moyen de la voir.

```
app:vocabulary:related "Data Scientist" --type=COMPETENCE
  → TensorFlow · Deep learning · PyTorch · Machine learning · Data science · Apache Spark
```

**Ce qui reste est une décision produit, pas du code** : faut-il élargir une recherche par métier
aux compétences associées, et à partir de quel score ? Tant qu'elle n'est pas prise, aucun code de
recherche ne doit dépendre de cette relation.

### Hiérarchie : mesurée, et NON dérivable

Testée par **inclusion asymétrique** (si tous ceux qui portent A portent aussi B, alors A ⊂ B), sur
1 625 profils et 87 461 paires. Le résultat brut est inexploitable : les concepts omniprésents
avalent tout (`Sponsoring ⊂ Gestion de projet`, `Responsable RH ⊂ Anglais`). Après extinction des
concepts présents sur plus de 10 % des profils, sur les 20 meilleures relations :

| ✅ 8 justes | ❌ 11 fausses |
| --- | --- |
| `Spring ⊂ Java` · `React ⊂ JavaScript` | `Arabe ⊂ Français` (langues sœurs) |
| `Node.js ⊂ JavaScript` · `B2B ⊂ Vente` | `C++ ⊂ Python` · `Docker ⊂ Python` · `Git ⊂ Python` |
| `Prospection ⊂ Vente` · `Audit financier ⊂ Comptabilité` | `Angular ⊂ Java` · `UML ⊂ Java` · `React ⊂ HTML/CSS` |

**~60 % de faux.** La cause est structurelle : la co-occurrence mesure « va ensemble », pas « est une
sorte de ». Aucun réglage de seuil ne distingue les deux — un filtre « Python » remonterait les
experts Docker et Git.

**Conclusion** : si la hiérarchie est voulue, elle doit être **écrite à la main**, comme le reste du
vocabulaire (un parent par concept sur 769, effort comparable aux 1 153 termes déjà mappés). Ne pas
la dériver.

## Subtilités sémantiques concernées (cadrage d'origine)

| Subtilité | Enjeu | Exemple |
|---|---|---|
| Similarité | rapprocher sans égalité stricte | `développeur` ≈ `ingénieur logiciel` |
| Synonymes | variantes, sigles, abréviations | `dev` = `développeur` ; `RH` = `ressources humaines` |
| Hiérarchie (broader/narrower) | filtrer le parent remonte les enfants | `back-end` ⊂ `développeur` ⊂ `IT` |
| Relation métier ↔ compétences | inférer compétences d'un métier et inversement | `data scientist` → `Python`, `ML` |
| Pondération | valeurs de poids inégal (exacte > synonyme > hiérarchie) | scoring du match |
| Désambiguïsation | un terme, plusieurs sens, trancher par contexte | `architecte`, `Java` |
| Multilinguisme FR/EN | profils en EN, recherche en FR | `software engineer` = `ingénieur logiciel` |

## Décision structurante

- **Option A — texte libre + FTS/trigram** : pas de dépendance externe, mais synonymes / hiérarchie /
  relation métier↔compétences sont à **réimplémenter et maintenir à la main** (coûteux, incomplet).
- **Option B — référentiel** (ROME / ESCO / O\*NET) : synonymes, hiérarchie et relation
  métier↔compétences **fournis nativement** et multilingues ; coût = intégration, mapping des
  intitulés Jarvi vers le référentiel à l'import, maintenance des versions.

→ **À trancher** avant `061`. Le rattachement au référentiel, s'il est retenu, se fait **à l'import**
(sur la clé normalisée de `094`), pas à la requête.

## Liste de fonctionnalités à statuer plus tard

- [x] **Référentiel** : maison, tranché par les actes — ni ROME ni ESCO.
- [x] **Similarité** : co-occurrence Jaccard + NPMI, livrée (`111`).
- [x] **Synonymes** : table maison, livrée (`103`).
- [x] **Multilinguisme** : FR/EN/NL sous un même concept, livré.
- [x] **Relation métier ↔ compétences** — exposée via `app:vocabulary:related`. Reste à décider si
      la RECHERCHE l'exploite, dans quel sens et à partir de quel seuil (décision produit).
- [x] ~~**Hiérarchie**~~ — **abandonnée le 2026-07-24**. Non dérivable (mesuré : ~60 % de faux), et
      l'écrire à la main coûterait 860 décisions pour un besoin non exprimé. La mesure reste
      consignée ci-dessus : ne pas relancer le sujet sans un besoin produit établi.
- [x] ~~**Pondération / scoring**~~ — abandonné le 2026-07-24.
- [x] ~~**Désambiguïsation**~~ — suffisamment traitée par la règle de spécificité (`118`).
- [ ] **Rappel vs précision** : curseur d'élargissement exposé à l'entreprise ou fixe.
- [ ] **Explicabilité** : afficher *pourquoi* un profil matche (obligatoire si élargissement/IA).
- [ ] **Négation / exclusion** : pouvoir exclure un secteur / poste.
- [ ] **Cohérence des compteurs de facettes** avec l'élargissement sémantique.
- [ ] **Recherche IA en langage naturel** (MCP `advancedSearch`) — articulation avec cette couche.

## Hors périmètre (de cette carte)

- Toute implémentation : cette carte s'arrête à la décision + au spike.
- Normalisation mécanique et axes (couverts par `094` / `095`).

## Invariants

- Le rapprochement sémantique n'altère jamais la donnée brute ni l'affichage d'origine.
- Un enrichissement dérivé reste recalculable par réingestion.

## Critères d'acceptation

- [ ] Un choix argumenté Option A / Option B est acté (avec coût d'intégration et de maintenance).
- [ ] Chaque item de la liste ci-dessus est tranché (retenu / différé / écarté) et daté.
- [ ] Les décisions retenues sont éclatées en tasks d'implémentation dédiées.
- [ ] La doc d'architecture recherche reflète la décision.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md`
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`
