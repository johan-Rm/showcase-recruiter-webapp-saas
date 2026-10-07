# TASK 091 — Inscription candidat : upload de CV (extraction Jarvi `resumesFiles`)

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire — **décision produit préalable** (CV obligatoire, optionnel, ou hors MVP ?).
- **Type** : API + Webapp — onboarding candidat.
- **Dépend de** : tâche `088` (canal `/applicants`).
- **Lié à** : tâches `089` (câblage onboarding), `015` (socle onboarding candidat).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/api/inscription.md`
  - `dev-book/cadrage/candidat/ui/parcours_utilisateur_candidat.md`

## Contexte

Confirmé (test 2026-07-18) : Jarvi extrait un CV fourni en base64 via `resumesFiles`
(`[{ fileName, data: "data:@file/pdf;base64,…" }]`) et peuple positions/skills en `source: RESUME`.
Actuellement [CandidateOnboardingInput.php](../../api/src/ApiResource/Input/CandidateOnboardingInput.php)
**n'a aucun champ CV** ; le candidat ne fournit qu'une `linkedinUrl`. Le CV et le LinkedIn sont
**complémentaires** (hybride = meilleure couverture).

## Objectif

Permettre au candidat de joindre un CV à l'inscription, transmis à Jarvi via `/applicants` pour
extraction, en complément (ou substitut) de l'URL LinkedIn.

## Périmètre

- **Back** : champ CV dans l'input d'onboarding (upload multipart ou base64 selon décision), avec
  garde-fous — type MIME (`application/pdf` au moins), taille max, 1 fichier au MVP. Mappage vers
  `JarviApplicant.resumesFiles` (tâche `088`).
- **Front (webapp)** : champ d'upload dans le tunnel d'inscription candidat (accessibilité clavier,
  label, erreurs), encodage/transmission au BFF.
- Régénérer `api/openapi.json` + `webapp/shared/types/api.ts` (gate `contracts-drift`).

## Décision produit à trancher (bloquant)

- CV **obligatoire**, **optionnel**, ou **différé post-MVP** ?
- Règle si ni CV ni LinkedIn exploitables (aujourd'hui `linkedinUrl` est obligatoire) ?

## Hors périmètre

- Copie/stockage du binaire côté showcase-recruiter-webapp-saas (règle « pas de copie » — le binaire reste chez Jarvi,
  servi à la demande, cf. `showcase-recruiter-webapp-saas-jarvi-read-contract`).
- Extraction/parsing côté showcase-recruiter-webapp-saas (c'est Jarvi qui parse).

## Invariants

- Le binaire du CV n'est **pas** copié dans la base showcase-recruiter-webapp-saas ; seules les métadonnées remontent par
  l'ingestion (`candidate_jarvi.resumes[]`).
- Validation stricte du fichier avant envoi (type/taille) — pas de payload arbitraire vers Jarvi.
- RGPD : le CV est une donnée personnelle sensible → consentement (tâche `033`) couvre l'envoi.

## Critères d'acceptation

- [ ] Le candidat peut joindre un CV PDF à l'inscription (selon décision produit).
- [ ] Le CV est transmis à Jarvi via `/applicants` et l'extraction produit des blocs `source: RESUME`.
- [ ] Fichier non conforme (type/taille) rejeté avec message clair (front + back).
- [ ] `openapi.json` et types TS régénérés, gate `contracts-drift` verte.
- [ ] Tests : upload valide, type refusé, taille refusée, absence de CV (comportement selon décision).

## Points de vigilance

- **Poids** : encodage base64 gonfle le payload — borner la taille tôt.
- **Sécurité** : ne pas faire confiance au `Content-Type` client seul (vérifier le contenu).

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/inscription.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
