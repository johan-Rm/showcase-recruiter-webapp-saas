# TASK 089 — API : câbler l'inscription candidat sur Jarvi `/applicants` (ancrage profileId)

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire.
- **Type** : API — onboarding candidat + intégration Jarvi.
- **Dépend de** : tâche `088` (canal `/applicants`).
- **Lié à** : tâches `013` (projection candidat), `015` (socle onboarding), `020`/`034` (ingestion),
  `033` (magic link consentement), `090` (enrichissement async).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/api/inscription.md`
  - `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`

## Contexte

Aujourd'hui [CandidateOnboarder.php](../../api/src/Domain/Candidate/CandidateOnboarder.php)
**ne contacte pas Jarvi** : il minte un `jarviProfileId` = **UUIDv7 aléatoire local** et crée un
`CandidateJarvi` vide (`PENDING`). Or l'import quotidien ([ProfileIngestor.php](../../api/src/Domain/Jarvi/ProfileIngestor.php))
s'ancre sur l'**`id` Jarvi réel** → un candidat auto-inscrit avec un UUID aléatoire **ne matchera
jamais** son profil Jarvi. Le `linkedinUrl` (obligatoire dans
[CandidateOnboardingInput.php](../../api/src/ApiResource/Input/CandidateOnboardingInput.php)) est
stocké sur `Person` mais **aucun rapprochement n'est fait**.

L'invariant produit « une inscription passe par Jarvi » n'est donc pas réalisé.

## Objectif

À l'inscription, créer/enrichir le profil côté Jarvi via `/applicants` et **ancrer le `Candidate`
sur le `profileId` renvoyé**, pour que l'ingestion peuple ensuite `candidate_jarvi`.

## Périmètre

- Dans le flux d'onboarding candidat ([CandidateOnboardingProcessor.php](../../api/src/State/Processor/CandidateOnboardingProcessor.php)
  → `CandidateOnboarder`), après création `Person`/`User`/`Candidate` :
  - appeler `JarviApplicantWriter::submit()` avec `externalId = Person.uuid`, identité,
    `linkedinUrl`, `projectId = <projet vivier showcase-recruiter-webapp-saas 00000000-…>` (pas un projet de test),
    et le CV si présent (tâche `091`).
  - **remplacer** le `jarviProfileId` local par le `profileId` Jarvi retourné (ancre stable).
- Rendre l'appel Jarvi **non bloquant pour l'inscription** : idéalement via Messenger (chaîne async
  tâche `080`) avec retry ; l'inscription réussit même si Jarvi est indisponible, le `Candidate`
  reste rattachable (statut de sync).
- Idempotence : re-soumission (même `externalId`) = même `profileId` (upsert Jarvi).
- Gérer le cas « profil Jarvi déjà existant » (déjà dans le vivier) : l'upsert doit converger sur
  l'ancre existante, pas créer un doublon.

## Hors périmètre

- Statut d'enrichissement + délai ~4 min exposé au candidat → tâche `090`.
- Upload/validation CV → tâche `091`.
- Édition des différenciants showcase-recruiter-webapp-saas → tâche `016`.

## Invariants

- Aucune donnée maître showcase-recruiter-webapp-saas n'est écrasée par la remontée Jarvi.
- L'inscription ne doit pas échouer si Jarvi est momentanément indisponible.
- `Candidate.jarviProfileId` = `profileId` Jarvi (réel), plus jamais un UUID purement local.
- Projet d'association = le projet vivier showcase-recruiter-webapp-saas, pour que l'import quotidien le capte.

## Critères d'acceptation

- [ ] Une inscription candidat déclenche un `POST /applicants` (réel ou mocké en test).
- [ ] `Candidate.jarviProfileId` est mis à jour avec le `profileId` renvoyé par Jarvi.
- [ ] L'inscription réussit même si l'appel Jarvi échoue (dégradé + reprise).
- [ ] Une 2ᵉ inscription avec le même `externalId` ne crée pas de doublon Jarvi.
- [ ] Après un run d'import, `candidate_jarvi` du candidat inscrit est peuplé (`SYNCED`).
- [ ] Tests : nominal, Jarvi KO, ré-inscription idempotente.

## Points de vigilance

- **RGPD/consentement** : coordonner avec la tâche `033` (le candidat consent avant remontée Jarvi).
- **Course avec l'import** : le profil est vide ~4 min ; ne pas conclure « échec » trop tôt (→ `090`).
- **Quota Jarvi**.

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/inscription.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
