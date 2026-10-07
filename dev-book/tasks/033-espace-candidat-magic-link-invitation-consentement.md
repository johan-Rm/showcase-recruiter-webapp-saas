# TASK 033 — Espace Candidat : Magic Link, invitation des profils Jarvi et consentement

- **Créée le** : 2026-07-01.
- **Statut** : ⏳ à faire.
- **Type** : fonctionnalité P4 — inscription candidat, rattachement profil et base légale.
- **Dépend de** : tâche `013` (projection candidat), tâche `020` (ingestion Jarvi), tâche `024`
  (socle Magic Link), tâche `018` (visibilité/consentement).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/inscription.md`
  - `dev-book/cadrage/candidat/vue_d_ensemble.md`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`

## Décision

L'inscription candidat cible se fait par **Magic Link**, comme l'entreprise.

showcase-recruiter-webapp-saas doit inviter les profils récupérés depuis Jarvi afin qu'ils créent leur compte showcase-recruiter-webapp-saas et
donnent leur consentement. Le workflow doit gérer deux cas :

1. **Profil déjà en base showcase-recruiter-webapp-saas** : profil importé depuis Jarvi par l'ingestion régulière.
2. **Profil absent de showcase-recruiter-webapp-saas / Jarvi** : profil saisi directement par le candidat, à créer
   minimalement chez Jarvi pour établir l'ancre `jarviProfileId`.

Les données maîtres showcase-recruiter-webapp-saas restent locales. Aucune présence Maroc, visibilité, consentement,
prétention ou projet de retour n'est poussé vers Jarvi.

## Périmètre

- Ajouter le parcours Magic Link candidat :
  - demande de Magic Link ;
  - envoi e-mail ;
  - consommation usage unique ;
  - expiration ;
  - reprise d'inscription incomplète.
- Rattacher le compte candidat à une projection existante quand le profil a déjà été importé depuis
  Jarvi.
- Définir la stratégie de rapprochement :
  - `jarviProfileId` si connu ;
  - e-mail issu de Jarvi / LinkedIn ;
  - URL LinkedIn ;
  - règle de conflit quand plusieurs profils correspondent.
- Créer un `User` showcase-recruiter-webapp-saas avec `ROLE_CANDIDATE` et le rattacher au `Person` existant ou nouvellement
  créé.
- Collecter et tracer le consentement requis avant exposition aux recruteurs.
- Si aucun profil Jarvi correspondant n'existe, créer un profil Jarvi minimal pour obtenir l'ancre,
  puis laisser l'ingestion descendante remplir `candidate_jarvi`.
- Prévoir l'invitation proactive des profils importés Jarvi : campagne e-mail / lien personnalisé /
  reprise de parcours.

## Hors périmètre

- Édition des différenciants → tâche `016`.
- Présence Maroc → tâche `017`.
- Recherche recruteur → tâche `014`.
- Ingestion Jarvi complète → tâche `020`.
- Suppression / droit à l'oubli complet → cadrage RGPD / runbook dédié.

## Invariants

- Magic Link authentifie le candidat ; il ne vaut pas consentement implicite.
- Le consentement doit être explicite, horodaté et vérifiable.
- Un profil importé Jarvi peut exister sans `User` showcase-recruiter-webapp-saas.
- Un `User` candidat ne doit être créé qu'après vérification du lien Magic Link.
- Les données showcase-recruiter-webapp-saas ne remontent pas vers Jarvi.
- La clé privée Jarvi reste côté `api/` uniquement.

## Points de vigilance

- **RGPD** : base légale de l'import massif, information des personnes, consentement, opposition,
  rétention et effacement doivent être cadrés avant exposition en production.
- **Rapprochement** : éviter les faux positifs entre profils homonymes ou e-mails partagés.
- **Idempotence** : consommer deux fois le même Magic Link ne doit pas créer deux comptes ni deux
  projections.
- **Sécurité** : token hashé, usage unique, expiration courte, aucun token en clair en base.

## Critères d'acceptation

- [ ] Un candidat déjà importé depuis Jarvi peut être invité, consommer un Magic Link et rattacher son
      compte au `Candidate` existant.
- [ ] Un candidat absent peut s'inscrire, créer une projection locale et déclencher la création
      minimale Jarvi.
- [ ] Le consentement est obligatoire avant exposition aux recruteurs.
- [ ] Aucun différenciant showcase-recruiter-webapp-saas n'est poussé vers Jarvi.
- [ ] Les cas de doublon ou conflit de rapprochement sont bloqués ou envoyés en revue admin.
- [ ] Tests fonctionnels : Magic Link valide, expiré, déjà consommé, rattachement existant, création
      nouvelle, doublon.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `dev-book/cadrage/candidat/inscription.md`
- `dev-book/cadrage/candidat/vue_d_ensemble.md`
