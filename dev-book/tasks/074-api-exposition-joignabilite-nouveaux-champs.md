# TASK 074 — API : joignabilité avant dévoilement et nouveaux champs exposés

- **Créée le** : 2026-07-12.
- **Statut** : 🟡 **partiellement livrée** (2026-07-13). **Faits** : `hasEmail`/`hasPhone`, retrait du
  salaire, masquage étendu (prénom → initiale). **Restent** : `openToOpportunities` et les **villes
  cibles** de mobilité — **bloqués en amont** : ni l'un ni l'autre n'est récupéré par la requête
  GraphQL (`JarviClient`), et leur **source Jarvi n'est pas identifiée** (custom field ? saisie showcase-recruiter-webapp-saas ?). À trancher avant d'écrire une ligne de code.
- **Type** : contrat d'exposition / masquage.
- **Dépend de** : tâche `006` (masquage), `020` (ingestion), `060` (import coordonnées).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` § Joignabilité
  - `dev-book/cadrage/entreprise/ui/vue_la_table.md` §3.1–§3.2
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` §7, §10

## Contexte

Le recruteur peut aujourd'hui **dépenser un crédit sur un profil injoignable** (ni e-mail ni
téléphone) : rien ne le lui dit avant de payer. C'est un défaut de conception d'un produit payant,
pas un confort d'affichage.

La refonte ajoute par ailleurs deux signaux au vivier (« ouvert aux opportunités », **villes cibles**
de mobilité) et **retire** le salaire.

## Objectif

Exposer, **avant dévoilement**, ce qu'il faut pour décider **sans** fuiter la donnée : la **présence**
d'une coordonnée, jamais sa valeur.

## Périmètre

- **`hasEmail` / `hasPhone`** : booléens exposés **avant** dévoilement.

  | Cas | Réponse |
  | --- | --- |
  | Coordonnée présente, profil masqué | `hasEmail: true`, `email: null` |
  | Coordonnée absente, profil masqué | `hasEmail: false`, `email: null` |
  | Profil dévoilé | `hasEmail: true`, `email: "…"` |

  Calculés après **aplatissement multi-coordonnées Jarvi** (priorité `MANUAL > RESUME > LINKEDIN`,
  téléphone en E.164 — cf. task `060`).
- **`openToOpportunities`** : booléen à ingérer depuis Jarvi et exposer. **Distinct de « vérifié »** :
  un profil peut être ouvert sans avoir été rencontré.
- **Mobilité au Maroc** : plus un booléen — `{ anywhere: true }` **ou** une **liste de villes cibles**.
  Modéliser la collection, l'ingérer, l'exposer, la rendre filtrable (filtrer sur une ville retourne
  *les mobiles partout* **OU** *ceux qui ciblent cette ville*).
- **Retraits de la sérialisation** : `salaryExpected`, `salaryMin` (notion **supprimée du produit**).
  Décision 2026-07-13 : le salaire n'est **ni exposé, ni filtrable** — mais la donnée reste
  **conservée telle quelle dans `candidate_jarvi`** (réplica JSON opaque). On ne la perd pas : on
  cesse simplement de s'en servir.
- **Photo** : corriger le contrat — **jamais transmise**, **dévoilement compris**.
- Aligner `CandidateMapper` sur le masquage étendu **déjà décidé** (2026-07-10) et **non implémenté** :
  prénom → **initiale seule**, WhatsApp `null`.

## Invariants

- **Le front ne reçoit jamais une donnée masquée.** Le flou de l'UI porte sur un **placeholder** ;
  après dévoilement, le front **re-fetch** — il n'a rien à « dé-flouter ».
- `hasEmail` / `hasPhone` n'exposent **que l'existence**, jamais la valeur ni un fragment.

## Critères d'acceptation

- [ ] Un profil masqué expose `hasEmail` / `hasPhone` et `email: null` / `phone: null`.
- [ ] Un profil masqué n'expose **ni photo, ni prénom complet, ni WhatsApp** ; un profil **dévoilé
      n'expose pas de photo non plus**.
- [ ] `openToOpportunities` est ingéré et exposé.
- [ ] La mobilité expose « tout le Maroc » ou une liste de villes, et le filtre applique la sémantique
      *anywhere OU ville ciblée*.
- [ ] Le salaire n'apparaît **plus** dans les réponses.
- [ ] Tests : aucun champ sensible ne fuit sur un profil non dévoilé.
- [ ] `make api-quality` vert.

## Hors périmètre

- Référentiel de postes et agrégats de facettes → tâche `075`.
