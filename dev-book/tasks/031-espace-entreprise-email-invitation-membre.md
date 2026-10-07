# TASK 031 — Espace Entreprise : e-mail d'invitation membre et acceptation guidée

- **Créée le** : 2026-06-30.
- **Statut** : 🚧 **partiel** — e-mail API branché ; page front à livrer après scaffold `webapp/`.
- **Périmètre** : transformer le flux technique livré en tâche `009` en parcours produit complet :
  un `COMPANY_OWNER` invite un membre, showcase-recruiter-webapp-saas envoie un e-mail contenant un lien sécurisé, le
  membre accepte l'invitation depuis l'espace entreprise, définit son mot de passe, puis peut se
  reconnecter avec `email + password`.
- **Hors périmètre** : droits fins personnalisables ; promotion/rétrogradation de rôle (→ `026b`) ;
  social login ; délivrabilité production avancée (SPF/DKIM/DMARC) ; templates e-mail définitifs ;
  réactivation d'un membre désactivé (à cadrer comme action dédiée si nécessaire).

## Contexte

La tâche `009` a livré le socle API d'invitation : création d'une `Invitation`, token sécurisé,
acceptation publique, création du compte membre et retrait par désactivation. Pour faciliter les
tests Insomnia, le token est actuellement retourné dans la réponse API.

Le parcours cible ne doit pas demander au recruteur owner de copier un token. L'invitation doit
être envoyée par e-mail au futur membre, qui arrive sur la plateforme entreprise via un lien
tokenisé. Ce lien est proche d'un Magic Link, mais il ne sert pas à connecter directement un
utilisateur existant : il sert à accepter une invitation puis à créer et activer le compte recruteur.

## Parcours cible

1. Le `COMPANY_OWNER` saisit l'e-mail d'un futur membre dans `/equipe`.
2. `POST /v1/organizations/{id}/invitations` crée une invitation `PENDING`.
3. L'API déclenche un e-mail vers l'adresse invitée avec une URL front :
   `https://entreprise.showcase-recruiter-webapp-saas.example/invitation/{token}`.
4. Le membre ouvre le lien et arrive sur une page d'acceptation d'invitation.
5. La page affiche le contexte minimal : entreprise, e-mail invité, expiration éventuelle.
6. Le membre saisit `givenName`, `familyName`, `password` et confirmation du mot de passe.
7. Le front appelle `GET /v1/invitations/{token}` pour afficher le contexte public minimal.
8. Le front appelle `POST /v1/invitations/{token}/accept`.
9. L'API crée `Person`, `User`, `Recruiter`, passe l'invitation à `ACCEPTED`, puis le membre peut
   se connecter avec `email + password`.

## Règles

- Le lien d'invitation est **usage unique** et expirant ; token inconnu → 404, token expiré ou déjà
  consommé → 410.
- Le mot de passe est obligatoire à l'acceptation pour permettre les connexions futures.
- Le token ne doit plus être affiché dans la réponse API en environnement produit ; il peut rester
  exposé en dev/test ou dans Mailpit pour faciliter les tests locaux.
- Le membre invité est créé avec le rôle demandé par l'invitation, borné en V1 à
  `ROLE_COMPANY_MEMBER`.
- Une invitation déjà `PENDING` pour le même couple `(organization, email)` reste refusée.
- Une adresse déjà membre de l'organisation ne crée pas une nouvelle invitation.
- Le parcours doit rester distinct du Magic Link d'inscription du déclarant et du lien
  `define-password` post-validation entreprise.
- L'acceptation d'une invitation membre ne déclenche aucun push Jarvi : seuls les comptes showcase-recruiter-webapp-saas
  internes à l'`Organization` sont créés ou activés.

## Backend

- [x] Brancher l'envoi d'e-mail sur `InvitationProcessor` via un service domaine dédié
  (`InvitationEmailSender`). L'async Messenger reste une optimisation ultérieure si nécessaire.
- [x] Réutiliser la configuration mail locale existante ou à venir (Mailpit en dev/test, aucun SMTP
  externe pendant les tests automatisés).
- [x] Préparer un template e-mail sobre avec :
  - nom de l'entreprise ;
  - e-mail invité ;
  - lien d'acceptation ;
  - date d'expiration ;
  - mention que le membre devra définir son mot de passe.
- [x] Exposer `GET /v1/invitations/{token}` pour le contexte public minimal de la page front
  (`email`, `organizationName`, `role`, `expiresAt`).
- [x] Ne pas exposer de secret dans les logs applicatifs production.

## Frontend

- [ ] Ajouter la page `entreprise.showcase-recruiter-webapp-saas.example/invitation/:token` dès que `webapp/` est scaffoldé.
- États attendus :
  - chargement ;
  - token valide ;
  - token expiré/déjà utilisé ;
  - acceptation réussie ;
  - erreur de validation formulaire.
- Formulaire : prénom, nom, mot de passe, confirmation.
- Après succès : rediriger vers `/login` ou ouvrir une session si le contrat auth le permet.
- Accessibilité : labels explicites, messages d'erreur associés aux champs, navigation clavier.

## Réactivation

Le retrait d'un membre reste une désactivation (`RecruiterStatus::DISABLED`) pour préserver l'audit.
La réactivation n'est pas couverte par cette tâche. Elle est cadrée séparément par la tâche `032`.
Un membre déjà accepté ne doit pas être réinvité pour revenir, et la réinvitation ne sert pas à
recréer un mot de passe. Si le membre ne connaît plus son mot de passe, il utilise le reset password
standard (`025b`).

## Tests

- Owner invite → invitation créée + e-mail capturé en dev/test.
- Contexte d'invitation public accessible par token valide.
- Le lien contenu dans l'e-mail permet d'accepter l'invitation.
- Acceptation valide → membre `ACTIVE`, invitation `ACCEPTED`, login possible avec le mot de passe.
- Token inconnu → 404.
- Token expiré ou déjà consommé → 410.
- Double invitation `PENDING` même e-mail → 409.
- `COMPANY_MEMBER` ou non-membre tente d'inviter → 403.
- Aucun e-mail réel ne sort en test automatisé.

## Définition de terminé

- [x] L'e-mail d'invitation est envoyé/capturé localement et contient le lien front correct.
- [x] Le contexte public d'invitation est disponible pour la future page front.
- [ ] Le parcours d'invitation ne dépend plus d'une copie manuelle du token hors dev/test.
- [ ] La page d'acceptation permet de définir le mot de passe et de finaliser le compte membre.
- [ ] Les erreurs token/expiration/doublon sont gérées côté API et affichées côté front.
- [ ] La documentation Insomnia/dev indique comment tester avec Mailpit ou le mode dev.
- [ ] Quality gates backend/front exécutés au point de validation.

## Références

- [inscription.md](../cadrage/entreprise/inscription.md)
- [vue_d_ensemble.md](../cadrage/entreprise/vue_d_ensemble.md)
- [009-espace-entreprise-invitations-multicomptes.md](./009-espace-entreprise-invitations-multicomptes.md)
- [024-magic-link-email-local-entreprise.md](./024-magic-link-email-local-entreprise.md)
- [025b-dev-mot-de-passe-libre-service.md](./025b-dev-mot-de-passe-libre-service.md)
- [032-espace-entreprise-reactivation-membre.md](./032-espace-entreprise-reactivation-membre.md)
