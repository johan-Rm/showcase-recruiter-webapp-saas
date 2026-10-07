# TASK 082 — webapp : tunnel d'inscription entreprise (front)

- **Créée le** : 2026-07-13.
- **Statut** : ✅ livré le 2026-07-13 (hors boutons social login → `081`). Vérifié de bout en bout :
  e-mail → magic link (Mailpit) → formulaire → `EN_REVUE` → visible dans la file de revue admin.
- **Type** : Front — Webapp Nuxt (espace entreprise, première surface produit).
- **Dépend de** : tâches `037` (auth/BFF) · `070` (kit `ui/`) · `081` (social login, pour les deux
  boutons — le tunnel e-mail fonctionne sans) · `069` (règles de validation exposées par l'API).
- **S'articule avec** : tâche `083` (file de revue admin — l'autre bout du parcours) · tâche `084`
  (définition du mot de passe — l'écran de sortie du tunnel).
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md`.
- **Réf code** : `webapp/base/app/pages/login.vue` · `webapp/base/server/api/auth/` ·
  `webapp/spaces/entreprise/`.

## Contexte

L'API sait déjà inscrire une entreprise : magic link, `POST /v1/organizations` (statut `EN_REVUE`,
wallet + crédits offerts), validation admin, lien de définition du mot de passe. **Le front n'a
rien** : la webapp n'expose qu'une page `login`. Une entreprise ne peut aujourd'hui s'inscrire par
aucun moyen — le tunnel décrit au cadrage n'a jamais été construit.

Le tunnel vit sur le **sous-domaine entreprise** (comme le login) ; la vitrine publique s'y
contente de pointer.

## Écrans à livrer (`spaces/entreprise/app/pages/inscription/`)

1. **Démarrer** — saisie de l'e-mail, plus « Continuer avec Google » / « Continuer avec LinkedIn »
   (tâche `081`). Toutes les adresses sont acceptées, y compris personnelles : ce qui est vérifié,
   c'est l'entreprise, pas le domaine de l'e-mail.
2. **E-mail envoyé** — écran d'attente, sans faux espoir : indique quoi faire si le message
   n'arrive pas (renvoyer, corriger l'adresse). **Les deux chemins y mènent** : le social login
   récupère l'e-mail et déclenche le même magic link (décision du 2026-07-13, cf. `081`), il ne
   court-circuite pas la boîte mail.
3. **Atterrissage magic link** — consomme le token, ouvre la session, redirige vers le formulaire.
   Token expiré ou déjà consommé → message clair + moyen de recommencer, jamais une page morte.
4. **Informations entreprise** — le formulaire du cadrage : raison sociale, pays, site web
   (optionnel), numéro d'immatriculation (ICE / RC), secteur d'activité, fonction du recruteur,
   **provenance** (liste fermée + précision libre obligatoire si « Autre »), description
   (optionnelle).
5. **En revue** — écran d'attente après soumission : le compte existe, l'accès viendra après
   validation. C'est le seul état terminal du tunnel côté entreprise.

## Routes BFF à ajouter (`base/server/api/`)

`auth/magic-link.post.ts` (demande), `auth/magic-link/consume.post.ts` (pose les cookies de
session), `organizations.post.ts` (proxy authentifié de la création). Le cookie de session ne doit
**jamais** transiter par le navigateur : le BFF reste le seul détenteur du JWT, comme pour le login.

## Règles

- **Aucun texte en dur** : clés i18n FR / EN / AR, comme le reste de la webapp.
- **Validation d'abord côté API** : les règles viennent de la tâche `069` — le front ne
  ré-implémente pas la vérité métier, il affiche les erreurs renvoyées.
- **Reprise possible** : un formulaire abandonné laisse l'entreprise en `INCOMPLET` ; revenir sur
  le tunnel avec une session valide doit rouvrir le formulaire, pas en recréer un.
- Mobile-first, accessibilité clavier / labels / focus (contrainte transverse).
- Le tunnel est **public** : il ne doit pas être happé par le middleware qui exige une session.

## Critères de validation

- Une entreprise s'inscrit de bout en bout depuis un navigateur vierge : e-mail → magic link
  (Mailpit en dev) → formulaire → écran « en revue », `Organization` en base au statut `EN_REVUE`
  avec son wallet.
- Le parcours social login aboutit au même écran « e-mail envoyé », puis au même formulaire.
- Magic link expiré → écran d'erreur exploitable.
- `quality:check` vert, les 3 langues rendues.

## Deux blocages API découverts en testant (corrigés dans la foulée)

Le tunnel ne pouvait **pas** fonctionner, et ça ne se voyait pas sur le papier :

1. **Une adresse inconnue ne recevait aucun lien.** `MagicLinkIssuer::request()` sortait en silence
   si l'e-mail n'était pas déjà en base — or un déclarant qui s'inscrit est inconnu **par
   définition**. Corrigé : le lien part toujours, mais **le compte n'est créé qu'à la consommation**
   (décision du 2026-07-13), quand la personne a prouvé qu'elle possède la boîte. Une campagne
   d'envois sur la route publique ne peut donc pas peupler la table des comptes.
2. **Un seul `APP_BASE_URL` pour tous les espaces.** Depuis les layers, chaque espace a son host : le
   lien d'un candidat doit mener sur `candidat.`, celui d'un déclarant sur `entreprise.`. Ajout de
   `APP_CANDIDATE_BASE_URL` / `APP_COMPANY_BASE_URL`, et la demande de lien porte désormais l'espace
   appelant (`space`, **liste fermée** — une URL de retour fournie par le client ouvrirait une
   redirection arbitraire depuis un e-mail signé showcase-recruiter-webapp-saas).

## Livré

- Écrans (`spaces/entreprise/app/pages/inscription/`) : démarrer + « e-mail envoyé » (`index.vue`),
  formulaire entreprise, « en revue ». L'**atterrissage du lien vit dans le socle**
  (`base/app/pages/auth/magic-link.vue`) : c'est le chemin construit par l'API, et le candidat
  l'utilisera aussi.
- Formulaires en **composants** (`RegistrationEmailForm`, `RegistrationCompanyForm`) : la convention
  du projet veut qu'une page orchestre et que l'interactif vive dans un composant.
- Routes BFF : demande et consommation du magic link, création de l'entreprise, référentiel des
  secteurs. Le JWT ne quitte jamais le serveur.
- Le middleware d'auth **laisse passer le tunnel** : le déclarant n'a pas encore de rôle entreprise
  (il l'obtient en créant l'entreprise), la garde de rôle l'aurait renvoyé au login — inscription
  impossible.
- Lien « Inscrire mon entreprise » depuis le login, en **surcharge de layer** (seul l'espace
  entreprise ouvre un tunnel : un candidat est invité, un admin est créé en interne).

## Reste

- Boutons « Continuer avec Google / LinkedIn » → tâche `081` (rien côté API aujourd'hui).
- **Pas de refresh token sur le chemin magic link** : la session du déclarant vit le temps du JWT
  (1 h). Suffisant pour s'inscrire ; à revoir si le formulaire s'allonge.
- Reprise d'un formulaire abandonné (`INCOMPLET`) : le statut existe côté API, aucun écran ne le
  reprend encore.

## Hors périmètre

- File de revue admin (→ `083`), définition du mot de passe (→ `084`).
- Invitation d'un membre d'équipe : parcours distinct, déjà cadré
  (`cadrage/entreprise/api/invitation_membre.md`, tâches `009` / `031`).
- Le push Jarvi à la validation (côté API, tâche `023`).
