# Import des profils Jarvi vers showcase-recruiter-webapp-saas

## Contexte

Phase 2 — Espace entreprise.

L'espace entreprise exploite un vivier de candidats consultable, filtrable et dévoilable par les
recruteurs. Ce vivier est alimenté par les profils professionnels Jarvi, mais la recherche et les
interactions entreprise tournent côté showcase-recruiter-webapp-saas.

## Décision

L'import des profils Jarvi vers showcase-recruiter-webapp-saas se fait par **CLI Symfony**, exécutée côté `api/`.

La CLI importe le profil professionnel Jarvi dans une réplica locale en lecture seule :

```text
Jarvi -> api/ CLI -> PostgreSQL showcase-recruiter-webapp-saas
```

Jarvi reste maître du profil professionnel. showcase-recruiter-webapp-saas reste maître du consentement, de la visibilité,
des différenciants, des crédits, des favoris, des dévoilements et des interactions entreprise.

## Décision technique API

L'import doit utiliser **GraphQL comme source primaire**.

Constats vérifiés le 2026-07-01 sur le projet showcase-recruiter-webapp-saas Jarvi
`00000000-0000-4000-8000-000000000001` :

- le périmètre contient **1676 profils** ;
- REST `/profiles` retourne bien `{data,total}`, mais `limit=500` et `limit=1000` renvoient HTTP 500
  sur le filtre projet ; `limit=100` fonctionne ;
- REST `updatedAfter` ne retourne pas les profils modifiés attendus sur le filtre projet testé ;
- GraphQL retourne `profiles_aggregate.aggregate.count = 1676` ;
- GraphQL accepte `limit=500` avec le shape complet d'import ; `limit=1000` fonctionne seulement sur
  une requête légère mais renvoie HTTP 500 avec les relations utiles ;
- GraphQL filtre correctement l'incrémental via `updatedAt: {_gt: $cursor}`.

Conclusion : **GraphQL pour l'import complet et l'incrémental**, avec des pages de **500 profils**
pour le shape complet. REST reste utilisable en fallback
ponctuel uniquement si un endpoint spécifique s'avère plus pratique pour un profil isolé.

## Cadence et environnements

- L'import initial complet est un **chargement one-shot global** du périmètre Jarvi showcase-recruiter-webapp-saas.
- L'import initial réel des 1676 profils ne doit **pas** être exécuté en environnement de dev local.
  En dev, utiliser `--limit` et/ou des données anonymisées.
- La synchronisation courante est **journalière et globale**, basée sur le curseur `updatedAt`
  conservé côté showcase-recruiter-webapp-saas.
- La synchro reste descendante : Jarvi -> showcase-recruiter-webapp-saas. showcase-recruiter-webapp-saas ne remonte pas ses données maîtres vers
  Jarvi après inscription, hors création initiale minimale d'un profil Jarvi absent.

## Objectifs

- Alimenter le vivier de profils exploité dans l'espace entreprise.
- Éviter les appels Jarvi live pendant la recherche recruteur.
- Maîtriser le quota Jarvi par des imports contrôlés.
- Garantir un import idempotent, relançable et observable.
- Préparer l'invitation des candidats importés pour obtenir leur consentement showcase-recruiter-webapp-saas.

## Données importées

La CLI importe uniquement les données professionnelles maîtres Jarvi nécessaires au vivier :

- identité professionnelle ;
- localisation professionnelle ;
- headline / résumé ;
- expériences ;
- formations ;
- langues ;
- compétences ;
- métadonnées CV, dont le nom de fichier quand Jarvi l'expose (`candidate_jarvi.resumes[].name`) ;
- contacts professionnels si disponibles dans le payload autorisé.
- provenance technique Jarvi : source globale du profil, sources des blocs, présence LinkedIn,
  nombre de CV, état de parsing CV.
- comptes rendus et champs riches maîtres Jarvi, conservés comme données sensibles dans
  `candidate_jarvi.rawPayload.historyEntries[]` et `candidate_jarvi.rawPayload.fieldsValues[]`.

Cette provenance sert à qualifier les profils importés sans changer le modèle maître :

| Cas | Signaux Jarvi |
| --- | --- |
| LinkedIn uniquement | `source = LINKEDIN`, `linkedinId` présent, `countResumes = 0` |
| CV uniquement | `countResumes > 0`, `files[].parsedAt` ou `shouldBeParsed`, sources `RESUME`, pas de LinkedIn |
| Hybride LinkedIn + CV | LinkedIn présent + CV parsé, sources `LINKEDIN` et `RESUME` sur les blocs |
| Manuel / import autre | `source = IMPORT` ou source non LinkedIn/CV, sans CV ni LinkedIn exploitable |

Ces données alimentent principalement :

- `candidate`
- `candidate_jarvi`
- `person` minimal si aucune identité locale n'existe encore
- `jarvi_sync_state` pour le curseur et l'observabilité

Les comptes rendus Jarvi ne deviennent pas un `Candidate.starInterviewReport` showcase-recruiter-webapp-saas. Ils restent
une information maître Jarvi, utile au vivier, non éditée localement et non exposée par défaut tant
qu'une règle produit/RGPD d'affichage recruteur n'est pas validée.

### Coordonnées Jarvi : multi-valuées, à aplatir

Constat vérifié le 2026-07-10 sur l'export réel `GET /profiles` : `emailAddresses[]` et
`phoneNumbers[]` sont des **tableaux** (jusqu'à 3 valeurs observées par profil), chaque entrée portant
une `source` (`MANUAL`, `RESUME`, `LINKEDIN`) — avec des **doublons internes** (même email sous
plusieurs sources, même numéro en `06…` et `+336…` ; le `canonicalNumber` Jarvi n'est pas fiable tel
quel). Le modèle showcase-recruiter-webapp-saas (`Person.contactEmail` / `Person.phone`) est **mono-valué** : l'import doit
appliquer une règle de choix explicite — **priorité `MANUAL` > `RESUME` > `LINKEDIN`**, puis la plus
récente — et normaliser le numéro retenu au format E.164.

Chaque numéro Jarvi porte aussi un `whatsappIdentifier` (+ `whatsappLastCheckedAt`) :
signal de joignabilité précieux (score de contactabilité), mais **l'identifiant contient le numéro en
clair** → même niveau de sensibilité que le téléphone.

## Données sensibles Jarvi

Les données suivantes sont importées pour préserver la vérité Jarvi, mais doivent rester derrière une
règle d'accès explicite :

- coordonnées issues de Jarvi (multi-valuées, cf. § ci-dessus), y compris l'identifiant
  WhatsApp qui révèle le numéro ;
- photo de profil (`image` / `imageId`) : très identifiante, jamais exposée avant dévoilement
  (cf. tableau de masquage de [recherche_et_masquage.md](./recherche_et_masquage.md)) ;
- variantes d'identité (`firstName` / `lastName` + `…Parsed` / `…Manual`) : masquer une variante sans
  les autres est inopérant, la règle d'anonymisation s'applique aux trois ;
- date de naissance (`birthday`), rare mais présente ;
- nom du fichier CV et métadonnées CV ;
- contenu des derniers échanges (`lastMessages[]`) : correspondance privée, jamais exposée côté
  entreprise ;
- `historyEntries[]` ;
- `fieldsValues[]`, dont les champs riches de type « Récap Entretien ».

Par défaut, elles ne sont pas exposées dans les réponses recruteur publiques. Leur affichage éventuel
doit être décidé par le produit et cadré RGPD avant d'être branché dans l'API entreprise.

## Données non importées comme vérité

Les données suivantes restent maîtres showcase-recruiter-webapp-saas et ne doivent pas être écrasées par l'import :

- consentement ;
- visibilité showcase-recruiter-webapp-saas ;
- présence Maroc ;
- projet de retour ;
- disponibilité showcase-recruiter-webapp-saas ;
- prétentions ;
- favoris ;
- dévoilements ;
- notes ;
- crédits ;
- activité entreprise.

## Modes CLI attendus

La CLI doit couvrir au minimum :

| Mode | Usage |
| --- | --- |
| import complet | premier chargement ou reconstruction d'environnement |
| import incrémental | récupération GraphQL des profils modifiés depuis le dernier curseur `updatedAt` |
| import ciblé | rafraîchir un profil par `jarviProfileId`, notamment avant ou après un dévoilement |
| dry-run | estimer les créations / mises à jour / désactivations sans écrire |
| réconciliation | désactiver les profils sortis du périmètre Jarvi showcase-recruiter-webapp-saas, sans suppression |

Nom de commande indicatif :

```bash
bin/console app:jarvi:profiles:import
```

Les options exactes seront figées à l'implémentation, mais doivent permettre au minimum :

- `--full`
- `--since`
- `--profile-id`
- `--dry-run`
- `--limit`
- `--reconcile`

## Règles d'import

- L'upsert est ancré sur `Candidate.jarviProfileId`.
- La pagination primaire se fait en GraphQL, par `limit` / `offset`, tri `updatedAt`.
- Le chargement initial du périmètre courant se fait en 4 pages de 500 maximum pour 1676 profils.
- La synchronisation journalière utilise le curseur `updatedAt` stocké côté showcase-recruiter-webapp-saas.
- Un profil Jarvi déjà présent met à jour uniquement le réplica professionnel.
- Un profil Jarvi absent crée une projection `Candidate` et un `CandidateJarvi`.
- Un `Candidate` importé peut exister sans `User` showcase-recruiter-webapp-saas.
- L'import ne crée pas de compte candidat.
- L'import ne vaut pas consentement.
- Un profil sorti du périmètre est désactivé (`active = false`) et jamais supprimé automatiquement.
- Un profil revenu dans le périmètre peut être réactivé.
- Les métadonnées d'interface Jarvi sont exclues par liste blanche.

## Exposition dans l'espace entreprise

L'import alimente le vivier technique, mais l'exposition recruteur reste décidée côté showcase-recruiter-webapp-saas :

- recherche sur profils actifs uniquement ;
- profils sans consentement showcase-recruiter-webapp-saas exposables uniquement sous forme anonymisée si la règle RGPD est
  validée avant production ;
- masquage des coordonnées, du CV et des comptes rendus Jarvi tant que le profil n'est pas dévoilé ;
- respect de la visibilité et du consentement showcase-recruiter-webapp-saas ;
- snapshot figé au dévoilement pour conserver ce qui a été payé.

Un profil importé mais sans consentement explicite doit être traité selon la règle RGPD validée
avant production. La cible produit retenue est l'exposition **anonymisée**, avec identité,
coordonnées, CV et comptes rendus masqués tant que le consentement et/ou le dévoilement ne permettent
pas leur accès.

## Points de vigilance

- **RGPD** : base légale de l'import massif, information des personnes, opposition, rétention et
  effacement.
- **Quota Jarvi** : l'import incrémental doit limiter les appels et journaliser les volumes.
- **Idempotence** : relancer la CLI ne doit pas dupliquer les profils.
- **Traçabilité** : journaliser créations, mises à jour, désactivations, erreurs et curseur.
- **Séparation des maîtres** : ne jamais écraser les données showcase-recruiter-webapp-saas avec l'import Jarvi.
- **Robustesse** : une erreur sur un profil ne doit pas invalider tout le lot si un traitement partiel
  est possible.

## Liens

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `dev-book/tasks/014-recherche-candidats-via-jarvi.md`
- `dev-book/tasks/020-ingestion-perimetre-jarvi.md`
- `dev-book/tasks/033-espace-candidat-magic-link-invitation-consentement.md`
