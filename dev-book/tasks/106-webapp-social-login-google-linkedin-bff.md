# TASK 106 — webapp : social login Google / LinkedIn (boutons + flux OAuth BFF)

- **Créée le** : 2026-07-21.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (BFF Nitro + espace entreprise).
- **Dépend de** : tâche `081` (route API `POST /v1/auth/social/magic-link` qui vérifie l'`id_token`
  et émet le magic link — **rien côté API aujourd'hui**, à livrer d'abord).
- **Débloque** : les deux boutons « Continuer avec Google / LinkedIn » de l'écran de démarrage du
  tunnel d'inscription.
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md` (§ Workflow d'inscription).
- **Réf code** : `webapp/base/server/api/auth/magic-link.post.ts` (le proxy public à répliquer) ·
  `webapp/base/server/api/places/*.ts` (modèle BFF qui garde un secret côté Nitro) ·
  `webapp/spaces/entreprise/app/pages/inscription/index.vue` (écran de démarrage, où vivent les
  boutons) · `webapp/base/app/components/ui/Button.vue`.

## Contexte

La tâche `082` a livré le tunnel d'inscription entreprise **par e-mail** et a renvoyé les boutons
social login à la `081`. La `081` couvre la moitié API (vérification de l'`id_token`, émission du
magic link) et renvoie explicitement le front et le flux `redirect`/`callback` du BFF à la `082` —
qui est close. **La moitié webapp/BFF n'a donc aucune carte vivante.** Cette tâche la porte.

État vérifié le 2026-07-21 : aucune dépendance OIDC dans `api/composer.json`, aucune route BFF
`oauth`/`social`, aucune route API. Rien n'est commencé, des deux côtés.

## Décision d'architecture (héritée de la 081)

**Le social login récupère l'e-mail, il n'ouvre pas la session.** Google / LinkedIn → e-mail vérifié
→ **envoi d'un magic link** → authentification par le chemin magic link existant. Les deux chemins
(saisie d'e-mail et social login) aboutissent donc au **même écran « e-mail envoyé »**, puis au même
formulaire. Le social login est un raccourci de saisie, pas une seconde porte d'authentification.

**Le BFF Nitro mène la danse OAuth, le client secret vit côté serveur** — jamais dans le navigateur,
comme la clé Places. Le navigateur ne fait que suivre une redirection ; c'est Nitro qui échange le
`code` contre l'`id_token` et le présente à l'API.

## À livrer

### Routes BFF (`base/server/api/auth/social/`)

| Méthode | Route | Rôle |
| --- | --- | --- |
| GET | `/api/auth/social/[provider]/redirect` | forge l'URL d'autorisation (state + nonce, PKCE) et redirige vers le fournisseur |
| GET | `/api/auth/social/[provider]/callback` | vérifie `state`, échange le `code` contre l'`id_token`, appelle `POST /v1/auth/social/magic-link` (tâche 081), puis redirige vers l'écran « e-mail envoyé » |

- `provider` = liste **fermée** (`google` | `linkedin`) : une valeur hors liste est rejetée avant
  tout appel réseau — pas de fournisseur arbitraire injecté par l'URL.
- `state` (anti-CSRF) et `nonce` (anti-rejeu de l'`id_token`) posés en cookie httpOnly signé au
  `redirect`, revérifiés au `callback`. PKCE (`code_verifier`/`code_challenge`) de préférence à un
  secret statique côté LinkedIn si le fournisseur le supporte.
- Le `client_secret` et le `client_id` viennent du `runtimeConfig` serveur (variables
  d'environnement), **jamais** de `runtimeConfig.public`.
- L'espace appelant (`space`) accompagne la demande, comme `magic-link.post.ts`, pour que le lien
  pointe sur le bon sous-domaine.

### Écran (`spaces/entreprise/app/pages/inscription/index.vue`)

- Deux boutons « Continuer avec Google » / « Continuer avec LinkedIn » au-dessus (ou sous) le champ
  e-mail, en surcharge de l'espace entreprise — seul espace à ouvrir un tunnel d'inscription.
- Chaque bouton est un simple lien vers `/api/auth/social/[provider]/redirect` : aucun SDK
  fournisseur chargé dans la page (RGPD + CSP inchangés, comme Places).
- **Dégradation propre** : si le social login n'est pas configuré (secrets absents), les boutons ne
  s'affichent pas — le tunnel e-mail reste entier. Prévoir une route `status` (modèle
  `places/status.get.ts`) ou un flag `runtimeConfig.public`.

## Règles

- **Aucune session émise côté front** : le seul effet du parcours est l'envoi d'un magic link ; la
  session naît de sa consommation, par le chemin existant. Le BFF ne pose aucun cookie de session
  au `callback`.
- **Aucun texte en dur** : clés i18n FR / EN / AR (`inscription.start.*`), parité stricte.
- Les secrets Google / LinkedIn ne transitent **jamais** par le navigateur.
- Même écran de sortie que l'e-mail existe ou non : le parcours ne doit pas devenir un oracle de
  comptes (l'API le garantit déjà côté 081 ; le front ne doit pas l'affaiblir en distinguant les
  cas).
- Mobile-first, accessibilité clavier / labels / focus (contrainte transverse). Les boutons portent
  un libellé texte, pas seulement un logo.

## Critères de validation

- Depuis un navigateur vierge : clic « Continuer avec Google » → consentement fournisseur → retour
  sur l'écran « e-mail envoyé » → magic link reçu (Mailpit en dev) → formulaire entreprise. Idem
  LinkedIn.
- `state` altéré ou absent au `callback` → erreur exploitable, aucun magic link émis.
- Secrets non configurés → boutons absents, tunnel e-mail intact.
- Le `client_secret` n'apparaît dans aucun payload réseau côté navigateur (vérifié à l'inspecteur).
- `quality-check` vert, les 3 langues rendues.

## Hors périmètre

- Vérification de l'`id_token` et émission du magic link → tâche `081` (côté API).
- Rattachement d'un second fournisseur à un compte existant depuis le profil (V2).
- Social login pour les espaces candidat / admin : un candidat est invité, un admin est créé en
  interne — seul l'espace entreprise ouvre un tunnel public.
- Apple / Microsoft.
