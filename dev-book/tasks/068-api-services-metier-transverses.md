# TASK 068 — API : centraliser les services métier transverses utilisés par les Providers/Processors

- **Créée le** : 2026-07-11.
- **Statut** : ⬜ à faire.
- **Type** : architecture API Symfony — services applicatifs.
- **Dépend de** : tâches `063`, `064`, `065`.
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-structure.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` § Authentification & accès

## Contexte

L'organisation actuelle des services est saine pour un MVP :

- `Domain/` porte les services métier nommés par usage (`ProfileRevealer`, `CandidateOnboarder`,
  `MemberInviter`, `CandidateAssigner`, etc.) ;
- `State/Provider` et `State/Processor` adaptent API Platform vers le métier ;
- les repositories portent les requêtes Doctrine ;
- aucun dossier générique `Service/` fourre-tout n'est nécessaire.

Le problème n'est donc pas le découpage général, mais la dispersion de certaines règles
transverses dans plusieurs providers/processors :

- résolution du recruteur courant dans une organisation ;
- vérification qu'un recruteur est actif ;
- définition d'un candidat "exposable" aux recruteurs ;
- différences entre accès historique payé et nouvelle action recruteur.

## Objectif

Introduire de petits services applicatifs explicites pour les règles transverses, afin que les
providers/processors restent des adaptateurs HTTP/API et que les invariants métier ne soient plus
réimplémentés route par route.

## Périmètre

- Créer des services ciblés, par exemple :
  - `Domain/Organization/CurrentRecruiterResolver` ou équivalent pour résoudre le recruteur courant
    dans une organisation, en vérifiant `RecruiterStatus::ACTIVE` ;
  - `Domain/Candidate/CandidateExposurePolicy` ou équivalent pour centraliser la règle
    `active + visible + consentement + non supprimé` ;
  - éventuellement `CandidateAccessPolicy` si le projet distingue accès historique payé et nouvelle
    action recruteur.
- Remplacer les duplications dans les processors/providers concernés :
  - recherche et détail candidat ;
  - reveal ;
  - favoris ;
  - assignations ;
  - invitations / membres si pertinent.
- Garder les responsabilités :
  - `State/*` orchestre la requête et traduit les erreurs HTTP ;
  - `Domain/*` porte les règles métier ;
  - `Security/*Voter` porte l'autorisation Symfony quand elle dépend du token courant ;
  - `Repository/*` ne porte que les requêtes.
- Mettre à jour `api-structure.md` si une convention de placement est ajoutée.

## Invariants

- Pas de dossier `Service/` générique.
- Pas d'abstraction large ou prématurée : un service = une règle métier partagée réelle.
- Les services de domaine ne dépendent pas de `ApiResource`, `State` ou HTTP.
- Les erreurs HTTP restent traduites côté `State`, pas dans le domaine.

## Critères d'acceptation

- [ ] La règle "recruteur courant actif dans l'organisation" est centralisée et testée.
- [ ] La règle "candidat exposable" est centralisée et réutilisée.
- [ ] Les processors/providers ne dupliquent plus ces vérifications.
- [ ] Deptrac reste vert.
- [ ] Les tests fonctionnels des tâches `063`, `064`, `065` passent.
- [ ] `make api-quality` vert.

## Hors périmètre

- Refonte hexagonale complète.
- Création d'un modèle domaine séparé des entités Doctrine.
- Renommage massif des dossiers existants.
