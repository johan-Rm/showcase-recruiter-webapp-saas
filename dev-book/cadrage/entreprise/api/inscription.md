# Validation des comptes Entreprise

## Principe

L'objectif n'est **pas** de vérifier si l'utilisateur possède une adresse e-mail professionnelle.

L'objectif est de vérifier que **l'entreprise est légitime** avant de lui donner accès aux profils candidats.

Par conséquent :

* ✅ Toutes les adresses e-mail sont acceptées (`@gmail.com`, `@outlook.com`, `@entreprise.com`, etc.).
* ✅ L'inscription peut commencer par une saisie d'e-mail ou un Social Login (Google / LinkedIn).
* ✅ Dans tous les cas, **l'inscription démarre par un Magic Link envoyé à l'adresse e-mail de l'utilisateur**.
* ✅ Les informations de l'entreprise sont contrôlées.
* ✅ Le compte est placé en revue avant validation définitive.
* ✅ Après validation, le déclarant reçoit un lien à usage unique pour **définir son mot de passe**.

---

# Si vous avez déjà un compte

Vous pouvez saisir votre adresse à tout moment, sans risque de créer un doublon.

* Si un compte existe déjà pour cette adresse, le lien de connexion reçu par e-mail vous ramène
  **directement à votre espace** — inutile de recommencer l'inscription.
* Une même personne ne peut être rattachée qu'à **une seule entreprise**.

Pour votre sécurité, l'écran de saisie reste identique quelle que soit l'adresse : la confirmation
qu'un compte existe se fait **uniquement dans votre boîte mail**, jamais à l'écran.

---

# Workflow d'inscription

```text
                 ┌───────────────────────────────┐
                 │ Début de l'inscription         │
                 └───────────────┬───────────────┘
                                 │
              ┌──────────────────┴──────────────────┐
              │                                     │
              ▼                                     ▼
   Saisie d'une adresse e-mail         Google / LinkedIn
                                              │
                                              ▼
                                 Récupération de l'e-mail
              └──────────────────┬──────────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Envoi d'un Magic Link    │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Authentification         │
                    │ via Magic Link           │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Saisie des informations  │
                    │ entreprise               │
                    └────────────┬─────────────┘
                                 │
                 ┌───────────────┴────────────────┐
                 │                                │
                 ▼                                ▼
      Formulaire complété             Formulaire abandonné
                 │                                │
                 ▼                                ▼
      Statut : EN REVUE              Statut : INCOMPLET
                 │                                │
                 ▼                                │
      Validation manuelle                         │
            par un admin                          │
                 │                                │
      ┌──────────┴──────────┐                     │
      │                     │                     │
      ▼                     ▼                     │
ENTREPRISE VALIDÉE   ENTREPRISE REFUSÉE           │
      │                                           │
      ▼                                           │
Envoi d'un lien                       L'administrateur peut
définir mon mot de passe              renvoyer un Magic Link
      │
      ▼
Mot de passe défini
      │
      ▼
Connexion automatique
      │
      ▼
Accès complet
à la plateforme
                                      pour reprendre
                                      l'inscription.
```

---

# Informations demandées

```text
Entreprise
├── Nom de l'entreprise
├── Secteur d'activité
├── Ville
├── Pays
└── Provenance (comment l'entreprise a connu showcase-recruiter-webapp-saas)

Personne de contact
├── Prénom
└── Nom
```

Toutes ces informations sont obligatoires : l'inscription part dès qu'elles sont réunies.

Le site web, le numéro d'immatriculation et la description ne sont plus demandés à l'inscription.
L'entreprise les renseigne depuis son espace une fois l'accès ouvert. En cas de doute lors de la
revue, l'équipe peut les demander par retour d'e-mail.

---

# Provenance de l'entreprise

À l'inscription, l'entreprise indique **par quel canal elle a connu showcase-recruiter-webapp-saas**.

Le champ est demandé dans le formulaire « Saisie des informations entreprise » (même écran que les
autres informations, après authentification par Magic Link).

**Valeurs proposées** (liste fermée + précision libre) :

```text
Provenance
├── Recommandation / bouche-à-oreille
├── LinkedIn
├── Recherche web
├── Salon ou événement
├── Contact direct showcase-recruiter-webapp-saas
└── Autre (champ libre pour préciser)
```

**Règles** :

* Champ **obligatoire** — une seule valeur.
* Si « Autre » : la précision en texte libre est obligatoire.
* La valeur est **figée à l'inscription** : elle n'est pas modifiable ensuite par l'entreprise
  (donnée d'acquisition, pas donnée de profil).

**Usages** :

* **Revue admin** : la provenance est affichée dans la fiche de validation — un canal identifiable
  (recommandation nominative, contact direct) est un signal de légitimité supplémentaire.
* **Suivi d'acquisition** : mesurer quels canaux amènent des entreprises (et lesquelles sont
  validées vs refusées).

**Hors périmètre** : la provenance est une donnée interne showcase-recruiter-webapp-saas — elle n'est **pas** incluse
dans le push unique vers Jarvi.

---

# Invitation d'un membre d'équipe

Le parcours d'invitation d'un membre est distinct de l'inscription initiale du déclarant.

Il est cadré dans [invitation_membre.md](./invitation_membre.md).

---

# Synchronisation Jarvi

**Push unique, aucune synchro ensuite.** À la **validation** de l'entreprise (fin du parcours
d'inscription), showcase-recruiter-webapp-saas effectue **un seul push** de l'entreprise vers Jarvi (création de
l'organisation côté Jarvi). **Il n'y a ensuite aucune synchronisation** : les modifications ultérieures
de l'entreprise côté showcase-recruiter-webapp-saas ne sont **pas** re-poussées vers Jarvi. Le push est **non bloquant**
(une erreur Jarvi n'empêche pas la validation et peut être retentée).

---

# États possibles

```text
[NOUVEAU]
     │
     ▼
[E-MAIL VÉRIFIÉ]
     │
     ├────────────► [INCOMPLET]
     │                  │
     │                  └──► Magic Link renvoyé par un admin
     │                        pour reprendre l'inscription
     │
     ▼
[EN REVUE]
     │
     ├────────────► [REFUSÉ]
     │
     ▼
[ENTREPRISE VALIDÉE]
     │
     ▼
[MOT DE PASSE À DÉFINIR]
     │
     ▼
[ACCÈS COMPLET]
```

---

# Pourquoi accepter Gmail ?

Un e-mail personnel ne signifie pas que l'entreprise est illégitime.

Exemples :

```text
✅ jean@gmail.com
   Société : Tech Solutions SARL
   Site web : https://techsolutions.ma
   → Peut être parfaitement valide

✅ recrutement@startup.io
   Société : Startup.io
   → Valide également

❌ rh@entreprise-fictive.com
   Société inexistante
   → À refuser malgré un domaine "professionnel"
```

---

# Ce qui compte réellement

```text
                 Légitimité de l'entreprise
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
 Authentification   Infos cohérentes   Validation admin
 initiale Magic Link                       humaine
        │                  │                  │
        └──────────────────┴──────────────────┘
                           │
                           ▼
          Lien de définition du mot de passe
                           │
                           ▼
                 Autorisation d'accès
```

---

# Conclusion

Le domaine de l'adresse e-mail (`gmail.com`, `outlook.com`, `entreprise.com`, etc.) est **un simple indicateur**, mais **jamais une preuve**.

La sécurité repose principalement sur :

* Une authentification initiale par **Magic Link**, quel que soit le mode d'inscription.
* Un mot de passe défini uniquement après validation de l'entreprise, via un token à usage unique.
* La collecte des informations de l'entreprise.
* Une revue et une validation manuelles par un administrateur.
* Aucun accès aux profils candidats tant que l'entreprise n'a pas été validée.
