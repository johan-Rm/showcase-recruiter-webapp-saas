# Invitation d'un membre d'equipe Entreprise

## Principe

L'invitation d'un membre est distincte de l'inscription initiale du declarant.

Le declarant cree l'entreprise, passe par le Magic Link d'inscription, puis definit son mot de
passe apres validation admin. Un membre invite, lui, rejoint une entreprise deja existante via un
lien d'invitation envoye par e-mail.

Par consequent :

* L'owner invite un membre depuis l'espace entreprise.
* showcase-recruiter-webapp-saas cree une `Invitation` en statut `PENDING`.
* showcase-recruiter-webapp-saas envoie un e-mail contenant un lien tokenise.
* Le membre invite ouvre le lien, renseigne son identite et definit son mot de passe.
* Le compte recruteur membre est cree puis rattache a l'`Organization`.
* Les membres invites restent internes a showcase-recruiter-webapp-saas et ne sont pas pushes chez Jarvi.

---

# Workflow d'invitation

```text
                 ┌───────────────────────────────┐
                 │ Owner invite un membre        │
                 └───────────────┬───────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Creation Invitation      │
                    │ statut : PENDING         │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Envoi e-mail             │
                    │ lien /invitation/:token  │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Ouverture du lien        │
                    │ par le membre invite     │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Lecture du contexte      │
                    │ public de l'invitation   │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Saisie prenom, nom,      │
                    │ mot de passe             │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Compte membre cree       │
                    │ invitation ACCEPTED      │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    Connexion classique
                    email + mot de passe
```

---

# Informations demandees

```text
Invitation
├── E-mail du futur membre
└── Role demande

Membre invite
├── Prenom
├── Nom
├── Mot de passe
└── Confirmation du mot de passe
```

---

# Routes API

| Methode | Route | Acces | Role |
| --- | --- | --- | --- |
| `POST` | `/v1/organizations/{organizationId}/invitations` | prive | owner entreprise |
| `GET` | `/v1/organizations/{organizationId}/invitations` | prive | owner entreprise |
| `GET` | `/v1/invitations/{token}` | public | lien tokenise |
| `POST` | `/v1/invitations/{token}/accept` | public | lien tokenise |

## Creation d'invitation

```json
{
  "email": "member@showcase-recruiter-webapp-saas.test",
  "role": "ROLE_COMPANY_MEMBER"
}
```

La V1 borne les invitations au role `ROLE_COMPANY_MEMBER`.

## Contexte public d'invitation

Le contexte public sert a afficher une page d'acceptation sans authentification prealable.

```json
{
  "email": "member@showcase-recruiter-webapp-saas.test",
  "organizationName": "Atlas Talent Conseil",
  "role": "ROLE_COMPANY_MEMBER",
  "expiresAt": "2026-07-07T20:37:58+00:00"
}
```

Le contexte ne doit exposer aucune information sensible autre que le minimum necessaire a la page
d'acceptation.

## Acceptation d'invitation

```json
{
  "givenName": "Nadia",
  "familyName": "Alaoui",
  "password": "password"
}
```

---

# Etats possibles

```text
[PENDING]
     │
     ├── Token expire
     ▼
[EXPIRED]

[PENDING]
     │
     ├── Acceptation valide
     ▼
[ACCEPTED]
```

Un token inconnu renvoie `404`.

Une invitation expiree ou deja acceptee renvoie `410`.

---

# Regles produit

* Le lien d'invitation est securise, tokenise, usage unique et expirant.
* Le lien d'invitation ne remplace pas le mot de passe.
* Le membre invite doit definir un mot de passe a l'acceptation pour pouvoir se reconnecter.
* L'e-mail contient le lien vers l'espace entreprise, par exemple
  `https://entreprise.showcase-recruiter-webapp-saas.example/invitation/{token}`.
* Le token retourne directement par l'API reste un raccourci de test V1 ; le parcours produit cible
  passe par l'e-mail.
* Une invitation acceptee cree puis active un compte recruteur rattache a l'`Organization`.
* L'acceptation d'une invitation membre ne declenche aucun push Jarvi.
* Les membres invites restent des comptes showcase-recruiter-webapp-saas internes a l'`Organization`.
* Une adresse deja membre de l'organisation ne cree pas de nouvelle invitation.
* Une invitation deja `PENDING` pour le meme couple `(organization, email)` reste refusee.

---

# Retrait et reactivation

Un membre n'est pas supprime physiquement lorsqu'un owner le retire de l'equipe.
Le systeme passe son `Recruiter.status` a `DISABLED`.

Cette regle protege les historiques metier qui referencent le membre :

* invitations envoyees ;
* ajouts a la short list ;
* profils devoiles ;
* transactions de credits ;
* assignations de candidats ;
* notes et consultations ;
* recherches sauvegardees.

La reactivation est une action metier distincte.

```text
Membre ACTIVE
     │
     ├── Retrait par owner
     ▼
Membre DISABLED
     │
     ├── Reactivation par owner
     ▼
Membre ACTIVE
```

La reinvitation sert uniquement a creer un nouveau membre qui n'a pas encore accepte d'invitation.
Elle ne doit pas servir a reactiver un membre existant ni a recreer son mot de passe.

Pour un mot de passe oublie, utiliser le reset password.

---

# Conclusion

L'invitation membre est un parcours d'entree dans une entreprise deja validee.

Elle sert a creer un compte recruteur interne showcase-recruiter-webapp-saas, rattache a une `Organization`, avec un role
borne et une trace d'audit. Elle ne cree pas de contact Jarvi et ne modifie pas la synchronisation
showcase-recruiter-webapp-saas -> Jarvi de l'entreprise.
