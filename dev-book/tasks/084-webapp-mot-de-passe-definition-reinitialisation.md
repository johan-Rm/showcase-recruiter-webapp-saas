# TASK 084 — webapp : mot de passe (définition, réinitialisation, changement)

- **Créée le** : 2026-07-13.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (socle, cross-cutting : tous rôles).
- **Dépend de** : tâches `037` (auth/BFF) · `070` (kit `ui/`) · `069` (règles de validation).
- **Complète** : tâche `082` (le tunnel d'inscription se termine ici) · tâche `025b` (routes API —
  **déjà livrées** : `PUT /v1/me/password`, `POST /v1/auth/password-reset/request` et
  `/consume`, `password-setup/consume` ; le statut « à faire » de `025b` est périmé, à corriger).
- **Réf code** : `webapp/base/app/pages/login.vue` · `api/src/ApiResource/Output/PasswordSetupOutput.php`
  · `PasswordResetOutput.php` · `ChangePasswordOutput.php`.

## Contexte

L'API sait définir, réinitialiser et changer un mot de passe. **Aucun écran ne le fait.** Un
recruteur dont l'entreprise vient d'être validée reçoit un lien de définition de mot de passe qui
ne mène nulle part, et un utilisateur qui oublie son mot de passe n'a aucun recours. C'est le socle
qui manque, pas une fonctionnalité d'espace : ces écrans vivent dans `base/`, partagés par les
quatre espaces.

## Écrans à livrer (`base/app/pages/`)

1. **Définir mon mot de passe** — atterrissage du lien à usage unique reçu après validation de
   l'entreprise (`password-setup`). Consomme le token, puis bascule sur le login.
2. **Mot de passe oublié** — saisie de l'e-mail, écran de confirmation d'envoi.
3. **Réinitialiser** — atterrissage du lien de réinitialisation.
4. **Changer mon mot de passe** — écran authentifié (mot de passe actuel + nouveau).

Plus le lien « mot de passe oublié » depuis `login.vue`, qui n'existe pas.

## Routes BFF à ajouter (`base/server/api/`)

Proxys des quatre routes API. Le token du lien e-mail ne doit **jamais** être stocké côté client :
il est consommé une fois, la session qui en résulte reste en cookie BFF.

## Règles

- **Message identique que l'e-mail existe ou non** sur la demande de réinitialisation : sinon
  l'écran devient un oracle qui énumère les comptes de la plateforme.
- Un token expiré ou déjà consommé donne un message clair et un chemin de sortie (en redemander
  un), jamais une page morte.
- Les règles de robustesse du mot de passe viennent de l'API (tâche `069`) — pas de seconde vérité
  côté front.
- Aucun texte en dur (i18n FR / EN / AR), mobile-first, accessibilité clavier.

## Critères de validation

- Une entreprise validée suit son lien, définit son mot de passe, se connecte par
  `login` → accès complet. C'est la sortie du tunnel de la tâche `082`, testée de bout en bout.
- Réinitialisation complète depuis un compte existant (Mailpit en dev).
- Changement de mot de passe depuis une session : l'ancien mot de passe ne fonctionne plus.
- `quality:check` vert, 3 langues.

## Hors périmètre

- La double authentification (V2).
- Le rattachement d'un fournisseur social à un compte existant (→ hors périmètre de `081` aussi).
