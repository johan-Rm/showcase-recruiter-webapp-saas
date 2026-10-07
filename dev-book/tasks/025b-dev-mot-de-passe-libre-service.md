# TASK 025b — DEV : mot de passe libre-service (changer + réinitialiser)

- **Créée le** : 2026-06-29.
- **Statut** : ✅ livré côté API — constaté le 2026-07-13 : les trois routes existent
  (`ChangePasswordProcessor`, `PasswordResetRequestProcessor`, `PasswordResetConsumeProcessor`).
  Reste la **validation de bout en bout** (→ `025`) et les **écrans** (→ `084`).
- **Périmètre** : implémenter la **gestion libre-service du mot de passe**, **cross-cutting** (tous
  rôles : candidat, membre/owner entreprise, admin). Comble le manque actuel : aucune route ne permet
  aujourd'hui de **changer** ou **réinitialiser** un mot de passe (seuls existent la définition à la
  création, `password-setup/consume` et le login).
- **Hors périmètre** : renommage des rôles (→ **025a**) ; tests & validation de bout en bout (→ **025**) ;
  envoi e-mail réel (V1 : token journalisé / Mailpit, e-mail via Messenger ultérieur).

## Routes à livrer

| Méthode | Route | Auth | Payload |
| --- | --- | --- | --- |
| PUT | `/v1/me/password` | 🔒 session/JWT | `{currentPassword, newPassword}` |
| POST | `/v1/auth/password-reset/request` | 🌐 public | `{email}` |
| POST | `/v1/auth/password-reset/consume` | 🌐 public (token) | `{token, newPassword}` |

## Règles

- **Changer** : vérifier `currentPassword` avant d'écrire le nouveau (sinon 403/422). Invalider les
  refresh tokens existants de l'utilisateur après changement effectif ; les JWT stateless déjà émis
  restent valides jusqu'à expiration.
- **Réinitialiser** : réutiliser l'**infra token e-mail** existante — `PasswordResetToken` analogue à
  [`MagicLinkToken`](../../api/src/Entity/MagicLinkToken.php) / `PasswordSetupToken` (ou factoriser).
  Token **usage unique**, expirant.
- **Anti-énumération** : `password-reset/request` répond **toujours 202**, que l'e-mail existe ou non.
- Routes publiques déclarées dans `security.yaml` `access_control` (PUBLIC_ACCESS) ; validation stricte du token.

## Structure (cf. api-structure.md)

- `ApiResource/Input/{ChangePasswordInput, PasswordResetRequestInput, PasswordResetConsumeInput}`.
- `State/Processor/{ChangePasswordProcessor, PasswordResetRequestProcessor, PasswordResetConsumeProcessor}`.
- `Domain/Auth/{PasswordChanger, PasswordResetIssuer, PasswordResetConsumer}` + exceptions dédiées
  (miroir de `PasswordSetup*`).
- `Entity/PasswordResetToken` + `Repository/PasswordResetTokenRepository` (`findOneByPlainToken`).

## Définition de terminé

- [ ] 3 routes en place et gardées correctement (1 connectée, 2 publiques).
- [ ] `currentPassword` vérifié au changement ; refresh tokens existants invalidés après changement/reset.
- [ ] Reset par token usage unique + expiration ; anti-énumération sur `request` (202 systématique).
- [ ] `make api-quality` vert (validation fonctionnelle complète = 025).

## Références

- Existant : `Domain/Auth/PasswordSetup*`, `MagicLink*` · [security.yaml](../../api/config/packages/security.yaml).
- Parent : **025** (validation). Sœur : **025a** (renommage rôles).
