# TASK 080 — API : envoi des e-mails en asynchrone (Symfony Messenger)

- **Créée le** : 2026-07-13.
- **Statut** : ✅ **faite** — périmètre V1 : les e-mails, et rien d'autre.
- **Type** : infrastructure applicative.
- **Réf ClickUp** : _à créer_.

## Contexte

Tous les e-mails partaient **dans la requête HTTP**, sans réessai :
`MagicLinkIssuer`, `PasswordResetIssuer`, `PasswordSetupIssuer`, `InvitationEmailSender`
appellent `MailerInterface::send()` en fin de traitement.

Conséquence : un SMTP lent ralentissait l'utilisateur, et un SMTP indisponible **faisait échouer
la requête après que le jeton ait été persisté** — l'action était à moitié faite, l'e-mail perdu,
et l'utilisateur bloqué sans moyen de se connecter.

## Ce qui a été fait

L'envoi est routé vers une file en base ; un worker la consomme hors requête. **Aucun code métier
n'a changé** : Symfony Mailer pousse déjà un `SendEmailMessage` dans le bus, seul son routage bascule.

| | |
| --- | --- |
| Transport | `async` — `doctrine://default` (pas d'infra supplémentaire : la file vit dans Postgres) |
| Réessai | 3 tentatives, délai croissant (5 s ×4) |
| Échec définitif | file `failed` — l'e-mail est conservé et rejouable (`messenger:failed:retry`) |
| Worker | un service `worker` par environnement (dev, preprod, prod), `restart: unless-stopped` |
| Tables | hors mapping Doctrine → créées par `messenger:setup-transports` (`make dev-schema`, `*-deploy`) |
| Tests | transport `in-memory` (`when@test`) |

Le worker recycle son processus (`--time-limit=3600`, `--memory-limit=128M`) : Docker le relance.

## Exploitation

Le mode d'échec a changé de nature, et c'est le point de vigilance : un SMTP mort ne provoque
plus d'erreur visible côté utilisateur. **Si le worker est arrêté, plus aucun e-mail ne part —
silencieusement**, les messages s'empilent en base.

- vérifier que le service `worker` tourne : `make preprod-status` / `make prod-status` ;
- inspecter les e-mails en échec : `make dev-messenger-failed` / `make prod-messenger-failed`.

## Invariants

- **L'échec d'un envoi ne perd jamais l'action de l'utilisateur** : le message est réessayé, puis
  conservé dans la file `failed`.
- L'asynchrone **ne contourne pas le Domain** : il déplace l'envoi hors de la requête, il ne
  réécrit aucune règle métier.

## Hors périmètre (et qui le reste)

- **Ingestion / synchronisation Jarvi** : reste une **commande CLI** (`api/src/Command/`), déclenchée
  à la main ou par planificateur. Le passage en continu n'est pas un besoin aujourd'hui.
- **Notifications** : brique d'émission dédiée (tâche `058`).
- `api/src/Message/` et `api/src/MessageHandler/` **restent vides** : le seul message transporté est
  celui de Symfony Mailer. Un message applicatif propre n'apparaîtra que le jour où un traitement
  métier long le justifiera.
