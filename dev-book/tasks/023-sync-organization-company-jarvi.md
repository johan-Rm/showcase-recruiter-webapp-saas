# TASK 023 — Sync Organization showcase-recruiter-webapp-saas vers Company Jarvi

- **Créée le** : 2026-06-25.
- **Statut** : implémentée à relire (2026-06-25) — tests ciblés ajoutés, non exécutés dans cette
  intervention.
- **Type** : intégration sortante Jarvi, déclenchée par l'onboarding entreprise.
- **Dépend de** : tâche `004` (`POST /v1/organizations` déjà en place) + contrats Jarvi
  `POST /rest/v2/companies` / `POST /rest/v2/projects` + workflow d'inscription entreprise par
  Magic Link (tâche `024`).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
  - `dev-book/cadrage/entreprise/api/inscription.md`
  - `https://api-docs.jarvi.tech/fr/api-reference/companies/post`

## Contexte

showcase-recruiter-webapp-saas doit rester maître de l'entreprise cliente et de ses membres. Jarvi ne reçoit pas les
membres recruteurs showcase-recruiter-webapp-saas comme `users` ni comme `profiles`.

Le workflow cible d'inscription entreprise passe par Magic Link, collecte des informations
entreprise, puis revue admin avant accès complet. Le domaine e-mail n'est pas une preuve : toute
adresse est acceptée, mais l'entreprise est validée manuellement.

Le besoin immédiat est de préparer le test complet du parcours :

1. authentification du déclarant par Magic Link ;
2. création locale de l'`Organization` ;
3. création de l'admin entreprise local (`Recruiter` avec `ROLE_COMPANY_OWNER`) ;
4. wallet + crédits offerts ;
5. statut local `EN_REVUE` tant que l'admin n'a pas validé ;
6. émission d'un lien de définition de mot de passe pour le déclarant après validation ;
7. création/upsert d'une `company` Jarvi correspondant à l'`Organization` seulement au bon moment ;
8. rattachement du `companyId` au projet recruteur Jarvi configuré à l'avance.

Le flux local actuel de `POST /v1/organizations` couvre déjà :

1. création locale de l'`Organization` ;
2. création de l'admin entreprise local (`Recruiter` avec `ROLE_COMPANY_OWNER`) ;
3. wallet + crédits offerts ;

Il manque le cycle de revue décrit dans `dev-book/cadrage/entreprise/api/inscription.md`.

⚠️ **Aucun appel réel Jarvi ne doit être lancé sans feu vert explicite.**

## État existant à préserver ou faire évoluer

### Observé dans le code actuel

- `POST /v1/organizations` existe déjà via `OrganizationOutput` / `OrganizationProcessor`.
- `OrganizationInput` accepte aujourd'hui : `name`, `legalName`, `taxId`, `country`, `city`,
  `billingAddress`, `website`.
- `OrganizationCreator` crée immédiatement, dans une transaction :
  - `Organization` ;
  - `Recruiter` `ROLE_COMPANY_OWNER` lié au `Person` courant ;
  - `CreditWallet` ;
  - `CreditTransaction` de bienvenue ;
  - ajout du rôle `ROLE_COMPANY_OWNER`.
- `OrganizationOutput` expose désormais le statut de validation et les champs de suivi Jarvi company.
- `OrganizationTest` a été révisé : création en `EN_REVUE`, accès sensible refusé avant validation,
  validation/refus admin.
- La validation admin émet aussi un token de définition de mot de passe pour le déclarant
  `ROLE_COMPANY_OWNER`; l'authentification classique prend le relais après consommation de ce token.
- Le cycle local `NOUVEAU` / `EMAIL_VERIFIE` / `INCOMPLET` / `EN_REVUE` / `REFUSE` / `VALIDEE` est
  représenté par `OrganizationValidationStatus`.

### À préserver

- La règle V1 `1 Recruiter = 1 Organization`.
- L'isolation multi-tenant par `OrganizationVoter`.
- Le pattern `Input DTO → State Processor → Domain service → Entity`.
- La création atomique locale `Organization + admin entreprise + wallet + grant`.
- Le fait que les membres showcase-recruiter-webapp-saas restent locaux et ne deviennent ni `users` ni `profiles` Jarvi.

### À modifier

- `POST /v1/organizations` ne doit plus être interprété comme une activation complète immédiate :
  il doit créer une entreprise locale en attente de revue (`EN_REVUE` si le formulaire est complet,
  `INCOMPLET` si le parcours est repris plus tard).
- `Organization` doit porter un statut de validation métier exposé dans `OrganizationOutput`.
- Les accès sensibles aux profils candidats, crédits consommables et recherche entreprise doivent
  être conditionnés à `VALIDEE` si ce n'est pas déjà garanti ailleurs.
- La sync Jarvi company doit être déclenchée par la validation admin, pas par la simple création
  locale.
- La validation admin doit aussi préparer l'accès durable du déclarant via un lien de définition de
  mot de passe ; aucun mot de passe temporaire n'est envoyé.
- Les tests fonctionnels existants de `OrganizationTest` doivent être révisés :
  - création locale attendue en `EN_REVUE` ;
  - admin entreprise local toujours créé ;
  - accès complet non accordé tant que l'entreprise n'est pas validée ;
  - aucun appel Jarvi tant que le statut n'est pas `VALIDEE`.

### À supprimer ou considérer obsolète

- Toute hypothèse produit selon laquelle une entreprise créée par `POST /v1/organizations` est
  immédiatement validée.
- Tout test ou doc qui assimile `créée` à `autorisée à accéder aux profils candidats`.
- Tout déclenchement synchrone Jarvi placé directement dans `OrganizationCreator::create`.
- Tout envoi des membres recruteurs showcase-recruiter-webapp-saas vers Jarvi comme contacts, `users`, `assignees` ou
  `profiles`, tant que Jarvi ne fournit pas un contrat dédié.

## Décision V1

- showcase-recruiter-webapp-saas `Organization` → Jarvi `company`.
- showcase-recruiter-webapp-saas `Recruiter` / membre d'organisation → reste uniquement showcase-recruiter-webapp-saas.
- Jarvi `assignees` n'est pas utilisé pour le membre qui remplit le formulaire, sauf s'il est
  explicitement mappé à un user Jarvi existant dans une décision ultérieure.
- Jarvi `profiles` reste réservé aux candidats/profils sourcés.
- `linkedinUrl` est un plus, jamais obligatoire.
- Le rattachement au projet se fait par mise à jour Jarvi `POST /projects` avec
  `projectId = JARVI_RECRUITER_PROJECT_ID` et `companyId`.
- La sync Jarvi ne doit pas partir sur une entreprise `INCOMPLET` ou `REFUSÉ`.
- Déclenchement recommandé : après validation admin (`ENTREPRISE_VALIDÉE`), pas dès le formulaire
  initial, pour éviter de polluer Jarvi avec des sociétés non légitimes.

## Données entreprise partagées showcase-recruiter-webapp-saas ↔ Jarvi

En V1, le partage doit rester minimal et explicite :

| Donnée | showcase-recruiter-webapp-saas | Jarvi | Décision |
| --- | --- | --- | --- |
| Nom transmis à Jarvi | `legalName` si disponible, sinon `name` | `company.name` | ✅ envoyé à la validation admin |
| Identifiant company Jarvi | `Organization.jarviCompanyId` | `companyId` retourné par Jarvi | ✅ stocké côté showcase-recruiter-webapp-saas |
| Projet recruteur cible | config `JARVI_RECRUITER_PROJECT_ID` | `projectId` dans `POST /projects` | ✅ utilisé pour rattacher la company au projet |
| LinkedIn entreprise | champ à ajouter plus tard si collecté | `linkedinUrl` | 🔭 optionnel, non envoyé tant que non collecté |
| E-mail déclarant / owner | `User.email` / `Person.contactEmail` | — | ❌ reste showcase-recruiter-webapp-saas en V1 |
| E-mail contact entreprise | champ dédié à cadrer si besoin | contrat Jarvi à confirmer | 🔭 ne pas envoyer tant qu'il n'existe pas de champ Jarvi dédié |
| Membres / recruteurs | `Recruiter` | — | ❌ jamais envoyés comme `users`, `profiles` ou `assignees` |

L'e-mail ne doit pas être glissé dans `description` ou un champ libre : c'est une donnée personnelle
du déclarant ou du contact entreprise. S'il devient nécessaire côté Jarvi, il faudra un champ dédié,
un consentement/usage clair, et un contrat Jarvi explicite.

## Payload Jarvi cible

Payload company minimal propre :

```json
{
  "name": "Entreprise ABC"
}
```

Payload company enrichi si LinkedIn existe :

```json
{
  "name": "Entreprise ABC",
  "linkedinUrl": "https://www.linkedin.com/company/entreprise-abc"
}
```

Puis rattachement au projet Jarvi défini à l'avance :

```json
{
  "projectId": "00000000-0000-4000-8000-000000000003",
  "companyId": "company-id-retourne-par-jarvi"
}
```

## Périmètre

- Ajouter un champ de persistance côté showcase-recruiter-webapp-saas pour stocker l'identifiant Jarvi retourné
  (`jarviCompanyId` ou équivalent) sur `Organization`.
- Ajouter si utile des champs de suivi de sync :
  `jarviCompanySyncStatus`, `jarviCompanySyncedAt`, `jarviCompanySyncError` (noms à aligner avec
  les conventions existantes de `SyncStatus`).
- Ajouter le statut de validation entreprise côté showcase-recruiter-webapp-saas si absent :
  `NOUVEAU` / `EMAIL_VERIFIE` / `INCOMPLET` / `EN_REVUE` / `REFUSE` / `VALIDEE`
  (noms exacts à aligner avec les enums du projet).
- Introduire un client/port dédié à l'écriture Jarvi `Company`, séparé du writer `Profile`.
- Mapper `Organization` vers le payload Jarvi :
  - `name` ← `Organization.name`
  - `linkedinUrl` ← champ optionnel à ajouter plus tard si le formulaire le collecte
- Après création de la company Jarvi, rattacher `companyId` au projet `JARVI_RECRUITER_PROJECT_ID`
  via `POST /projects`.
- Brancher la sync sur la transition admin vers `VALIDEE`, avec possibilité de mode test contrôlé
  pour simuler le parcours complet sans appel réseau réel.
- Brancher l'émission du lien de définition de mot de passe sur cette même transition vers `VALIDEE`,
  sans la coupler à la réussite Jarvi.
- Prévoir une exécution testable sans appel réseau réel : mock HTTP / fake writer / mode dry-run.
- Retourner l'`OrganizationOutput` local même si la sync Jarvi est désactivée.
- Mettre à jour les routes/admin nécessaires à la transition de validation si elles n'existent pas
  encore (ex. action admin `validate` / `reject`, nom exact à cadrer dans la tâche d'admin).

## Hors périmètre

- Création de users Jarvi.
- Création de profiles Jarvi pour les membres d'entreprise.
- Sync des membres showcase-recruiter-webapp-saas vers Jarvi.
- Création dynamique d'un nouveau project Jarvi par entreprise.
- Gestion fine des statuts Jarvi `statusId`.
- Écriture de custom fields Jarvi tant que le contrat n'est pas confirmé.
- Déclenchement réel en production sans validation manuelle.
- Social Login Google / LinkedIn complet (hors contrat de sync Jarvi).
- Magic Link, e-mail local et reprise d'inscription incomplète → tâche `024`.
- Refonte complète de l'auth entreprise existante : cette tâche ne doit introduire que les contrats
  nécessaires au parcours d'inscription et au déclenchement Jarvi.

## Garde-fous

- Clé Jarvi privée côté `api/` uniquement.
- Feature flag obligatoire, désactivé par défaut, par exemple `JARVI_COMPANY_SYNC_ENABLED=false`.
- Projet recruteur Jarvi configuré via `JARVI_RECRUITER_PROJECT_ID`
  (`00000000-0000-4000-8000-000000000003` en dev/test).
- En `test`, aucun appel HTTP réel : double de test obligatoire.
- En `dev`, le passage réel est autorisé après feu vert explicite, avec `JARVI_COMPANY_SYNC_ENABLED=true`,
  une clé privée Jarvi locale non versionnée, et le projet de test
  `00000000-0000-4000-8000-000000000003`.
- Une entreprise `INCOMPLET`, `EN_REVUE` ou `REFUSE` ne doit pas être envoyée vers Jarvi par défaut.
- En cas d'échec Jarvi :
  - ne pas perdre l'`Organization` locale ;
  - tracer l'échec ;
  - marquer un statut de sync si le modèle est ajouté ;
  - prévoir un retry manuel ou asynchrone.

## Questions à confirmer

- Sans `externalId`, un échec entre création company et persistance locale pourrait créer un doublon
  lors d'un retry manuel. Accepté en V1 à confirmer au premier test réel.
- La donnée d'identité commune aux deux bases est le nom transmis à Jarvi : `legalName` si disponible,
  sinon `name`.
- Après validation, `name` / `legalName` ne sont plus modifiables via le `PUT` standard ; un
  changement de raison sociale devra repasser par un workflow dédié de revue/validation.
- La réponse `companyId` est persistée telle quelle en V1.
- Décidé V1 : pas de company Jarvi au `POST /v1/organizations`; déclenchement sur validation admin.
- Décidé V1 : rattacher chaque company au projet recruteur Jarvi défini à l'avance ; ne pas créer un
  project par company.
- Décidé V1 : le wallet et les crédits sont créés dès `EN_REVUE`, mais la consommation est bloquée
  par `OrganizationVoter::ACCESS` jusqu'à `VALIDEE`.
- Décidé V1 : après validation, le déclarant définit son mot de passe via
  `POST /v1/auth/password-setup/consume`, puis utilise `POST /v1/auth/login`.
- Endpoints ajoutés : `POST /v1/organizations/{id}/validate` et
  `POST /v1/organizations/{id}/reject`.

## Critères d'acceptation

- [x] `POST /v1/organizations` continue de créer localement l'Organization, l'admin entreprise, le wallet et les
  crédits offerts.
- [x] Le comportement obsolète "créée = validée" est retiré des tests, docs et hypothèses de routes.
- [x] Le cycle d'inscription Magic Link → infos entreprise → `EN_REVUE` → validation admin est
  représenté côté modèle ou explicitement couvert par la tâche `024`.
- [x] La validation admin émet le lien de définition de mot de passe du déclarant sans exposer de mot
  de passe temporaire.
- [x] Une Organization non validée ne déclenche pas de sync Jarvi.
- [x] La validation admin déclenche la sync Jarvi company en mode activé.
- [x] La sync Jarvi company est isolée derrière une interface testable.
- [x] Les tests fonctionnels couvrent le mode désactivé : aucun appel Jarvi, comportement local inchangé.
- [x] Les tests couvrent le mode activé avec fake writer : payload company `{name}` correct et
  rattachement projet `{projectId, companyId}`.
- [x] `jarviCompanyId` est persisté quand le fake writer retourne un identifiant.
- [x] Un échec Jarvi ne casse pas la création locale, ou bien le choix inverse est documenté et testé.
- [x] Aucun membre showcase-recruiter-webapp-saas n'est envoyé comme `profile` Jarvi.
- [x] Aucun appel réel Jarvi n'est exécuté pendant les tests automatiques.

## Validations prévues

- Tests ciblés `OrganizationTest` / service de sync Jarvi company ajoutés, à exécuter avant PR.
- Mise à jour faite des docs impactées :
  - `dev-book/tasks/004-espace-entreprise-onboarding.md` : préciser que la création n'implique plus
    l'accès complet et que le mot de passe est défini après validation ;
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` : statut de validation Organization ;
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` : route/admin de validation si ajoutée ;
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md` : mapping Organization → company.
- Avant PR : quality gates API du projet (`make api-quality` ou équivalent disponible).
