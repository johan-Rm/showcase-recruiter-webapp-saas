# TASK 104 — API : ré-ingérer l'identité d'entreprise LinkedIn (`linkedinCompanyId`)

- **Créée le** : 2026-07-19.
- **Statut** : 🟢 code livré — reste la ré-ingestion `--full` (run Jarvi).
- **Type** : API — ingestion Jarvi / réplica `candidate_jarvi`.
- **Lié à** : `020` (ingestion), `034` (import CLI), `105` (sac secteur — consommateur), `095` (axes d'expérience).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
  - `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`

## Contexte

Le nom d'employeur (`companyName`) est le champ le mieux rempli du parcours : **99,9 %** des postes
(9 949 / 9 955), 100 % des profils qui ont une expérience. Mais c'est du **texte libre sale** :
sur le vivier, **6 654 noms distincts** — gonflés par les variantes (casse, espaces, orthographe :
« Catalyst Business Solutions » apparaît en 4 graphies).

Jarvi fournit pourtant une **identité stable** par poste — `linkedinCompanyId` + `linkedinCompanyData
{ id, name }` (nom canonique) — **que notre ingestion jette** aujourd'hui : la réplica ne garde que
`{ companyName, title, startDate, endDate, id, source }`.

## Objectif

Conserver `linkedinCompanyId` et le nom canonique d'entreprise sur chaque poste, pour **effondrer**
les 6 654 variantes en entreprises canoniques uniques — préalable au sac « secteurs » (`105`).

## Livré (code)

- [x] Requête GraphQL `JarviClient` étendue : `positions { … linkedinCompanyId linkedinCompanyData { id name } }`
  (les 3 variantes). `withoutTypename` récursif → les champs traversent proprement `arrayRows`.
- [x] Passthrough automatique dans `work_experiences` (aucune transformation : la réplica reste
  opaque, Jarvi maître).

## Reste à faire

- [ ] **Ré-ingestion `--full`** pour peupler l'existant (run Jarvi, clé + rate limit 500/j). Phase
  dev = base recréable.
- [ ] Vérifier en base la couverture réelle de `linkedinCompanyId` (present vs null) et le nombre
  d'entreprises **canoniques** distinctes (= vraie taille du chantier `105`).

## Invariants

- Réplica `candidate_jarvi` = lecture seule, opaque, Jarvi maître : on **ajoute** des champs bruts,
  on ne normalise pas ici (la canonicalisation vit dans le sac secteur `105`).
- Ingestion idempotente ; réingestion ne duplique pas.

## Points de vigilance

- `linkedinCompanyId` / `linkedinCompanyData` sont **nullables** : présents surtout sur les postes
  `source: LINKEDIN` reliés à une page entreprise ; absents pour la saisie libre. La canonicalisation
  (`105`) devra retomber sur `companyName` normalisé quand l'ID manque.
- **Le secteur n'est PAS dans cette donnée** : `linkedinCompanyData` = `{ id, name }`, sans industrie.
  Le secteur reste à **déduire** du nom canonique (`105`).

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`
