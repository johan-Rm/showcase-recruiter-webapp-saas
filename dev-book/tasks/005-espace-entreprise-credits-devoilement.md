# TASK 005 — Espace Entreprise : crédits & dévoilement (cœur sensible, TDD)

- **Créée le** : 2026-06-18.
- **Statut** : ✅ implémenté (2026-06-18) — crédits & dévoilement (cœur sensible). _Cible du reveal
  révisée par `013`._ Accès révisé par `023` : Organization validée requise.
- **Périmètre** : le **cœur sensible crédits** (cf. [data-integrity.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md)).
  Lecture du solde et du journal, et **dévoilement d'un profil** = débit d'1 crédit **atomique,
  idempotent, solde jamais négatif**. Développé **en tests d'abord**.
- **Hors périmètre** : achat de crédits (Stripe `credit-purchases`), allocation/diffusion des crédits
  aux membres par `ROLE_COMPANY_OWNER`, recherche/lecture candidats (masquage), favoris. Tranches
  suivantes.

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{id}/credit-wallet` | 🧑‍💼 membre validé | solde courant |
| GET | `/v1/organizations/{id}/credit-transactions` | 🧑‍💼 membre validé | journal (enveloppe plate) |
| POST | `/v1/organizations/{id}/revealed-profiles` | 🧑‍💼 membre validé | `{candidateId}` → **débit 1 crédit atomique** |
| GET | `/v1/organizations/{id}/revealed-profiles` | 🧑‍💼 membre validé | « déjà dévoilés » (partagés entreprise) |

> Achat et diffusion des crédits : réservés au `ROLE_COMPANY_OWNER`. L'endpoint d'allocation
> nominative sera livré avec la tranche billing ; le reveal V1 continue de consommer le wallet
> entreprise partagé.

## Invariants (cf. data-integrity.md) — testés en priorité

1. **Atomicité** : débit wallet + `CreditTransaction(CONSUMPTION, -1)` + `RevealedProfile` dans une
   **seule transaction**, avec **verrou pessimiste** sur le wallet (sérialise les dévoilements
   concurrents d'une même entreprise).
2. **Idempotence** : dévoiler un candidat **déjà dévoilé** par l'entreprise = **no-op** (renvoie le
   `RevealedProfile` existant, **aucun second débit**). Garanti par l'unicité `(organization, candidate)`.
3. **Solde ≥ 0** : si `balance < coût`, refus **402 Payment Required**, **aucun débit**. Backstop
   en base : `CHECK (credit_wallet.balance >= 0)`.
4. **Cohérence** : `wallet.balance` == somme des `CreditTransaction.amount` ; chaque débit trace le
   `recruiter` et lie le `RevealedProfile`.

## Structure

- `Domain/Credit/ProfileRevealer` (transaction + verrou + invariants) ; `InsufficientCreditsException`.
- `ApiResource/Input/RevealInput` (`candidateId`) ; `Output/CreditWalletOutput`,
  `CreditTransactionOutput`, `RevealedProfileOutput`.
- `State/Processor/RevealProcessor` ; `Provider/CreditWalletProvider`, `CreditTransactionsProvider`,
  `RevealedProfilesProvider`.
- `Repository/CandidateRepository` (résolution `candidateId`).
- Voter : `OrganizationVoter::ACCESS` (membre + Organization `VALIDEE`) gate ces endpoints ; le coût
  = `CreditPolicy::REVEAL_COST`.

## Tests (TDD — écrits avant l'implémentation)

- `tests/Functional/RevealTest.php` :
  - dévoilement OK → 201, solde 2 → 1, +1 transaction `CONSUMPTION` (-1), `RevealedProfile` créé.
  - **idempotent** : 2e dévoilement du même candidat → pas de 2e débit (solde reste 1, 1 seul reveal).
  - **solde insuffisant** (wallet à 0) → 402, aucun débit, aucun reveal.
  - isolation : non-membre → 403.
  - wallet/transactions/revealed-profiles : lecture membre OK, non-membre 403.

## Critères d'acceptation

- [ ] Les 4 endpoints répondent ; voter membre effectif.
- [ ] Débit **atomique + idempotent** ; **solde jamais négatif** (402 + CHECK).
- [ ] `wallet.balance` == somme des transactions après dévoilements.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
