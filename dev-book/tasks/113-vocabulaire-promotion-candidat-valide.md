# TASK 113 — Vocabulaire : promotion `candidat → validé` (relecture humaine)

- **Créée le** : 2026-07-22.
- **Statut** : 🟡 **mécanisme livré** (voie fichier) — reste la relecture humaine et l'effet recherche — **bloque l'usage en filtre dur** de tout l'enrichissement.
- **Type** : API + admin — gouvernance du vocabulaire.
- **Lié à** : `103`, `105`, `107` (producteurs de `candidat`), `096`, `101` (verticale admin).

## Contexte

**Tout ce que la machine produit entre en statut `candidat`** — invariant assumé : le modèle propose,
l'humain dispose. Mais **le circuit qui permet de disposer n'existe pas**.

Conséquence : rien de l'enrichissement ne peut piloter un **filtre dur**. En base, **493 concepts**,
**1 072 libellés** et **1 207 entreprises classées** sont tous en `candidat` — des propositions, bonnes
pour élargir une recherche, pas pour affirmer qu'un profil « est » dans tel secteur.

L'enum `VocabularyStatus` prévoit déjà les trois états (`CANDIDAT`, `VALIDE`, `DEPRECIE`) : le modèle
est prêt, l'usage ne l'est pas.

## Objectif

Permettre à un humain de **relire et statuer**, et faire en sorte que le statut ait un **effet réel**
sur la recherche.

## Périmètre pressenti

- **Écran de relecture** : propositions listées **par volume décroissant** — c'est là que l'erreur
  coûte le plus cher.
- **Bascule de statut** `candidat → validé` / `→ déprécié`, avec **traçabilité** (qui, quand).
- **Effet sur la recherche** : `validé` = filtre strict possible ; `candidat` = élargissement seul ;
  `déprécié` = écarté sans suppression.
- Correction d'un rattachement manifestement faux sans repasser par une passe LLM.

## Invariants

- **Une relecture ne se perd jamais** : un `validé` ou une correction doit **survivre** à une nouvelle
  passe d'enrichissement et à une réingestion — l'incrémental ne réécrit pas un jugement humain.
- Rien n'est supprimé : on **déprécie**.

## Critères d'acceptation

- [ ] Un humain peut faire passer un concept, un libellé ou une entreprise en `validé` / `déprécié`.
- [ ] Le statut et son auteur sont tracés.
- [ ] Une nouvelle passe d'enrichissement **ne réécrit pas** ce qui a été validé ou corrigé.
- [ ] La recherche distingue `validé` (strict) de `candidat` (élargissement).
- [ ] Les propositions sont priorisées par volume.

## Voie A livrée le 2026-07-24 — le fichier versionné fait autorité

Le mécanisme de promotion existe : **`app:vocabulary:import` lit désormais le champ `statut` de
`taxonomy.json`**. Valider un concept = passer `"statut": "VALIDE"` dans le fichier, relancer
l'import. Vérifié de bout en bout.

Pourquoi le fichier plutôt qu'un écran, pour commencer :

- **même patron que tout le reste du projet** : le fichier est la source, l'import est déterministe,
  la décision se relit en `git diff` — qui a validé et quand sont dans l'historique, gratuitement ;
- **survit à toute réingestion**, alors qu'une validation posée seulement en base serait écrasée au
  prochain import ;
- disponible immédiatement, sans développement full-stack.

**Repli volontairement asymétrique** : une valeur absente, vide ou inconnue retombe sur `CANDIDAT`,
**jamais** sur `VALIDE`. Un typo dans le fichier ne doit pas promouvoir un concept en silence — c'est
couvert par 10 cas de test.

**Conséquence pour l'écran (`120`)** : le fichier étant la source, l'écran devra **écrire dedans**,
et non directement en base. Sinon la prochaine réingestion effacerait silencieusement les
validations faites à la souris. À trancher avant de construire `120`.

Reste à faire : la **relecture elle-même** (décision humaine, priorisée par fréquence) et l'effet sur
la recherche (`validé` = filtre dur autorisé, cf. `062`).

## Deux dettes soldées le 2026-07-25, avant que la validation ait de la valeur

**Les proximités ne portent plus de statut.** `ConceptSimilarityImporter` purge la table entière
avant de réinsérer : un statut posé sur ces lignes aurait été effacé au premier
`app:vocabulary:similarity` rejoué, en silence. La colonne est retirée — la confiance se **dérive du
concept**, les voisins d'un concept validé héritent de la sienne.

**Le remplacement est devenu transactionnel.** La purge se faisait hors transaction : le
2026-07-25, un simple décalage de schéma a fait échouer l'insertion **après** la purge et laissé la
table vide, 6 729 relations perdues sans recours. C'est désormais tout ou rien, avec un test qui
provoque l'échec et vérifie que la purge est annulée.

**Un concept s'identifie par son CODE, plus par son libellé.** `taxonomy.json` porte déjà un `id`
stable par concept (`competence-001`) — 860 identifiants, tous uniques — que l'importeur ignorait.
Il l'utilise maintenant comme clé d'identité, avec repli sur le libellé pour rattraper le stock
(recalé sans aucune création). Vérifié en réel : renommer « Gestion de projet » en « Gestion de
projets » **conserve l'identité, suit le nouveau libellé, et préserve la validation**. Avant ce
correctif, la même opération créait un doublon `candidat` et orphelinait le validé.

Reste ouvert, hors périmètre de ces correctifs : le filtre de recherche compare `jc.prefLabel`, donc
un renommage **casse toujours les recherches sauvegardées** (`072`) qui transportent l'ancien
libellé. Le code stable protège le référentiel, pas encore les critères sauvegardés.

## Points de vigilance

- **Volume** : ~2 770 objets, dont 493 concepts qui portent l'essentiel de la décision (un concept
  validé vaut pour tous ses libellés). Prioriser par fréquence ; ne pas faire de la relecture exhaustive un
  préalable à l'usage.
- **Ne pas bloquer l'existant** : le statut change la **confiance**, pas la disponibilité.
- **À corriger en priorité** : « Stage / Internship » classé comme métier ; « Freelance » /
  « Indépendant » / « - » agrégés comme des entreprises.
