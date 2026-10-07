# showcase-recruiter-webapp-saas — Carte des routes de l'API

> Inventaire des endpoints exposés par l'`api/` (Symfony + API Platform). La source de vérité
> reste l'OpenAPI généré (`api/openapi.json`, exposé sur `/v1/docs`). Items par uuid, réponses
> plates, JWT bearer. Cf. [data-model.md](./data-model.md) et
> [api-conventions.md](./api-conventions.md).
>
> Légende rôle : 🌐 public · 🧑‍💼 recruteur · 🧑 candidat · 🛠️ admin · 🔒 self (l'utilisateur lui-même)
>
> **« actif »** = recruteur dont l'entreprise n'a **pas été refusée**. Une entreprise encore en
> revue travaille normalement : la vérification est un contrôle, pas une porte. Un refus suspend ses
> recruteurs — connexion refusée, et session en cours rendue inerte.

## Système & contrat

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/docs` · `/v1/docs.json` | 🌐 | OpenAPI / Swagger |
| GET | `/v1/health` | 🌐 | statut + **version** déployée |
| GET | `/v1/config` | 🌐 | **valeurs métier** (coût dévoilé, crédits offerts, locales, locale par défaut, pagination) |
| GET | `/v1/validation-rules/{form}` | 🌐 | contraintes Symfony Validator exposées pour les formulaires webapp |

## Auth & compte courant

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| POST | `/v1/auth/login` | 🌐 | `{email,password}` → `{token, refreshToken}`. Un compte suspendu répond `401` avec le message `account_suspended`, distinct d'`Invalid credentials.` : l'interface doit les séparer, sinon elle envoie réinitialiser un mot de passe qui n'est pas en cause |
| POST | `/v1/auth/refresh` | 🌐 | renouvellement JWT |
| POST | `/v1/auth/magic-link/request` | 🌐 | demande un Magic Link déclarant par e-mail |
| POST | `/v1/auth/magic-link/consume` | 🌐 | consomme le Magic Link et retourne un JWT pour créer l'entreprise en revue |
| POST | `/v1/auth/password-setup/consume` | 🌐 | définit le mot de passe via token reçu après validation entreprise |
| POST | `/v1/auth/password-reset/request` | 🌐 | demande un lien de réinitialisation de mot de passe |
| POST | `/v1/auth/password-reset/consume` | 🌐 | réinitialise le mot de passe via token à usage unique |
| GET | `/v1/me` | 🔒 | utilisateur courant : person, rôles, contexte(s) candidat/recruteur |
| PUT | `/v1/me/password` | 🔒 | changement de mot de passe |

## Candidat

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| POST | `/v1/candidates/onboarding` | 🌐 | inscription candidat et création du profil |
| GET | `/v1/candidates/{uuid}` | 🧑‍💼/🔒 | lecture ; identité, employeur et LinkedIn **en clair**, **coordonnées masquées par canal** tant qu'il n'est pas dévoilé. **Laisse une trace** (`ProfileView`) quand l'appelant est un recruteur — une par recruteur, candidat et jour ; cf. [data-model.md](./data-model.md#profileview-activité--analytics) |
| GET/PUT | `/v1/candidates/{uuid}/differentiators` | 🔒 | ★ différenciants (dispo, horizon, projet, prétentions, mobilité/pied-à-terre) ; maîtres showcase-recruiter-webapp-saas locaux |
| GET/POST | `/v1/candidates/{uuid}/presence-periods` | 🔒 | ★ présence Maroc (maître showcase-recruiter-webapp-saas local) ; item en `PUT`/`DELETE` sur `/{id}` |
| GET/PUT | `/v1/candidates/{uuid}/visibility` | 🔒 | visible/masqué/restreint/premium-only |
| GET/PUT | `/v1/candidates/{uuid}/consent` | 🔒 | consentement d'exposition du profil au vivier |
| GET | `/v1/candidates/{uuid}/activity` | 🔒 | self-only ; compteurs agrégés `profileViews`/`reveals`/`interestedCompanies` ; identité entreprise **jamais exposée** |

> **Profil pro = réplica lecture seule (Jarvi maître).** L'API n'expose pas d'endpoint showcase-recruiter-webapp-saas pour
> éditer expériences / formations / compétences / langues / CV : ces données descendent de Jarvi par
> ingestion (`CandidateJarvi`) et sont exposées en lecture via `GET /v1/candidates/{uuid}`.
> showcase-recruiter-webapp-saas n'édite **que** ses différenciants (maîtres) et `PresencePeriod`, sans les pousser vers
> Jarvi. À l'inscription, si aucun profil Jarvi n'existe, l'API crée un profil Jarvi minimal pour
> obtenir l'ancre, puis la fraîcheur redevient descendante.

## Entreprise & multi-comptes

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| POST | `/v1/organizations` | 🔒 | création en `EN_REVUE` ; le créateur devient `ROLE_COMPANY_OWNER`, et **le lien de définition du mot de passe part aussitôt** — l'accès n'attend aucune décision |
| GET/PUT | `/v1/organizations/{uuid}` | 🧑‍💼 | fiche entreprise |
| POST | `/v1/organizations/{uuid}/validate` | 🛠️ admin | passe l'entreprise en `VALIDEE`, annonce la vérification au déclarant (e-mail sans lien : rien à faire en le lisant) et synchronise la company Jarvi. N'ouvre aucun accès — il l'était déjà |
| POST | `/v1/organizations/{uuid}/reject` | 🛠️ admin | passe l'entreprise en `REFUSE`, ce qui **suspend ses recruteurs** |
| GET | `/v1/organizations/{uuid}/members` | 🧑‍💼 | recruteurs |
| DELETE | `/v1/organizations/{uuid}/members/{recruiterId}` | `ROLE_COMPANY_OWNER` | retirer un recruteur |
| POST | `/v1/organizations/{uuid}/invitations` | `ROLE_COMPANY_OWNER` | inviter un collègue `{email, role}` + envoi e-mail |
| GET | `/v1/organizations/{uuid}/invitations` | `ROLE_COMPANY_OWNER` | invitations en cours |
| GET | `/v1/invitations/{token}` | 🌐 | contexte public d'acceptation |
| POST | `/v1/invitations/{token}/accept` | 🌐 | acceptation (création du compte membre) |

## Crédits

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{uuid}/credit-wallet` | 🧑‍💼 actif | solde courant |
| GET | `/v1/organizations/{uuid}/credit-transactions` | 🧑‍💼 actif | journal |

## Recherche, dévoilés & collaboration (cœur recruteur)

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/search/candidates` | 🧑‍💼 actif | recherche **simple / filtres** sur la réplique locale (`?q` ou `?q[]` répété — les mots-clés se relient en **OU** —, filtres, pagination) ; `?view=full` renvoie le profil détaillé au lieu de la carte. ⚠️ **`experienceMin` change de sens selon le contexte** — voir sous le tableau |
| GET | `/v1/suggestions` | 🧑‍💼 actif | auto-complétion des champs de recherche (`?type=JOB\|SKILL&q=…&limit=`) ; **postes** = dictionnaire de concepts, **compétences** = ce que portent les profils. Chaque proposition ne porte que son **terme** : le nombre de profils reste en base, où il **classe** (du plus porté au moins porté) et où il écarte ce que personne n'exerce — **aucune ne ramène zéro**. Le terme est cherché au **début de n'importe quel mot** ; s'il ne trouve rien, un second passage **tolère les fautes** (≥ 5 lettres). Sans `pg_trgm` / `unaccent`, dégrade sans tomber |
| POST | `/v1/organizations/{uuid}/revealed-profiles` | 🧑‍💼 actif | **dévoiler** `{candidateId}` pour un `type` (`EMAIL`, `PHONE`, `RESUME`, `INTERVIEW_REPORT`) → débit 1 crédit **par accès** (atomique) |
| GET | `/v1/organizations/{uuid}/revealed-profiles` | 🧑‍💼 actif | « déjà dévoilés » (partagés) |
| GET/POST | `/v1/organizations/{uuid}/favorites` | 🧑‍💼 actif | favoris partagés ; retrait par `DELETE /favorites/{candidateId}` |
| GET/POST | `/v1/searches` | 🧑‍💼 actif | mes recherches **et celles que l'équipe a partagées**, non paginé ; `PATCH`/`DELETE` sur `/{id}`, duplication sur `/{id}/duplicate`. L'identifiant est un uuid, et la route l'exige — sans quoi `/searches/workspace` serait lue comme une recherche |
| GET | `/v1/searches/{id}/results` | 🧑‍💼 actif | le jeu **matérialisé** d'une recherche, servi **entier et paginé** (`?page`, `?perPage`) : une recherche se parcourt jusqu'à son dernier profil. La **première page de la pile** porte en plus la liste ordonnée complète (`resultIds`) et l'état de travail — retenus, écartés, vus. Une page au-delà de la fin revient vide, jamais en erreur. `?scope=favorites\|dismissed` change de point de vue |
| GET | `/v1/searches/workspace` | 🧑‍💼 actif | **l'état de travail** : toutes les recherches **ouvertes** du recruteur en un appel — pour chacune, cinq listes d'identifiants **entières** : le jeu, les retenus (dans l'ordre du jeu), les écartés, les vus, les nouveaux. **Ni fiche, ni explication** : elles se demandent ailleurs et à un autre rythme. Aucune pagination, la clôture automatique des recherches inactives en est la borne |
| GET | `/v1/searches/profiles` | 🧑‍💼 actif | **les fiches par lot**, depuis des recherches nommées : `?ids=A,B,C` et `?take=` (défaut 30, plafond 100, au plus 50 recherches). Rend les premiers profils de chacune, **dédoublonnés** entre elles — un profil qui sert trois de ces recherches ne voyage qu'une fois. **Ni ordre, ni appartenance, ni explication** : l'ordre vient de `/searches/workspace`. Les bornes **réduisent** au lieu de refuser ; un identifiant inconnu ou hors périmètre est ignoré, et rien ne distingue les deux — c'est ce qui empêche de deviner ce qui existe |
| GET | `/v1/searches/{id}/explanations` | 🧑‍💼 actif | **les explications, page par page** : pourquoi chaque profil répond à cette recherche — le critère, le libellé **du profil** qui le satisfait, et l'endroit où il a été vu. `?page=` et `?perPage=` (défaut 30, plafond 200) ; l'ordre est celui du jeu servi par `/searches/workspace`, et `total` porte le jeu entier. **Calculées, jamais stockées** — les garder créerait une donnée à invalider à chaque changement de critère et à chaque réimport. Un profil dont aucun critère ne peut rien énoncer n'a pas d'entrée ; une page au-delà de la fin rend une collection vide. Même garde que la recherche, même 404 |
| GET/POST | `/v1/searches/{id}/favorites` | 🧑‍💼 actif | le registre des mises de côté — qui a retenu qui, et quand ; retrait par `DELETE /favorites/{candidateId}` |
| POST | `/v1/searches/{id}/reviews` | 🧑‍💼 actif | trancher un lot : `SEEN`, `DISMISS`, `RESTORE` |
| POST | `/v1/searches/{id}/export` | 🧑‍💼 actif | export CSV d'une sélection, réservé à l'auteur, accusé RGPD exigé |
| GET | `/v1/organizations/{uuid}/assignments` | 🧑‍💼 actif | attribution d'un candidat dévoilé à un recruteur de l'entreprise |
| PUT/DELETE | `/v1/organizations/{uuid}/assignments/{candidateId}` | 🧑‍💼 actif | assigner / désassigner |

### `experienceMin` compte deux choses différentes

Le paramètre garde sa forme — un nombre d'**années** —, mais pas son sens :

| Contexte | Ce qui est compté |
| --- | --- |
| `job` posé, et le dictionnaire connaît le terme | les mois exercés **dans ce métier**, sur la correspondance même qui fait entrer le candidat |
| `job` posé, terme **inconnu** du dictionnaire | la **carrière entière** — le repli cherche l'intitulé écrit, qui ne porte aucune date |
| `job` absent | la **carrière entière** |

C'est voulu : « architecte, 5 ans » demande cinq ans d'architecture, pas cinq ans de carrière chez
quelqu'un qui vient d'y arriver.

⚠️ **La durée par métier ne totalise que ce que le dictionnaire a su rattacher** — 73 % des postes du
vivier. Un candidat dont un poste porte un intitulé inconnu voit ces mois-là manquer à son compte :
le filtre est **strictement plus sévère** que la réalité. Il n'ajoute jamais un profil qui ne
convient pas, il en écarte qui conviendraient.

## Activité

`ProfileView` est une table interne d'activité/analytics. L'activité candidat exposée est la lecture
agrégée `GET /v1/candidates/{uuid}/activity`, self-only.

## Supervision admin

> Section **non exhaustive** : les autres routes `/v1/admin/*` (vivier, candidats, utilisateurs,
> référentiels, Jarvi) existent depuis longtemps sans figurer ici. Les inventorier est un travail à
> part, pas un à-côté du prochain ajout.

| Méthode | Route | Rôle | Notes |
| --- | --- | --- | --- |
| GET | `/v1/admin/organizations/{uuid}` | 🛠️ admin | **la fiche d'une entreprise** : identité, statut de validation, **solde de crédits**, **abonnement** (plan, état, fin de période — `null` donc absent quand il n'y en a pas), membres et rôles, et le **journal des mouvements** du plus récent au plus ancien, chacun avec son montant signé et le **solde après opération** — c'est lui qui rend le journal auto-portant. Borné par `?transactions` (défaut 20, ≤ 100). Sans portefeuille : solde à **0** et journal vide, jamais une erreur. **Lecture seule** — créditer une entreprise à la main est un geste commercial, non cadré |
| GET | `/v1/admin/activity` | 🛠️ admin | **le fil des visites de profil**, toutes organisations confondues, du plus récent au plus ancien, paginé (`?page`, `?perPage` ≤ 100). Chaque ligne porte la date, le recruteur (nom et adresse), son organisation, le candidat **non masqué** et `revealed` — cette organisation a-t-elle débloqué ce profil. C'est cet écart qui porte la valeur du fil : regardé souvent, jamais débloqué. La trace est écrite par `GET /v1/candidates/{uuid}` (cf. [data-model.md](./data-model.md#profileview-activité--analytics)). **Un compte fermé garde ses lignes** : la suppression douce est levée le temps de la lecture, l'administration voit le supprimé |
| GET | `/v1/admin/searches` | 🛠️ admin | **les recherches larges** : celles qui dépassent le seuil recommandé, toutes organisations confondues. Rien n'est caché au recruteur — le jeu se sert entier — mais trier plusieurs centaines de profils un par un ne se mène pas jusqu'au bout. Chaque ligne porte le nombre de correspondances et le seuil, plus l'organisation, le propriétaire, les critères, l'état et la dernière activité. Trié du plus large au plus étroit, paginé (`?page`, `?perPage` ≤ 100). **Le seuil est celui de la recommandation**, pas un réglage de cet écran |
| GET | `/v1/admin/vivier/gaps/{metric}` | 🛠️ admin | **pourquoi** une qualification manque, profil par profil. `{metric}` ∈ `experienceYears`, `currentRoleMonths`, `educationLevel`, `jarviUpdatedAt` — inconnue → **404**, jamais une liste vide. Rend `breakdown` (effectif par motif, **toujours le manque entier**), `items` paginés (`?page`, `?perPage` ≤ 100) et le filtre `?reason=` — un motif étranger à la métrique → **404**. Le motif est **recalculé à la demande**, il n'est stocké nulle part ; cf. [profile-processing.md](./profile-processing.md#pourquoi-une-qualification-manque) |

## Éditorial — landing

Pas de route dédiée : la mise en avant « profils stars » est dérivée du statut candidat
(`Candidate.starInterviewedAt`) **côté front**.
