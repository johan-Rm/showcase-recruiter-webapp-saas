# Profil témoin candidat — Salma Benjelloun (démo / recette)

Jeu de données de référence pour un **candidat showcase-recruiter-webapp-saas complet** : différenciants showcase-recruiter-webapp-saas
renseignés + réplica Jarvi alimentée. Profil volontairement **non-tech** (supply chain / industrie)
pour compléter les profils dev existants.

> **Pas une fixture.** Les candidats entrent par la **CLI d'import Jarvi** (profil pro →
> `candidate_jarvi`) et par l'**inscription** (compte `User` + `Person`). Les différenciants CAP
> NOUR (projet Maroc, présence, salaire, star) sont saisis via l'API / l'espace candidat. Ce
> document décrit l'état cible du profil une fois ces trois canaux passés.

## 1. Person + User (inscription)

| Champ | Valeur |
| --- | --- |
| givenName / familyName | Salma **Benjelloun** |
| contactEmail / User.email | `salma.benjelloun@example.test` |
| Mot de passe (démo) | `password` |
| phone | `+33 6 12 34 56 78` |
| country / city | FR / Lille |
| linkedinUrl | `https://www.linkedin.com/in/salma-benjelloun-demo` |

## 2. Candidate (maître showcase-recruiter-webapp-saas — différenciants)

| Champ | Valeur |
| --- | --- |
| jarviProfileId | UUID Jarvi (clé d'upsert, fourni par l'import) |
| desiredSector | `sector:industry` (Industrie / الصناعة / Industry) — code à prévoir au référentiel |
| desiredFunction | `job_function:supply_chain` (Responsable Supply Chain / مسؤولة سلسلة التوريد / Supply Chain Manager) — code à prévoir au référentiel |
| returnHorizon | `M6` |
| returnProject | « Rejoindre un acteur industriel de la zone Tanger Med pour structurer sa chaîne logistique, dans le cadre d'une installation familiale prévue début 2027. » |
| hasPiedATerre / piedATerreCity | `true` / Tanger |
| geoMobility | `true` (Tanger–Casablanca–Kénitra) |
| availability | `NOTICE_3M` (préavis en cours) |
| salaryExpected / salaryMin | 42 000,00 MAD / 35 000,00 MAD (mensuel) |
| visibility | `VISIBLE` |
| syncStatus / syncedAt | `SYNCED` / 2026-06-28 09:00 |
| starInterviewedAt | 2026-06-25 14:30 |
| starInterviewReport | « Entretien showcase-recruiter-webapp-saas réalisé : parcours supply chain solide (8 ans, distribution puis automobile), projet de retour mûri et daté, anglais courant, très bonne communication. » |
| PresencePeriod | 2026-08-10 → 2026-08-24, Tanger |

## 3. CandidateJarvi (réplica opaque — vient de la CLI d'import)

| Champ | Valeur |
| --- | --- |
| headline | « Responsable Supply Chain — Distribution & Industrie automobile » |
| summary | « 8 ans d'expérience en pilotage de flux logistiques et planification S&OP, entre grande distribution et équipementier automobile. Basée à Lille, projet de retour au Maroc (Tanger Med). » |
| workExperiences | ① Responsable Supply Chain — Norauto France Distribution, 2022-03 → *en cours* · ② Planificatrice S&OP — Valéo Systèmes (site Amiens), 2019-01 → 2022-02 · ③ Coordinatrice logistique — Groupe Casino Nord, 2017-09 → 2018-12 (`title, companyName, startDate, endDate, source: RESUME`) |
| educations | ① Master Management de la chaîne logistique — IAE Lille, 2015-09 → 2017-06 · ② Licence Économie-Gestion — Université Hassan II Casablanca, 2012-09 → 2015-06 |
| languages | fr `NATIVE_OR_BILINGUAL` · ar `NATIVE_OR_BILINGUAL` · en `PROFESSIONAL` |
| skills | Planification S&OP, SAP APO, Gestion des stocks, Prévision de la demande, Transport international, Douane & Incoterms, Lean Management, Power BI, Management d'équipe (12 pers.), Excel avancé |
| resumes (métadonnées seules) | `fileId: 00000000-0000-7000-8000-0000000000f2`, `cv-salma-benjelloun.pdf`, `application/pdf`, `parsedAt: 2026-06-20T10:12:00Z` |
| contacts | `emailAddresses: [salma.benjelloun@example.test]` · `phoneNumbers: [+33612345678]` · `whatsappIdentifier: +33612345678` · `profileUrl` LinkedIn + `publicIdentifier: salma-benjelloun-demo` |
| rawPayload | `source: LINKEDIN`, `updatedAt` (curseur d'ingestion), `fieldsValues[]` avec le custom field **« Récap Entretien »** (richtext HTML : dispo, prétentions 42k MAD, avis recruteur — exposition **recruteur only**), `manualHistoryEntries[]` : 1 note `PHONECALL` du 2026-06-25 (« Entretien 45 min. Candidate qualifiée, anglais 8/10, préavis 3 mois négociable. ») |
| syncStatus / syncedAt | `SYNCED` / 2026-06-28 09:00 |

## Points induits

- Secteur et fonction sont des **valeurs libres** : « Industrie » et « Supply chain » s'écrivent
  telles quelles, sans référentiel à respecter.
- Aucun candidat démo ne dispose aujourd'hui d'un compte `User` connectable : ce profil sert aussi
  de cas de test pour l'espace candidat.
- Structures JSON conformes à [profil-jarvi-reference.md](api/profil-jarvi-reference.md) (liste
  blanche `candidate_jarvi`, récap entretien dans `rawPayload`, CV métadonnées seules).
