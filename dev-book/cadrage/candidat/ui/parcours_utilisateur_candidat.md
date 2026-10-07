# showcase-recruiter-webapp-saas — Parcours Utilisateur Candidat

## Objectif

Comprendre les usages réels d'un candidat afin de concevoir un espace personnel simple, utile et centré sur son projet de retour professionnel au Maroc.

* * *

# Principe Fondamental

Le recruteur vient sur showcase-recruiter-webapp-saas pour chercher.

Le candidat vient sur showcase-recruiter-webapp-saas pour être trouvé.

Contrairement aux recruteurs, la majorité des candidats ne se connecteront pas quotidiennement. L'objectif de la plateforme est donc de leur permettre de maintenir un profil attractif et à jour tout en maximisant leur visibilité auprès des entreprises marocaines.

* * *

# Parcours d'Inscription

> **Prérequis — authentification.** L'inscription démarre par une **connexion LinkedIn obligatoire** ; l'e-mail récupéré sert à envoyer un **Magic Link**, seul mécanisme d'authentification de showcase-recruiter-webapp-saas. Le candidat ne complète son profil qu'une fois authentifié, et le **consentement RGPD** est requis avant toute publication. Workflow détaillé (rattachement au profil Jarvi, états) → [api/inscription.md](../api/inscription.md).

## 1. Compléter son profil

Action la plus importante du parcours candidat.

**Informations renseignées**

*   CV
*   Expériences professionnelles
*   Compétences
*   Langues
*   Pays actuel
*   Ville actuelle
*   Situation familiale (si pertinent)
*   Secteur recherché
*   Fonction recherchée

> **Qui possède quoi.** Le **profil professionnel** (CV, expériences, formations, compétences, langues, identité) est **maître chez Jarvi** : côté showcase-recruiter-webapp-saas il est **affiché en lecture seule** depuis la réplica locale, il n'est pas ressaisi. Les données ci-dessous — **projet de retour, prétentions, disponibilité, présence Maroc, visibilité, situation familiale** — sont **maîtres showcase-recruiter-webapp-saas** et stockées localement. Frontière détaillée → [vue_d_ensemble.md](../vue_d_ensemble.md).

## 2. Définir son projet de retour au Maroc

C'est l'une des données différenciantes de showcase-recruiter-webapp-saas.

**Exemples**

*   Retour prévu dans 3 mois
*   Retour prévu dans 6 mois
*   Retour prévu dans 1 an
*   Projet encore en réflexion

Cette information permet aux recruteurs de mieux anticiper leurs besoins de recrutement.

## 3. Définir ses prétentions

**Informations**

*   Salaire souhaité
*   Package minimum accepté
*   Mobilité géographique

## 4. Définir sa disponibilité

**Exemples**

*   Disponible immédiatement
*   Préavis de 1 mois
*   Préavis de 3 mois

* * *

# Utilisation Régulière

## 5. Mettre à jour son profil

Le candidat enrichit son dossier au fil du temps.

**Exemples**

*   Ajouter une expérience
*   Modifier ses prétentions salariales
*   Ajouter une compétence
*   Mettre à jour son CV

## 6. Déclarer ses prochains voyages au Maroc

Donnée à très forte valeur pour les recruteurs.

**Exemple**

"Je serai à Casablanca du 10 au 25 août."

Cette information permet l'organisation d'entretiens physiques pendant un séjour déjà programmé.

## 7. Gérer sa visibilité

Le candidat garde le contrôle sur l'exposition de son profil.

**Options possibles**

*   Visible
*   Masqué
*   Visible uniquement pour certaines entreprises
*   Visible uniquement pour les recruteurs premium

> **Consentement RGPD.** Toute exposition d'un profil aux recruteurs suppose le **consentement explicite** du candidat, obligatoire avant publication. Visibilité et consentement sont horodatés et relèvent de la base légale / rétention (à cadrer).

## 8. Consulter l'activité autour de son profil

Le candidat mesure l'intérêt généré par son profil.

**Exemples**

*   15 consultations
*   3 déblocages de profil
*   1 entreprise intéressée

Selon le niveau d'abonnement, l'identité des entreprises peut être masquée ou visible.

## 9. Recevoir des notifications

**Exemples**

*   Votre profil apparaît dans 12 recherches cette semaine.
*   Une entreprise a débloqué votre profil.
*   Votre profil est recommandé dans une sélection showcase-recruiter-webapp-saas.
*   Une entreprise consulte régulièrement votre profil.

* * *

# Fonctionnalités Premium

## 10. Mise en avant du profil

Le candidat peut augmenter sa visibilité.

**Options possibles**

*   Badge Premium
*   Mise en avant sur la page d'accueil
*   Remontée dans les résultats de recherche
*   Mise en avant dans les sélections recruteurs

## 11. Accompagnement personnalisé

**Services possibles**

*   Optimisation CV
*   Préparation entretien
*   Coaching retour au Maroc
*   Conseil carrière

## 12. Candidature assistée

Fonctionnalité envisageable dans une version ultérieure.

**Exemples**

*   Candidature en un clic
*   Diffusion ciblée vers des recruteurs
*   Recommandation automatique de profils

* * *

# Synthèse V1

## Les 7 actions cœur du produit

1.  Créer son compte
2.  Compléter son profil
3.  Déposer son CV
4.  Définir son projet de retour au Maroc
5.  Définir ses prétentions salariales
6.  Déclarer ses prochains séjours au Maroc
7.  Recevoir des notifications

* * *

# Différenciateur showcase-recruiter-webapp-saas

## La donnée stratégique

Ce qui crée réellement de la valeur n'est pas uniquement le CV.

La donnée la plus différenciante est :

**"Quand cette personne sera physiquement présente au Maroc ?"**

**Exemple**

Directeur Supply Chain basé à Lyon.

Présent à Casablanca du 12 au 20 septembre.

Pour une entreprise marocaine, cette information peut avoir autant de valeur que le CV lui-même puisqu'elle permet d'organiser immédiatement des entretiens physiques pendant un séjour déjà prévu.

Cette donnée est aujourd'hui très peu exploitée par les plateformes de recrutement traditionnelles et constitue un avantage concurrentiel fort pour showcase-recruiter-webapp-saas.

# Conclusion Produit

La V1 de l'espace candidat doit rester extrêmement simple.

L'objectif n'est pas de créer un portail emploi complexe mais de permettre à un professionnel basé en Europe de présenter son projet de retour au Maroc, de rester visible auprès des recruteurs et d'être contacté au bon moment.
