# TASK 081 — API : social login Google / LinkedIn (OIDC)

- **Créée le** : 2026-07-13.
- **Statut** : ⬜ à faire.
- **Type** : API — authentification (socle, cross-cutting).
- **Dépend de** : néant (l'infra JWT + magic link est en place).
- **Débloque** : tâche `082` (tunnel d'inscription entreprise — bouton « Continuer avec Google /
  LinkedIn »).
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md` (§ Workflow d'inscription).
- **Réf code** : `api/config/packages/security.yaml` · `src/Domain/Auth/MagicLinkIssuer.php` ·
  `src/ApiResource/Output/MagicLinkConsumeOutput.php` (le modèle à répliquer).

## Contexte

Le cadrage prévoit que l'inscription puisse démarrer par un Social Login Google ou LinkedIn.
**Rien n'existe** : aucune dépendance OAuth/OIDC dans `composer.json`, aucune route. Aujourd'hui
la seule porte d'entrée sans mot de passe est le magic link.

## Décision d'architecture

**Le social login sert à récupérer l'e-mail, pas à ouvrir la session** — conformément au cadrage
(décision confirmée le 2026-07-13). Le parcours reste : Google / LinkedIn → e-mail vérifié → **envoi
d'un magic link** → authentification par magic link. Le social login est un raccourci de saisie,
pas une seconde porte d'authentification.

Conséquence heureuse : le magic link demeure **l'unique chemin d'émission de session sans mot de
passe**. Le rapprochement compte / e-mail, la création du `User`, l'émission des JWT restent où ils
sont déjà (`MagicLinkIssuer` et `magic-link/consume`) — cette tâche n'y touche pas.

**Le BFF Nuxt mène la danse OAuth, l'API vérifie la preuve.** Le client secret vit côté Nitro
(jamais dans le navigateur) : le BFF gère `redirect` puis `callback`, récupère l'`id_token` OIDC, et
le présente à l'API. **L'API ne fait jamais confiance à un e-mail annoncé par le BFF** — elle vérifie
la signature de l'`id_token` contre le JWKS du fournisseur (émetteur, audience, expiration,
`email_verified`). Sans cette vérification, quiconque atteint l'API sur le réseau interne pourrait
faire envoyer un magic link vers l'adresse de son choix.

Google et LinkedIn exposent tous deux de l'OIDC standard (LinkedIn via « Sign In with LinkedIn
using OpenID Connect », scopes `openid profile email`) : **un seul vérificateur** couvre les deux,
paramétré par fournisseur. Pas de bundle OAuth côté Symfony — la vérification JWKS suffit et évite
d'introduire un firewall supplémentaire.

## À livrer

| Méthode | Route | Auth | Payload |
| --- | --- | --- | --- |
| POST | `/v1/auth/social/magic-link` | 🌐 public | `{provider: 'google'\|'linkedin', idToken}` |

Vérifie l'`id_token`, puis **envoie un magic link à l'e-mail vérifié**. Réponse identique à
`magic-link/request` — le BFF a un seul chemin, et l'écran « e-mail envoyé » est le même.

- `Domain/Auth/SocialIdentityVerifier` : vérifie l'`id_token` contre le JWKS du fournisseur (cache
  du JWKS, rotation des clés supportée) ; rejette si `email_verified` est faux.
- Enum `SocialProvider` (`GOOGLE`, `LINKEDIN`) + configuration par fournisseur (`issuer`,
  `audience`, `jwks_uri`) en `services.yaml`, secrets par variables d'environnement.
- Réutilise `MagicLinkIssuer` tel quel : aucune duplication du chemin de session.

## Règles

- **Aucune session émise ici.** Le seul effet de bord est l'envoi d'un magic link.
- **Rapprochement par e-mail** (déjà assuré par le magic link) : un compte créé par saisie d'e-mail
  puis repris via Google est **le même compte**. Pas de doublon `Person`.
- **Aucune élévation de privilège** : le social login n'accorde aucun rôle. Le rôle vient du
  parcours (création d'entreprise, invitation), comme aujourd'hui.
- Les secrets Google / LinkedIn ne sont **jamais** exposés au navigateur.
- Même réponse que l'e-mail existe ou non : cette route ne doit pas devenir un oracle de comptes.

## Critères de validation

- Un `id_token` valide (fixture signée par un JWKS de test) → 202 + magic link parti (Mailpit).
- Un `id_token` expiré, d'un mauvais `aud`, mal signé, ou avec `email_verified: false` → 401, aucun
  e-mail envoyé, aucun `User` créé.
- Le magic link ainsi émis se consomme par le chemin existant, sans code spécifique au social login.
- Tests fonctionnels : cœur sensible (porte d'entrée d'authentification) → TDD.

## Hors périmètre

- Écrans front (→ tâche `082`) et flux `redirect`/`callback` du BFF (→ tâche `082`).
- Rattachement d'un second fournisseur à un compte existant depuis le profil (V2).
- Apple / Microsoft.
