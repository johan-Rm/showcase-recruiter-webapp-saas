# Validation des comptes Candidat

## Principe

Contrairement à l'espace Entreprise, **le compte LinkedIn est obligatoire**.

L'objectif est de s'assurer que chaque candidat dispose d'une identité professionnelle minimale avant
de rejoindre la plateforme, tout en rattachant son compte à un profil Jarvi existant quand ce profil a
déjà été importé dans showcase-recruiter-webapp-saas.

Par conséquent :

* ✅ La connexion débute obligatoirement par **LinkedIn**.
* ✅ L'adresse e-mail récupérée depuis LinkedIn est utilisée pour envoyer un **Magic Link**.
* ✅ Le **Magic Link reste l'unique mécanisme d'authentification**.
* ✅ Une fois authentifié, le candidat complète progressivement son profil.
* ✅ Si le profil existe déjà dans la réplica showcase-recruiter-webapp-saas issue de Jarvi, l'inscription rattache le
  compte candidat à cette projection.
* ✅ Si aucun profil Jarvi/showcase-recruiter-webapp-saas ne correspond, showcase-recruiter-webapp-saas crée un profil Jarvi minimal puis le
  récupère ensuite par ingestion descendante.
* ✅ Les données showcase-recruiter-webapp-saas (consentement, visibilité, présence Maroc, projet, prétentions) restent
  locales et ne sont pas synchronisées vers Jarvi.
* ✅ Le profil peut ensuite être soumis à une revue interne avant publication.

---

# Workflow d'inscription

```text
                 ┌───────────────────────────────┐
                 │ Début de l'inscription         │
                 └───────────────┬───────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Connexion LinkedIn       │
                    │ (obligatoire)            │
                    └────────────┬─────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────┐
                    │ Récupération de          │
                    │ l'adresse e-mail         │
                    └────────────┬─────────────┘
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
                    │ Onboarding candidat      │
                    │ (complétion du profil)   │
                    └────────────┬─────────────┘
                                 │
                 ┌───────────────┴────────────────┐
                 │                                │
                 ▼                                ▼
      Profil complété                 Profil abandonné
                 │                                │
                 ▼                                ▼
      Statut : EN REVUE              Statut : INCOMPLET
                 │                                │
                 ▼                                │
      Validation interne                         │
        (optionnelle)                            │
                 │                                │
      ┌──────────┴──────────┐                     │
      │                     │                     │
      ▼                     ▼                     │
PROFIL VALIDÉ         PROFIL REFUSÉ              │
      │                                           │
      ▼                                           │
Publication                         Un administrateur peut
sur la plateforme                    renvoyer un Magic Link
                                     afin de reprendre
                                     l'onboarding.
```

## Rattachement au profil

L'inscription doit gérer deux cas.

### Cas A — profil déjà importé depuis Jarvi

Le candidat existe déjà dans showcase-recruiter-webapp-saas parce que son profil professionnel a été récupéré depuis Jarvi
par l'ingestion régulière.

Le Magic Link doit alors :

* authentifier l'e-mail du candidat ;
* retrouver la projection `Candidate` existante via une clé fiable (`jarviProfileId`, e-mail,
  LinkedIn ou règle de rapprochement validée) ;
* créer ou rattacher le `User` showcase-recruiter-webapp-saas au `Person`/`Candidate` existant ;
* demander le consentement et la visibilité showcase-recruiter-webapp-saas ;
* laisser le profil professionnel maître chez Jarvi.

### Cas B — profil absent de showcase-recruiter-webapp-saas / Jarvi

Le candidat n'a pas encore de profil dans la réplica showcase-recruiter-webapp-saas.

Le Magic Link doit alors :

* créer le compte showcase-recruiter-webapp-saas ;
* créer une projection `Candidate` locale ;
* créer un profil Jarvi minimal pour obtenir l'ancre `jarviProfileId` ;
* laisser l'ingestion descendante remplir ensuite `candidate_jarvi` ;
* stocker les données showcase-recruiter-webapp-saas localement uniquement.

Dans les deux cas, le consentement showcase-recruiter-webapp-saas est obligatoire avant publication ou exposition aux
recruteurs.

---

# Informations minimales requises

Avant l'onboarding :

```text
Authentification
├── Compte LinkedIn
└── Adresse e-mail (récupérée via LinkedIn)
```

Ces deux éléments suffisent pour créer le compte et envoyer le Magic Link.

---

# Informations collectées durant l'onboarding

```text
Profil candidat
├── Photo (optionnelle)
├── Titre professionnel
├── Localisation actuelle
├── Nationalité(s)
├── CV
├── Expériences professionnelles
├── Formation
├── Compétences clés
├── Langues parlées
├── Niveau d'études
├── Prétentions salariales
├── Disponibilité
├── Mobilité géographique
├── Projet professionnel au Maroc
├── Date envisagée d'installation
├── Présentation libre
├── Provenance (comment le candidat a connu showcase-recruiter-webapp-saas)
└── Consentements obligatoires
```

Tous ces champs peuvent être enrichis progressivement, mais un minimum sera requis avant publication.

---

# Provenance du candidat

À l'inscription, le candidat indique **par quel canal il a connu showcase-recruiter-webapp-saas**.

Le champ est demandé **au début de l'onboarding** (première complétion du profil, juste après
l'authentification par Magic Link) : contrairement au reste du profil, il n'a pas vocation à être
enrichi progressivement.

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
* **Cas particulier — invitation** : quand le candidat arrive par un Magic Link d'invitation
  (profil déjà importé depuis Jarvi, cf. Cas A), la provenance est préremplie à
  « Contact direct showcase-recruiter-webapp-saas » et reste modifiable avant validation.
* La valeur est **figée après l'onboarding** : elle n'est pas modifiable ensuite par le candidat
  (donnée d'acquisition, pas donnée de profil).

**Usages** :

* **Revue interne** : contexte d'arrivée du candidat lors de la validation (si activée).
* **Suivi d'acquisition** : mesurer quels canaux amènent des candidats (et lesquels aboutissent à
  un profil publié).

**Hors périmètre** : comme les autres données showcase-recruiter-webapp-saas (consentement, visibilité, projet…), la
provenance reste **locale** et n'est **jamais synchronisée vers Jarvi**.

---

# États possibles

```text
[NOUVEAU]
     │
     ▼
[LINKEDIN + E-MAIL VALIDÉS]
     │
     ├────────────► [INCOMPLET]
     │                  │
     │                  └──► Magic Link renvoyé
     │                        pour reprendre
     │                        l'onboarding
     │
     ▼
[EN REVUE]
     │
     ├────────────► [REFUSÉ]
     │
     ▼
[PROFIL VALIDÉ]
```

---

# Pourquoi imposer LinkedIn ?

LinkedIn permet de disposer dès le départ :

* d'une identité professionnelle ;
* d'une adresse e-mail vérifiée ;
* d'un parcours potentiellement prérempli ;
* d'une meilleure qualité globale des profils.

Le Magic Link reste néanmoins le **seul mécanisme d'authentification** utilisé par showcase-recruiter-webapp-saas.

---

# Ce qui compte réellement

```text
                   Qualité du profil candidat
                               │
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
        ▼                      ▼                      ▼
Compte LinkedIn         Profil complété      Validation interne
      obligatoire          et cohérent          (si activée)
        │                      │                      │
        └──────────────────────┴──────────────────────┘
                               │
                               ▼
                  Publication sur showcase-recruiter-webapp-saas
```

---

# Conclusion

Le processus candidat repose sur les principes suivants :

* Compte **LinkedIn obligatoire** pour démarrer l'inscription.
* Authentification **100 % Magic Link**.
* Onboarding progressif après authentification.
* Possibilité d'interrompre puis de reprendre le parcours grâce à un nouveau Magic Link.
* Validation interne facultative avant publication du profil sur la plateforme.
