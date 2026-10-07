# showcase-recruiter-webapp-saas — Intégrité de la base de données

> Stratégie d'intégrité. Complète [data-model.md](./data-model.md).
> Principe directeur : **la base PostgreSQL est la garante finale de l'intégrité** — pas seulement
> le code applicatif. Validation applicative et base se renforcent (défense en profondeur).

## Défense en profondeur — 5 couches

```
1. API Platform / Symfony Validator   validation des entrées (formats, plages, requis)
2. Doctrine (types, relations)         typage fort, mapping des FK
3. PostgreSQL                          FK · UNIQUE · NOT NULL · CHECK · transactions  ← garant final
4. Services domaine                    invariants métier (idempotence, réconciliation)
5. Tests TDD-ciblés                    crédits, reveal, sync Jarvi
```

Une validation contournée (script, import, bug applicatif) ne doit **jamais** pouvoir corrompre la
base : les invariants critiques sont aussi en **couche 3**.

## Mécanismes utilisés

- **Clés étrangères** sur **toutes** les relations (pas d'orphelins).
- **Contraintes d'unicité** (voir liste).
- **NOT NULL** sur les champs requis.
- **CHECK** pour les invariants métier exprimables en base (bornes, cohérence temporelle).
- **Transactions** pour les opérations multi-tables (atomicité).
- **Types stricts** (`date`, `timestamptz`, `numeric`, UTF-8) — jamais de string libre pour
  dates/montants.
- **Enums = `string` + `CHECK`** (mappés sur enums PHP côté code). Souple à faire évoluer, lisible
  en base. Pas de type ENUM natif PG (migrations lourdes), pas d'entiers (illisibles/fragiles).

## Champs JSON — usage restreint

Les colonnes **JSON** sont réservées à la **configuration / personnalisation**. Elles **ne doivent
jamais contenir d'identifiants ni de relations** : toute relation est une **FK réelle** (contrainte,
intégrité garantie). Raison : un id stocké en JSON n'a **aucune intégrité référentielle**
(orphelins indétectables, exactement la corruption qu'on veut éviter).

| Champ JSON | Contenu |
| --- | --- |
| `Notification.data` | contexte non-relationnel (compteurs, libellés). Les entités liées = **FK** (`relatedOrganization`, `relatedCandidate`) |
| `SavedSearch.criteria` | **valeurs métier** (slugs `codeValue`, codes langue, primitives), **pas d'ids/FK** |
| `User.roles` | exception assumée — convention Symfony Security : liste de rôles (valeurs), pas de relations |
| `CandidateJarvi.*` (workExperiences, educations, languages, skills, resumes, contacts, rawPayload) | **réplica opaque Jarvi** (lecture seule, Jarvi maître) : données externes non normalisées, **aucun id/FK interne showcase-recruiter-webapp-saas** |

Règle : **une relation interne → une FK** ; le JSON ne sert qu'à de la donnée de configuration ou à
un **snapshot externe opaque** (réplica Jarvi), sans sémantique relationnelle interne.

## Contraintes d'unicité

Toute entité porte de plus un `uuid` unique (`AbstractEntity`).

| Table | Unicité |
| --- | --- |
| `User` | `email` |
| `Candidate` | `jarviProfileId` |
| `CandidateJarvi` | `candidate` (1-1) |
| `CreditWallet` | `organization` (1-1) |
| `RevealedProfile` | `(organization, candidate)` |
| `Favorite` | `(organization, candidate)` |
| `CandidateAssignment` | `(organization, candidate)` |
| `MagicLinkToken` / `PasswordSetupToken` / `PasswordResetToken` | `tokenHash` |
| `JarviSyncState` | `name` |

## NOT NULL (requis)

Liaisons obligatoires (`Candidate.person`, `Candidate.jarviProfileId`, `Recruiter.person`,
`Recruiter.organization`, `*.organization`/`*.candidate` des entités d'usage),
`User.email`/`password`/`roles`,
`CreditTransaction.organization`/`type`/`amount`/`balanceAfter`, `Person.givenName`/`familyName`.

## Contraintes CHECK (invariants en base)

- **`CreditWallet.balance >= 0`** — le solde ne peut jamais être négatif.
- `CreditTransaction.amount <> 0`.
- `MonetaryAmount.amount >= 0` (salaires, etc.).
- `Candidate.salaryMin <= salaryExpected` (quand les deux sont renseignés).
- **Cohérence temporelle** `endDate >= startDate` : `PresencePeriod`, `CandidateOption`.
- Enums : `CHECK (col IN (…valeurs…))`.

## Intégrité référentielle & suppression

- **FK partout**, `ON DELETE RESTRICT` par défaut (empêche les orphelins et la perte de contexte).
- **`CASCADE`** uniquement pour les **pur-dépendants** : dépendants candidat (`PresencePeriod`,
  `CandidateJarvi`) → `Candidate`.
- **Jamais de cascade** sur les enregistrements **financiers / d'audit** (`CreditTransaction`,
  `RevealedProfile`) : ils survivent et tracent l'historique.
- **Soft delete** (`deletedAt`) par défaut → on ne hard-delete pas ; les requêtes filtrent
  `deletedAt IS NULL`. Le hard delete est réservé au **droit à l'oubli RGPD** (procédure dédiée,
  runbook, à cadrer).

## Atomicité — opérations sensibles

- **Déblocage d'un profil** = transaction unique : débit (`CreditTransaction` `CONSUMPTION`) +
  création `RevealedProfile` + mise à jour `CreditWallet.balance`. Tout réussit ou rien.
  Idempotent ; l'unicité `(organization, candidate)` empêche le double déblocage.
- **Réconciliation** : `CreditWallet.balance` doit toujours = `Σ CreditTransaction.amount`
  (invariant vérifié par les services domaine + tests).

## Sauvegardes & corruption physique

Sauvegardes PostgreSQL régulières (dump planifié + rétention) côté infra — procédure documentée
en **runbook**. Restauration testée périodiquement.

## Où c'est garanti

- **Migrations Doctrine** : portent les FK, UNIQUE, NOT NULL, CHECK (la base reflète ces règles).
- **Schema validate** en CI (`doctrine:schema:validate`).
- **Tests TDD-ciblés** (cf. [testing-and-ci.md](../3.quality/testing-and-ci.md)) sur le cœur
  sensible : solde jamais négatif, reveal atomique/idempotent, pas d'orphelins.
- **Checklist de review** : crédits idempotents, migrations préservant les données.

## Décidé

- **Défense en profondeur** : la base est garante (FK + UNIQUE + NOT NULL + CHECK métier), en plus
  de la validation Symfony et des invariants domaine.
- **Enums = `string` + `CHECK`** (mappés sur enums PHP).
- `ON DELETE RESTRICT` par défaut, `CASCADE` pour les pur-dépendants, jamais sur le financier/audit.
- Soft delete par défaut ; hard delete réservé au RGPD (runbook).
