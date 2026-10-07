# TASK 115 — Vocabulaire : référentiel des écoles (chaîne D)

- **Créée le** : 2026-07-22.
- **Statut** : 🟡 référentiel récolté (`app:school:harvest`) — **le regroupement utile reste à faire, et sa valeur produit à confirmer**.
- **Type** : API — vocabulaire / 4ᵉ chaîne de traitement.
- **Lié à** : `104` (même problème d'identité stable, déjà résolu pour les entreprises), `105`/`107`
  (même patron de classification), `112` (niveau d'études, autre exploitation des formations).
- **Réf** :
  - `dev-book/cadrage/entreprise/profil_dimensions_vivier.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/traitements-profils.md`

## Contexte

Les écoles sont la **dimension non traitée la mieux couverte du vivier**, et l'une des plus
regroupables :

| Dimension | Valeurs distinctes | Couverture | Dispersion | Traitée ? |
| --- | ---: | ---: | ---: | :---: |
| **Écoles** | **2 365** | **92 %** | **53 %** | ❌ |
| Intitulés de poste | 7 137 | 97 % | 72 % | ✅ (`103`) |
| Sociétés | 445 *(éch.)* | 98 % | 80 % | ✅ (`105`) |

Une dispersion de 53 % signifie qu'elles **se regroupent mieux** que les postes (72 %) et les
sociétés (80 %) — les deux dimensions qu'on a déjà traitées. Aujourd'hui c'est du texte libre non
qualifié, avec les mêmes doublons qu'ailleurs (`Université Hassan II de casablanca`,
`ESITH Casablanca`, `Ecole Supérieure de Technologie de Casablanca`…).

## Identifiant d'école — ingéré, mais il ne dédoublonne PAS

La requête GraphQL ne retenait que le **nom** de l'école, alors que Jarvi expose aussi `school { id }`.
C'était la même omission que pour les entreprises (`104`), et elle est corrigée : l'identifiant est
désormais ingéré, **présent sur 100 % des formations**.

**Mais la mesure invalide l'hypothèse de départ** (2026-07-22, 4 494 formations) :

| | |
| --- | ---: |
| Noms bruts distincts | **2 370** |
| Identifiants distincts | **2 465** |
| Noms portant **plusieurs** identifiants | **87** |

L'identifiant **fragmente** au lieu de regrouper : Jarvi héberge plusieurs entités école pour un même
établissement (« université privée de fès » en compte 4). **Contrairement aux entreprises, la clé de
dédoublonnage des écoles est le NOM normalisé, pas l'identifiant** — celui-ci ne sert que de
référence vers l'entité source.

C'est exactement le garde-fou « mesurer avant de qualifier » de cette carte, et il a servi
immédiatement.

## Ce qui a été livré

`app:school:harvest` (déterministe, aucun LLM) récolte les établissements regroupés par **nom
normalisé**, avec leur fréquence, les graphies rencontrées et les identifiants Jarvi rassemblés.
La normalisation vient de `TermNormalizer`, partagée avec la récolte du vocabulaire — c'était la
troisième dimension à en avoir besoin, la recopier aurait rouvert la divergence fermée par `094a`.

**Deuxième mesure, et deuxième hypothèse invalidée** (4 494 formations) :

| Clé de regroupement | Valeurs distinctes |
| --- | ---: |
| Identifiant Jarvi | 2 465 — il **fragmente** |
| Nom brut | 2 444 |
| **Nom normalisé** | **2 366** |

La normalisation ne fusionne que **78 noms, soit 3,2 %**. Elle corrige la casse et les espaces, rien
de plus : 73 établissements rassemblent plusieurs graphies, 91 rassemblent plusieurs entités Jarvi.

**Les doublons résiduels sont SÉMANTIQUES, pas orthographiques** — « ENCG Casablanca » et « École
Nationale de Commerce et de Gestion de Casablanca » sont le même établissement sous deux noms sans
lettre commune exploitable. Aucune normalisation de chaîne ne les rapprochera : il faudrait un
regroupement par le sens (patron de la chaîne C, avec LLM) ou une liste de référence externe.

C'est donc la **deuxième fois** que le garde-fou « mesurer avant de qualifier » évite un
développement : d'abord sur l'identifiant, puis sur le nom normalisé.

## Objectif

Constituer un référentiel d'**écoles canoniques**, base d'un filtre par établissement et d'une
qualification éventuelle.

## Périmètre pressenti

- **Ingestion** : retenir l'identifiant d'école (prérequis ci-dessus).
- **Récolte** des écoles distinctes + fréquences, sur le patron de `CompanySectorHarvester`.
- **Canonicalisation par le NOM normalisé** (mesure ci-dessus), l'identifiant Jarvi n'étant qu'une
  référence vers la source. La normalisation doit être **mutualisée** plutôt que dupliquée une
  troisième fois (cf. `094`).
- **Qualification** (à statuer) : type d'établissement (université / grande école / business school),
  pays de formation. Utile pour un filtre, mais c'est un **choix produit** à confirmer avant de coder.

## Invariants

- Champ **dérivé et recalculable** ; Jarvi reste maître.
- Statut `candidat` pour toute qualification déduite (cf. `113`).
- Une école non reconnue n'est pas rangée par défaut.

## Critères d'acceptation

- [x] L'identifiant d'école est ingéré (100 % des formations) et sa capacité de dédoublonnage mesurée.
- [x] Les écoles distinctes sont récoltées par **nom normalisé** avec leur fréquence
      (2 366 établissements pour 4 494 formations).
- [x] La normalisation est **partagée** (`TermNormalizer`), pas recopiée une troisième fois.
- [x] **Chaîne de regroupement sémantique construite** (`app:school:group`, patron de la chaîne C :
      port + adaptateur LLM + double de test), mais **DÉSACTIVÉE par défaut** — flag
      `SCHOOL_GROUPING_ENABLED=0`. Le code est complet, câblé et testé ; il refuse simplement de
      s'exécuter tant que la valeur produit n'est pas confirmée. Aucun appel LLM tant que le verrou
      tient.
- [ ] **Décision produit** : lever le flag (et lancer un `--dry-run` d'abord) le jour où le filtre
      par école est acté.

## Points de vigilance

- **Mesurer avant de qualifier** : pour les entreprises, l'identifiant stable n'avait ramené 6 654
  noms qu'à ~6 673 entités — le gain espéré ne s'était pas produit. Refaire la mesure ici **avant**
  d'engager une classification.
- **Valeur produit à confirmer — c'est ce que le verrou attend** : filtrer par école est-il un vrai
  besoin entreprise ? Le cadrage ne le liste pas. La commande existe (`app:school:group`) mais
  `SCHOOL_GROUPING_ENABLED=0` la bloque : le jour de la décision, passer le flag à 1 dans `api/.env`,
  lancer un `--dry-run`, relire le `git diff` de `schools.json`. Rien n'est irréversible.
- **Ne pas confondre** avec le **niveau d'études** (`112`), qui se lit dans le *titre* de la formation,
  pas dans le nom de l'établissement.
