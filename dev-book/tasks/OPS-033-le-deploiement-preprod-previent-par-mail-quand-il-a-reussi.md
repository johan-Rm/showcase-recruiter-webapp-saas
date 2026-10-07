# TASK OPS-033 — le déploiement preprod prévient par mail quand il a réussi

- **Créée le** : 2026-08-12.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-12 — `make preprod-deploy` se termine par un e-mail portant le
  commit déployé, sa branche, l'heure et la machine, expédié par **le compte mail du domaine**
  (aucun service tiers). Prouvé contre un vrai serveur SMTP : le message est reçu, son sujet
  accentué se décode correctement, ses deux destinataires sont livrés, et un mot de passe truffé de
  caractères spéciaux traverse intact. Deux défauts ont été trouvés par les tests, pas par la
  relecture — cf. *Vérification*.
- **Type** : feat — le déploiement preprod ne dit rien de lui-même. On lui ajoute une notification
  de fin, sans toucher à ce qu'il fait.
- **Tests** : **aucune suite automatisée** — le dépôt n'en a pas pour les scripts shell
  (`scripts/worktree-*.sh` n'en ont pas davantage), et en écrire une supposerait d'introduire un
  cadre de test bash pour un script de quatre-vingts lignes. La preuve est l'**exécution réelle**
  contre deux serveurs SMTP : Mailpit (envoi nominal, lecture du message reçu) et un serveur minimal
  exigeant une authentification (round-trip du mot de passe).
- **Réf** : [Makefile.preprod](../../Makefile.preprod) · [scripts/notify-deploy.sh](../../scripts/notify-deploy.sh) ·
  [docker-and-make.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md) § *Déploiement & secrets*.

## Contexte

Le déploiement de la preprod se lance à la main sur le VPS : `git pull`, puis `make preprod-deploy`.
La commande enchaîne cinq étapes — build des six images, démarrage des conteneurs, génération des
clés JWT, création des tables Messenger, vérification que l'API répond à travers le proxy frontal.

**Elle ne dit rien à personne quand elle se termine.** Qui a lancé le déploiement le sait, puisqu'il
regarde son terminal ; personne d'autre. Un déploiement dure plusieurs minutes, et celui qui l'a
lancé retourne à autre chose en attendant.

Deux propriétés sont déjà acquises et n'ont pas à être reconstruites :

- **`make` interrompt la chaîne à la première étape en échec** — le build boucle avec `|| exit 1`,
  et `preprod-healthcheck` sort en `exit 1` quand l'API ne répond pas ;
- **le déploiement finit par une vérification réelle**, pas par une supposition : le healthcheck
  interroge l'API à travers le proxy, comme le ferait un navigateur.

Atteindre une sixième ligne signifie donc déjà « tout est passé ». La notification n'a aucune
condition à évaluer : sa seule présence en fin de chaîne porte le sens.

### Pourquoi le mailer de l'application ne peut pas servir

En preprod, `MAILER_DSN` vaut `smtp://mailpit:1025` — un attrape-mails installé dans le compose,
dont le rôle est précisément qu'**aucun e-mail réel ne parte de la preprod** vers de vraies
personnes pendant la recette. Une commande Symfony qui enverrait la notification irait sagement se
ranger dans cette boîte fictive, sans erreur nulle part.

Il faudrait donc un second transport, avec ses identifiants embarqués dans l'image de l'API, pour
faire entrer une préoccupation d'exploitation dans le code applicatif. Le signal de déploiement
n'appartient pas au produit : il sort **à côté** de l'application.

### Pourquoi il faut malgré tout un compte d'envoi

Un serveur ne poste pas un e-mail, il **demande au serveur d'en face de l'accepter**. Une machine
dont l'adresse réseau n'est connue de personne, qui n'a jamais envoyé de courrier et qui prétend
écrire au nom de `showcase-recruiter-webapp-saas.example` sans en apporter la preuve, est rejetée ou classée en indésirable.
Il faut passer par une machine déjà connue et déjà autorisée à écrire au nom du domaine.

**Cette machine existe déjà** : les boîtes de `showcase-recruiter-webapp-saas.example` sont hébergées chez OVH
(`MX → mx1.mail.ovh.net`), et le domaine déclare publiquement qu'OVH a le droit d'envoyer en son nom
(`SPF → include:mx.ovh.com`). Une adresse comme `contact@showcase-recruiter-webapp-saas.example` peut donc expédier ce mail
avec le seul mot de passe de la boîte, et il arrivera authentifié.

## Périmètre

- **`scripts/notify-deploy.sh`** — envoie l'e-mail par le compte mail du domaine, en SMTP.
- **`preprod-notify`** dans `Makefile.preprod`, et son appel en **dernière ligne** de
  `preprod-deploy`.
- **`.env.example`** — les cinq variables documentées, valeurs vides.
- **La documentation d'infrastructure** — § *Déploiement & secrets* de `docker-and-make.md`.

## Hors périmètre

- **La notification en cas d'ÉCHEC.** Tranché avec l'humain : succès seul. C'est pourtant le cas le
  plus utile des deux — un déploiement réussi se constate en ouvrant le site, un déploiement raté
  peut passer inaperçu. Le script le couvrirait sans changer de forme, en étant appelé aussi depuis
  la sortie d'erreur de la chaîne. À rouvrir si le besoin se confirme.
- **La prod.** `prod-deploy` a sa propre chaîne, avec ses migrations Doctrine. Le script prend déjà
  l'environnement en argument, mais brancher `prod-notify` demande de relire cette chaîne-là.
- **Le `MAILER_DSN` de la prod**, qui utilisera le même compte. `docker-compose.prod.yml` l'exige
  déjà (`${MAILER_DSN:?…}`) et refuse de démarrer sans ; le renseigner appartient à la mise en
  production, pas à cette carte. Cette tâche a l'utilité secondaire d'**éprouver le compte avant**.
- **L'automatisation du déploiement** (intégration continue qui déploie sur `develop`). Le compose
  preprod se dit « déployé auto depuis develop » ; c'est une intention, pas un fait — le déploiement
  reste manuel.

## Décisions de cadrage

### Le compte mail du domaine, pas un service transactionnel

Une première version passait par l'API HTTP d'un service transactionnel (Brevo), au motif que les
hébergeurs bloquent fréquemment les ports d'envoi. **L'argument était mal posé** : le port fermé par
défaut est le **25**, celui que les serveurs de mail utilisent entre eux ; un compte mail, lui,
envoie par le **465** ou le **587**, qui restent ouverts. Et surtout, cela se vérifie en une
commande depuis le VPS plutôt que de se supposer.

Une fois ce faux obstacle levé, le service tiers n'apportait plus rien : il fallait créer un compte,
faire authentifier un domaine d'expéditeur, gérer une clé — pour obtenir ce que la boîte OVH du
domaine sait déjà faire. **Le compte existant l'emporte**, et il a un avantage supplémentaire : la
prod exigera de toute façon un `MAILER_DSN` réel, donc ce canal-là. L'éprouver ici, sur un cas sans
enjeu, vaut mieux que le découvrir le jour de la mise en ligne.

### Le script est silencieux s'il n'est pas configuré

Sans `DEPLOY_NOTIFY_TO` ni compte, le script affiche une ligne et **sort en 0**. Le déploiement
d'une machine non configurée — un poste de développement, un VPS fraîchement monté — se termine
exactement comme avant. Une notification est un confort ; la faire échouer reviendrait à rendre
rouge un déploiement qui a réussi.

### Un envoi raté n'est pas un déploiement raté

Même raisonnement, poussé un cran plus loin : si le serveur d'envoi refuse la connexion, le script
**avertit sur la sortie d'erreur et sort quand même en 0**. Le déploiement, lui, s'est bien passé —
c'est un fait établi cinq étapes plus tôt par le healthcheck. Laisser `curl` faire échouer `make`
apprendrait à l'équipe à se méfier d'un rouge qui ne veut rien dire.

### Le mot de passe ne passe pas par la ligne de commande

Un `curl --user "compte:motdepasse"` expose le secret dans `ps` à tout compte de la machine, le
temps de la connexion. Identifiants et destinataires passent donc par un fichier de configuration
temporaire, créé en lecture pour son seul propriétaire et effacé à la sortie.

### Le corps du message part encodé, pas en clair

Sujet en encodage d'en-tête, corps en base64. Ce n'est pas de la précaution décorative : un en-tête
d'e-mail ne transporte que de l'ASCII, donc un sujet accentué envoyé nu s'affiche abîmé chez le
destinataire ; et une ligne de corps réduite à un point unique **terminerait l'envoi au milieu du
message**, ce que l'encodage rend impossible par construction.

## Règles et cas limites

- **Le mot de passe s'écrit entre apostrophes dans le `.env`.** Le fichier est lu par un shell : un
  `$` y fait disparaître la suite de la valeur, et un guillemet nu **vide la valeur entière**. Sans
  message d'erreur : l'envoi échoue en « authentification refusée », et l'on cherche le problème du
  mauvais côté. Mesuré, cf. *Vérification*.
- **Ces variables ne sont lues par aucun compose**, donc la règle du `$` doublé qui vaut pour les
  hash de basic-auth voisins **ne s'applique pas ici**. Confondre les deux abîme le mot de passe.
- **`make preprod-notify` s'exécute seul**, sans conteneur ni reconstruction : c'est par là que se
  vérifie la configuration, en deux secondes, plutôt qu'en relançant cinq minutes de build.
- **Changer de destinataires ne demande aucun déploiement** — le script vit sur l'hôte et relit le
  `.env` à chaque exécution.
- **`DEPLOY_NOTIFY_TO` accepte un ou plusieurs destinataires**, séparés par des virgules, espaces
  tolérés, et la forme `Nom <adresse>` admise — seule l'adresse est retenue pour la livraison, sinon
  l'enveloppe partirait malformée et le serveur refuserait ce destinataire **sans que le déploiement
  le sache**, puisqu'il ne juge pas l'envoi. Une entrée sans arobase est signalée et écartée plutôt
  que de faire rejeter l'envoi entier. Tous les destinataires se voient mutuellement (en-tête `To:`).
- **L'expéditeur doit appartenir au domaine du compte.** Un serveur d'envoi refuse d'expédier au nom
  d'une adresse étrangère au compte authentifié.
- **Le script suppose un dépôt Git** sur la machine de déploiement (il lit le commit courant). C'est
  le cas : le déploiement commence par un `git pull`.
- **Changer d'hébergeur mail** = la seule variable `DEPLOY_NOTIFY_SMTP_URL`. Le script n'a rien
  d'OVH au-delà de sa valeur par défaut.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` § *Déploiement & secrets* — la chaîne du
  déploiement gagne une étape, et la raison de ne pas passer par le mailer de l'application y est
  écrite. Sans elle, la prochaine session refera une commande Symfony qui finira dans Mailpit.
- `.env.example` — cinq variables, avec l'avertissement sur les apostrophes.
- `cspell.json` — `smtps`, introduit par cette tâche.

## Définition de terminé

- [x] `make preprod-deploy` appelle `preprod-notify` en **dernière** ligne.
- [x] Le message est **reçu par un vrai serveur SMTP** : expéditeur, deux destinataires, sujet
      accentué décodé correctement, corps restitué avec ses sauts de ligne et son alignement.
- [x] Un mot de passe contenant guillemet, antislash, `$`, `%`, `&`, `#`, `!`, `;` et un accent
      arrive **intact** au serveur, vérifié en décodant ce qu'il a reçu.
- [x] `DEPLOY_NOTIFY_TO` accepte un **ou plusieurs** destinataires, avec espaces superflus et forme
      `Nom <adresse>` ; une entrée qui n'est pas une adresse est signalée sans faire échouer l'envoi.
- [x] Sans configuration, le script affiche une ligne et sort en **0**.
- [x] Serveur injoignable : avertissement sur la sortie d'erreur, sortie en **0**.
- [x] Le mot de passe n'apparaît **jamais** dans la ligne de commande.
- [x] Le piège des apostrophes dans le `.env` est **mesuré** et écrit dans `.env.example` et la doc.
- [x] `.env.example`, la documentation d'infrastructure et le code disent la même chose.

## Vérification

Sorties lues, jamais supposées. Deux serveurs SMTP ont servi de témoins : **Mailpit**, celui de la
stack de développement, qui parle un vrai protocole et décode le message reçu ; et un **serveur
minimal exigeant une authentification**, écrit pour l'occasion, qui écrit en clair les identifiants
qu'on lui présente.

| Ce qui a été lancé | Résultat |
| --- | --- |
| Envoi nominal vers Mailpit, deux destinataires | `→ notification envoyée`, **sortie 0** |
| Liste mêlant adresse nue, espaces superflus, `Nom <adresse>` et une entrée invalide | les **trois adresses** livrées, la quatrième signalée et écartée |
| Message relu par le serveur | `From: showcase-recruiter-webapp-saas <contact@showcase-recruiter-webapp-saas.example>`, **les deux destinataires**, sujet **« showcase-recruiter-webapp-saas preprod déployée — 04755a3 »** décodé sans dégât |
| Corps relu par le serveur | identique à l'original — accents, sauts de ligne et alignement des colonnes |
| Mot de passe `aB3"x\y$z%&#!;é` | reçu **intact** par le serveur d'authentification |
| Sans `DEPLOY_NOTIFY_TO` ni compte | `→ notification ignorée`, **sortie 0** |
| Serveur injoignable | `⚠ notification NON envoyée (le déploiement, lui, est bien passé)`, **sortie 0** |
| `make -n preprod-deploy` | `preprod-notify` bien en **dernière** position |

**Deux défauts trouvés par les tests, aucun par la relecture.**

1. **L'échappement des lignes suivantes.** Une version antérieure construisait le message en JSON et
   enchaînait les substitutions `sed` avant de réunir les lignes. Or un branchement revient *après*
   le label : les lignes 2 et suivantes n'étaient jamais échappées. Le corps du mail en contenant
   toujours, l'envoi aurait été refusé à chaque déploiement. Le passage en base64 a depuis supprimé
   la classe entière de ce problème.
2. **Le `.env` qui détruit le mot de passe.** Mesuré sur un dépôt jetable, avec la valeur
   `aB3x$z%25y"suite` :

   | Ligne écrite dans le `.env` | Valeur réellement lue |
   | --- | --- |
   | `P=aB3x$z%25y"suite` | **vide** |
   | `P='aB3x$z%25y"suite'` | `aB3x$z%25y"suite` |

   Le guillemet non fermé avale toute la valeur. Aucune erreur nulle part : l'envoi échoue plus loin
   en « authentification refusée », loin de sa cause. D'où l'avertissement en toutes lettres dans
   `.env.example`.

**Non vérifié, et nommé comme tel** : l'envoi réel par le serveur d'OVH depuis le VPS. Il demande le
mot de passe d'une boîte du domaine, que je n'ai pas. Ce qui reste à prouver là-bas tient en une
commande — `make preprod-notify` — et ne dépend plus du script : l'existence de la boîte, son mot de
passe, et l'ouverture du port 465 en sortie du VPS.

## Livré

- `scripts/notify-deploy.sh` (+) — envoi de la notification par SMTP. Silencieux sans configuration,
  sortie 0 même en cas d'échec d'envoi, identifiants hors de la ligne de commande, sujet et corps
  encodés.
- `Makefile.preprod` (M) — cible `preprod-notify`, appelée en dernière ligne de `preprod-deploy` ;
  l'annotation d'aide de `preprod-deploy` mentionne l'étape ajoutée.
- `Makefile` (M) — le bloc d'aide écrit à la main annonçait l'ancienne chaîne.
- `.env.example` (M) — les cinq variables, vides, avec l'avertissement sur les apostrophes.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` (M) — § *Déploiement & secrets*.
- `cspell.json` (M) — `smtps`.

## Reste à faire (hors périmètre)

- **Renseigner les cinq variables** dans le `.env` du VPS, à partir d'une boîte du domaine, puis
  vérifier par `make preprod-notify`. Tant que ce n'est pas fait, le déploiement se termine comme
  avant, sans e-mail et sans erreur.
- **La notification d'échec**, écartée au cadrage alors qu'elle porte l'essentiel de la valeur.
- **Le `MAILER_DSN` de la prod**, qui utilisera le même compte — la mise en production refusera de
  démarrer sans lui.
- **La prod** — `prod-deploy` ne notifie rien ; le script est déjà paramétré par environnement.
