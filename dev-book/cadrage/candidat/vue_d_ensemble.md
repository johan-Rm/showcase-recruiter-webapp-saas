# showcase-recruiter-webapp-saas — Espace Candidat (référence)

> Vue d'ensemble **consolidée** de l'espace candidat (`candidat.showcase-recruiter-webapp-saas.example`, person-centric).
> Ce doc **route vers le détail**, il ne le duplique pas : usages → [parcours_utilisateur_candidat.md](./ui/parcours_utilisateur_candidat.md) ;
> modèle → [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) (cluster `Candidate`) ; frontière Jarvi →
> [jarvi-integration.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md) ; livraison → `dev-book/tasks/015`→`019` ;
> backlog → [todo.md](./todo.md) ; profil témoin démo/recette → [profil-temoin-salma-benjelloun.md](./profil-temoin-salma-benjelloun.md).
>
> **Phase** : Espace Candidat = **P4** ([roadmap.md](./roadmap.md), jalon **31 août 2026**).
>
> ⚠️ **Écrit post-pivot 013.** Tout ce doc applique la frontière **Jarvi maître du profil pro /
> showcase-recruiter-webapp-saas maître des différenciants**. **`013` implémenté (2026-06-23)** → le socle candidat (`015`)
> et la présence Maroc (`017`) sont **livrés** ; restent `016` (édition profil + sync), `018`
> (visibilité), `019` (activité/notifs), et le branchement Jarvi via l'ingestion (`020`).

## 0. Principe — qui possède quoi (frontière 013)

L'espace candidat manipule **deux zones de données** dont la **source de vérité diffère** :

| Zone | Contenu | Source de vérité | Rôle de l'API showcase-recruiter-webapp-saas |
| --- | --- | --- | --- |
| **Profil pro** | identité professionnelle, CV, expériences, formations, compétences, langues, secteur/fonction, accroche | **Jarvi** (maître) | **réplica locale lecture seule** pour affichage/recherche |
| **Différenciants** ★ | projet de retour (`returnHorizon`), prétentions (`salaryExpected`/`salaryMin`/`geoMobility`), disponibilité (`availability`), **présence Maroc** (`PresencePeriod`), `visibility`, consentement | **showcase-recruiter-webapp-saas** (maître) | **stockage local uniquement** |

> **Décision 2026-07-01** : pas de synchro montante des différenciants showcase-recruiter-webapp-saas vers Jarvi. À
> l'inscription, si le profil n'existe pas encore chez Jarvi, l'`api/` crée un profil Jarvi minimal
> pour établir l'ancre, puis le profil professionnel redevient maître Jarvi et descend par ingestion.

## 1. Fonctionnalités

Statut : ✅ livré · 🚧 partiel · ⏳ à faire (V1) · 🔭 V2. **`013` fait → socle (`015`) et présence (`017`) livrés ; suite en cours.**

| Fonctionnalité | Statut | Source |
| --- | --- | --- |
| Socle candidat : `ROLE_CANDIDATE`, voters « self », onboarding technique actuel | ✅ (2026-06-23) | task 015 |
| Magic Link candidat + invitation des profils Jarvi + consentement | ⏳ V1 | task 033 |
| Édition **différenciants locaux** (projet retour, prétentions, disponibilité) | ⏳ V1 | task 016 |
| Profil pro Jarvi en lecture seule via réplica locale | 🚧 | task 020 |
| **Présence au Maroc** ★ (`PresencePeriod` — dispo entretien) | 🚧 (CRUD ✅ 2026-06-23) | task 017 |
| **Visibilité & consentement** (`visibility` self-service + horodatage ; RGPD complet à cadrer) | 🚧 (visibilité ✅ 2026-06-23) | task 018 |
| Activité autour du profil (`ProfileView` en lecture candidat) | 🚧 (compteurs ✅ 2026-06-24, 019-A) | task 019 |
| Notifications candidat (profil consulté / dévoilé) | ⏳ V1 | task 019 (dépend brique notif multi-canal) |
| Profil Star (entretien showcase-recruiter-webapp-saas + compte rendu) | ⏳ V1 | modèle `Candidate.starInterviewedAt` / `starInterviewReport` |
| Options payantes candidat (`CandidateOption`) | 🔭 V2 | **décision reportée V2** : (a) mise en avant homepage pour une durée, OU (b) diffusion sectorielle du profil complet + compte rendu — cf. [todo.md](./todo.md) |
| Accompagnement personnalisé (coaching, optimisation CV) | 🔭 V2 | parcours #11 |
| Candidature assistée | 🔭 V2 | parcours #12 |

## 2. Actions candidat (self-service)

Le candidat **n'édite que son propre profil** (voter « self » : `candidate.person.user === currentUser`).

| Action | Effet | Zone | Sync Jarvi |
| --- | --- | --- | --- |
| **Consulter le profil pro** | CV, expériences, formations, compétences, langues, secteur/fonction | profil pro (Jarvi maître) | descend par ingestion |
| **Définir le projet de retour** ★ | `returnHorizon` (M3/M6/Y1/THINKING) | différenciant | local showcase-recruiter-webapp-saas |
| **Définir ses prétentions** | `salaryExpected`, `salaryMin`, `geoMobility` | différenciant | local showcase-recruiter-webapp-saas |
| **Définir sa disponibilité** | `availability` (IMMEDIATE/NOTICE_1M/NOTICE_3M) | différenciant | local showcase-recruiter-webapp-saas |
| **Déclarer une présence Maroc** ★ | `PresencePeriod` (startDate, endDate, city) | différenciant | local showcase-recruiter-webapp-saas |
| **Gérer sa visibilité** | `visibility` (VISIBLE/HIDDEN/RESTRICTED/PREMIUM_ONLY) | différenciant | filtre serveur (recherche/masquage) |
| **Consulter son activité** | compteurs `ProfileView` (vues, dévoilements) | local | — |
| **Obtenir le statut Star** | entretien réalisé par showcase-recruiter-webapp-saas + compte rendu associé | validation interne | — |

**Règles transverses**
- **Self-only** : toute écriture passe par l'**API Symfony** et un voter restreignant au profil du
  candidat connecté. Pas d'accès croisé entre candidats.
- **Consentement RGPD** : `visibility` et le snapshot d'un profil non inscrit relèvent de la base
  légale / rétention — cf. item RGPD du [todo.md](./todo.md) et tâche 018.
- **Pas de sync montante des données showcase-recruiter-webapp-saas** : consentement, visibilité, différenciants et présence
  restent locaux. Seule la création initiale d'un profil Jarvi absent est autorisée.

## 3. Carte de navigation — V1

```
candidat.showcase-recruiter-webapp-saas.example
│
├─ ENTRÉE / AUTH
│   ├─ /login                   Connexion
│   └─ /onboarding              Magic Link, rattachement profil, consentement            (task 033)
│
├─ /  (HOME = TABLEAU DE BORD)  Complétude profil · activité récente · prochaines présences Maroc
│
├─ /profil                      Profil pro en lecture seule depuis réplica Jarvi         (task 020)
│   ├─ /experiences             Expériences (Jarvi maître)
│   ├─ /formations              Formations  (Jarvi maître)
│   └─ /cv                      CV courant unique
│
├─ /projet                      ★ Différenciants showcase-recruiter-webapp-saas (maître)                       (task 016/017)
│   ├─ retour                   returnHorizon
│   ├─ pretentions              salaryExpected · salaryMin · geoMobility
│   ├─ disponibilite            availability
│   └─ presences                ★ PresencePeriod — « quand je serai au Maroc »           (task 017)
│
├─ /visibilite                  Contrôle d'exposition + consentement (RGPD)              (task 018)
│
├─ /activite                    Vues / dévoilements de mon profil                        (task 019)
│
├─ /compte                      Identité, e-mail, mot de passe, suppression (RGPD)
│
└─ 🔔 cloche / inbox            Notifications reçues (header, transversal)               (task 019)
```

**Portées & accès**

| Zone | Portée | Accès |
| --- | --- | --- |
| `/profil`, `/projet`, `/visibilite`, `/activite`, `/compte` | individuel | candidat propriétaire (voter self) |
| création profil Jarvi absent | clé privée Jarvi | passe par `api/`, uniquement à l'inscription si aucun match |
| différenciants showcase-recruiter-webapp-saas | base showcase-recruiter-webapp-saas | local uniquement |

## 4. V2 / différé

```
/options            options payantes candidat (CandidateOption) → V2, dépend décisions produit + Stripe/billing
/accompagnement     coaching, optimisation CV, prépa entretien
candidature assistée (1 clic, diffusion ciblée)
```

→ détail et décisions ouvertes : [todo.md](./todo.md).
