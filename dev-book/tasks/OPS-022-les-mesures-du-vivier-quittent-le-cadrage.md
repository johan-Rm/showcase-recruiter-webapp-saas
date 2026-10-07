# TASK OPS-022 — la composition du vivier passe en doc, le rapport daté disparaît

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — `dimensions-du-vivier.md` en doc technique, rapport supprimé
  après extraction de son seul fait durable.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche déplace, supprime et réécrit du Markdown.
- **Réf** : task `OPS-018` (qui avait déjà sorti deux mesures du cadrage) · `OPS-021`

## Contexte

Deux documents de `dev-book/cadrage/entreprise/` mesuraient le vivier, et ni l'un ni l'autre n'était
dans la doc technique. Ils se ressemblaient sans être de la même nature — c'est ce qui décide de
leur sort.

- `profil_dimensions_vivier.md` (123 lignes) décrit **ce que contient la donnée** : pour chaque
  dimension, valeurs distinctes, couverture, dispersion, avec sa méthode de mesure explicitée.
- `rapport-taux-remplissage-jarvi.md` (84 lignes) s'ouvre sur « **Date de mesure : 2026-07-18** » et
  répond à une question ponctuelle : *notre import perd-il des données ?*

## Périmètre

- `profil_dimensions_vivier.md` → `docs/…/2.architecture/dimensions-du-vivier.md`.
- Le fait durable du rapport reversé dans `jarvi-integration.md`, puis le rapport supprimé.
- Sommaire de la doc et README du cadrage entreprise mis à jour.

## Hors périmètre

- Les deux commentaires de `api/` qui citent le rapport — voir « Reste à faire ».

## Décisions de cadrage

### Une caractérisation reste, un rapport daté part

La composition du vivier est **durable** : elle décrit une population, se recalcule, et sa méthode de
mesure vaut indépendamment des chiffres du jour. C'est le complément naturel de
`profile-processing.md` — l'un dit ce qu'on **fait** de la donnée, l'autre ce qu'elle **est**.

Le rapport, lui, dit ce qui a été mesuré un mardi. Sa conclusion — « l'import est fidèle, e-mail
67 = 67, téléphone 56 = 56 » — est **acquise** : la question ne se repose pas. Dans six mois ses
chiffres seront faux et personne ne saura s'il faut les corriger ou les lire comme une archive.

**La doc dit ce qui est vrai en permanence.** Un rapport de vérification daté n'y a pas sa place, et
le laisser en cadrage revenait à le garder faux ailleurs.

### Ce qui méritait de survivre

Une seule chose du rapport reste vraie et coûte cher à redécouvrir : **les coordonnées sont rares à
la source, et ce n'est pas un défaut d'import.** E-mail ~4 %, téléphone ~3 %, CV ~4 %, compte rendu
< 1 % — parce que le périmètre vient du sourcing LinkedIn passif.

Elle est reversée dans `jarvi-integration.md`, section ingestion, avec sa conséquence produit : une
promesse de « profil joignable » ne peut pas s'appuyer sur l'e-mail ou le téléphone. Sans cette
phrase, la prochaine personne qui voit 4 % d'e-mails rouvre l'enquête sur le pipeline.

## Règles et cas limites

- Le renvoi du README du cadrage entreprise **pointe désormais hors du cadrage**, et le dit : la
  mesure d'une population n'est pas un cadrage produit.
- Les cartes de tâches qui citent les deux anciens chemins sont laissées telles quelles.

## Documentation impactée

- `docs/…/2.architecture/jarvi-integration.md` — le fait durable extrait du rapport.
- `docs/…/2.architecture/index.md` — la nouvelle page au sommaire.
- `dev-book/cadrage/entreprise/README.md` — renvoi repointé vers la doc.

## Vérification

- **Le chemin relatif du README** (`../../../docs/…/dimensions-du-vivier.md`) a été résolu sur
  disque depuis le dossier du fichier citant.
- **Le déplacement est vu comme un renommage** par Git — l'historique du document suit.
- Plus aucune référence vivante hors `dev-book/tasks/` et `api/`.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/dimensions-du-vivier.md` (R) — depuis le cadrage entreprise.
- `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md` (D).
- `docs/…/2.architecture/jarvi-integration.md` (M) — les coordonnées rares à la source.
- `docs/…/2.architecture/index.md` (M), `dev-book/cadrage/entreprise/README.md` (M).

## Reste à faire (hors périmètre de cette tâche)

- **Deux commentaires de `api/` citent le rapport supprimé** — `CandidateMapper.php` et
  `QualificationReporter.php`. Ils relèvent d'`espace/api`, pas de cette lane : à repointer vers
  `jarvi-integration.md` par qui travaille là-bas.

## Définition de terminé

- [x] La composition du vivier est en doc technique et au sommaire.
- [x] Le rapport daté a disparu, son fait durable est dans `jarvi-integration.md`.
- [x] Les renvois vivants résolvent.
