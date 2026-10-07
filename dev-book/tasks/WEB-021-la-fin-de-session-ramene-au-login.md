# TASK WEB-021 — socle : la fin de session se dit et ramène au login

- **Créée le** : 2026-08-19.
- **Session** : `b631e1c6-7054-4826-9378-9e2dec851bec`.
- **Statut** : ✅ fait le 2026-08-19 — le relais nomme son refus, la garde le contrôle au rendu
  serveur et le navigateur l'intercepte après l'affichage ; la session se referme et ramène au login
  avec la page visée en mémoire.
- **Type** : fix — socle webapp (`base/` relais serveur, garde de navigation, page de connexion),
  aucun changement d'API.
- **Tests** : `vitest` sur la reconnaissance du motif de refus, code pur dans
  `webapp/services/auth/` — aux côtés de la lecture du motif de login, qu'il partage (le cadrage
  visait `webapp/shared/` ; c'est le voisinage immédiat qui a tranché). Le parcours complet — base
  remise à zéro puis retour à l'écran — se prouve à la main sur la pile de développement.
- **Réf** :
  - `WEB-004` — a livré `safeRedirectTarget` et le `?redirect=` consommé par `useLogin`, et renvoie
    explicitement ce chantier en aval dans son *Hors périmètre*
  - `webapp/base/server/utils/api.ts` — `sessionExpired`, qui détecte déjà le cas
  - `webapp/base/app/middleware/auth.global.ts` — la garde qui ne consulte que le cookie
  - `webapp/base/app/composables/useAppBootstrap.ts` — le `catch` qui uniformise toutes les pannes
  - `webapp/base/app/pages/login.vue` — porte déjà des états venus de la query
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` §5 (BFF `nuxt-auth-utils`)

## Contexte

L'application garde **deux mémoires de la connexion**, et elles ne se parlent pas : le cookie scellé
déposé par le serveur Nuxt, et le compte dans la base côté API. Le cookie est scellé par une clé qui
ne change pas au déploiement — il survit donc à une remise à zéro de la base, à la suppression d'un
compte, à la révocation d'un rôle.

La garde de navigation ne consulte que le cookie : elle laisse entrer. L'écran s'affiche avec le nom
de la personne en haut à droite. Puis l'API refuse les données, et ce refus tombe dans le même sac
que « le réseau a coupé » — le `catch` nu de `runBlocking` range session morte, API en panne et
timeout sous un seul statut `'error'`. La personne voit un bandeau rouge qui l'accuse d'un problème
de chargement, et aucun chemin ne la mène vers la seule action utile : se reconnecter.

Observé le 2026-08-19 sur `entreprise.preprod.showcase-recruiter-webapp-saas.example` après un déploiement avec base remise à
zéro : shell connecté, badge « entreprise », et « Vos recherches n'ont pas pu être chargées. »

**Ce n'est pas un défaut de préproduction.** Le même écran attend la production le jour où un compte
est désactivé ou un accès révoqué : aucun redéploiement n'est nécessaire pour le reproduire.

Le relais serveur, lui, fait déjà la moitié du chemin — `sessionExpired` détruit la session et
répond un 401 nommé. Le commentaire au-dessus de `apiFetchForUser` affirme même que « le middleware
traduit en redirection vers /login » : **aucun middleware ne fait cela**. Le commentaire décrit une
intention, pas le code.

## Périmètre

- Le relais serveur **nomme** son refus : un motif explicite (`session_expired`) voyage avec le 401,
  au lieu du seul code HTTP.
- Ce motif traverse jusqu'au navigateur pour **tous** les appels au relais — chargement d'écran comme
  geste utilisateur (créer une recherche, dévoiler un profil).
- À sa réception : la session est purgée côté navigateur, et la personne part vers `/login`.
- Au **premier affichage**, la redirection se produit **pendant le rendu serveur** : la page
  d'application n'est jamais servie.
- `/login` affiche « Votre session a expiré, reconnectez-vous », par un état porté par l'URL comme
  `?inscription` et `?social=` le sont déjà.
- Le retour sur la page visée après reconnexion réutilise `safeRedirectTarget` et le `?redirect=`
  de `WEB-004`, sans les modifier.
- Le commentaire de `apiFetchForUser` cesse de décrire un mécanisme qui n'existe pas.

## Hors périmètre

- **Le recruteur retiré de son organisation** — sa session est vivante, c'est son accès qui a
  disparu ; le déconnecter serait faux. Ce que doit voir cette personne est une question produit à
  part entière, nommée ici et non ouverte.
- **Le bandeau générique des vraies pannes** (réseau coupé, API en panne) : inchangé. Cette carte se
  contente d'extraire de ce sac le seul cas « session morte ».
- **La rotation de la clé de scellement au reset de préproduction** — écartée au cadrage : elle
  masquerait le défaut en préproduction sans rien régler en production, et ferait diverger les deux
  environnements.
- **Le flux temps réel des notifications** (`059`), qui n'existe pas encore en code.
- **La déconnexion volontaire**, qui continue d'aller sur `/login` nu (décision de `WEB-004`).

## Décisions de cadrage

### Un motif nommé, jamais le code 401 nu

Se fier au seul code HTTP déconnecterait la personne pour tout refus venu d'ailleurs — un appel mal
formé, un futur endpoint. Le dépôt sait déjà faire circuler un motif du relais vers l'écran : c'est
ce que fait `bffLoginFailure` pour le compte suspendu au login. Même mécanisme, même endroit.

### La garde contrôle la session au rendu serveur — écart au cadrage, tranché en cours de route

Le cadrage refusait d'interroger l'API à chaque entrée, jugeant la taxe permanente trop chère pour un
incident rare, et pariait sur une redirection au réveil de l'application. L'implémentation a montré
que ce pari ne tient pas partout : **un écran dont les données sont chargées pendant le rendu serveur
et jamais redemandées ensuite ne fait repartir aucun appel**, donc rien ne déclenche la fermeture. Vu
sur `/l-equipe`, qui restait sur « L'équipe n'a pas pu être chargée » à chaque rechargement — le
défaut d'origine, intact.

Deux voies gratuites ont été cherchées et écartées sur constat, pas par principe :

- le contexte de requête n'est **pas** partagé entre le document et les appels internes du rendu
  (clés distinctes), donc le relais ne peut pas marquer la requête qui l'englobe ;
- l'emplacement standard où Nuxt range les erreurs de chargement (`payload._errors`) ne conserve
  **pas** le motif — il y a `true`, rien de plus. Sans motif, impossible de distinguer une session
  close d'une panne réseau, ce que ce cadrage refuse explicitement.

Le contrôle est donc posé dans la garde, au rendu serveur uniquement : une session qui n'est plus
honorée est découverte **avant** que l'écran soit servi. Coût mesuré sur la pile de développement :
**~150 ms** par document d'espace connecté, à chaud. Le site public n'est pas concerné (la garde en
sort avant), et après l'affichage aucun contrôle n'est fait — les appels y sont enveloppés.

### Une session close ne se referme pas toute seule

Vérifié avant de coder : la purge que le relais fait déjà **n'atteint pas** le cookie du navigateur.
Émise depuis un appel interne pendant un rendu serveur, elle reste côté serveur — aucun en-tête de
suppression ne part avec la page. C'est pourquoi l'écran cassé se répétait indéfiniment plutôt qu'une
seule fois. `clear()` du module de session, lui, recopie son en-tête sur la réponse principale : la
fermeture passe donc par l'application, jamais par le seul relais.

### Un seul message, quel que soit le motif

« Votre session a expiré » couvre l'expiration, le compte supprimé et l'accès révoqué. Distinguer
les cas obligerait l'API à dire *pourquoi* elle refuse, ce qui renseigne un attaquant sur l'état
d'un compte. Si le compte est réellement suspendu, la tentative de reconnexion l'annoncera d'elle-même
— `useLogin` sait déjà le dire.

### Les écrans d'authentification et le tunnel d'inscription sont exclus

Mêmes exclusions que la garde de navigation et que `WEB-004` : `/login`, `auth-magic-link`,
`auth-define-password`, le tunnel `inscription`. Y déclencher un retour au login produirait une
boucle.

### La garde reste l'autorité

Ce mécanisme est une commodité de navigation, pas un contrôle d'accès. L'autorisation réelle reste
côté API, et la garde de rôle par espace continue de s'appliquer à l'arrivée.

## Règles et cas limites

- **Refus de droits (403)** : ne déconnecte jamais. Seul le motif de session morte le fait.
- **Plusieurs appels échouent ensemble** au chargement d'un écran : une seule redirection, pas une
  par appel.
- **Espace public** : aucune session à perdre, aucun chargement gardé — le mécanisme ne s'y applique
  pas.
- **Cookie partagé entre sous-domaines** (`.showcase-recruiter-webapp-saas.example`, cf. architecture §5) : la purge vaut pour
  tous les espaces, c'est le même cookie.
- **Message traduit** dans les trois langues du produit, jamais un texte en dur.
- **Cible de retour** : celle de la page où la session est morte, assainie par `safeRedirectTarget`
  — une cible refusée renvoie à l'accueil de l'espace, jamais hors du domaine.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` — section 5, auth : une ligne de contrat,
  un refus de session ramène au login.
- `webapp/base/server/README.md` — la précision côté relais : ce que le refus nommé signifie et qui
  le consomme.
- Le commentaire de `apiFetchForUser` dans `webapp/base/server/utils/api.ts`, qui décrit aujourd'hui
  un middleware inexistant.

## Définition de terminé

- [x] Le comportement actuel est établi : **le cookie survit** (1380 caractères, aucun en-tête de
      suppression dans la réponse), l'écran cassé se répète donc à chaque rechargement. Constat écrit
      en *Décisions de cadrage*.
- [x] Identité disparue de la base puis ouverture d'un espace → la page de connexion s'affiche, et
      l'application n'est plus rendue au nom de quelqu'un qu'elle ne connaît plus.
- [x] La page de connexion porte le message « Votre session a expiré », dans les trois langues.
- [x] La page visée est mémorisée : `/login?session=expired&redirect=/l-equipe`. Le retour après
      connexion est le mécanisme de `WEB-004`, inchangé.
- [x] Un appel parti après l'affichage ramène lui aussi au login, pas un message d'échec silencieux.
- [x] Un refus de droits (403) n'entraîne aucune déconnexion — couvert par `sessionFailure.vitest.ts`.
- [x] Une vraie panne laisse l'écran dire ce qu'il ne peut pas charger : le motif n'est pas reconnu,
      et la garde laisse entrer plutôt que de transformer une panne en déconnexion.
- [x] `vitest` vert sur la reconnaissance du motif (30 tests, `services/auth` + parité i18n).
- [x] Le commentaire de `apiFetchForUser` décrit le mécanisme réel.

## Vérification

Sur la pile de développement du worktree, dans un vrai navigateur — le mécanisme d'après-affichage ne
vit que côté navigateur, une requête en ligne de commande ne pouvait pas le prouver. L'identité du
compte de démo est retirée de la base pour reproduire ce que fait une remise à zéro.

**Avant le correctif :**

```
ecran (session close)      HTTP 200, aucune redirection
set-cookie                 aucun — le cookie de session reste intact (1380 caractères)
page servie                affiche owner@demo.showcase-recruiter-webapp-saas.example
2e chargement              identique, indéfiniment
appel au relais            HTTP 401 « Session expirée » — détecté, mais sans suite
```

**Après le correctif :**

```
1. écran profond, session saine : /l-equipe
2. identité disparue de la base
3. après rechargement — URL : /login?session=expired&redirect=/l-equipe
   message : Votre session a expiré. Reconnectez-vous pour retrouver votre espace.
```

```
cookie de session   1380 → 334 caractères (la session est vidée)
2e visite           /login?redirect=/recherches/nouvelle — l'application n'est jamais servie
appel après affichage (navigation dans l'application) → /login?redirect=/l-equipe
```

Coût du contrôle au rendu serveur, mesuré à chaud : **0,148 · 0,151 · 0,161 s**, sur un document
d'espace connecté à **~0,56 s**.

`bun run vitest run services/auth i18n` → 3 fichiers, 30 tests, verts.

## Livré

- `webapp/services/auth/sessionFailure.ts` **(+)** — le motif `session_expired` et sa
  reconnaissance, code pur.
- `webapp/services/auth/sessionFailure.vitest.ts` **(+)** — ce qui doit être reconnu, et surtout
  tout ce qui ne doit pas l'être.
- `webapp/services/auth/errorReason.ts` **(+)** — la lecture d'un motif dans un corps d'erreur,
  extraite de `loginFailure.ts` pour servir les deux sans duplication.
- `webapp/services/auth/loginFailure.ts` **(M)** — consomme le helper extrait.
- `webapp/base/server/utils/api.ts` **(M)** — le refus porte son motif ; le commentaire cesse de
  décrire un middleware qui n'existe pas.
- `webapp/base/app/composables/useSessionExpiry.ts` **(+)** — referme la session et rend l'adresse
  du login, cible mémorisée.
- `webapp/base/app/plugins/session-expiry.client.ts` **(+)** — enveloppe les appels partis du
  navigateur, y compris ceux des espaces.
- `webapp/base/app/middleware/auth.global.ts` **(M)** — contrôle la session au rendu serveur des
  pages protégées.
- `webapp/base/app/composables/useAppBootstrap.ts` **(M)** — une session close sort du lot des
  pannes de chargement.
- `webapp/base/app/pages/login.vue` **(M)** + les trois fichiers de langue — le message d'accueil.

## Reste à faire (hors périmètre)

- **Le recruteur retiré de son organisation** garde une session vivante et se voit refuser ses
  données : il n'est pas déconnecté, et rien ne lui dit quoi faire. Nommé au cadrage, non ouvert.
- **Le ton du message** reprend celui d'un échec de connexion (rouge, `text-alert`), faute d'un autre
  registre sur cet écran. Une session qui expire n'est pourtant pas une faute de la personne.
