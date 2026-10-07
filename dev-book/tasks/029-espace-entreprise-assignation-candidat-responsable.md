# TASK 029 — DEV : assignation d'un candidat à un responsable de suivi

- **Créée le** : 2026-06-30.
- **Statut** : ⏳ **à faire** — **dev** (feature de collaboration entreprise, anticipée « V2 » par 008 /
  data-model). *Après 025a* (rôles).
- **Décision projet** : en période de développement, **pas de migration Doctrine dédiée** ; le schéma
  est recréé depuis les entités avec les fixtures.
- **Périmètre** : permettre à un membre d'une entreprise d'**assigner un candidat dévoilé** à un
  **responsable de suivi** (un `Recruiter` membre de la même `Organization`). **1 candidat → 1
  responsable** (réassignable). Visibilité partagée : l'équipe voit qui suit quel profil.
- **Hors périmètre** : pipeline / colonnes de statut (étapes de suivi → V2) ; notes recruteurs
  (`ProfileNote` → V2) ; notifications au responsable (dépend de la brique notif multi-canal, encore
  bloquée → V2) ; assignation d'un candidat **non dévoilé** ; front (webapp) ; doc officielle (→ **027**).

## Décision de cadrage

- Mécanique = **responsable de suivi** (ownership interne léger, style CRM), **pas** un partage
  multi-destinataires ni un pipeline. Unicité **`(organization, candidate)`** → un seul responsable.
- **Cible = candidat dévoilé** : on n'assigne que des profils déjà débloqués par l'entreprise
  (`RevealedProfile` existant). Sinon **409/422**.
- **Qui peut assigner** : tout **membre validé** (`OrganizationVoter::ACCESS`), par symétrie avec les
  favoris partagés. *(À confirmer si l'on veut réserver l'assignation au `COMPANY_OWNER`.)*

## Routes à livrer

| Méthode | Route | Rôle | Payload |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{id}/assignments` | membre validé | liste partagée (qui suit quoi) |
| PUT | `/v1/organizations/{id}/assignments/{candidateId}` | membre validé | `{assigneeRecruiterId}` → assigne / réassigne (upsert) |
| DELETE | `/v1/organizations/{id}/assignments/{candidateId}` | membre validé | retire l'assignation |

## Règles métier

- **Garde** : membre d'une `Organization` `VALIDEE` (`OrganizationVoter::ACCESS`) ; isolation
  multi-tenant stricte (cross-tenant → 403).
- **Responsable valide** : `assigneeRecruiterId` doit être un `Recruiter` **actif de la même
  `Organization`** ; sinon **422**.
- **Candidat dévoilé requis** : `RevealedProfile (organization, candidate)` doit exister → sinon
  **409/422** (« dévoiler avant d'assigner »).
- **Upsert idempotent** : `PUT` sur un candidat déjà assigné **réassigne** (remplace le responsable) ;
  même responsable → no-op. Unicité `(organization, candidate)`.
- **Traçabilité** : conserver `assignedBy` (recruteur courant) + `assignedAt`.

## Structure (cf. api-structure.md & data-model.md)

- **Nouvelle entité** `CandidateAssignment` : `organization` (FK), `candidate` (FK),
  `assignee` (FK → `Recruiter`), `assignedBy` (FK → `Recruiter`), `assignedAt` ; unicité
  `(organization, candidate)`.
- `ApiResource/Input/AssignmentInput` (`assigneeRecruiterId`) ; `Output/AssignmentOutput`
  (enveloppe plate : `candidateId`, `assigneeRecruiterId`, `assignedBy`, `assignedAt`).
- `State/Processor/AssignmentProcessor` (PUT/DELETE) ; `State/Provider/AssignmentsProvider`.
- `Domain/Organization/CandidateAssigner` (validations : responsable même org + candidat dévoilé)
  + exceptions dédiées.
- Réutiliser `OrganizationVoter::ACCESS`, `CandidateRepository`, `RecruiterRepository`,
  `RevealedProfileRepository`.

## Tests (tests/Functional)

- Assignation OK (candidat dévoilé, responsable membre) → 200/201, `assignedAt` tracé.
- Réassignation (PUT) → remplace le responsable, pas de doublon.
- Candidat **non dévoilé** → 409/422 ; responsable **hors org** → 422.
- Liste partagée visible par tout membre ; suppression → retiré.
- Isolation : membre d'une autre entreprise → 403.

## Définition de terminé

- [ ] Entité + schéma recréé depuis Doctrine ; unicité `(organization, candidate)`.
- [ ] 3 routes gardées `COMPANY_*` (membre validé), isolées multi-tenant.
- [ ] Garde « candidat dévoilé » + « responsable même org » effectives.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).

## Références

- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) §cluster entreprise
  (assignation / suivi anticipée), §`RevealedProfile`, §`Recruiter`.
- Existant proche : **008** (favoris partagés, même patterns Input/Output/Processor/Provider) ·
  **005** (`RevealedProfile`). Rôles : **025a**. Aval : **027** (doc).
