# TASK 028 — Espace Entreprise : validation favoris & crédits/dévoilement sous nouveaux rôles

- **Créée le** : 2026-06-30.
- **Statut** : ⏳ **validation** — **dédiée aux tests & à la re-garde**. Les routes sont **déjà codées**
  (favoris → 008, crédits/dévoilement → 005) ; aucune refonte fonctionnelle. Sœur de **026** (qui valide
  le cycle de vie & les membres) pour le volet **exploitation des candidats**.
- **Périmètre** : revalider de bout en bout les routes **favoris partagés** et **crédits / dévoilement**,
  re-gardées par les **nouveaux rôles** (`ROLE_COMPANY_OWNER` / `ROLE_COMPANY_MEMBER`, 025a) au lieu de
  l'ancien « membre validé ». Vérifier isolation multi-tenant, masquage et invariants crédits.
- **Hors périmètre** : tout dev (routes existantes) ; achat de crédits (Stripe `credit-purchases`) ;
  allocation nominative de crédits (tranche billing) ; assignation responsable de suivi (→ **029**) ;
  recherches sauvegardées (→ **030**) ; notes recruteurs (V2) ; doc officielle `docs/` (→ **027**).

## Dépendances

- **Après 025a** : les voters/routes doivent résoudre les **nouveaux** noms de rôles. Le
  `OrganizationVoter::ACCESS` (membre + `Organization` `VALIDEE`) reste la garde, aligné 025a.

## Périmètre de validation (routes)

| Méthode | Route | Rôle | Origine |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{id}/favorites` | membre validé (`COMPANY_*`) | ✅ 008 |
| POST | `/v1/organizations/{id}/favorites` | membre validé (`COMPANY_*`) | ✅ 008 |
| DELETE | `/v1/organizations/{id}/favorites/{candidateId}` | membre validé (`COMPANY_*`) | ✅ 008 |
| GET | `/v1/organizations/{id}/credit-wallet` | membre validé (`COMPANY_*`) | ✅ 005 |
| GET | `/v1/organizations/{id}/credit-transactions` | membre validé (`COMPANY_*`) | ✅ 005 |
| POST | `/v1/organizations/{id}/revealed-profiles` | membre validé (`COMPANY_*`) | ✅ 005 |
| GET | `/v1/organizations/{id}/revealed-profiles` | membre validé (`COMPANY_*`) | ✅ 005 |

## Règles à re-vérifier (cf. data-integrity.md & data-model.md)

- **Garde de rôle** : `OWNER` **et** `MEMBER` d'une `Organization` **`VALIDEE`** accèdent ; non-membre,
  membre d'une autre entreprise, ou organisation `EN_REVUE`/`REFUSE` → **403**.
- **Favoris** : ajout idempotent (`(organization, candidate)` unique), liste partagée, suppression ;
  `addedBy` tracé.
- **Crédits / dévoilement** (cœur sensible — invariants préservés) : débit **atomique + idempotent**,
  **solde jamais négatif** (402 + `CHECK`), `wallet.balance` == somme des `CreditTransaction`.
- **Masquage** : un profil **non dévoilé** reste masqué dans les enveloppes renvoyées.

## Plan de tests (tests/Functional)

- Favoris : `OWNER` ajoute/liste/supprime ; `MEMBER` idem ; cross-tenant → 403.
- Crédits : `OWNER` et `MEMBER` lisent wallet/journal ; dévoilement → 201 + débit ; ré-dévoilement →
  no-op ; wallet à 0 → 402.
- Isolation : membre d'une autre entreprise → 403 sur chaque route ; `Organization` non validée → 403.

## Critères de validation

- [ ] Les 7 routes répondent et sont gardées par les **nouveaux** rôles (`COMPANY_OWNER`/`MEMBER`).
- [ ] Invariants crédits (atomicité, idempotence, solde ≥ 0) toujours verts.
- [ ] Favoris idempotents + isolation multi-tenant vérifiés (403 attendus).
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).

## Références

- Existant : tâches **005** (crédits/dévoilement) · **008** (favoris) ; révision d'accès **023**.
- Rôles : **025a** (renommage & hiérarchie). Sœur : **026** (validation cycle de vie & membres).
- Aval : **027** (doc officielle).
- Invariants : [data-integrity.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md) ·
  [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) §cluster entreprise.
