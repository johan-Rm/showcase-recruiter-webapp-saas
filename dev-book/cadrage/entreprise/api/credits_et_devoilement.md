# Crédits & dévoilement (cœur sensible)

> Règles serveur du **système de crédits** et du **dévoilement d'un profil** de l'espace entreprise.
> Cœur sensible : toute écriture passe par le Domain, jamais par le front. Invariants transverses →
> [data-integrity.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md) ; entités →
> [data-model.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) ; produit →
> [vue_d_ensemble.md](../vue_d_ensemble.md) §2.
>
> **Statut** : dévoilement **unitaire livré** (task 005) ; **dévoilement groupé ⏳ V1** (à livrer).

## Contexte

Un profil du vivier est **masqué** par défaut (cf. [recherche_et_masquage.md](./recherche_et_masquage.md)).
Le **dévoilement** débloque l'identité complète, les coordonnées, le CV et le compte rendu d'entretien,
en échange d'un **crédit**. Les crédits, les dévoilés et le solde sont **partagés au niveau
`Organization`** (communs à tous les recruteurs) ; on trace toujours **quel recruteur** a agi.

## Modèle & constantes

| Élément | Valeur | Source |
| --- | --- | --- |
| `CreditWallet` | 1-1 `Organization`, porte le `balance` | entité |
| `CreditTransaction` | journal immuable (type, montant, solde après, motif, recruteur, dévoilé lié) | entité |
| `RevealedProfile` | dévoilé `(organization, candidate, revealedBy)` — **unicité `(organization, candidate)`** | entité |
| Crédits offerts à la création | **2** (`WELCOME_CREDITS`) | `CreditPolicy` |
| Coût d'un dévoilé | **1** (`REVEAL_COST`) | `CreditPolicy` |

> Les constantes crédits ont **une source unique** (`CreditPolicy`) exposée via `/v1/config` — pas de
> valeur en dur côté front (cf. [data-contracts.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)).

## Routes

| Méthode | Route | Rôle | Effet |
| --- | --- | --- | --- |
| `GET` | `/v1/organizations/{organizationId}/credit-wallet` | recruteur (`ACCESS`) | solde courant |
| `GET` | `/v1/organizations/{organizationId}/credit-transactions` | recruteur (`ACCESS`) | journal de consommation |
| `GET` | `/v1/organizations/{organizationId}/revealed-profiles` | recruteur (`ACCESS`) | liste des dévoilés (partagée) |
| `POST` | `/v1/organizations/{organizationId}/revealed-profiles` | recruteur (`ACCESS`) | **dévoile** un candidat (`{ candidateId }`) — débit 1 crédit |

> L'achat / la recharge de crédits (Stripe Europe / CMI Maroc) et l'allocation nominative aux
> recruteurs sont réservés au `ROLE_COMPANY_OWNER` → à cadrer dans `achat_credits.md` (P2, cf.
> [todo.md](../../todo.md) § billing).

## Invariants du dévoilement unitaire (garantis par `Domain\Credit\ProfileRevealer`)

- **Atomique** : débit du wallet + `CreditTransaction` + `RevealedProfile` dans **une seule
  transaction**.
- **Sérialisé** : **verrou pessimiste** (`PESSIMISTIC_WRITE`) sur le wallet → pas de double-dépense
  concurrente (wallet partagé entreprise).
- **Idempotent** : un candidat **déjà dévoilé** renvoie l'existant **sans nouveau débit** (unicité
  `(organization, candidate)`).
- **Jamais à vide** : un accès dont le profil **n'a rien à servir** n'est ni facturé ni enregistré →
  **`422`** avec un message explicite (task `API-063`). Voir la règle de présence ci-dessous.
- **Solde ≥ 0** : refus si `balance < REVEAL_COST` → **`402 Payment Required`** (aucun débit),
  backstop `CHECK` en base.
- **Traçabilité** : la `CreditTransaction` enregistre le **recruteur** auteur et le dévoilé lié.

### Ce qu'un canal doit avoir pour être facturé

**Une seule règle, deux lecteurs** : le serveur s'en sert pour refuser le débit, l'écran pour
éteindre un canal avant le clic. Elle est écrite ici, et implémentée une fois — dans
`Domain\Candidate\RevealableContent`, dont le dévoilement **et** la carte candidat dépendent. Deux
implémentations divergeraient au premier changement, et aucun test pris isolément ne le verrait.

| Canal | Ce qui doit exister |
| --- | --- |
| `EMAIL` | une adresse — celle déclarée par la personne, **ou** à défaut celle du réplica Jarvi |
| `PHONE` | un numéro, selon la même priorité |
| `RESUME` | au moins une entrée de CV **portant un identifiant de fichier** — une entrée sans fichier n'est pas téléchargeable |
| `INTERVIEW_REPORT` | le **texte** du compte rendu, non vide — « rencontré » ne suffit pas, un entretien peut n'avoir jamais été rédigé |

**Rien n'est enregistré pour un canal vide**, pas même une ligne gratuite : elle rendrait le profil
« déjà dévoilé », et la donnée arrivée plus tard par une synchro serait servie sans contrepartie.

Ces quatre présences **descendent sur la carte de recherche et sur la fiche** — `hasEmail`,
`hasPhone`, `hasResume`, `hasReport` (task `API-064`) —, ce qui permet à l'écran d'éteindre un canal
avant le clic plutôt que d'annoncer un coût que le serveur refusera. Un profil **sans réplica Jarvi**,
jamais synchronisé, les reçoit toutes à faux : rien à servir n'est pas une erreur.

## Dévoilement groupé (sélection multiple) — ⏳ V1

Extension du cœur sensible à un lot de candidats. Décisions retenues (à implémenter) :

- **Coût recalculé serveur** : les candidats **déjà dévoilés** du lot sont **gratuits** (idempotence).
- **Deux exclusions qui se cumulent** : ce qui est déjà payé, et ce qui n'a **rien à servir** sur le
  canal demandé. Le solde se vérifie sur le coût **après** exclusion, si bien qu'un lot qui ne
  passait pas peut désormais passer. Le lot **rend ce qui a été dévoilé, pas ce qui a été demandé** :
  il peut être plus court que la sélection. Un lot entièrement vide **aboutit en succès**, sans débit
  ni ligne — comme un lot entièrement déjà payé.
- **Tout-ou-rien** : si le solde est insuffisant pour le lot → **`402`, aucun débit**.
- **Atomicité du lot** : une **transaction unique**, verrou pessimiste sur le wallet partagé.
- **Endpoint batch** : `{ candidateIds: [...] }` côté serveur — **jamais** N appels client.
- **UX de confirmation** : décomposition du coût recalculée au clic (wallet partagé).

**Deux points d'entrée** dans l'UI (cf. [ui/vue_la_table.md](../ui/vue_la_table.md)) :

| Entrée | Lot |
| --- | --- |
| **Menu de la sélection** → *Dévoiler N profils* | les profils cochés |
| **Menu d'un tab d'état** → *Tout dévoiler* | **tous les non-dévoilés du sous-ensemble** (Favoris, Tous…) |

### Garde-fous de « Tout dévoiler » (décision 2026-07-12)

Sur un tab « Tous » d'une recherche large, un clic peut viser des centaines de profils. Le
**tout-ou-rien protège d'un solde insuffisant, pas d'un solde suffisant dépensé par erreur**. Donc :

- **Plafond de lot côté serveur** : au-delà de N candidats, l'API **refuse** le lot (valeur à fixer,
  exposée via `/v1/config` comme les autres constantes de `CreditPolicy` — jamais en dur côté front).
- **Double confirmation côté UI** au-delà d'un seuil : le recruteur doit poser un geste explicite
  (retaper le nombre de crédits), pas seulement valider une modale.

## Modale de confirmation

Contenu attendu (le mockup fait foi) :

```text
Dévoiler 12 profils
Le dévoilement est partagé : ces profils seront visibles par toute l'entreprise.

  Déjà dévoilés (gratuits)      3          ← n'apparaît que s'il y en a
  Coût du lot                   9 crédits
  Solde entreprise            844
  Solde après dévoilement     835          ← seulement si le solde suffit

  [ Annuler ]            [ Confirmer · 9 crédits ]
```

Solde insuffisant → alerte **tout-ou-rien** (« aucun profil ne sera dévoilé ») + confirmation
**désactivée**. Lot entièrement déjà dévoilé → `OK (aucun débit)`.

## Imputation par recherche

Chaque **recherche** compte les **crédits qu'elle a consommés**. C'est un **compteur d'imputation**, pas
un budget :

- le **solde** reste unique et **partagé `Organization`** — il n'y a **pas** d'enveloppe par recherche ;
- le débit est **atomique sur le wallet partagé**, quelle que soit la recherche d'origine ;
- ⏳ **à implémenter** : rattacher la `CreditTransaction` à la **recherche** courante (en plus du
  recruteur), pour alimenter ce compteur.

## Présentation du solde — un seul vocabulaire

**Décision 2026-07-12 : partout, le SOLDE RESTANT.** C'est la seule question que le recruteur se pose
avant de cliquer « Dévoiler ».

| Surface | Affiche |
| --- | --- |
| **Chip topbar**, **menu compte** | **restants** (`844`) |
| **Modale crédits** | consommés · **restants** · total + jauge + **répartition par membre** |

La **consommation** ne disparaît pas : elle vit dans la **modale**, où l'on vient précisément la
chercher. La **répartition par membre** (qui a dévoilé combien) s'appuie sur la traçabilité déjà en
place (`CreditTransaction.recruteur`) — ⏳ endpoint d'agrégation à exposer.

## Ce qui se débloque au dévoilement

Cf. le tableau de masquage dans [recherche_et_masquage.md](./recherche_et_masquage.md) : prénom et nom
complets, **photo de profil**, e-mail, téléphone, LinkedIn, WhatsApp, CV (URL storage Jarvi) et
**compte rendu d'entretien** (recruteur uniquement). Avant dévoilement, ces champs sont masqués ou
`null`.

> La photo n'est pas copiée chez showcase-recruiter-webapp-saas : le dévoilement libère l'**URL publique** du storage
> Jarvi, que le front affiche à distance. Elle n'est donc disponible que pour les profils dont
> Jarvi détient une photo.

**Le dévoilement conditionne aussi trois actions** : **Shortlister**, **Exporter** et **Télécharger**
(CV, compte rendu) exigent un profil dévoilé (`shortlist ⊆ dévoilés`). **Favoriser**, non.
