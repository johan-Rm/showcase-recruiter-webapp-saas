# TASK 006 — Espace Entreprise : recherche & lecture candidats (masquage)

- **Créée le** : 2026-06-18.
- **Statut** : ✅ implémenté (2026-06-18) — recherche + lecture avec masquage. _Révisé par `013`/`014`
  (la recherche tourne désormais sur la réplica locale)._
- **Périmètre** : exploitation du vivier côté recruteur — **recherche** simple (filtres + pagination)
  et **lecture d'un profil** avec **masquage des coordonnées tant que le candidat n'est pas
  dévoilé** par l'entreprise du recruteur (s'appuie sur les `RevealedProfile` de la tâche 005).
  Réutilise le pattern lecture (Output DTO + Provider + i18n) des tâches 003/004/005.
- **Hors périmètre** : recherche **IA** (langage naturel → MCP Jarvi, route V2 à cadrer), favoris, notes recruteurs
  V2, édition du profil par le candidat (espace candidat, étape 5), upload CV. Tranches ultérieures.

## Endpoints livrés

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/search/candidates` | 🧑‍💼 recruteur | filtres simples + pagination → **cartes masquées** |
| GET | `/v1/candidates/{id}` | 🧑‍💼 recruteur | profil ; **coordonnées masquées** si non dévoilé |

> La recherche est servie par l'**index local** (Doctrine) en V1 ; l'IA (Jarvi MCP) viendra plus tard.
>
> **⚠️ Révisée par `013` / `020` / `014` (2026-06-23).** L'index local est **conservé**, mais sa
> source change : ce n'est plus un profil maître showcase-recruiter-webapp-saas, c'est la **réplica locale lecture seule**
> alimentée par l'**ingestion du périmètre Jarvi** (tâche `020`). La recherche elle-même (Postgres,
> masquage, visibilité) est reprise par la **tâche `014`**. Cf. `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`.

## Règles métier

- **Visibilité** : seuls les `Candidate.visibility = VISIBLE` apparaissent en recherche
  (`HIDDEN`/`RESTRICTED`/`PREMIUM_ONLY` exclus en V1 ; affiner ultérieurement).
- **Masquage** : tant que l'entreprise du recruteur n'a pas dévoilé le candidat
  (`RevealedProfile(organization, candidate)` absent), on **masque** `contactEmail`, `phone`,
  `linkedinUrl` (et nom de famille → initiale). Le reste du profil (accroche, secteur, compétences,
  langues, **présence Maroc ★**, dispo, prétentions) reste **visible** — c'est le hook produit.
- Un champ `revealed` (bool) indique au front si le profil est débloqué.
- **Contexte** : le recruteur appartient à **une** Organization (V1) ; le masquage se calcule pour
  **cette** Organization.

## Filtres V1 (`/v1/search/candidates`)

- `q` (mot-clé sur l'accroche / mots-clés), `sector` (`codeValue`), `function` (`codeValue`),
  `availability`, `returnHorizon`, `geoMobility`, pagination `page`/`perPage` (enveloppe plate).
- Tri par défaut : `updatedAt` desc (UUIDv7 = ordre temporel cohérent).

## Structure (cf. api-structure.md)

- `ApiResource/Output/CandidateCardOutput` (recherche) + `CandidateOutput` (détail) — value objects
  en sous-objet (`salaryExpected: {value,currency}`), collections minimales (`skills`, `languages`,
  `presencePeriods`), métadonnées i18n pour les libellés de référentiels résolus.
- `State/Provider/CandidateSearchProvider`, `CandidateItemProvider`.
- `Domain/Candidate/ProfileMasker` (applique le masquage selon `revealed`) — testable.
- `Repository/CandidateRepository` (déjà créé en 005) : ajouter la requête de recherche filtrée.
- Voter / contexte : recruteur authentifié ; résolution de son `Organization` pour le masquage.

## Tests (tests/Functional)

- Recherche : ne renvoie que les `VISIBLE`, filtres OK, pagination (enveloppe plate).
- Lecture **non dévoilé** : coordonnées masquées, `revealed=false`.
- Lecture **après dévoilement** (RevealedProfile présent) : coordonnées visibles, `revealed=true`.
- i18n : libellés secteur/fonction/compétences résolus selon `?locale` + fallback.

## Critères d'acceptation

- [ ] Les 2 endpoints répondent (recruteur authentifié).
- [ ] Masquage effectif : coordonnées cachées tant que non dévoilé, révélées sinon.
- [ ] Seuls les profils `VISIBLE` sont cherchables ; filtres + pagination plate OK.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
