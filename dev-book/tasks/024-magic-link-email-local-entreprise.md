# TASK 024 — Magic Link entreprise et e-mail local de dev

- **Créée le** : 2026-06-25.
- **Statut** : implémentée à relire (2026-06-25) — complétée par le lien de définition de mot de
  passe après validation entreprise.
- **Type** : auth entreprise + outillage dev e-mail.
- **Dépend de** :
  - workflow cible `dev-book/cadrage/entreprise/api/inscription.md` ;
  - tâche `023` pour le statut de validation `Organization` et la revue admin ;
  - socle API Symfony existant.
- **Réf** :
  - `dev-book/cadrage/entreprise/api/inscription.md`
  - `dev-book/cadrage/candidat/inscription.md`
  - `docker-compose.dev.yml`
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-structure.md`

## Contexte

Le workflow entreprise commence par la récupération d'une adresse e-mail. Cette adresse vient soit
d'un **formulaire**, soit d'une **connexion sociale Google ou LinkedIn** qui fournit un e-mail vérifié.
Ensuite seulement, showcase-recruiter-webapp-saas génère un Magic Link pour authentifier le déclarant et lui permettre de
créer l'entreprise en revue. Toutes les adresses e-mail sont acceptées ; la sécurité métier vient de
la validation manuelle de l'entreprise, pas du domaine e-mail.

Après validation admin de l'entreprise, showcase-recruiter-webapp-saas n'envoie pas de mot de passe en clair. Le déclarant
reçoit un lien à usage unique pour définir son mot de passe, puis utilise `POST /v1/auth/login` pour
les accès recruteur classiques.

L'environnement dev actuel ne contient pas encore de service SMTP de capture : `docker-compose.dev.yml`
ne démarre que `db`, `api`, `webapp`, et aucun `MAILER_DSN` dédié n'est câblé dans les fichiers
d'environnement API.

Cette tâche isole le sujet Magic Link / e-mail local pour éviter de mélanger auth, validation
entreprise et sync Jarvi dans la même passe.

## Décision V1

- Source de l'e-mail : formulaire ou Social Login Google / LinkedIn.
- Magic Link = mécanisme généré après obtention de l'e-mail pour authentifier le déclarant entreprise.
- Le lien est envoyé à l'adresse récupérée.
- L'e-mail est capturé localement en dev/test via un service Docker, jamais envoyé vers un SMTP réel.
- Le parcours peut reprendre une inscription `INCOMPLET` via un nouveau Magic Link.
- La validation admin de l'`Organization` émet un lien `define-password` à usage unique pour le
  déclarant `ROLE_COMPANY_OWNER`.
- Le token de définition de mot de passe est distinct du Magic Link : il ne crée pas d'entreprise,
  ne valide rien et ne s'utilise qu'une seule fois.
- Social Login Google / LinkedIn complet reste hors V1 de cette tâche, sauf récupération future de
  l'e-mail comme source d'entrée.

## État existant à préserver ou faire évoluer

### Observé dans le code actuel

- L'API expose aujourd'hui une auth JWT classique (`POST /v1/auth/login`) utilisée par les tests.
- `User.email` est unique et sert d'identifiant.
- Les invitations entreprise existent déjà avec token, mais l'envoi d'e-mail réel était hors périmètre
  de la tâche `009`.
- L'architecture prévoit `Message/` + `MessageHandler/` pour l'async : sync Jarvi, e-mails,
  notifications.
- Aucun service mail local n'est dockerisé dans `docker-compose.dev.yml`.

### À préserver

- L'auth JWT classique existante : elle reste le mode durable après définition du mot de passe.
- La séparation entre auth utilisateur et validation métier de l'entreprise.
- Les tokens d'invitation existants : ne pas les confondre avec les tokens Magic Link.
- Les tokens de définition de mot de passe : ne pas les confondre avec les tokens Magic Link ni avec
  les invitations.
- Aucun envoi SMTP externe en dev/test.

### À modifier

- Ajouter une brique SMTP locale de capture dans l'environnement dev.
- Ajouter une configuration mailer dev/test.
- Ajouter les contrats Magic Link : demande, envoi, consommation, expiration, usage unique.
- Ajouter le contrat de définition de mot de passe après validation : émission par la validation
  admin, consommation publique, expiration, usage unique.
- Adapter les tests d'inscription entreprise pour lire le lien via un fake mailer ou une capture
  contrôlée, sans dépendre d'un vrai SMTP.

## Périmètre

- Docker dev :
  - ajouter un service de capture SMTP (`mailpit` recommandé ; `mailhog` acceptable si choisi) ;
  - exposer l'UI locale ;
  - relier l'API au SMTP interne.
- Configuration API :
  - ajouter `MAILER_DSN` dev pointant vers le service mail local ;
  - ajouter une valeur test qui ne sort jamais du process de test ;
  - documenter les variables dans les `.env*.example` utiles.
- Magic Link :
  - endpoint de demande de lien ;
  - génération d'un token non prédictible ;
  - expiration ;
  - usage unique ;
  - endpoint de consommation qui authentifie l'utilisateur ou crée/récupère le compte selon le flux ;
  - reprise d'une inscription entreprise `INCOMPLET`.
- Mot de passe post-validation :
  - émission d'un lien après `POST /v1/organizations/{uuid}/validate` ;
  - endpoint de consommation du token ;
  - hash du mot de passe côté API ;
  - usage unique et refus des tokens expirés, inconnus ou déjà consommés.
- Tests :
  - demande de Magic Link ;
  - token invalide / expiré / déjà consommé ;
  - consommation valide ;
  - définition du mot de passe après validation ;
  - aucun envoi e-mail externe.
- Documentation :
  - URL de l'interface mail locale ;
  - procédure manuelle pour déclencher et lire un Magic Link ;
  - limites V1.

## Hors périmètre

- Sync Jarvi company → tâche `023`.
- Validation/refus admin de l'entreprise → tâche `023` ou tâche admin dédiée.
- Social Login Google / LinkedIn complet.
- Provider SMTP production, SPF/DKIM/DMARC, délivrabilité.
- Templates e-mail finaux brandés.
- Notifications multi-canal complètes.
- Remplacement global de tous les flux JWT existants si non nécessaire à l'inscription entreprise.

## Garde-fous

- Aucun Magic Link ne doit être envoyé vers un SMTP externe en dev/test.
- Les tokens Magic Link ne doivent jamais être stockés en clair si un hash suffit.
- Expiration courte et usage unique obligatoires.
- Le Magic Link authentifie l'utilisateur ; il ne valide pas l'entreprise.
- Le lien ne doit pas donner accès aux profils candidats tant que l'`Organization` n'est pas `VALIDEE`.
- Les logs ne doivent pas contenir de token exploitable en production.
- Aucun mot de passe temporaire ne doit être généré, envoyé ou stocké en clair.
- Le lien de définition de mot de passe ne doit être émis qu'après validation admin de
  l'`Organization`.

## Proposition d'outillage local

- Outil recommandé : `mailpit`.
- SMTP interne Docker : `mailpit:1025`.
- UI locale proposée : `http://localhost:18025`.
- DSN dev proposé : `smtp://mailpit:1025`.

## Endpoints ajoutés

| Méthode | Route | Rôle | Effet |
| --- | --- | --- | --- |
| POST | `/v1/auth/magic-link/request` | public | reçoit `{email}`, crée/récupère le `User`, génère un token hashé et envoie l'e-mail |
| POST | `/v1/auth/magic-link/consume` | public | reçoit `{token}`, consomme le token une seule fois et retourne un JWT showcase-recruiter-webapp-saas |
| POST | `/v1/auth/password-setup/consume` | public | reçoit `{token,password}`, consomme le token une seule fois et définit le mot de passe |

Réponse de `consume` :

```json
{
  "token": "jwt-showcase-recruiter-webapp-saas",
  "email": "declarant@entreprise.test",
  "userId": "uuid-user"
}
```

Le JWT retourné est celui à utiliser ensuite sur :

```http
POST /v1/organizations
Authorization: Bearer <token>
```

Après validation admin de l'entreprise, l'e-mail de définition de mot de passe pointe vers :

```text
http://localhost:3000/auth/define-password?token=<token>
```

Consommation API :

```json
{
  "token": "password-setup-token",
  "password": "password"
}
```

Réponse :

```json
{
  "passwordSet": true
}
```

Le déclarant utilise ensuite `POST /v1/auth/login` avec son e-mail et le mot de passe défini.

## Questions à confirmer

- Durée d'expiration V1 : 15 min, 30 min, ou 1 h ?
- Reprise `INCOMPLET` : quel écran/URL webapp cible le lien ?

## Critères d'acceptation

- [x] `docker-compose.dev.yml` expose un service mail local de capture utilisable par l'API.
- [x] `MAILER_DSN` dev/test est configuré pour ne jamais envoyer de Magic Link vers l'extérieur.
- [x] Un test manuel documenté permet de déclencher un Magic Link et de le lire dans l'UI mail locale.
- [x] Un Magic Link valide authentifie l'utilisateur attendu.
- [x] Un token expiré, inconnu ou déjà consommé est refusé.
- [x] Une inscription entreprise peut reprendre après Magic Link sans valider automatiquement
  l'`Organization`.
- [x] La validation admin émet un lien de définition de mot de passe pour le déclarant.
- [x] Un token de définition de mot de passe expiré, inconnu ou déjà consommé est refusé.
- [x] Les tests n'ont pas besoin d'un vrai SMTP.
- [x] Les docs d'environnement mentionnent l'URL mail locale et les ports.

## Validations prévues

- Tests ciblés auth Magic Link.
- Tests ciblés définition de mot de passe.
- Tests fonctionnels d'inscription entreprise avec fake mailer ou capture contrôlée.
- Vérification manuelle en dev via l'UI mail locale.
- Avant PR : quality gates API du projet (`make api-quality` ou équivalent disponible).
