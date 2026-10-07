# TASK 127 — Admin : taux de remplissage du vivier sur l'écran candidats

- **Créée le** : 2026-07-25.
- **Statut** : ✅ faite.
- **Type** : API + webapp admin — section agrégée sur l'écran candidats.
- **Lié à** : `119` (santé du vivier — même domaine `QualificationReporter`, même endpoint
  `/admin/vivier`), `110` (résolution des coordonnées `Person` puis réplica), `108`/`109`
  (ingestion WhatsApp / photo).
- **Réf** : `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`,
  `dev-book/resources/sql/scratchpad-taux-remplissage.sql`.

## Le besoin

L'écran admin `/candidats` annonce « 2557 candidat(s) » mais ne dit rien de la **qualité** du vivier :
combien de profils ont un e-mail, un LinkedIn, une photo, un employeur ? Le rapport de remplissage
existait en markdown daté ; il fallait l'exposer **en direct** dans l'outil, à côté du compteur.

## Décision

**Une section « Taux de remplissage » agrégée**, pas un score par profil. Réutilise le domaine
« état du vivier » déjà en place plutôt qu'un nouvel endpoint : le calcul « champ rempli » devient
`QualificationReporter::fillRate()`, exposé sur `/admin/vivier` (source unique CLI + écran +
réutilisable côté recruteur, comme `snapshot`/`distributions`).

Le back renvoie `filled`/`total` bruts par champ (pas un pourcentage) : la présentation calcule le
taux et choisit les champs. Ordre du mieux rempli au plus rare, comme le rapport.

## Le terrain est déjà prêt

- Le SQL de référence (`scratchpad-taux-remplissage.sql`) donne le comptage flag par flag.
- La règle de lecture des coordonnées est déjà tranchée (`110`) : e-mail/téléphone dans le JSON
  `contacts`, LinkedIn dans `raw_payload.profileUrl`, **jamais** les booléens `person.*` qui
  sous-comptent les profils joignables.

## Périmètre

- [x] **Back** : `QualificationReporter::fillRate()` — 10 champs (prénom, nom, LinkedIn, employeur,
      photo, e-mail, téléphone, CV, WhatsApp, compte rendu) comptés sur `candidate_jarvi` + `person`.
      WhatsApp = `whatsappIdentifier` borné E.164 (même règle que `WhatsAppIdentifier`).
- [x] **API** : champ `fillRate` (`{key, filled, total}[]`) ajouté à `AdminVivierOutput` / provider.
- [x] **UI** : `AdminFillRateSection.vue` (grille de tuiles) au-dessus de la liste `/candidats`.
- [x] **i18n** : libellés `fr`/`en`/`ar`.
- [x] **Contrat OpenAPI + types front** régénérés.
- [x] **Test** fonctionnel : `AdminVivierTest` couvre la forme et la cohérence (`filled ≤ total`).

## Invariants

- Lecture seule, gardée `ROLE_ADMIN` (agrégat, aucun profil nommé — pas de masquage à gérer).
- Mêmes chiffres que `app:qualification:report` / l'écran santé du vivier : une seule source.
- Les coordonnées se comptent à la résolution `Person` → réplica, jamais sur `person.*` seul.

## Hors périmètre

- Score de complétude **par profil** (colonne dans la liste) — écarté au profit de l'agrégat.
- Rendu de `fillRate` sur l'écran `/vivier` lui-même — le champ existe sur l'endpoint, l'y afficher
  serait un suivi trivial.

## Docs à tenir à jour

- Aucune : le rapport de cadrage reste la référence de méthode ; l'écran en est la vue live.
