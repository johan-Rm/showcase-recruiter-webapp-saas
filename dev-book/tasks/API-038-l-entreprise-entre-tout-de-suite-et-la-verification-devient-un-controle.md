# TASK API-038 — l'entreprise entre tout de suite, et la vérification devient un contrôle

- **Créée le** : 2026-08-12.
- **Session** : `783be030-740c-4ff7-aa00-704b86f47d9b`.
- **Statut** : ✅ fait le 2026-08-12 — un recruteur qui finit son inscription reçoit son lien de mot
  de passe dans la seconde et cherche des candidats sans attendre personne. Le refus d'une entreprise
  coupe l'accès de ses recruteurs, y compris une session déjà ouverte, et le dit à la reconnexion.
- **Type** : feat. Le moment où un compte devient utilisable change ; la vérification humaine reste,
  mais elle cesse d'être une porte.
- **Tests** : fonctionnels PHPUnit — `OrganizationTest` (l'e-mail de mot de passe part à la création,
  la recherche répond sans validation), un nouveau cas de refus (accès coupé, connexion refusée), et
  la re-validation d'une entreprise refusée. `PasswordSetupTest` à réaligner sur le nouveau moment
  d'émission.
- **Réf** : `ENT-101` — les écrans qui en découlent, **aval, à faire après celle-ci** ·
  `082` / `085` / `083` — le tunnel, les champs et la file de revue tels qu'ils existent ·
  `dev-book/cadrage/entreprise/api/inscription.md` — le cadrage que cette carte réécrit ·
  `067` — le rate-limit du magic link, toujours à faire et rendu plus urgent par cette carte.

## Contexte

Aujourd'hui, quand un recruteur finit de remplir son inscription, **il ne se passe rien**. Il voit un
écran qui dit « demande reçue », il est renvoyé sur la page de connexion au bout de quinze secondes,
et il ne reçoit **aucun e-mail**. Son compte existe, il porte déjà son rôle et ses deux crédits, mais
il n'a pas de mot de passe et toutes les portes lui sont fermées.

Ce qui débloque tout, c'est un clic d'administrateur, à un moment que personne ne lui a annoncé. Ce
clic déclenche le seul e-mail qu'il recevra — celui qui lui permet enfin de choisir son mot de passe
et d'entrer.

Entre les deux, l'attente est **muette et de durée inconnue**. C'est là qu'on perd des inscriptions
déjà remplies : la personne a donné son entreprise, son secteur, son adresse, son nom — et elle
repart sans rien avoir vu du produit.

La décision : **on inverse l'ordre**. La personne entre tout de suite, et on vérifie son entreprise
pendant qu'elle utilise la plateforme. Si la vérification échoue, on lui coupe l'accès.

## Périmètre

- **L'e-mail « définissez votre mot de passe » part à la fin du formulaire d'inscription**, et non
  plus au moment où un administrateur valide.
- **La validation d'une entreprise n'ouvre plus l'accès** — il est déjà ouvert. Elle envoie un
  e-mail de courtoisie, « votre entreprise est vérifiée ».
- **Les portes fermées tant que l'entreprise n'est pas validée s'ouvrent** : la recherche, la
  consultation d'un profil, les suggestions, les recherches enregistrées, le dévoilement.
- **Le refus suspend le compte** : la personne ne peut plus rien faire, et ne peut plus se
  reconnecter. Elle voit à la connexion un message qui dit que son compte est suspendu, distinct
  d'un mot de passe faux.
- **Un refus se répare** : une entreprise refusée puis validée retrouve son accès.
- **La file de revue de l'administrateur ne change pas** — mêmes statuts, mêmes deux boutons.

## Hors périmètre

- **Les deux crédits offerts** : ils restent posés à la création et **utilisables immédiatement**,
  avant toute vérification. Décision assumée, son risque est écrit plus bas.
- **Les écrans** — l'écran de fin d'inscription et le message de suspension à la connexion sont dans
  `ENT-101`, sur la lane entreprise.
- **Les champs du formulaire**, qui ne bougent pas.
- **Les états morts** `NOUVEAU`, `EMAIL_VERIFIE` et `INCOMPLET`, que le code n'écrit nulle part et
  que cette carte ne réveille pas.
- **Le rate-limit du magic link** (`067`) : hors périmètre, mais cette carte en augmente l'enjeu —
  voir *Risques*.
- **La synchronisation Jarvi** à la validation, inchangée.

## Décisions de cadrage

### Le mot de passe garde son mécanisme, il change seulement de moment

Trois autres chemins étaient possibles : un champ de mot de passe dans le formulaire, un écran dédié
juste après l'envoi, ou pas de mot de passe du tout. **Le mécanisme existant est conservé** — le même
e-mail, la même page de définition, la même connexion automatique qui suit. Seul le déclencheur
change : la création de l'organisation au lieu de la décision de l'administrateur.

C'est le choix le plus sobre : rien à concevoir, rien à sécuriser de neuf, et le parcours reste
identique pour la personne — à ceci près qu'il ne s'interrompt plus.

### La vérification cesse d'être une condition d'accès

Les gardes qui refusent aujourd'hui tout ce qui touche aux candidats posent la même question :
*« l'entreprise est-elle validée ? »*. Elles doivent poser l'inverse : *« l'entreprise a-t-elle été
refusée ? »*. Une entreprise en cours de revue passe désormais ; une entreprise refusée ne passe
plus.

C'est un renversement, pas un assouplissement : le nombre de gardes ne change pas, ce sont les mêmes
points de contrôle qui répondent différemment.

### Le refus coupe l'accès, et il le dit

La personne ne doit pas découvrir sa suspension par une succession d'échecs inexpliqués. Deux effets
sont attendus : sa session en cours cesse de fonctionner, et sa prochaine tentative de connexion est
refusée **avec un motif reconnaissable**, distinct de « identifiants invalides ».

Ce motif remonte jusqu'à l'écran de connexion, qui l'affiche — c'est `ENT-101` qui l'affiche, cette
carte le rend disponible.

⚠️ **Le jeton de session survit à la décision.** Il est signé et porte sa propre échéance : le
révoquer côté serveur n'est pas immédiat. La coupure passe donc par les gardes, qui refusent chaque
requête d'un compte suspendu — la session devient inerte avant d'expirer.

### Deux crédits partent avant qu'un humain ait regardé

Assumé. Le parcours reste fluide et le produit se goûte tout de suite, au prix suivant : quelqu'un
qui déclare une entreprise fictive peut **dévoiler les coordonnées de deux candidats** — nom complet,
e-mail, téléphone — avant la première vérification humaine. Un refus a posteriori suspend le compte,
il **ne reprend pas ce qui a été vu**.

Deux garde-fous existent déjà et bornent l'exposition : l'inscription exige une adresse e-mail
réellement relevée (le lien de départ y est envoyé), et le plafond est de deux profils par compte.

## Règles et cas limites

- **Entreprise en revue** — accès complet, y compris le dévoilement. C'est le nouveau normal.
- **Entreprise refusée** — toute requête authentifiée est refusée, la connexion aussi.
- **Entreprise refusée puis validée** — l'accès revient sans intervention de la personne, et **sans
  nouvel e-mail de mot de passe** : celui-ci n'est plus lié à la décision.
- **Membre invité d'une entreprise refusée** — suspendu comme le propriétaire : la suspension porte
  sur l'entreprise, pas sur la personne.
- **Validation d'une entreprise déjà validée** — reste sans effet, comme aujourd'hui.
- **Personne qui appartient déjà à une entreprise** — refus inchangé (une personne, une entreprise),
  hors périmètre.
- **E-mail de mot de passe perdu ou expiré** — le lien a une durée de vie ; la personne doit pouvoir
  en redemander un. Vérifier que le chemin existe ; s'il manque, c'est une carte aval, pas celle-ci.

### ⚠️ Trois écarts au cadrage, tranchés pendant l'implémentation

- **La suspension ne pose rien sur le compte.** La carte disait « le refus suspend le compte » sans
  dire où vivait cet état. Deux chemins existaient : recopier un statut sur chaque `User`, ou laisser
  le statut de l'entreprise être la seule source. Le second l'emporte — il n'y a rien à
  resynchroniser, et la re-validation rétablit l'accès sans geste de rattrapage. Le prix est une
  requête par appel authentifié, bornée aux comptes portant un rôle entreprise.
- **Le contrôle passe par un `UserCheckerInterface`**, branché sur les trois firewalls (connexion,
  rafraîchissement, requêtes JWT). C'est le seul point que Symfony traverse à **chaque**
  authentification : sans lui, une session ouverte avant la décision aurait continué jusqu'à
  expiration de son jeton.
- **Le cadrage produit et une page d'architecture ne sont pas dans ce commit** — voir *Reste à
  faire*.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`** — ce que `validationStatus` ferme, le
  moment d'émission du lien de mot de passe, l'e-mail de vérification.
- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`** — la marque de rôle « validé » devenue
  « actif » et sa définition, ce que déclenchent `/validate` et `/reject`, le motif de refus au login.

## Risques

- **Le point d'entrée du magic link n'a aucun garde-fou** (`067`, toujours à faire) : c'est une route
  publique qui envoie un e-mail à chaque appel. Elle ouvrait jusqu'ici sur une salle d'attente ; elle
  ouvrira sur un accès réel avec deux crédits. Le besoin d'un rate-limit change d'ordre de grandeur.
- **La revue devient une charge de fond** : tant qu'elle gardait la porte, un retard ne coûtait
  qu'une attente. Elle coupe maintenant un accès déjà donné — un refus tardif est plus violent, et un
  refus jamais rendu ne protège plus rien.

## Définition de terminé

- [x] Une inscription terminée déclenche l'e-mail de définition de mot de passe.
- [x] Un compte dont l'entreprise est en revue cherche, consulte et dévoile.
- [x] La validation par un administrateur envoie « votre entreprise est vérifiée », sans lien de mot
      de passe.
- [x] Le refus rend la session inerte et refuse la connexion suivante, avec un motif distinguable.
- [x] Une entreprise refusée puis validée retrouve son accès.
- [x] Les deux pages d'architecture du contrat ne décrivent plus une porte qui n'existe plus.
      Le cadrage produit et l'arborescence entreprise sont renvoyés — cf. *Reste à faire*.

## Vérification

### Le parcours complet, joué en appels réels sur la stack de la lane

Inscription neuve, boîte mail de test, une adresse jamais vue. Neuf étapes :

```
1. POST /v1/auth/magic-link/request        201    e-mail « Poursuivez votre inscription »
2. POST /v1/auth/magic-link/consume        JWT
3. POST /v1/organizations                  201    validationStatus = EN_REVUE
   → e-mail parti AUSSITÔT                        « Définissez votre mot de passe showcase-recruiter-webapp-saas »
                                                  (aucun administrateur n'est intervenu)
4. POST /v1/auth/password-setup/consume    201
5. POST /v1/auth/login                     JWT

6. ACCÈS AVEC UNE ENTREPRISE EN REVUE
   GET /v1/search/candidates               200
   GET /v1/searches                        200
   GET /v1/suggestions                     200

7. POST /v1/organizations/{id}/reject      201
   la session DÉJÀ OUVERTE, mêmes routes :  401 · 401 · 401

8. reconnexion du compte suspendu          401  {"message":"account_suspended"}
   même compte, mot de passe faux          401  {"message":"Invalid credentials."}

9. POST /v1/organizations/{id}/validate    201
   reconnexion                             rétablie
   GET /v1/search/candidates               200
   → e-mail parti                          « Votre entreprise est vérifiée »
```

### Les tests

```
$ php bin/phpunit tests/Functional/PasswordSetupTest.php tests/Functional/OrganizationTest.php
  OK (21 tests, 132 assertions)

$ php bin/phpunit tests/Functional/SearchListsTest.php tests/Functional/SuggestionTest.php \
                 tests/Functional/SearchFavoritesScopeTest.php tests/Functional/MeTest.php
  OK (22 tests, 152 assertions)

$ php bin/phpunit tests/Functional/SearchTest.php tests/Functional/CandidateTest.php \
                 tests/Functional/SearchClosureTest.php
  OK (55 tests, 470 assertions)
```

### Le contrat

```
$ make dev-openapi && git diff --exit-code -- api/openapi.json
  aucune dérive — la surface d'API est inchangée, seul le comportement bouge
```

## Livré

| Fichier | Geste |
|---|---|
| `api/src/Security/SuspendedAccountChecker.php` | (+) refuse un recruteur d'entreprise refusée, à la connexion et sur chaque requête |
| `api/src/Domain/Organization/OrganizationValidationNotifier.php` | (+) annonce la vérification, e-mail sans lien |
| `api/config/packages/security.yaml` | (M) le checker sur les trois firewalls |
| `api/src/Entity/Organization.php` | (M) `isRejected()`, ce que les gardes interrogent désormais |
| `api/src/State/Processor/OrganizationProcessor.php` | (M) le lien de mot de passe part à la création |
| `api/src/State/Processor/OrganizationValidationProcessor.php` | (M) la validation annonce au lieu d'ouvrir |
| `api/src/Domain/Auth/PasswordSetupIssuer.php` | (M) l'e-mail n'annonce plus une validation qui n'a pas eu lieu |
| `api/src/Security/OrganizationVoter.php` · `SavedSearchVoter.php` | (M) 2 gardes renversées |
| `api/src/State/Provider/{CandidateItem,CandidateSearch,SavedSearches,Suggestion}Provider.php` | (M) 4 gardes renversées |
| `api/src/State/Processor/{SavedSearch,SavedSearchDuplicate}Processor.php` | (M) 2 gardes renversées |
| `api/tests/Functional/OrganizationTest.php` | (M) 4 cas : accès en revue, refus, re-validation, membre invité |
| `api/tests/Functional/PasswordSetupTest.php` | (M) le lien s'émet à l'inscription, plus à la validation |
| `docs/showcase-recruiter-webapp-saas/content/2.architecture/{api-routes,data-model}.md` | (M) le contrat mis au clair |

## Reste à faire (hors périmètre)

- **`dev-book/cadrage/entreprise/api/inscription.md` reste faux** — sa machine à états et son
  workflow décrivent encore la validation comme une porte. Il est écrit **pour les clients**, suit
  son propre rythme, et `/commit-tache` le tient hors de tout commit de code : sa réécriture est un
  geste de cadrage à part.
- **`docs/.../arborescence-entreprise.md`** dit « entrer se fait en quatre temps, et on ne choisit
  son mot de passe qu'à la fin ». C'est `ENT-101` qui change ces écrans, et le fichier est **déjà
  modifié par une autre session** dans le worktree entreprise : le corriger ici produirait un
  conflit.
- **`UserStatus::DISABLED` est un champ mort.** L'entité `User` le porte, l'écran d'administration
  l'affiche, et **rien ne l'écrit ni ne le lit** côté sécurité. Cette carte ne s'en sert pas — la
  suspension se lit sur l'entreprise. Soit on lui donne un sens, soit on le retire.
- **Aucun garde-fou sur l'envoi du magic link** (`067`) : route publique qui envoie un e-mail par
  appel, et qui ouvre désormais sur un accès réel avec deux crédits dépensables.
- **Le lien de mot de passe expire en 30 minutes** et rien ne permet d'en redemander un depuis
  l'écran de fin d'inscription. À vérifier côté parcours — c'est `ENT-101` qui verra le manque.
