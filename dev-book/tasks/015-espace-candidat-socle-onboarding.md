# TASK 015 — Espace Candidat : socle (rôle, voters self, onboarding)

- **Créée le** : 2026-06-23.
- **Statut** : ✅ implémenté (2026-06-23) — `CandidateVoter`, `CandidateOnboarder`, `/me` candidat + tests.
- **Type** : fondation P4 (Espace Candidat).
- **Dépend de** : tâche `013` (Candidate = projection ancrée sur `jarviProfileId`).
- **Réf cadrage** : [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) §1-2, [`PARCOURS_UTILISATEUR_CANDIDAT.md`](../cadrage/PARCOURS_UTILISATEUR_CANDIDAT.md) §1-4.

## Contexte

L'API expose aujourd'hui le candidat **vu par le recruteur** (lecture + masquage). Le **self-service
candidat** est greenfield : aucun `ROLE_CANDIDATE`, aucun Input/Processor d'écriture candidat. 013
introduit déjà la **création paresseuse de la projection à l'inscription** (compte + différenciants) ;
cette tâche construit la **surface candidat** par-dessus ce modèle.

## Périmètre

- **Rôle & sécurité** : `ROLE_CANDIDATE` (hiérarchie dans `security.yaml`) ; **voter « self »** —
  un candidat n'agit que sur **son** `Candidate` (`candidate.person.user === currentUser`).
- **Onboarding candidat** : endpoint de création du compte candidat = **projection** (lien `Person`/`User`)
  + saisie initiale des **différenciants** (cf. task 016). S'appuie sur la matérialisation 013, ne la
  réinvente pas.
- **`MeOutput` candidat** : étendre le `/me` pour exposer le contexte candidat (complétude, casquette).
- **Cumul de casquettes** : un `Person` peut être candidat **et** recruteur (cf. data-model §196) →
  ne pas présumer un type unique ; switch de contexte côté webapp.

## Hors périmètre

- Édition profil pro + sync Jarvi → task 016.
- `PresencePeriod` → task 017 ; visibilité → task 018 ; activité/notifs → task 019.

## Invariants

- **Toute écriture passe par l'API Symfony** (garde-fou non négociable).
- **Self-only** strict : aucun accès croisé entre candidats (testé).
- Pas de régression sur l'auth recruteur existante (JWT + refresh + voters entreprise).

## Critères d'acceptation

- [ ] `ROLE_CANDIDATE` + voter self opérationnels ; un candidat ne peut éditer qu'un autre profil → 403.
- [ ] Onboarding crée la projection (compte + différenciants) cohérente avec 013.
- [ ] `/me` renvoie le contexte candidat ; cumul candidat/recruteur géré.
- [ ] `make api-quality` vert ; tests fonctionnels (self-only, cumul casquettes).

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` : routes onboarding/`me` candidat.
- [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) : statut fonctionnalités.
