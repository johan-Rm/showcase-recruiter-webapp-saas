# TASK 064 — API : conditionner l'exposition recruteur au consentement candidat

- **Créée le** : 2026-07-11.
- **Statut** : ⬜ à faire.
- **Type** : correction RGPD / exposition vivier.
- **Dépend de** : tâches `033` (magic link candidat + consentement), `018` (visibilité),
  `035` (recherche candidat).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` § Différenciants showcase-recruiter-webapp-saas
  - `docs/showcase-recruiter-webapp-saas/content/3.quality/code-review-checklist.md` § Données & Jarvi

## Contexte

Le modèle `Candidate` distingue clairement :

- `visibility` : choix d'exposition interne (`VISIBLE` / `HIDDEN`) ;
- `active` : appartenance au périmètre Jarvi showcase-recruiter-webapp-saas ;
- `consentGivenAt` : consentement explicite showcase-recruiter-webapp-saas, requis pour exposer le profil aux recruteurs.

Problème : la recherche candidat et le détail recruteur filtrent aujourd'hui `visibility`,
`active` et `deletedAt`, mais pas `consentGivenAt IS NOT NULL`. Un profil importé de Jarvi,
visible par défaut mais sans consentement explicite, peut donc apparaître dans le vivier.

## Objectif

Un candidat n'est exposé aux recruteurs que si les trois conditions sont vraies :

1. profil actif dans le périmètre ;
2. visibilité `VISIBLE` ;
3. consentement explicite non retiré (`consentGivenAt` non null).

## Périmètre

- Recherche vivier :
  - ajouter le filtre `consentGivenAt IS NOT NULL` dans la requête serveur.
- Détail candidat recruteur :
  - retourner `404` si le candidat n'est pas exposable.
- Tests fonctionnels :
  - profil visible sans consentement absent de `/v1/search/candidates` ;
  - profil visible sans consentement inaccessible via `/v1/candidates/{id}` ;
  - profil réapparaît après consentement ;
  - profil disparaît après retrait du consentement.
- Documentation courte si nécessaire dans les commentaires de repository/provider.

## Invariants

- Le magic link authentifie mais ne vaut jamais consentement implicite.
- Un profil `HIDDEN`, inactif ou sans consentement ne doit pas être listé ni consultable par un
  recruteur.
- Le candidat et les admins conservent l'accès self-service au consentement.

## Critères d'acceptation

- [ ] La recherche ne renvoie que les profils `active = true`, `visibility = VISIBLE`,
      `deletedAt IS NULL`, `consentGivenAt IS NOT NULL`.
- [ ] Le détail recruteur applique la même règle.
- [ ] Les tests couvrent l'ajout et le retrait du consentement.
- [ ] `make api-quality` vert.

## Hors périmètre

- Refonte RGPD complète du pipeline d'ingestion Jarvi.
- Workflow produit d'obtention du consentement à grande échelle.
