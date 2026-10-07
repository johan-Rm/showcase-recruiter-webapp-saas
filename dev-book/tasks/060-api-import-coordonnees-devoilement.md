# TASK 060 — API : coordonnées perdues au dévoilement (ingestion → person)

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : correction (bug import Jarvi / dévoilement).
- **Dépend de** : tâches `020`/`034` (ingestion Jarvi), `005` (crédits/dévoilement).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` (constat n°0 — analyse du 2026-07-09)
  - `dev-book/cadrage/entreprise/api/credits_et_devoilement.md`

## Contexte

Constat sur profil réel (Mehdi R., `jarviProfileId 1412952b-467c-43ab-913e-01bbe7999793`) puis
vérifié sur tout le vivier importé (1697 candidats) : **un recruteur qui paie un crédit pour
dévoiler un profil obtient des coordonnées `null`** alors que la donnée existe dans la réplica.

- Le mapper expose `person.contactEmail / phone / linkedinUrl` quand `revealed = true`
  (`api/src/State/CandidateMapper.php:84-86`).
- L'ingestion n'écrit **jamais** ces colonnes (`api/src/Domain/Jarvi/ProfileIngestor.php:145` :
  seulement nom / pays / ville) : en base, **5/1697 emails** (comptes seedés), **0/1697 LinkedIn**.
- La donnée est pourtant répliquée : `candidate_jarvi.contacts` (emails/téléphones typés),
  `raw_payload.profileUrl` (URL LinkedIn), `raw_payload.linkedinId`.
- `RevealProcessor` ne recopie rien au moment du reveal.

## Décision d'implémentation (proposée)

Corriger **côté ingestion** (option recommandée) : monter les contacts Jarvi dans `person` à
l'import — `contactEmail` (premier email), `phone` (premier numéro), `linkedinUrl`
(`profileUrl`). Le mapper et les règles de masquage restent inchangés (la donnée normalisée
vit dans `person`, la réplica reste opaque). Alternative écartée : faire lire la réplica au
mapper (déporte du parsing JSON dans la couche de sortie).

⚠️ Ne pas écraser une coordonnée déjà renseignée par le candidat lui-même (espace candidat =
maître sur ses différenciants ; définir la règle : Jarvi ne remplit que les champs vides).

## Périmètre

- `ProfileIngestor` : mapping contacts → `Person.contactEmail` / `Person.phone` +
  `profileUrl` → `Person.linkedinUrl` (création **et** mise à jour, sans écraser une saisie
  candidat non vide).
- **Backfill du vivier existant** : un ré-import (`app:jarvi:profiles:import --full`) doit
  remplir les colonnes des 1697 profils déjà importés (upsert idempotent — vérifier ce point).
- Coquille secondaire : exposer la **fraîcheur Jarvi** (`candidate_jarvi.syncedAt` /
  `updatedAt` Jarvi) dans `CandidateOutput` — aujourd'hui seul l'`updatedAt` local (= date
  d'import) est exposé, information trompeuse pour le recruteur.

## Invariants

- Masquage inchangé : coordonnées **null tant que non dévoilé**, servies uniquement si
  `revealed = true` (jamais contournable client).
- La réplica `candidate_jarvi` reste **opaque et lecture seule** ; `person` porte la donnée
  normalisée.
- Aucune écriture vers Jarvi.

## Critères d'acceptation

- [ ] Après import d'un profil Jarvi avec email/téléphone/profileUrl, `person` porte ces valeurs.
- [ ] Après dévoilement, `GET /v1/candidates/{id}` renvoie email/téléphone/LinkedIn non null
      (profil témoin Mehdi R.).
- [ ] Avant dévoilement, ces champs restent null (test de non-régression masquage).
- [ ] Une coordonnée saisie par le candidat n'est pas écrasée par un ré-import.
- [ ] Ré-import complet : backfill effectif du vivier existant (compteurs > 0 en base).
- [ ] `make api-quality` vert ; tests fonctionnels ingestion + reveal.

## Hors périmètre

- Normalisation ville/localisation (locality Jarvi null sur ~29 % du vivier) — critère de
  filtrage, à traiter avec le chantier filtres.
- Téléchargement du binaire CV (pas d'URL exposée par Jarvi — cf. contrat read).
- Multi-emails / multi-téléphones (on prend le premier ; V2 si besoin).
