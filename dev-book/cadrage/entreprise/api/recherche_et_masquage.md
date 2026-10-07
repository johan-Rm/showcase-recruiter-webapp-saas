# Recherche & masquage des profils

> Règles serveur de la **recherche du vivier** et du **masquage** d'un profil tant qu'il n'est pas
> dévoilé. Le masquage est appliqué **côté serveur** (jamais de donnée sensible envoyée au front avant
> dévoilement). Dévoilement & crédits → [credits_et_devoilement.md](./credits_et_devoilement.md) ;
> ingestion du vivier → [import_profils_jarvi.md](./import_profils_jarvi.md) ; produit →
> [vue_d_ensemble.md](../vue_d_ensemble.md) §2.
>
> **Statut** : recherche & lecture avec masquage **livrés** (task 006) ; recherche réplica
> **classique (FTS + filtres) ⏳ V1** (task 014, réplica via task 020) ; **recherche IA (MCP) = V2**.
> Extension du masquage (prénom → initiale, photo, WhatsApp) : **livrée** — photo et identifiant
> WhatsApp sont ingérés dans la projection `CandidateJarvi` et ne sortent qu'au dévoilement
> (cf. [import_profils_jarvi.md](./import_profils_jarvi.md)).

## Contexte

Le vivier entreprise est une **réplica locale** (PostgreSQL) alimentée par l'ingestion Jarvi
(task 020). **La recherche ne tape plus Jarvi en direct** : elle interroge la réplica. Chaque profil
est **masqué** par défaut ; l'identité et les coordonnées ne se débloquent qu'au **dévoilement**
(1 crédit, cf. [credits_et_devoilement.md](./credits_et_devoilement.md)).

## Routes

| Méthode | Route | Rôle | Effet |
| --- | --- | --- | --- |
| `GET` | `/v1/search/candidates` | recruteur (`ACCESS`) | recherche vivier (mots-clés + filtres) → **cartes masquées** |
| `GET` | `/v1/candidates/{id}` | recruteur (`ACCESS`) | fiche détaillée — **masquée** si non dévoilée pour l'entreprise |

## Recherche (V1)

- **Source = réplica locale** : requête **Postgres FTS + filtres**, jamais un appel Jarvi en direct
  (cf. [jarvi-integration.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md)).
  Choix moteur **tranché (2026-07-06) → Postgres FTS en V1** (cf. § Moteur & passage à l'échelle).
- **Hors périmètre exclu** : les profils sortis du périmètre Jarvi sont **désactivés** et n'apparaissent
  pas dans les résultats (cf. réconciliation, task 020).
- **Consentement / visibilité** : le filtre de `visibility` candidat (self-service + trace de
  consentement, task 018) est appliqué **côté serveur** — un profil `HIDDEN` n'est jamais listé.
- Critères de filtrage détaillés : [criteres_de_filtrage_profils.md](../criteres_de_filtrage_profils.md).

## Moteur & passage à l'échelle — décision (benchmark 2026-07-06)

Tranche le point ouvert « moteur d'index local » (cf. [todo.md](../../todo.md)) : **recherche
serveur** sur la réplica Postgres, moteur **FTS Postgres (`tsvector` GIN + `pg_trgm`) en V1**. La
recherche **ne descend jamais le vivier dans le navigateur**.

**Pourquoi pas une recherche 100 % côté client** (dump chargé dans le front) — benchmark sur le dump
réel (1 626 profils, 6,1 Mio) répliqué ×20 (~32 500 profils, cible visée) :

| | 1 626 profils | ~32 500 (×20) |
| --- | --- | --- |
| Recherche via index inversé en RAM | ~0,1 µs | ~0,1 µs (plat, ≈ O(1)) |
| Build de l'index (au chargement) | ~50 ms | **~1,5 s** (fige l'UI) |
| RAM navigateur | ~30 Mio | **~150 Mio** |
| Poids réseau (gzip) | ~0,6 Mio | **~12 Mio** |

- La **recherche** elle-même reste instantanée à toute échelle — ce n'est **pas** le facteur limitant.
- Ce qui casse côté client, c'est **poids réseau + RAM + temps de build** : rédhibitoire en
  **mobile-first / PWA** dès quelques dizaines de milliers de profils.
- Surtout, descendre le dump exposerait la **donnée non dévoilée** côté client → contraire au
  **masquage serveur** (cf. § Invariants).

**Conséquences retenues :**

- **V1** : moteur **Postgres FTS + `pg_trgm`** (préfixe + tolérance aux fautes), infra déjà en place
  (réplica, task 020) — KISS, aucun service supplémentaire. Requête indexée sur ~30 k lignes = quelques ms.
- **Front (Nuxt)** : barre + filtres côté UI, mais **résultats servis par l'API, paginés** (≈ 20/page,
  scroll infini) et **debouncés** ; le front ne reçoit que des **cartes masquées** (→ task `043`).
- **PWA** : cache l'**app-shell** et les **résultats / consultations récents** ; **jamais la base
  entière hors-ligne** (PWA = V2, cf. [todo.md](../../todo.md)).
- **Escape hatch** : bascule **Meilisearch / Typesense** uniquement si l'UX exige un fuzzy/instantané
  au-delà de ce que Postgres FTS rend confortablement.

## Masquage — champ par champ (`Domain` / `CandidateMapper`)

Un profil non dévoilé pour l'entreprise expose une **carte / fiche qualifiante** mais **jamais**
l'identité complète ni les coordonnées.

| Champ | Non dévoilé | Dévoilé |
| --- | --- | --- |
| Prénom (`givenName`) | **initiale seule** (`A.`) | ✅ complet |
| Nom (`familyName`) | **initiale seule** (`D.`) | ✅ complet |
| E-mail / téléphone / LinkedIn | **`null`** | ✅ — déclarés par le candidat, sinon détenus par Jarvi |
| **`hasEmail` / `hasPhone` / `hasResume` / `hasReport`** | ✅ **booléens exposés** — cf. § Joignabilité | ✅ |
| WhatsApp (`whatsappNumber`) | **`null`** (l'identifiant Jarvi contient le numéro) | ✅ numéro E.164 |
| Photo de profil (`photoUrl`) | **`null`** (un visage désigne) | ✅ URL publique du storage Jarvi |
| **Employeurs** (`workExperiences[].companyName`) | **`null`** — cf. § Ré-identification | ✅ |
| Intitulés de poste & périodes (`title`, dates) | ✅ visible | ✅ |
| **Années d'expérience** (`experienceYears`) | ✅ visible — **agrégat**, pas une identité | ✅ |
| CV (binaire) | ❌ | ✅ via URL storage Jarvi (`…/v1/files/<fileId>`) |
| **Compte rendu d'entretien** (`starInterviewReport`) | **`null`** | ✅ (**recruteur uniquement**) |
| Accroche (`headline`), ville, pays | ✅ visible | ✅ |
| Secteur / fonction souhaités | ✅ visible | ✅ |
| Dispo, horizon de retour, **pied-à-terre** | ✅ visible | ✅ |
| **Mobilité au Maroc** (tout le Maroc **ou villes cibles**) | ✅ visible | ✅ |
| Compétences, langues | ✅ visible | ✅ |
| Présence au Maroc (`PresencePeriod`) | ✅ visible | ✅ |
| Drapeau « vérifié » (`starInterviewedAt`) | ✅ booléen | ✅ |
| **Ouvert aux opportunités** *(nouveau)* | ✅ booléen | ✅ |

> **Photo.** Tant que le profil est masqué, la vignette de la Table est un **placeholder flouté** —
> aucune URL de photo n'est envoyée au front. Au dévoilement, l'API sert l'**URL publique** du
> storage Jarvi : le binaire n'est jamais copié côté showcase-recruiter-webapp-saas, le front affiche l'image à distance
> et la dimensionne (`?w=…&h=…&q=…`). Cette URL est accessible **sans authentification** : elle vaut
> identité, au même titre qu'un e-mail. Cf. [[showcase-recruiter-webapp-saas-masquage-etendu]].

## Ré-identification — la ligne de partage (décision 2026-07-13)

Le masquage ne protège pas seulement les **coordonnées** : il protège l'**identité**. Or un nom propre
suffit souvent à la reconstituer.

> « Développeur Symfony senior · **Atlas Group** · Lyon · 11 ans » : trois champs anodins, et la
> personne se retrouve sur LinkedIn en une recherche.

D'où la règle :

| | Exemple | Avant dévoilement |
| --- | --- | --- |
| **Agrégat** — décrit sans nommer | années d'expérience, nombre de postes, ville | ✅ **visible** |
| **Nom propre** — désigne | employeur, e-mail, téléphone, nom de famille | ❌ **masqué** |

**L'employeur est donc masqué**, actuel comme passés — les **intitulés de poste** et les **périodes**,
eux, restent visibles : ils qualifient le parcours sans le rattacher à quelqu'un. L'employeur fait
partie de **ce que le crédit achète**, et figure à ce titre dans le contrat de masquage affiché au
recruteur. ⏳ **À implémenter** — `CandidateMapper` sérialise aujourd'hui `workExperiences` en clair
quel que soit l'état de dévoilement (cf. task `086`).

## Joignabilité avant dévoilement — les quatre présences

**Nouveau contrat (2026-07-12).** La réponse expose, **avant dévoilement**, des booléens indiquant la
**présence** d'une donnée — **jamais sa valeur**.

**Pourquoi** : sans ce signal, un recruteur dépense un crédit sur un profil **injoignable** (ni e-mail
ni téléphone). C'est un défaut de conception du produit payant, pas un confort d'affichage.

**Un booléen par canal payant** (task `API-064`) : `hasEmail`, `hasPhone`, `hasResume`, `hasReport`.
Les deux derniers ont suivi, et pour la même raison : le CV et le compte rendu se paient aussi, et
l'écran annonçait leur coût sans savoir s'il y avait quelque chose derrière. Ce que chacun exige pour
être vrai est écrit une seule fois, avec la règle de facturation →
[credits_et_devoilement.md](credits_et_devoilement.md).

> **« Rencontré » n'est pas « compte rendu ».** Un profil peut avoir été reçu en entretien sans que le
> compte rendu ait été rédigé : `hasReport` se juge sur le texte, jamais sur l'indicateur d'entretien.

| Cas | Réponse API | Rendu UI |
| --- | --- | --- |
| Coordonnée présente, profil masqué | `hasEmail: true`, `email: null` | valeur **floutée** (placeholder) |
| Coordonnée absente, profil masqué | `hasEmail: false`, `email: null` | `—` |
| Profil dévoilé | `hasEmail: true`, `email: "…"` | valeur en clair |

> **Le flou est une convention d'UI appliquée à un placeholder.** L'API ne descend **jamais** la
> donnée masquée dans le navigateur — le front n'a rien à « dé-flouter » : il **re-fetch**.

> **D'où viennent les coordonnées.** Ce que le candidat a déclaré (`Person`) prime ; à défaut, on
> sert ce que Jarvi détient dans la réplica, aplati par priorité de source
> **`MANUAL` > `RESUME` > `LINKEDIN`** (cf. [import_profils_jarvi.md](./import_profils_jarvi.md)).
> Les booléens de joignabilité suivent cette même résolution — sans quoi ils annoncent
> « injoignable » des profils dont on détient l'e-mail ou le téléphone.

## Agrégats de facettes (compteurs)

La console affiche, pour **chaque option** de filtre, le nombre de profils qu'elle donnerait *tous les
autres filtres appliqués sauf le sien* (comptage facetté). L'API doit donc renvoyer, avec les
résultats, des **agrégats par facette** — pas seulement une liste. ⏳ **À cadrer** (task 014 / 035).

## Invariants

- **Masquage serveur** : les champs sensibles sont retirés/anonymisés **avant** sérialisation ; le
  front ne reçoit jamais une donnée masquée « cachée » côté client.
- **Cibler ≠ dévoiler** : **Favoriser** fonctionne sans dévoiler (profil masqué OK) ; **Shortlister,
  Exporter et Télécharger** exigent un profil **dévoilé** — la shortlist est réservée aux dévoilés
  (`shortlist ⊆ dévoilés`). Cf. [les_2_outils.md §4](../les_2_outils.md).
- **Dévoilement partagé** : l'état « dévoilé » est au niveau `Organization` — un candidat dévoilé par
  un recruteur l'est pour toute l'entreprise (cf. [credits_et_devoilement.md](./credits_et_devoilement.md)).
- **Listes personnelles** : **favoris** et **shortlist** sont portés par la **recherche** (donc par son
  auteur), **pas** par l'`Organization` — contrairement aux dévoilés et au solde de crédits.
- **Une recherche a un seul auteur** : privée par défaut, **explicitement partageable en lecture
  seule**. L'API doit donc appliquer **deux règles distinctes** :
  - **lecture** : refusée sur une recherche d'un autre recruteur **non partagée**, même au sein de la
    même `Organization` ;
  - **écriture** (critères, favoris, shortlist, suppression) : réservée à l'**auteur** — un
    destinataire d'une recherche partagée est **toujours en lecture seule**. Pas de co-édition.
- **Compte rendu d'entretien** : exposé **recruteur uniquement**, jamais côté candidat (décision
  2026-07-02).

## Dette ouverte par la refonte cockpit

| Sujet | Détail |
| --- | --- |
| `hasEmail` / `hasPhone` | à exposer (aplatissement multi-coordonnées Jarvi : MANUAL > RESUME > LINKEDIN) |
| **Ouvert aux opportunités** | champ à ingérer depuis Jarvi et à exposer |
| **Villes cibles au Maroc** | la mobilité s'exprime en villes cibles (cf. [criteres §7](../criteres_de_filtrage_profils.md)) |
| **Référentiel de postes** | alimente l'autocomplétion, point d'entrée de la recherche |
| **Agrégats de facettes** | compteurs par option |
| **Partage d'une recherche** | auteur + état de partage ; contrôle d'accès **inter-recruteurs** dans une même `Organization` — **lecture** pour le destinataire, **écriture** réservée à l'auteur |
| **Employeurs masqués** | `workExperiences[].companyName` → `null` avant dévoilement (ré-identification) — task `086` |
| **Années d'expérience** | absentes de tout DTO ; Jarvi ne les donne pas — à **dériver** des périodes de `positions` à l'ingestion (task `061`) puis à **exposer** (task `086`) |
| **`isNew` par carte** | segmente le tab « Nouveaux » de la Table — même sémantique que le `newCount` d'une recherche, à la maille de la ligne — task `086` |
