# TASK ENT-101 — l'inscription se termine dans son espace, et non en salle d'attente

- **Créée le** : 2026-08-12.
- **Session** : `783be030-740c-4ff7-aa00-704b86f47d9b`.
- **Statut** : ✅ fait le 2026-08-12 — l'écran de fin annonce un espace prêt et l'e-mail qui vient
  de partir, la redirection minutée a disparu, et un compte suspendu est nommé comme tel à la
  connexion au lieu de passer pour une faute de frappe.
- **Type** : feat. Deux écrans changent de propos : la fin de l'inscription et la connexion refusée.
- **Tests** : vitest sur `services/auth/loginFailure.ts` — 11 cas, les deux formes du motif et tout
  ce qui n'est pas lisible. Les **écrans** restent prouvés par le parcours réel : le tunnel n'a
  aucun test front aujourd'hui, et lui en donner un est une carte à part.
- **Réf** : `API-038` — **amont, obligatoire** : elle déclenche l'e-mail à la bonne heure et fournit
  le motif de suspension · `082` — la carte fondatrice du tunnel · `149` — le nommage des composables
  d'inscription.

## Contexte

Quand un recruteur finit son inscription aujourd'hui, il tombe sur un écran qui lui dit que sa
demande est reçue et qu'elle sera examinée. **Quinze secondes plus tard, l'écran le renvoie de
lui-même à la page de connexion** — sur laquelle il ne peut pas se connecter, puisqu'il n'a pas
encore de mot de passe. Aucun e-mail ne l'attend : celui qui lui permettra d'entrer ne partira que
lorsqu'un administrateur aura cliqué, à un moment que personne ne lui annonce.

`API-038` supprime cette attente : l'e-mail part maintenant à la fin du formulaire, et l'accès est
immédiat. L'écran de fin doit donc dire l'inverse de ce qu'il dit — non plus « patientez », mais
« c'est prêt, ouvrez votre boîte mail ».

Le second manque est le symétrique : quand une entreprise est refusée, la personne se retrouvera
devant un écran de connexion qui la rejette. **Sans explication, elle croira s'être trompée de mot de
passe** et recommencera.

## Périmètre

- **L'écran de fin d'inscription** annonce que l'espace est prêt et qu'un e-mail vient de partir pour
  choisir son mot de passe. Il nomme l'adresse à laquelle il a été envoyé.
- **La redirection automatique au bout de quinze secondes disparaît** : elle envoyait vers une page
  inutilisable, et elle chassait l'écran avant qu'on ait fini de le lire.
- **L'écran de connexion sait dire « compte suspendu »** — un message distinct de l'échec
  d'identifiants, qui explique et donne un moyen de nous joindre.
- **Les trois langues** sont servies : français, anglais, arabe.

## Hors périmètre

- **Tout ce que fait le serveur** — moment d'envoi de l'e-mail, ouverture des accès, effet du refus :
  c'est `API-038`.
- **Le formulaire lui-même** — ses champs, son découpage en trois temps, sa jauge : inchangés.
- **La page de définition du mot de passe** et la connexion automatique qui la suit, qui
  fonctionnent déjà et ne changent pas.
- **Les textes des e-mails**, écrits côté serveur.
- **La file de revue de l'administrateur**, qui ne bouge pas.

## Décisions de cadrage

### L'écran de fin cesse d'être une salle d'attente

Il gardait la personne devant une promesse — « nous examinons votre demande » — et la lâchait dans le
vide. Il devient un **relais** : il dit ce qui vient de partir, où, et ce qu'il faut faire ensuite.
Nommer l'adresse destinataire n'est pas décoratif : c'est ce qui permet de repérer une faute de
frappe avant d'aller fouiller ses spams.

### Plus de redirection automatique

Une redirection minutée n'a de sens que si elle mène quelque part d'utile. Ici elle menait à une page
de connexion inutilisable, et elle imposait un compte à rebours à un écran qu'on doit avoir le temps
de lire. La personne reste sur cet écran tant qu'elle le souhaite.

### Un compte suspendu se dit, il ne se devine pas

Refuser sans motif est le comportement le moins coûteux à écrire et le plus coûteux à vivre : la
personne réessaie, doute de son mot de passe, le réinitialise, échoue encore. Le message doit dire
que le compte est suspendu **et** ouvrir un recours — un refus injustifié doit pouvoir remonter.

⚠️ **Ce message ne dit pas pourquoi.** Le motif du refus appartient à l'administrateur, et l'exposer
renseignerait aussi les inscriptions de mauvaise foi sur ce qui les a trahies.

## Règles et cas limites

- **Session en cours au moment du refus** — les requêtes commencent à être refusées ; la personne
  doit se retrouver sur l'écran de connexion avec le message, pas devant une erreur technique.
- **Entreprise refusée puis validée** — la personne se reconnecte normalement, sans rien de
  particulier à afficher.
- **E-mail jamais reçu** — l'écran de fin doit dire quoi faire (vérifier les indésirables, redemander
  un lien) plutôt que laisser la personne sans issue.
- **Retour en arrière après l'envoi** — le formulaire est parti, l'organisation existe : revenir
  dessus ne doit pas proposer de le renvoyer.
- **Coque d'inscription** — l'écran vit dans le layout `inscription`, sans langue, ni thème, ni
  déconnexion : le message doit tenir sans ces réglages.

### ⚠️ Deux écarts au cadrage, tranchés pendant l'implémentation

- **Le motif de refus voyage sous deux formes, donc deux lectures.** La carte disait « l'écran de
  connexion sait dire compte suspendu » sans dire comment le motif y arrivait. Le BFF avalait toute
  erreur dans un `Invalid credentials` unique : il fallait un relais. L'API nomme le cas dans
  `message`, le BFF le range dans `data.reason` — d'où un service à deux entrées plutôt qu'une, et
  un type partagé pour que le motif ne se retape pas en chaîne de caractères à chaque étage.
- **Le message n'ouvre pas de recours cliquable.** « Écrivez-nous » n'est suivi d'aucune adresse :
  le produit n'a **aucun point de contact** en place, et en inventer un dépassait le périmètre. Voir
  *Reste à faire*.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md`** — « entrer se fait en quatre
  temps, et on ne choisit son mot de passe qu'à la fin », devenu faux.
- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/services-standard.md`** — le nouveau domaine `auth/` dans
  l'arborescence des services.

## Risques

- **Le fichier de traductions est partagé.** Trois autres sessions écrivent dans `webapp/base/i18n/`
  en ce moment. Ajouter des clés plutôt que réorganiser, et relire ce qui est mis en attente de
  commit avant de figer.

## Définition de terminé

- [x] L'écran de fin d'inscription annonce un espace prêt et un e-mail parti, en nommant l'adresse.
- [x] Plus aucune redirection automatique depuis cet écran.
- [x] Une connexion refusée pour suspension affiche un message distinct. ⚠️ **Le recours est nommé,
      pas cliquable** — aucun point de contact n'existe dans le produit ; cf. *Reste à faire*.
- [x] Les trois langues sont servies.
- [x] La page d'architecture ne décrit plus un parcours en quatre temps.

## Vérification

### Le parcours réel, dans un navigateur, sur la stack de la lane

Inscription neuve menée de bout en bout — lien demandé, e-mail relevé, lien consommé, formulaire
rempli en trois temps, envoyé. Ce que l'écran affiche alors :

```
Votre espace est prêt
Il ne manque que votre mot de passe.
Un e-mail vient de partir à ent101-…@example.test. Ouvrez-le pour choisir
votre mot de passe : vous entrerez juste après.
Notre équipe vérifie les informations de votre entreprise de son côté.
Vous n'avez rien à attendre pour commencer.
Vous ne le trouvez pas ? Regardez dans vos courriers indésirables.
```

**La redirection minutée a disparu** : elle partait à 15 s, l'écran a été tenu 18 s et l'adresse
n'a pas bougé — `…/inscription/entreprise`, inchangée.

### Le motif de refus, à la connexion

Mot de passe faux sur un compte réel — rien n'a régressé :

```
réponse du BFF   401  {"statusCode":401,"data":{"reason":"invalid_credentials"}}
écran            « Identifiants invalides »
```

Cas suspendu — **l'API de cette lane ignore `API-038`**, un vrai compte suspendu n'y est pas
produisible. La réponse que le BFF composera a été rejouée au niveau du navigateur, ce qui prouve le
dernier maillon :

```
réponse simulée  401  {"statusCode":401,"data":{"reason":"account_suspended"}}
écran            « Votre compte est suspendu. Écrivez-nous pour en connaître la raison
                   et demander un réexamen. »
URL              reste /login
```

Le maillon API → BFF est couvert par les tests ci-dessous. Il ne sera vérifiable en conditions
réelles qu'une fois `API-038` mergée.

### Les tests

```
$ bunx vitest run services/auth/loginFailure.vitest.ts
  Test Files  1 passed (1)
       Tests  11 passed (11)
```

## Livré

| Fichier | Geste |
|---|---|
| `webapp/services/auth/loginFailure.ts` | (+) le motif d'un refus, lu côté BFF puis côté navigateur |
| `webapp/services/auth/loginFailure.vitest.ts` | (+) 11 cas, dont tout ce qui n'est pas lisible |
| `webapp/shared/types/auth.ts` | (+) le motif comme type partagé, plutôt qu'une chaîne retapée à chaque étage |
| `webapp/spaces/entreprise/app/pages/inscription/entreprise.vue` | (M) l'écran de fin relaie vers l'e-mail ; la redirection minutée retirée |
| `webapp/base/server/api/auth/login.post.ts` | (M) le BFF relaie le motif au lieu de l'avaler |
| `webapp/base/app/composables/useLogin.ts` | (M) choisit le message selon le motif |
| `webapp/base/i18n/locales/{fr,en,ar}.yaml` | (M) l'écran de fin réécrit, `auth.accountSuspended` ajouté, et la page de définition du mot de passe qui n'annonce plus une validation qui n'a pas eu lieu |
| `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` | (M) le parcours d'entrée n'a plus quatre temps |
| `docs/showcase-recruiter-webapp-saas/content/2.architecture/services-standard.md` | (M) le domaine `auth/` dans l'arborescence |

## Reste à faire (hors périmètre)

- **Le message de suspension n'ouvre aucun recours cliquable.** Le produit n'a pas de point de
  contact : ni adresse affichée, ni page de contact. Trois issues possibles — une adresse en dur,
  une page à créer, ou laisser le recours passer par le canal habituel. À trancher, ça dépasse cette
  carte.
- **Le tunnel d'inscription n'a toujours aucun test front.** Trois écrans et un formulaire en trois
  temps ne sont prouvés que par un parcours joué à la main. Une carte à part.
- **La chaîne complète de suspension n'est pas vérifiée en conditions réelles** : elle attend le
  merge d'`API-038`. À rejouer à ce moment-là — un vrai refus, une vraie reconnexion.
- **Le lien de mot de passe expire en 30 minutes**, et l'écran de fin ne permet pas d'en redemander
  un. Le manque est réel mais le chemin n'existe pas côté serveur ; il appartient à la lane API.
